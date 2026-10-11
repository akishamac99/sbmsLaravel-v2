<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\OtpToken;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public const VALIDITY_MINUTES = 15;
    public const MAX_ATTEMPTS = 3;

    // Make a new 6-digit code for this email and save it. Any older unused code is removed first.
    public function generate(string $contact, string $type = 'registration'): string
    {
        $this->clear($contact);

        $code = (string) random_int(100000, 999999);

        OtpToken::create([
            'contact_number' => $contact,
            'otp_code' => $code,
            'otp_type' => $type,
            'expires_at' => now()->addMinutes(self::VALIDITY_MINUTES),
        ]);

        return $code;
    }

    // Check a code the user typed. Returns ['success' => bool, 'message' => string].
    public function verify(string $contact, string $code): array
    {
        $record = OtpToken::where('contact_number', $contact)
            ->where('is_used', 0)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record) {
            return ['success' => false, 'message' => 'OTP has expired or not found. Please request a new one.'];
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            return ['success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.'];
        }

        $storedCode = (string) $record->otp_code;
        $submittedCode = trim((string) $code);

        if (! hash_equals($storedCode, $submittedCode)) {
            $record->increment('attempts');

            return ['success' => false, 'message' => 'Incorrect OTP. Please try again.'];
        }

        $record->update(['is_used' => 1, 'used_at' => now()]);

        return ['success' => true, 'message' => 'OTP verified successfully.'];
    }

    // Delete this email's unused codes.
    public function clear(string $contact): void
    {
        OtpToken::where('contact_number', $contact)->where('is_used', 0)->delete();
    }

    // Email the code.
    public function send(string $email, string $code): void
    {
        Mail::to($email)->send(new OtpMail($code, self::VALIDITY_MINUTES));
    }
}
