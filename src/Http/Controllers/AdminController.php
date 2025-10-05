<?php

namespace Mak8Tech\DpoPayments\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Mak8Tech\DpoPayments\Models\Transaction;
use Mak8Tech\DpoPayments\Models\Subscription;
use Mak8Tech\DpoPayments\Services\CountryService;
use Carbon\Carbon;

class AdminController extends Controller
{
    protected CountryService $countryService;

    public function __construct(CountryService $countryService)
    {
        $this->countryService = $countryService;
    }

    /**
     * Dashboard view
     */
    public function dashboard(Request $request): View
    {
        $stats = [
            'total_transactions' => Transaction::count(),
            'successful_transactions' => Transaction::successful()->count(),
            'total_revenue' => Transaction::successful()->sum('amount'),
            'active_subscriptions' => Subscription::active()->count(),
            'recent_transactions' => Transaction::latest()->limit(10)->get(),
            'recent_subscriptions' => Subscription::latest()->limit(5)->get(),
        ];

        // Get revenue by country
        $revenueByCountry = Transaction::successful()
            ->selectRaw('country, currency, SUM(amount) as total')
            ->groupBy('country', 'currency')
            ->get()
            ->map(function ($item) {
                $country = $this->countryService->getCountry($item->country);
                return [
                    'country' => $country['name'] ?? $item->country,
                    'currency' => $item->currency,
                    'total' => $item->total,
                    'formatted' => $this->countryService->formatCurrency($item->total, $item->currency),
                ];
            });

        return view('dpo::admin.dashboard', compact('stats', 'revenueByCountry'));
    }

    /**
     * Transactions list view
     */
    public function transactions(Request $request): View
    {
        $query = Transaction::query();

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('country')) {
            $query->where('country', $request->get('country'));
        }

        if ($request->has('currency')) {
            $query->where('currency', $request->get('currency'));
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->get('date_from')));
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->get('date_to')));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest()->paginate(20);
        $countries = $this->countryService->getAllCountries();

        return view('dpo::admin.transactions', compact('transactions', 'countries'));
    }

    /**
     * Subscriptions list view
     */
    public function subscriptions(Request $request): View
    {
        $query = Subscription::query();

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('country')) {
            $query->where('country', $request->get('country'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('subscription_reference', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->latest()->paginate(20);
        $countries = $this->countryService->getAllCountries();

        return view('dpo::admin.subscriptions', compact('subscriptions', 'countries'));
    }

    /**
     * Reports view
     */
    public function reports(Request $request): View
    {
        $period = $request->get('period', 'month');
        $startDate = match ($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subMonths(3),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        $stats = [
            'total_revenue' => Transaction::successful()
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'total_transactions' => Transaction::where('created_at', '>=', $startDate)->count(),
            'success_rate' => Transaction::where('created_at', '>=', $startDate)->count() > 0
                ? (Transaction::successful()->where('created_at', '>=', $startDate)->count() /
                    Transaction::where('created_at', '>=', $startDate)->count()) * 100
                : 0,
            'new_subscriptions' => Subscription::where('created_at', '>=', $startDate)->count(),
            'cancelled_subscriptions' => Subscription::where('cancelled_at', '>=', $startDate)->count(),
        ];

        // Daily revenue chart data
        $dailyRevenue = Transaction::successful()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get();

        return view('dpo::admin.reports', compact('stats', 'dailyRevenue', 'period'));
    }
}
