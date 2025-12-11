<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use App\Mail\UpcomingConsultationReminder;

class UpcomingConsultationReminderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Minimal professors table
        if (!Schema::hasTable("professors")) {
            Schema::create("professors", function (Blueprint $table) {
                $table->integer("Prof_ID")->primary();
                $table->string("Name")->nullable();
                $table->string("Email")->nullable();
                $table->text("Schedule")->nullable();
            });
        } else {
            // Ensure required columns exist
            Schema::table("professors", function (Blueprint $table) {
                if (!Schema::hasColumn("professors", "Email")) {
                    $table->string("Email")->nullable();
                }
                if (!Schema::hasColumn("professors", "Schedule")) {
                    $table->text("Schedule")->nullable();
                }
            });
            DB::table("professors")->truncate();
        }

        // Minimal students table
        if (!Schema::hasTable("t_student")) {
            Schema::create("t_student", function (Blueprint $table) {
                $table->integer("Stud_ID")->primary();
                $table->string("Name")->nullable();
                $table->string("Email")->nullable();
            });
        } else {
            DB::table("t_student")->truncate();
        }

        // Minimal subject table for join (optional values)
        if (!Schema::hasTable("t_subject")) {
            Schema::create("t_subject", function (Blueprint $table) {
                $table->integer("Subject_ID")->primary();
                $table->string("Subject_Name")->nullable();
            });
        } else {
            DB::table("t_subject")->truncate();
        }

        // Minimal consultation types table for join (optional values)
        if (!Schema::hasTable("t_consultation_types")) {
            Schema::create("t_consultation_types", function (Blueprint $table) {
                $table->integer("Consult_type_ID")->primary();
                $table->string("Consult_Type")->nullable();
            });
        } else {
            DB::table("t_consultation_types")->truncate();
        }

        // Notifications table used by Notification::refreshTodayReminder
        if (!Schema::hasTable("notifications")) {
            Schema::create("notifications", function (Blueprint $table) {
                $table->increments("id");
                $table->integer("user_id");
                $table->integer("booking_id")->nullable();
                $table->string("type");
                $table->string("title");
                $table->text("message")->nullable();
                $table->boolean("is_read")->default(false);
                $table->timestamps();
            });
        } else {
            DB::table("notifications")->truncate();
        }

        // Bookings table with needed columns
        if (!Schema::hasTable("t_consultation_bookings")) {
            Schema::create("t_consultation_bookings", function (Blueprint $table) {
                $table->increments("Booking_ID");
                $table->integer("Prof_ID")->nullable();
                $table->integer("Stud_ID")->nullable();
                $table->integer("Subject_ID")->nullable();
                $table->integer("Consult_type_ID")->nullable();
                $table->string("Custom_Type")->nullable();
                $table->string("Booking_Date")->nullable(); // e.g., Mon Sep 22 2025
                $table->string("Mode")->nullable();
                $table->string("Status")->nullable();
                $table->timestamp("one_hour_reminder_sent_at")->nullable();
                $table->timestamps();
            });
        } else {
            // Ensure the new timestamp column exists
            Schema::table("t_consultation_bookings", function (Blueprint $table) {
                if (!Schema::hasColumn("t_consultation_bookings", "one_hour_reminder_sent_at")) {
                    $table->timestamp("one_hour_reminder_sent_at")->nullable();
                }
                if (!Schema::hasColumn("t_consultation_bookings", "Subject_ID")) {
                    $table->integer("Subject_ID")->nullable();
                }
            });
            DB::table("t_consultation_bookings")->truncate();
        }
    }

    public function test_sends_email_when_within_60_to_55_min_window_and_gmail()
    {
        // Set time to Monday 10:02 Asia/Manila, schedule 11:00 AM -> diff = 58
        $now = Carbon::create(2025, 9, 22, 10, 2, 0, "Asia/Manila"); // Monday
        Carbon::setTestNow($now);

        // Seed minimal data
        DB::table("t_student")->insert([
            "Stud_ID" => 1,
            "Name" => "Student A",
            "Email" => "student@example.com",
        ]);

        DB::table("professors")->insert([
            "Prof_ID" => 10,
            "Name" => "Prof P",
            "Email" => "prof@gmail.com",
            "Schedule" => "Monday: 11:00 AM - 12:00 PM",
        ]);

        DB::table("t_subject")->insert([
            "Subject_ID" => 200,
            "Subject_Name" => "Algorithms",
        ]);
        DB::table("t_consultation_types")->insert([
            "Consult_type_ID" => 1,
            "Consult_Type" => "consultation",
        ]);

        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 10,
            "Stud_ID" => 1,
            "Subject_ID" => 200,
            "Consult_type_ID" => 1,
            "Custom_Type" => null,
            "Booking_Date" => $now->format("D M d Y"),
            "Mode" => "onsite",
            "Status" => "approved",
            "one_hour_reminder_sent_at" => null,
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        Mail::fake();

        $this->artisan("consultations:remind-upcoming")->assertExitCode(0);

        Mail::assertSent(UpcomingConsultationReminder::class, function ($mail) {
            return $mail->hasTo("prof@gmail.com") && $mail->bookingDate !== null;
        });

        $updated = DB::table("t_consultation_bookings")->first();
        $this->assertNotNull(
            $updated->one_hour_reminder_sent_at,
            "Expected one_hour_reminder_sent_at to be set",
        );
    }

    public function test_does_not_send_when_professor_email_is_not_gmail()
    {
        // Monday 10:02, still within window but non-gmail -> skip
        $now = Carbon::create(2025, 9, 22, 10, 2, 0, "Asia/Manila");
        Carbon::setTestNow($now);

        DB::table("t_student")->insert([
            "Stud_ID" => 2,
            "Name" => "Student B",
            "Email" => "studentb@example.com",
        ]);

        DB::table("professors")->insert([
            "Prof_ID" => 11,
            "Name" => "Prof Q",
            "Email" => "prof@school.edu",
            "Schedule" => "Monday: 11:00 AM - 12:00 PM",
        ]);

        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 11,
            "Stud_ID" => 2,
            "Subject_ID" => null,
            "Consult_type_ID" => null,
            "Custom_Type" => null,
            "Booking_Date" => $now->format("D M d Y"),
            "Mode" => "onsite",
            "Status" => "approved",
            "one_hour_reminder_sent_at" => null,
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        Mail::fake();

        $this->artisan("consultations:remind-upcoming")->assertExitCode(0);

        Mail::assertNothingSent();

        $row = DB::table("t_consultation_bookings")->first();
        $this->assertNull($row->one_hour_reminder_sent_at, "Non-gmail should not be updated");
    }

    public function test_does_not_send_outside_time_window()
    {
        // Monday 10:06, start 11:00 -> diff = 54 (outside 60..55 window) -> skip
        $now = Carbon::create(2025, 9, 22, 10, 6, 0, "Asia/Manila");
        Carbon::setTestNow($now);

        DB::table("t_student")->insert([
            "Stud_ID" => 3,
            "Name" => "Student C",
            "Email" => "studentc@example.com",
        ]);

        DB::table("professors")->insert([
            "Prof_ID" => 12,
            "Name" => "Prof R",
            "Email" => "prof@gmail.com",
            "Schedule" => "Monday: 11:00 AM - 12:00 PM",
        ]);

        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 12,
            "Stud_ID" => 3,
            "Subject_ID" => null,
            "Consult_type_ID" => null,
            "Custom_Type" => null,
            "Booking_Date" => $now->format("D M d Y"),
            "Mode" => "onsite",
            "Status" => "approved",
            "one_hour_reminder_sent_at" => null,
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        Mail::fake();

        $this->artisan("consultations:remind-upcoming")->assertExitCode(0);

        Mail::assertNothingSent();

        $row = DB::table("t_consultation_bookings")->first();
        $this->assertNull($row->one_hour_reminder_sent_at, "Outside window should not be updated");
    }
}
