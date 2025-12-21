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
        Schema::create('results', function (Blueprint $table) {
            $table->id();

            // Election this row belongs to
            $table->foreignId('election_id')
                  ->constrained('elections')
                  ->cascadeOnDelete();

            // Geographic dimension: district + polling station
            $table->foreignId('district_id')
                  ->constrained('districts')
                  ->cascadeOnDelete();

            // Nullable so you can also store aggregated per-district rows later if you want
            $table->foreignId('polling_station_id')
                  ->nullable()
                  ->constrained('polling_stations')
                  ->nullOnDelete();

            // Party for this result row
            $table->foreignId('party_id')
                  ->constrained('parties')
                  ->cascadeOnDelete();

            // Core metrics
            $table->unsignedInteger('votes')->default(0);

            // Station-level turnout & registered count (for that station)
            $table->unsignedInteger('turnout')->nullable();    // sum of votes across parties for this PS
            $table->unsignedInteger('registered')->nullable(); // registered voters (for that PS)

            // Optional: any extra JSON (e.g. spoiled votes, notes, source file)
            $table->json('meta')->nullable();

            $table->timestamps();

            // You should not have duplicate rows for the same (election, district, station, party)
            $table->unique(
                ['election_id', 'district_id', 'polling_station_id', 'party_id'],
                'results_unique_elect_dist_ps_party'
            );

            // Helpful indexes for queries
            $table->index(['election_id', 'district_id']);
            $table->index(['election_id', 'party_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
