<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpMail extends Mailable
{
    public function __construct(public string $code, public int $minutes = 15, public string $purpose = 'verification')
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->purpose === 'password reset' ? 'Your password reset code' : 'Your OTP Code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp');
    }
}
