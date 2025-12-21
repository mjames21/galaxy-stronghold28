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
        Schema::create('district_populations', function (Blueprint $table) {
            $table->id();

            // Link to districts
            $table->foreignId('district_id')
                  ->constrained('districts')
                  ->cascadeOnDelete();

            // Census year (e.g. 2004, 2015, 2021)
            $table->unsignedSmallInteger('census_year');

            // Total population in that year
            $table->unsignedBigInteger('total_population');

            // Optional: estimated or measured 18+ population
            $table->unsignedBigInteger('population_18_plus')->nullable();

            $table->timestamps();

            // Each district + year combo should be unique
            $table->unique(['district_id', 'census_year'], 'district_year_unique');

            $table->index('census_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('district_populations');
    }
};
