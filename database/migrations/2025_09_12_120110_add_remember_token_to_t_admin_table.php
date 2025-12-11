<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('t_admin')) { return; }
        Schema::table('t_admin', function (Blueprint $table) {
            if (!Schema::hasColumn('t_admin', 'remember_token')) {
                $table->string('remember_token', 100)->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('t_admin')) { return; }
        Schema::table('t_admin', function (Blueprint $table) {
            if (Schema::hasColumn('t_admin', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
        });
    }
};
