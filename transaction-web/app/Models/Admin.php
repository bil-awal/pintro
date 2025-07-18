<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class Admin extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
        'permissions',
        'is_active',
        'last_login_at',
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
            'roles' => 'array',
            'permissions' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get activity logs for this admin.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(AdminActivityLog::class);
    }

    /**
     * Get approved transactions.
     */
    public function approvedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'approved_by');
    }

    /**
     * Get updated system settings.
     */
    public function updatedSettings(): HasMany
    {
        return $this->hasMany(SystemSetting::class, 'updated_by');
    }

    /**
     * Check if admin has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? []);
    }

    /**
     * Check if admin has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if admin can access Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): bool
    {
        $this->last_login_at = now();
        return $this->save();
    }

    /**
     * Get the roles as a formatted string.
     */
    public function getRolesStringAttribute(): string
    {
        return implode(', ', $this->roles ?? []);
    }

    /**
     * Get the permissions as a formatted string.
     */
    public function getPermissionsStringAttribute(): string
    {
        return implode(', ', $this->permissions ?? []);
    }

    /**
     * Get the status color for display.
     */
    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }
}
