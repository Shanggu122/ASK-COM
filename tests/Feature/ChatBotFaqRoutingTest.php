<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Carbon\Carbon;

class ChatBotFaqRoutingTest extends TestCase
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
                $table->string("Booking_Date", 32)->nullable();
                $table->string("Status", 32)->nullable();
                $table->timestamps();
            });
        }

        DB::table("t_student")->truncate();
        DB::table("professors")->truncate();
        DB::table("t_consultation_bookings")->truncate();

        // Seed one student and authenticate
        DB::table("t_student")->insert([
            "Stud_ID" => "202211002",
            "Name" => "Student A",
            "Email" => "a@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
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

    public function test_faq_contact_professor_routes_to_guidance_not_schedule_fallback()
    {
        $res = $this->post("/chat", ["message" => "How do I contact my professor after booking?"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        // Should provide guidance, not scheduling fallback
        $this->assertStringContainsString("message your professor", strtolower($text));
        $this->assertStringNotContainsString(
            "you don't have any consultations scheduled",
            strtolower($text),
        );
        $this->assertStringNotContainsString("your latest consultation", strtolower($text));
    }
}
