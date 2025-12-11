<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Professor;
use App\Events\ProfessorAdded;
use App\Events\ProfessorUpdated;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

class ProfessorEventsProfilePhotoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!Schema::hasTable("professors")) {
            Schema::create("professors", function (Blueprint $table) {
                $table->integer("Prof_ID")->primary();
                $table->string("Name")->nullable();
                $table->string("Dept_ID")->nullable();
                $table->string("Email")->nullable();
                $table->string("Password")->nullable();
                $table->string("Schedule")->nullable();
                $table->string("profile_picture")->nullable();
                $table->boolean("is_active")->default(1);
                $table->rememberToken();
            });
        }
    }

    protected function makeProfessor(array $overrides = []): Professor
    {
        $data = array_merge(
            [
                "Prof_ID" => 991122,
                "Name" => "Prof Broadcast",
                "Dept_ID" => "1",
                "Email" => "pusher@example.com",
                "Password" => "secret",
                "Schedule" => "MWF",
                "profile_picture" => null,
            ],
            $overrides,
        );
        return Professor::create($data);
    }

    #[Test]
    public function added_event_payload_includes_profile_photo_url_even_when_null(): void
    {
        $prof = $this->makeProfessor(["profile_picture" => null]);
        $event = new ProfessorAdded($prof);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey("profile_photo_url", $payload);
        $this->assertIsString($payload["profile_photo_url"]);
        $this->assertStringContainsString("images/dprof.jpg", $payload["profile_photo_url"]);
    }

    #[Test]
    public function updated_event_payload_uses_storage_url_when_file_exists(): void
    {
        Storage::fake("public");
        $path = "profile_pictures/abc.png";
        Storage::disk("public")->put($path, "123");

        $prof = $this->makeProfessor(["profile_picture" => $path]);
        $event = new ProfessorUpdated($prof);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey("profile_photo_url", $payload);
        $url = $payload["profile_photo_url"];
        $this->assertIsString($url);
        $this->assertTrue(str_starts_with($url, "/storage") || str_contains($url, "http"));
    }
}
