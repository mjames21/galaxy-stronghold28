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
        Schema::create('forecast_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('election_id')->constrained()->cascadeOnDelete();
                $table->string('label')->default('Base Run');
                $table->unsignedInteger('simulations')->default(1000);
                $table->json('params')->nullable();           // stores alpha/beta priors, etc.
                $table->json('summary')->nullable();          // national means, intervals
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forecast_runs');
    }
};
