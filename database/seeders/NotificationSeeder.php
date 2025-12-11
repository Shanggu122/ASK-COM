<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        // Get a sample student ID (assuming t_student table exists)
        $studentId = DB::table("t_student")->first()->Stud_ID ?? 1;

        // Create sample notifications
        $notifications = [
            [
                "user_id" => $studentId,
                "booking_id" => 1,
                "type" => "accepted",
                "title" => "Consultation Accepted",
                "message" =>
                    "Your consultation with Prof. Smith has been accepted for March 15, 2025.",
                "is_read" => false,
                "created_at" => now()->subHours(2),
                "updated_at" => now()->subHours(2),
            ],
            [
                "user_id" => $studentId,
                "booking_id" => 2,
                "type" => "completed",
                "title" => "Consultation Completed",
                "message" => "Your consultation with Prof. Johnson has been completed.",
                "is_read" => false,
                "created_at" => now()->subHours(5),
                "updated_at" => now()->subHours(5),
            ],
            [
                "user_id" => $studentId,
                "booking_id" => 3,
                "type" => "rescheduled",
                "title" => "Consultation Rescheduled",
                "message" =>
                    "Your consultation with Prof. Brown has been rescheduled to March 20, 2025.",
                "is_read" => true,
                "created_at" => now()->subDays(1),
                "updated_at" => now()->subDays(1),
            ],
            [
                "user_id" => $studentId,
                "booking_id" => 4,
                "type" => "cancelled",
                "title" => "Consultation Cancelled",
                "message" =>
                    "Your consultation with Prof. Davis has been cancelled due to unforeseen circumstances.",
                "is_read" => true,
                "created_at" => now()->subDays(2),
                "updated_at" => now()->subDays(2),
            ],
        ];

        foreach ($notifications as $notification) {
            Notification::create($notification);
        }
    }
}
