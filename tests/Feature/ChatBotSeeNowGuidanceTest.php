<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;

class ChatBotSeeNowGuidanceTest extends TestCase
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
        if (!Schema::hasTable("professors")) {
            Schema::create("professors", function (Blueprint $table) {
                $table->unsignedBigInteger("Prof_ID")->primary();
                $table->string("Name");
                $table->text("Schedule")->nullable();
                $table->unsignedInteger("Dept_ID")->nullable();
            });
        } elseif (!Schema::hasColumn("professors", "Dept_ID")) {
            Schema::table("professors", function (Blueprint $table) {
                $table->unsignedInteger("Dept_ID")->nullable();
            });
        }

        DB::table("t_student")->delete();
        DB::table("professors")->delete();

        DB::table("t_student")->insert([
            "Stud_ID" => "202211005",
            "Name" => "Student D",
            "Email" => "d@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
        ]);

        DB::table("professors")->insert([
            [
                "Prof_ID" => 4001,
                "Name" => "Professor Abaleta",
                "Schedule" => "Mon 1:00 PM - 2:00 PM; Tue 1:54 PM - 2:54 PM",
                "Dept_ID" => 1,
            ],
        ]);

        $user = User::where("Stud_ID", "202211005")->first();
        if (!$user) {
            DB::table("users")->insert([
                "Stud_ID" => "202211005",
                "Name" => "Student D",
                "Email" => "d@example.test",
                "Password" => bcrypt("secret"),
            ]);
            $user = User::where("Stud_ID", "202211005")->first();
        }
        $this->be($user);
    }

    public function test_can_i_see_professor_now_guides_to_messages_and_department()
    {
        $res = $this->post("/chat", ["message" => "Can I see Professor Abaleta now?"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Messages page", $text);
        $this->assertStringContainsString("IT&IS Department", $text);
        $this->assertStringNotContainsString("slots available", $text);
    }

    public function test_is_professor_in_the_office_guides_to_messages_and_department()
    {
        $res = $this->post("/chat", ["message" => "Is Professor Abaleta in the office?"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Messages page", $text);
        $this->assertStringContainsString("IT&IS Department", $text);
        $this->assertStringNotContainsString("slots available", $text);
    }
}
