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
        if (Schema::hasTable('t_chat_messages')) {
            Schema::table('t_chat_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('t_chat_messages', 'file_path')) {
                    $table->string('file_path')->nullable();
                }
                if (!Schema::hasColumn('t_chat_messages', 'file_type')) {
                    $table->string('file_type')->nullable();
                }
                if (!Schema::hasColumn('t_chat_messages', 'original_name')) {
                    $table->string('original_name')->nullable();
                }
                // Only add laravel timestamps if they do not already exist
                if (!Schema::hasColumn('t_chat_messages', 'created_at') && !Schema::hasColumn('t_chat_messages', 'updated_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('t_chat_messages')) {
            Schema::table('t_chat_messages', function (Blueprint $table) {
                if (Schema::hasColumn('t_chat_messages', 'file_path')) {
                    $table->dropColumn('file_path');
                }
                if (Schema::hasColumn('t_chat_messages', 'file_type')) {
                    $table->dropColumn('file_type');
                }
                if (Schema::hasColumn('t_chat_messages', 'original_name')) {
                    $table->dropColumn('original_name');
                }
                if (Schema::hasColumn('t_chat_messages', 'created_at') && Schema::hasColumn('t_chat_messages', 'updated_at')) {
                    $table->dropTimestamps();
                }
            });
        }
    }
};
