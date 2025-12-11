<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $studentName;
    public string $email;
    public string $tempPassword;
    public string $loginUrl;

    public function __construct(
        string $studentName,
        string $email,
        string $tempPassword,
        string $loginUrl,
    ) {
        $this->studentName = $studentName;
        $this->email = $email;
        $this->tempPassword = $tempPassword;
        $this->loginUrl = $loginUrl;
    }

    public function build()
    {
        return $this->subject("Your ASCC-IT Student Account Details")
            ->view("emails.student-welcome")
            ->with([
                "studentName" => $this->studentName,
                "email" => $this->email,
                "tempPassword" => $this->tempPassword,
                "loginUrl" => $this->loginUrl,
            ]);
    }
}
