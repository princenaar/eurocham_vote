<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One cast ballot per member company. The UNIQUE constraint on company_id makes a
 * double vote impossible at the DB layer (CLAUDE.md rules 1 & 5). The per-candidate
 * choices live in vote_selections and are not exposed to third parties (rule 7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();

            // The voting company — one vote each (rule 1). UNIQUE enforces anti-double-vote.
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unique('company_id');

            // Optional proxy: the represented company's name when present (rule 3).
            $table->string('proxy_company_name')->nullable();

            // Unique timestamped reference number shown after submit (rule 5).
            $table->string('reference_number')->unique();

            // Timestamp tied to the voting company for traceability (rule 7).
            $table->timestamp('voted_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
