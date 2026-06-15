<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historical scope of each CA tiebreaker round. Votes store the ballots; this
 * table stores which tied candidates and seats were officially in play.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_runoff_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->unsignedSmallInteger('round');
            $table->json('candidate_ids');
            $table->unsignedSmallInteger('seats');
            $table->timestamps();

            $table->unique(['election_id', 'round']);
            $table->index('round');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_runoff_rounds');
    }
};
