<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An order is now for a number of tankers (1, 2, 3…), not a number of litres.
 *
 * Not a rename: `5000` litres is not `5000` tankers. Every order already in
 * the table was one tanker-load, so each is backfilled to 1 and the old
 * column is then dropped.
 *
 * `amount_fixed` records that an amount was given rather than calculated — an
 * admin typing it by hand, or a standing order carrying its own. Assignment
 * re-prices an order from the vehicle it was given, and must not overwrite
 * those figures.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('tanker_count')->default(1)->after('delivery_at');
            $table->boolean('amount_fixed')->default(false)->after('order_amount');
        });

        // Everything already placed was one tanker, and its amount was either
        // typed or already calculated — either way it is the figure of record.
        DB::table('orders')->update(['tanker_count' => 1, 'amount_fixed' => true]);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('quantity_litres');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('quantity_litres')->nullable()->after('delivery_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tanker_count', 'amount_fixed']);
        });
    }
};
