<?php

namespace Mak8Tech\DpoPayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $table = 'dpo_subscriptions';

    protected $fillable = [
        'subscription_reference',
        'dpo_subscription_id',
        'status',
        'frequency',
        'amount',
        'currency',
        'country',
        'start_date',
        'end_date',
        'next_billing_date',
        'billing_cycle',
        'customer_email',
        'customer_name',
        'customer_phone',
        'customer_country',
        'payment_method',
        'payment_token',
        'card_details',
        'description',
        'metadata',
        'auto_renew',
        'retry_attempts',
        'last_payment_at',
        'last_failed_at',
        'cancelled_at',
        'cancellation_reason',
        'successful_payments',
        'failed_payments',
        'total_paid',
        'user_id',
    ];

    protected $casts = [
        'card_details' => 'array',
        'metadata' => 'array',
        'auto_renew' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'last_payment_at' => 'datetime',
        'last_failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PENDING = 'pending';

    // Frequency constants
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_YEARLY = 'yearly';

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Get the transactions for the subscription
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for subscriptions due for billing
     */
    public function scopeDueForBilling($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('next_billing_date', '<=', now())
            ->where('auto_renew', true);
    }

    /**
     * Scope for subscriptions by country
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if subscription is due for billing
     */
    public function isDueForBilling(): bool
    {
        return $this->isActive()
            && $this->auto_renew
            && $this->next_billing_date
            && $this->next_billing_date->lte(now());
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
     * Calculate next billing date
     */
    public function calculateNextBillingDate(): Carbon
    {
        $current = $this->next_billing_date ?? $this->start_date;

        switch ($this->frequency) {
            case self::FREQUENCY_WEEKLY:
                return $current->copy()->addWeek();
            case self::FREQUENCY_MONTHLY:
                return $current->copy()->addMonth();
            case self::FREQUENCY_QUARTERLY:
                return $current->copy()->addMonths(3);
            case self::FREQUENCY_YEARLY:
                return $current->copy()->addYear();
            default:
                return $current->copy()->addMonth();
        }
    }

    /**
     * Record successful payment
     */
    public function recordSuccessfulPayment(float $amount): void
    {
        $this->update([
            'successful_payments' => $this->successful_payments + 1,
            'total_paid' => $this->total_paid + $amount,
            'last_payment_at' => now(),
            'next_billing_date' => $this->calculateNextBillingDate(),
            'billing_cycle' => $this->billing_cycle + 1,
            'retry_attempts' => 0,
        ]);
    }

    /**
     * Record failed payment
     */
    public function recordFailedPayment(): void
    {
        $this->update([
            'failed_payments' => $this->failed_payments + 1,
            'last_failed_at' => now(),
            'retry_attempts' => $this->retry_attempts + 1,
        ]);

        // Auto-cancel after max retries
        if ($this->retry_attempts >= config('dpo.recurring.max_retry_attempts', 3)) {
            $this->cancel('Maximum retry attempts reached');
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);
    }

    /**
     * Pause subscription
     */
    public function pause(): void
    {
        $this->update([
            'status' => self::STATUS_PAUSED,
            'auto_renew' => false,
        ]);
    }

    /**
     * Resume subscription
     */
    public function resume(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'auto_renew' => true,
        ]);
    }
}
