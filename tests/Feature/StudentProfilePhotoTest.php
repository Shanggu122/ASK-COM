<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;

class StudentProfilePhotoTest extends TestCase
{
    private function createTinyPngUploadedFile(string $name = "avatar.png"): UploadedFile
    {
        // 1x1 transparent PNG
        $pngBase64 =
            "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9YJp8lcAAAAASUVORK5CYII=";
        $tmp = tempnam(sys_get_temp_dir(), "png");
        file_put_contents($tmp, base64_decode($pngBase64));
        return new UploadedFile($tmp, $name, "image/png", null, true);
    }
    protected function setUp(): void
    {
        parent::setUp();
        if (!Schema::hasTable("t_student")) {
            Schema::create("t_student", function (Blueprint $table) {
                $table->string("Stud_ID", 9)->primary();
                $table->string("Name")->nullable();
                $table->string("Dept_ID")->nullable();
                $table->string("Email")->nullable();
                $table->string("Password")->nullable();
                $table->string("profile_picture")->nullable();
                $table->boolean("is_active")->default(1);
                $table->rememberToken();
            });
        }
    }

    protected function createStudent(array $overrides = []): User
    {
        $data = array_merge(
            [
                "Stud_ID" => "202488888",
                "Name" => "Photo Student",
                "Dept_ID" => "CS",
                "Email" => "photostud@example.com",
                "Password" => Hash::make("secret123"),
                "profile_picture" => null,
            ],
            $overrides,
        );

        return User::create($data);
    }

    #[Test]
    public function upload_stores_relative_path_and_url_resolves(): void
    {
        Storage::fake("public");
        $student = $this->createStudent();
        $this->be($student);

        $file = $this->createTinyPngUploadedFile("avatar.png");

        $res = $this->post(route("profile.uploadPicture"), [
            "profile_picture" => $file,
        ]);

        $res->assertSessionHas("status");
        $student->refresh();

        // Stored path should be relative like "profile_pictures/xyz.jpg"
        $this->assertNotNull($student->profile_picture);
        $this->assertStringStartsWith("profile_pictures/", $student->profile_picture);

        // File exists on disk
        $this->assertTrue(Storage::disk("public")->exists($student->profile_picture));

        // Accessor should give a web URL (starts with /storage or http)
        $url = $student->profile_photo_url;
        $this->assertIsString($url);
        $this->assertTrue(str_starts_with($url, "/storage") || str_contains($url, "http"));
    }

    #[Test]
    public function delete_clears_path_and_removes_file(): void
    {
        Storage::fake("public");
        $student = $this->createStudent();
        $this->be($student);

        // Seed an uploaded file
        $file = $this->createTinyPngUploadedFile("avatar2.png");
        $path = Storage::disk("public")->put(
            "profile_pictures/avatar2.png",
            file_get_contents($file->getRealPath()),
        )
            ? "profile_pictures/avatar2.png"
            : null;
        $student->forceFill(["profile_picture" => $path])->save();

        $this->assertTrue(Storage::disk("public")->exists($path));

        $res = $this->post(route("profile.deletePicture"));
        $res->assertJson(["success" => true]);

        $student->refresh();
        $this->assertNull($student->profile_picture);
        $this->assertFalse(Storage::disk("public")->exists($path));
    }
}
