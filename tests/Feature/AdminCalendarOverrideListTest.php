<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;
use Carbon\Carbon;

class AdminCalendarOverrideListTest extends TestCase
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
                "Admin_ID" => "ADMOVLIST",
                "Name" => "Admin List Tester",
                "Email" => "adminlist@test.local",
                "Password" => Hash::make("secret123"),
            ],
            $overrides,
        );
        return Admin::create($data);
    }

    public function test_admin_list_returns_block_all_for_oct_1_and_4_2025(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, "admin");

        // Seed two Suspended days: 2025-10-01 and 2025-10-04
        DB::table("calendar_overrides")->insert([
            [
                "start_date" => "2025-10-01",
                "end_date" => "2025-10-01",
                "scope_type" => "all",
                "scope_id" => null,
                "effect" => "block_all",
                "allowed_mode" => null,
                "reason_key" => "facility",
                "reason_text" => "Strike",
                "created_by" => "TEST",
                "created_at" => now(),
                "updated_at" => now(),
            ],
            [
                "start_date" => "2025-10-04",
                "end_date" => "2025-10-04",
                "scope_type" => "all",
                "scope_id" => null,
                "effect" => "block_all",
                "allowed_mode" => null,
                "reason_key" => "power_outage",
                "reason_text" => "Strike",
                "created_by" => "TEST",
                "created_at" => now(),
                "updated_at" => now(),
            ],
        ]);

        $res = $this->getJson(
            "/api/admin/calendar/overrides?start_date=2025-10-01&end_date=2025-10-31",
        );
        $res->assertOk();
        $json = $res->json();
        $this->assertTrue(($json["success"] ?? false) === true);
        $k1 = Carbon::parse("2025-10-01")->format("Y-m-d");
        $k4 = Carbon::parse("2025-10-04")->format("Y-m-d");

        $this->assertArrayHasKey($k1, $json["overrides"]);
        $this->assertArrayHasKey($k4, $json["overrides"]);

        $this->assertNotEmpty($json["overrides"][$k1], "Oct 1 should have at least one override");
        $this->assertNotEmpty($json["overrides"][$k4], "Oct 4 should have at least one override");

        $this->assertSame("block_all", $json["overrides"][$k1][0]["effect"]);
        $this->assertSame("block_all", $json["overrides"][$k4][0]["effect"]);

        // Labels now use the corrected term "Suspension" for block_all days
        $this->assertSame(
            "Suspension",
            $json["overrides"][$k1][0]["label"],
            "Admin list label for block_all should be Suspension",
        );
        $this->assertSame(
            "Suspension",
            $json["overrides"][$k4][0]["label"],
            "Admin list label for block_all should be Suspension",
        );
    }
}
