<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProfessorWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $professorName;
    public string $email;
    public string $tempPassword;
    public string $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $professorName,
        string $email,
        string $tempPassword,
        string $loginUrl,
    ) {
        $this->professorName = $professorName;
        $this->email = $email;
        $this->tempPassword = $tempPassword;
        $this->loginUrl = $loginUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("Your ASCC-IT Faculty Account Details")
            ->view("emails.professor-welcome")
            ->with([
                "professorName" => $this->professorName,
                "email" => $this->email,
                "tempPassword" => $this->tempPassword,
                "loginUrl" => $this->loginUrl,
            ]);
    }
}
