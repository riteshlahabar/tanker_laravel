<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A standing order: this customer gets a tanker every N days.
     * `next_run_date` is the single source of truth for when the next one is
     * due — the generator reads it and advances it, nothing else does.
     */
    public function up(): void
    {
        Schema::create('delivery_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            // 1 = daily, 2 = alternate day, 3 = every third day...
            $table->unsignedTinyInteger('interval_days')->default(1);
            // Optional fixed weekdays, ISO-8601: 1 = Monday ... 7 = Sunday.
            $table->json('weekdays')->nullable();

            $table->unsignedInteger('quantity_litres')->nullable();
            // Null falls through to OrderPricing.
            $table->decimal('order_amount', 10, 2)->nullable();

            $table->foreignId('delivery_slot_id')->nullable()->constrained('delivery_slots')->nullOnDelete();
            $table->time('delivery_time')->nullable();

            $table->text('delivery_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->foreignId('preferred_driver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date')->nullable()->index();

            $table->boolean('is_paused')->default(false);
            $table->date('pause_until')->nullable();

            $table->boolean('status')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'is_paused', 'next_run_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_schedules');
    }
};
