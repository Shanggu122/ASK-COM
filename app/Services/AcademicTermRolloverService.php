<?php

namespace App\Services;

use App\Events\TermClosed;
use App\Models\Term;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AcademicTermRolloverService
{
    public function closeTerm(Term $term, int $adminId): void
    {
        DB::transaction(function () use ($term, $adminId) {
            $term = Term::query()->lockForUpdate()->findOrFail($term->id);
            if ($term->status === "closed") {
                return;
            }

            $term
                ->forceFill([
                    "status" => "closed",
                    "closed_at" => now(),
                ])
                ->save();

            $this->assignTermIdsForRange($term);
            $term->refresh();
            event(new TermClosed($term, $adminId));

            /** @var AcademicTermService $termService */
            $termService = app(AcademicTermService::class);
            $year = $term->academicYear()->lockForUpdate()->first();
            $termService->closeYearIfComplete($year, $adminId);
        });
    }

    public function initializeTerm(Term $term, int $adminId): void
    {
        DB::transaction(function () use ($term) {
            $term = Term::query()->lockForUpdate()->findOrFail($term->id);

            $this->assignTermIdsForRange($term);

            $this->clearBufferedState($term);
        });

        Log::info("Academic term initialized", [
            "term_id" => $term->id,
            "term" => $term->name,
            "year" => optional($term->academicYear)->label,
            "triggered_by" => $adminId,
        ]);
    }

    protected function assignTermIdsForRange(Term $term): void
    {
        $start = CarbonImmutable::parse($term->start_at);
        $end = CarbonImmutable::parse($term->end_at);

        $rangeStrings = collect(CarbonPeriod::create($start, $end))
            ->map(function ($day) {
                return $day->format("D M d Y");
            })
            ->values()
            ->all();

        if (!empty($rangeStrings)) {
            DB::table("t_consultation_bookings")
                ->whereNull("term_id")
                ->whereIn("Booking_Date", $rangeStrings)
                ->update(["term_id" => $term->id]);
        }

        DB::table("calendar_overrides")
            ->whereNull("term_id")
            ->where(function ($query) use ($start, $end) {
                $query
                    ->whereBetween("start_date", [$start, $end])
                    ->orWhereBetween("end_date", [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where("start_date", "<=", $start)->where("end_date", ">=", $end);
                    });
            })
            ->update(["term_id" => $term->id]);

        DB::table("notifications")
            ->whereNull("term_id")
            ->whereIn("booking_id", function ($sub) use ($term) {
                $sub->select("Booking_ID")
                    ->from("t_consultation_bookings")
                    ->where("term_id", $term->id);
            })
            ->update(["term_id" => $term->id]);
    }

    protected function clearBufferedState(Term $term): void
    {
        // Reset common caches so the new term starts clean.
        if (Schema::hasTable("professors") && Schema::hasColumn("professors", "Schedule")) {
            DB::table("professors")->update(["Schedule" => null]);
        }

        if (Schema::hasTable("t_consultation_bookings")) {
            DB::table("t_consultation_bookings")
                ->whereNull("Status")
                ->update(["Status" => "pending"]);
        }
    }
}
