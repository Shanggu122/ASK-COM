<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Services\AcademicTermService;

class AdminDashboardController extends Controller
{
    public function __invoke(AcademicTermService $service)
    {
        $active = $service->getActiveTerm();
        $terms = Term::query()->with("academicYear")->orderByDesc("start_at")->limit(20)->get();

        return view("admin-dashboard", [
            "activeTerm" => $active,
            "termOptions" => $terms,
        ]);
    }
}
