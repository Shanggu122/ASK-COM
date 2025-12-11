<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Carbon\Carbon;

class ChatBotScheduleSynonymsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        DB::table("t_student")->insert([
            "Stud_ID" => "202211002",
            "Name" => "Student A",
            "Email" => "a@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
        ]);

        DB::table("professors")->insert([
            [
                "Prof_ID" => 12345,
                "Name" => "Ma'am Anette Santos",
                "Schedule" => "MWF 9:00 AM - 11:00 AM; TH 1:00 PM - 3:00 PM",
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

    public function test_consultation_hours_is_treated_as_schedule_intent()
    {
        // Simulate sanitized spacing like in the screenshot: "Ma am Anette s" (apostrophes lost)
        $res = $this->post("/chat", [
            "message" => "What are Ma am Anette s consultation hours?",
        ]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Schedule of", $text);
        $this->assertStringContainsString("Anette", $text);
        $this->assertStringContainsString("9:00 AM", $text);
    }
}
