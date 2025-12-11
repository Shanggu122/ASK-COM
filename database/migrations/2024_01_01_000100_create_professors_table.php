<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('professors')) {
            Schema::create('professors', function (Blueprint $table) {
                // Using string to align with login form ID usage (alphanumeric like P1234)
                $table->string('Prof_ID', 12)->primary();
                $table->string('Name')->nullable();
                $table->string('Dept_ID', 50)->nullable();
                $table->string('Email')->nullable();
                $table->string('Password')->nullable();
                $table->string('profile_picture')->nullable();
                $table->text('Schedule')->nullable();
                $table->string('remember_token',100)->nullable();
                $table->boolean('is_active')->default(1);
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('professors');
    }
};
