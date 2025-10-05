<?php

namespace Mak8Tech\DpoPayments\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Mak8Tech\DpoPayments\Services\PaymentService;
use Mak8Tech\DpoPayments\Services\CountryService;
use Mak8Tech\DpoPayments\Models\Transaction;
use Mak8Tech\DpoPayments\Models\PaymentLog;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected CountryService $countryService;

    public function __construct(PaymentService $paymentService, CountryService $countryService)
    {
        $this->paymentService = $paymentService;
        $this->countryService = $countryService;
    }

    /**
     * Create a new payment
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'country' => 'nullable|string|size:2',
            'description' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_country' => 'nullable|string|size:2',
            'items' => 'nullable|array',
            'items.*.description' => 'required_with:items|string',
            'items.*.date' => 'nullable|date',
        ]);

        // Validate country if provided
        if (isset($validated['country']) && !$this->countryService->isValidCountry($validated['country'])) {
            return response()->json([
                'error' => 'Invalid country code',
            ], 422);
        }

        try {
            $transaction = $this->paymentService->createPayment($validated);

            return response()->json([
                'success' => true,
                'transaction' => $transaction->toArray(),
                'payment_url' => $transaction->payment_url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify payment callback
     */
    public function callback(Request $request): RedirectResponse
    {
        $token = $request->get('TransToken') ?? $request->get('token');

        if (!$token) {
            return redirect()->route('payment.failed')
                ->with('error', 'Invalid payment token');
        }

        try {
            // Log callback
            PaymentLog::logCallback('', $request->all(), $token);

            $transaction = $this->paymentService->verifyPayment($token);

            if ($transaction->isSuccessful()) {
                return redirect()->route('payment.success', ['reference' => $transaction->reference]);
            } else {
                return redirect()->route('payment.failed', ['reference' => $transaction->reference])
                    ->with('error', $transaction->dpo_result_explanation);
            }
        } catch (\Exception $e) {
            return redirect()->route('payment.failed')
                ->with('error', 'Payment verification failed');
        }
    }

    /**
     * Handle payment notification (webhook)
     */
    public function notify(Request $request): JsonResponse
    {
        $token = $request->get('TransToken');
        $reference = $request->get('CompanyRef');

        // Log notification
        PaymentLog::logCallback($reference ?? '', $request->all(), $token);

        if (!$token) {
            return response()->json(['error' => 'Invalid token'], 400);
        }

        try {
            $transaction = $this->paymentService->verifyPayment($token);

            // Trigger events based on status
            if ($transaction->isSuccessful()) {
                event(new \Mak8Tech\DpoPayments\Events\PaymentSuccessful($transaction));
            } else {
                event(new \Mak8Tech\DpoPayments\Events\PaymentFailed($transaction));
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get payment status
     */
    public function status(string $reference): JsonResponse
    {
        $transaction = $this->paymentService->getPaymentByReference($reference);

        if (!$transaction) {
            return response()->json([
                'error' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'transaction' => $transaction->toArray(),
            'formatted_amount' => $transaction->formatted_amount,
        ]);
    }

    /**
     * Process refund
     */
    public function refund(Request $request, string $reference): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $transaction = $this->paymentService->refundPayment(
                $reference,
                $validated['amount'] ?? null,
                $validated['reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'transaction' => $transaction->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel payment
     */
    public function cancel(string $reference): JsonResponse
    {
        try {
            $transaction = $this->paymentService->cancelPayment($reference);

            return response()->json([
                'success' => true,
                'transaction' => $transaction->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get supported countries
     */
    public function countries(): JsonResponse
    {
        $countries = $this->countryService->getAllCountries();

        return response()->json([
            'countries' => $countries,
            'default' => config('dpo.default_country'),
        ]);
    }
}
