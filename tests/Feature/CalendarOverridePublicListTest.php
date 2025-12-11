<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CalendarOverridePublicListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable("calendar_overrides")) {
            Schema::create("calendar_overrides", function (Blueprint $table) {
                $table->bigIncrements("id");
                $table->date("start_date");
                $table->date("end_date");
                $table->string("scope_type", 32)->default("all");
                $table->string("scope_id", 64)->nullable();
                $table->string("effect", 16);
                $table->string("allowed_mode", 16)->nullable();
                $table->string("reason_key", 64)->nullable();
                $table->string("reason_text", 255)->nullable();
                $table->string("created_by", 64)->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_public_list_returns_holiday_keyed_by_date_string(): void
    {
        // Arrange: insert a global holiday on 2025-12-25
        DB::table("calendar_overrides")->insert([
            "start_date" => "2025-12-25",
            "end_date" => "2025-12-25",
            "scope_type" => "all",
            "scope_id" => null,
            "effect" => "holiday",
            "allowed_mode" => null,
            "reason_key" => "holiday",
            "reason_text" => "Christmas",
            "created_by" => "TEST",
            "created_at" => now(),
            "updated_at" => now(),
        ]);

        // Act
        $res = $this->getJson("/api/calendar/overrides?start_date=2025-12-01&end_date=2025-12-31");
        $res->assertOk();
        $json = $res->json();

        // Assert
        $this->assertTrue(($json["success"] ?? false) === true, "success flag should be true");
        $key = Carbon::parse("2025-12-25")->format("Y-m-d");
        $this->assertArrayHasKey($key, $json["overrides"], "Date key should exist");
        $items = $json["overrides"][$key];
        $this->assertNotEmpty($items, "Overrides array for date should not be empty");
        $this->assertEquals("holiday", $items[0]["effect"]);
        $this->assertEquals("Christmas", $items[0]["reason_text"]);
        $this->assertEquals("Christmas", $items[0]["label"]);
    }
}
