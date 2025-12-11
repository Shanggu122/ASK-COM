<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Professor;
use App\Models\Notification;
use App\Mail\UpcomingConsultationReminder;

class SendUpcomingConsultationReminders extends Command
{
    protected $signature = 'consultations:remind-upcoming {--debug : Output detailed per-booking evaluation info}';
    protected $description = 'Send reminder emails 1 hour before consultation start based on professor schedule time.';

    public function handle(): int
    {
    $now = Carbon::now('Asia/Manila');
    $debug = (bool)$this->option('debug');

        // We only have Booking_Date (whole day). Need to derive the time window from professor schedule text.
        // Strategy: parse professor Schedule lines for the weekday; assume booking uses the FIRST time range on that weekday.
        // This lets us approximate a start time (range start) for reminder.

    $weekday = $now->format('l'); // e.g. Monday
    $todayDateStr = $now->format('D M d Y');      // e.g. Fri Sep 05 2025
    $todayDateStrNoPad = $now->format('D M j Y'); // e.g. Fri Sep 5 2025 (in case stored without leading zero)

        $bookingsQuery = DB::table('t_consultation_bookings as b')
            ->join('professors as p','p.Prof_ID','=','b.Prof_ID')
            ->join('t_student as s','s.Stud_ID','=','b.Stud_ID')
            ->leftJoin('t_subject as subj','subj.Subject_ID','=','b.Subject_ID')
            ->leftJoin('t_consultation_types as ct','ct.Consult_type_ID','=','b.Consult_type_ID')
            ->select([
                'b.Booking_ID','b.Prof_ID','b.Booking_Date','b.Status','b.Custom_Type','b.Consult_type_ID','b.one_hour_reminder_sent_at',
                'p.Schedule','p.Email','p.Name as professor_name',
                's.Name as student_name',
                'subj.Subject_Name as subject_name',
                'ct.Consult_Type as consult_type'
            ])
            ->whereIn('b.Booking_Date',[$todayDateStr,$todayDateStrNoPad])
            ->whereIn('b.Status',['approved','rescheduled'])
            ->whereNull('b.one_hour_reminder_sent_at')
            ;

        $bookings = $bookingsQuery->get();
        if($debug && $bookings->isEmpty()) {
            $this->warn('[DEBUG] No bookings matched date. Tried: "'.$todayDateStr.'" and "'.$todayDateStrNoPad.'"');
            // As fallback for debugging, peek at any approved/rescheduled today-year bookings to show developer.
            $nearby = DB::table('t_consultation_bookings')
                ->whereIn('Status',[ 'approved','rescheduled'])
                ->where('Booking_Date','like','% '.$now->format('Y'))
                ->orderByDesc('Booking_ID')
                ->limit(5)->get(['Booking_ID','Booking_Date','Status']);
            foreach($nearby as $n){ $this->line('[DEBUG] Nearby booking: ID='.$n->Booking_ID.' Date="'.$n->Booking_Date.'" Status='.$n->Status); }
        }

        $sent = 0; $skipped = 0;
        if($debug) $this->line('[DEBUG] Evaluating '.count($bookings).' booking(s) at '.$now->format('H:i:s'));
        foreach($bookings as $booking){
            $startTime = $this->extractStartTimeForWeekday($booking->Schedule, $weekday);
            if(!$startTime){
                if($debug) $this->warn('[DEBUG]['.$booking->Booking_ID.'] No start time parsed from schedule; skipping.');
                $skipped++; continue; // cannot derive a time
            }
            $startDateTime = Carbon::createFromFormat('D M d Y H:i',''.$booking->Booking_Date.' '.$startTime,'Asia/Manila');
            if(!$startDateTime){ if($debug) $this->warn('[DEBUG]['.$booking->Booking_ID.'] Failed to create startDateTime.'); $skipped++; continue; }
            // Grace window: fire once when diff is between 60 and 55 minutes (inclusive) before start.
            // Rationale: scheduler or approval timing might miss the exact 60 mark; still helpful up to 5 min late.
            // If diff < 55 we skip permanently (too close to start per requirements).
            $diffMinutes = $now->diffInMinutes($startDateTime, false); // positive if future, negative if past
            if($debug) $this->line('[DEBUG]['.$booking->Booking_ID.'] diff='.$diffMinutes.' start='.$startDateTime->format('H:i').' status='.$booking->Status.' email='.$booking->Email);
            if($diffMinutes <= 60 && $diffMinutes >= 55){
                $prof = Professor::find($booking->Prof_ID);
                if(!$prof || !$prof->Email) { if($debug) $this->warn('[DEBUG]['.$booking->Booking_ID.'] Missing professor email.'); $skipped++; continue; }
                // Only send to Gmail addresses per new requirement. Skip all others.
                if (!preg_match('/@gmail\.com$/i', $prof->Email)) { if($debug) $this->warn('[DEBUG]['.$booking->Booking_ID.'] Non-gmail ('.$prof->Email.') skipping.'); $skipped++; continue; }
                $typeName = $booking->Custom_Type ?: ($booking->consult_type ?: 'consultation');
                try{
                    Mail::to($prof->Email)->send(new UpcomingConsultationReminder(
                        $booking->student_name,
                        $booking->subject_name,
                        $typeName,
                        $booking->Booking_Date,
                        $booking->Booking_ID,
                        $booking->Prof_ID,
                        $prof->Name ?? null
                    ));
                    Notification::refreshTodayReminder($booking->Booking_ID, $booking->Booking_Date);
                    DB::table('t_consultation_bookings')->where('Booking_ID',$booking->Booking_ID)->update(['one_hour_reminder_sent_at'=>Carbon::now('Asia/Manila')]);
                    $sent++;
                    if($debug) $this->info('[DEBUG]['.$booking->Booking_ID.'] SENT');
                }catch(\Exception $e){
                    Log::warning('[UpcomingReminder] failed send', ['booking_id'=>$booking->Booking_ID,'error'=>$e->getMessage()]);
                    if($debug) $this->error('[DEBUG]['.$booking->Booking_ID.'] Send failed: '.$e->getMessage());
                }
            } else {
                if($debug) $this->line('[DEBUG]['.$booking->Booking_ID.'] Outside window (diff='.$diffMinutes.').');
                $skipped++;
            }
        }

        $this->info("1-hour reminders sent: $sent skipped: $skipped");
        return Command::SUCCESS;
    }

    private function extractStartTimeForWeekday(?string $scheduleText, string $weekday): ?string
    {
        if(!$scheduleText) return null;
        // Expect lines like: Monday: 11:11 PM-11:22 PM
        $lines = preg_split('/\r?\n/',$scheduleText);
        foreach($lines as $line){
            $line = trim($line);
            if(stripos($line,$weekday.':')===0){
                // capture first time range start
                if(preg_match('/(\d{1,2}:\d{2} ?[AP]M)\s*-\s*(\d{1,2}:\d{2} ?[AP]M)/i',$line,$m)){
                    // Normalize to 24h for Carbon parse use intermediate
                    $start = Carbon::parse($m[1],'Asia/Manila')->format('H:i');
                    return $start; // return 24h H:i
                }
            }
        }
        return null;
    }
}
