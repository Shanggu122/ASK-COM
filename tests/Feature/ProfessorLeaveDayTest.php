<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Professor;

class ProfessorLeaveDayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure tables exist (mirror patterns used in other feature tests)
        if (!Schema::hasTable("professors")) {
            Schema::create("professors", function (Blueprint $table) {
                $table->integer("Prof_ID")->primary();
                $table->string("Name", 100)->nullable();
                $table->string("Email", 150)->nullable();
                $table->string("Password", 255);
                $table->boolean("is_active")->default(1);
                $table->string("remember_token", 100)->nullable();
                $table->text("Schedule")->nullable();
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
                "Prof_ID" => 20256661,
                "Name" => "Prof Tester",
                "Email" => "leave@test.local",
                "Password" => Hash::make("secret"),
                "is_active" => 1,
            ],
            $overrides,
        );
        return Professor::create($data);
    }

    public function test_professor_can_apply_and_remove_leave_day(): void
    {
        $prof = $this->createProfessor();
        $this->actingAs($prof, "professor");

        $date = now()->toDateString();

        // Apply leave
        $resp = $this->postJson("/api/professor/calendar/leave/apply", [
            "start_date" => $date,
        ]);
        $resp->assertStatus(200)->assertJson(["success" => true]);
        $this->assertDatabaseHas("calendar_overrides", [
            "start_date" => $date,
            "end_date" => $date,
            "scope_type" => "professor",
            "scope_id" => (string) $prof->Prof_ID,
            "effect" => "block_all",
            "reason_key" => "prof_leave",
        ]);

        // Remove leave
        $resp2 = $this->postJson("/api/professor/calendar/leave/remove", [
            "start_date" => $date,
        ]);
        $resp2->assertStatus(200)->assertJson(["success" => true]);
        $this->assertDatabaseMissing("calendar_overrides", [
            "start_date" => $date,
            "end_date" => $date,
            "scope_type" => "professor",
            "scope_id" => (string) $prof->Prof_ID,
            "effect" => "block_all",
            "reason_key" => "prof_leave",
        ]);
    }

    public function test_dashboard_professor_contains_modal_and_binder_scripts(): void
    {
        $prof = $this->createProfessor(["Prof_ID" => 20256662, "Email" => "bind@test.local"]);
        $this->actingAs($prof, "professor");

        $resp = $this->get("/dashboard-professor");
        $resp->assertStatus(200);

        // Check for themed confirm/toast CSS classes rendered in the Blade
        $resp->assertSee("ascc-confirm-overlay", false);
        $resp->assertSee("ascc-confirm", false);
        $resp->assertSee("toast-wrapper", false);
        $resp->assertSee("ascc-toast", false);

        // Check for our leave-day binder exposure and function body
        $resp->assertSee("window.__bindLeaveHandlers", false);
        $resp->assertSee("function bindLeaveHandlers()", false);
        $resp->assertSee("addEventListener('click'", false); // click binding on .pika-button

        // Ensure the enable flag is present
        $resp->assertSee("window.__enableLeaveToggle = true", false);
    }

    public function test_dashboard_professor_requires_authentication(): void
    {
        // Without professor auth, the page should redirect (middleware enforced)
        $resp = $this->get("/dashboard-professor");
        $resp->assertStatus(302);
        // Prefer redirect to login-professor if configured; keep it loose to avoid hard-coding
        $this->assertTrue(
            str_contains($resp->headers->get("Location"), "login") ||
                str_contains($resp->headers->get("Location"), "login-professor"),
        );
    }
}
