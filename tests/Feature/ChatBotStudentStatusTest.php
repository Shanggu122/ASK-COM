<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Carbon\Carbon;

class ChatBotStudentStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Freeze time for deterministic tests
        Carbon::setTestNow(Carbon::create(2025, 10, 29, 9, 0, 0, "Asia/Manila"));

        // Ensure core tables exist
        if (!Schema::hasTable("t_student")) {
            Schema::create("t_student", function (Blueprint $table) {
                $table->string("Stud_ID", 9)->primary();
                $table->string("Name", 100)->nullable();
                $table->unsignedInteger("Dept_ID")->nullable();
                $table->string("Email", 150)->nullable();
                $table->string("Password", 255)->nullable();
                $table->string("profile_picture", 255)->nullable();
                $table->boolean("is_active")->default(true);
                $table->rememberToken();
                // No timestamps per app\Models\User
            });
        }

        if (!Schema::hasTable("professors")) {
            Schema::create("professors", function (Blueprint $table) {
                $table->unsignedBigInteger("Prof_ID")->primary();
                $table->string("Name", 150);
                $table->text("Schedule")->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable("t_consultation_bookings")) {
            Schema::create("t_consultation_bookings", function (Blueprint $table) {
                $table->bigIncrements("Booking_ID");
                $table->unsignedBigInteger("Prof_ID")->nullable();
                $table->string("Stud_ID", 9)->nullable();
                $table->string("Booking_Date", 32)->nullable(); // e.g., "Wed Oct 29 2025"
                $table->string("Status", 32)->default("pending");
                $table->string("Custom_Type", 64)->nullable();
                $table->unsignedInteger("Consult_type_ID")->nullable();
                $table->timestamps();
            });
        }

        // Clean tables
        DB::table("t_consultation_bookings")->truncate();
        DB::table("professors")->truncate();
        DB::table("t_student")->truncate();

        // Seed a student and a couple of professors
        DB::table("t_student")->insert([
            "Stud_ID" => "202211002",
            "Name" => "Test Student",
            "Dept_ID" => 1,
            "Email" => "student@example.com",
            "Password" => Hash::make("secret123"),
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

        // Authenticate as the student
        $user = User::where("Stud_ID", "202211002")->first();
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function keyFor(Carbon $date): string
    {
        return $date->copy()->timezone("Asia/Manila")->startOfDay()->format("D M d Y");
    }

    public function test_weekly_summary_happy_path()
    {
        $base = Carbon::now("Asia/Manila")->startOfWeek(Carbon::MONDAY); // 2025-10-27
        $friday = $base->copy()->addDays(4); // Fri 2025-10-31
        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 20200001,
            "Stud_ID" => "202211002",
            "Booking_Date" => $this->keyFor($friday),
            "Status" => "approved",
            "created_at" => Carbon::now("Asia/Manila"),
            "updated_at" => Carbon::now("Asia/Manila"),
        ]);

        $res = $this->post("/chat", ["message" => "Do I have a schedule this week?"]);
        $res->assertStatus(200);
        $text = $res->json("reply");
        $this->assertStringContainsString("Your approved this week consultations:", $text);
        // Should mention Fri (Oct 31) with at least one entry
        $this->assertStringContainsString("Fri (Oct 31)", $text);
    }

    public function test_weekly_summary_no_bookings()
    {
        // Ensure no bookings in the week
        DB::table("t_consultation_bookings")->truncate();

        $res = $this->post("/chat", ["message" => "Do I have a schedule this week?"]);
        $res->assertStatus(200);
        $text = strtolower($res->json("reply"));
        $this->assertStringContainsString("no approved consultations this week", $text);
    }

    public function test_per_day_bookings_default_includes_pending()
    {
        $today = Carbon::now("Asia/Manila")->startOfDay();
        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 20200002,
            "Stud_ID" => "202211002",
            "Booking_Date" => $this->keyFor($today),
            "Status" => "pending",
            "created_at" => Carbon::now("Asia/Manila"),
            "updated_at" => Carbon::now("Asia/Manila"),
        ]);

        $res = $this->post("/chat", ["message" => "Do I have any consultation today?"]);
        $res->assertStatus(200);
        $text = $res->json("reply");
        $this->assertStringContainsString("You have 1 consultation(s) on", $text);
        $this->assertStringContainsString("Pending", $text);
    }

    public function test_per_day_bookings_accepted_only_filters_pending()
    {
        $today = Carbon::now("Asia/Manila")->startOfDay();
        // Pending and Approved on the same day
        DB::table("t_consultation_bookings")->insert([
            [
                "Prof_ID" => 20200002,
                "Stud_ID" => "202211002",
                "Booking_Date" => $this->keyFor($today),
                "Status" => "pending",
                "created_at" => Carbon::now("Asia/Manila"),
                "updated_at" => Carbon::now("Asia/Manila"),
            ],
            [
                "Prof_ID" => 20200001,
                "Stud_ID" => "202211002",
                "Booking_Date" => $this->keyFor($today),
                "Status" => "approved",
                "created_at" => Carbon::now("Asia/Manila"),
                "updated_at" => Carbon::now("Asia/Manila"),
            ],
        ]);

        $res = $this->post("/chat", ["message" => "Do I have any accepted consultation today?"]);
        $res->assertStatus(200);
        $text = $res->json("reply");
        $this->assertStringContainsString("accepted consultation(s) on", strtolower($text));
        $this->assertStringNotContainsString("Pending", $text);
        $this->assertStringContainsString("Approved", $text);
    }

    public function test_acceptance_status_with_professor_includes_tanggalog_phrase()
    {
        $today = Carbon::now("Asia/Manila")->startOfDay();
        // Insert latest booking as approved with Prof Abaleta
        DB::table("t_consultation_bookings")->insert([
            "Prof_ID" => 20200001,
            "Stud_ID" => "202211002",
            "Booking_Date" => $this->keyFor($today),
            "Status" => "approved",
            "created_at" => Carbon::now("Asia/Manila"),
            "updated_at" => Carbon::now("Asia/Manila"),
        ]);

        // Tagalog phrasing: "status ko kay sir Abaleta"
        $res = $this->post("/chat", ["message" => "Ano ang status ko kay sir Abaleta?"]);
        $res->assertStatus(200);
        $text = $res->json("reply");
        $this->assertStringContainsString("Your consultation with", $text);
        $this->assertStringContainsString("Abaleta", $text);
        $this->assertStringContainsString("Approved", $text);
    }

    public function test_tagalog_question_prompts_english_fallback()
    {
        $res = $this->post("/chat", ["message" => "kailan schedule ko?"]);
        $res->assertStatus(200);
        $text = strtolower($res->json("reply"));

        $this->assertStringContainsString("english", $text);
        $this->assertStringContainsString("consultation", $text);
    }
}
