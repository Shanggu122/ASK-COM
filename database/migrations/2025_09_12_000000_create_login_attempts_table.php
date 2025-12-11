<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if(!Schema::hasTable('login_attempts')) {
            Schema::create('login_attempts', function (Blueprint $table) {
                $table->id();
                $table->string('stud_id', 32)->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->boolean('successful')->default(false);
                $table->string('reason', 40)->nullable(); // e.g., not_found, inactive, bad_password, locked
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
