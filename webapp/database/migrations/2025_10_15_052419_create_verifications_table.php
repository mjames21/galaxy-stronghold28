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
        Schema::create('verifications', function (Blueprint $table) {
           $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('polling_station_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('reported_turnout')->nullable();
            $table->json('reported_votes')->nullable();   // {party_id: votes}
            $table->float('z_score')->nullable();         // anomaly score vs expectation
            $table->string('status')->default('pending'); // pending|consistent|flagged
            $table->timestamps();

            $table->unique(['election_id','polling_station_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
