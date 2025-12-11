<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;
use App\Models\Professor;

class AdminCalendarExcludeProfessorLeaveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!Schema::hasTable("admin")) {
            Schema::create("admin", function (Blueprint $table) {
                $table->integer("Admin_ID")->primary();
                $table->string("Name")->nullable();
                $table->string("Email")->nullable();
                $table->string("Password");
            });
        }
        if (!Schema::hasTable("calendar_overrides")) {
            Schema::create("calendar_overrides", function (Blueprint $table) {
                $table->bigIncrements("id");
                $table->date("start_date");
                $table->date("end_date");
                $table->string("scope_type", 32)->default("all");
                $table->string("scope_id", 64)->nullable();
                $table->string("effect", 16);
                $table->string("allowed_mode", 16)->nullable();
                $table->string("reason_key", 64)->nullable();
                $table->string("reason_text", 255)->nullable();
                $table->string("created_by", 64)->nullable();
                $table->timestamps();
            });
        }
    }

    protected function createAdmin(array $overrides = []): Admin
    {
        $data = array_merge(
            [
                "Admin_ID" => 1,
                "Name" => "Admin Tester",
                "Email" => "admin@test.local",
                "Password" => Hash::make("secret"),
            ],
            $overrides,
        );
        return Admin::create($data);
    }

    public function test_admin_overrides_api_excludes_professor_leave(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, "admin");

        $date = now()->toDateString();
        // Insert a holiday, a professor leave, and a force_mode
        DB::table("calendar_overrides")->insert([
            "start_date" => $date,
            "end_date" => $date,
            "scope_type" => "all",
            "scope_id" => null,
            "effect" => "holiday",
            "allowed_mode" => null,
            "reason_key" => null,
            "reason_text" => "Holiday",
            "created_by" => "admin",
            "created_at" => now(),
            "updated_at" => now(),
        ]);
        DB::table("calendar_overrides")->insert([
            "start_date" => $date,
            "end_date" => $date,
            "scope_type" => "professor",
            "scope_id" => "20256661",
            "effect" => "block_all",
            "allowed_mode" => null,
            "reason_key" => "prof_leave",
            "reason_text" => "Leave",
            "created_by" => null,
            "created_at" => now(),
            "updated_at" => now(),
        ]);
        DB::table("calendar_overrides")->insert([
            "start_date" => $date,
            "end_date" => $date,
            "scope_type" => "all",
            "scope_id" => null,
            "effect" => "force_mode",
            "allowed_mode" => "online",
            "reason_key" => "online_day",
            "reason_text" => "Forced Online",
            "created_by" => "admin",
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        $res = $this->getJson("/api/admin/calendar/overrides?start_date={$date}&end_date={$date}");
        $res->assertStatus(200);
        $json = $res->json();
        $this->assertTrue($json["success"]);
        $this->assertArrayHasKey($date, $json["overrides"]);
        $items = $json["overrides"][$date];
        // Ensure there is no 'prof_leave' item in admin list
        $this->assertTrue(
            collect($items)->every(function ($i) {
                return ($i["reason_key"] ?? null) !== "prof_leave";
            }),
        );
    }
}
