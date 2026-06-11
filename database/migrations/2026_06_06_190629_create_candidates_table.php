<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidates for one CA board vote. Count drives the Mode A/B rule per vote.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->foreignId('assembly_company_id')->constrained('assembly_companies')->restrictOnDelete();
            $table->string('name');
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('auto_elected')->default(false);
            $table->timestamps();

            $table->index(['election_id', 'display_order']);
            $table->index('assembly_company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
