<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Mail\UpcomingConsultationReminder;

class PreviewUpcomingConsultationReminder extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature =
        "consultations:preview-upcoming " .
        "{--to= : Destination email address. If omitted with --dump, no email is sent.}" .
        " {--student=Sample Student : Student name}" .
        " {--subject=Sample Subject : Subject / course name}" .
        " {--type=consultation : Consultation type label}" .
        " {--date= : Booking date (format: D M d Y). Defaults to today in Asia/Manila}" .
        " {--booking=999999 : Booking ID placeholder}" .
        " {--prof=888888 : Professor ID placeholder}" .
        " {--profName=Professor Sample : Professor display name}" .
        " {--use-booking= : Load a REAL booking by ID (overrides student/subject/type/date/booking/prof/profName)}" .
        " {--dump : Output the rendered HTML to console instead of sending}" .
        " {--send-and-dump : Send (if --to provided) AND output HTML}";

    /**
     * The console command description.
     */
    protected $description = "Preview or send a sample upcoming (1-hour) consultation reminder email.";

    public function handle(): int
    {
        $to = $this->option("to");
        $student = $this->option("student");
        $subjectName = $this->option("subject");
        $typeName = $this->option("type");
        $bookingDate = $this->option("date") ?: Carbon::now("Asia/Manila")->format("D M d Y");
        $bookingId = $this->option("booking");
        $profId = $this->option("prof");
        $profName = $this->option("profName");
        $dump = (bool) $this->option("dump");
        $sendAndDump = (bool) $this->option("send-and-dump");

        // If a real booking is requested, fetch and override fields so links work.
        if ($realId = $this->option("use-booking")) {
            $row = DB::table("t_consultation_bookings as b")
                ->leftJoin("t_student as s", "s.Stud_ID", "=", "b.Stud_ID")
                ->leftJoin("t_subject as subj", "subj.Subject_ID", "=", "b.Subject_ID")
                ->leftJoin("professors as p", "p.Prof_ID", "=", "b.Prof_ID")
                ->select(
                    "b.Booking_ID",
                    "b.Booking_Date",
                    "b.Prof_ID",
                    "s.Name as student_name",
                    "subj.Subject_Name as subject_name",
                    "p.Name as professor_name",
                )
                ->where("b.Booking_ID", $realId)
                ->first();
            if (!$row) {
                $this->error("Real booking ID " . $realId . " not found.");
                return Command::FAILURE;
            }
            $student = $row->student_name ?: $student;
            $subjectName = $row->subject_name ?: $subjectName;
            $bookingDate = $row->Booking_Date ?: $bookingDate;
            $bookingId = $row->Booking_ID;
            $profId = $row->Prof_ID ?: $profId;
            $profName = $row->professor_name ?: $profName;
            $this->info(
                "Loaded real booking " . $bookingId . " (Prof " . $profId . ") for preview.",
            );
        }

        $mailable = new UpcomingConsultationReminder(
            $student,
            $subjectName,
            $typeName,
            $bookingDate,
            $bookingId,
            $profId,
            $profName,
        );

        if (!$to && !$dump && !$sendAndDump) {
            $this->error(
                "Provide --to=address or use --dump to just preview. Tip: use --use-booking=ID for valid action links.",
            );
            return Command::FAILURE;
        }

        // Send email if destination provided
        if ($to) {
            if (!preg_match('/@gmail\.com$/i', $to)) {
                $this->warn(
                    "Non-Gmail address detected (" .
                        $to .
                        "). Email will not be sent. Use a @gmail.com address or --dump for preview.",
                );
            } else {
                try {
                    Mail::to($to)->send($mailable);
                    $this->info("Sample email sent to " . $to);
                } catch (\Exception $e) {
                    $this->error("Failed sending email: " . $e->getMessage());
                    return Command::FAILURE;
                }
            }
        }

        if ($dump || $sendAndDump) {
            $this->line(str_repeat("-", 40));
            $this->line("Rendered HTML Preview:");
            $this->line(str_repeat("-", 40));
            try {
                $this->output->writeln($mailable->render());
            } catch (\Exception $e) {
                $this->error("Failed rendering mailable: " . $e->getMessage());
                return Command::FAILURE;
            }
            $this->line(str_repeat("-", 40));
        }

        $this->line(
            "Done. Customize content with options: --student --subject --type --date --booking --prof --profName OR use --use-booking=ID for real links.",
        );
        return Command::SUCCESS;
    }
}
