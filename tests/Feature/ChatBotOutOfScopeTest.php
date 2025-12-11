<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ChatBotOutOfScopeTest extends TestCase
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

        DB::table("t_student")->delete();

        User::query()->create([
            "Stud_ID" => "202211002",
            "Name" => "Test Student",
            "Email" => "student@example.test",
            "Password" => bcrypt("secret123"),
            "is_active" => 1,
        ]);

        $this->actingAs(User::query()->find("202211002"));
    }

    public function test_out_of_scope_message_returns_scope_notice(): void
    {
        $response = $this->postJson("/chat", ["message" => "Mabaho ba ako?"]);
        $response->assertStatus(200);
        $reply = strtolower($response->json("reply"));

        $this->assertStringContainsString("english", $reply);
        $this->assertStringContainsString("consultation", $reply);
    }
}
