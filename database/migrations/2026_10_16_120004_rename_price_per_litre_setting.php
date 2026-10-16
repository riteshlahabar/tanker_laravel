<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `price_per_litre` becomes `price_per_tanker`. A settings row, not a column,
 * so the change is data — but it belongs in a migration all the same, or the
 * live server would keep answering with a key nothing reads any more.
 *
 * The value is deliberately NOT carried across: a per-litre figure (0.25) is
 * meaningless as a per-tanker price. It resets to 0, which means "use the flat
 * default_order_amount" until the owner sets a real rate.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'price_per_litre')->delete();

        DB::table('settings')->updateOrInsert(
            ['key' => 'price_per_tanker'],
            ['value' => '0', 'updated_at' => now(), 'created_at' => now()],
        );
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'price_per_tanker')->delete();

        DB::table('settings')->updateOrInsert(
            ['key' => 'price_per_litre'],
            ['value' => '0', 'updated_at' => now(), 'created_at' => now()],
        );
    }
};
