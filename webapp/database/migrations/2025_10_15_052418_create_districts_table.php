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
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // e.g. "Bo", "Western Area Urban"
            $table->string('code', 10)->unique();   // e.g. "BO", "WAU"
            $table->string('region', 50)->nullable(); // e.g. "South", "North", etc.
            $table->timestamps();

            $table->index('name');
            $table->index('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
