<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('archetype', 32);
            $table->string('gender', 8);
            $table->unsignedSmallInteger('age');
            $table->string('prefecture', 16);
            $table->string('dialect', 16);
            $table->string('voice', 16);
            $table->json('interests');
            $table->float('activity');
            $table->bigInteger('seed');
            $table->timestamp('last_sim_active_at')->nullable();
            $table->timestamps();
            $table->index(['activity']);
        });

        // Single-row key/value store for simulation bookkeeping (clock epoch, counters).
        Schema::create('sim_state', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
        Schema::dropIfExists('sim_state');
    }
};
