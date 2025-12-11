<?php

namespace Tests\Feature;

use App\Models\Admin;
use Tests\TestCase;

class AdminSidebarNavigationTest extends TestCase
{
    public function test_unauthenticated_admin_is_redirected_from_protected_pages(): void
    {
        // Do not bypass middleware here; we want to assert redirect behavior

        $protected = [route("admin.dashboard"), route("admin.comsci"), route("admin.analytics")];

        foreach ($protected as $url) {
            $resp = $this->get($url);
            $resp->assertRedirect("/login/admin");
        }
    }

    public function test_authenticated_admin_can_visit_sidebar_pages(): void
    {
        // Avoid database dependency: use an in-memory Admin instance
        $admin = new Admin([
            "Admin_ID" => "ADM-TEST",
            "Name" => "Test Admin",
            "Email" => "admin@test.local",
            "Password" => bcrypt("secret123"),
            "profile_picture" => null,
        ]);
        // Mark as existing model for guard serialization
        $admin->exists = true;
        $this->actingAs($admin, "admin");

        // Verify safe, DB-light pages load (status 200)
        $this->get(route("admin.dashboard"))->assertOk();
        $this->get(route("admin.analytics"))->assertOk();
    }

    public function test_sidebar_links_have_correct_urls_for_admin(): void
    {
        // Render a page that includes the admin navbar and inspect hrefs
        $admin = new Admin([
            "Admin_ID" => "ADM-TEST",
            "Name" => "Test Admin",
            "Email" => "admin@test.local",
            "Password" => bcrypt("secret123"),
        ]);
        $admin->exists = true;
        $this->actingAs($admin, "admin");

        $resp = $this->get(route("admin.dashboard"));
        $resp->assertOk();

        // Assert sidebar links point to expected routes
        $resp->assertSee('href="' . route("admin.dashboard") . '"', false);
        $resp->assertSee('href="' . url("/admin-comsci") . '"', false);
        $resp->assertSee('href="' . url("/admin-analytics") . '"', false);
        $resp->assertSee('action="' . route("logout.admin") . '"', false);
    }
}
