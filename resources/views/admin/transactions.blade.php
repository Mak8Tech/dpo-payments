@extends('dpo::layouts.admin')

@section('title', 'Transactions')
@section('page-title', 'Transactions')

@section('content')
<div class="bg-white rounded-lg shadow">
    <!-- Filters -->
    <div class="p-6 border-b">
        <form method="GET" action="{{ route('dpo.admin.transactions') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <select name="country" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">All Countries</option>
                    @foreach($countries as $code => $country)
                    <option value="{{ $code }}" {{ request('country') === $code ? 'selected' : '' }}>{{ $country['name'] }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Reference, email, name..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Filter
                </button>
                <a href="{{ route('dpo.admin.transactions') }}" class="ml-2 px-4 py-2 text-gray-600 hover:text-gray-900">
                    Clear
                </a>
            </div>
        </form>
    </div>
    
    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <code>{{ $transaction->reference }}</code>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div>{{ $transaction->customer_name ?? '-' }}</div>
                        <div class="text-gray-500">{{ $transaction->customer_email ?? '-' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        {{ $transaction->formatted_amount }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        {{ $countries[$transaction->country]['name'] ?? $transaction->country }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full 
                            @if($transaction->status === 'success') bg-green-100 text-green-800
                            @elseif($transaction->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($transaction->status === 'processing') bg-blue-100 text-blue-800
                            @elseif($transaction->status === 'refunded') bg-purple-100 text-purple-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ ucfirst($transaction->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $transaction->created_at->format('Y-m-d H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button x-data @click="$dispatch('open-modal', { transaction: {{ $transaction->toJson() }} })"
                                class="text-indigo-600 hover:text-indigo-900">View</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No transactions found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="p-6 border-t">
        {{ $transactions->links() }}
    </div>
</div>
@endsection