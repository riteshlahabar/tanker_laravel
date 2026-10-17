<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `default_driver_rate` becomes `default_monthly_salary`. A settings row, not a
 * column, so the change is data — but it belongs in a migration all the same,
 * or the live server would keep answering with a key nothing reads any more
 * while the Settings form refuses to save without the new one.
 *
 * The value is deliberately NOT carried across: 150 per delivery is meaningless
 * as a monthly salary. It lands on the config default, and only ever pre-fills
 * a new driver's form — each driver's real salary is typed on their own form,
 * where it is required.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'default_driver_rate')->delete();

        DB::table('settings')->updateOrInsert(
            ['key' => 'default_monthly_salary'],
            ['value' => '15000', 'updated_at' => now(), 'created_at' => now()],
        );
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'default_monthly_salary')->delete();

        DB::table('settings')->updateOrInsert(
            ['key' => 'default_driver_rate'],
            ['value' => '150', 'updated_at' => now(), 'created_at' => now()],
        );
    }
};
