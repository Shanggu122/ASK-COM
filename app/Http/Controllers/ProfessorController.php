<?php

namespace App\Http\Controllers;

use App\Models\Professor;
use Illuminate\Http\Request;

class ProfessorController extends Controller
{
    public function getUserData($id)
    {
        // Retrieve user data by the given ID
        $user = Professor::find($id);

        // Return the user data in a JSON response
        return response()->json($user);
    }
}