<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Latitude and longitude leave the project.
 *
 * They were on four forms — customer, filling point, standing order and the
 * app's booking screen — and on all four the only way one was ever filled in
 * was an admin reading two decimals off a map and typing them by hand. In
 * practice they stayed empty, which made every feature built on them a feature
 * that silently did nothing.
 *
 * The one that did read them was the driver's "nearest filling point" list,
 * ordered by a great-circle distance over `spots.latitude/longitude`. With the
 * columns empty the scope's `whereNotNull` filtered every spot out, so the
 * screen showed an empty list and the driver used the ordinary list instead.
 * That endpoint goes with the columns; the driver's filling-point screen is the
 * plain list of usable points, which is what it effectively already was.
 *
 * What remains is the address, as text, which is what everyone actually reads —
 * the driver included, who navigates by the address and the contact mobile that
 * is now required on every filling point.
 *
 * `delivery_schedules` lost its pair in the migration before this one, along
 * with the fixed amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'orders', 'spots'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn(['latitude', 'longitude']);
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'orders', 'spots'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->decimal('latitude', 10, 7)->nullable();
                $blueprint->decimal('longitude', 10, 7)->nullable();
            });
        }
    }
};
