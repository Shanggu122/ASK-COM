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
        if (Schema::hasTable('t_chat_messages') && !Schema::hasColumn('t_chat_messages', 'Recipient')) {
            Schema::table('t_chat_messages', function (Blueprint $table) {
                $table->string('Recipient')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('t_chat_messages') && Schema::hasColumn('t_chat_messages', 'Recipient')) {
            Schema::table('t_chat_messages', function (Blueprint $table) {
                $table->dropColumn('Recipient');
            });
        }
    }
};
