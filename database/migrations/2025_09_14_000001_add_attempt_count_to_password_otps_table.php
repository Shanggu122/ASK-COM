<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('password_otps', function (Blueprint $table) {
            if (!Schema::hasColumn('password_otps', 'attempt_count')) {
                $table->unsignedTinyInteger('attempt_count')->default(0)->after('otp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('password_otps', function (Blueprint $table) {
            if (Schema::hasColumn('password_otps', 'attempt_count')) {
                $table->dropColumn('attempt_count');
            }
        });
    }
};
