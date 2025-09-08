<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('checagens', function (Blueprint $table) {
            $table->dropForeign(['string_id']);
            $table->foreign('string_id')->references('id')->on('strings')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checagens', function (Blueprint $table) {
            $table->dropForeign(['string_id']);
            $table->foreign('string_id')->references('id')->on('strings');
        });
    }
};
