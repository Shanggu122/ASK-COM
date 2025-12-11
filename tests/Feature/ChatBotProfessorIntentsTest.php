<?php

namespace Tests\Feature;

use App\Models\Professor;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatBotProfessorIntentsTest extends TestCase
{
    protected Professor $professor;
    protected string $todayKey;
    protected int $termId;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2025, 11, 11, 9, 0, 0, "Asia/Manila"));

        $this->ensureSchema();
        $this->seedBaseData();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_professor_consultations_today_lists_entries(): void
    {
        $response = $this->postJson("/chat", ["message" => "What are my consultations for today?"]);

        $response->assertStatus(200);
        $reply = $response->json("reply");

        $this->assertStringContainsString("Consultations for today", $reply);
        $this->assertStringContainsString("Anna Cruz", $reply);
        $this->assertStringContainsString("Ben Reyes", $reply);
        $this->assertStringContainsString("9:00 AM", $reply);
    }

    public function test_professor_students_today_lists_names(): void
    {
        $response = $this->postJson("/chat", [
            "message" => "Who are the students scheduled for consultation today?",
        ]);

        $response->assertStatus(200);
        $reply = $response->json("reply");

        $this->assertStringContainsString("Students scheduled for today", $reply);
        $this->assertStringContainsString("Anna Cruz", $reply);
        $this->assertStringContainsString("Ben Reyes", $reply);
    }

    public function test_professor_available_slots_today_counts_remaining(): void
    {
        $response = $this->postJson("/chat", [
            "message" => "How many consultation slots are still available today?",
        ]);

        $response->assertStatus(200);
        $reply = $response->json("reply");

        $this->assertStringContainsString("4 open slot", $reply);
        $this->assertStringContainsString("1 of 5 slots are already booked", $reply);
    }

    public function test_professor_schedule_handles_sched_shortcut(): void
    {
        $response = $this->postJson("/chat", ["message" => "MY SCHED"]);

        $response->assertStatus(200);
        $reply = $response->json("reply");

        $this->assertStringContainsString("Your consultation schedule is", $reply);
        $this->assertStringContainsString("Tuesday 9:00 AM - 11:00 AM", $reply);
    }

    public function test_professor_completed_consultations_this_week(): void
    {
        $response = $this->postJson("/chat", [
            "message" => "How many completed consultations do I have for this week?",
        ]);

        $response->assertStatus(200);
        $reply = $response->json("reply");

        $this->assertStringContainsString("You completed 2 consultation", $reply);
        $this->assertStringContainsString("this week", $reply);
    }

    public function test_professor_semester_dates_are_exposed(): void
    {
        $startResponse = $this->postJson("/chat", ["message" => "When does the semester start?"]);
        $startResponse->assertStatus(200);
        $startText = $startResponse->json("reply");

        $this->assertStringContainsString("started on September 1, 2025", $startText);
        $this->assertStringContainsString("Term 1", $startText);

        $endResponse = $this->postJson("/chat", ["message" => "When does the semester end?"]);
        $endResponse->assertStatus(200);
        $endText = $endResponse->json("reply");

        $this->assertStringContainsString("ends on January 15, 2026", $endText);
        $this->assertStringContainsString("AY 2025-2026", $endText);
    }

    public function test_professor_subjects_list_is_returned(): void
    {
        $response = $this->postJson("/chat", [
            "message" => "What are my subjects for consultation?",
        ]);

        $response->assertStatus(200);
        $reply = $response->json("reply");

        $this->assertStringContainsString("Your consultation subjects are", $reply);
        $this->assertStringContainsString("Algorithms", $reply);
        $this->assertStringContainsString("Capstone Project", $reply);
    }

    public function test_professor_out_of_scope_message_returns_scope_notice(): void
    {
        $response = $this->postJson("/chat", ["message" => "Do I look pogi today?"]);

        $response->assertStatus(200);
        $reply = strtolower($response->json("reply"));

        $this->assertStringContainsString("english", $reply);
        $this->assertStringContainsString("consultation", $reply);
    }

    public function test_professor_profanity_returns_respectful_notice(): void
    {
        $response = $this->postJson("/chat", ["message" => "fuck you"]);

        $response->assertStatus(200);
        $reply = strtolower($response->json("reply"));

        $this->assertStringContainsString("respectful", $reply);
        $this->assertStringContainsString("english", $reply);
    }

    private function ensureSchema(): void
    {
        if (!Schema::hasTable("professors")) {
            Schema::create("professors", function (Blueprint $table) {
                $table->integer("Prof_ID")->primary();
                $table->string("Name")->nullable();
                $table->string("Email")->nullable();
                $table->string("Password")->nullable();
                $table->text("Schedule")->nullable();
            });
        }

        if (!Schema::hasTable("t_student")) {
            Schema::create("t_student", function (Blueprint $table) {
                $table->string("Stud_ID", 12)->primary();
                $table->string("Name")->nullable();
                $table->string("Email")->nullable();
                $table->string("Password")->nullable();
                $table->boolean("is_active")->default(true);
            });
        }

        if (!Schema::hasTable("t_subject")) {
            Schema::create("t_subject", function (Blueprint $table) {
                $table->bigIncrements("Subject_ID");
                $table->string("Subject_Name");
            });
        }

        if (!Schema::hasTable("professor_subject")) {
            Schema::create("professor_subject", function (Blueprint $table) {
                $table->bigIncrements("id");
                $table->integer("Prof_ID");
                $table->unsignedBigInteger("Subject_ID");
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

        if (!Schema::hasTable("t_consultation_bookings")) {
            Schema::create("t_consultation_bookings", function (Blueprint $table) {
                $table->bigIncrements("Booking_ID");
                $table->integer("Prof_ID");
                $table->string("Stud_ID", 12)->nullable();
                $table->string("Booking_Date", 32);
                $table->string("Booking_Time", 16)->nullable();
                $table->string("Mode", 16)->nullable();
                $table->string("Status", 32)->nullable();
                $table->unsignedBigInteger("Subject_ID")->nullable();
                $table->unsignedBigInteger("term_id")->nullable();
                $table->timestamps();
            });
        }
    }

    private function seedBaseData(): void
    {
        DB::table("professor_subject")->delete();
        DB::table("t_consultation_bookings")->delete();
        DB::table("t_subject")->delete();
        DB::table("t_student")->delete();
        DB::table("professors")->delete();
        DB::table("terms")->delete();
        DB::table("academic_years")->delete();

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

        $this->termId = DB::table("terms")->insertGetId([
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

        $this->professor = Professor::create([
            "Prof_ID" => 2001,
            "Name" => "Prof Uno",
            "Email" => "prof1@example.test",
            "Password" => bcrypt("secret123"),
            "Schedule" => "Tuesday 9:00 AM - 11:00 AM\nThursday 1:00 PM - 3:00 PM",
        ]);

        $this->actingAs($this->professor, "professor");

        $students = [
            ["Stud_ID" => "2022A", "Name" => "Anna Cruz", "Email" => "anna@example.test"],
            ["Stud_ID" => "2022B", "Name" => "Ben Reyes", "Email" => "ben@example.test"],
            ["Stud_ID" => "2022C", "Name" => "Chino Dizon", "Email" => "chino@example.test"],
        ];

        foreach ($students as $student) {
            DB::table("t_student")->insert([
                "Stud_ID" => $student["Stud_ID"],
                "Name" => $student["Name"],
                "Email" => $student["Email"],
                "Password" => bcrypt("secret123"),
                "is_active" => 1,
            ]);
        }

        $algorithmsId = DB::table("t_subject")->insertGetId(["Subject_Name" => "Algorithms"]);
        $capstoneId = DB::table("t_subject")->insertGetId(["Subject_Name" => "Capstone Project"]);

        DB::table("professor_subject")->insert([
            ["Prof_ID" => $this->professor->Prof_ID, "Subject_ID" => $algorithmsId],
            ["Prof_ID" => $this->professor->Prof_ID, "Subject_ID" => $capstoneId],
        ]);

        $this->todayKey = Carbon::now("Asia/Manila")->format("D M d Y");

        DB::table("t_consultation_bookings")->insert([
            [
                "Prof_ID" => $this->professor->Prof_ID,
                "Stud_ID" => "2022A",
                "Booking_Date" => $this->todayKey,
                "Booking_Time" => "09:00",
                "Mode" => "online",
                "Status" => "approved",
                "Subject_ID" => $algorithmsId,
                "term_id" => $this->termId,
                "created_at" => $now,
                "updated_at" => $now,
            ],
            [
                "Prof_ID" => $this->professor->Prof_ID,
                "Stud_ID" => "2022B",
                "Booking_Date" => $this->todayKey,
                "Booking_Time" => "10:00",
                "Mode" => "onsite",
                "Status" => "pending",
                "Subject_ID" => $capstoneId,
                "term_id" => $this->termId,
                "created_at" => $now,
                "updated_at" => $now,
            ],
        ]);

        $weekStart = Carbon::now("Asia/Manila")->startOfWeek(Carbon::MONDAY);
        $completedSlots = [$weekStart->copy()->addDays(1), $weekStart->copy()->addDays(2)];

        foreach ($completedSlots as $slot) {
            DB::table("t_consultation_bookings")->insert([
                "Prof_ID" => $this->professor->Prof_ID,
                "Stud_ID" => "2022C",
                "Booking_Date" => $slot->format("D M d Y"),
                "Booking_Time" => "14:00",
                "Mode" => "online",
                "Status" => "completed",
                "Subject_ID" => $algorithmsId,
                "term_id" => $this->termId,
                "created_at" => $now,
                "updated_at" => $now,
            ]);
        }
    }
}
