<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoMeetingSeeder extends Seeder
{
    public function run(): void
    {
        app(DemoStudentSeeder::class)->run();

        $profTable = null;
        if (Schema::hasTable("professors")) {
            $profTable = "professors";
        } elseif (Schema::hasTable("t_professor")) {
            $profTable = "t_professor";
        }

        if (!$profTable) {
            return;
        }

        $profColumns = Schema::getColumnListing($profTable);
        $hasProf = fn(string $col): bool => in_array($col, $profColumns, true);
        $profId = 3001;

        $profPayload = ["Prof_ID" => $profId];
        if ($hasProf("Name")) {
            $profPayload["Name"] = "Demo Professor";
        }
        if ($hasProf("Dept_ID")) {
            $profPayload["Dept_ID"] = 1;
        }
        if ($hasProf("Email")) {
            $profPayload["Email"] = "demo.prof@example.com";
        }
        if ($hasProf("Password")) {
            $profPayload["Password"] = Hash::make("demo1234");
        }
        if ($hasProf("Schedule")) {
            $profPayload["Schedule"] = "08:00-17:00";
        }
        if ($hasProf("is_active")) {
            $profPayload["is_active"] = 1;
        }
        if ($hasProf("remember_token")) {
            $profPayload["remember_token"] = null;
        }
        if ($hasProf("Created_At")) {
            $profPayload["Created_At"] = now();
        }
        if ($hasProf("Updated_At")) {
            $profPayload["Updated_At"] = now();
        }

        DB::table($profTable)->updateOrInsert(["Prof_ID" => $profId], $profPayload);

        if (!Schema::hasTable("t_consultation_bookings")) {
            return;
        }

        $bookingColumns = Schema::getColumnListing("t_consultation_bookings");
        $resolveBooking = function (array $candidates) use ($bookingColumns): ?string {
            foreach ($candidates as $candidate) {
                if (in_array($candidate, $bookingColumns, true)) {
                    return $candidate;
                }
            }
            return null;
        };
        $profIdCol = $resolveBooking(["Prof_ID", "prof_id"]);
        $studIdCol = $resolveBooking(["Stud_ID", "stud_id"]);
        $bookingDateCol = $resolveBooking(["Booking_Date", "booking_date"]);
        $bookingTimeCol = $resolveBooking(["Booking_Time", "booking_time"]);
        $modeCol = $resolveBooking(["Mode", "mode"]);
        $statusCol = $resolveBooking(["Status", "status"]);
        $subjectCol = $resolveBooking(["Subject_ID", "subject_id"]);
        $consultTypeIdCol = $resolveBooking(["Consult_type_ID", "consult_type_id"]);
        $consultationTypeCol = $resolveBooking(["Consultation_Type", "consultation_type"]);
        $meetingLinkCol = $resolveBooking(["Meeting_Link", "meeting_link"]);
        $createdAtCol = $resolveBooking(["Created_At", "created_at"]);
        $updatedAtCol = $resolveBooking(["Updated_At", "updated_at"]);
        $reschedCol = $resolveBooking(["reschedule_reason", "Reschedule_Reason"]);
        $reminderCol = $resolveBooking(["one_hour_reminder_sent_at", "One_Hour_Reminder_Sent_At"]);
        $now = Carbon::now("Asia/Manila");
        $sharedChannel = "demo-group-call-prof-3001";
        $timeSlots = [
            ["Stud_ID" => "910000001", "time" => "08:30:00"],
            ["Stud_ID" => "910000002", "time" => "09:00:00"],
            ["Stud_ID" => "910000003", "time" => "09:30:00"],
            ["Stud_ID" => "910000004", "time" => "10:00:00"],
            ["Stud_ID" => "910000005", "time" => "10:30:00"],
            ["Stud_ID" => "910000006", "time" => "11:00:00"],
            ["Stud_ID" => "910000007", "time" => "11:30:00"],
        ];
        $bookingDates = [$now->toDateString(), $now->copy()->addDay()->toDateString()];

        foreach ($bookingDates as $date) {
            foreach ($timeSlots as $slot) {
                $payload = [];
                if ($profIdCol) {
                    $payload[$profIdCol] = $profId;
                }
                if ($studIdCol) {
                    $payload[$studIdCol] = $slot["Stud_ID"];
                }
                if ($bookingDateCol) {
                    $payload[$bookingDateCol] = $date;
                }
                if ($bookingTimeCol) {
                    $payload[$bookingTimeCol] = $slot["time"];
                }
                if ($modeCol) {
                    $payload[$modeCol] = "online";
                }
                if ($statusCol) {
                    $payload[$statusCol] = "approved";
                }
                if ($subjectCol) {
                    $payload[$subjectCol] = null;
                }
                if ($consultTypeIdCol) {
                    $payload[$consultTypeIdCol] = 1;
                }
                if ($consultationTypeCol) {
                    $payload[$consultationTypeCol] = "General";
                }
                if ($meetingLinkCol) {
                    $payload[$meetingLinkCol] = $sharedChannel;
                }
                if ($createdAtCol) {
                    $payload[$createdAtCol] = $now;
                }
                if ($updatedAtCol) {
                    $payload[$updatedAtCol] = $now;
                }
                if ($reschedCol) {
                    $payload[$reschedCol] = null;
                }
                if ($reminderCol) {
                    $payload[$reminderCol] = null;
                }

                $match = [];
                if ($profIdCol) {
                    $match[$profIdCol] = $profId;
                }
                if ($studIdCol) {
                    $match[$studIdCol] = $payload[$studIdCol] ?? null;
                }
                if ($bookingDateCol) {
                    $match[$bookingDateCol] = $payload[$bookingDateCol] ?? $date;
                }

                DB::table("t_consultation_bookings")->updateOrInsert($match, $payload);
            }
        }
    }
}
