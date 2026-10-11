<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\OtpToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterOtpDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_the_otp_during_the_request(): void
    {
        Mail::fake();

        $response = $this->post(route('register.send-otp'), $this->registrationData());

        $response->assertRedirect(route('register'));
        $response->assertSessionHas('status', 'OTP sent to your email. Please verify below.');
        $response->assertSessionHas('reg.email', 'customer@example.test');

        $sentCode = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$sentCode): bool {
            $sentCode = $mail->code;

            return $mail->hasTo('customer@example.test');
        });

        $this->assertDatabaseHas('otp_storage', [
            'contact_number' => 'customer@example.test',
            'otp_code' => $sentCode,
            'otp_type' => 'registration',
        ]);
    }

    public function test_resend_sends_a_new_otp_immediately_and_invalidates_the_previous_one(): void
    {
        Mail::fake();

        $this->post(route('register.send-otp'), $this->registrationData());
        $previousCode = OtpToken::firstOrFail()->otp_code;

        $this->from(route('register'))
            ->post(route('register.resend'))
            ->assertRedirect(route('register'))
            ->assertSessionHas('status', 'A new OTP has been sent.');

        Mail::assertSent(OtpMail::class, 2);
        $this->assertDatabaseCount('otp_storage', 1);
        $this->assertNotSame($previousCode, OtpToken::firstOrFail()->otp_code);
    }

    private function registrationData(): array
    {
        return [
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@example.test',
            'contact_number' => '09123456789',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ];
    }
}