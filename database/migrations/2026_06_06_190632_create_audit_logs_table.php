<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only activity journal for audit/dispute, admin-readable only (CLAUDE.md rule 7).
 * Rows are never updated or deleted in normal operation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // Who triggered it: admin user id when known, null for public voter actions.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            // Optional structured context (e.g. company id, reference number).
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            // Append-only: creation time only, no updated_at.
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
