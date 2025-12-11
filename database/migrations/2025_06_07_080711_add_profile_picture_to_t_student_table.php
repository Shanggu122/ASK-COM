<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasTable('t_student') && !Schema::hasColumn('t_student', 'profile_picture')) {
            Schema::table('t_student', function ($table) {
                $table->string('profile_picture')->nullable();
            });
        }
    }
    public function down()
    {
        if (Schema::hasTable('t_student') && Schema::hasColumn('t_student', 'profile_picture')) {
            Schema::table('t_student', function ($table) {
                $table->dropColumn('profile_picture');
            });
        }
    }
};
