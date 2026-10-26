<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A standing order priced the same way every other order is.
 *
 * `delivery_schedules.order_amount` — "Fixed Amount" on the form — was the
 * last place a total could be typed in directly, and it ignored
 * `tanker_count` exactly as the deleted `default_order_amount` setting did:
 * a routine for two tankers a visit with 500 in that box billed 500 a visit,
 * not 1,000. The same trap, one screen further along.
 *
 * It becomes `tanker_rate`, nullable, in the one unit the system has. Left
 * empty, each generated order prices from the vehicle that goes out; filled
 * in, that rate is the routine's own and the order carries `rate_fixed`.
 *
 * Existing rows are converted rather than cleared: a fixed amount divided by
 * the loads per visit is the rate that was in effect, so the figure the owner
 * entered keeps billing the same for a one-tanker routine and starts billing
 * correctly for a multi-tanker one.
 *
 * The coordinates go at the same time. Nothing collected them — the admin
 * typed latitude and longitude by hand on four forms, or more often did not —
 * and no screen ever read them back. Addresses are text everywhere now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_schedules', function (Blueprint $table) {
            $table->decimal('tanker_rate', 10, 2)->nullable()->after('tanker_count');
        });

        DB::statement(
            'UPDATE delivery_schedules SET tanker_rate = ROUND(order_amount / '
            .'(CASE WHEN tanker_count < 1 THEN 1 ELSE tanker_count END), 2) '
            .'WHERE order_amount IS NOT NULL',
        );

        Schema::table('delivery_schedules', function (Blueprint $table) {
            $table->dropColumn(['order_amount', 'latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('delivery_schedules', function (Blueprint $table) {
            $table->decimal('order_amount', 10, 2)->nullable()->after('tanker_count');
            $table->decimal('latitude', 10, 7)->nullable()->after('delivery_address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        DB::statement(
            'UPDATE delivery_schedules SET order_amount = ROUND(tanker_rate * '
            .'(CASE WHEN tanker_count < 1 THEN 1 ELSE tanker_count END), 2) '
            .'WHERE tanker_rate IS NOT NULL',
        );

        Schema::table('delivery_schedules', function (Blueprint $table) {
            $table->dropColumn('tanker_rate');
        });
    }
};
