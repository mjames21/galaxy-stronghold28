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
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->foreignId('polling_station_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('votes')->default(0);
            $table->unsignedInteger('turnout')->nullable(); // raw count at station, or district aggregate
            $table->unsignedInteger('registered')->nullable();
            $table->timestamps();

            $table->unique(['election_id','district_id','polling_station_id','party_id'], 'uniq_result_scope');
            $table->index(['election_id','district_id']);
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
