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
        if (Schema::hasTable('professors') && !Schema::hasColumn('professors', 'Schedule')) {
            Schema::table('professors', function (Blueprint $table) {
                $table->text('Schedule')->nullable()->after('profile_picture');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('professors') && Schema::hasColumn('professors', 'Schedule')) {
            Schema::table('professors', function (Blueprint $table) {
                $table->dropColumn('Schedule');
            });
        }
    }
};
