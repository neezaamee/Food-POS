<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FbrSetting;
use App\Models\FbrSubmission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SaaS\FeatureAccessService;
use App\Services\SaaS\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        $tenantId = app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();

        $query = User::with('roles')
            ->when(! auth()->user()?->isSuperAdmin() || $tenantId, function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->storeStaff();
            })
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::whereNotIn('slug', ['super-admin', 'super_admin'])->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function storeUser(Request $request)
    {
        $tenantId = app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();

        if ($tenantId && ! app(FeatureAccessService::class)->hasQuota($tenantId, 'users')) {
            return redirect()->back()->with('error', 'You have reached the maximum staff user limit allowed by your subscription plan. Please upgrade to add more staff.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'role' => 'required|string|not_in:super-admin,super_admin',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => 'active',
            'password' => Hash::make($request->password),
        ]);

        $roleRecord = Role::where('slug', $request->role)->first();
        if ($roleRecord) {
            $user->roles()->sync([$roleRecord->id]);
        }

        return redirect()->back()->with('success', 'Staff account created successfully.');
    }

    public function updateUser(Request $request, $id)
    {
        $tenantId = app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();

        $user = auth()->user()?->isSuperAdmin()
            ? User::findOrFail($id)
            : User::where('tenant_id', $tenantId)->storeStaff()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:50',
            'role' => 'required|string|not_in:super-admin,super_admin',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $roleRecord = Role::where('slug', $request->role)->first();
        if ($roleRecord) {
            $user->roles()->sync([$roleRecord->id]);
        }

        return redirect()->back()->with('success', 'User account updated successfully.');
    }

    public function destroyUser($id)
    {
        $tenantId = app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();

        $user = auth()->user()?->isSuperAdmin()
            ? User::findOrFail($id)
            : User::where('tenant_id', $tenantId)->storeStaff()->findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Staff account removed successfully.');
    }

    public function profile()
    {
        $user = auth()->user();

        return view('admin.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => 'nullable|min:6|confirmed',
        ]);

        if ($request->filled('new_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return redirect()->back()->withErrors(['current_password' => 'Current password does not match our records.']);
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function settings()
    {
        $settings = SystemSetting::all()->pluck('value', 'key')->toArray();

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'restaurant_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        // Handle logo file upload
        if ($request->hasFile('restaurant_logo')) {
            $file = $request->file('restaurant_logo');
            $extension = $file->getClientOriginalExtension() ?: 'png';
            $filename = 'logo_'.time().'.'.$extension;
            $destination = public_path('uploads/branding');

            if (! file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->move($destination, $filename);
            SystemSetting::set('restaurant_logo', 'uploads/branding/'.$filename, 'branding');
        } elseif ($request->boolean('remove_logo')) {
            $oldLogo = SystemSetting::where('key', 'restaurant_logo')->value('value');
            if ($oldLogo && file_exists(public_path($oldLogo))) {
                @unlink(public_path($oldLogo));
            }
            SystemSetting::where('key', 'restaurant_logo')->delete();
        }

        $data = $request->except(['_token', 'restaurant_logo', 'remove_logo']);

        foreach ($data as $key => $val) {
            SystemSetting::set($key, (string) $val, 'general');
        }

        return redirect()->back()->with('success', 'System settings saved successfully.');
    }

    public function fbr()
    {
        $setting = FbrSetting::first() ?? new FbrSetting([
            'pos_id' => 'FOODPOINT-POS-01',
            'api_url' => 'https://sandbox.fbr.gov.pk/api/v1/invoice',
            'mode' => 'sandbox',
            'is_enabled' => false,
        ]);

        $submissions = FbrSubmission::with('order')->latest()->paginate(15);

        return view('admin.fbr', compact('setting', 'submissions'));
    }

    public function updateFbr(Request $request)
    {
        $request->validate([
            'pos_id' => 'required|string',
            'bearer_token' => 'nullable|string',
            'api_url' => 'required|url',
            'mode' => 'required|in:sandbox,live',
        ]);

        $setting = FbrSetting::first() ?? new FbrSetting;
        $setting->pos_id = $request->pos_id;
        $setting->bearer_token = $request->bearer_token;
        $setting->api_url = $request->api_url;
        $setting->mode = $request->mode;
        $setting->is_enabled = $request->has('is_enabled');
        $setting->save();

        return redirect()->back()->with('success', 'FBR Digital Invoicing configuration updated.');
    }

    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate(25)->withQueryString();
        $users = User::all();

        return view('admin.audit-logs', compact('logs', 'users'));
    }
}
