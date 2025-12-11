<?php

namespace App\Services;

use App\Models\CalendarOverride;
use Carbon\Carbon;

class CalendarOverrideService
{
    /**
     * Evaluate if a date is blocked or has a forced mode.
     * Precedence: professor > subject > department > all; and block_all > force_mode > holiday.
     */
    public function evaluate(int $profId, string $dateString): array
    {
        $date = Carbon::parse($dateString, "Asia/Manila")->toDateString();

        // Fetch all overrides that may apply on that date
        $overrides = CalendarOverride::query()
            ->where("start_date", "<=", $date)
            ->where("end_date", ">=", $date)
            ->orderByRaw("FIELD(effect, 'block_all','force_mode','holiday') asc")
            ->get();

        // Resolve scope precedence: professor > subject > department > all
        $chosen = null;
        foreach (["professor", "subject", "department", "all"] as $scope) {
            $cand = $overrides->first(function ($o) use ($scope, $profId) {
                if ($o->scope_type !== $scope) {
                    return false;
                }
                if ($scope === "all") {
                    return true;
                }
                // For v1, we only use professor scope concretely; others reserved
                if ($scope === "professor") {
                    return (int) $o->scope_id === (int) $profId;
                }
                return false;
            });
            if ($cand) {
                $chosen = $cand;
                break;
            }
        }

        if (!$chosen) {
            return [
                "blocked" => false,
                "forced_mode" => null,
                "label" => null,
                "reason_key" => null,
            ];
        }

        if ($chosen->effect === "block_all") {
            return [
                "blocked" => true,
                "forced_mode" => null,
                "label" => null,
                "reason_key" => $chosen->reason_key,
            ];
        }
        if ($chosen->effect === "force_mode") {
            return [
                "blocked" => false,
                "forced_mode" => $chosen->allowed_mode,
                "label" => null,
                "reason_key" => $chosen->reason_key,
            ];
        }
        // holiday
        return [
            "blocked" => false,
            "forced_mode" => null,
            "label" => "holiday",
            "reason_key" => $chosen->reason_key,
        ];
    }
}
