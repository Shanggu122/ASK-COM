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
        if(!Schema::hasTable('t_consultation_bookings')) return; // guard for test sqlite
        Schema::table('t_consultation_bookings', function (Blueprint $table) {
            if(!Schema::hasColumn('t_consultation_bookings','Subject_ID')) {
                $table->integer('Subject_ID')->after('Consult_type_ID');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(!Schema::hasTable('t_consultation_bookings')) return;
        Schema::table('t_consultation_bookings', function (Blueprint $table) {
            if(Schema::hasColumn('t_consultation_bookings','Subject_ID')) {
                $table->dropColumn('Subject_ID');
            }
        });
    }
};
