<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The candidates chosen on a Mode A ballot (within the configured CA limits).
 * Kept in a separate table so individual choices stay decoupled from the voter's
 * identity except for admin audit (rule 7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vote_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vote_id')->constrained('votes')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->timestamps();

            // A candidate can be selected at most once per ballot.
            $table->unique(['vote_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_selections');
    }
};
