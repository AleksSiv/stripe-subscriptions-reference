<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Middleware\EnsureActiveSubscription;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController;

// Public landing: the two tiers.
Route::get('/', [SubscriptionController::class, 'pricing'])->name('pricing');

Route::middleware('auth')->group(function () {
    Route::post('/subscribe/{tier}', [SubscriptionController::class, 'checkout'])->name('subscribe');
    Route::get('/billing', [SubscriptionController::class, 'billingPortal'])->name('billing');
    Route::get('/account', fn () => view('account'))->name('account');

    Route::get('/member', [MemberController::class, 'index'])
        ->middleware(EnsureActiveSubscription::class)
        ->name('member');
});

// Stripe webhook. No auth; CSRF-exempt (see bootstrap/app.php note in the README).
// Cashier verifies the signature with STRIPE_WEBHOOK_SECRET and syncs its tables;
// the HandleStripeWebhook listener then runs our idempotent side effects.
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('cashier.webhook');
