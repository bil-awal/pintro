<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AdminActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * Get the admin that performed this action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Scope by action.
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope by admin.
     */
    public function scopeByAdmin(Builder $query, int $adminId): Builder
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope for today's activities.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Get formatted old values.
     */
    public function getFormattedOldValuesAttribute(): string
    {
        return $this->old_values ? json_encode($this->old_values, JSON_PRETTY_PRINT) : '';
    }

    /**
     * Get formatted new values.
     */
    public function getFormattedNewValuesAttribute(): string
    {
        return $this->new_values ? json_encode($this->new_values, JSON_PRETTY_PRINT) : '';
    }

    /**
     * Create activity log.
     */
    public static function createLog(
        int $adminId,
        string $action,
        string $description,
        array $oldValues = null,
        array $newValues = null,
        string $ipAddress = null,
        string $userAgent = null
    ): self {
        return self::create([
            'admin_id' => $adminId,
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }
}
