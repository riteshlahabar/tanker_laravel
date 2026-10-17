<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a driver spent out of pocket — petrol above all. Deliberately NOT
     * tied to an order: fuel is bought for a shift, not for a delivery, and
     * attaching it to one order would make every per-order figure wrong.
     *
     * There is no approval workflow. The owner chose "just record it"
     * (2026-10-17), so there is no status, no reviewer and no paid flag — the
     * admin simply edits or deletes anything wrong.
     */
    public function up(): void
    {
        Schema::create('driver_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            // Which vehicle it was spent on. Nullable because a toll or a meal
            // belongs to the driver, not to a tanker.
            $table->foreignId('tanker_id')->nullable()->constrained('tankers')->nullOnDelete();
            // When it was spent, not when it was typed in.
            $table->date('expense_date');
            $table->string('category', 20);
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            // The driver themselves, or the admin who entered it on their
            // behalf. This one column is what makes "the admin added it
            // because the driver didn't" visible rather than guessed.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['driver_id', 'expense_date']);
            $table->index('category');
            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_expenses');
    }
};
