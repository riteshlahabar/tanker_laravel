<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One unit for the price of water, not two.
 *
 * `default_order_amount` was a flat price **per delivery** that ignored how
 * many tanker-loads were ordered. It predates the per-tanker model — it was
 * the fallback for the old `price_per_litre` — and was carried along
 * unexamined when pricing moved to the load. Two tankers at a 500 rate cost
 * 1000; two tankers on a 500 flat amount cost 500. Same number, same Settings
 * screen, opposite meaning, and nothing on the form said so.
 *
 * It is deleted rather than hidden: a setting that silently switches off
 * quantity is worse than no setting at all. When no rate is configured an
 * order now prices at 0, which reads as "pricing is not set up" — the honest
 * answer — instead of quietly charging a flat fee.
 *
 * `price_per_tanker` becomes `default_tanker_rate` in the same pass, so the
 * pair reads as what it is: a vehicle carries `tankers.tanker_rate`, and this
 * is the default for a vehicle that has none. Same unit, different scope. The
 * value carries across unchanged — it already was a price per load.
 */
return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('settings')->where('key', 'price_per_tanker')->value('value');

        DB::table('settings')->whereIn('key', ['default_order_amount', 'price_per_tanker'])->delete();

        DB::table('settings')->updateOrInsert(
            ['key' => 'default_tanker_rate'],
            ['value' => $existing ?? '0', 'updated_at' => now(), 'created_at' => now()],
        );
    }

    public function down(): void
    {
        $existing = DB::table('settings')->where('key', 'default_tanker_rate')->value('value');

        DB::table('settings')->where('key', 'default_tanker_rate')->delete();

        DB::table('settings')->updateOrInsert(
            ['key' => 'price_per_tanker'],
            ['value' => $existing ?? '0', 'updated_at' => now(), 'created_at' => now()],
        );

        DB::table('settings')->updateOrInsert(
            ['key' => 'default_order_amount'],
            ['value' => '0', 'updated_at' => now(), 'created_at' => now()],
        );
    }
};
