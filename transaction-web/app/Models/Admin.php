<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    /**
     * The table associated with the model.
     * Set to null since we're not using database authentication
     */
    protected $table = null;

    /**
     * The connection name for the model.
     * Set to null since we're not using database
     */
    protected $connection = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'status',
        'balance',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * Get the admin's full name.
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the admin's display name.
     */
    public function getDisplayNameAttribute()
    {
        return $this->full_name ?: $this->email;
    }

    /**
     * Check if the admin is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if the admin is blocked.
     */
    public function isBlocked()
    {
        return $this->status === 'blocked';
    }

    /**
     * Check if the admin is inactive.
     */
    public function isInactive()
    {
        return $this->status === 'inactive';
    }

    /**
     * Get the formatted balance.
     */
    public function getFormattedBalanceAttribute()
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }

    /**
     * Override the save method to prevent database operations.
     */
    public function save(array $options = [])
    {
        // Since we're not using database, we'll just return true
        // In a real implementation, you might want to sync with Go API
        return true;
    }

    /**
     * Override the delete method to prevent database operations.
     */
    public function delete()
    {
        // Since we're not using database, we'll just return true
        return true;
    }

    /**
     * Override the fresh method to prevent database operations.
     */
    public function fresh($with = [])
    {
        return $this;
    }

    /**
     * Override the refresh method to prevent database operations.
     */
    public function refresh()
    {
        return $this;
    }

    /**
     * Get the admin's avatar URL.
     */
    public function getAvatarUrlAttribute()
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get the admin's initials.
     */
    public function getInitialsAttribute()
    {
        $names = explode(' ', $this->full_name);
        $initials = '';
        
        foreach ($names as $name) {
            if (!empty($name)) {
                $initials .= strtoupper(substr($name, 0, 1));
            }
        }
        
        return $initials ?: strtoupper(substr($this->email, 0, 2));
    }

    /**
     * Get the admin's JWT token if available.
     */
    public function getToken()
    {
        return $this->getAttribute('token');
    }

    /**
     * Set the admin's JWT token.
     */
    public function setToken($token)
    {
        return $this->setAttribute('token', $token);
    }

    /**
     * Check if admin has a valid token.
     */
    public function hasValidToken()
    {
        return !empty($this->getToken());
    }

    /**
     * Convert the model instance to an array.
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Add computed attributes
        $array['full_name'] = $this->full_name;
        $array['display_name'] = $this->display_name;
        $array['initials'] = $this->initials;
        $array['avatar_url'] = $this->avatar_url;
        $array['formatted_balance'] = $this->formatted_balance;
        $array['is_active'] = $this->isActive();
        $array['is_blocked'] = $this->isBlocked();
        $array['is_inactive'] = $this->isInactive();
        
        return $array;
    }
}
