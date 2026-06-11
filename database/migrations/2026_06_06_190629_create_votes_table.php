<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One cast ballot per member company per vote and round. Board choices live in
 * vote_selections; question responses live in question_responses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('assembly_company_id')->constrained('assembly_companies')->cascadeOnDelete();
            $table->unsignedSmallInteger('round')->default(1);

            // Vote cast by proxy for the selected company when true.
            $table->boolean('is_proxy')->default(false);

            // Unique timestamped reference number shown after submit (rule 5).
            $table->string('reference_number')->unique();

            // Timestamp tied to the voting company for traceability (rule 7).
            $table->timestamp('voted_at');

            $table->timestamps();

            $table->unique(['election_id', 'company_id', 'round']);
            $table->index(['election_id', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
