<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UpcomingConsultationReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $studentName;
    public $subjectName;
    public $typeName;
    public $bookingDate;
    public $bookingId;
    public $profId;
    public $professorName;

    public function __construct($studentName, $subjectName, $typeName, $bookingDate, $bookingId, $profId, $professorName = null)
    {
        $this->studentName = $studentName;
        $this->subjectName = $subjectName;
        $this->typeName = $typeName;
        $this->bookingDate = $bookingDate;
        $this->bookingId = $bookingId;
        $this->profId = $profId;
        $this->professorName = $professorName;
    }

    public function build()
    {
        return $this->subject('Consultation – Starts In 1 Hour')
            ->view('emails.consultation-today-reminder');
    }
}
