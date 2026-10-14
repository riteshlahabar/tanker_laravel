<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refill points on the route. `refill_charge` is the one-time charge to fill
     * a tanker at this spot.
     */
    public function up(): void
    {
        Schema::create('spots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('refill_charge', 10, 2)->default(0);
            $table->string('water_type')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_mobile', 20)->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spots');
    }
};
