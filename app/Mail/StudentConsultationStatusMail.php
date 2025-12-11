<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentConsultationStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $studentName;
    public $professorName;
    public $status;
    public $date;
    public $reason;

    public function __construct($studentName,$professorName,$status,$date,$reason=null)
    {
        $this->studentName = $studentName;
        $this->professorName = $professorName;
        $this->status = $status; // accepted | rescheduled
        $this->date = $date;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('Consultation Status Update')
            ->view('emails.student-consultation-status');
    }
}
