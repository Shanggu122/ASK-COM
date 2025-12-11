<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Carbon\Carbon;

class ChatBotProfessorSchedulePhrasingTest extends TestCase
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

        DB::table("t_student")->delete();
        DB::table("professors")->delete();

        DB::table("t_student")->insert([
            "Stud_ID" => "202211009",
            "Name" => "Student F",
            "Email" => "f@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
        ]);

        DB::table("professors")->insert([
            [
                "Prof_ID" => 7001,
                "Name" => "Professor Abaleta",
                "Schedule" => "Mon 1:00 PM - 2:00 PM; Tue 1:54 PM - 2:54 PM",
            ],
        ]);

        $user = User::where("Stud_ID", "202211009")->first();
        if (!$user) {
            DB::table("users")->insert([
                "Stud_ID" => "202211009",
                "Name" => "Student F",
                "Email" => "f@example.test",
                "Password" => bcrypt("secret"),
            ]);
            $user = User::where("Stud_ID", "202211009")->first();
        }
        $this->be($user);

        Carbon::setTestNow(Carbon::create(2025, 10, 29, 10, 0, 0, "Asia/Manila")); // Wed
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_free_phrasing_returns_professor_schedule_when_no_date()
    {
        $res = $this->post("/chat", ["message" => "when professor Abaleta free"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Schedule of Professor Abaleta", $text);
        $this->assertStringContainsString("Mon 1:00 PM - 2:00 PM", $text);
    }

    public function test_consultations_are_phrasing_maps_to_schedule()
    {
        $res = $this->post("/chat", ["message" => "what professor Abaleta consultations are"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Schedule of Professor Abaleta", $text);
    }
}
