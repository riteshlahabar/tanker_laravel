<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The per-delivery earning snapshot goes with the per-delivery rate. A salaried
 * driver earns nothing per order, so keeping this column would mean writing
 * zero onto every order for ever.
 *
 * This drops recorded history: past orders lose the driver cost they carried,
 * and the driver report's figures go to zero for every range. The owner
 * confirmed that on 2026-10-17 — dump the orders table before running this on
 * a server that holds real deliveries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('driver_earning');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('driver_earning', 10, 2)->default(0)->after('refill_charge');
        });
    }
};
