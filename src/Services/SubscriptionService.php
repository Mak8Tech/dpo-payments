<?php

namespace Mak8Tech\DpoPayments\Services;

use Mak8Tech\DpoPayments\Models\Subscription;
use Mak8Tech\DpoPayments\Models\Transaction;
use Mak8Tech\DpoPayments\Models\PaymentLog;
use Mak8Tech\DpoPayments\Data\TransactionData;
use Mak8Tech\DpoPayments\Exceptions\DpoException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SubscriptionService
{
    protected DpoService $dpoService;
    protected PaymentService $paymentService;

    public function __construct(DpoService $dpoService)
    {
        $this->dpoService = $dpoService;
        $this->paymentService = new PaymentService($dpoService);
    }

    /**
     * Create a new subscription
     */
    public function createSubscription(array $data): Subscription
    {
        return DB::transaction(function () use ($data) {
            // Validate country supports recurring
            $country = $data['country'] ?? config('dpo.default_country');
            if (!$this->supportsRecurring($country)) {
                throw new DpoException("Recurring payments not supported in {$country}");
            }

            // Generate unique reference
            $reference = $this->generateReference();

            // Create subscription record
            $subscription = Subscription::create([
                'subscription_reference' => $reference,
                'status' => Subscription::STATUS_PENDING,
                'frequency' => $data['frequency'] ?? Subscription::FREQUENCY_MONTHLY,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? config('dpo.default_currency'),
                'country' => $country,
                'start_date' => Carbon::parse($data['start_date'] ?? now()),
                'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
                'next_billing_date' => Carbon::parse($data['start_date'] ?? now()),
                'customer_email' => $data['customer_email'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_country' => $data['customer_country'] ?? $country,
                'description' => $data['description'] ?? 'Subscription',
                'metadata' => $data['metadata'] ?? null,
                'auto_renew' => $data['auto_renew'] ?? true,
                'user_id' => $data['user_id'] ?? Auth::id(),
            ]);

            try {
                // Log request
                PaymentLog::logRequest($reference, 'create_subscription', $data);

                // Create initial payment if immediate charge is enabled
                if (config('dpo.recurring.immediate_charge')) {
                    $transaction = $this->createInitialPayment($subscription);

                    // Update subscription with payment token for future charges
                    $subscription->update([
                        'payment_token' => $transaction->token,
                    ]);
                }

                // Create subscription with DPO
                $result = $this->dpoService->createSubscription([
                    'amount' => $subscription->amount,
                    'currency' => $subscription->currency,
                    'frequency' => strtoupper($subscription->frequency),
                    'start_date' => $subscription->start_date->format('Y-m-d'),
                    'end_date' => $subscription->end_date?->format('Y-m-d'),
                    'customer_email' => $subscription->customer_email,
                    'customer_first_name' => explode(' ', $subscription->customer_name)[0],
                    'customer_last_name' => implode(' ', array_slice(explode(' ', $subscription->customer_name), 1)),
                ]);

                // Update subscription with DPO ID
                $subscription->update([
                    'dpo_subscription_id' => $result['SubscriptionID'] ?? null,
                    'status' => Subscription::STATUS_ACTIVE,
                ]);

                // Log response
                PaymentLog::logResponse($reference, 'create_subscription', $result, $result['Result'] ?? null);

                return $subscription;
            } catch (\Exception $e) {
                // Update subscription status
                $subscription->update([
                    'status' => Subscription::STATUS_FAILED,
                ]);

                // Log error
                PaymentLog::logResponse($reference, 'create_subscription', [
                    'error' => $e->getMessage(),
                ], 'error');

                throw $e;
            }
        });
    }

    /**
     * Process subscription payment
     */
    public function processSubscriptionPayment(Subscription $subscription): Transaction
    {
        if (!$subscription->isDueForBilling()) {
            throw new DpoException('Subscription is not due for billing');
        }

        return DB::transaction(function () use ($subscription) {
            // Create transaction record
            $transaction = Transaction::create([
                'reference' => $this->generatePaymentReference($subscription),
                'type' => Transaction::TYPE_SUBSCRIPTION,
                'status' => Transaction::STATUS_PENDING,
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'country' => $subscription->country,
                'customer_email' => $subscription->customer_email,
                'customer_name' => $subscription->customer_name,
                'customer_phone' => $subscription->customer_phone,
                'customer_country' => $subscription->customer_country,
                'description' => "Subscription payment for {$subscription->subscription_reference}",
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);

            try {
                // Log request
                PaymentLog::logRequest($transaction->reference, 'subscription_payment', [
                    'subscription_id' => $subscription->id,
                    'amount' => $subscription->amount,
                ]);

                // Process payment using stored token or create new payment
                if ($subscription->payment_token) {
                    // Verify and charge using existing token
                    $result = $this->dpoService->verifyToken($subscription->payment_token, $transaction->reference);

                    if ($result['Result'] === '000') {
                        $transaction->markAsPaid($result);
                        $subscription->recordSuccessfulPayment($transaction->amount);
                    } else {
                        throw new DpoException($result['ResultExplanation'] ?? 'Payment failed');
                    }
                } else {
                    // Create new payment token
                    $transactionData = new TransactionData(
                        amount: $subscription->amount,
                        currency: $subscription->currency,
                        reference: $transaction->reference,
                        description: $transaction->description,
                        customerEmail: $subscription->customer_email,
                        customerName: $subscription->customer_name,
                        customerPhone: $subscription->customer_phone,
                        customerCountry: $subscription->customer_country,
                        services: [['description' => $transaction->description]],
                        isRecurring: true
                    );

                    $tokenResponse = $this->dpoService->createToken($transactionData);

                    $transaction->update([
                        'token' => $tokenResponse->token,
                        'trans_id' => $tokenResponse->reference,
                        'payment_url' => $tokenResponse->paymentUrl,
                        'status' => Transaction::STATUS_PROCESSING,
                    ]);

                    // Store token for future use
                    $subscription->update(['payment_token' => $tokenResponse->token]);
                }

                // Log response
                PaymentLog::logResponse($transaction->reference, 'subscription_payment', [
                    'success' => true,
                    'subscription_id' => $subscription->id,
                ], '000');

                return $transaction;
            } catch (\Exception $e) {
                // Mark payment as failed
                $transaction->markAsFailed($e->getMessage());
                $subscription->recordFailedPayment();

                // Log error
                PaymentLog::logResponse($transaction->reference, 'subscription_payment', [
                    'error' => $e->getMessage(),
                ], 'error');

                throw $e;
            }
        });
    }

    /**
     * Update subscription
     */
    public function updateSubscription(string $reference, array $updates): Subscription
    {
        $subscription = Subscription::where('subscription_reference', $reference)->firstOrFail();

        if ($subscription->isCancelled()) {
            throw new DpoException('Cannot update cancelled subscription');
        }

        try {
            // Log request
            PaymentLog::logRequest($reference, 'update_subscription', $updates);

            // Update with DPO if subscription ID exists
            if ($subscription->dpo_subscription_id) {
                $success = $this->dpoService->updateSubscription($subscription->dpo_subscription_id, $updates);

                if (!$success) {
                    throw new DpoException('Failed to update subscription with DPO');
                }
            }

            // Update local record
            $subscription->update($updates);

            // Log response
            PaymentLog::logResponse($reference, 'update_subscription', ['success' => true], '000');

            return $subscription;
        } catch (\Exception $e) {
            // Log error
            PaymentLog::logResponse($reference, 'update_subscription', [
                'error' => $e->getMessage(),
            ], 'error');

            throw $e;
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(string $reference, string $reason = null): Subscription
    {
        $subscription = Subscription::where('subscription_reference', $reference)->firstOrFail();

        if ($subscription->isCancelled()) {
            return $subscription;
        }

        try {
            // Log request
            PaymentLog::logRequest($reference, 'cancel_subscription', ['reason' => $reason]);

            // Cancel with DPO if subscription ID exists
            if ($subscription->dpo_subscription_id) {
                $success = $this->dpoService->cancelSubscription($subscription->dpo_subscription_id);

                if (!$success) {
                    Log::warning('Failed to cancel subscription with DPO', [
                        'subscription_id' => $subscription->dpo_subscription_id,
                    ]);
                }
            }

            // Cancel local subscription
            $subscription->cancel($reason);

            // Log response
            PaymentLog::logResponse($reference, 'cancel_subscription', ['success' => true], '000');

            return $subscription;
        } catch (\Exception $e) {
            // Log error
            PaymentLog::logResponse($reference, 'cancel_subscription', [
                'error' => $e->getMessage(),
            ], 'error');

            throw $e;
        }
    }

    /**
     * Pause subscription
     */
    public function pauseSubscription(string $reference): Subscription
    {
        $subscription = Subscription::where('subscription_reference', $reference)->firstOrFail();

        if (!$subscription->isActive()) {
            throw new DpoException('Only active subscriptions can be paused');
        }

        $subscription->pause();

        return $subscription;
    }

    /**
     * Resume subscription
     */
    public function resumeSubscription(string $reference): Subscription
    {
        $subscription = Subscription::where('subscription_reference', $reference)->firstOrFail();

        if ($subscription->status !== Subscription::STATUS_PAUSED) {
            throw new DpoException('Only paused subscriptions can be resumed');
        }

        $subscription->resume();

        return $subscription;
    }

    /**
     * Process due subscriptions
     */
    public function processDueSubscriptions(): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $subscriptions = Subscription::dueForBilling()->get();

        foreach ($subscriptions as $subscription) {
            try {
                $this->processSubscriptionPayment($subscription);
                $results['successful']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'subscription' => $subscription->subscription_reference,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to process subscription payment', [
                    'subscription' => $subscription->subscription_reference,
                    'error' => $e->getMessage(),
                ]);
            }

            $results['processed']++;
        }

        return $results;
    }

    /**
     * Check if country supports recurring payments
     */
    protected function supportsRecurring(string $country): bool
    {
        $countryConfig = config("dpo.countries.{$country}");

        return $countryConfig && ($countryConfig['supports_recurring'] ?? false);
    }

    /**
     * Create initial payment for subscription
     */
    protected function createInitialPayment(Subscription $subscription): Transaction
    {
        return $this->paymentService->createPayment([
            'amount' => $subscription->amount,
            'currency' => $subscription->currency,
            'country' => $subscription->country,
            'customer_email' => $subscription->customer_email,
            'customer_name' => $subscription->customer_name,
            'customer_phone' => $subscription->customer_phone,
            'customer_country' => $subscription->customer_country,
            'description' => "Initial payment for subscription {$subscription->subscription_reference}",
            'user_id' => $subscription->user_id,
        ]);
    }

    /**
     * Generate unique subscription reference
     */
    protected function generateReference(): string
    {
        do {
            $reference = 'SUB-' . strtoupper(Str::random(10)) . '-' . time();
        } while (Subscription::where('subscription_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Generate payment reference for subscription
     */
    protected function generatePaymentReference(Subscription $subscription): string
    {
        return 'SUB-PAY-' . $subscription->id . '-' . $subscription->billing_cycle . '-' . time();
    }
}
