<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void {
		if (!Schema::hasTable('t_chat_messages')) return;
		Schema::table('t_chat_messages', function (Blueprint $table) {
			if (!Schema::hasColumn('t_chat_messages','is_unsent')) {
				$table->boolean('is_unsent')->default(false);
			}
			if (!Schema::hasColumn('t_chat_messages','edited_at')) {
				$table->timestamp('edited_at')->nullable();
			}
		});
	}
	public function down(): void {
		if (!Schema::hasTable('t_chat_messages')) return;
		Schema::table('t_chat_messages', function (Blueprint $table) {
			if (Schema::hasColumn('t_chat_messages','is_unsent')) $table->dropColumn('is_unsent');
			if (Schema::hasColumn('t_chat_messages','edited_at')) $table->dropColumn('edited_at');
		});
	}
};

