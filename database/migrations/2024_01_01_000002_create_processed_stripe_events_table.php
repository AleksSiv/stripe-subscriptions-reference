<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processed_stripe_events', function (Blueprint $table) {
            $table->id();
            // The Stripe event id (evt_...). The unique index is what makes our
            // custom side effects idempotent: a redelivered event cannot insert
            // a second row, so it cannot fire the side effect twice.
            $table->string('stripe_event_id')->unique();
            $table->string('type');
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_stripe_events');
    }
};
