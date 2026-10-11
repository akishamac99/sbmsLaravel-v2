<?php

namespace App\Http\Controllers;

use App\Services\MessengerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

// Meta calls these two URLs: GET to check we own the endpoint, POST to deliver messages.
class MessengerWebhookController extends Controller
{
    // PHP turns the dots in "hub.mode" into underscores, so they arrive as hub_mode, hub_verify_token and hub_challenge.
    public function verify(Request $request, MessengerService $messenger): Response
    {
        abort_unless(
            $request->query('hub_mode') === 'subscribe' && $messenger->validVerifyToken($request->query('hub_verify_token')),
            403
        );

        return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request, MessengerService $messenger): JsonResponse
    {
        if (! $messenger->validSignature($request->getContent(), $request->header('X-Hub-Signature-256'))) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        foreach ((array) $request->input('entry', []) as $entry) {
            foreach ((array) ($entry['messaging'] ?? []) as $event) {
                try {
                    $messenger->handleIncoming((array) $event);
                } catch (Throwable $e) {
                    // One bad event must not make Meta retry the whole delivery.
                    report($e);
                }
            }
        }

        // Meta needs a 200 within a few seconds.
        return response()->json(['received' => true]);
    }
}
