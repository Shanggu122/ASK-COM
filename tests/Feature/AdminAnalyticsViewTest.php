<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminAnalyticsViewTest extends TestCase
{
    public function test_admin_analytics_page_has_expected_elements(): void
    {
        // Bypass admin auth and any other middleware so we can render the view directly
        $this->withoutMiddleware();

        // Use the named route defined in routes/web.php (path: /admin-analytics)
        $response = $this->get(route('admin.analytics'));

        $response->assertOk();

        // Page title exists in <title>
        $response->assertSee('Admin Analytics');

        // Section headers present in the Blade
        $response->assertSee('Consultation Activity');
        $response->assertSee('Peak Consultation Days');

        // Canvas elements and legend container
        $response->assertSee('id="topicsChart"', false);
        $response->assertSee('id="activityChart"', false);
        $response->assertSee('id="peakDaysChart"', false);

        // CSRF meta and Chart.js script URL
        $response->assertSee('name="csrf-token"', false);
        $response->assertSee('https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', false);
    }
}
