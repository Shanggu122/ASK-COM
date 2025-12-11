<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Carbon\Carbon;

class ChatBotGlobalScheduleAndAvailabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Minimal schemas for tests
        if (!Schema::hasTable("t_student")) {
            Schema::create("t_student", function (Blueprint $table) {
                $table->string("Stud_ID", 9)->primary();
                $table->string("Name")->nullable();
                $table->string("Email")->nullable();
                $table->string("Password")->nullable();
                $table->boolean("is_active")->default(true);
            });
        }
        if (!Schema::hasTable("professors")) {
            Schema::create("professors", function (Blueprint $table) {
                $table->unsignedBigInteger("Prof_ID")->primary();
                $table->string("Name");
                $table->text("Schedule")->nullable();
                // Department column for filtering (1=IT&IS, 2=CS)
                $table->unsignedInteger("Dept_ID")->nullable();
            });
        } elseif (!Schema::hasColumn("professors", "Dept_ID")) {
            Schema::table("professors", function (Blueprint $table) {
                $table->unsignedInteger("Dept_ID")->nullable();
            });
        }
        if (!Schema::hasTable("t_consultation_bookings")) {
            Schema::create("t_consultation_bookings", function (Blueprint $table) {
                $table->bigIncrements("Booking_ID");
                $table->unsignedBigInteger("Prof_ID")->nullable();
                $table->string("Stud_ID", 9)->nullable();
                $table->string("Booking_Date", 32)->nullable();
                $table->string("Status", 32)->nullable();
                $table->timestamps();
            });
        }

        DB::table("t_student")->truncate();
        DB::table("professors")->truncate();
        DB::table("t_consultation_bookings")->truncate();

        DB::table("t_student")->insert([
            "Stud_ID" => "202211002",
            "Name" => "Student A",
            "Email" => "a@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
        ]);

        DB::table("professors")->insert([
            [
                "Prof_ID" => 1001,
                "Name" => "Professor Alpha",
                // Test date anchor: Wed, Oct 29, 2025
                "Schedule" => "Wed 9:00 AM - 11:00 AM",
                "Dept_ID" => 1,
            ],
            [
                "Prof_ID" => 1002,
                "Name" => "Professor Beta",
                "Schedule" => "Tue 1:00 PM - 3:00 PM",
                "Dept_ID" => 2,
            ],
        ]);

        $user = User::where("Stud_ID", "202211002")->first();
        if (!$user) {
            DB::table("users")->insert([
                "Stud_ID" => "202211002",
                "Name" => "Student A",
                "Email" => "a@example.test",
                "Password" => bcrypt("secret"),
            ]);
            $user = User::where("Stud_ID", "202211002")->first();
        }
        $this->be($user);

        Carbon::setTestNow(Carbon::create(2025, 10, 29, 10, 0, 0, "Asia/Manila"));

        // Seed some bookings for today (Wed, Oct 29, 2025 -> use key format 'D M d Y')
        $todayKey = Carbon::now("Asia/Manila")->startOfDay()->format("D M d Y");
        DB::table("t_consultation_bookings")->insert([
            [
                "Prof_ID" => 1001,
                "Stud_ID" => "202211002",
                "Booking_Date" => $todayKey,
                "Status" => "approved",
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "Prof_ID" => 1002,
                "Stud_ID" => "202211002",
                "Booking_Date" => $todayKey,
                "Status" => "rescheduled",
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_full_faculty_schedule_lists_all_professors()
    {
        $res = $this->post("/chat", [
            "message" => "Show me the full faculty consultation schedule",
        ]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Full faculty consultation schedules:", $text);
        $this->assertStringContainsString("Professor Alpha", $text);
        $this->assertStringContainsString("Professor Beta", $text);
    }

    public function test_all_professor_availability_lists_only_with_schedule_today()
    {
        $res = $this->post("/chat", ["message" => "Display all professor availability"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("All professors availability", $text);
        // Only Alpha has a Wednesday schedule; Beta should be filtered out
        $this->assertStringContainsString("Professor Alpha", $text);
        $this->assertStringNotContainsString("Professor Beta", $text);
        $this->assertMatchesRegularExpression("/\bSlots: \d\/5 available\b/", $text);
    }

    public function test_full_faculty_schedule_it_is_only_filters_department()
    {
        $res = $this->post("/chat", [
            "message" => "Show me the full faculty consultation schedule for ITIS only",
        ]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Full faculty consultation schedules:", $text);
        $this->assertStringContainsString("IT&IS Department", $text);
        $this->assertStringNotContainsString("Computer Science Department", $text);
        $this->assertStringContainsString("Professor Alpha", $text);
        $this->assertStringNotContainsString("Professor Beta", $text);
    }

    public function test_full_faculty_schedule_comsci_only_filters_department()
    {
        $res = $this->post("/chat", [
            "message" => "Show me the full faculty consultation schedule for ComSci only",
        ]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Full faculty consultation schedules:", $text);
        $this->assertStringContainsString("Computer Science Department", $text);
        $this->assertStringNotContainsString("IT&IS Department", $text);
        $this->assertStringContainsString("Professor Beta", $text);
        $this->assertStringNotContainsString("Professor Alpha", $text);
    }

    public function test_faculty_schedule_detects_plural_professors_without_all_keyword()
    {
        $res = $this->post("/chat", ["message" => "Consultation schedule of professors in IT&IS"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Full faculty consultation schedules:", $text);
        $this->assertStringContainsString("IT&IS Department", $text);
        $this->assertStringContainsString("Professor Alpha", $text);
        $this->assertStringNotContainsString("Professor Beta", $text);
    }

    public function test_faculty_availability_detects_faculty_word_without_all_keyword()
    {
        $res = $this->post("/chat", ["message" => "Faculty availability for today"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("All professors availability", $text);
    }
}
