<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RescheduleModeLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Migrate default Laravel tables and any project-specific ones
        $this->artisan("migrate");
    }

    /**
     * Helper to insert a booking row quickly.
     */
    private function createBooking($profId, $date, $mode, $status)
    {
        return DB::table("t_consultation_bookings")->insertGetId([
            "Prof_ID" => $profId,
            "Stud_ID" => 1,
            "Booking_Date" => $date,
            "Mode" => $mode,
            "Status" => $status,
            "Created_At" => now(),
            "Updated_At" => now(),
        ]);
    }

    public function test_reschedule_to_wrong_mode_is_blocked()
    {
        $profId = 1;
        // Day A has mode lock 'online' (earliest approved on that date)
        $dayA = now()->addDays(2)->startOfDay()->format("D M d Y");
        $this->createBooking($profId, $dayA, "online", "approved");

        // Existing booking to reschedule has mode 'onsite'
        $origDate = now()->addDay()->startOfDay()->format("D M d Y");
        $bookingId = $this->createBooking($profId, $origDate, "onsite", "approved");

        $resp = $this->postJson("/api/consultations/update-status", [
            "id" => $bookingId,
            "status" => "rescheduled",
            "new_date" => $dayA,
            "reschedule_reason" => "Test move",
        ]);

        $resp->assertOk()->assertJson([
            "success" => false,
        ]);
        $resp->assertJsonFragment(["Cannot reschedule: the date is locked to Online mode."]);
    }

    public function test_reschedule_to_same_mode_succeeds()
    {
        $profId = 1;
        // Day B lock to onsite
        $dayB = now()->addDays(3)->startOfDay()->format("D M d Y");
        $this->createBooking($profId, $dayB, "onsite", "approved");

        // Existing booking mode 'onsite'
        $origDate = now()->addDay()->startOfDay()->format("D M d Y");
        $bookingId = $this->createBooking($profId, $origDate, "onsite", "approved");

        $resp = $this->postJson("/api/consultations/update-status", [
            "id" => $bookingId,
            "status" => "rescheduled",
            "new_date" => $dayB,
            "reschedule_reason" => "Test move",
        ]);

        $resp->assertOk()->assertJson([
            "success" => true,
        ]);
    }
}
