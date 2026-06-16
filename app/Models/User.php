<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'listing_visible'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'listing_visible'   => 'boolean',
        ];
    }

    /**
     * Which tier (basic|pro) the user is on right now, or null if none.
     * Maps the subscription's Stripe price back to a configured tier.
     */
    public function currentTier(): ?string
    {
        $sub = $this->subscription('default');

        if (! $sub) {
            return null;
        }

        foreach (config('subscription.tiers') as $key => $tier) {
            if ($tier['price_id'] === $sub->stripe_price) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Single source of truth for "should this listing be visible".
     * Explicit on purpose, rather than leaning on Cashier's active()/valid()
     * semantics, so the rule is unambiguous:
     *   - visible while paying (active / trialing)
     *   - visible during the grace window after a cancellation
     *   - hidden the moment a renewal fails (past_due) or the sub ends
     */
    public function listingShouldBeVisible(): bool
    {
        $sub = $this->subscription('default');

        if (! $sub) {
            return false;
        }

        if ($sub->onGracePeriod()) {
            return true;
        }

        return in_array($sub->stripe_status, ['active', 'trialing'], true);
    }
}
