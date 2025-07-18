<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PaymentCallback extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'gateway_transaction_id',
        'gateway_status',
        'raw_payload',
        'signature',
        'verified',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'verified' => 'boolean',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($callback) {
            if (empty($callback->received_at)) {
                $callback->received_at = now();
            }
        });
    }

    /**
     * Get the transaction that owns this callback.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    /**
     * Scope for verified callbacks.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verified', true);
    }

    /**
     * Scope for unverified callbacks.
     */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('verified', false);
    }

    /**
     * Scope by gateway status.
     */
    public function scopeByGatewayStatus(Builder $query, string $status): Builder
    {
        return $query->where('gateway_status', $status);
    }

    /**
     * Scope for processed callbacks.
     */
    public function scopeProcessed(Builder $query): Builder
    {
        return $query->whereNotNull('processed_at');
    }

    /**
     * Scope for unprocessed callbacks.
     */
    public function scopeUnprocessed(Builder $query): Builder
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Get the status color for display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->gateway_status) {
            'settlement', 'capture' => 'success',
            'pending' => 'warning',
            'deny', 'cancel', 'expire', 'failure' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get the verification status color.
     */
    public function getVerificationColorAttribute(): string
    {
        return $this->verified ? 'success' : 'danger';
    }

    /**
     * Mark callback as processed.
     */
    public function markAsProcessed(): bool
    {
        $this->processed_at = now();
        return $this->save();
    }

    /**
     * Mark callback as verified.
     */
    public function markAsVerified(): bool
    {
        $this->verified = true;
        return $this->save();
    }

    /**
     * Check if callback is successful.
     */
    public function isSuccessful(): bool
    {
        return in_array($this->gateway_status, ['settlement', 'capture']);
    }

    /**
     * Check if callback is failed.
     */
    public function isFailed(): bool
    {
        return in_array($this->gateway_status, ['deny', 'cancel', 'expire', 'failure']);
    }

    /**
     * Get formatted payload for display.
     */
    public function getFormattedPayloadAttribute(): string
    {
        return json_encode($this->raw_payload, JSON_PRETTY_PRINT);
    }
}
