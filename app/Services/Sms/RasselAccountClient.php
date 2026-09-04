<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Read-only view of the GlowRez platform's Rassel account. Wraps the three
 * CONFIRMED GET endpoints only — no send endpoint is called or assumed here:
 *
 *   GET /account                       → profile, wallet balance, active
 *                                        subscription, message/channel limits,
 *                                        remaining segments, usage, free grants
 *   GET /account/subscriptions         → plans, period limits, bonus, billing
 *   GET /account/wallet/transactions   → per-transaction messageCount (segments),
 *                                        amount, type, description, balanceAfter
 *
 * This is the provider-side balance the OWNER monitors. GlowRez's own branch
 * credits are a separate ledger (SmsWallet / SmsTransaction) — Rassel is purely
 * the sending provider, never the source of truth for a branch's balance.
 *
 * Fails soft: any network/config problem returns ['ok' => false, 'error' => ...]
 * so a dashboard renders an empty/error state instead of throwing.
 */
class RasselAccountClient
{
    private string $base;
    private ?string $apiKey;

    public function __construct()
    {
        $this->base   = config('booksy.sms.api_base', 'https://raselsms.com/api/v2');
        $this->apiKey = config('booksy.sms.api_key') ?: null;
    }

    public function configured(): bool
    {
        return $this->apiKey !== null;
    }

    /** GET /account — balance, remaining segments, limits, usage, free grants. */
    public function account(bool $fresh = false): array
    {
        return $this->get('/account', 'account', $fresh);
    }

    /** GET /account/subscriptions — active plans, limits, bonus, billing cycle. */
    public function subscriptions(int $skip = 0, int $limit = 100, bool $fresh = false): array
    {
        return $this->get("/account/subscriptions?skip={$skip}&limit={$limit}", "subs:{$skip}:{$limit}", $fresh);
    }

    /** GET /account/wallet/transactions — usage rows with messageCount (segments). */
    public function walletTransactions(int $skip = 0, int $limit = 100, bool $fresh = false): array
    {
        return $this->get("/account/wallet/transactions?skip={$skip}&limit={$limit}", "wallet:{$skip}:{$limit}", $fresh);
    }

    /**
     * Flatten GET /account into the handful of fields the owner dashboard shows,
     * shielding views from Rassel's nested envelope (data.data.*). Confirmed
     * against the live account response shape.
     *
     * @return array{configured: bool, ok: bool, error?: string, ...}
     */
    public function snapshot(bool $fresh = false): array
    {
        $res = $this->account($fresh);

        if (! ($res['ok'] ?? false)) {
            return [
                'configured' => $this->configured(),
                'ok'         => false,
                'error'      => $res['error'] ?? 'Unavailable',
            ];
        }

        $d       = $res['data']['data'] ?? [];
        $wallet  = $d['wallet'] ?? [];
        $access  = $d['subscription']['access'] ?? [];
        $active  = $d['subscription']['active'] ?? [];
        $grants  = $d['freeMessageGrants']['summary']['totals'] ?? [];

        // Per-grant detail (channel, provider, expiry, reason) so the owner can
        // see exactly which free-message grants Rassel gave and their status.
        $grantList = [];
        foreach (($d['freeMessageGrants']['grants'] ?? []) as $g) {
            $grantList[] = [
                'channel'      => $g['channel'] ?? null,
                'provider_key' => $g['providerKey'] ?? null,
                'message_type' => $g['messageType'] ?? null,
                'granted'      => (int) ($g['initialQuantity'] ?? 0),
                'consumed'     => (int) ($g['consumedQuantity'] ?? 0),
                'remaining'    => (int) ($g['remainingQuantity'] ?? 0),
                'status'       => $g['status'] ?? null,
                'reason'       => $g['reason'] ?? null,
                'expires_at'   => $g['expiresAt'] ?? null,
            ];
        }

        return [
            'configured'         => true,
            'ok'                 => true,
            'business_name'      => $d['profile']['businessName'] ?? ($d['profile']['name'] ?? null),
            'wallet_balance'     => $wallet['balance'] ?? 0,
            'wallet_currency'    => $wallet['currency'] ?? 'USD',
            'plan_name'          => $active['planName'] ?? null,
            'plan_status'        => $active['status'] ?? null,
            'cycle_start'        => $access['cycleStart'] ?? null,
            'cycle_end'          => $access['cycleEnd'] ?? null,
            'effective_limit'    => (int) ($access['effectiveLimit'] ?? 0),
            'period_limit'       => (int) ($access['periodMessageLimit'] ?? 0),
            'bonus'              => (int) ($access['bonusMessagesGranted'] ?? 0),
            'remaining_segments' => (int) ($access['remainingSegments'] ?? 0),
            'used_segments'      => (int) ($d['subscription']['pool']['usedSegments'] ?? ($access['usageCount'] ?? 0)),
            'usage_by_channel'   => $access['usageByChannel'] ?? [],
            'channel_limits'     => $access['channelLimits'] ?? [],
            'can_send'           => (bool) ($access['canSend'] ?? false),
            'pool_exhausted'     => (bool) ($access['poolExhausted'] ?? false),
            'free_grant'         => [
                'granted'          => (int) ($grants['granted'] ?? 0),
                'consumed'         => (int) ($grants['consumed'] ?? 0),
                'remaining'        => (int) ($grants['remaining'] ?? 0),
                'active_remaining' => (int) ($grants['activeRemaining'] ?? 0),
            ],
            'free_grants'        => $grantList,
        ];
    }

    /**
     * @return array{ok: bool, data?: array, status?: int, error?: string}
     */
    private function get(string $path, string $cacheKey, bool $fresh): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'Rassel API key is not configured.'];
        }

        $key = 'rassel:' . $cacheKey;
        if (! $fresh && ($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept'    => 'application/json',
                ])
                ->timeout(15)
                ->get($this->base . $path);

            $result = $response->successful()
                ? ['ok' => true, 'data' => $response->json(), 'status' => $response->status()]
                : ['ok' => false, 'status' => $response->status(), 'error' => $response->body()];
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'error' => $e->getMessage()];
        }

        // Short cache so a dashboard refresh doesn't hammer the provider.
        Cache::put($key, $result, now()->addMinutes($result['ok'] ? 5 : 1));

        return $result;
    }
}
