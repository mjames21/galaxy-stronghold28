<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gotv_priors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();

            // Turnout priors: Beta(alpha, beta)
            $table->decimal('alpha', 10, 3)->default(3.000);
            $table->decimal('beta', 10, 3)->default(2.000);

            // Baseline snapshot for comparison
            $table->decimal('baseline_alpha', 10, 3)->nullable();
            $table->decimal('baseline_beta', 10, 3)->nullable();

            $table->timestamps();

            $table->unique(['election_id', 'district_id']);
            $table->index(['election_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gotv_priors');
    }
};
