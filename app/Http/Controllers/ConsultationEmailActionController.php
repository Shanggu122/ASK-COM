<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Notification;
use App\Mail\StudentConsultationStatusMail;

class ConsultationEmailActionController extends Controller
{
    protected function findBooking($bookingId, $profId)
    {
        return DB::table('t_consultation_bookings as b')
            ->join('t_student as s','s.Stud_ID','=','b.Stud_ID')
            ->join('professors as p','p.Prof_ID','=','b.Prof_ID')
            ->leftJoin('t_subject as subj','subj.Subject_ID','=','b.Subject_ID')
            ->select('b.*','s.Name as student_name','s.Email as student_email','p.Name as professor_name','p.Email as professor_email','p.Schedule as professor_schedule','subj.Subject_Name as subject_name')
            ->where('b.Booking_ID',$bookingId)
            ->where('b.Prof_ID',$profId)
            ->first();
    }

    public function accept(Request $request, $bookingId, $profId)
    {
        $booking = $this->findBooking($bookingId, $profId);
        if(!$booking) return response()->view('consultation_email_actions.result',[ 'title'=>'Invalid Link', 'message'=>'Booking not found or link invalid.' ],404);

        // Update if not already completed/cancelled; enforce mode lock if another approved/rescheduled exists that day
        if(!in_array(strtolower($booking->Status), ['approved','completed'])) {
            $capacityStatuses = ['approved','rescheduled'];
            $firstExisting = DB::table('t_consultation_bookings')
                ->where('Prof_ID', $booking->Prof_ID)
                ->where('Booking_Date', $booking->Booking_Date)
                ->whereIn('Status', $capacityStatuses)
                ->where('Booking_ID','!=',$booking->Booking_ID)
                ->orderBy('Booking_ID','asc')
                ->first();
            if ($firstExisting && $firstExisting->Mode && $firstExisting->Mode !== $booking->Mode) {
                return response()->view('consultation_email_actions.result',[ 'title'=>'Mode Conflict', 'message'=>'Cannot accept: the date is locked to '.ucfirst($firstExisting->Mode).' mode.' ]);
            }
            DB::table('t_consultation_bookings')->where('Booking_ID',$bookingId)->update(['Status'=>'approved']);
            try {
                Notification::updateNotificationStatus($bookingId,'accepted',$booking->professor_name,$booking->Booking_Date,null);
            } catch(\Exception $e) { /* ignore */ }
            // Email student
            if($booking->student_email) {
                Mail::to($booking->student_email)->send(new StudentConsultationStatusMail(
                    $booking->student_name,
                    $booking->professor_name,
                    'accepted',
                    $booking->Booking_Date,
                    null
                ));
            }
        }
        return response()->view('consultation_email_actions.result',[ 'title'=>'Consultation Accepted', 'message'=>'The consultation has been marked as accepted. The student has been notified.' ]);
    }

    public function rescheduleForm(Request $request, $bookingId, $profId)
    {
        $booking = $this->findBooking($bookingId, $profId);
        if(!$booking) return response()->view('consultation_email_actions.result',[ 'title'=>'Invalid Link', 'message'=>'Booking not found or link invalid.' ],404);

        // Parse professor weekly schedule to determine allowed weekdays
        $allowedWeekdays = []; // numeric 0 (Sun) .. 6 (Sat)
        if(!empty($booking->professor_schedule) && strtolower($booking->professor_schedule) !== 'no schedule set') {
            $lines = preg_split('/\r?\n/', $booking->professor_schedule);
            $map = [ 'sun'=>0,'mon'=>1,'tue'=>2,'wed'=>3,'thu'=>4,'fri'=>5,'sat'=>6 ];
            foreach($lines as $line){
                $line = trim($line);
                if($line==='') continue;
                if(preg_match('/^([A-Za-z]{3,9})/', $line, $m)) {
                    $key = strtolower(substr($m[1],0,3));
                    if(isset($map[$key])) $allowedWeekdays[$map[$key]] = true;
                }
            }
        }

        // Build list of upcoming allowed dates (next 21 days, cap 12 options)
        $allowedDates = [];
        if(!empty($allowedWeekdays)) {
            $now = Carbon::now('Asia/Manila')->startOfDay();
            for($i=0; $i<21 && count($allowedDates) < 12; $i++) {
                $dt = $now->copy()->addDays($i);
                if(isset($allowedWeekdays[$dt->dayOfWeek])) {
                    $allowedDates[] = [
                        'iso' => $dt->format('Y-m-d'),
                        'display' => $dt->format('D, M d')
                    ];
                }
            }
        }

        return view('consultation_email_actions.reschedule-form',[ 'booking'=>$booking, 'allowedDates'=>$allowedDates ]);
    }

    public function rescheduleSubmit(Request $request, $bookingId, $profId)
    {
        $booking = $this->findBooking($bookingId, $profId);
        if(!$booking) return response()->view('consultation_email_actions.result',[ 'title'=>'Invalid Link', 'message'=>'Booking not found or link invalid.' ],404);

        $request->validate([
            'new_date' => 'required|string|max:50',
            'reason' => 'nullable|string|max:255'
        ]);

        $raw = trim($request->input('new_date'));
        $carbon = null; $formats=['D M d Y','D M d Y H:i','Y-m-d'];
        foreach($formats as $fmt){ try { $carbon = Carbon::createFromFormat($fmt, $raw,'Asia/Manila'); break; } catch(\Exception $e) {} }
        if(!$carbon){ try { $carbon = Carbon::parse($raw,'Asia/Manila'); } catch(\Exception $e) { $carbon=null; } }
        if(!$carbon) return back()->withErrors(['new_date'=>'Invalid date format. Use e.g. Thu Sep 05 2025']);
        $carbon = $carbon->setTimezone('Asia/Manila')->startOfDay();
        $formatted = $carbon->format('D M d Y');

        DB::table('t_consultation_bookings')->where('Booking_ID',$bookingId)->update([
            'Booking_Date'=>$formatted,
            'Status'=>'rescheduled',
            'reschedule_reason'=>$request->input('reason')
        ]);
        try {
            Notification::updateNotificationStatus($bookingId,'rescheduled',$booking->professor_name,$formatted,$request->input('reason'));
        } catch(\Exception $e) {}
        if($booking->student_email) {
            Mail::to($booking->student_email)->send(new StudentConsultationStatusMail(
                $booking->student_name,
                $booking->professor_name,
                'rescheduled',
                $formatted,
                $request->input('reason')
            ));
        }
        return response()->view('consultation_email_actions.result',[ 'title'=>'Consultation Rescheduled', 'message'=>'The consultation has been rescheduled and the student informed.' ]);
    }
}
