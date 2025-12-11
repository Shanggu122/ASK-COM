<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable("t_consultation_bookings")) {
            return;
        }
        Schema::create("t_consultation_bookings", function (Blueprint $table) {
            $table->bigIncrements("Booking_ID");
            $table->unsignedBigInteger("Prof_ID");
            $table->unsignedBigInteger("Stud_ID")->nullable();
            $table->date("Booking_Date");
            $table->string("Mode", 16); // 'online' | 'onsite'
            $table->string("Status", 32)->default("pending");
            $table->unsignedBigInteger("Subject_ID")->nullable();
            $table->timestamp("Created_At")->nullable();
            $table->timestamp("Updated_At")->nullable();
            $table->text("reschedule_reason")->nullable();
            // Optional future columns safety for app code referencing them
            if (!Schema::hasColumn("t_consultation_bookings", "Booking_Time")) {
                $table->time("Booking_Time")->nullable();
            }
            if (!Schema::hasColumn("t_consultation_bookings", "one_hour_reminder_sent_at")) {
                $table->timestamp("one_hour_reminder_sent_at")->nullable();
            }

            $table->index(["Prof_ID", "Booking_Date"]);
            $table->index(["Stud_ID"]);
            $table->index(["Status"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("t_consultation_bookings");
    }
};
