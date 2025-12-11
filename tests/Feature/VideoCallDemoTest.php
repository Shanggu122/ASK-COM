<?php

namespace Tests\Feature;

use Tests\TestCase;

class VideoCallDemoTest extends TestCase
{
    public function test_demo_route_redirects_with_defaults()
    {
        if (!config("app.debug")) {
            $this->markTestSkipped("Demo route only registered in debug mode.");
        }

        $response = $this->get("/dev/video-call-demo");

        $response->assertRedirect();
        $location = $response->headers->get("Location");
        $this->assertIsString($location);
        $this->assertStringContainsString("/dev/video-call-demo?", $location);
        $this->assertStringContainsString("mock=", $location);
    }

    public function test_demo_route_renders_mock_view()
    {
        if (!config("app.debug")) {
            $this->markTestSkipped("Demo route only registered in debug mode.");
        }

        $response = $this->get("/dev/video-call-demo?mock=2&mockNames=Alpha|Bravo");

        $response->assertStatus(200);
        $response->assertSee("Meeting —", false);
        $response->assertSee("Waiting for others to join", false);
    }
}
