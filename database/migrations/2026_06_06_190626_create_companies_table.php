<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eligible member companies, imported by the admin before the AG (CLAUDE.md rule 2).
 * The election's identity is the member company, not a person (rule 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Normalized key (lowercased + trimmed) used to match imports and voter input.
            $table->string('normalized_name')->unique();

            // Eligibility inputs (rule 2): up to date on the 2025 survey, OR 2025 dues,
            // OR — for new members — entry fees + 2026 dues (captured as new_member_2026).
            $table->boolean('survey_2025')->default(false);
            $table->boolean('dues_2025')->default(false);
            $table->boolean('new_member_2026')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
