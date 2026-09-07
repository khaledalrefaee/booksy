<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Read-only view of the GlowRez platform's Rassel account. Wraps the
 * documented account/reference GET endpoints only — sending lives in
 * RasselClient, never here:
 *
 *   GET /account                       → profile, wallet balance, active
 *                                        subscription, effective limit,
 *                                        remaining segments, usage, free grants
 *   GET /account/subscriptions         → subscription periods and billing
 *   GET /account/wallet/transactions   → per-transaction messageCount (segments),
 *                                        amount, type, description, balanceAfter
 *   GET /sms-senders                   → approved SMS sender names (sender.id)
 *   GET /messages/policies             → allowed message types per channel
 *   POST /sms-senders                  → request a new sender (owner only)
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

    /** GET /sms-senders — approved SMS sender names for the platform account. */
    public function smsSenders(bool $approvedOnly = true, bool $fresh = false): array
    {
        $q = $approvedOnly ? '?approvedOnly=true' : '';

        return $this->get('/sms-senders' . $q, 'senders:' . ($approvedOnly ? 'appr' : 'all'), $fresh);
    }

    /** GET /messages/policies — allowed message types per provider/channel. */
    public function policies(bool $fresh = false): array
    {
        return $this->get('/messages/policies', 'policies', $fresh);
    }

    /**
     * The message types the local_sms channel currently allows, per the live
     * provider policies. Returns [] when policies are unavailable so callers can
     * fall back to permitting everything (fails open, never blocks a send).
     *
     * @return string[]
     */
    public function localSmsAllowedTypes(bool $fresh = false): array
    {
        $res = $this->policies($fresh);
        if (! ($res['ok'] ?? false)) {
            return [];
        }

        $types = [];
        foreach (($res['data']['data'] ?? []) as $policy) {
            $channel = $policy['channel'] ?? '';
            if (($policy['enabled'] ?? false) && in_array($channel, ['sms_syria', 'sms_local', 'local_sms'], true)) {
                $types = array_merge($types, $policy['allowedTypes'] ?? []);
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * POST /sms-senders — request a new sender name (account owner only). Returns
     * the raw ['ok'=>bool, ...] envelope. Never called from a send path.
     */
    public function requestSmsSender(string $name, ?string $webhookUrl = null, ?string $webhookSecret = null): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'Rassel API key is not configured.'];
        }

        $payload = ['name' => $name];
        if ($webhookUrl && $webhookSecret) {
            $payload['webhookUrl']    = $webhookUrl;
            $payload['webhookSecret'] = $webhookSecret;
        }

        try {
            $response = Http::withHeaders([
                    'X-API-Key'    => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->timeout(20)
                ->post($this->base . '/sms-senders', $payload);

            $body = $response->json() ?? [];

            return $response->successful()
                ? ['ok' => true, 'data' => $body]
                : ['ok' => false, 'status' => $response->status(), 'code' => $body['code'] ?? null, 'error' => $body['error'] ?? $response->body()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalized list of approved senders for a picker:
     * [['id'=>..., 'name'=>..., 'status'=>..., 'is_default'=>bool], ...].
     *
     * @return array{ok: bool, senders: array, error?: string}
     */
    public function approvedSenders(bool $fresh = false): array
    {
        $res = $this->smsSenders(true, $fresh);
        if (! ($res['ok'] ?? false)) {
            return ['ok' => false, 'senders' => [], 'error' => $res['error'] ?? 'Unavailable'];
        }

        $senders = [];
        foreach (($res['data']['data']['smsSenders'] ?? []) as $s) {
            $senders[] = [
                'id'         => $s['id'] ?? null,
                'name'       => $s['name'] ?? null,
                'status'     => $s['status'] ?? null,
                'is_default' => (bool) ($s['isDefault'] ?? false),
            ];
        }

        return ['ok' => true, 'senders' => $senders];
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
        $pool    = $d['subscription']['pool'] ?? [];
        $summary = $d['freeMessageGrants']['summary'] ?? [];
        $totals  = $summary['totals'] ?? [];

        // Per-scope free-grant remaining (documented: summary.byScope[]).
        $byScope = [];
        foreach (($summary['byScope'] ?? []) as $s) {
            $byScope[] = [
                'channel'      => $s['channel'] ?? null,
                'provider_key' => $s['providerKey'] ?? null,
                'message_type' => $s['messageType'] ?? null,
                'remaining'    => (int) ($s['remaining'] ?? 0),
                'grant_count'  => (int) ($s['grantCount'] ?? 0),
            ];
        }

        // Per-grant detail — DOCUMENTED fields only (id, channel,
        // remainingQuantity, status, reason). Rasel does not return the original
        // granted/consumed amounts per grant, so we never fabricate them.
        $grantList = [];
        foreach (($d['freeMessageGrants']['grants'] ?? []) as $g) {
            $grantList[] = [
                'id'        => $g['id'] ?? null,
                'channel'   => $g['channel'] ?? null,
                'remaining' => (int) ($g['remainingQuantity'] ?? 0),
                'status'    => $g['status'] ?? null,
                'reason'    => $g['reason'] ?? null,
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
            // Billing cycle from the active subscription (documented startAt/endAt).
            'cycle_start'        => $active['startAt'] ?? null,
            'cycle_end'          => $active['endAt'] ?? null,
            'effective_limit'    => (int) ($access['effectiveLimit'] ?? ($pool['effectiveLimit'] ?? 0)),
            'remaining_segments' => (int) ($access['remainingSegments'] ?? ($pool['remainingSegments'] ?? 0)),
            'used_segments'      => (int) ($pool['usedSegments'] ?? ($access['usageCount'] ?? 0)),
            'can_send'           => (bool) ($access['canSend'] ?? false),
            'reason'             => $access['reason'] ?? null,
            'pool_exhausted'     => (bool) ($access['poolExhausted'] ?? ($pool['poolExhausted'] ?? false)),
            'free_grant'         => [
                'granted'          => (int) ($totals['granted'] ?? 0),
                'consumed'         => (int) ($totals['consumed'] ?? 0),
                'remaining'        => (int) ($totals['remaining'] ?? 0),
                'active_remaining' => (int) ($totals['activeRemaining'] ?? 0),
            ],
            'by_scope'           => $byScope,
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
