<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Payments\RecordPayment;
use App\Exceptions\BusinessRuleViolation;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Record Payment, from the booking details page. */
class RecordPaymentController extends Controller
{
    public function __invoke(Request $request, Booking $booking, RecordPayment $record): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'method' => ['required', 'in:cash,gcash,bank_transfer'],
            'reference' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $record->handle(Auth::guard('staff')->user(), $booking, $validated);
        } catch (BusinessRuleViolation $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Payment recorded.');
    }
}
