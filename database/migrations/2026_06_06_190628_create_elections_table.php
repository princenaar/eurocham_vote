<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single configurable scrutin that gates the voter flow (CLAUDE.md rules 4 & 6).
 * One row is expected; the admin toggles the QR code and the voting window here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Assemblée Générale EUROCHAM 2026');

            // Scrutin mode, resolved from candidate count. Null until candidates exist.
            $table->enum('mode', ['A', 'B'])->nullable();
            // Threshold that separates Mode A from Mode B (rule 4: 20 seats).
            $table->unsignedSmallInteger('candidate_threshold')->default(20);

            // Voting window + QR gate, toggled remotely by the admin (rule 6).
            $table->boolean('window_open')->default(false);
            $table->boolean('qr_active')->default(false);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
