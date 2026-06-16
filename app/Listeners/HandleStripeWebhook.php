<?php

namespace App\Listeners;

use App\Models\ProcessedStripeEvent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookHandled;

class HandleStripeWebhook
{
    /**
     * Fires AFTER Cashier has verified the signature and synced its own tables,
     * so we can read the resulting subscription state. We layer our own custom,
     * idempotent side effects on top: keep listing visibility in step with the
     * subscription, and react to failed payments.
     */
    public function handle(WebhookHandled $event): void
    {
        $payload = $event->payload;
        $eventId = $payload['id'] ?? null;
        $type    = $payload['type'] ?? null;

        if (! $eventId) {
            return;
        }

        // --- Idempotency guard --------------------------------------------
        // Stripe can deliver the same event more than once (retries, manual
        // resend). The unique index on stripe_event_id means a redelivery
        // cannot insert a second row, so our side effects run exactly once.
        if (ProcessedStripeEvent::where('stripe_event_id', $eventId)->exists()) {
            return;
        }

        try {
            ProcessedStripeEvent::create([
                'stripe_event_id' => $eventId,
                'type'            => (string) $type,
            ]);
        } catch (QueryException) {
            // Lost a race with a concurrent delivery of the same event. Safe to stop.
            return;
        }
        // ------------------------------------------------------------------

        $customerId = $payload['data']['object']['customer'] ?? null;
        $user = $customerId ? User::where('stripe_id', $customerId)->first() : null;

        if (! $user) {
            return;
        }

        match ($type) {
            'invoice.payment_failed'        => $this->onPaymentFailed($user),
            'invoice.payment_succeeded'     => $this->syncVisibility($user),
            'customer.subscription.updated' => $this->syncVisibility($user),
            'customer.subscription.deleted' => $this->syncVisibility($user),
            default                         => null,
        };
    }

    private function onPaymentFailed(User $user): void
    {
        $this->syncVisibility($user);

        // A real build queues a dunning email here. Logged to keep the demo self-contained.
        Log::info("Stripe dunning: payment failed for user {$user->id}; visibility synced.");
        // Mail::to($user)->queue(new \App\Mail\PaymentFailed($user));
    }

    private function syncVisibility(User $user): void
    {
        $user->load('subscriptions'); // read the state Cashier just wrote

        $shouldShow = $user->listingShouldBeVisible();

        if ($user->listing_visible !== $shouldShow) {
            $user->forceFill(['listing_visible' => $shouldShow])->save();
            Log::info("Listing for user {$user->id} set to " . ($shouldShow ? 'visible' : 'hidden'));
        }
    }
}
