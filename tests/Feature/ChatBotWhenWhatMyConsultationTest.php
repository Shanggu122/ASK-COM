<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Carbon\Carbon;

class ChatBotWhenWhatMyConsultationTest extends TestCase
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

        Carbon::setTestNow(Carbon::create(2025, 10, 29, 9, 0, 0, "Asia/Manila"));

        DB::table("t_student")->insert([
            "Stud_ID" => "202211008",
            "Name" => "Student G",
            "Email" => "g@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
        ]);

        DB::table("professors")->insert([
            ["Prof_ID" => 7001, "Name" => "Prof Alpha", "Schedule" => "Wed: 3:40 PM - 4:40 PM"],
        ]);

        $user = User::where("Stud_ID", "202211008")->first();
        if (!$user) {
            DB::table("users")->insert([
                "Stud_ID" => "202211008",
                "Name" => "Student G",
                "Email" => "g@example.test",
                "Password" => bcrypt("secret"),
            ]);
            $user = User::where("Stud_ID", "202211008")->first();
        }
        $this->be($user);

        $dateKey = Carbon::now("Asia/Manila")->startOfDay()->format("D M d Y");
        // Only pending exists (no approved)
        DB::table("t_consultation_bookings")->insert([
            "Stud_ID" => "202211008",
            "Prof_ID" => 7001,
            "Booking_Date" => $dateKey,
            "Status" => "pending",
            "Mode" => "onsite",
            "created_at" => now(),
            "updated_at" => now(),
        ]);
    }

    public function test_when_is_my_consultation_does_not_show_pending_when_no_approved()
    {
        $res = $this->post("/chat", ["message" => "when is my consultation"]);
        $res->assertStatus(200);
        $text = $res->json("reply");
        $this->assertStringContainsString(
            "don't have any approved/rescheduled consultations",
            strtolower($text),
        );
        $this->assertStringNotContainsString("Pending", $text);
    }

    public function test_what_is_my_consultation_does_not_show_pending_when_no_approved()
    {
        $res = $this->post("/chat", ["message" => "what is my consultation"]);
        $res->assertStatus(200);
        $text = $res->json("reply");
        $this->assertStringContainsString(
            "don't have any approved/rescheduled consultations",
            strtolower($text),
        );
        $this->assertStringNotContainsString("Pending", $text);
    }
}
