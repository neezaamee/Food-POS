<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'status',
        'avatar',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string $roleSlug): bool
    {
        if ($this->role === 'super-admin' || $this->role === 'owner') {
            return true;
        }

        return $this->role === $roleSlug || $this->roles->contains('slug', $roleSlug);
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->role === 'super-admin' || $this->role === 'owner') {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionSlug)) {
                return true;
            }
        }

        return false;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canCancelOrder(): bool
    {
        $allowed = ['super-admin', 'super_admin', 'owner', 'manager', 'admin'];
        $userRole = strtolower(str_replace('_', '-', (string) ($this->role ?? '')));

        if (in_array($userRole, ['super-admin', 'owner', 'manager', 'admin'])) {
            return true;
        }

        return $this->roles()->whereIn('slug', $allowed)->exists();
    }
}
