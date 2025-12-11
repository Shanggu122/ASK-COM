<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class ProfVideoCallController extends Controller
{
    public function show($channel)
    {
        $counterpartName = null;
        $professor = Auth::guard("professor")->user();
        $profId = $professor?->Prof_ID;
        $studId = null;

        if (is_string($channel)) {
            if (preg_match('/stud-([^-]+)/', $channel, $studMatch)) {
                $studId = $studMatch[1];
            }
            if (!$profId && preg_match('/prof-([^-]+)/', $channel, $profMatch)) {
                $profId = $profMatch[1];
            }
        }

        [$studId, $profId, $studentName] = $this->resolveParticipants($channel, $studId, $profId);

        if ($studId !== null && $studentName !== null) {
            $counterpartName = $studentName;
        }

        return view("video-call-professor", [
            "channel" => $channel,
            "counterpartName" => $counterpartName,
            "professorName" => $professor?->Name,
            "studId" => $studId,
            "profId" => $profId,
        ]);
    }

    private function resolveParticipants(string $channel, $studId, $profId): array
    {
        $studentName = null;
        $bookingTable = "t_consultation_bookings";
        if (!Schema::hasTable($bookingTable)) {
            return [$studId, $profId, $studentName];
        }

        $columns = Schema::getColumnListing($bookingTable);
        $detect = function (array $candidates) use ($columns) {
            foreach ($candidates as $candidate) {
                if (in_array($candidate, $columns, true)) {
                    return $candidate;
                }
            }
            return null;
        };

        $meetingLinkCol = $detect(["Meeting_Link", "meeting_link"]);
        $studCol = $detect(["Stud_ID", "stud_id"]);
        $profCol = $detect(["Prof_ID", "prof_id"]);
        $bookingDateCol = $detect(["Booking_Date", "booking_date"]);
        $bookingTimeCol = $detect(["Booking_Time", "booking_time"]);

        $row = null;
        if ($meetingLinkCol) {
            $row = DB::table($bookingTable)
                ->where($meetingLinkCol, $channel)
                ->orderBy($studCol ?? $meetingLinkCol)
                ->first();
        }

        if (!$row && $profCol && $profId) {
            $candidates = DB::table($bookingTable)
                ->where($profCol, $profId)
                ->get();
            foreach ($candidates as $candidate) {
                $generated = $this->buildScheduleChannel(
                    (int) ($candidate->{$profCol} ?? 0),
                    $meetingLinkCol ? ($candidate->{$meetingLinkCol} ?? "") : "",
                    $bookingDateCol ? ($candidate->{$bookingDateCol} ?? null) : null,
                    $bookingTimeCol ? ($candidate->{$bookingTimeCol} ?? null) : null,
                );
                if ($generated === $channel) {
                    $row = $candidate;
                    break;
                }
            }
        }

        if ($row) {
            if (!$studId && $studCol && isset($row->{$studCol})) {
                $studId = (string) $row->{$studCol};
            }
            if (!$profId && $profCol && isset($row->{$profCol})) {
                $profId = (string) $row->{$profCol};
            }
            if ($studCol && isset($row->{$studCol})) {
                $student = User::find($row->{$studCol});
                if ($student && $student->Name) {
                    $studentName = $student->Name;
                }
            }
        }

        return [$studId, $profId, $studentName];
    }

    private function buildScheduleChannel(
        int $profId,
        $meetingLink,
        $bookingDate,
        $bookingTime,
    ): string {
        $link = trim((string) ($meetingLink ?? ""));
        if ($link !== "") {
            return $link;
        }
        $dateKey = trim((string) ($bookingDate ?? ""));
        $timeKey = trim((string) ($bookingTime ?? ""));
        $raw = $profId . "|" . $dateKey . "|" . $timeKey;
        if ($raw === "0||") {
            return "schedule-prof-" . $profId;
        }
        $hash = substr(sha1($raw), 0, 12);
        return "schedule-prof-{$profId}-{$hash}";
    }
}
