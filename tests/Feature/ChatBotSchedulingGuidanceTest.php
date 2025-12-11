<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Carbon\Carbon;

class ChatBotSchedulingGuidanceTest extends TestCase
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
                $table->string("Name");
                $table->text("Schedule")->nullable();
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

        // Seed data and login
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
                "Name" => "Professor Abaleta",
                "Schedule" => "MWF 10:00 AM - 12:00 PM",
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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_can_i_schedule_question_returns_guidance_not_status_fallback()
    {
        $res = $this->post("/chat", [
            "message" => "can I schedule to Professor Abaleta even though he still not available",
        ]);
        $res->assertStatus(200);
        $text = strtolower($res->json("reply"));

        $this->assertStringContainsString("you can only book on dates", $text);
        $this->assertStringNotContainsString("you don't have any consultations scheduled", $text);
    }

    public function test_unavailable_phrase_returns_guidance_without_can_i()
    {
        $res = $this->post("/chat", ["message" => "Professor Abaleta is unavailable right now"]);
        $res->assertStatus(200);
        $text = strtolower($res->json("reply"));
        $this->assertStringContainsString("you can only book on dates", $text);
    }

    public function test_tagalog_walang_schedule_returns_guidance()
    {
        $res = $this->post("/chat", ["message" => "walang schedule si sir Abaleta"]);
        $res->assertStatus(200);
        $text = strtolower($res->json("reply"));
        $this->assertStringContainsString("you can only book on dates", $text);
    }
}
