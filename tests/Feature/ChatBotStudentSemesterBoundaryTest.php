<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;
use Carbon\Carbon;

class ChatBotStudentSemesterBoundaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 10, 29, 10, 0, 0, "Asia/Manila"));

        if (!Schema::hasTable("t_student")) {
            Schema::create("t_student", function (Blueprint $table) {
                $table->string("Stud_ID", 9)->primary();
                $table->string("Name")->nullable();
                $table->string("Email")->nullable();
                $table->string("Password")->nullable();
                $table->boolean("is_active")->default(true);
            });
        }

        if (!Schema::hasTable("academic_years")) {
            Schema::create("academic_years", function (Blueprint $table) {
                $table->bigIncrements("id");
                $table->string("label")->unique();
                $table->date("start_at");
                $table->date("end_at");
                $table->string("status")->default("draft");
                $table->timestamp("activated_at")->nullable();
                $table->timestamp("closed_at")->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable("terms")) {
            Schema::create("terms", function (Blueprint $table) {
                $table->bigIncrements("id");
                $table->unsignedBigInteger("academic_year_id");
                $table->unsignedTinyInteger("sequence");
                $table->string("name");
                $table->date("start_at");
                $table->date("end_at");
                $table->string("status")->default("draft");
                $table->timestamp("activated_at")->nullable();
                $table->timestamps();
            });
        }

        DB::table("terms")->delete();
        DB::table("academic_years")->delete();
        DB::table("t_student")->delete();

        $now = Carbon::now("Asia/Manila");

        $yearId = DB::table("academic_years")->insertGetId([
            "label" => "AY 2025-2026",
            "start_at" => "2025-08-01",
            "end_at" => "2026-06-30",
            "status" => "active",
            "activated_at" => $now,
            "created_at" => $now,
            "updated_at" => $now,
        ]);

        DB::table("terms")->insert([
            "academic_year_id" => $yearId,
            "sequence" => 1,
            "name" => "Term 1",
            "start_at" => "2025-09-01",
            "end_at" => "2026-01-15",
            "status" => "active",
            "activated_at" => $now,
            "created_at" => $now,
            "updated_at" => $now,
        ]);

        User::query()->create([
            "Stud_ID" => "202211002",
            "Name" => "Test Student",
            "Email" => "student@example.test",
            "Password" => bcrypt("secret123"),
            "is_active" => 1,
        ]);

        $this->actingAs(User::query()->find("202211002"));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_student_semester_start_question_is_answered(): void
    {
        $response = $this->postJson("/chat", ["message" => "When does the semester start?"]);
        $response->assertStatus(200);
        $reply = $response->json("reply");

        $this->assertStringContainsString("started on September 1, 2025", $reply);
        $this->assertStringContainsString("Term 1", $reply);
    }

    public function test_student_semester_end_question_is_answered(): void
    {
        $response = $this->postJson("/chat", ["message" => "When does the semester end?"]);
        $response->assertStatus(200);
        $reply = $response->json("reply");

        $this->assertStringContainsString("ends on January 15, 2026", $reply);
        $this->assertStringContainsString("AY 2025-2026", $reply);
    }
}
