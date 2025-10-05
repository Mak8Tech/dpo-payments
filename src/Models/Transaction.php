<?php

namespace Mak8Tech\DpoPayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'dpo_transactions';

    protected $fillable = [
        'reference',
        'token',
        'trans_id',
        'type',
        'status',
        'amount',
        'currency',
        'country',
        'payment_method',
        'customer_email',
        'customer_name',
        'customer_phone',
        'customer_country',
        'description',
        'items',
        'payment_url',
        'paid_at',
        'failed_at',
        'cancelled_at',
        'refunded_at',
        'refunded_amount',
        'dpo_response',
        'dpo_result_code',
        'dpo_result_explanation',
        'user_id',
        'subscription_id',
    ];

    protected $casts = [
        'items' => 'array',
        'dpo_response' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Type constants
    const TYPE_ONE_TIME = 'one-time';
    const TYPE_RECURRING = 'recurring';
    const TYPE_SUBSCRIPTION = 'subscription';

    /**
     * Get the user that owns the transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Get the subscription associated with the transaction
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for transactions by country
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope for transactions by currency
     */
    public function scopeByCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if transaction is refundable
     */
    public function isRefundable(): bool
    {
        return $this->status === self::STATUS_SUCCESS
            && $this->refunded_amount < $this->amount;
    }

    /**
     * Get formatted amount with currency symbol
     */
    public function getFormattedAmountAttribute(): string
    {
        $currencyConfig = config('dpo.currencies.' . $this->currency, [
            'symbol' => $this->currency,
            'decimals' => 2
        ]);

        return $currencyConfig['symbol'] . ' ' .
            number_format($this->amount, $currencyConfig['decimals']);
    }

    /**
     * Mark transaction as paid
     */
    public function markAsPaid(array $dpoResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'paid_at' => now(),
            'dpo_response' => array_merge($this->dpo_response ?? [], $dpoResponse),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason = null, array $dpoResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'dpo_result_explanation' => $reason,
            'dpo_response' => array_merge($this->dpo_response ?? [], $dpoResponse),
        ]);
    }

    /**
     * Process refund
     */
    public function processRefund(float $amount, array $dpoResponse = []): void
    {
        $newRefundedAmount = $this->refunded_amount + $amount;

        $this->update([
            'status' => $newRefundedAmount >= $this->amount ? self::STATUS_REFUNDED : $this->status,
            'refunded_at' => now(),
            'refunded_amount' => $newRefundedAmount,
            'dpo_response' => array_merge($this->dpo_response ?? [], $dpoResponse),
        ]);
    }
}
