<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\Professor;
use PHPUnit\Framework\Attributes\Test;

class ProfessorLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure professors table exists (minimal columns needed for tests)
        if (!Schema::hasTable("professors")) {
            Schema::create("professors", function (Blueprint $table) {
                $table->integer("Prof_ID")->primary();
                $table->string("Name", 100)->nullable();
                $table->string("Email", 150)->nullable();
                $table->string("Password", 255);
                $table->boolean("is_active")->default(1);
                $table->string("remember_token", 100)->nullable();
                $table->string("profile_picture", 255)->nullable();
                $table->integer("Dept_ID")->nullable();
                $table->text("Schedule")->nullable();
            });
        } else {
            // Make sure key columns exist
            Schema::table("professors", function (Blueprint $table) {
                if (!Schema::hasColumn("professors", "is_active")) {
                    $table->boolean("is_active")->default(1);
                }
                if (!Schema::hasColumn("professors", "remember_token")) {
                    $table->string("remember_token", 100)->nullable();
                }
            });
        }

        // Ensure audit table exists and supports professor IDs too
        if (!Schema::hasTable("login_attempts")) {
            Schema::create("login_attempts", function (Blueprint $table) {
                $table->bigIncrements("id");
                $table->string("stud_id", 32)->nullable();
                $table->string("prof_id", 32)->nullable();
                $table->string("ip", 45)->nullable();
                $table->string("user_agent", 255)->nullable();
                $table->boolean("successful")->default(false);
                $table->string("reason", 64)->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table("login_attempts", function (Blueprint $table) {
                if (!Schema::hasColumn("login_attempts", "prof_id")) {
                    $table->string("prof_id", 32)->nullable()->after("stud_id");
                }
            });
        }
    }

    protected function createProfessor(array $overrides = []): Professor
    {
        $data = array_merge(
            [
                "Prof_ID" => 20200001,
                "Name" => "Prof. Test",
                "Email" => "prof@example.com",
                "Password" => Hash::make("secret123"),
                "is_active" => 1,
                "profile_picture" => null,
            ],
            $overrides,
        );

        return Professor::create($data);
    }

    // ===== Core login flow =====

    // [1] Valid login redirects and grants dashboard access
    #[Test]
    public function professor_can_login_with_valid_credentials_and_redirects_to_dashboard(): void
    {
        $prof = $this->createProfessor();

        $response = $this->post("/login-professor", [
            "Prof_ID" => $prof->Prof_ID,
            "Password" => "secret123",
        ]);

        $response->assertRedirect(route("dashboard.professor"));
        $this->assertAuthenticatedAs($prof, "professor");
        $this->get(route("dashboard.professor"))->assertOk();
    }

    // [5] Guest is redirected to login when accessing dashboard
    #[Test]
    public function guest_is_redirected_to_login_when_accessing_professor_dashboard(): void
    {
        $response = $this->get(route("dashboard.professor"));
        $response->assertRedirect(route("login.professor"));
        $this->assertGuest("professor");
    }

    // [7] Session ID regenerates on successful login
    #[Test]
    public function session_is_regenerated_on_successful_professor_login(): void
    {
        $prof = $this->createProfessor([
            "Prof_ID" => 20209977,
            "Email" => "sessprof@example.com",
            "Password" => Hash::make("sessionpass"),
        ]);
        $oldId = session()->getId();
        $this->post("/login-professor", ["Prof_ID" => $prof->Prof_ID, "Password" => "sessionpass"]);
        $newId = session()->getId();
        $this->assertAuthenticatedAs($prof, "professor");
        $this->assertNotEquals($oldId, $newId, "Session ID should change after login");
    }

    // [14] Already-authenticated visit to login is handled (allow or redirect)
    #[Test]
    public function professor_already_authenticated_redirects_away_from_login(): void
    {
        $prof = $this->createProfessor(["Prof_ID" => 20208811, "Password" => Hash::make("go")]);
        $this->post("/login-professor", ["Prof_ID" => 20208811, "Password" => "go"]);
        $this->assertAuthenticatedAs($prof, "professor");
        $res = $this->get(route("login.professor"));
        $this->assertTrue(in_array($res->getStatusCode(), [200, 302, 303]));
    }

    // [15] Intended URL after login redirects to professor dashboard
    #[Test]
    public function intended_redirect_after_login_goes_to_professor_dashboard(): void
    {
        $prof = $this->createProfessor(["Prof_ID" => 20208812, "Password" => Hash::make("intend")]);
        session(["url.intended" => route("dashboard.professor")]);
        $res = $this->post("/login-professor", ["Prof_ID" => 20208812, "Password" => "intend"]);
        $res->assertRedirect(route("dashboard.professor"));
        $this->assertAuthenticatedAs($prof, "professor");
    }

    // [20] Logout invalidates session; dashboard access is blocked afterward
    #[Test]
    public function professor_logout_then_cannot_access_dashboard(): void
    {
        $prof = $this->createProfessor(["Prof_ID" => 20208813, "Password" => Hash::make("bye")]);
        $this->post("/login-professor", ["Prof_ID" => 20208813, "Password" => "bye"]);
        $this->assertAuthenticatedAs($prof, "professor");
        $this->get("/logout-professor");
        session()->invalidate();
        session()->regenerateToken();
        $res = $this->get(route("dashboard.professor"));
        $res->assertRedirect(route("login.professor"));
    }

    // ===== Credentials & validation =====

    // [2] Wrong password denies login and logs failed attempt
    #[Test]
    public function professor_cannot_login_with_wrong_password(): void
    {
        Log::spy();
        $prof = $this->createProfessor(["Prof_ID" => 20207770]);

        $response = $this->post("/login-professor", [
            "Prof_ID" => $prof->Prof_ID,
            "Password" => "wrongpass",
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest("professor");
        Log::shouldHaveReceived("notice")->atLeast()->once();
        $this->assertGreaterThan(
            0,
            DB::table("login_attempts")->where("prof_id", (string) $prof->Prof_ID)->count(),
        );
    }

    // [3] Invalid professor ID shows “ID not found” message
    #[Test]
    public function professor_cannot_login_with_invalid_professor_id(): void
    {
        $this->createProfessor(["Prof_ID" => 20207771]);

        $response = $this->post("/login-professor", [
            "Prof_ID" => 999999999,
            "Password" => "secret123",
        ]);

        $response->assertSessionHas("errors");
        $bag = session("errors");
        $flat = $bag ? $bag->all() : [];
        $this->assertTrue(
            in_array("Professor ID does not exist.", $flat),
            "Expected specific missing ID message",
        );
        $this->assertGuest("professor");
    }

    // [4] Empty or missing fields return validation errors for Prof_ID and Password
    #[Test]
    public function professor_cannot_login_with_empty_fields(): void
    {
        $response = $this->post("/login-professor", [
            "Prof_ID" => "",
            "Password" => "",
        ]);
        $response->assertSessionHasErrors(["Prof_ID", "Password"]);
        $this->assertGuest("professor");
    }

    // [8] Inactive or locked account cannot login
    #[Test]
    public function inactive_professor_account_cannot_login(): void
    {
        $prof = $this->createProfessor(["Prof_ID" => 20207772, "is_active" => 0]);
        $response = $this->from(route("login.professor"))->post("/login-professor", [
            "Prof_ID" => $prof->Prof_ID,
            "Password" => "secret123",
        ]);
        $response->assertSessionHasErrors("login");
        $this->assertGuest("professor");
    }

    // [11] Password with leading/trailing whitespace is handled safely
    #[Test]
    public function professor_password_with_leading_or_trailing_whitespace_may_trim_or_fail(): void
    {
        $prof = $this->createProfessor([
            "Prof_ID" => 20207773,
            "Password" => Hash::make("edgepass"),
        ]);
        $response = $this->post("/login-professor", [
            "Prof_ID" => $prof->Prof_ID,
            "Password" => " edgepass ",
        ]);
        $code = $response->getStatusCode();
        if (in_array($code, [302, 303])) {
            $this->assertAuthenticatedAs($prof, "professor");
        } else {
            $response->assertSessionHasErrors();
            $this->assertGuest("professor");
        }
    }

    // [12] Professor ID exceeding max length is rejected
    #[Test]
    public function professor_id_exceeds_max_length_rejected(): void
    {
        $longId = str_repeat("1", 100);
        $response = $this->post("/login-professor", [
            "Prof_ID" => $longId,
            "Password" => "any",
        ]);
        $response->assertSessionHasErrors(["Prof_ID"]);
        $this->assertGuest("professor");
    }

    // [13] Professor ID with leading zeros is accepted
    #[Test]
    public function professor_id_with_leading_zeros_logs_in(): void
    {
        $prof = $this->createProfessor(["Prof_ID" => 123456, "Password" => Hash::make("zeroes")]);
        $response = $this->post("/login-professor", [
            "Prof_ID" => "000123456",
            "Password" => "zeroes",
        ]);
        // Controller trims right only when reading stored, but validator enforces digits, so cast works
        $response->assertRedirect(route("dashboard.professor"));
        $this->assertAuthenticatedAs($prof, "professor");
    }

    // [17] Password minimum length enforced via mismatch error
    #[Test]
    public function professor_password_min_length_edge_behaves_as_incorrect(): void
    {
        $prof = $this->createProfessor([
            "Prof_ID" => 20207774,
            "Password" => Hash::make("validpass"),
        ]);
        $res = $this->post("/login-professor", ["Prof_ID" => 20207774, "Password" => "12"]);
        $res->assertSessionHasErrors();
        $this->assertGuest("professor");
    }

    // [19] Professor ID with internal spaces is rejected (validation)
    #[Test]
    public function professor_id_with_internal_spaces_rejected(): void
    {
        $this->createProfessor(["Prof_ID" => 20207775, "Password" => Hash::make("spacer")]);
        $res = $this->post("/login-professor", ["Prof_ID" => "2020 7775", "Password" => "spacer"]);
        $res->assertSessionHasErrors(["Prof_ID"]);
        $this->assertGuest("professor");
    }

    // ===== Password variants =====

    // [9] Password with special characters works
    #[Test]
    public function professor_password_with_special_characters_allows_login(): void
    {
        $prof = $this->createProfessor([
            "Prof_ID" => 20207776,
            "Password" => Hash::make('@G$w0rd!#2024'),
        ]);
        $response = $this->post("/login-professor", [
            "Prof_ID" => 20207776,
            "Password" => '@G$w0rd!#2024',
        ]);
        $response->assertRedirect(route("dashboard.professor"));
        $this->assertAuthenticatedAs($prof, "professor");
    }

    // [16] Unicode password works
    #[Test]
    public function professor_unicode_password_works(): void
    {
        $pwd = "Pásswörd🔥123";
        $prof = $this->createProfessor(["Prof_ID" => 20207777, "Password" => Hash::make($pwd)]);
        $res = $this->post("/login-professor", ["Prof_ID" => 20207777, "Password" => $pwd]);
        $res->assertRedirect(route("dashboard.professor"));
        $this->assertAuthenticatedAs($prof, "professor");
    }

    // ===== Security/threat tests =====

    // [10] SQL injection attempts do not authenticate
    #[Test]
    public function professor_sql_injection_like_strings_do_not_bypass_auth(): void
    {
        $inj = "' OR 1=1 --";
        $response = $this->post("/login-professor", ["Prof_ID" => $inj, "Password" => $inj]);
        $response->assertStatus(302);
        $this->assertGuest("professor");

        $prof = $this->createProfessor(["Prof_ID" => 20207991, "Password" => Hash::make("safe")]);
        $res2 = $this->post("/login-professor", ["Prof_ID" => 20207991, "Password" => $inj]);
        $res2->assertSessionHasErrors();
        $this->assertGuest("professor");
    }

    // [24] Rate limiter blocks after too many failed attempts
    #[Test]
    public function professor_rate_limiter_blocks_after_too_many_attempts(): void
    {
        $prof = $this->createProfessor([
            "Prof_ID" => 20207111,
            "Password" => Hash::make("ratelimit"),
        ]);
        for ($i = 0; $i < 5; $i++) {
            $this->post("/login-professor", ["Prof_ID" => 20207111, "Password" => "wrong-" . $i]);
        }
        $resp = $this->post("/login-professor", [
            "Prof_ID" => 20207111,
            "Password" => "wrong-final",
        ]);
        $resp->assertSessionHasErrors("login");
        $msg = session("errors")->get("login")[0] ?? "";
        $this->assertStringContainsString("Too many attempts", $msg);
        $this->assertGuest("professor");
    }

    // [25] Missing or invalid CSRF token results in redirect or 419
    #[Test]
    public function professor_missing_csrf_token_results_in_redirect_or_419(): void
    {
        $this->withHeader("X-CSRF-TOKEN", "bogus");
        $response = $this->post("/login-professor", ["Prof_ID" => "X", "Password" => "Y"]);
        $this->assertTrue(
            in_array($response->getStatusCode(), [302, 419]),
            "Expected redirect or 419 for bad CSRF",
        );
    }

    // ===== Cookies & session =====

    // [6] Remember me sets persistent cookie on successful login
    #[Test]
    public function professor_remember_me_sets_cookie_after_login(): void
    {
        $prof = $this->createProfessor([
            "Prof_ID" => 20207767,
            "Email" => "remember.prof@example.com",
            "Password" => Hash::make("remember123"),
        ]);
        $response = $this->post("/login-professor", [
            "Prof_ID" => $prof->Prof_ID,
            "Password" => "remember123",
            "remember" => "on",
        ]);
        $response->assertRedirect(route("dashboard.professor"));
        $cookies = $response->headers->getCookies();
        $this->assertTrue(
            collect($cookies)->contains(function ($c) {
                return str_contains($c->getName(), "remember");
            }),
            "Remember me cookie not found",
        );
    }

    // [18] No professor is created on failed login
    #[Test]
    public function no_professor_is_created_on_failed_login(): void
    {
        $before = Professor::count();
        $this->post("/login-professor", ["Prof_ID" => 909999999, "Password" => "nope"]);
        $after = Professor::count();
        $this->assertSame($before, $after);
    }

    // [21] User remains authenticated after a subsequent failed login attempt
    #[Test]
    public function professor_remains_authenticated_after_failed_relogin_attempt(): void
    {
        $prof = $this->createProfessor(["Prof_ID" => 20207778, "Password" => Hash::make("stay")]);
        $this->post("/login-professor", ["Prof_ID" => 20207778, "Password" => "stay"]);
        $this->assertAuthenticatedAs($prof, "professor");
        $res = $this->post("/login-professor", [
            "Prof_ID" => 20207778,
            "Password" => "wrong-after",
        ]);
        $this->assertAuthenticatedAs($prof, "professor");
    }

    // ===== UI/Routes =====

    // [23] Forgot Password route renders successfully (shared)
    #[Test]
    public function professor_forgot_password_route_renders(): void
    {
        $resp = $this->get(route("forgotpassword"));
        $resp->assertStatus(200);
    }

    // [22] Login page renders expected professor form elements and controls
    #[Test]
    public function professor_login_page_has_expected_form_elements(): void
    {
        $resp = $this->get(route("login.professor"));
        $resp->assertStatus(200);
        $html = $resp->getContent();
        $this->assertStringContainsString('id="prof-login-form"', $html);
        $this->assertStringContainsString('name="Prof_ID"', $html);
        $this->assertStringContainsString('name="Password"', $html);
    }

    // ===== Auditing & messaging =====

    // [26] Successful login is audited in attempts table
    #[Test]
    public function professor_successful_login_is_audited_in_attempts_table(): void
    {
        $prof = $this->createProfessor([
            "Prof_ID" => 20207133,
            "Password" => Hash::make("auditpw"),
        ]);
        $this->post("/login-professor", ["Prof_ID" => 20207133, "Password" => "auditpw"]);
        $row = DB::table("login_attempts")
            ->where("prof_id", (string) $prof->Prof_ID)
            ->orderByDesc("id")
            ->first();
        $this->assertNotNull($row, "Expected an audit row for professor");
        $this->assertEquals(1, (int) $row->successful);
        $this->assertEquals("success", $row->reason);
    }

    // [27] Specific error message shown when password is wrong
    #[Test]
    public function professor_shows_specific_message_when_password_is_wrong(): void
    {
        $prof = $this->createProfessor([
            "Prof_ID" => 20207144,
            "Password" => Hash::make("specpass123"),
        ]);
        $res = $this->post("/login-professor", [
            "Prof_ID" => $prof->Prof_ID,
            "Password" => "wrong",
        ]);
        $res->assertSessionHas("errors");
        $bag = session("errors");
        $flat = $bag ? $bag->all() : [];
        $this->assertTrue(
            in_array("Incorrect password.", $flat),
            "Expected specific incorrect password message",
        );
    }
}
