<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists("login_attempts");
        Schema::dropIfExists("professors");
        Schema::dropIfExists("admin");
        Schema::dropIfExists("t_student");

        Schema::create("t_student", function (Blueprint $table): void {
            $table->string("Stud_ID")->primary();
            $table->string("Name")->nullable();
            $table->string("Dept_ID")->nullable();
            $table->string("Email")->nullable();
            $table->string("Password");
            $table->boolean("is_active")->default(true);
            $table->string("remember_token")->nullable();
        });

        Schema::create("admin", function (Blueprint $table): void {
            $table->string("Admin_ID")->primary();
            $table->string("Name")->nullable();
            $table->string("Email")->nullable();
            $table->string("Password");
            $table->boolean("is_active")->default(true);
            $table->string("remember_token")->nullable();
        });

        Schema::create("professors", function (Blueprint $table): void {
            $table->integer("Prof_ID")->primary();
            $table->string("Name")->nullable();
            $table->string("Email")->nullable();
            $table->string("Password");
            $table->boolean("is_active")->default(true);
            $table->string("remember_token")->nullable();
        });

        Schema::create("login_attempts", function (Blueprint $table): void {
            $table->id();
            $table->string("stud_id")->nullable();
            $table->string("prof_id")->nullable();
            $table->string("admin_id")->nullable();
            $table->string("ip")->nullable();
            $table->string("user_agent")->nullable();
            $table->boolean("successful")->default(false);
            $table->string("reason")->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function student_can_login_with_username_and_password(): void
    {
        $student = User::query()->create([
            "Stud_ID" => "202400001",
            "Password" => Hash::make("secret123"),
            "is_active" => 1,
        ]);

        $response = $this->post(route("login.submit"), [
            "username" => "202400001",
            "password" => "secret123",
        ]);

        $response->assertRedirect(route("dashboard"));
        $this->assertAuthenticatedAs($student, "web");
    }

    #[Test]
    public function admin_is_redirected_to_admin_dashboard(): void
    {
        $admin = Admin::query()->create([
            "Admin_ID" => "admin1",
            "Password" => Hash::make("adminpass"),
            "is_active" => 1,
        ]);

        $response = $this->post(route("login.submit"), [
            "username" => "admin1",
            "password" => "adminpass",
        ]);

        $response->assertRedirect(route("admin.dashboard"));
        $this->assertAuthenticatedAs($admin, "admin");
    }

    #[Test]
    public function professor_is_redirected_to_professor_dashboard(): void
    {
        $professor = Professor::query()->create([
            "Prof_ID" => 42,
            "Password" => Hash::make("profpass"),
            "is_active" => 1,
        ]);

        $response = $this->post(route("login.submit"), [
            "username" => "00042",
            "password" => "profpass",
        ]);

        $response->assertRedirect(route("dashboard.professor"));
        $this->assertAuthenticatedAs($professor, "professor");
    }

    #[Test]
    public function unknown_username_returns_username_error(): void
    {
        $response = $this->from(route("login"))->post(route("login.submit"), [
            "username" => "does-not-exist",
            "password" => "irrelevant",
        ]);

        $response->assertRedirect(route("login"));
        $response->assertSessionHasErrors(["login"]);
        $this->assertSame("Username does not exist.", session("errors")->first("login"));
        $this->assertGuest();
    }

    #[Test]
    public function incorrect_password_returns_specific_message(): void
    {
        User::query()->create([
            "Stud_ID" => "202400002",
            "Password" => Hash::make("correct-pass"),
            "is_active" => 1,
        ]);

        $response = $this->from(route("login"))->post(route("login.submit"), [
            "username" => "202400002",
            "password" => "wrong-pass",
        ]);

        $response->assertRedirect(route("login"));
        $response->assertSessionHasErrors(["login"]);
        $this->assertSame("Incorrect password.", session("errors")->first("login"));
        $this->assertGuest();
    }

    #[Test]
    public function inactive_account_receives_inactive_message(): void
    {
        User::query()->create([
            "Stud_ID" => "202400003",
            "Password" => Hash::make("secret123"),
            "is_active" => 0,
        ]);

        $response = $this->from(route("login"))->post(route("login.submit"), [
            "username" => "202400003",
            "password" => "secret123",
        ]);

        $response->assertRedirect(route("login"));
        $response->assertSessionHasErrors(["login"]);
        $this->assertSame("Account is inactive.", session("errors")->first("login"));
        $this->assertGuest();
    }

    #[Test]
    public function validation_requires_username_and_password(): void
    {
        $response = $this->post(route("login.submit"), []);

        $response->assertSessionHasErrors(["username", "password"]);
        $this->assertGuest();
    }

    #[Test]
    public function rate_limiter_eventually_locks_account(): void
    {
        User::query()->create([
            "Stud_ID" => "202400004",
            "Password" => Hash::make("correct123"),
            "is_active" => 1,
        ]);

        $maxAttempts = (int) config("auth_security.rate_limit_max_attempts", 5);

        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->from(route("login"))->post(route("login.submit"), [
                "username" => "202400004",
                "password" => "bad-pass-" . $i,
            ]);
        }

        $response = $this->from(route("login"))->post(route("login.submit"), [
            "username" => "202400004",
            "password" => "another-bad-pass",
        ]);

        $response->assertRedirect(route("login"));
        $response->assertSessionHasErrors(["login"]);
        $this->assertStringContainsString("Too many attempts.", session("errors")->first("login"));
    }

    #[Test]
    public function remember_me_sets_cookie_when_supported(): void
    {
        $student = User::query()->create([
            "Stud_ID" => "202400005",
            "Password" => Hash::make("rememberme"),
            "is_active" => 1,
        ]);

        $response = $this->post(route("login.submit"), [
            "username" => "202400005",
            "password" => "rememberme",
            "remember" => "on",
        ]);

        $response->assertRedirect(route("dashboard"));
        $cookies = $response->headers->getCookies();
        $this->assertTrue(
            collect($cookies)->contains(
                fn($cookie) => str_contains($cookie->getName(), "remember"),
            ),
        );
        $this->assertAuthenticatedAs($student);
    }

    #[Test]
    public function login_page_renders_unified_form_controls(): void
    {
        $response = $this->get(route("login"));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('id="unified-login-form"', $html);
        $this->assertStringContainsString('name="username"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="remember"', $html);
        $this->assertStringContainsString("Forgot Password?", $html);
    }

    #[Test]
    public function successful_login_is_audited(): void
    {
        $student = User::query()->create([
            "Stud_ID" => "202400006",
            "Password" => Hash::make("auditpass"),
            "is_active" => 1,
        ]);

        $this->post(route("login.submit"), [
            "username" => "202400006",
            "password" => "auditpass",
        ]);

        $row = DB::table("login_attempts")
            ->where("stud_id", $student->Stud_ID)
            ->orderByDesc("id")
            ->first();

        $this->assertNotNull($row);
        $this->assertEquals(1, $row->successful);
        $this->assertEquals("success", $row->reason);
    }
}
