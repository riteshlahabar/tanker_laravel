<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The same switch on the standing orders, so the generator hands the order
 * service a tanker count rather than a litre figure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_schedules', function (Blueprint $table) {
            $table->unsignedTinyInteger('tanker_count')->default(1)->after('weekdays');
        });

        DB::table('delivery_schedules')->update(['tanker_count' => 1]);

        Schema::table('delivery_schedules', function (Blueprint $table) {
            $table->dropColumn('quantity_litres');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_schedules', function (Blueprint $table) {
            $table->unsignedInteger('quantity_litres')->nullable()->after('weekdays');
        });

        Schema::table('delivery_schedules', function (Blueprint $table) {
            $table->dropColumn('tanker_count');
        });
    }
};
