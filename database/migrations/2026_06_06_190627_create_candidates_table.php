<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidates for the 2026 Board. Count drives the scrutin mode (CLAUDE.md rule 4):
 * > 20 candidates => Mode A (pick exactly 20); <= 20 => Mode B (all auto-elected).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('display_order')->default(0);
            // Set true when Mode B applies and the candidate is automatically elected.
            $table->boolean('auto_elected')->default(false);
            $table->timestamps();

            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
