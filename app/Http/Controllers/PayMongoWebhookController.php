<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// PayMongo calls this URL when a payment happens. No login is involved, so the signature is the proof.
class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PayMongoService $paymongo): JsonResponse
    {
        if (! $paymongo->validSignature($request->getContent(), $request->header('Paymongo-Signature'))) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        if ($request->input('data.attributes.type') === 'checkout_session.payment.paid') {
            $sessionId = $request->input('data.attributes.data.id');
            $payment = $sessionId ? Payment::where('gateway_ref', $sessionId)->first() : null;

            if ($payment) {
                $paymongo->markPaid($payment);
            }
        }

        // Always answer 200 so PayMongo stops retrying events we do not care about.
        return response()->json(['received' => true]);
    }
}
