<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The price of water now comes from one place only: the tanker that delivers
 * it. `default_tanker_rate` is removed with it.
 *
 * A global default could only ever be a guess at what *some* vehicle charges,
 * and the owner's tankers are not the same size — so the guess was wrong for
 * every vehicle that had a rate of its own, and silently filled in a number
 * for every vehicle that did not. The admin now picks the driver on the order
 * form and sees that vehicle's real rate, editable before saving.
 *
 * Consequence, and it is deliberate: an order placed from the customer app has
 * no driver yet, so it has no vehicle and **prices at 0** until an admin
 * assigns one. That is honest — the price genuinely is not known until a
 * tanker is chosen — where the old default quietly quoted a figure the
 * delivery might not match.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'default_tanker_rate')->delete();
    }

    public function down(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'default_tanker_rate'],
            ['value' => '0', 'updated_at' => now(), 'created_at' => now()],
        );
    }
};
