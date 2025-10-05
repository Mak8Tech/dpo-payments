<?php

namespace Mak8Tech\DpoPayments\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Mak8Tech\DpoPayments\Services\SubscriptionService;
use Mak8Tech\DpoPayments\Services\CountryService;
use Mak8Tech\DpoPayments\Models\Subscription;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;
    protected CountryService $countryService;

    public function __construct(SubscriptionService $subscriptionService, CountryService $countryService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->countryService = $countryService;
    }

    /**
     * Create a new subscription
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'frequency' => 'nullable|in:monthly,weekly,quarterly,yearly',
            'currency' => 'nullable|string|size:3',
            'country' => 'nullable|string|size:2',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'customer_email' => 'required|email',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_country' => 'nullable|string|size:2',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'auto_renew' => 'nullable|boolean',
        ]);

        // Validate country supports recurring
        $country = $validated['country'] ?? config('dpo.default_country');
        if (!$this->countryService->getCountry($country)['supports_recurring'] ?? false) {
            return response()->json([
                'error' => "Recurring payments not supported in {$country}",
            ], 422);
        }

        try {
            $subscription = $this->subscriptionService->createSubscription($validated);

            return response()->json([
                'success' => true,
                'subscription' => $subscription->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subscription details
     */
    public function show(string $reference): JsonResponse
    {
        $subscription = Subscription::where('subscription_reference', $reference)->first();

        if (!$subscription) {
            return response()->json([
                'error' => 'Subscription not found',
            ], 404);
        }

        return response()->json([
            'subscription' => $subscription->toArray(),
            'transactions' => $subscription->transactions()->latest()->limit(10)->get(),
        ]);
    }

    /**
     * Update subscription
     */
    public function update(Request $request, string $reference): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'auto_renew' => 'nullable|boolean',
            'end_date' => 'nullable|date|after:today',
            'metadata' => 'nullable|array',
        ]);

        try {
            $subscription = $this->subscriptionService->updateSubscription($reference, $validated);

            return response()->json([
                'success' => true,
                'subscription' => $subscription->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request, string $reference): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $subscription = $this->subscriptionService->cancelSubscription(
                $reference,
                $validated['reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'subscription' => $subscription->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Pause subscription
     */
    public function pause(string $reference): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->pauseSubscription($reference);

            return response()->json([
                'success' => true,
                'subscription' => $subscription->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Resume subscription
     */
    public function resume(string $reference): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->resumeSubscription($reference);

            return response()->json([
                'success' => true,
                'subscription' => $subscription->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
