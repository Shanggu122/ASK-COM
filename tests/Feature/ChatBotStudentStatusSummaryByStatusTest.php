<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;

class ChatBotStudentStatusSummaryByStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Minimal schemas for tests (only if missing)
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
                $table->string("Name")->nullable();
                $table->string("Schedule")->nullable();
            });
        }
        if (!Schema::hasTable("t_consultation_bookings")) {
            Schema::create("t_consultation_bookings", function (Blueprint $table) {
                $table->unsignedBigInteger("id", true);
                $table->unsignedBigInteger("Prof_ID")->nullable();
                $table->string("Stud_ID", 9)->nullable();
                $table->string("Booking_Date", 32)->nullable(); // e.g., "Wed Oct 29 2025"
                $table->string("Status", 32)->nullable();
                $table->timestamps();
            });
        }

        DB::table("t_consultation_bookings")->truncate();
        DB::table("t_student")->truncate();
        DB::table("professors")->truncate();

        // Seed base data
        DB::table("t_student")->insert([
            "Stud_ID" => "202211002",
            "Name" => "Student A",
            "Email" => "a@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
        ]);
        DB::table("professors")->insert([
            [
                "Prof_ID" => 20200001,
                "Name" => "Prof Abaleta",
                "Schedule" => "MWF 10:00 AM - 12:00 PM",
            ],
            ["Prof_ID" => 20200002, "Name" => "Prof Benito", "Schedule" => "TTh 1:00 PM - 3:00 PM"],
        ]);

        // Log in the student
        $user = User::where("Stud_ID", "202211002")->first();
        if (!$user) {
            // Some test environments use App\Models\User mapping to t_student
            DB::table("users")->insert([
                "Stud_ID" => "202211002",
                "Name" => "Student A",
                "Email" => "a@example.test",
                "Password" => bcrypt("secret"),
            ]);
            $user = User::where("Stud_ID", "202211002")->first();
        }
        $this->be($user);

        // Freeze time (Asia/Manila)
        Carbon::setTestNow(Carbon::create(2025, 10, 29, 10, 0, 0, "Asia/Manila"));
    }

    private function keyFor(Carbon $date): string
    {
        return $date->copy()->timezone("Asia/Manila")->startOfDay()->format("D M d Y");
    }

    public function test_can_get_pending_summary()
    {
        $today = Carbon::now("Asia/Manila");
        $tomorrow = $today->copy()->addDay();

        // Two pending, one approved noise
        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 20200001,
            "Stud_ID" => "202211002",
            "Booking_Date" => $this->keyFor($today),
            "Status" => "pending",
            "created_at" => now(),
            "updated_at" => now(),
        ]);
        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 20200002,
            "Stud_ID" => "202211002",
            "Booking_Date" => $this->keyFor($tomorrow),
            "Status" => "pending",
            "created_at" => now(),
            "updated_at" => now(),
        ]);
        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 20200002,
            "Stud_ID" => "202211002",
            "Booking_Date" => $this->keyFor($tomorrow),
            "Status" => "approved",
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        $res = $this->post("/chat", ["message" => "summary of my pending schedules"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Your pending consultations", $text);
        $this->assertStringContainsString("Prof Abaleta", $text);
        $this->assertStringContainsString("Prof Benito", $text);
        $this->assertStringContainsString("Pending", $text);
    }

    public function test_can_get_approved_summary()
    {
        $today = Carbon::now("Asia/Manila");

        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 20200002,
            "Stud_ID" => "202211002",
            "Booking_Date" => $this->keyFor($today),
            "Status" => "accepted",
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        $res = $this->post("/chat", ["message" => "what are my approved schedules"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("approved/accepted", $text);
        $this->assertStringContainsString("Prof Benito", $text);
        $this->assertStringContainsString("Accepted", $text);
    }

    public function test_can_get_pending_this_week_summary()
    {
        $base = Carbon::now("Asia/Manila")->startOfWeek(Carbon::MONDAY);
        $friday = $base->copy()->addDays(4);
        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 20200001,
            "Stud_ID" => "202211002",
            "Booking_Date" => $this->keyFor($friday),
            "Status" => "pending",
            "created_at" => now(),
            "updated_at" => now(),
        ]);
        // Also add an approved booking to ensure it doesn't appear in pending filter
        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 20200002,
            "Stud_ID" => "202211002",
            "Booking_Date" => $this->keyFor($friday),
            "Status" => "approved",
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        $res = $this->post("/chat", ["message" => "my pending schedules this week"]);
        $res->assertStatus(200);
        $text = $res->json("reply");
        $this->assertStringContainsString("Your pending this week consultations:", $text);
        $this->assertStringContainsString("Pending", $text);
        $this->assertStringNotContainsString("Approved", $text);
    }

    public function test_can_get_approved_next_week_summary()
    {
        $base = Carbon::now("Asia/Manila")->startOfWeek(Carbon::MONDAY)->addWeek();
        $tuesday = $base->copy()->addDays(1);
        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 20200002,
            "Stud_ID" => "202211002",
            "Booking_Date" => $this->keyFor($tuesday),
            "Status" => "accepted",
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        $res = $this->post("/chat", ["message" => "my approved schedules next week"]);
        $res->assertStatus(200);
        $text = $res->json("reply");
        $this->assertStringContainsString(
            "approved/accepted next week consultations",
            strtolower($text),
        );
        $this->assertStringContainsString("Accepted", $text);
    }
}
