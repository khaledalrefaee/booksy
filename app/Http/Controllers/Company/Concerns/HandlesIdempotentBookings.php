<?php

namespace App\Http\Controllers\Company\Concerns;

use App\Models\BookingIdempotencyKey;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

/**
 * Makes a booking write safe to repeat.
 *
 * The page queues writes while offline and replays them on reconnect, so the
 * same request can legitimately arrive twice. Consumers must expose
 * company(): \App\Models\Company.
 */
trait HandlesIdempotentBookings
{
    /**
     * Run $fn at most once per key.
     *
     * The key is *claimed* before the work starts, not after: if two identical
     * requests raced and we only wrote the key at the end, both would have
     * created an appointment before either claimed it. The unique index on
     * (company_id, key) settles the race, and the loser is told to retry —
     * by which point the winner's response is stored and gets replayed.
     */
    protected function idempotent(?string $key, \Closure $fn): JsonResponse
    {
        if (! $key) {
            return $fn();
        }

        $company = $this->company();

        $existing = BookingIdempotencyKey::where('company_id', $company->id)
            ->where('key', $key)
            ->first();

        if ($existing) {
            return $this->replayOrWait($existing);
        }

        /* claim the key first — this is the race guard */
        try {
            $claim = BookingIdempotencyKey::create([
                'company_id' => $company->id,
                'key'        => $key,
                'response'   => null,
            ]);
        } catch (QueryException $e) {
            $winner = BookingIdempotencyKey::where('company_id', $company->id)
                ->where('key', $key)
                ->first();

            return $winner
                ? $this->replayOrWait($winner)
                : throw $e;
        }

        try {
            $response = $fn();
        } catch (\Throwable $e) {
            /* the work blew up — release the claim so a retry can try cleanly */
            $claim->delete();
            throw $e;
        }

        if ($response->getStatusCode() === 200) {
            $claim->update(['response' => json_decode($response->getContent(), true)]);
        } else {
            /* a rejected booking never happened; the same key may be retried */
            $claim->delete();
        }

        return $response;
    }

    private function replayOrWait(BookingIdempotencyKey $row): JsonResponse
    {
        /* claimed but unfinished — the original request is still in flight */
        if ($row->response === null) {
            return response()->json([
                'ok'      => false,
                'code'    => 'in_progress',
                'message' => __('This booking is still being processed.'),
            ], 409);
        }

        return response()->json(array_merge($row->response, ['duplicate' => true]));
    }
}
