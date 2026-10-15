<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_schedule_id')->nullable()->after('customer_id')
                ->constrained('delivery_schedules')->nullOnDelete();

            $table->foreignId('delivery_slot_id')->nullable()->after('delivery_slot')
                ->constrained('delivery_slots')->nullOnDelete();

            // Resolved once at creation: delivery_date + slot start or delivery_time.
            // The six-hour skip check is one comparison against this, and editing
            // a slot's hours later cannot move orders already placed.
            $table->dateTime('delivery_at')->nullable()->after('delivery_slot_id')->index();

            // One order per schedule per date, so a repeated run cannot double-order.
            $table->unique(['delivery_schedule_id', 'delivery_date'], 'orders_schedule_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_schedule_date_unique');
            $table->dropConstrainedForeignId('delivery_schedule_id');
            $table->dropConstrainedForeignId('delivery_slot_id');
            $table->dropColumn('delivery_at');
        });
    }
};
