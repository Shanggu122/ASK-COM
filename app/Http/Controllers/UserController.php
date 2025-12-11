<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUserData($id)
    {
        // Retrieve user data by the given ID
        $user = User::find($id);

        // Return the user data in a JSON response
        return response()->json($user);
    }
}