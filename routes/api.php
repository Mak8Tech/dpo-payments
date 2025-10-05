<?php

use Illuminate\Support\Facades\Route;
use Mak8Tech\DpoPayments\Http\Controllers\PaymentController;
use Mak8Tech\DpoPayments\Http\Controllers\SubscriptionController;

Route::prefix('api/dpo')->middleware(['api'])->name('api.dpo.')->group(function () {
    // Payment endpoints
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::post('/', [PaymentController::class, 'create'])->name('create');
        Route::get('/{reference}/status', [PaymentController::class, 'status'])->name('status');
        Route::post('/{reference}/refund', [PaymentController::class, 'refund'])->name('refund');
        Route::post('/{reference}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
    });

    // Subscription endpoints
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::post('/', [SubscriptionController::class, 'create'])->name('create');
        Route::get('/{reference}', [SubscriptionController::class, 'show'])->name('show');
        Route::put('/{reference}', [SubscriptionController::class, 'update'])->name('update');
        Route::post('/{reference}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{reference}/pause', [SubscriptionController::class, 'pause'])->name('pause');
        Route::post('/{reference}/resume', [SubscriptionController::class, 'resume'])->name('resume');
    });

    // Utility endpoints
    Route::get('/countries', [PaymentController::class, 'countries'])->name('countries');

    // Webhook endpoint
    Route::post('/notify', [PaymentController::class, 'notify'])
        ->middleware('dpo.callback')
        ->name('notify');
});
