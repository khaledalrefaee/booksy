<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

/**
 * The single place that talks to the Rasel SMS gateway for SENDING.
 *
 * Scope: local_sms only. This client never manages balances — GlowRez charges
 * the company's own wallet in SmsCreditService; Rasel is purely the transport.
 * The `billing.estimatedCost` Rasel returns is recorded for reference only.
 *
 * Endpoint is derived from the single config('booksy.sms.api_base') root
 * ({api_base}/messages/send), with config('booksy.sms.url') as an optional
 * override. Inert-safe: with no key/base it returns a clear reason instead of
 * throwing, so a caller can record a failed message without breaking a request.
 *
 * send() returns a structured result mirroring the documented v2 response:
 *   [
 *     'ok'                => bool,   // accepted (HTTP 200/202, body.success)
 *     'retry'             => bool,   // transient (429/502/503/network) — safe to retry
 *     'http'              => ?int,
 *     'success'           => ?bool,  // body.success
 *     'provider_status'   => ?string,// body.status: sent|queued|scheduled
 *     'request_id'        => ?string,// requestId
 *     'message_id'        => ?string,// tracking.messageId
 *     'usage_id'          => ?string,// tracking.usageId
 *     'queue_id'          => ?string,// tracking.queueId
 *     'resolved_provider' => ?string,// resolved.provider
 *     'sender_source'     => ?string,// resolved.senderSource
 *     'estimated_cost'    => ?float, // billing.estimatedCost
 *     'currency'          => ?string,// billing.currency
 *     'code'              => ?string,// error code on failure
 *     'error'             => ?string,// human-readable message
 *     'retry_after'       => ?int,   // seconds (429)
 *   ]
 */
