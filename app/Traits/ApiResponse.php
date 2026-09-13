<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * One response shape for the whole mobile API.
 *
 * Every endpoint — and every handled error (see bootstrap/app.php) — returns:
 *
 *   {
 *     "status":  true | false,     // did the request succeed?
 *     "message": "human text",     // localised, safe to show the user
 *     "data":    { ... } | null,   // the payload (only meaningful part that changes)
 *     "errors":  { field: [..] }   // present ONLY on validation failures
 *   }
 *
 * Controllers just call $this->success($data, $message) or
 * $this->error($message, $code); nothing else needs to know the envelope.
 */
trait ApiResponse
{
    /** A successful response. */
    protected function success(mixed $data = null, string $message = '', int $code = 200): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => $message !== '' ? $message : __('Done.'),
            'data'    => $data,
        ], $code);
    }

    /** A failed response. Pass $errors for field-level validation messages. */
    protected function error(string $message, int $code = 400, ?array $errors = null): JsonResponse
    {
        $payload = [
            'status'  => false,
            'message' => $message,
            'data'    => null,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
