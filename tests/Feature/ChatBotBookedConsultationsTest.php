<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Carbon\Carbon;

class ChatBotBookedConsultationsTest extends TestCase
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
            "Stud_ID" => "202211003",
            "Name" => "Student B",
            "Email" => "b@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
        ]);

        DB::table("professors")->insert([
            [
                "Prof_ID" => 2001,
                "Name" => "Professor Gamma",
                "Schedule" => "Wed 9:00 AM - 11:00 AM",
            ],
            [
                "Prof_ID" => 2002,
                "Name" => "Professor Delta",
                "Schedule" => "Thu 1:00 PM - 3:00 PM",
            ],
        ]);

        $user = User::where("Stud_ID", "202211003")->first();
        if (!$user) {
            DB::table("users")->insert([
                "Stud_ID" => "202211003",
                "Name" => "Student B",
                "Email" => "b@example.test",
                "Password" => bcrypt("secret"),
            ]);
            $user = User::where("Stud_ID", "202211003")->first();
        }
        $this->be($user);

        Carbon::setTestNow(Carbon::create(2025, 10, 29, 10, 0, 0, "Asia/Manila"));
        $todayKey = Carbon::now("Asia/Manila")->startOfDay()->format("D M d Y");

        DB::table("t_consultation_bookings")->insert([
            [
                "Prof_ID" => 2001,
                "Stud_ID" => "202211003",
                "Booking_Date" => $todayKey,
                "Status" => "approved",
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "Prof_ID" => 2002,
                "Stud_ID" => "202211003",
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

    public function test_show_me_my_booked_consultations_routes_to_accepted_summary()
    {
        $res = $this->post("/chat", ["message" => "Show me my booked consultations"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        // Should summarize accepted/approved/rescheduled consultations
        $this->assertStringContainsString("consultations", $text);
        $this->assertStringContainsString("Professor Gamma", $text);
        $this->assertStringContainsString("Professor Delta", $text);
        $this->assertMatchesRegularExpression("/(Approved|Accepted|Rescheduled)/i", $text);
    }
}
