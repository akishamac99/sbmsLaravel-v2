<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayMongoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsk_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paymongo.webhook_secret' => self::SECRET]);
    }

    private function signature(string $body, ?int $time = null): string
    {
        $time ??= time();

        return 't='.$time.',te='.hash_hmac('sha256', $time.'.'.$body, self::SECRET).',li=';
    }

    private function postWebhook(string $body, ?string $signature)
    {
        $server = ['CONTENT_TYPE' => 'application/json'];

        if ($signature !== null) {
            $server['HTTP_PAYMONGO_SIGNATURE'] = $signature;
        }

        return $this->call('POST', '/webhooks/paymongo', [], [], [], $server, $body);
    }

    private function paidEventBody(): string
    {
        return json_encode([
            'data' => ['attributes' => [
                'type' => 'checkout_session.payment.paid',
                'data' => ['id' => 'cs_unknown_session'],
            ]],
        ]);
    }

    public function test_it_rejects_a_request_without_a_signature(): void
    {
        $this->postWebhook($this->paidEventBody(), null)->assertStatus(400);
    }

    public function test_it_rejects_a_wrong_signature(): void
    {
        $this->postWebhook($this->paidEventBody(), 't='.time().',te=deadbeef,li=')->assertStatus(400);
    }

    public function test_it_rejects_a_body_that_was_changed_after_signing(): void
    {
        $signature = $this->signature($this->paidEventBody());

        $this->postWebhook($this->paidEventBody().' ', $signature)->assertStatus(400);
    }

    public function test_it_rejects_an_old_signature(): void
    {
        $body = $this->paidEventBody();

        $this->postWebhook($body, $this->signature($body, time() - 3600))->assertStatus(400);
    }

    public function test_it_accepts_a_valid_signature_even_when_the_session_is_unknown(): void
    {
        $body = $this->paidEventBody();

        $this->postWebhook($body, $this->signature($body))
            ->assertOk()
            ->assertJson(['received' => true]);
    }
}
