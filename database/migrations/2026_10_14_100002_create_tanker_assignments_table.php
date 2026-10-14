<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * History of which tanker each driver drives. The row with a null `assigned_to`
     * is the driver's current tanker; reassigning closes that row and opens a new
     * one, so past assignments are never overwritten.
     */
    public function up(): void
    {
        Schema::create('tanker_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tanker_id')->constrained('tankers')->cascadeOnDelete();
            $table->date('assigned_from');
            $table->date('assigned_to')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'assigned_to']);
            $table->index(['tanker_id', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanker_assignments');
    }
};
