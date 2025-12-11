<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void {
		if (Schema::hasTable('users')) {
			Schema::table('users', function (Blueprint $table) {
				if (!Schema::hasColumn('users','otp_secret')) $table->string('otp_secret')->nullable();
				if (!Schema::hasColumn('users','otp_enabled_at')) $table->timestamp('otp_enabled_at')->nullable();
			});
		}
		if (Schema::hasTable('professors')) {
			Schema::table('professors', function (Blueprint $table) {
				if (!Schema::hasColumn('professors','otp_secret')) $table->string('otp_secret')->nullable();
				if (!Schema::hasColumn('professors','otp_enabled_at')) $table->timestamp('otp_enabled_at')->nullable();
			});
		}
	}
	public function down(): void {
		if (Schema::hasTable('users')) {
			Schema::table('users', function (Blueprint $table) {
				if (Schema::hasColumn('users','otp_secret')) $table->dropColumn('otp_secret');
				if (Schema::hasColumn('users','otp_enabled_at')) $table->dropColumn('otp_enabled_at');
			});
		}
		if (Schema::hasTable('professors')) {
			Schema::table('professors', function (Blueprint $table) {
				if (Schema::hasColumn('professors','otp_secret')) $table->dropColumn('otp_secret');
				if (Schema::hasColumn('professors','otp_enabled_at')) $table->dropColumn('otp_enabled_at');
			});
		}
	}
};

