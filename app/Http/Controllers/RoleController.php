<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Services\SaaS\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * Display a listing of all roles available to the tenant.
     */
    public function index(Request $request)
    {
        $tenantId = app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();
        $isSuperAdmin = auth()->user()?->isSuperAdmin() && empty($tenantId);

        $query = Role::query()
            ->withCount('users')
            ->with('permissions')
            ->when(! $isSuperAdmin, function ($q) use ($tenantId) {
                $q->forTenant($tenantId)
                    ->whereNotIn('slug', ['super-admin', 'super_admin']);
            })
            ->orderBy('is_system', 'desc')
            ->orderBy('name', 'asc');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $roles = $query->paginate(15)->withQueryString();
        $totalPermissions = Permission::count();

        return view('admin.roles.index', compact('roles', 'totalPermissions'));
    }

    /**
     * Show the form for creating a new custom role.
     */
    public function create()
    {
        $groupedPermissions = Permission::groupedByModule();

        return view('admin.roles.create', compact('groupedPermissions'));
    }

    /**
     * Store a newly created custom role in storage.
     */
    public function store(Request $request)
    {
        $tenantId = app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $baseSlug = Str::slug($request->name);
        if (empty($baseSlug)) {
            $baseSlug = 'role-'.Str::random(6);
        }

        // Check for slug conflicts within tenant or with system roles
        $slug = $baseSlug;
        $counter = 1;
        while (Role::forTenant($tenantId)->where('slug', $slug)->exists() || in_array($slug, ['super-admin', 'super_admin', 'owner', 'admin'])) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $role = Role::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'is_system' => false,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        AuditLog::record(
            auth()->user(),
            'Role Created',
            "Created custom role '{$role->name}' with {$role->permissions()->count()} permissions.",
            'Roles',
            $role->id
        );

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' created successfully.");
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit($id)
    {
        $tenantId = app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();
        $isSuperAdmin = auth()->user()?->isSuperAdmin() && empty($tenantId);

        $role = Role::with('permissions')
            ->when(! $isSuperAdmin, function ($q) use ($tenantId) {
                $q->forTenant($tenantId)
                    ->whereNotIn('slug', ['super-admin', 'super_admin']);
            })
            ->findOrFail($id);

        $groupedPermissions = Permission::groupedByModule();
        $assignedPermissionIds = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'groupedPermissions', 'assignedPermissionIds'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, $id)
    {
        $tenantId = app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();
        $isSuperAdmin = auth()->user()?->isSuperAdmin() && empty($tenantId);

        $role = Role::when(! $isSuperAdmin, function ($q) use ($tenantId) {
            $q->forTenant($tenantId)
                ->whereNotIn('slug', ['super-admin', 'super_admin']);
        })->findOrFail($id);

        $request->validate([
            'name' => $role->isSystem() ? 'nullable|string|max:100' : 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $updateData = [
            'description' => $request->description,
        ];

        // Non-system roles can have their name updated
        if (! $role->isSystem() && $request->filled('name')) {
            $updateData['name'] = $request->name;
        }

        $role->update($updateData);

        // Sync permissions
        $permissionIds = $request->input('permissions', []);
        $role->permissions()->sync($permissionIds);

        AuditLog::record(
            auth()->user(),
            'Role Updated',
            "Updated permissions and details for role '{$role->name}'.",
            'Roles',
            $role->id
        );

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' updated successfully.");
    }

    /**
     * Remove the specified custom role from storage.
     */
    public function destroy($id)
    {
        $tenantId = app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();

        $role = Role::withCount('users')
            ->forTenant($tenantId)
            ->findOrFail($id);

        if ($role->isSystem()) {
            return redirect()->route('roles.index')->with('error', 'System default roles cannot be deleted.');
        }

        if ($role->users_count > 0) {
            return redirect()->route('roles.index')->with(
                'error',
                "Cannot delete role '{$role->name}' because {$role->users_count} staff user(s) are currently assigned to it. Please reassign them first."
            );
        }

        $roleName = $role->name;
        $role->permissions()->detach();
        $role->delete();

        AuditLog::record(
            auth()->user(),
            'Role Deleted',
            "Deleted custom role '{$roleName}'.",
            'Roles',
            $role->id
        );

        return redirect()->route('roles.index')->with('success', "Role '{$roleName}' deleted successfully.");
    }
}
