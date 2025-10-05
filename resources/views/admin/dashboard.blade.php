@extends('dpo::layouts.admin')

@section('title', 'DPO Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Transactions Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Transactions</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_transactions']) }}</p>
            </div>
        </div>
    </div>
    
    <!-- Successful Transactions Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Successful</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['successful_transactions']) }}</p>
            </div>
        </div>
    </div>
    
    <!-- Total Revenue Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-full">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Revenue</p>
                <p class="text-2xl font-semibold text-gray-900">{{ config('dpo.currencies.' . config('dpo.default_currency') . '.symbol') }} {{ number_format($stats['total_revenue'], 2) }}</p>
            </div>
        </div>
    </div>
    
    <!-- Active Subscriptions Card -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-full">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Active Subscriptions</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['active_subscriptions']) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Revenue by Country -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Revenue by Country</h2>
        <div class="space-y-3">
            @foreach($revenueByCountry->take(5) as $country)
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $country['country'] }}</span>
                <span class="text-sm font-semibold">{{ $country['formatted'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Transactions</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2">Reference</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2">Amount</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($stats['recent_transactions']->take(5) as $transaction)
                    <tr>
                        <td class="py-2 text-sm">{{ Str::limit($transaction->reference, 15) }}</td>
                        <td class="py-2 text-sm">{{ $transaction->formatted_amount }}</td>
                        <td class="py-2">
                            <span class="px-2 py-1 text-xs rounded-full 
                                @if($transaction->status === 'success') bg-green-100 text-green-800
                                @elseif($transaction->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
