<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ChatBotProfanityFilterTest extends TestCase
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

        User::query()->updateOrCreate(
            ["Stud_ID" => "202211002"],
            [
                "Name" => "Test Student",
                "Email" => "student@example.test",
                "Password" => bcrypt("secret123"),
                "is_active" => 1,
            ],
        );

        $this->actingAs(User::query()->find("202211002"));
    }

    public function test_tagalog_profanity_returns_respectful_notice(): void
    {
        $response = $this->postJson("/chat", ["message" => "tang ina mo"]);
        $response->assertStatus(200);
        $reply = strtolower($response->json("reply"));

        $this->assertStringContainsString("respectful", $reply);
        $this->assertStringContainsString("english", $reply);
    }

    public function test_english_profanity_returns_respectful_notice(): void
    {
        $response = $this->postJson("/chat", ["message" => "fuck this"]);
        $response->assertStatus(200);
        $reply = strtolower($response->json("reply"));

        $this->assertStringContainsString("respectful", $reply);
        $this->assertStringContainsString("english", $reply);
    }

    public function test_explicit_word_returns_respectful_notice(): void
    {
        $response = $this->postJson("/chat", ["message" => "you are a whore"]);
        $response->assertStatus(200);
        $reply = strtolower($response->json("reply"));

        $this->assertStringContainsString("respectful", $reply);
        $this->assertStringContainsString("english", $reply);
    }

    public function test_porn_keyword_returns_respectful_notice(): void
    {
        $response = $this->postJson("/chat", ["message" => "send porn links"]);
        $response->assertStatus(200);
        $reply = strtolower($response->json("reply"));

        $this->assertStringContainsString("respectful", $reply);
        $this->assertStringContainsString("english", $reply);
    }

    public function test_additional_tagalog_insult_returns_notice(): void
    {
        $response = $this->postJson("/chat", ["message" => "bobo ka"]);
        $response->assertStatus(200);
        $reply = strtolower($response->json("reply"));

        $this->assertStringContainsString("respectful", $reply);
        $this->assertStringContainsString("english", $reply);
    }
}
