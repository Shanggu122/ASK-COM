<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_call_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('channel');
            $table->string('sender_role');
            $table->string('sender_uid')->nullable();
            $table->string('sender_name')->nullable();
            $table->text('message');
            $table->timestampsTz();

            $table->index(['channel', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_call_messages');
    }
};
