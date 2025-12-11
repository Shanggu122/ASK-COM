<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Admin;

class SuspensionNotificationsTest extends TestCase
{
    // Note: We avoid RefreshDatabase and instead create minimal tables needed for this test.

    protected function setUp(): void
    {
        parent::setUp();

        // Create minimal schemas if they don't exist (SQLite-friendly)
        if (!Schema::hasTable("professors")) {
            Schema::create("professors", function (Blueprint $table) {
                $table->integer("Prof_ID")->primary();
                $table->string("Name")->nullable();
                $table->text("Schedule")->nullable();
            });
        } else {
            DB::table("professors")->truncate();
        }

        if (!Schema::hasTable("t_student")) {
            Schema::create("t_student", function (Blueprint $table) {
                $table->integer("Stud_ID")->primary();
                $table->string("Name")->nullable();
                $table->string("Email")->nullable();
            });
        } else {
            DB::table("t_student")->truncate();
        }

        if (!Schema::hasTable("notifications")) {
            Schema::create("notifications", function (Blueprint $table) {
                $table->increments("id");
                $table->integer("user_id");
                $table->integer("booking_id")->nullable();
                $table->string("type");
                $table->string("title");
                $table->text("message")->nullable();
                $table->boolean("is_read")->default(false);
                $table->timestamps();
            });
        } else {
            DB::table("notifications")->truncate();
        }

        if (!Schema::hasTable("calendar_overrides")) {
            Schema::create("calendar_overrides", function (Blueprint $table) {
                $table->increments("id");
                $table->date("start_date");
                $table->date("end_date");
                $table->string("scope_type")->nullable();
                $table->integer("scope_id")->nullable();
                $table->string("effect");
                $table->string("allowed_mode")->nullable();
                $table->string("reason_key")->nullable();
                $table->string("reason_text")->nullable();
                $table->integer("created_by")->nullable();
                $table->timestamps();
            });
        } else {
            DB::table("calendar_overrides")->truncate();
        }

        if (!Schema::hasTable("t_consultation_bookings")) {
            Schema::create("t_consultation_bookings", function (Blueprint $table) {
                $table->increments("Booking_ID");
                $table->integer("Prof_ID")->nullable();
                $table->integer("Stud_ID")->nullable();
                $table->integer("Consult_type_ID")->nullable();
                $table->string("Custom_Type")->nullable();
                $table->string("Booking_Date")->nullable();
                $table->string("Mode")->nullable();
                $table->string("Status")->nullable();
                $table->timestamps();
            });
        } else {
            DB::table("t_consultation_bookings")->truncate();
        }

        if (!Schema::hasTable("admin")) {
            Schema::create("admin", function (Blueprint $table) {
                $table->integer("Admin_ID")->primary();
                $table->string("Name")->nullable();
                $table->string("Email")->nullable();
                $table->string("Password")->nullable();
                $table->string("profile_picture")->nullable();
            });
        } else {
            DB::table("admin")->truncate();
        }
    }

    public function test_applies_suspension_and_creates_notifications_for_all_users()
    {
        // Seed professors and students
        DB::table("professors")->insert([
            ["Prof_ID" => 1, "Name" => "Prof A"],
            ["Prof_ID" => 2, "Name" => "Prof B"],
        ]);
        DB::table("t_student")->insert([
            ["Stud_ID" => 101, "Name" => "Student One"],
            ["Stud_ID" => 102, "Name" => "Student Two"],
            ["Stud_ID" => 103, "Name" => "Student Three"],
        ]);

        // Create admin and act as admin guard
        DB::table("admin")->insert([
            "Admin_ID" => 9001,
            "Name" => "Test Admin",
            "Email" => "admin@test.local",
            "Password" => bcrypt("secret"),
        ]);
        $admin = Admin::query()->where("Admin_ID", 9001)->first();
        $this->actingAs($admin, "admin");

        // Apply a block_all override (suspension)
        $payload = [
            "start_date" => now()->toDateString(),
            "end_date" => now()->toDateString(),
            "scope_type" => "all",
            "effect" => "block_all",
            "reason_text" => "Test suspension",
            "auto_reschedule" => false,
        ];

        $response = $this->postJson("/api/admin/calendar/overrides/apply", $payload);
        $response->assertStatus(200);

        // Assert notifications exist for all professors
        $profNotifs = DB::table("notifications")
            ->whereIn("user_id", [1, 2])
            ->where("type", "suspention_day")
            ->count();
        $this->assertEquals(2, $profNotifs, "Expected suspension notifications for all professors");

        // Assert notifications exist for all students
        $studNotifs = DB::table("notifications")
            ->whereIn("user_id", [101, 102, 103])
            ->where("type", "suspention_day")
            ->count();
        $this->assertEquals(3, $studNotifs, "Expected suspension notifications for all students");

        // Assert message shape contains "No classes" phrase
        $sample = DB::table("notifications")->where("type", "suspention_day")->first();
        $this->assertNotNull($sample);
        $this->assertStringContainsString("No classes", $sample->message ?? "");
    }
}
