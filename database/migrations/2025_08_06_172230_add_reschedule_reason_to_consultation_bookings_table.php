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
        if (Schema::hasTable('t_consultation_bookings')) {
            if (!Schema::hasColumn('t_consultation_bookings', 'reschedule_reason')) {
                Schema::table('t_consultation_bookings', function (Blueprint $table) {
                    $table->text('reschedule_reason')->nullable()->after('Status');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('t_consultation_bookings') && Schema::hasColumn('t_consultation_bookings', 'reschedule_reason')) {
            Schema::table('t_consultation_bookings', function (Blueprint $table) {
                $table->dropColumn('reschedule_reason');
            });
        }
    }
};
