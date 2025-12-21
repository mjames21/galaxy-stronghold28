<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voter_registries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_id')->constrained()->cascadeOnDelete();

            // Text fields from CSV (keep as-is)
            $table->string('region')->nullable();
            $table->string('district')->nullable();
            $table->unsignedInteger('constituency')->nullable();
            $table->unsignedInteger('ward')->nullable();

            $table->unsignedBigInteger('centre_id')->nullable();
            $table->string('centre_name')->nullable();

            $table->unsignedInteger('station_id')->nullable(); // 1..N within centre
            $table->string('station_code')->nullable();        // e.g. "1001-1" (CSV "ID")

            $table->unsignedInteger('registered_voters')->nullable();

            $table->timestamps();

            // One polling station per election
            $table->unique(['election_id','centre_id','station_id'], 'vr_unique_station_per_election');
            $table->index(['election_id','district']);
            $table->index(['election_id','region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_registries');
    }
};
