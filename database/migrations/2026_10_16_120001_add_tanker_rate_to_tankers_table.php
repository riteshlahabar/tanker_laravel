<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Water is sold by the tanker, not by the litre, and the owner's tankers are
 * not all the same size — so the price of one tanker-load belongs on the
 * vehicle. `capacity_litres` stays as vehicle information; it simply no longer
 * has anything to do with what the customer pays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tankers', function (Blueprint $table) {
            // Nullable: a vehicle without its own rate falls through to the
            // `price_per_tanker` setting, the same way a schedule without an
            // order_amount falls through to OrderPricing.
            $table->decimal('tanker_rate', 10, 2)->nullable()->after('capacity_litres');
        });
    }

    public function down(): void
    {
        Schema::table('tankers', function (Blueprint $table) {
            $table->dropColumn('tanker_rate');
        });
    }
};
