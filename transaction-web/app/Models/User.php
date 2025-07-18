<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name', 
        'name', // Add name for compatibility
        'email',
        'phone',
        'password',
        'balance',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (empty($user->user_id)) {
                $user->user_id = 'USR-' . strtoupper(Str::random(10));
            }
        });
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get/Set the name attribute (for compatibility).
     */
    public function getNameAttribute(): string
    {
        return $this->first_name ?: ($this->name ?? '');
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['first_name'] = $value;
    }

    /**
     * Get formatted balance.
     */
    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }

    /**
     * Scope to get active users.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Get transactions where user is the owner.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get transactions where user is the sender.
     */
    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_account_id');
    }

    /**
     * Get transactions where user is the receiver.
     */
    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_account_id');
    }

    /**
     * Get approved transactions.
     */
    public function approvedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'approved_by');
    }

    /**
     * Get the status color for display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'inactive' => 'warning',
            'suspended' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Check if user has sufficient balance.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Update user balance.
     */
    public function updateBalance(float $amount, string $operation = 'add'): bool
    {
        if ($operation === 'add') {
            $this->balance += $amount;
        } elseif ($operation === 'subtract') {
            if (!$this->hasSufficientBalance($amount)) {
                return false;
            }
            $this->balance -= $amount;
        }

        return $this->save();
    }
}
