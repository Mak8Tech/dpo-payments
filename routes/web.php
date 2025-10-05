<?php

use Illuminate\Support\Facades\Route;
use Mak8Tech\DpoPayments\Http\Controllers\PaymentController;
use Mak8Tech\DpoPayments\Http\Controllers\AdminController;

Route::prefix('dpo')->name('dpo.')->group(function () {
    // Payment callbacks
    Route::get('/callback', [PaymentController::class, 'callback'])->name('callback');
    Route::get('/success', function () {
        return view('dpo::payment.success');
    })->name('payment.success');
    Route::get('/failed', function () {
        return view('dpo::payment.failed');
    })->name('payment.failed');

    // Admin routes (protected by auth middleware in implementation)
    Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
        Route::get('/subscriptions', [AdminController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    });
});
