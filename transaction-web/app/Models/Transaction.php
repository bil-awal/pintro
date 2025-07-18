<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'reference',
        'user_id',
        'from_account_id',
        'to_account_id',
        'type',
        'amount',
        'fee',
        'currency',
        'description',
        'status',
        'payment_gateway_id',
        'payment_method',
        'metadata',
        'approved_by',
        'approved_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'metadata' => 'array',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = 'TXN-' . strtoupper(Str::random(10)) . '-' . time();
            }
            if (empty($transaction->reference)) {
                $transaction->reference = 'REF-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sender account.
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_account_id');
    }

    /**
     * Get the receiver account.
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_account_id');
    }

    /**
     * Get the admin who approved this transaction.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get payment callbacks for this transaction.
     */
    public function paymentCallbacks(): HasMany
    {
        return $this->hasMany(PaymentCallback::class, 'transaction_id', 'transaction_id');
    }

    /**
     * Scope by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by date range.
     */
    public function scopeByDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for today's transactions.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for pending transactions.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed transactions.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Get the status color for display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'primary',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get formatted fee.
     */
    public function getFormattedFeeAttribute(): string
    {
        return 'Rp ' . number_format($this->fee, 0, ',', '.');
    }

    /**
     * Get total amount including fee.
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->amount + $this->fee;
    }

    /**
     * Get formatted total amount.
     */
    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    /**
     * Check if transaction can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction can be rejected.
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Approve the transaction.
     */
    public function approve(int $approvedBy = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->status = 'completed';
        $this->approved_by = $approvedBy;
        $this->approved_at = now();
        $this->processed_at = now();

        return $this->save();
    }

    /**
     * Reject the transaction.
     */
    public function reject(int $rejectedBy = null): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->status = 'failed';
        $this->approved_by = $rejectedBy;
        $this->approved_at = now();
        $this->processed_at = now();

        return $this->save();
    }

    /**
     * Mark transaction as processing.
     */
    public function markAsProcessing(): bool
    {
        $this->status = 'processing';
        return $this->save();
    }

    /**
     * Get the type icon.
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'topup' => 'heroicon-o-arrow-trending-up',
            'payment' => 'heroicon-o-credit-card',
            'transfer' => 'heroicon-o-arrow-right-left',
            'withdrawal' => 'heroicon-o-arrow-trending-down',
            default => 'heroicon-o-banknotes',
        };
    }
}
