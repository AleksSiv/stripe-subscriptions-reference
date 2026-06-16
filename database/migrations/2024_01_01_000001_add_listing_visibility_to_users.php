<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Denormalised flag that mirrors subscription state and is toggled
            // by webhooks. In the directory build this is literally "is this
            // professional's listing shown to the public right now".
            $table->boolean('listing_visible')->default(false)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('listing_visible');
        });
    }
};
