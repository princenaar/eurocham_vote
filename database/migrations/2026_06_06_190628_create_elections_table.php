<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A vote/scrutin inside an AG. Type "board" preserves the CA election flow;
 * type "questions" stores a grouped Oui/Non/Abstention vote.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->constrained('assemblies')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('board');
            $table->unsignedSmallInteger('display_order')->default(0);

            $table->string('status')->default('draft');
            $table->enum('mode', ['A', 'B'])->nullable();
            $table->unsignedSmallInteger('candidate_threshold')->default(20);
            $table->unsignedSmallInteger('candidate_min_choices')->default(5);
            $table->unsignedSmallInteger('candidate_max_choices')->default(20);
            $table->unsignedSmallInteger('current_round')->default(1);
            $table->json('runoff_candidate_ids')->nullable();
            $table->unsignedSmallInteger('runoff_seats')->nullable();

            $table->boolean('window_open')->default(false);
            $table->boolean('qr_active')->default(false);
            // Nullable unique slot: MySQL allows many NULL values, but only one "global".
            $table->string('active_slot')->nullable()->unique();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['assembly_id', 'type']);
            $table->index(['status', 'window_open']);
            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
