<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->string('singleton_key')->default('current')->after('id');
            $table->string('status')->default('draft')->after('name');

            $table->unique('singleton_key');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropUnique(['singleton_key']);
            $table->dropIndex(['status']);
            $table->dropColumn(['singleton_key', 'status']);
        });
    }
};
