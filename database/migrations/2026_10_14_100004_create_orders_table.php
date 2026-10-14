<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 30)->unique();

            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->text('delivery_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('delivery_slot')->nullable();

            $table->unsignedInteger('quantity_litres')->nullable();
            $table->decimal('order_amount', 10, 2)->default(0);

            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tanker_id')->nullable()->constrained('tankers')->nullOnDelete();
            $table->foreignId('spot_id')->nullable()->constrained('spots')->nullOnDelete();

            // Snapshots taken when the event happens, so later rate changes
            // never alter historical reports.
            $table->decimal('refill_charge', 10, 2)->default(0);
            $table->decimal('driver_earning', 10, 2)->default(0);

            $table->string('status', 20)->default('pending')->index();

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('refilled_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['driver_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
