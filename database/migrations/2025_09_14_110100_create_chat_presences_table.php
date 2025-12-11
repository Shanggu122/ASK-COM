<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_presences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Stud_ID')->nullable();
            $table->unsignedBigInteger('Prof_ID')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unique(['Stud_ID','Prof_ID']); // only one row per participant type (one side filled)
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_presences');
    }
};
