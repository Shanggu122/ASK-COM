<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;
use PHPUnit\Framework\Attributes\Test;

class AdminCalendarOverrideApplyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure minimal admin table exists for guard auth
        if (!Schema::hasTable("admin")) {
            Schema::create("admin", function (Blueprint $table) {
                $table->string("Admin_ID", 9)->primary();
                $table->string("Name", 100)->nullable();
                $table->string("Email", 150)->nullable();
                $table->string("Password", 255);
                $table->string("remember_token", 100)->nullable();
                $table->boolean("is_active")->default(1);
            });
        } else {
            Schema::table("admin", function (Blueprint $table) {
                if (!Schema::hasColumn("admin", "remember_token")) {
                    $table->string("remember_token", 100)->nullable();
                }
                if (!Schema::hasColumn("admin", "is_active")) {
                    $table->boolean("is_active")->default(1);
                }
            });
        }

        // Ensure calendar_overrides exists in test DB (sqlite-friendly schema)
        if (!Schema::hasTable("calendar_overrides")) {
            Schema::create("calendar_overrides", function (Blueprint $table) {
                $table->bigIncrements("id");
                $table->date("start_date");
                $table->date("end_date");
                $table->string("scope_type", 32)->default("all");
                $table->string("scope_id", 64)->nullable();
                $table->string("effect", 16); // force_mode | block_all | holiday
                $table->string("allowed_mode", 16)->nullable(); // online | onsite
                $table->string("reason_key", 64)->nullable();
                $table->string("reason_text", 255)->nullable();
                $table->string("created_by", 64)->nullable();
                $table->timestamps();
            });
        }

        // Minimal bookings table to satisfy controller queries (can be empty)
        if (!Schema::hasTable("t_consultation_bookings")) {
            Schema::create("t_consultation_bookings", function (Blueprint $table) {
                $table->bigIncrements("Booking_ID");
                $table->unsignedBigInteger("Prof_ID")->nullable();
                $table->unsignedBigInteger("Stud_ID")->nullable();
                $table->unsignedBigInteger("Consult_type_ID")->nullable();
                $table->string("Custom_Type", 255)->nullable();
                $table->string("Booking_Date", 32); // e.g., "Thu Dec 25 2025"
                $table->string("Mode", 16)->nullable();
                $table->string("Status", 32)->default("pending");
                $table->string("reschedule_reason", 255)->nullable();
                $table->timestamps();
            });
        }
    }

    protected function createAdmin(array $overrides = []): Admin
    {
        $data = array_merge(
            [
                "Admin_ID" => "ADMTEST01",
                "Name" => "Admin Tester",
                "Email" => "admin@test.local",
                "Password" => Hash::make("secret123"),
            ],
            $overrides,
        );
        return Admin::create($data);
    }

    #[Test]
    public function admin_can_apply_holiday_override_and_row_is_created(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, "admin");

        $payload = [
            "start_date" => "2025-12-25",
            "effect" => "holiday",
            "allowed_mode" => null,
            "reason_key" => "holiday",
            "reason_text" => "Christmas Day",
        ];

        $res = $this->postJson("/api/admin/calendar/overrides/apply", $payload);
        $res->assertStatus(200);
        $json = $res->json();
        $this->assertTrue($json["success"] ?? false, "apply should return success");

        $count = DB::table("calendar_overrides")
            ->where("start_date", "2025-12-25")
            ->where("end_date", "2025-12-25")
            ->where("effect", "holiday")
            ->count();

        $this->assertGreaterThan(0, $count, "calendar_overrides record should exist after apply");
    }
}
