<?php

namespace App\Support;

use App\Models\Booking;

/** Adds up what has been paid on a booking, so "paid so far" and "still to pay" always agree. */
final class PaymentLedger
{
    /** Payments that count as received money. */
    private const RECEIVED = ['confirmed', 'paid'];

    public static function paid(Booking $booking): float
    {
        return round((float) $booking->payments()->whereIn('payStatus', self::RECEIVED)->sum('amount'), 2);
    }

    public static function outstanding(Booking $booking): float
    {
        return max(0.0, round((float) $booking->total_amount - self::paid($booking), 2));
    }
}
