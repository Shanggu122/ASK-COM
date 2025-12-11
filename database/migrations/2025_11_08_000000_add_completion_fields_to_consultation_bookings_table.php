<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable("t_consultation_bookings")) {
            return;
        }

        Schema::table("t_consultation_bookings", function (Blueprint $table) {
            if (!Schema::hasColumn("t_consultation_bookings", "completion_reason")) {
                $table->text("completion_reason")->nullable()->after("reschedule_reason");
            }
            if (!Schema::hasColumn("t_consultation_bookings", "completion_requested_at")) {
                $table
                    ->timestamp("completion_requested_at")
                    ->nullable()
                    ->after("completion_reason");
            }
            if (!Schema::hasColumn("t_consultation_bookings", "completion_reviewed_at")) {
                $table
                    ->timestamp("completion_reviewed_at")
                    ->nullable()
                    ->after("completion_requested_at");
            }
            if (!Schema::hasColumn("t_consultation_bookings", "completion_student_response")) {
                $table
                    ->string("completion_student_response", 32)
                    ->nullable()
                    ->after("completion_reviewed_at");
            }
            if (!Schema::hasColumn("t_consultation_bookings", "completion_student_comment")) {
                $table
                    ->text("completion_student_comment")
                    ->nullable()
                    ->after("completion_student_response");
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable("t_consultation_bookings")) {
            return;
        }

        Schema::table("t_consultation_bookings", function (Blueprint $table) {
            if (Schema::hasColumn("t_consultation_bookings", "completion_student_comment")) {
                $table->dropColumn("completion_student_comment");
            }
            if (Schema::hasColumn("t_consultation_bookings", "completion_student_response")) {
                $table->dropColumn("completion_student_response");
            }
            if (Schema::hasColumn("t_consultation_bookings", "completion_reviewed_at")) {
                $table->dropColumn("completion_reviewed_at");
            }
            if (Schema::hasColumn("t_consultation_bookings", "completion_requested_at")) {
                $table->dropColumn("completion_requested_at");
            }
            if (Schema::hasColumn("t_consultation_bookings", "completion_reason")) {
                $table->dropColumn("completion_reason");
            }
        });
    }
};
