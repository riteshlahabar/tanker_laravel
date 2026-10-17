<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drivers are on a monthly salary, not a per-delivery rate. Written as a new
 * file rather than an edit to the original users migration because the live
 * server has real rows.
 *
 * Deliberately not a rename: 150 per delivery is not 150 per month, so no
 * value is carried across. Every existing driver lands on 0 and the owner sets
 * the real figure on the driver's edit screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->decimal('monthly_salary', 10, 2)->default(0)->after('per_delivery_rate');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('per_delivery_rate');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->decimal('per_delivery_rate', 10, 2)->default(0)->after('longitude');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('monthly_salary');
        });
    }
};
