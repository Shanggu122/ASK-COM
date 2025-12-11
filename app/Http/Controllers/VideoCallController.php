<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Professor;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VideoCallController extends Controller
{
    public function show($user)
    {
        $channel = $user;
        $counterpartName = null;

        $authenticatedStudent = Auth::user();
        $authenticatedProfessor = Auth::guard("professor")->user();

        $studId = $authenticatedStudent->Stud_ID ?? null;
        $profId = $authenticatedProfessor->Prof_ID ?? null;

        $channelText = is_string($channel) ? $channel : "";

        if (!$studId && preg_match('/stud-([^-]+)/', $channelText, $studMatch)) {
            $studId = $studMatch[1];
        }
        if (!$profId && preg_match('/prof-([^-]+)/', $channelText, $profMatch)) {
            $profId = $profMatch[1];
        }

        [$studId, $profId, $derivedName] = $this->resolveParticipants($channelText, $studId, $profId);

        if ($profId) {
            $prof = Professor::find($profId);
            if ($prof) {
                $counterpartName = $prof->Name;
            }
        }

        if ($counterpartName === null && $derivedName !== null) {
            $counterpartName = $derivedName;
        }

        return view("video-call", [
            "channel" => $channel,
            "counterpartName" => $counterpartName,
            "studId" => $studId,
            "profId" => $profId,
        ]);
    }

    private function resolveParticipants(string $channel, $studId, $profId): array
    {
        $derivedName = null;
        $bookingTable = "t_consultation_bookings";
        if (!Schema::hasTable($bookingTable)) {
            return [$studId, $profId, $derivedName];
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
            if ($profCol && isset($row->{$profCol})) {
                $professor = Professor::find($row->{$profCol});
                if ($professor && $professor->Name) {
                    $derivedName = $professor->Name;
                }
            }
        }

        return [$studId, $profId, $derivedName];
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

    public function participant($uid)
    {
        if (!Auth::check() && !Auth::guard("professor")->check()) {
            return response()->json(["error" => "unauthorized"], 401);
        }

        $key = trim((string) $uid);
        if ($key === "") {
            return response()->json(["error" => "missing_uid"], 422);
        }

        $role = "student";
        $participant = User::find($key);
        if (!$participant) {
            $participant = Professor::find($key);
            $role = $participant ? "professor" : $role;
        }

        if (!$participant) {
            return response()->json(["error" => "not_found"], 404);
        }

        $name = (string) ($participant->Name ?? ($participant->name ?? "Participant"));
        $name = trim($name) === "" ? "Participant" : $name;
        $hasPhoto = !empty($participant->profile_picture);
        $photoUrl = $hasPhoto ? $participant->profile_photo_url : null;
        $initialSource = trim($name);
        $initial = $initialSource !== "" ? Str::upper(Str::substr($initialSource, 0, 1)) : "P";

        return response()->json([
            "uid" => (string) $key,
            "name" => $name,
            "initial" => $initial,
            "photoUrl" => $photoUrl,
            "hasPhoto" => (bool) $hasPhoto,
            "role" => $role,
        ]);
    }
}
