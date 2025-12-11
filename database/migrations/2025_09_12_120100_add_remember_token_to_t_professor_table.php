<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Support either legacy table name t_professor or current professors
        $tableName = Schema::hasTable('professors') ? 'professors' : (Schema::hasTable('t_professor') ? 't_professor' : null);
        if (!$tableName) { return; }
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'remember_token')) {
                $table->string('remember_token', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('professors') ? 'professors' : (Schema::hasTable('t_professor') ? 't_professor' : null);
        if (!$tableName) { return; }
        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'remember_token')) {
                $table->dropColumn('remember_token');
            }
        });
    }
};
