<?php

namespace App\Services;

use App\Events\TermActivated;
use App\Models\AcademicYear;
use App\Models\Term;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicTermService
{
    public function getActiveTerm(): ?Term
    {
        return Term::query()->active()->with("academicYear")->orderByDesc("activated_at")->first();
    }

    public function getActiveYear(): ?AcademicYear
    {
        return AcademicYear::query()->active()->with("terms")->orderByDesc("activated_at")->first();
    }

    public function createYearWithTerm(array $yearData, array $termData, int $adminId): Term
    {
        return DB::transaction(function () use ($yearData, $termData, $adminId) {
            $this->validateYearRange($yearData["start_at"], $yearData["end_at"]);
            $yearLabel = $yearData["label"];

            if (AcademicYear::query()->where("label", $yearLabel)->exists()) {
                throw ValidationException::withMessages([
                    "label" => "Academic year already exists.",
                ]);
            }

            $year = AcademicYear::query()->create([
                "label" => $yearLabel,
                "start_at" => $yearData["start_at"],
                "end_at" => $yearData["end_at"],
                "status" => "draft",
            ]);

            $term = $this->createTerm($year, $termData);

            $year->refresh();
            $year->terms()->save($term);

            return $term;
        });
    }

    public function createTerm(AcademicYear $year, array $data): Term
    {
        $sequence = (int) ($data["sequence"] ?? $year->terms()->max("sequence") + 1);
        $name = $data["name"] ?? "Term {$sequence}";

        $start = CarbonImmutable::parse($data["start_at"]);
        $end = CarbonImmutable::parse($data["end_at"]);
        $this->validateYearRange($start, $end);
        $this->assertWithinYear($year, $start, $end);
        $this->assertNoOverlap($year, $start, $end, $sequence);

        return new Term([
            "sequence" => $sequence,
            "name" => $name,
            "start_at" => $start,
            "end_at" => $end,
            "enrollment_deadline" => $data["enrollment_deadline"] ?? null,
            "grade_submission_deadline" => $data["grade_submission_deadline"] ?? null,
            "status" => "draft",
        ]);
    }

    public function updateTermDates(Term $term, array $data): Term
    {
        $start = CarbonImmutable::parse($data["start_at"]);
        $end = CarbonImmutable::parse($data["end_at"]);
        $this->validateYearRange($start, $end);
        $this->assertWithinYear($term->academicYear, $start, $end);
        $this->assertNoOverlap($term->academicYear, $start, $end, $term->sequence, $term->id);

        $term
            ->forceFill([
                "start_at" => $start,
                "end_at" => $end,
                "enrollment_deadline" => $data["enrollment_deadline"] ?? null,
                "grade_submission_deadline" => $data["grade_submission_deadline"] ?? null,
            ])
            ->save();

        return $term->refresh();
    }

    public function activateTerm(Term $term, int $adminId, bool $force = false): Term
    {
        return DB::transaction(function () use ($term, $adminId, $force) {
            $term = Term::query()->lockForUpdate()->findOrFail($term->id);
            $current = $this->getActiveTerm();
            if ($current && $current->is($term)) {
                return $term;
            }

            if ($current) {
                if (
                    !$force &&
                    CarbonImmutable::now()->lt(CarbonImmutable::parse($current->end_at))
                ) {
                    throw ValidationException::withMessages([
                        "term" => "The current term has not ended yet.",
                    ]);
                }
                app(AcademicTermRolloverService::class)->closeTerm($current, $adminId);
            }

            $year = $term->academicYear()->lockForUpdate()->first();
            if ($year->status !== "active") {
                $year
                    ->forceFill([
                        "status" => "active",
                        "activated_at" => now(),
                        "activated_by" => $adminId,
                    ])
                    ->save();
            }

            $term
                ->forceFill([
                    "status" => "active",
                    "activated_at" => now(),
                ])
                ->save();

            app(AcademicTermRolloverService::class)->initializeTerm($term, $adminId);

            $term->refresh();
            event(new TermActivated($term, $adminId));

            return $term;
        });
    }

    public function closeYearIfComplete(AcademicYear $year, int $adminId): void
    {
        $openTerms = $year
            ->terms()
            ->whereIn("status", ["draft", "active"])
            ->count();
        if ($openTerms === 0 && $year->status !== "closed") {
            $year
                ->forceFill([
                    "status" => "closed",
                    "closed_at" => now(),
                    "closed_by" => $adminId,
                ])
                ->save();
        }
    }

    protected function validateYearRange($start, $end): void
    {
        $start = CarbonImmutable::parse($start);
        $end = CarbonImmutable::parse($end);
        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                "range" => "End date must be after start date.",
            ]);
        }
    }

    protected function assertWithinYear(
        AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): void {
        if (
            $start->lt(CarbonImmutable::parse($year->start_at)) ||
            $end->gt(CarbonImmutable::parse($year->end_at))
        ) {
            throw ValidationException::withMessages([
                "term" => "Term dates must fall within the academic year.",
            ]);
        }
    }

    protected function assertNoOverlap(
        AcademicYear $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
        int $sequence,
        ?int $ignoreId = null,
    ): void {
        $conflict = $year
            ->terms()
            ->where("sequence", "!=", $sequence)
            ->when($ignoreId, fn($q) => $q->where("id", "!=", $ignoreId))
            ->where(function ($query) use ($start, $end) {
                $query
                    ->whereBetween("start_at", [$start, $end])
                    ->orWhereBetween("end_at", [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where("start_at", "<=", $start)->where("end_at", ">=", $end);
                    });
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                "term" => "Term dates overlap with an existing term.",
            ]);
        }
    }
}
