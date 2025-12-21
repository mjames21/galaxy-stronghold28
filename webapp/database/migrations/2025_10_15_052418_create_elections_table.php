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
        Schema::create('elections', function (Blueprint $table) {
            $table->id();

            // Display name (what leadership sees)
            $table->string('name'); // e.g. "2018 Presidential – First Round"

            // Slug used in URLs, imports and model references
            $table->string('slug')->unique(); // e.g. "sl_2018_president_r1"

            // When the election was held (can be null for future planning)
            $table->date('election_date')->nullable();

            // Type: presidential / parliamentary / local, etc.
            $table->string('type', 50)->default('presidential');

            // Round number (1 = first round, 2 = runoff)
            $table->unsignedTinyInteger('round')->default(1);

            // Optional free-text description
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['type', 'round']);
            $table->index('election_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
