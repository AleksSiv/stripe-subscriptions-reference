<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function pricing()
    {
        return view('pricing', ['tiers' => config('subscription.tiers')]);
    }

    /**
     * Start a Stripe Checkout session for the chosen tier. Cashier creates the
     * Stripe customer (if needed) and the subscription once payment succeeds.
     */
    public function checkout(Request $request, string $tier)
    {
        $tiers = config('subscription.tiers');
        abort_unless(isset($tiers[$tier]), 404);

        return $request->user()
            ->newSubscription('default', $tiers[$tier]['price_id'])
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('member'),
                'cancel_url'  => route('pricing'),
            ]);
    }

    /**
     * Hand the user to Stripe's hosted billing portal to upgrade, downgrade,
     * update a card or cancel. State changes flow back to us via webhooks.
     */
    public function billingPortal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('account'));
    }
}
