<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — tiebreaker runoff (vote de départage). A boundary tie in Mode A is
 * resolved by re-opening a restricted scrutin among only the tied candidates for
 * the remaining seats. Votes are scoped to a `round` so a company votes once per
 * round (one vote each — rule 1) without breaching the original single-vote rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->unsignedSmallInteger('round')->default(1)->after('company_id');
        });

        // Add the composite unique first so the company_id FK keeps a usable index,
        // then drop the original single-column unique (one vote per company per round).
        Schema::table('votes', function (Blueprint $table) {
            $table->unique(['company_id', 'round']);
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique('votes_company_id_unique');
        });

        Schema::table('elections', function (Blueprint $table) {
            // The scrutin round currently accepting ballots (1 = main vote, 2+ = runoffs).
            $table->unsignedSmallInteger('current_round')->default(1)->after('candidate_threshold');
            // The tied candidates standing in the active runoff, and the seats it fills.
            $table->json('runoff_candidate_ids')->nullable()->after('current_round');
            $table->unsignedSmallInteger('runoff_seats')->nullable()->after('runoff_candidate_ids');
        });
    }

    public function down(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropColumn(['current_round', 'runoff_candidate_ids', 'runoff_seats']);
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'round']);
            $table->unique('company_id');
            $table->dropColumn('round');
        });
    }
};
