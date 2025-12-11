<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/debug/notifications', function() {
    $notifications = DB::table('notifications')
        ->join('professors', 'notifications.user_id', '=', 'professors.Prof_ID')
        ->select('notifications.*', 'professors.Name as professor_name')
        ->orderBy('notifications.created_at', 'desc')
        ->limit(10)
        ->get();
        
    $bookings = DB::table('t_consultation_bookings')
        ->join('t_student', 't_consultation_bookings.Stud_ID', '=', 't_student.Stud_ID')
        ->join('professors', 't_consultation_bookings.Prof_ID', '=', 'professors.Prof_ID')
        ->select('t_consultation_bookings.*', 't_student.Name as student_name', 'professors.Name as professor_name')
        ->orderBy('t_consultation_bookings.Created_At', 'desc')
        ->limit(10)
        ->get();
    
    return view('debug.notifications', [
        'notifications' => $notifications,
        'bookings' => $bookings
    ]);
});
