<?php

namespace Mak8Tech\DpoPayments\Services;

use Illuminate\Support\Facades\Auth;
use Mak8Tech\DpoPayments\Models\Transaction;
use Mak8Tech\DpoPayments\Models\PaymentLog;
use Mak8Tech\DpoPayments\Data\TransactionData;
use Mak8Tech\DpoPayments\Data\TokenResponse;
use Mak8Tech\DpoPayments\Exceptions\DpoException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected DpoService $dpoService;

    public function __construct(DpoService $dpoService)
    {
        $this->dpoService = $dpoService;
    }

    /**
     * Create a one-time payment
     */
    public function createPayment(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Generate unique reference
            $reference = $this->generateReference();

            // Create transaction record
            $transaction = Transaction::create([
                'reference' => $reference,
                'type' => Transaction::TYPE_ONE_TIME,
                'status' => Transaction::STATUS_PENDING,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? config('dpo.default_currency'),
                'country' => $data['country'] ?? config('dpo.default_country'),
                'customer_email' => $data['customer_email'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_country' => $data['customer_country'] ?? null,
                'description' => $data['description'] ?? 'Payment',
                'items' => $data['items'] ?? null,
                'user_id' => $data['user_id'] ?? Auth::id(),
            ]);

            try {
                // Log request
                PaymentLog::logRequest($reference, 'create_payment', $data);

                // Prepare transaction data
                $transactionData = new TransactionData(
                    amount: $transaction->amount,
                    currency: $transaction->currency,
                    reference: $reference,
                    description: $transaction->description,
                    customerEmail: $transaction->customer_email,
                    customerName: $transaction->customer_name,
                    customerPhone: $transaction->customer_phone,
                    customerCountry: $transaction->customer_country,
                    services: $this->prepareServices($data['items'] ?? [['description' => $transaction->description]]),
                    isRecurring: false
                );

                // Create DPO token
                $tokenResponse = $this->dpoService->createToken($transactionData);

                // Update transaction with token info
                $transaction->update([
                    'token' => $tokenResponse->token,
                    'trans_id' => $tokenResponse->reference,
                    'payment_url' => $tokenResponse->paymentUrl,
                    'status' => Transaction::STATUS_PROCESSING,
                    'dpo_result_code' => $tokenResponse->result,
                    'dpo_result_explanation' => $tokenResponse->explanation,
                ]);

                // Log response
                PaymentLog::logResponse($reference, 'create_payment', [
                    'token' => $tokenResponse->token,
                    'reference' => $tokenResponse->reference,
                    'result' => $tokenResponse->result,
                ], $tokenResponse->result, $tokenResponse->token);

                return $transaction;
            } catch (\Exception $e) {
                // Update transaction status
                $transaction->update([
                    'status' => Transaction::STATUS_FAILED,
                    'dpo_result_explanation' => $e->getMessage(),
                ]);

                // Log error
                PaymentLog::logResponse($reference, 'create_payment', [
                    'error' => $e->getMessage(),
                ], 'error');

                throw $e;
            }
        });
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $token): Transaction
    {
        $transaction = Transaction::where('token', $token)->firstOrFail();

        try {
            // Log request
            PaymentLog::logRequest($transaction->reference, 'verify_payment', ['token' => $token], $token);

            // Verify with DPO
            $result = $this->dpoService->verifyToken($token, $transaction->reference);

            // Log response
            PaymentLog::logResponse($transaction->reference, 'verify_payment', $result, $result['Result'] ?? null, $token);

            // Update transaction based on result
            if ($result['Result'] === '000' && ($result['TransactionApproval'] ?? false)) {
                $transaction->markAsPaid($result);
            } else {
                $transaction->markAsFailed($result['ResultExplanation'] ?? 'Payment verification failed', $result);
            }

            return $transaction;
        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            $transaction->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Process refund
     */
    public function refundPayment(string $reference, float $amount = null, string $reason = null): Transaction
    {
        $transaction = Transaction::where('reference', $reference)->firstOrFail();

        if (!$transaction->isRefundable()) {
            throw new DpoException('Transaction is not refundable');
        }

        $refundAmount = $amount ?? $transaction->amount;

        if ($refundAmount > ($transaction->amount - $transaction->refunded_amount)) {
            throw new DpoException('Refund amount exceeds available amount');
        }

        try {
            // Log request
            PaymentLog::logRequest($reference, 'refund_payment', [
                'amount' => $refundAmount,
                'reason' => $reason,
            ], $transaction->token);

            // Process refund with DPO
            $result = $this->dpoService->refundToken(
                $transaction->token,
                $refundAmount,
                $transaction->reference,
                $reason
            );

            // Log response
            PaymentLog::logResponse($reference, 'refund_payment', $result, $result['Result'] ?? null, $transaction->token);

            // Update transaction
            $transaction->processRefund($refundAmount, $result);

            return $transaction;
        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'reference' => $reference,
                'amount' => $refundAmount,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel payment
     */
    public function cancelPayment(string $reference): Transaction
    {
        $transaction = Transaction::where('reference', $reference)->firstOrFail();

        if (!$transaction->isPending()) {
            throw new DpoException('Only pending transactions can be cancelled');
        }

        try {
            // Log request
            PaymentLog::logRequest($reference, 'cancel_payment', [], $transaction->token);

            // Cancel with DPO
            $success = $this->dpoService->cancelToken($transaction->token, $transaction->reference);

            // Log response
            PaymentLog::logResponse($reference, 'cancel_payment', ['success' => $success], $success ? '000' : 'error', $transaction->token);

            if ($success) {
                $transaction->update([
                    'status' => Transaction::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);
            }

            return $transaction;
        } catch (\Exception $e) {
            Log::error('Cancel payment failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get payment by reference
     */
    public function getPaymentByReference(string $reference): ?Transaction
    {
        return Transaction::where('reference', $reference)->first();
    }

    /**
     * Get payment by token
     */
    public function getPaymentByToken(string $token): ?Transaction
    {
        return Transaction::where('token', $token)->first();
    }

    /**
     * Generate unique reference
     */
    protected function generateReference(): string
    {
        do {
            $reference = 'PAY-' . strtoupper(Str::random(10)) . '-' . time();
        } while (Transaction::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Prepare services array for DPO
     */
    protected function prepareServices(array $items): array
    {
        if (empty($items)) {
            return [['description' => 'Payment', 'date' => now()->format('Y/m/d H:i')]];
        }

        return array_map(function ($item) {
            return [
                'description' => $item['description'] ?? 'Item',
                'date' => $item['date'] ?? now()->format('Y/m/d H:i'),
            ];
        }, $items);
    }
}
