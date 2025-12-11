<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;
use PHPUnit\Framework\Attributes\Test;

class AdminLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure admin table exists (minimal schema)
        if (!Schema::hasTable('admin')) {
            Schema::create('admin', function (Blueprint $table) {
                $table->string('Admin_ID', 9)->primary();
                $table->string('Name', 100)->nullable();
                $table->string('Email', 150)->nullable();
                $table->string('Password', 255);
                $table->string('remember_token', 100)->nullable();
                $table->string('profile_picture', 255)->nullable();
                $table->boolean('is_active')->default(1);
            });
        } else {
            Schema::table('admin', function (Blueprint $table) {
                if (!Schema::hasColumn('admin', 'remember_token')) {
                    $table->string('remember_token', 100)->nullable();
                }
                if (!Schema::hasColumn('admin', 'is_active')) {
                    $table->boolean('is_active')->default(1);
                }
            });
        }

        // Ensure audit table exists and supports admin ids
        if (!Schema::hasTable('login_attempts')) {
            Schema::create('login_attempts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('stud_id', 32)->nullable();
                $table->string('prof_id', 32)->nullable();
                $table->string('admin_id', 32)->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->boolean('successful')->default(false);
                $table->string('reason', 64)->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('login_attempts', function (Blueprint $table) {
                if (!Schema::hasColumn('login_attempts', 'admin_id')) {
                    $table->string('admin_id', 32)->nullable()->after('prof_id');
                }
            });
        }
    }

    protected function createAdmin(array $overrides = []): Admin
    {
        $data = array_merge([
            'Admin_ID' => 'A0000001',
            'Name' => 'Admin User',
            'Email' => 'admin@example.com',
            'Password' => Hash::make('secret123'),
        ], $overrides);
        return Admin::create($data);
    }

    // ===== Core login flow =====

    // [1] Valid login redirects and grants dashboard access
    #[Test]
    public function admin_can_login_with_valid_credentials_and_redirects_to_dashboard(): void
    {
        $admin = $this->createAdmin();
        $response = $this->post(route('login.admin.submit'), [
            'Admin_ID' => $admin->Admin_ID,
            'Password' => 'secret123',
        ]);
        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('admin.dashboard'))->assertOk();
    }

    // [5] Guest is redirected to login when accessing dashboard
    #[Test]
    public function guest_is_redirected_to_admin_login_when_accessing_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login.admin'));
        $this->assertGuest('admin');
    }

    // [7] Session ID regenerates on successful login
    #[Test]
    public function session_is_regenerated_on_successful_admin_login(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00002', 'Email' => 'sessadmin@example.com', 'Password' => Hash::make('sessionpass')]);
        $oldId = session()->getId();
        $this->post(route('login.admin.submit'), ['Admin_ID' => $admin->Admin_ID, 'Password' => 'sessionpass']);
        $newId = session()->getId();
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotEquals($oldId, $newId, 'Session ID should change after login');
    }

    // [14] Already-authenticated visit to login is handled (no re-login; allowed or redirected)
    #[Test]
    public function admin_already_authenticated_redirects_away_from_login(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00003', 'Password' => Hash::make('go')]);
        $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00003', 'Password' => 'go']);
        $this->assertAuthenticatedAs($admin, 'admin');
        $res = $this->get(route('login.admin'));
        $this->assertTrue(in_array($res->getStatusCode(), [200, 302, 303]));
    }

    // [15] Intended URL after login redirects to dashboard
    #[Test]
    public function admin_intended_redirect_after_login_goes_to_dashboard(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00004', 'Password' => Hash::make('intend')]);
        session(['url.intended' => route('admin.dashboard')]);
        $res = $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00004', 'Password' => 'intend']);
        $res->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    // [20] Logout invalidates session; dashboard access is blocked afterward
    #[Test]
    public function admin_logout_then_cannot_access_dashboard(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00005', 'Password' => Hash::make('bye')]);
        $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00005', 'Password' => 'bye']);
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->post(route('logout.admin'));
        session()->invalidate();
        session()->regenerateToken();
        $res = $this->get(route('admin.dashboard'));
        $res->assertRedirect(route('login.admin'));
    }

    // ===== Credentials & validation =====

    // [2] Wrong password denies login and logs failed attempt
    #[Test]
    public function admin_cannot_login_with_wrong_password(): void
    {
        Log::spy();
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00100']);
        $response = $this->post(route('login.admin.submit'), [
            'Admin_ID' => $admin->Admin_ID,
            'Password' => 'wrongpass',
        ]);
        $response->assertSessionHasErrors();
        $this->assertGuest('admin');
        Log::shouldHaveReceived('notice')->atLeast()->once();
        $this->assertGreaterThan(0, DB::table('login_attempts')->where('admin_id', $admin->Admin_ID)->where('successful', 0)->count());
    }

    // [3] Invalid admin ID shows “ID not found” message
    #[Test]
    public function admin_cannot_login_with_invalid_id(): void
    {
        $this->createAdmin(['Admin_ID' => 'ADM00101']);
        $response = $this->post(route('login.admin.submit'), [
            'Admin_ID' => 'ADM99999',
            'Password' => 'secret123',
        ]);
        $response->assertSessionHas('errors');
        $bag = session('errors');
        $flat = $bag ? $bag->all() : [];
        $this->assertTrue(in_array('Admin ID does not exist.', $flat));
        $this->assertGuest('admin');
    }

    // [4] Empty or missing fields return validation errors for Admin_ID and Password
    #[Test]
    public function admin_cannot_login_with_empty_fields(): void
    {
        $response = $this->post(route('login.admin.submit'), [
            'Admin_ID' => '',
            'Password' => '',
        ]);
        $response->assertSessionHasErrors(['Admin_ID', 'Password']);
        $this->assertGuest('admin');
    }

    // [8] Inactive or locked account cannot login (optional column)
    #[Test]
    public function inactive_admin_account_cannot_login(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM77777', 'Password' => Hash::make('secret123'), 'Email' => 'inact@example.com']);
        // ensure set to inactive
        DB::table('admin')->where('Admin_ID', $admin->Admin_ID)->update(['is_active' => 0]);
        $response = $this->from(route('login.admin'))
            ->post(route('login.admin.submit'), ['Admin_ID' => $admin->Admin_ID, 'Password' => 'secret123']);
        $response->assertSessionHasErrors('login');
        $this->assertGuest('admin');
    }

    // [11] Password with leading/trailing whitespace is trimmed and still authenticates (parity)
    #[Test]
    public function admin_password_with_leading_or_trailing_whitespace_is_trimmed_and_authenticates(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00102', 'Password' => Hash::make('edgepass')]);
        $response = $this->post(route('login.admin.submit'), [
            'Admin_ID' => 'ADM00102',
            'Password' => ' edgepass ',
        ]);
        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    // [12] Admin ID exceeding max length is rejected
    #[Test]
    public function admin_id_exceeds_max_length_rejected(): void
    {
        $longId = str_repeat('A', 100);
        $response = $this->post(route('login.admin.submit'), [
            'Admin_ID' => $longId,
            'Password' => 'any',
        ]);
        $response->assertSessionHasErrors(['Admin_ID']);
        $this->assertGuest('admin');
    }

    // [19] Admin ID with internal spaces is rejected (validation)
    #[Test]
    public function admin_id_with_internal_spaces_rejected(): void
    {
        $this->createAdmin(['Admin_ID' => 'ADM20123', 'Password' => Hash::make('spacer')]);
        $res = $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM 20123', 'Password' => 'spacer']);
        $res->assertSessionHasErrors(['Admin_ID']);
        $this->assertGuest('admin');
    }

    // [17] Password minimum length is enforced (treated as incorrect)
    #[Test]
    public function admin_password_min_length_enforced_on_validation(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00103', 'Password' => Hash::make('validpass')]);
        $res = $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00103', 'Password' => '12']);
        $res->assertSessionHasErrors();
        $this->assertGuest('admin');
    }

    // [26] Admin ID with special characters is rejected (alphanumeric only)
    #[Test]
    public function admin_id_with_special_characters_rejected(): void
    {
        $res = $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM#001', 'Password' => 'any']);
        $res->assertSessionHasErrors(['Admin_ID']);
        $this->assertGuest('admin');
    }

    // ===== Password variants =====

    // [9] Password with special characters works
    #[Test]
    public function admin_password_with_special_characters_allows_login(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00104', 'Password' => Hash::make('@G$w0rd!#2024')]);
        $response = $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00104', 'Password' => '@G$w0rd!#2024']);
        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    // [16] Unicode password works
    #[Test]
    public function admin_unicode_password_works(): void
    {
        $pwd = "Pásswörd🔥123";
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00105', 'Password' => Hash::make($pwd)]);
        $res = $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00105', 'Password' => $pwd]);
        $res->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    // ===== Security/threat tests =====

    // [10] SQL injection attempts (fields or password) do not authenticate
    #[Test]
    public function admin_sql_injection_like_strings_do_not_bypass_auth(): void
    {
        $inj = "' OR 1=1 --";
        $response = $this->post(route('login.admin.submit'), ['Admin_ID' => $inj, 'Password' => $inj]);
        $response->assertStatus(302);
        $this->assertGuest('admin');

        $admin = $this->createAdmin(['Admin_ID' => 'ADM00991', 'Password' => Hash::make('safe')]);
        $res2 = $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00991', 'Password' => $inj]);
        $res2->assertSessionHasErrors();
        $this->assertGuest('admin');
    }

    // [24] Rate limiter blocks after too many failed attempts
    #[Test]
    public function admin_rate_limiter_blocks_after_too_many_attempts(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00106', 'Password' => Hash::make('ratelimit')]);
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00106', 'Password' => 'wrong-'.$i]);
        }
        $resp = $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00106', 'Password' => 'wrong-final']);
        $resp->assertSessionHasErrors('login');
        $msg = session('errors')->get('login')[0] ?? '';
        $this->assertStringContainsString('Too many attempts', $msg);
        $this->assertGuest('admin');
    }

    // [25] Missing or invalid CSRF token results in redirect or 419
    #[Test]
    public function admin_missing_csrf_token_results_in_redirect_or_419(): void
    {
        $this->withHeader('X-CSRF-TOKEN','bogus');
        $response = $this->post(route('login.admin.submit'), ['Admin_ID' => 'X', 'Password' => 'Y']);
        $this->assertTrue(in_array($response->getStatusCode(), [302, 419]));
    }

    // ===== Cookies & session =====

    // [6] Remember me sets persistent cookie on successful login
    #[Test]
    public function admin_remember_me_sets_cookie_after_login(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00107', 'Email' => 'remember.admin@example.com', 'Password' => Hash::make('remember123')]);
        $response = $this->post(route('login.admin.submit'), [
            'Admin_ID' => $admin->Admin_ID,
            'Password' => 'remember123',
            'remember' => 'on',
        ]);
        $response->assertRedirect(route('admin.dashboard'));
        $cookies = $response->headers->getCookies();
        $this->assertTrue(collect($cookies)->contains(function($c){ return str_contains($c->getName(), 'remember'); }));
    }

    // [27] Successful login is audited in login_attempts
    #[Test]
    public function admin_success_login_is_audited(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00888', 'Password' => Hash::make('okpass')]);
        $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00888', 'Password' => 'okpass']);
        $this->assertGreaterThan(0, DB::table('login_attempts')->where('admin_id', 'ADM00888')->where('successful', 1)->count());
    }

    // [18] No admin is created on failed login
    #[Test]
    public function no_admin_is_created_on_failed_login(): void
    {
        $before = Admin::count();
        $this->post(route('login.admin.submit'), ['Admin_ID' => 'X999', 'Password' => 'nope']);
        $after = Admin::count();
        $this->assertSame($before, $after);
    }

    // [21] User remains authenticated after a subsequent failed login attempt
    #[Test]
    public function admin_remains_authenticated_after_failed_relogin_attempt(): void
    {
        $admin = $this->createAdmin(['Admin_ID' => 'ADM00108', 'Password' => Hash::make('stay')]);
        $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00108', 'Password' => 'stay']);
        $this->assertAuthenticatedAs($admin, 'admin');
        $res = $this->post(route('login.admin.submit'), ['Admin_ID' => 'ADM00108', 'Password' => 'wrong-after']);
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    // ===== UI/Routes =====

    // [23] Forgot Password route renders successfully (shared)
    #[Test]
    public function admin_forgot_password_route_renders(): void
    {
        $resp = $this->get(route('forgotpassword'));
        $resp->assertStatus(200);
    }

    // [22] Login page renders expected form elements and controls
    #[Test]
    public function admin_login_page_has_expected_form_elements(): void
    {
        $resp = $this->get(route('login.admin'));
        $resp->assertStatus(200);
        $html = $resp->getContent();
        $this->assertStringContainsString('id="admin-login-form"', $html);
        $this->assertStringContainsString('name="Admin_ID"', $html);
        $this->assertStringContainsString('name="Password"', $html);
    }
}
