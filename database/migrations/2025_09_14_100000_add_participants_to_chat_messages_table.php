<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_chat_messages', function (Blueprint $table) {
            // Allow null to preserve legacy rows that only have Booking_ID
            if (!Schema::hasColumn('t_chat_messages','Stud_ID')) {
                $table->unsignedBigInteger('Stud_ID')->nullable()->after('Booking_ID');
            }
            if (!Schema::hasColumn('t_chat_messages','Prof_ID')) {
                $table->unsignedBigInteger('Prof_ID')->nullable()->after('Stud_ID');
            }
            // Indexes to speed up lookups by participants
            $table->index(['Stud_ID','Prof_ID']);
        });
    }

    public function down(): void
    {
        Schema::table('t_chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('t_chat_messages','Prof_ID')) {
                $table->dropIndex(['Stud_ID','Prof_ID']);
                $table->dropColumn('Prof_ID');
            }
            if (Schema::hasColumn('t_chat_messages','Stud_ID')) {
                $table->dropColumn('Stud_ID');
            }
        });
    }
};
