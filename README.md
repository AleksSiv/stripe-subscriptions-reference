# Recurring Stripe subscriptions: Laravel + Cashier reference

A reference implementation of **recurring** subscription billing (not one-time
payments) with Laravel Cashier, by [Bineks](https://bineks.net). It shows how we
approach recurring billing: the parts that separate a real subscription system from a
simple charge, in clean, readable code you can clone and run.

It demonstrates the three things that matter most in subscription and directory builds:

1. **Two recurring tiers** with Stripe Checkout and a hosted billing portal.
2. **Idempotent webhook handling**, so a redelivered event never fires a side effect twice.
3. **Access that follows subscription state**, so when a renewal fails, access and
   "listing visibility" are pulled automatically, and return when payment recovers.

---

## Repository layout

```
.
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   ├── MemberController.php
│   │   │   └── SubscriptionController.php
│   │   └── Middleware/
│   │       └── EnsureActiveSubscription.php
│   ├── Listeners/
│   │   └── HandleStripeWebhook.php
│   └── Models/
│       ├── ProcessedStripeEvent.php
│       └── User.php
├── config/
│   └── subscription.php
├── database/
│   └── migrations/
│       ├── 2024_01_01_000001_add_listing_visibility_to_users.php
│       └── 2024_01_01_000002_create_processed_stripe_events_table.php
├── resources/
│   └── views/
│       ├── account.blade.php
│       ├── member.blade.php
│       └── pricing.blade.php
└── routes/
    └── web.php
```

---

## What each piece is for

| Concern | Where it lives |
|---|---|
| Subscription billing, two tiers | `config/subscription.php`, `SubscriptionController@checkout` |
| Webhook handling | `routes/web.php` (Cashier controller) + `HandleStripeWebhook` listener |
| Event deduplication / idempotency | `processed_stripe_events` table + the guard in `HandleStripeWebhook` |
| Account status that follows billing | `HandleStripeWebhook@syncVisibility`, `EnsureActiveSubscription` middleware |
| Payment-failure handling (dunning) | `HandleStripeWebhook@onPaymentFailed` |

---

## Install

Assumes a fresh Laravel 11/12 app (PHP 8.2+) with auth scaffolding (e.g. Breeze), so
you have a logged-in `User` to subscribe.

```bash
composer require laravel/cashier
php artisan vendor:publish --tag="cashier-migrations"
```

Copy the files into the matching paths in your app (`app/...`, `config/...`,
`database/migrations/...`, `resources/views/...`, `routes/web.php`). The provided
`User.php` already has the `Billable` trait; merge it into your existing model if needed.

```bash
php artisan migrate          # Cashier tables + the two migrations here
cp .env.example .env         # or merge the Stripe block into your existing .env
php artisan key:generate
```

### One-time wiring

**1. Register the webhook listener.** In `app/Providers/AppServiceProvider.php`, `boot()`:

```php
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookHandled;
use App\Listeners\HandleStripeWebhook;

public function boot(): void
{
    Event::listen(WebhookHandled::class, HandleStripeWebhook::class);
}
```

**2. Exempt the webhook route from CSRF.** In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['stripe/*']);
})
```

---

## Stripe dashboard setup (test mode)

1. Switch the dashboard to **Test mode**.
2. **Products → add product.** Add two **recurring** prices (monthly), e.g. Basic $19
   and Pro $49. Copy each `price_...` id into `STRIPE_PRICE_BASIC` / `STRIPE_PRICE_PRO`.
3. **Developers → API keys.** Copy the test publishable and secret keys into
   `STRIPE_KEY` / `STRIPE_SECRET`.
4. **Webhooks.** Add an endpoint at `https://your-host/stripe/webhook`, or use the
   Stripe CLI (below). Copy the signing secret into `STRIPE_WEBHOOK_SECRET`. Subscribe
   it to at least: `invoice.payment_succeeded`, `invoice.payment_failed`,
   `customer.subscription.updated`, `customer.subscription.deleted`.

---

## Run and test the happy path

```bash
php artisan serve
```

Log in, open `/`, choose a tier, pay with the success test card
`4242 4242 4242 4242` (any future expiry, any CVC). You land on `/member`,
`listing_visible` is `true`, and Cashier's `subscriptions` table has an `active` row.

---

## Test the part that matters: a failed renewal (dunning)

This is what separates recurring from one-time, so it is worth exercising on purpose.

**Option A, Stripe CLI (fastest):**

```bash
stripe login
stripe listen --forward-to localhost:8000/stripe/webhook
# in another terminal:
stripe trigger invoice.payment_failed
```

The subscription moves toward `past_due`, `syncVisibility` flips `listing_visible` to
`false`, and `/member` becomes unreachable. No manual step.

**Option B, Test Clocks (a true renewal cycle).** Create a customer on a test clock,
subscribe with the card `4000 0000 0000 0341` (attaches but fails on the next charge),
then advance the clock past the renewal date. Stripe attempts the renewal, it fails,
and the same flow runs.

---

## Prove idempotency

In the Stripe dashboard, open any delivered event and click **Resend**, or re-run the
CLI trigger. Confirm that `processed_stripe_events` still has exactly **one** row for
that event id and the side effect did not run twice. The unique index rejects the
second insert, so the listener returns early.

---

## Why the visibility check is explicit

`User::listingShouldBeVisible()` does not lean on Cashier's `active()` / `valid()`
helpers, because their treatment of `past_due` varies between versions. Instead it
states the rule plainly: visible while `active`/`trialing` or `onGracePeriod()`, hidden
otherwise. On a live directory that single predicate decides whether a paying
professional's listing is shown, so it should be unambiguous.

---

## About Bineks

[Bineks](https://bineks.net) is a small in-house team shipping full-cycle web work for
English and European markets since 2012. Design, development, DevOps, hosted on our own
infrastructure. Get in touch: dir@bineks.net.

## License

MIT. See [LICENSE](LICENSE).
