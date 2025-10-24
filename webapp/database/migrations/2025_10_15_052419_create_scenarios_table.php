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
        Schema::create('scenarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('election_id')->constrained()->cascadeOnDelete();
                $table->foreignId('forecast_run_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');                       // e.g., "Youth +3% turnout"
                $table->json('changes');                      // JSON of parameter deltas (by region/district/party)
                $table->json('summary')->nullable();          // seat delta, etc.
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scenarios');
    }
};
