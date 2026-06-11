<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Annual General Meetings. Each AG owns its own immutable voting context and
 * can contain several votes/scrutins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assemblies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('reference')->unique();
            $table->date('held_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assemblies');
    }
};
