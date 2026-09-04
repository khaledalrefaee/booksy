<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

/**
 * The single place that talks to the Rassel SMS gateway (raselsms.com). Config
 * lives under config('booksy.sms') — the same keys the app already used, so no
 * new credentials. WhatsappService::dispatchViaSms() now delegates here instead
 * of duplicating the HTTP call, keeping one code path for the provider.
 *
 * Inert-safe: with no URL/key configured it returns a clear reason rather than
 * throwing, so callers can record a failed message without blowing up a request.
 */
class RasselClient
{
    /**
     * @return array{0: bool, 1: ?string, 2: ?string} [ok, providerMessageId, error]
     */
    public function send(string $phone, string $message): array
    {
        $driver = config('booksy.sms.driver', 'rasel');
        $url    = config('booksy.sms.url');
        $apiKey = config('booksy.sms.api_key');

        if (! $url) {
            return [false, null, 'SMS provider is not configured (set BOOKSY_SMS_URL).'];
        }
        if (! $apiKey) {
            return [false, null, 'SMS provider API key is missing (set BOOKSY_SMS_KEY).'];
        }

        // Rassel expects digits only, no leading "+" (e.g. 963949863373).
        $to = preg_replace('/\D+/', '', $phone);

        if ($driver === 'rasel') {
            $response = Http::withHeaders([
                    'X-API-Key'    => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->post($url, [
                    'to'          => $to,
                    'channel'     => config('booksy.sms.channel', 'local_sms'),
                    'messageType' => 'free_text',
                    'content'     => ['text' => $message],
                ]);

            $providerId = $response->successful()
                ? ($response->json('id') ?? $response->json('messageId') ?? $response->json('data.id'))
                : null;

            return [$response->successful(), $providerId, $response->successful() ? null : $response->body()];
        }

        // Generic Twilio-style gateway: Bearer key + { to, from, message }.
        $sender   = config('booksy.sms.sender', 'GlowRez');
        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post($url, [
                'to'      => $to,
                'from'    => $sender,
                'message' => $message,
            ]);

        $providerId = $response->successful()
            ? ($response->json('sid') ?? $response->json('id'))
            : null;

        return [$response->successful(), $providerId, $response->successful() ? null : $response->body()];
    }
}
