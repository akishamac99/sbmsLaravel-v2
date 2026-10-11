<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams a payment screenshot to the people allowed to see it.
 *
 * Proofs are read straight from the "public" disk, so they display even when
 * `php artisan storage:link` has not been run, and only the booking's customer,
 * the owner, permitted managers and the assigned staff member can open them.
 */
class PaymentProofFileController extends Controller
{
    public function customer(Payment $payment): Response
    {
        abort_unless((int) $payment->booking?->customerID === (int) Auth::guard('customer')->id(), 404);

        return $this->stream($payment);
    }

    public function staff(Payment $payment): Response
    {
        $user = Auth::guard('staff')->user();

        $allowed = match ($user->role_slug) {
            'owner' => true,
            'manager' => Permission::allows('Manager', 'payments', 'view') || Permission::allows('Manager', 'bookings', 'view'),
            default => (int) $payment->booking?->staffID === (int) $user->getKey(),
        };

        abort_unless($allowed, 403);

        return $this->stream($payment);
    }

    private function stream(Payment $payment): Response
    {
        $path = ltrim((string) $payment->payment_proof, '/');
        $path = preg_replace('#^storage/#', '', $path);

        abort_if($path === '' || str_contains($path, '..') || ! Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
