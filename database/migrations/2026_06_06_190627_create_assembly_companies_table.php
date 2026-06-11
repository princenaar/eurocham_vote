<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot of member-company eligibility for one AG. This freezes the electoral
 * body for historical results even if the global company list changes later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assembly_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->constrained('assemblies')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name');
            $table->boolean('survey_2025')->default(false);
            $table->boolean('dues_2025')->default(false);
            $table->boolean('new_member_2026')->default(false);
            $table->boolean('eligible')->default(false);
            $table->timestamps();

            $table->unique(['assembly_id', 'company_id']);
            $table->unique(['assembly_id', 'normalized_name']);
            $table->index(['assembly_id', 'eligible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assembly_companies');
    }
};
