<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $sub = $request->user()?->subscription('default');

        // Same predicate as listingShouldBeVisible(): paying or in grace.
        // A failed renewal moves the subscription to past_due, which fails this
        // check, so access is pulled automatically with no manual step.
        $allowed = $sub && ($sub->onGracePeriod()
            || in_array($sub->stripe_status, ['active', 'trialing'], true));

        if (! $allowed) {
            return redirect()->route('pricing')
                ->with('status', 'An active subscription is required to view that area.');
        }

        return $next($request);
    }
}
