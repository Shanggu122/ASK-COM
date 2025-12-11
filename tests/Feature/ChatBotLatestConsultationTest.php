<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Carbon\Carbon;

class ChatBotLatestConsultationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create minimal tables if missing
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
                $table->unsignedInteger("Dept_ID")->nullable();
            });
        }
        if (!Schema::hasTable("t_consultation_bookings")) {
            Schema::create("t_consultation_bookings", function (Blueprint $table) {
                $table->bigIncrements("Booking_ID");
                $table->string("Stud_ID", 9)->index();
                $table->unsignedBigInteger("Prof_ID");
                $table->string("Booking_Date");
                $table->string("Status");
                $table->string("Mode")->nullable();
                $table->timestamps();
            });
        }

        DB::table("t_student")->delete();
        DB::table("professors")->delete();
        DB::table("t_consultation_bookings")->delete();

        // Fixed today for deterministic behavior
        Carbon::setTestNow(Carbon::create(2025, 10, 29, 9, 0, 0, "Asia/Manila"));

        DB::table("t_student")->insert([
            "Stud_ID" => "202211007",
            "Name" => "Student F",
            "Email" => "f@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
        ]);

        DB::table("professors")->insert([
            [
                "Prof_ID" => 6001,
                "Name" => "Prof Latest",
                "Dept_ID" => 1,
                "Schedule" => "Wed: 3:40 PM - 4:40 PM",
            ],
        ]);

        $user = User::where("Stud_ID", "202211007")->first();
        if (!$user) {
            DB::table("users")->insert([
                "Stud_ID" => "202211007",
                "Name" => "Student F",
                "Email" => "f@example.test",
                "Password" => bcrypt("secret"),
            ]);
            $user = User::where("Stud_ID", "202211007")->first();
        }
        $this->be($user);

        // Seed two bookings on the same day: one Pending, one Approved (should prefer Approved)
        $dateKey = Carbon::now("Asia/Manila")->startOfDay()->format("D M d Y");
        DB::table("t_consultation_bookings")->insert([
            [
                "Stud_ID" => "202211007",
                "Prof_ID" => 6001,
                "Booking_Date" => $dateKey,
                "Status" => "pending",
                "Mode" => "onsite",
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "Stud_ID" => "202211007",
                "Prof_ID" => 6001,
                "Booking_Date" => $dateKey,
                "Status" => "approved",
                "Mode" => "onsite",
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ]);
    }

    public function test_what_is_my_latest_consultation_prefers_approved()
    {
        $res = $this->post("/chat", ["message" => "what is my latest consultation"]);
        $res->assertStatus(200);
        $text = $res->json("reply");
        $this->assertStringContainsString("Your latest consultation is with", $text);
        $this->assertStringContainsString("Approved", $text);
        $this->assertStringNotContainsString("Pending", $text);
    }

    public function test_when_is_my_latest_consultation_same_answer()
    {
        $res1 = $this->post("/chat", ["message" => "what is my latest consultation"]);
        $res2 = $this->post("/chat", ["message" => "when is my latest consultation"]);
        $res1->assertStatus(200);
        $res2->assertStatus(200);
        $text1 = $res1->json("reply");
        $text2 = $res2->json("reply");
        $this->assertEquals(
            $text1,
            $text2,
            "WHAT and WHEN should return the same latest consultation answer",
        );
    }
}
