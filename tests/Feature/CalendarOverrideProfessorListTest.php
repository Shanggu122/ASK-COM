<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Professor;
use Carbon\Carbon;

class CalendarOverrideProfessorListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable("professors")) {
            Schema::create("professors", function (Blueprint $table) {
                $table->integer("Prof_ID")->primary();
                $table->string("Name", 100)->nullable();
                $table->string("Email", 150)->nullable();
                $table->string("Password", 255);
                $table->boolean("is_active")->default(1);
                $table->string("remember_token", 100)->nullable();
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

    protected function createProfessor(array $overrides = []): Professor
    {
        $data = array_merge(
            [
                "Prof_ID" => 20250001,
                "Name" => "Prof Test",
                "Email" => "proflist@test.local",
                "Password" => Hash::make("secret123"),
            ],
            $overrides,
        );
        return Professor::create($data);
    }

    public function test_requires_authentication(): void
    {
        $res = $this->get(
            "/api/professor/calendar/overrides?start_date=2025-12-01&end_date=2025-12-31",
        );
        $res->assertStatus(302);
    }

    public function test_professor_list_includes_global_and_prof_scoped(): void
    {
        $prof = $this->createProfessor(["Prof_ID" => 20251234]);
        $this->actingAs($prof, "professor");

        // Global suspended on Dec 20
        DB::table("calendar_overrides")->insert([
            "start_date" => "2025-12-20",
            "end_date" => "2025-12-20",
            "scope_type" => "all",
            "scope_id" => null,
            "effect" => "block_all",
            "allowed_mode" => null,
            "reason_key" => "others",
            "reason_text" => "Campus maintenance",
            "created_by" => "TEST",
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        // Professor-scoped force online on Dec 22
        DB::table("calendar_overrides")->insert([
            "start_date" => "2025-12-22",
            "end_date" => "2025-12-22",
            "scope_type" => "professor",
            "scope_id" => (string) $prof->Prof_ID,
            "effect" => "force_mode",
            "allowed_mode" => "online",
            "reason_key" => "online_day",
            "reason_text" => null,
            "created_by" => "TEST",
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        $res = $this->getJson(
            "/api/professor/calendar/overrides?start_date=2025-12-01&end_date=2025-12-31",
        );
        $res->assertOk();
        $json = $res->json();
        $this->assertTrue(($json["success"] ?? false) === true);

        $k1 = Carbon::parse("2025-12-20")->format("Y-m-d");
        $k2 = Carbon::parse("2025-12-22")->format("Y-m-d");
        $this->assertArrayHasKey($k1, $json["overrides"]);
        $this->assertArrayHasKey($k2, $json["overrides"]);
        $this->assertSame("block_all", $json["overrides"][$k1][0]["effect"]);
        $this->assertSame("force_mode", $json["overrides"][$k2][0]["effect"]);
        $this->assertSame("online", $json["overrides"][$k2][0]["allowed_mode"]);
        // Labels: global block_all now labeled as "Suspension"
        $this->assertSame("Suspension", $json["overrides"][$k1][0]["label"]);
        $this->assertSame("Force Online", $json["overrides"][$k2][0]["label"]);
    }
}
