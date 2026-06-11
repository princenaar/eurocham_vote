<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Questions inside a grouped Oui/Non/Abstention vote.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('election_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('elections')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['election_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_questions');
    }
};
