<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\Professor;
use PHPUnit\Framework\Attributes\Test;

class ProfessorProfilePhotoTest extends TestCase
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
                $table->string("profile_picture")->nullable();
                $table->boolean("is_active")->default(1);
                $table->rememberToken();
            });
        }
    }

    private function createTinyPngUploadedFile(string $name = "avatar.png"): UploadedFile
    {
        $pngBase64 =
            "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9YJp8lcAAAAASUVORK5CYII=";
        $tmp = tempnam(sys_get_temp_dir(), "png");
        file_put_contents($tmp, base64_decode($pngBase64));
        return new UploadedFile($tmp, $name, "image/png", null, true);
    }

    protected function createProfessor(array $overrides = []): Professor
    {
        $data = array_merge(
            [
                "Prof_ID" => 112233,
                "Name" => "Prof Uno",
                "Dept_ID" => "IT",
                "Email" => "prof@example.com",
                "Password" => "secret123",
                "profile_picture" => null,
            ],
            $overrides,
        );

        return Professor::create($data);
    }

    #[Test]
    public function upload_stores_relative_path_and_url_resolves(): void
    {
        Storage::fake("public");
        $prof = $this->createProfessor();
        $this->be($prof, "professor");

        $file = $this->createTinyPngUploadedFile("avatar.png");

        $res = $this->post(route("profile.uploadPicture.professor"), [
            "profile_picture" => $file,
        ]);

        $res->assertSessionHas("status");
        $prof->refresh();

        $this->assertNotNull($prof->profile_picture);
        $this->assertStringStartsWith("profile_pictures/", $prof->profile_picture);
        $this->assertTrue(Storage::disk("public")->exists($prof->profile_picture));

        $url = $prof->profile_photo_url;
        $this->assertIsString($url);
        $this->assertTrue(str_starts_with($url, "/storage") || str_contains($url, "http"));
    }

    #[Test]
    public function delete_clears_path_and_removes_file(): void
    {
        Storage::fake("public");
        $prof = $this->createProfessor();
        $this->be($prof, "professor");

        $file = $this->createTinyPngUploadedFile("avatar2.png");
        $path = Storage::disk("public")->put(
            "profile_pictures/avatar2.png",
            file_get_contents($file->getRealPath()),
        )
            ? "profile_pictures/avatar2.png"
            : null;
        $prof->forceFill(["profile_picture" => $path])->save();

        $this->assertTrue(Storage::disk("public")->exists($path));

        $res = $this->post(route("profile.deletePicture.professor"));
        $res->assertJson(["success" => true]);

        $prof->refresh();
        $this->assertNull($prof->profile_picture);
        $this->assertFalse(Storage::disk("public")->exists($path));
    }
}
