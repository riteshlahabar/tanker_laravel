<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Not this day." Held against the schedule and the date rather than an
     * order, so it works whether or not the order has been generated yet and
     * survives a regeneration.
     */
    public function up(): void
    {
        Schema::create('schedule_skips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_schedule_id')->constrained('delivery_schedules')->cascadeOnDelete();
            $table->date('skip_date');
            $table->string('reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            // The order that was cancelled, when one had already been created.
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();

            $table->unique(['delivery_schedule_id', 'skip_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_skips');
    }
};
