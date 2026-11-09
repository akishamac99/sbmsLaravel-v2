<?php

namespace App\Actions\Payments;

use App\Exceptions\BusinessRuleViolation;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\SystemLog;
use App\Models\UserNotification;
use App\Support\BookingAccess;
use App\Support\PaymentLedger;
use Illuminate\Support\Facades\DB;

/** Record Payment: the salon records money received for a booking (a down payment, the balance, or a full payment). */
final class RecordPayment
{
    /** @param array{amount: float|string, method: string, reference?: ?string} $data */
    public function handle(Staff $actor, Booking $booking, array $data): Payment
    {
        if (! BookingAccess::canRecordPayment($actor)) {
            throw new BusinessRuleViolation('You are not allowed to record payments.');
        }

        $payment = DB::transaction(function () use ($booking, $data): Payment {
            $fresh = Booking::whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($fresh->bookingStatus, ['pending', 'approved', 'completed'], true)) {
                throw new BusinessRuleViolation('Payments can only be recorded for pending, approved or completed bookings.');
            }

            $amount = round((float) $data['amount'], 2);
            $outstanding = PaymentLedger::outstanding($fresh);

            if ($amount <= 0) {
                throw new BusinessRuleViolation('Enter an amount greater than zero.');
            }

            if ($amount > $outstanding) {
                throw new BusinessRuleViolation('That is more than the remaining balance of PHP '.number_format($outstanding, 2).'.');
            }

            $payment = Payment::create([
                'bookingID' => $fresh->getKey(),
                'payType' => $data['method'],
                'amount' => $amount,
                'payStatus' => 'paid',
                'referenceNo' => ($data['reference'] ?? null) ?: 'REC'.now()->format('YmdHis').$fresh->getKey(),
                'payDate' => now(),
            ]);

            $fresh->update(['paymentStatus' => PaymentLedger::outstanding($fresh) <= 0 ? 'paid' : 'confirmed']);

            if ($fresh->customer && ! $fresh->is_walkin) {
                UserNotification::send('customer', (int) $fresh->customerID, 'Payment Recorded', 'A payment of PHP '.number_format($amount, 2)." was recorded for booking {$fresh->bookingRef}.");
            }

            return $payment;
        });

        SystemLog::record($actor->role_slug, (int) $actor->getKey(), 'record_payment', "Recorded PHP {$payment->amount} for booking {$booking->bookingRef}");

        return $payment;
    }
}
