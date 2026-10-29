<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The buzzer's two columns: who has to see a new order, and when they did.
 *
 * One audience per order, so one timestamp rather than a column per role —
 * an order placed by the admin is for its driver to see, one placed by a
 * driver is for the admin, and no order is ever both.
 *
 * Additive only: nothing is dropped, so this is safe to run on the live
 * database without the dump rule 9 demands of the salary migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('alert_for', 16)->nullable()->after('created_by');
            $table->timestamp('alert_seen_at')->nullable()->after('alert_for');

            // The alert list filters on exactly this pair before anything
            // else, and it is read every few seconds by every open app.
            $table->index(['alert_for', 'alert_seen_at'], 'orders_alert_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_alert_idx');
            $table->dropColumn(['alert_for', 'alert_seen_at']);
        });
    }
};
