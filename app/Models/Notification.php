<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ["user_id", "booking_id", "type", "title", "message", "is_read"];

    protected $casts = [
        "is_read" => "boolean",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id", "Stud_ID");
    }

    public function booking()
    {
        return $this->hasOne("App\Models\ConsultationBooking", "Booking_ID", "booking_id");
    }

    public function markAsRead()
    {
        $this->update(["is_read" => true]);
    }

    public static function createConsultationNotification(
        $userId,
        $bookingId,
        $type,
        $professorName = null,
        $date = null,
        $reason = null,
    ) {
        // Use the updateOrCreate method to prevent duplicates
        return self::updateOrCreateConsultationNotification(
            $userId,
            $bookingId,
            $type,
            $professorName,
            $date,
            $reason,
        );
    }

    public static function createProfessorNotification(
        $professorId,
        $bookingId,
        $studentName,
        $subject,
        $date,
        $type,
    ) {
        $title = "New Consultation Booking";
        $message = "{$studentName} booked an appointment on {$date} for {$subject} - {$type}";

        // Check if notification already exists for this booking and professor
        $existingNotification = self::where("user_id", $professorId)
            ->where("booking_id", $bookingId)
            ->first();

        if ($existingNotification) {
            // Update existing notification
            return $existingNotification->update([
                "type" => "booking_request",
                "title" => $title,
                "message" => $message,
                "is_read" => false,
                "updated_at" => now(),
            ]);
        } else {
            // Create new notification
            return self::create([
                "user_id" => $professorId,
                "booking_id" => $bookingId,
                "type" => "booking_request",
                "title" => $title,
                "message" => $message,
                "is_read" => false,
            ]);
        }
    }

    public static function updateNotificationStatus(
        $bookingId,
        $newType,
        $professorName = null,
        $date = null,
        $reason = null,
    ) {
        // Get booking details to identify student and professor
        $booking = DB::table("t_consultation_bookings")
            ->leftJoin("t_student", "t_consultation_bookings.Stud_ID", "=", "t_student.Stud_ID")
            ->leftJoin("professors", "t_consultation_bookings.Prof_ID", "=", "professors.Prof_ID")
            ->select(
                "t_consultation_bookings.*",
                "t_student.Name as Student_Name",
                "professors.Name as Professor_Name",
            )
            ->where("Booking_ID", $bookingId)
            ->first();

        if (!$booking) {
            return false;
        }

        $titles = [
            "booking_request" => "New Consultation Booking",
            "accepted" => "Consultation Accepted",
            "completed" => "Consultation Completed",
            "rescheduled" => "Consultation Rescheduled",
            "cancelled" => "Consultation Cancelled",
            "today_reminder" => 'Today\'s Consultation Reminder',
            "completion_pending" => "Completion Review Required",
            "completion_declined" => "Completion Declined",
        ];

        // Student-specific messages
        $studentMessages = [
            "booking_request" => "Your consultation booking is pending approval.",
            "accepted" => "Your consultation with {$professorName} has been accepted for {$date}.",
            "completed" =>
                "Your consultation with {$professorName} has been completed." .
                ($reason ? " Remarks: {$reason}" : ""),
            "rescheduled" =>
                "Your consultation with {$professorName} has been rescheduled to {$date}." .
                ($reason ? " Reason: {$reason}" : ""),
            "cancelled" => "Your consultation with {$professorName} has been cancelled.",
            "today_reminder" => "Reminder: You have a consultation today with {$professorName}.",
            "completion_pending" =>
                "{$professorName} marked your consultation as completed." .
                ($reason ? " Remarks: {$reason}." : "") .
                " Please review and confirm.",
            "completion_declined" =>
                "You declined {$professorName}'s completion request." .
                ($reason ? " Notes: {$reason}." : ""),
        ];

        // Professor-specific messages
        $professorMessages = [
            "booking_request" => "{$booking->Student_Name} booked a consultation.",
            "accepted" => "You accepted {$booking->Student_Name}'s consultation for {$date}.",
            "completed" =>
                "Consultation with {$booking->Student_Name} has been completed." .
                ($reason ? " Remarks: {$reason}" : ""),
            "rescheduled" =>
                "You rescheduled {$booking->Student_Name}'s consultation to {$date}." .
                ($reason ? " Reason: {$reason}" : ""),
            "cancelled" => "Consultation with {$booking->Student_Name} has been cancelled.",
            "today_reminder" => "You have a consultation today with {$booking->Student_Name}. Please accept or reschedule.",
            "completion_pending" => "Completion remarks sent to {$booking->Student_Name}. Awaiting student confirmation.",
            "completion_declined" =>
                "{$booking->Student_Name} declined your completion request." .
                ($reason ? " Student notes: {$reason}" : ""),
        ];

        $updatedCount = 0;

        // Find ALL notifications for this specific booking (both student and professor)
        $allNotifications = self::where("booking_id", $bookingId)->get();

        if ($allNotifications->isEmpty()) {
            // No notifications exist - create them for both student and professor

            // Create student notification
            self::create([
                "user_id" => $booking->Stud_ID,
                "booking_id" => $bookingId,
                "type" => $newType,
                "title" => $titles[$newType] ?? "Consultation Update",
                "message" =>
                    $studentMessages[$newType] ?? "Your consultation status has been updated.",
                "is_read" => false,
            ]);
            $updatedCount++;

            // Create professor notification
            self::create([
                "user_id" => $booking->Prof_ID,
                "booking_id" => $bookingId,
                "type" => $newType,
                "title" => $titles[$newType] ?? "Consultation Update",
                "message" =>
                    $professorMessages[$newType] ?? "Consultation status has been updated.",
                "is_read" => false,
            ]);
            $updatedCount++;
        } else {
            // Update existing notifications
            foreach ($allNotifications as $notification) {
                // Determine if this is a student or professor notification
                $isStudentNotification = DB::table("t_student")
                    ->where("Stud_ID", $notification->user_id)
                    ->exists();

                $message = $isStudentNotification
                    ? $studentMessages[$newType] ?? "Your consultation status has been updated."
                    : $professorMessages[$newType] ?? "Consultation status has been updated.";

                $notification->update([
                    "type" => $newType,
                    "title" => $titles[$newType] ?? "Consultation Update",
                    "message" => $message,
                    "is_read" => false,
                    "updated_at" => now(),
                ]);
                $updatedCount++;
            }

            // Check if we need to create missing notifications
            $hasStudentNotif =
                $allNotifications
                    ->filter(function ($n) {
                        return DB::table("t_student")->where("Stud_ID", $n->user_id)->exists();
                    })
                    ->count() > 0;

            $hasProfessorNotif =
                $allNotifications
                    ->filter(function ($n) {
                        return DB::table("professors")->where("Prof_ID", $n->user_id)->exists();
                    })
                    ->count() > 0;

            // Create missing student notification
            if (!$hasStudentNotif) {
                self::create([
                    "user_id" => $booking->Stud_ID,
                    "booking_id" => $bookingId,
                    "type" => $newType,
                    "title" => $titles[$newType] ?? "Consultation Update",
                    "message" =>
                        $studentMessages[$newType] ?? "Your consultation status has been updated.",
                    "is_read" => false,
                ]);
                $updatedCount++;
            }

            // Create missing professor notification
            if (!$hasProfessorNotif) {
                self::create([
                    "user_id" => $booking->Prof_ID,
                    "booking_id" => $bookingId,
                    "type" => $newType,
                    "title" => $titles[$newType] ?? "Consultation Update",
                    "message" =>
                        $professorMessages[$newType] ?? "Consultation status has been updated.",
                    "is_read" => false,
                ]);
                $updatedCount++;
            }
        }

        return $updatedCount > 0;
    }

    /**
     * Specialized updater when a STUDENT accepts a professor's reschedule.
     * Uses a distinct type so the professor sees an appropriate message.
     */
    public static function updateOnStudentAcceptReschedule(int $bookingId, string $date)
    {
        $booking = DB::table("t_consultation_bookings")
            ->leftJoin("t_student", "t_consultation_bookings.Stud_ID", "=", "t_student.Stud_ID")
            ->leftJoin("professors", "t_consultation_bookings.Prof_ID", "=", "professors.Prof_ID")
            ->select(
                "t_consultation_bookings.*",
                "t_student.Name as Student_Name",
                "professors.Name as Professor_Name",
            )
            ->where("Booking_ID", $bookingId)
            ->first();

        if (!$booking) {
            return false;
        }

        $type = "reschedule_accepted";
        $title = "Reschedule Accepted";
        $studentMsg = "Your consultation with {$booking->Professor_Name} is confirmed for {$date}.";
        $profMsg = "{$booking->Student_Name} accepted the reschedule to {$date}.";

        $all = self::where("booking_id", $bookingId)->get();

        if ($all->isEmpty()) {
            self::create([
                "user_id" => $booking->Stud_ID,
                "booking_id" => $bookingId,
                "type" => $type,
                "title" => $title,
                "message" => $studentMsg,
                "is_read" => false,
            ]);
            self::create([
                "user_id" => $booking->Prof_ID,
                "booking_id" => $bookingId,
                "type" => $type,
                "title" => $title,
                "message" => $profMsg,
                "is_read" => false,
            ]);
            return true;
        }

        foreach ($all as $n) {
            $isStudent = DB::table("t_student")->where("Stud_ID", $n->user_id)->exists();
            $n->update([
                "type" => $type,
                "title" => $title,
                "message" => $isStudent ? $studentMsg : $profMsg,
                "is_read" => false,
                "updated_at" => now(),
            ]);
        }
        // Ensure both sides exist
        $hasStudent = $all->first(function ($n) {
            return DB::table("t_student")->where("Stud_ID", $n->user_id)->exists();
        });
        $hasProf = $all->first(function ($n) {
            return DB::table("professors")->where("Prof_ID", $n->user_id)->exists();
        });
        if (!$hasStudent) {
            self::create([
                "user_id" => $booking->Stud_ID,
                "booking_id" => $bookingId,
                "type" => $type,
                "title" => $title,
                "message" => $studentMsg,
                "is_read" => false,
            ]);
        }
        if (!$hasProf) {
            self::create([
                "user_id" => $booking->Prof_ID,
                "booking_id" => $bookingId,
                "type" => $type,
                "title" => $title,
                "message" => $profMsg,
                "is_read" => false,
            ]);
        }
        return true;
    }

    /**
     * Update or create notification for a consultation booking
     * This ensures we don't create duplicates
     */
    public static function updateOrCreateConsultationNotification(
        $userId,
        $bookingId,
        $type,
        $professorName = null,
        $date = null,
        $reason = null,
    ) {
        $titles = [
            "booking_request" => "New Consultation Booking",
            "accepted" => "Consultation Accepted",
            "completed" => "Consultation Completed",
            "rescheduled" => "Consultation Rescheduled",
            "cancelled" => "Consultation Cancelled",
            "today_reminder" => 'Today\'s Consultation Reminder',
            "completion_pending" => "Completion Review Required",
            "completion_declined" => "Completion Declined",
        ];

        $messages = [
            "booking_request" => "Your consultation booking is pending approval.",
            "accepted" => "Your consultation with {$professorName} has been accepted for {$date}.",
            "completed" =>
                "Your consultation with {$professorName} has been completed." .
                ($reason ? " Remarks: {$reason}" : ""),
            "rescheduled" =>
                "Your consultation with {$professorName} has been rescheduled to {$date}." .
                ($reason ? " Reason: {$reason}" : ""),
            "cancelled" => "Your consultation with {$professorName} has been cancelled.",
            "today_reminder" => "Reminder: You have a consultation today with {$professorName}.",
            "completion_pending" =>
                "{$professorName} marked your consultation as completed." .
                ($reason ? " Remarks: {$reason}." : "") .
                " Please review and confirm.",
            "completion_declined" =>
                "You declined {$professorName}'s completion request." .
                ($reason ? " Notes: {$reason}." : ""),
        ];

        // Try to find existing notification for this booking and user
        $existingNotification = self::where("user_id", $userId)
            ->where("booking_id", $bookingId)
            ->first();

        if ($existingNotification) {
            // Update existing notification
            $existingNotification->update([
                "type" => $type,
                "title" => $titles[$type] ?? "Consultation Update",
                "message" => $messages[$type] ?? "Your consultation status has been updated.",
                "is_read" => false,
                "updated_at" => now(),
            ]);
            return $existingNotification;
        } else {
            // Create new notification
            return self::create([
                "user_id" => $userId,
                "booking_id" => $bookingId,
                "type" => $type,
                "title" => $titles[$type] ?? "Consultation Update",
                "message" => $messages[$type] ?? "Your consultation status has been updated.",
                "is_read" => false,
            ]);
        }
    }

    /**
     * Refresh existing professor & student notifications for a booking that is happening today
     * WITHOUT creating duplicates. Marks them unread and appends a short today tag once.
     * Only acts on notifications whose type is accepted or rescheduled (already approved flow).
     */
    public static function refreshTodayReminder(int $bookingId, string $date): void
    {
        $notifications = self::where("booking_id", $bookingId)
            ->whereIn("type", ["accepted", "rescheduled"])
            ->get();
        foreach ($notifications as $n) {
            $msg = $n->message;
            if (!str_contains($msg, "[Today]")) {
                // Lightweight tag so UI can highlight; keep original message text
                $msg .= " [Today]";
            }
            $n->update([
                "message" => $msg,
                "is_read" => false, // make it surface again
                "updated_at" => now(),
            ]);
        }
    }

    /**
     * Create a simple system notification for a user (no booking_id required).
     * Types are free-form strings (e.g., 'override_applied').
     */
    public static function createSystem(
        int $userId,
        string $title,
        string $message,
        string $type = "system",
    ) {
        return self::create([
            "user_id" => $userId,
            "booking_id" => null,
            "type" => $type,
            "title" => $title,
            "message" => $message,
            "is_read" => false,
        ]);
    }
}
