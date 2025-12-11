<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;

class BackfillRescheduleReasons extends Command
{
    protected $signature = "ascc:backfill-reschedule-reasons {--dry-run} {--override-id=} {--days=7}";

    protected $description = "Backfill reschedule_reason for auto-rescheduled bookings using nearby admin overrides";

    public function handle(): int
    {
        $dry = (bool) $this->option("dry-run");
        $overrideId = $this->option("override-id");
        $days = (int) $this->option("days");
        if ($days <= 0) {
            $days = 7;
        }

        // Resolve an override to use
        if ($overrideId) {
            $override = DB::table("calendar_overrides")->where("id", (int) $overrideId)->first();
        } else {
            $since = date("Y-m-d H:i:s", strtotime("-{$days} days"));
            $override = DB::table("calendar_overrides")
                ->whereIn("effect", ["block_all", "force_mode"])
                ->where("created_at", ">=", $since)
                ->orderBy("created_at", "desc")
                ->first();
        }

        if (!$override) {
            $this->error("No suitable override found. Specify --override-id or adjust --days.");
            return Command::FAILURE;
        }

        // Skip End Year-like overrides for block_all
        if ($override->effect === "block_all") {
            $rk = strtolower((string) ($override->reason_key ?? ""));
            $rt = strtolower((string) ($override->reason_text ?? ""));
            if ($rk === "end_year" || str_contains($rt, "end year")) {
                $this->error(
                    "Selected override appears to be End Year. Choose a different override via --override-id.",
                );
                return Command::FAILURE;
            }
        }

        // Derive reason once
        $reasonTxt = trim((string) ($override->reason_text ?? ""));
        if ($reasonTxt === "" && !empty($override->reason_key)) {
            $reasonTxt = $this->mapReasonKey((string) $override->reason_key);
        }
        if ($reasonTxt === "") {
            $reasonTxt = "administrative reasons";
        }

        $this->info(
            "Using override #{$override->id} ({$override->effect}) -> reason: {$reasonTxt}",
        );

        $rows = DB::table("t_consultation_bookings")
            ->where("Status", "rescheduled")
            ->where("reschedule_reason", "=", "Admin override reschedule")
            ->get();

        $count = $rows->count();
        $this->info("Found {$count} bookings to update.");

        $updated = 0;
        foreach ($rows as $b) {
            $this->line(
                "#{$b->Booking_ID}: set reason -> {$reasonTxt}" . ($dry ? " [dry-run]" : ""),
            );
            if ($dry) {
                continue;
            }

            DB::table("t_consultation_bookings")
                ->where("Booking_ID", $b->Booking_ID)
                ->update(["reschedule_reason" => $reasonTxt]);

            try {
                $prof = DB::table("professors")->where("Prof_ID", $b->Prof_ID)->value("Name");
                Notification::updateNotificationStatus(
                    $b->Booking_ID,
                    "rescheduled",
                    $prof,
                    $b->Booking_Date,
                    $reasonTxt,
                );
            } catch (\Throwable $e) {
            }
            $updated++;
        }

        $this->info("Updated {$updated} bookings." . ($dry ? " (dry-run)" : ""));
        return Command::SUCCESS;
    }

    private function mapReasonKey(string $rk): string
    {
        $rk = strtolower($rk);
        $map = [
            "weather" => "Inclement weather",
            "power_outage" => "Power outage",
            "health_advisory" => "Health advisory",
            "holiday_shift" => "Holiday shift",
            "facility" => "Facility issue",
            "prof_leave" => "Professor leave",
            "health" => "Health advisory",
            "emergency" => "Emergency advisory",
        ];
        return $map[$rk] ?? ucfirst(str_replace("_", " ", $rk));
    }
}
