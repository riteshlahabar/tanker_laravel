<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Named delivery windows. The `start_time` is what the six-hour skip
     * notice is measured back from, so it has to be a real time rather than
     * the free text the orders table used to hold.
     */
    public function up(): void
    {
        Schema::create('delivery_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_slots');
    }
};
