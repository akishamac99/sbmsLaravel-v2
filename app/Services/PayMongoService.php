<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\SystemLog;
use App\Models\UserNotification;
use DomainException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Talks to PayMongo: starts a hosted checkout, checks whether it was paid, and verifies webhooks.
class PayMongoService
{
    private const BASE = 'https://api.paymongo.com/v1';

    private function client(): PendingRequest
    {
        $key = config('services.paymongo.secret');

        if (! $key) {
            throw new DomainException('Online payment is not set up yet.');
        }

        // PayMongo uses HTTP Basic auth: the secret key is the username, the password is empty.
        return Http::withBasicAuth($key, '')->acceptJson()->asJson()->timeout(20);
    }

    /**
     * Creates a PayMongo checkout page for this payment.
     *
     * @return array{id: string, url: string}
     */
    public function createSession(Booking $booking, Payment $payment, string $successUrl, string $cancelUrl): array
    {
        // PayMongo wants whole centavos: PHP 500.00 is 50000.
        $centavos = (int) round($booking->amount_due * 100);

        if ($centavos <= 0) {
            throw new DomainException('Nothing to pay for this booking.');
        }

        $label = $booking->payment_type === 'partial' ? 'Down payment' : 'Full payment';

        $response = $this->client()->post(self::BASE.'/checkout_sessions', ['data' => ['attributes' => [
            'line_items' => [[
                'currency' => 'PHP',
                'amount' => $centavos,
                'name' => mb_substr($booking->service_name.' - '.$label, 0, 120),
                'quantity' => 1,
            ]],
            'payment_method_types' => array_values(config('services.paymongo.methods')),
            'description' => 'Booking #'.$booking->bookingRef,
            'reference_number' => $booking->bookingRef,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'send_email_receipt' => false,
            'metadata' => [
                'booking_ref' => $booking->bookingRef,
                'pay_id' => (string) $payment->getKey(),
            ],
        ]]]);

        $id = $response->json('data.id');
        $url = $response->json('data.attributes.checkout_url');

        if ($response->failed() || ! $id || ! $url) {
            Log::error('PayMongo could not create a checkout session', ['status' => $response->status(), 'body' => $response->json()]);

            throw new DomainException('Could not start the online payment. Please try again, or send a payment proof instead.');
        }

        return ['id' => $id, 'url' => $url];
    }

    // Asks PayMongo whether a checkout session has a paid payment. Used when the customer returns,
    // and as a safety net because PayMongo does not re-send a webhook that was missed.
    public function sessionIsPaid(string $sessionId): bool
    {
        $response = $this->client()->get(self::BASE.'/checkout_sessions/'.$sessionId);

        if ($response->failed()) {
            return false;
        }

        foreach ((array) $response->json('data.attributes.payments', []) as $payment) {
            if (($payment['attributes']['status'] ?? null) === 'paid') {
                return true;
            }
        }

        return false;
    }

    // Marks the payment and its booking as paid. Safe to call twice: the second call does nothing.
    public function markPaid(Payment $payment): void
    {
        $booking = null;

        DB::transaction(function () use ($payment, &$booking): void {
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->first();

            if (! $locked || $locked->payStatus === 'paid') {
                return;
            }

            $locked->update(['payStatus' => 'paid', 'payDate' => now()]);

            $booking = Booking::find($locked->bookingID);
            $booking?->update(['paymentStatus' => 'paid']);

            if ($booking) {
                UserNotification::send('customer', (int) $booking->customerID, 'Payment Received', 'Your online payment for booking #'.$booking->bookingRef.' was received. Thank you!');

                Staff::whereIn('role', ['Owner', 'Manager'])->where('is_active', 1)->get()->each(
                    fn (Staff $staff) => UserNotification::send('staff', (int) $staff->getKey(), 'Online Payment', 'Booking #'.$booking->bookingRef.' was paid online.')
                );
            }
        });

        if ($booking) {
            SystemLog::record('customer', (int) $booking->customerID, 'online_payment', "Paid booking #{$booking->bookingRef} online (PayMongo)");
        }
    }

    // Checks the Paymongo-Signature header: "t=<time>,te=<test signature>,li=<live signature>".
    // The signature is HMAC-SHA256 of "<time>.<raw request body>" using the webhook secret.
    public function validSignature(string $rawBody, ?string $header): bool
    {
        $secret = config('services.paymongo.webhook_secret');

        if (! $secret || ! $header) {
            return false;
        }

        $parts = [];

        foreach (explode(',', $header) as $piece) {
            [$key, $value] = array_pad(explode('=', trim($piece), 2), 2, null);
            $parts[$key] = $value;
        }

        $time = $parts['t'] ?? null;

        // Reject very old requests so a captured one cannot be replayed later.
        if (! $time || ! ctype_digit($time) || abs(time() - (int) $time) > 600) {
            return false;
        }

        $expected = hash_hmac('sha256', $time.'.'.$rawBody, $secret);

        foreach (['te', 'li'] as $field) {
            if (! empty($parts[$field]) && hash_equals($expected, $parts[$field])) {
                return true;
            }
        }

        return false;
    }
}
