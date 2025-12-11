<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;

class ChatBotDepartmentProfessorListTest extends TestCase
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
            "Stud_ID" => "202211006",
            "Name" => "Student E",
            "Email" => "e@example.test",
            "Password" => bcrypt("secret"),
            "is_active" => 1,
        ]);

        DB::table("professors")->insert([
            ["Prof_ID" => 5001, "Name" => "Prof ITIS A", "Dept_ID" => 1],
            ["Prof_ID" => 5002, "Name" => "Prof ITIS B", "Dept_ID" => 1],
            ["Prof_ID" => 5003, "Name" => "Prof CS A", "Dept_ID" => 2],
        ]);

        $user = User::where("Stud_ID", "202211006")->first();
        if (!$user) {
            DB::table("users")->insert([
                "Stud_ID" => "202211006",
                "Name" => "Student E",
                "Email" => "e@example.test",
                "Password" => bcrypt("secret"),
            ]);
            $user = User::where("Stud_ID", "202211006")->first();
        }
        $this->be($user);
    }

    public function test_list_professors_in_itis()
    {
        $res = $this->post("/chat", ["message" => "List all the professors in IT&IS"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("IT&IS Professors:", $text);
        $this->assertStringContainsString("Prof ITIS A", $text);
        $this->assertStringContainsString("Prof ITIS B", $text);
        $this->assertStringNotContainsString("Prof CS A", $text);
    }

    public function test_list_professors_in_comsci()
    {
        $res = $this->post("/chat", ["message" => "List all the professors in ComSci"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Computer Science Professors:", $text);
        $this->assertStringContainsString("Prof CS A", $text);
        $this->assertStringNotContainsString("Prof ITIS A", $text);
    }

    public function test_who_teaches_in_comsci_department_maps_to_same_list()
    {
        $res = $this->post("/chat", [
            "message" => "Who teaches in the Computer Science department?",
        ]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("Computer Science Professors:", $text);
        $this->assertStringContainsString("Prof CS A", $text);
        $this->assertStringNotContainsString("Prof ITIS A", $text);
    }

    public function test_who_teaches_in_information_technology_maps_to_itis()
    {
        $res = $this->post("/chat", ["message" => "Who teaches in the Information Technology"]);
        $res->assertStatus(200);
        $text = $res->json("reply");

        $this->assertStringContainsString("IT&IS Professors:", $text);
        $this->assertStringContainsString("Prof ITIS A", $text);
        $this->assertStringContainsString("Prof ITIS B", $text);
        $this->assertStringNotContainsString("Prof CS A", $text);
    }
}
