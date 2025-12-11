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
        if (!Schema::hasTable('professors') || !Schema::hasTable('t_subject')) {
            return; // skip in minimal test schema
        }
        if (!Schema::hasTable('professor_subject')) {
            Schema::create('professor_subject', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('Prof_ID');
                $table->unsignedBigInteger('Subject_ID');
                $table->foreign('Prof_ID')->references('Prof_ID')->on('professors')->onDelete('cascade');
                $table->foreign('Subject_ID')->references('Subject_ID')->on('t_subject')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('professor_subject')) {
            Schema::drop('professor_subject');
        }
    }
};
