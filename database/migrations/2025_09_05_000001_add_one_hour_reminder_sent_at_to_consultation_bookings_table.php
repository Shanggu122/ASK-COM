<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('t_consultation_bookings')) return;
        Schema::table('t_consultation_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('t_consultation_bookings', 'one_hour_reminder_sent_at')) {
                $table->timestamp('one_hour_reminder_sent_at')->nullable()->after('reschedule_reason');
            }
        });
    }

    public function down(): void
    {
        if(!Schema::hasTable('t_consultation_bookings')) return;
        Schema::table('t_consultation_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('t_consultation_bookings', 'one_hour_reminder_sent_at')) {
                $table->dropColumn('one_hour_reminder_sent_at');
            }
        });
    }
};
