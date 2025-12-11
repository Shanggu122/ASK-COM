<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_chat_messages', function (Blueprint $table) {
            if(!Schema::hasColumn('t_chat_messages','is_read')){
                $table->tinyInteger('is_read')->default(0)->after('status');
                $table->index(['Stud_ID','Prof_ID','is_read']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('t_chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('t_chat_messages','is_read')) {
                $table->dropIndex(['Stud_ID','Prof_ID','is_read']);
                $table->dropColumn('is_read');
            }
        });
    }
};
