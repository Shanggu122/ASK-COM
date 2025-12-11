<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Professor;

class AdminNotificationOnProfessorLeaveTest extends TestCase
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
        if (!Schema::hasTable("notifications")) {
            Schema::create("notifications", function (Blueprint $table) {
                $table->id();
                $table->integer("user_id");
                $table->integer("booking_id");
                $table->string("type");
                $table->string("title");
                $table->text("message");
                $table->boolean("is_read")->default(false);
                $table->timestamp("created_at")->useCurrent();
                $table->timestamp("updated_at")->useCurrent()->useCurrentOnUpdate();
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
            ],
            $overrides,
        );
        return Professor::create($data);
    }

    public function test_professor_leave_creates_admin_notification(): void
    {
        $prof = $this->createProfessor();
        $this->actingAs($prof, "professor");

        $date = now()->toDateString();
        $resp = $this->postJson("/api/professor/calendar/leave/apply", ["start_date" => $date]);
        $resp->assertStatus(200)->assertJson(["success" => true]);

        $notif = DB::table("notifications")->where("type", "professor_leave")->first();
        $this->assertNotNull($notif, "Admin notification for professor leave should be created");
        $this->assertEquals($prof->Prof_ID, $notif->user_id);
        $this->assertEquals(0, $notif->booking_id);
        $this->assertStringContainsString($prof->Name, $notif->message);
        $this->assertStringContainsString($date, $notif->message);
    }
}
