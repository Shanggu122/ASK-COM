<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Carbon\Carbon;

class ChatBotAvailabilityRespectsScheduleTest extends TestCase
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
            "Stud_ID" => "202211004",
            "Name" => "Student C",
            "Email" => "c@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
        ]);

        DB::table("professors")->insert([
            [
                "Prof_ID" => 3001,
                "Name" => "Professor Abaleta",
                // Monday and Tuesday only, no Wednesday
                "Schedule" => "Mon 1:00 PM - 2:00 PM; Tue 1:54 PM - 2:54 PM",
            ],
        ]);

        $user = User::where("Stud_ID", "202211004")->first();
        if (!$user) {
            DB::table("users")->insert([
                "Stud_ID" => "202211004",
                "Name" => "Student C",
                "Email" => "c@example.test",
                "Password" => bcrypt("secret"),
            ]);
            $user = User::where("Stud_ID", "202211004")->first();
        }
        $this->be($user);

        Carbon::setTestNow(Carbon::create(2025, 10, 29, 10, 0, 0, "Asia/Manila")); // Wed
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_professor_availability_returns_no_schedule_when_not_scheduled_that_day()
    {
        $res = $this->post("/chat", ["message" => "Is Professor Abaleta available today?"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("no consultation schedule", $text);
        $this->assertStringNotContainsString("slots available", $text);
    }
}
