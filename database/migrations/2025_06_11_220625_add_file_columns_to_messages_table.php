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
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                if (!Schema::hasColumn('messages', 'file_path')) {
                    $table->string('file_path')->nullable();
                }
                if (!Schema::hasColumn('messages', 'file_type')) {
                    $table->string('file_type')->nullable();
                }
                if (!Schema::hasColumn('messages', 'original_name')) {
                    $table->string('original_name')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $drops = [];
                foreach (['file_path', 'file_type', 'original_name'] as $col) {
                    if (Schema::hasColumn('messages', $col)) {
                        $drops[] = $col;
                    }
                }
                if (!empty($drops)) {
                    $table->dropColumn($drops);
                }
            });
        }
    }
};
