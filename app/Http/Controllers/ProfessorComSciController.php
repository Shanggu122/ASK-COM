<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Professor;
use Illuminate\Support\Facades\Auth;

class ProfessorComSciController extends Controller
{
    public function showColleagues()
    {
        // Get the current professor's department ID
        $currentUser = Auth::guard('professor')->user();
        
        // Get other professors from the same department (Computer Science - Dept_ID = 2)
        // Exclude the current professor to show only colleagues
        $colleagues = Professor::where('Dept_ID', 2)
            ->where('Prof_ID', '!=', $currentUser->Prof_ID)
            ->get(['Name', 'Prof_ID', 'Email', 'Dept_ID', 'profile_picture']);
            
        return view('comsci-professor', compact('colleagues'));
    }
}
