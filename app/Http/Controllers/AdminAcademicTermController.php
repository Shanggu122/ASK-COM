<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\AcademicTermService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAcademicTermController extends Controller
{
    public function index(AcademicTermService $service)
    {
        $years = AcademicYear::query()
            ->with([
                "terms" => function ($query) {
                    $query->orderBy("sequence");
                },
            ])
            ->orderByDesc("start_at")
            ->get();

        $active = $service->getActiveTerm();

        return response()->json([
            "active_term" => $active ? $this->transformTerm($active) : null,
            "years" => $years->map(function (AcademicYear $year) {
                return [
                    "id" => $year->id,
                    "label" => $year->label,
                    "start_at" => optional($year->start_at)->toDateString(),
                    "end_at" => optional($year->end_at)->toDateString(),
                    "status" => $year->status,
                    "terms" => $year->terms->map(fn(Term $term) => $this->transformTerm($term)),
                ];
            }),
        ]);
    }

    public function store(Request $request, AcademicTermService $service)
    {
        $data = $request->validate([
            "year.label" => ["required", "string", "max:50"],
            "year.start_at" => ["required", "date"],
            "year.end_at" => ["required", "date"],
            "term.sequence" => ["sometimes", "integer", "min:1"],
            "term.name" => ["required", "string", "max:120"],
            "term.start_at" => ["required", "date"],
            "term.end_at" => ["required", "date"],
            "term.enrollment_deadline" => ["nullable", "date"],
            "term.grade_submission_deadline" => ["nullable", "date"],
        ]);

        $term = $service->createYearWithTerm($data["year"], $data["term"], $this->currentAdminId());

        return response()->json($this->transformTerm($term->fresh(["academicYear"])));
    }

    public function storeTerm(
        AcademicYear $academicYear,
        Request $request,
        AcademicTermService $service,
    ) {
        $data = $request->validate([
            "sequence" => ["sometimes", "integer", "min:1"],
            "name" => ["required", "string", "max:120"],
            "start_at" => ["required", "date"],
            "end_at" => ["required", "date"],
            "enrollment_deadline" => ["nullable", "date"],
            "grade_submission_deadline" => ["nullable", "date"],
        ]);

        $term = $service->createTerm($academicYear, $data);
        $academicYear->terms()->save($term);

        return response()->json($this->transformTerm($term->fresh(["academicYear"])));
    }

    public function update(Term $term, Request $request, AcademicTermService $service)
    {
        $data = $request->validate([
            "start_at" => ["required", "date"],
            "end_at" => ["required", "date"],
            "enrollment_deadline" => ["nullable", "date"],
            "grade_submission_deadline" => ["nullable", "date"],
            "year_label" => ["nullable", "string", "max:50"],
        ]);

        $termPayload = [
            "start_at" => $data["start_at"],
            "end_at" => $data["end_at"],
            "enrollment_deadline" => $data["enrollment_deadline"] ?? null,
            "grade_submission_deadline" => $data["grade_submission_deadline"] ?? null,
        ];

        $updated = $service->updateTermDates($term, $termPayload);

        if (array_key_exists("year_label", $data)) {
            $label = trim((string) $data["year_label"]);
            $year = $term->academicYear;
            if ($year && $label !== "" && $year->label !== $label) {
                $year->forceFill(["label" => $label])->save();
            }
        }

        return response()->json($this->transformTerm($updated->fresh(["academicYear"])));
    }

    public function activate(Term $term, Request $request, AcademicTermService $service)
    {
        $force = $request->boolean("force", false);
        $activated = $service->activateTerm($term, $this->currentAdminId(), $force);

        return response()->json($this->transformTerm($activated->fresh(["academicYear"])));
    }

    protected function currentAdminId(): int
    {
        $guard = Auth::guard("admin");
        $user = $guard->user();
        if ($user && isset($user->Admin_ID)) {
            return (int) $user->Admin_ID;
        }

        return 0;
    }

    protected function transformTerm(Term $term): array
    {
        return [
            "id" => $term->id,
            "academic_year_id" => $term->academic_year_id,
            "sequence" => $term->sequence,
            "name" => $term->name,
            "start_at" => optional($term->start_at)->toDateString(),
            "end_at" => optional($term->end_at)->toDateString(),
            "enrollment_deadline" => optional($term->enrollment_deadline)->toDateString(),
            "grade_submission_deadline" => optional(
                $term->grade_submission_deadline,
            )->toDateString(),
            "status" => $term->status,
            "activated_at" => optional($term->activated_at)?->toDateTimeString(),
            "closed_at" => optional($term->closed_at)?->toDateTimeString(),
            "year_label" => optional($term->academicYear)->label,
        ];
    }
}
