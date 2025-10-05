<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'DPO Payment Admin')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen" x-data="{ sidebarOpen: false }">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-64 bg-indigo-900 transform transition-transform duration-200"
             :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
             @click.away="sidebarOpen = false">
            <div class="flex items-center justify-center h-16 bg-indigo-800">
                <span class="text-white text-xl font-bold">DPO Payments</span>
            </div>
            <nav class="mt-8">
                <a href="{{ route('dpo.admin.dashboard') }}" 
                   class="flex items-center px-6 py-3 text-white hover:bg-indigo-800 {{ request()->routeIs('dpo.admin.dashboard') ? 'bg-indigo-800' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('dpo.admin.transactions') }}" 
                   class="flex items-center px-6 py-3 text-white hover:bg-indigo-800 {{ request()->routeIs('dpo.admin.transactions') ? 'bg-indigo-800' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Transactions
                </a>
                <a href="{{ route('dpo.admin.subscriptions') }}" 
                   class="flex items-center px-6 py-3 text-white hover:bg-indigo-800 {{ request()->routeIs('dpo.admin.subscriptions') ? 'bg-indigo-800' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Subscriptions
                </a>
                <a href="{{ route('dpo.admin.reports') }}" 
                   class="flex items-center px-6 py-3 text-white hover:bg-indigo-800 {{ request()->routeIs('dpo.admin.reports') ? 'bg-indigo-800' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Reports
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="lg:ml-64 flex flex-col min-h-screen">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-4 py-4 lg:px-8">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-900">@yield('page-title')</h1>
                </div>
            </header>
            
            <!-- Content -->
            <main class="flex-1 p-4 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
