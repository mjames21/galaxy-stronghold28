<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('polling_stations', function (Blueprint $table) {
            $table->id();

            // Link to district
            $table->foreignId('district_id')
                  ->constrained('districts')
                  ->cascadeOnDelete();

            // Human-readable name (can be same as code if needed)
            $table->string('name');

            // Official code from NEC, used in CSV imports
            $table->string('code', 50)->index();

            // Registered voters at this station (from NEC data)
            $table->unsignedInteger('registered_voters')->nullable();

            // Optional location details (you can expand later)
            $table->string('centre_name')->nullable();
            $table->string('section')->nullable();
            $table->string('ward')->nullable();

            $table->timestamps();

            // Avoid duplicates of (district, code)
            $table->unique(['district_id', 'code'], 'district_station_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('polling_stations');
    }
};
