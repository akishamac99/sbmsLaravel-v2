<?php

namespace App\Services;

use App\Models\MessengerContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessengerService
{
    public function configured(): bool
    {
        return filled(config('services.messenger.page_token'));
    }

    /**
     * Send a notification to a person on Messenger, if they connected it.
     * Waits until the database transaction is saved, and never throws.
     */
    public function notify(string $type, int $userId, string $title, string $message): void
    {
        if (! $this->configured()) {
            return;
        }

        DB::afterCommit(function () use ($type, $userId, $title, $message) {
            try {
                $contact = MessengerContact::forUser($type, $userId)->first();

                // Meta only allows a message within 24 hours of the person's last message to the Page.
                if (! $contact || ! $contact->can_receive) {
                    return;
                }

                $this->sendText($contact->psid, "{$title}\n{$message}");
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    public function sendText(string $psid, string $text): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $version = config('services.messenger.graph_version', 'v25.0');

        $response = Http::asJson()
            ->timeout(10)
            ->withQueryParameters(['access_token' => config('services.messenger.page_token')])
            ->post("https://graph.facebook.com/{$version}/me/messages", [
                'recipient' => ['id' => $psid],
                'messaging_type' => 'RESPONSE',
                'message' => ['text' => mb_substr($text, 0, 1900)],
            ]);

        if ($response->failed()) {
            Log::warning('Messenger send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response->successful();
    }

    /**
     * One event from Meta's webhook: someone messaged the Page.
     */
    public function handleIncoming(array $event): void
    {
        $psid = $event['sender']['id'] ?? null;
        $text = $event['message']['text'] ?? null;

        // Ignore delivery receipts, echoes of our own messages, and anything without a sender.
        if (! $psid || ! empty($event['message']['is_echo'])) {
            return;
        }

        // Every message from a known person reopens their 24-hour window.
        MessengerContact::where('psid', $psid)->update(['last_inbound_at' => now()]);

        if (! is_string($text)) {
            return;
        }

        if (preg_match('/SBMS-[A-Z0-9]{6}/i', $text, $m)) {
            $reply = $this->linkByCode(strtoupper($m[0]), $psid)
                ? 'Messenger connected! You will now get your salon updates here.'
                : 'That code is invalid or expired. Please create a new one from your profile page.';

            $this->sendText($psid, $reply);
        }
    }

    public function linkByCode(string $code, string $psid): bool
    {
        $contact = MessengerContact::where('link_code', $code)
            ->where('link_code_expires_at', '>', now())
            ->first();

        if (! $contact) {
            return false;
        }

        // A Messenger account can belong to only one salon account.
        MessengerContact::where('psid', $psid)->where('id', '!=', $contact->id)->update(['psid' => null, 'linked_at' => null]);

        $contact->update([
            'psid' => $psid,
            'linked_at' => now(),
            'last_inbound_at' => now(),
            'link_code' => null,
            'link_code_expires_at' => null,
        ]);

        return true;
    }

    public function validVerifyToken(?string $token): bool
    {
        $expected = (string) config('services.messenger.verify_token');

        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }

    public function validSignature(string $rawBody, ?string $header): bool
    {
        $secret = (string) config('services.messenger.app_secret');

        if ($secret === '' || ! $header || ! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $given = substr($header, 7);

        // Meta signs a payload with non-ASCII characters escaped as \uXXXX, so check both forms.
        foreach ([$rawBody, $this->escapeUnicode($rawBody)] as $body) {
            if (hash_equals(hash_hmac('sha256', $body, $secret), $given)) {
                return true;
            }
        }

        return false;
    }

    private function escapeUnicode(string $body): string
    {
        return preg_replace_callback('/[^\x00-\x7F]/u', function ($m) {
            $code = mb_ord($m[0], 'UTF-8');

            if ($code > 0xFFFF) {
                $code -= 0x10000;

                return sprintf('\u%04x\u%04x', 0xD800 + ($code >> 10), 0xDC00 + ($code & 0x3FF));
            }

            return sprintf('\u%04x', $code);
        }, $body) ?? $body;
    }
}
