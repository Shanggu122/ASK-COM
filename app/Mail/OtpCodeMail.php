<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $userType;
    public string $name;

    public function __construct(string $otp, string $userType, string $name)
    {
        $this->otp = $otp;
        $this->userType = $userType;
        $this->name = $name;
    }

    public function build()
    {
        return $this->subject("Your Password Reset OTP Code")->view("emails.otp");
    }
}
