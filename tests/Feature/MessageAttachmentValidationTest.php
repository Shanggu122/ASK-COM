<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Tests\TestCase;

class MessageAttachmentValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Use fake storage to avoid real file writes
        Storage::fake("public");
        // Silence broadcasts in tests
        Event::fake();
        // Disable CSRF middleware for posting in tests
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        // Authenticate as a student user (web guard)
        $user = User::create([
            "Stud_ID" => "S0000001",
            "Name" => "Test Student",
            "Dept_ID" => "1",
            "Email" => "stud@example.com",
            "Password" => Hash::make("password"),
        ]);
        $this->be($user, "web");
    }

    public function test_rejects_disallowed_mime_single_file()
    {
        $file = UploadedFile::fake()->create("clip.mp4", 1000, "video/mp4");
        $payload = [
            "sender" => "student",
            "recipient" => "prof",
            "stud_id" => 123,
            "prof_id" => 456,
            "file" => $file,
        ];

        $res = $this->post("/send-message", $payload);

        $res->assertStatus(422)->assertJsonFragment(["status" => "Invalid attachment"]);
    }

    public function test_rejects_file_over_25mb()
    {
        // Size in kilobytes; > 25600 KB triggers validation fail
        $file = UploadedFile::fake()->create("big.pdf", 26000, "application/pdf");
        $payload = [
            "sender" => "student",
            "recipient" => "prof",
            "stud_id" => 1,
            "prof_id" => 2,
            "file" => $file,
        ];

        $res = $this->post("/send-message", $payload);
        $res->assertStatus(422)->assertJsonFragment(["status" => "Invalid attachment"]);
    }

    public function test_accepts_allowed_doc_under_25mb_and_stores()
    {
        $file = UploadedFile::fake()->create(
            "ok.docx",
            1024,
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        );
        $payload = [
            "sender" => "student",
            "recipient" => "prof",
            "stud_id" => 10,
            "prof_id" => 20,
            "file" => $file,
            "message" => "",
        ];

        $res = $this->post("/send-message", $payload);
        $res->assertStatus(200)->assertJson(["status" => "Message sent!"]);

        // Ensure DB row created with a stored file path
        $exists = \Illuminate\Support\Facades\DB::table("t_chat_messages")
            ->where("Stud_ID", 10)
            ->where("Prof_ID", 20)
            ->whereNotNull("file_path")
            ->exists();
        $this->assertTrue($exists, "Expected a chat message row with file_path to be created");
    }

    public function test_rejects_when_any_file_in_array_is_invalid()
    {
        $good = UploadedFile::fake()->create("ok.pdf", 1000, "application/pdf");
        $bad = UploadedFile::fake()->create("song.mp3", 500, "audio/mpeg");
        $payload = [
            "sender" => "professor",
            "recipient" => "student",
            "stud_id" => 77,
            "prof_id" => 88,
            "files" => [$good, $bad],
            "message" => "",
        ];

        $res = $this->post("/send-message", $payload);
        $res->assertStatus(422)->assertJsonFragment(["status" => "Invalid attachment"]);
    }
}