class RasselClient
{
    /**
     * @param array{idempotencyKey?: ?string, senderId?: ?string, messageType?: string} $options
     */
    public function send(string $phone, string $message, array $options = []): array
    {
        $driver = config('booksy.sms.driver', 'rasel');
        $apiKey = config('booksy.sms.api_key');

        if (! $apiKey) {
            return $this->fail(null, 'SMS provider API key is missing (set BOOKSY_SMS_KEY).', 'NOT_CONFIGURED');
        }

        if ($driver !== 'rasel') {
            return $this->sendGeneric($phone, $message, $apiKey);
        }

        $url = $this->endpoint();
        if (! $url) {
            return $this->fail(null, 'SMS provider is not configured (set BOOKSY_SMS_API_BASE).', 'NOT_CONFIGURED');
        }

        // Documented `to` format is E.164 with a leading "+".
        $to = $this->toE164($phone);

        $payload = [
            'to'          => $to,
            'channel'     => config('booksy.sms.channel', 'local_sms'),
            'messageType' => $options['messageType'] ?? 'free_text',
            'content'     => ['text' => $message],
        ];

        // Optional approved sender name (sender.id from GET /api/v2/sms-senders).
        if (! empty($options['senderId'])) {
            $payload['sender'] = ['id' => $options['senderId']];
        }

        $headers = [
            'X-API-Key'    => $apiKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        // Idempotency-Key makes a retried send replay the cached response for 24h
        // instead of delivering (and billing) twice.
        if (! empty($options['idempotencyKey'])) {
            $headers['Idempotency-Key'] = (string) $options['idempotencyKey'];
        }

        try {
            $response = Http::withHeaders($headers)->timeout(30)->post($url, $payload);
        } catch (\Throwable $e) {
            // Network/timeout — transient, safe to retry.
            return $this->retry(null, $e->getMessage(), 'NETWORK_ERROR');
        }

        return $this->parse($response);
    }

    // ── Response parsing ──────────────────────────────────────────────────────

    private function parse(\Illuminate\Http\Client\Response $response): array
    {
        $status = $response->status();
        $body   = $response->json() ?? [];

        // A batch (to[]) reply — we only send one recipient, but handle 207 and
        // any `results[]`/`summary` envelope defensively per the documented shape.
        if (isset($body['batch']) || isset($body['results'])) {
            $first = $body['results'][0] ?? [];
            $ok    = (bool) ($first['success'] ?? ($body['success'] ?? false));

            return array_merge($this->extract($first ?: $body, $body), [
                'ok'    => $ok && $status < 300,
                'retry' => false,
                'http'  => $status,
            ]);
        }

        $success = $body['success'] ?? null;

        // 200 sent / 202 accepted-or-queued.
        if ($status === 200 || $status === 202) {
            $result = $this->extract($body, $body);
            $result['ok']    = ($success !== false);
            $result['retry'] = false;
            $result['http']  = $status;
            if (! $result['provider_status']) {
                $result['provider_status'] = $status === 202 ? 'queued' : 'sent';
            }
            return $result;
        }

        // Transient provider problems — safe to retry.
        if (in_array($status, [429, 502, 503], true)) {
            $retryAfter = (int) ($body['retryAfterSeconds']
                ?? $response->header('Retry-After')
                ?: 0) ?: null;

            return $this->retry(
                $status,
                $body['error'] ?? 'Provider temporarily unavailable',
                $body['code'] ?? null,
                $retryAfter,
                $body,
            );
        }

        // Hard failures: 400 validation / pre-send block, 401/403 auth,
        // 402 insufficient provider balance, 409 idempotency conflict, others.
        return $this->fail(
            $status,
            $body['error'] ?? ('HTTP ' . $status),
            $body['code'] ?? null,
            $body,
        );
    }

    /** Pull the documented tracking/resolved/billing fields out of a body. */
    private function extract(array $node, array $root): array
    {
        $tracking = $node['tracking'] ?? [];
        $resolved = $node['resolved'] ?? [];
        $billing  = $node['billing'] ?? [];

        return [
            'success'           => $node['success'] ?? ($root['success'] ?? null),
            'provider_status'   => $node['status'] ?? null,
            'request_id'        => $node['requestId'] ?? ($root['requestId'] ?? null),
            'message_id'        => $tracking['messageId'] ?? null,
            'usage_id'          => $tracking['usageId'] ?? null,
            'queue_id'          => $tracking['queueId'] ?? null,
            'resolved_provider' => $resolved['provider'] ?? null,
            'sender_source'     => $resolved['senderSource'] ?? null,
            'estimated_cost'    => isset($billing['estimatedCost']) ? (float) $billing['estimatedCost'] : null,
            'currency'          => $billing['currency'] ?? null,
            'code'              => null,
            'error'             => null,
            'retry_after'       => null,
        ];
    }

    // ── Result builders ─────────────────────────────────────────────────────

    private function fail(?int $http, string $error, ?string $code, array $body = []): array
    {
        return array_merge($this->extract($body, $body), [
            'ok'    => false,
            'retry' => false,
            'http'  => $http,
            'code'  => $code,
            'error' => $error,
        ]);
    }

    private function retry(?int $http, string $error, ?string $code, ?int $retryAfter = null, array $body = []): array
    {
        return array_merge($this->extract($body, $body), [
            'ok'          => false,
            'retry'       => true,
            'http'        => $http,
            'code'        => $code,
            'error'       => $error,
            'retry_after' => $retryAfter,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** The send endpoint: explicit override, else derived from api_base. */
    private function endpoint(): ?string
    {
        $override = config('booksy.sms.url');
        if (is_string($override) && $override !== '') {
            return $override;
        }

        $base = config('booksy.sms.api_base');

        return $base ? rtrim($base, '/') . '/messages/send' : null;
    }

    private function toE164(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return $digits === '' ? '' : '+' . $digits;
    }

    /** Generic Twilio-style gateway: Bearer key + { to, from, message }. */
    private function sendGeneric(string $phone, string $message, string $apiKey): array
    {
        $url = config('booksy.sms.url');
        if (! $url) {
            return $this->fail(null, 'SMS provider is not configured (set BOOKSY_SMS_URL).', 'NOT_CONFIGURED');
        }

        $to     = preg_replace('/\D+/', '', $phone);
        $sender = config('booksy.sms.sender', 'GlowRez');

        try {
            $response = Http::withToken($apiKey)->timeout(30)->post($url, [
                'to'      => $to,
                'from'    => $sender,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            return $this->retry(null, $e->getMessage(), 'NETWORK_ERROR');
        }

        if ($response->successful()) {
            $id = $response->json('sid') ?? $response->json('id');

            return array_merge($this->extract([], []), [
                'ok'              => true,
                'retry'           => false,
                'http'            => $response->status(),
                'success'         => true,
                'provider_status' => 'sent',
                'message_id'      => $id,
            ]);
        }

        return $this->fail($response->status(), $response->body(), null);
    }
}
