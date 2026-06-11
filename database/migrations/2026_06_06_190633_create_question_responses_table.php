<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One Oui/Non/Abstention response per question on a submitted ballot.
 * NULL represents abstention.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vote_id')->constrained('votes')->cascadeOnDelete();
            $table->foreignId('election_question_id')->constrained('election_questions')->cascadeOnDelete();
            $table->boolean('answer')->nullable();
            $table->timestamps();

            $table->unique(['vote_id', 'election_question_id']);
            $table->index(['election_question_id', 'answer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_responses');
    }
};
