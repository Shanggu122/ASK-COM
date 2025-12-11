<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Professor;
use App\Mail\UpcomingConsultationReminder;

class SendTestConsultationReminder extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'consultations:test-email {bookingId? : Existing booking id (optional)} {--to= : Override destination email} {--all : Send test emails for ALL today\'s bookings (statuses: pending/approved/rescheduled)}';

    /**
     * The console command description.
     */
    protected $description = "Send a single test consultation reminder email with signed action links.";

    public function handle(): int
    {
        $bookingId = $this->argument("bookingId");
        $overrideTo = $this->option("to");
        $sendAll = (bool) $this->option("all");
        $today = Carbon::now("Asia/Manila")->format("D M d Y");

        $query = DB::table("t_consultation_bookings as b")
            ->join("t_student as s", "s.Stud_ID", "=", "b.Stud_ID")
            ->join("t_subject as subj", "subj.Subject_ID", "=", "b.Subject_ID")
            ->leftJoin("t_consultation_types as ct", "ct.Consult_type_ID", "=", "b.Consult_type_ID")
            ->select([
                "b.Booking_ID",
                "b.Prof_ID",
                "b.Booking_Date",
                "b.Custom_Type",
                "b.Consult_type_ID",
                "b.Status",
                "s.Name as student_name",
                "subj.Subject_Name as subject_name",
                "ct.Consult_Type as consult_type",
            ])
            ->whereIn("b.Status", ["pending", "approved", "rescheduled"]);

        if ($bookingId && $sendAll) {
            $this->warn(
                "Both bookingId and --all supplied. Ignoring bookingId and sending all for today.",
            );
        }

        if ($bookingId && !$sendAll) {
            $query->where("b.Booking_ID", $bookingId);
        } elseif ($sendAll) {
            $query->where("b.Booking_Date", $today);
        } else {
            $query->where("b.Booking_Date", $today);
        }
        $bookings = $sendAll ? $query->get() : collect([$query->first()]);

        if ($bookings->filter()->isEmpty()) {
            $this->error(
                "No matching booking" .
                    ($sendAll ? "s" : "") .
                    " found " .
                    ($bookingId && !$sendAll ? "for ID " . $bookingId : "for today") .
                    " (statuses: pending/approved/rescheduled).",
            );
            return Command::FAILURE;
        }

        $sent = 0;
        $failed = 0;
        foreach ($bookings as $booking) {
            if (!$booking) {
                continue;
            }
            $prof = Professor::find($booking->Prof_ID);
            if (!$prof || !$prof->Email) {
                $this->warn(
                    "Skipping booking " . $booking->Booking_ID . " (missing professor email).",
                );
                $failed++;
                continue;
            }
            $to = $overrideTo ?: $prof->Email;
            // Only allow gmail.com recipients now.
            if (!preg_match('/@gmail\.com$/i', $to)) {
                $this->warn(
                    "Skipping booking " . $booking->Booking_ID . " (non-Gmail: " . $to . ").",
                );
                continue;
            }
            $typeName = $booking->Custom_Type ?: ($booking->consult_type ?: "consultation");
            Log::info("[TestReminderEmail] Sending test reminder", [
                "booking_id" => $booking->Booking_ID,
                "prof_id" => $booking->Prof_ID,
                "to" => $to,
                "date" => $booking->Booking_Date,
                "type" => $typeName,
            ]);
            try {
                Mail::to($to)->send(
                    new UpcomingConsultationReminder(
                        $booking->student_name,
                        $booking->subject_name,
                        $typeName,
                        $booking->Booking_Date,
                        $booking->Booking_ID,
                        $booking->Prof_ID,
                        $prof->Name ?? null,
                    ),
                );
                $this->info("Sent booking " . $booking->Booking_ID . " to " . $to);
                $sent++;
            } catch (\Exception $e) {
                Log::error("[TestReminderEmail] Failed", [
                    "booking_id" => $booking->Booking_ID,
                    "error" => $e->getMessage(),
                ]);
                $this->error(
                    "Send failed for booking " . $booking->Booking_ID . ": " . $e->getMessage(),
                );
                $failed++;
            }
        }

        $this->line("Summary: sent=$sent failed=$failed");
        $this->line(
            "Use --all to send all for today, or provide a specific bookingId. Use --to=you@example.com to override destination.",
        );
        return $sent > 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
