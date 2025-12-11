<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Professor; // Make sure this is included
use Illuminate\Support\Facades\DB;

class CardComsci extends Controller
{
    public function showComsci()
    {
        // Get only professors with Dept_ID = 2 and load their subjects
        $professors = Professor::where("Dept_ID", 2)->with("subjects")->get();

        // Get consultation types for the modal
        $consultationTypes = DB::table("t_consultation_types")->get();

        // Preload ALL override types (global + professor-scoped) for prev/current/next months
        $profIds = $professors->pluck("Prof_ID")->all();
        $preloadedOverrides = [];
        if (!empty($profIds)) {
            $tz = "Asia/Manila";
            $start = now($tz)->startOfMonth()->subMonth()->toDateString();
            $end = now($tz)->addMonth()->endOfMonth()->toDateString();

            $rows = \App\Models\CalendarOverride::query()
                ->where(function ($q) use ($profIds) {
                    $q->where("scope_type", "all")->orWhere(function ($qq) use ($profIds) {
                        $qq->where("scope_type", "professor")->whereIn("scope_id", $profIds);
                    });
                })
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween("start_date", [$start, $end])
                        ->orWhereBetween("end_date", [$start, $end])
                        ->orWhere(function ($qq) use ($start, $end) {
                            $qq->where("start_date", "<=", $start)->where("end_date", ">=", $end);
                        });
                })
                ->orderBy("start_date", "asc")
                ->get([
                    "start_date",
                    "end_date",
                    "scope_type",
                    "scope_id",
                    "effect",
                    "allowed_mode",
                    "reason_key",
                    "reason_text",
                ]);

            foreach ($profIds as $pid) {
                $preloadedOverrides[(int) $pid] = [];
            }

            foreach ($rows as $ov) {
                $period = \Carbon\CarbonPeriod::create($ov->start_date, $ov->end_date);
                foreach ($period as $d) {
                    $iso = $d->format("Y-m-d");
                    $targetProfIds = [];
                    if ($ov->scope_type === "all") {
                        $targetProfIds = array_map("intval", $profIds);
                    } elseif ($ov->scope_type === "professor") {
                        $targetProfIds = [(int) $ov->scope_id];
                    }
                    foreach ($targetProfIds as $pid) {
                        if (!isset($preloadedOverrides[$pid])) {
                            $preloadedOverrides[$pid] = [];
                        }
                        $preloadedOverrides[$pid][$iso] = $preloadedOverrides[$pid][$iso] ?? [];
                        $label = null;
                        if ($ov->effect === "holiday") {
                            $label = $ov->reason_text ?: "Holiday";
                        } elseif ($ov->effect === "block_all") {
                            $label = $ov->reason_key === "prof_leave" ? "Leave" : "Suspension";
                        } elseif ($ov->effect === "force_mode") {
                            $label = "Force " . ucfirst($ov->allowed_mode ?? "mode");
                        }
                        $preloadedOverrides[$pid][$iso][] = [
                            "effect" => $ov->effect,
                            "allowed_mode" => $ov->allowed_mode,
                            "reason_key" => $ov->reason_key,
                            "reason_text" => $ov->reason_text,
                            "label" => $label,
                        ];
                    }
                }
            }
        }

        return view("comsci", compact("professors", "consultationTypes", "preloadedOverrides"));
    }
}
