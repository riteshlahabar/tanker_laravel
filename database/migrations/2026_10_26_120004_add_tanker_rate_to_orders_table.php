<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The rate per tanker becomes a column on the order.
 *
 * It was never stored before: assignment multiplied the vehicle's rate by the
 * count and kept only the product, so an order said what it cost but not how
 * that was arrived at. With the rate editable on the order form — the admin may
 * sell a load at something other than the vehicle's usual price — the figure
 * that produced the total has to be the order's own, or the total cannot be
 * explained to a customer who asks.
 *
 * So the order now carries the whole rule it was priced by:
 *
 *     tanker_rate × tanker_count = order_amount
 *
 * `amount_fixed` is renamed `rate_fixed` with it. The flag always meant "this
 * figure was given, not calculated — do not re-price it from whichever tanker
 * goes out"; what is given is now the rate, and the amount simply follows it.
 *
 * Existing rows are backfilled from the arithmetic they already hold, so every
 * historical order keeps the amount it was billed at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('tanker_rate', 10, 2)->default(0)->after('tanker_count');
        });

        // The rate each order was actually priced at: its own total divided by
        // the number of loads. `tanker_count` is 1 or more on every row (the
        // 2026-10-16 migration backfilled it), so there is no divide by zero.
        // Written the long way rather than with GREATEST(), which SQLite — the
        // driver a quick local test run uses — does not have.
        DB::statement(
            'UPDATE orders SET tanker_rate = ROUND(order_amount / '
            .'(CASE WHEN tanker_count < 1 THEN 1 ELSE tanker_count END), 2)',
        );

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('amount_fixed', 'rate_fixed');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('rate_fixed', 'amount_fixed');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tanker_rate');
        });
    }
};
