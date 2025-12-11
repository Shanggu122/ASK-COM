<?php

namespace App\Http\Controllers;

use App\Models\Professor;
use Illuminate\Http\Request;

class comsciController extends Controller
{
    public function show()
    {

    $professors = Professor::where('Dept_ID', 2)->get();
    return view('comsci', ['professor' => $professors[0]]);
    }

}