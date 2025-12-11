<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('t_student')) { return; }
        // Add remember_token only if missing. Some legacy schemas may not have an is_active column;
        // in that case we simply append the column (no after() call) to avoid SQL error.
        Schema::table('t_student', function (Blueprint $table) {
            if (!Schema::hasColumn('t_student', 'remember_token')) {
                if (Schema::hasColumn('t_student', 'is_active')) {
                    $table->string('remember_token', 100)->nullable()->after('is_active');
                } else {
                    $table->string('remember_token', 100)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('t_student')) { return; }
        Schema::table('t_student', function (Blueprint $table) {
            if (Schema::hasColumn('t_student', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
        });
    }
};
