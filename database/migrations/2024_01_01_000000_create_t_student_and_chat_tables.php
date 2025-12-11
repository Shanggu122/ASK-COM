<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('t_student')) {
            Schema::create('t_student', function (Blueprint $table) {
                $table->string('Stud_ID', 9)->primary();
                $table->string('Name')->nullable();
                $table->string('Dept_ID', 20)->nullable();
                $table->string('Email')->nullable();
                $table->string('Password')->nullable();
                $table->string('profile_picture')->nullable();
            });
        }

        if (!Schema::hasTable('t_chat_messages')) {
            Schema::create('t_chat_messages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('Booking_ID')->nullable();
                $table->string('Sender')->nullable();
                $table->string('Recipient')->nullable();
                $table->text('Message')->nullable();
                $table->timestamp('Created_At')->nullable();
                $table->string('status')->nullable();
                // Columns added later by migrations (file_path, file_type, original_name, timestamps) will be appended
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('t_chat_messages');
        Schema::dropIfExists('t_student');
    }
};
