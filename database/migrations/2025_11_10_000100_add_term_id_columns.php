<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (
            Schema::hasTable("t_consultation_bookings") &&
            !Schema::hasColumn("t_consultation_bookings", "term_id")
        ) {
            Schema::table("t_consultation_bookings", function (Blueprint $table) {
                $table
                    ->foreignId("term_id")
                    ->nullable()
                    ->after("Subject_ID")
                    ->constrained("terms")
                    ->nullOnDelete();
                $table->index("term_id");
            });
        }

        if (
            Schema::hasTable("calendar_overrides") &&
            !Schema::hasColumn("calendar_overrides", "term_id")
        ) {
            Schema::table("calendar_overrides", function (Blueprint $table) {
                $table
                    ->foreignId("term_id")
                    ->nullable()
                    ->after("scope_id")
                    ->constrained("terms")
                    ->nullOnDelete();
                $table->index("term_id");
            });
        }

        if (Schema::hasTable("notifications") && !Schema::hasColumn("notifications", "term_id")) {
            Schema::table("notifications", function (Blueprint $table) {
                $table
                    ->foreignId("term_id")
                    ->nullable()
                    ->after("booking_id")
                    ->constrained("terms")
                    ->nullOnDelete();
                $table->index("term_id");
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable("notifications") && Schema::hasColumn("notifications", "term_id")) {
            Schema::table("notifications", function (Blueprint $table) {
                $table->dropConstrainedForeignId("term_id");
            });
        }

        if (
            Schema::hasTable("calendar_overrides") &&
            Schema::hasColumn("calendar_overrides", "term_id")
        ) {
            Schema::table("calendar_overrides", function (Blueprint $table) {
                $table->dropConstrainedForeignId("term_id");
            });
        }

        if (
            Schema::hasTable("t_consultation_bookings") &&
            Schema::hasColumn("t_consultation_bookings", "term_id")
        ) {
            Schema::table("t_consultation_bookings", function (Blueprint $table) {
                $table->dropConstrainedForeignId("term_id");
            });
        }
    }
};
