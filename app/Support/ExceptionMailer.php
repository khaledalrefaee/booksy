<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Emails a summary of unhandled server errors (HTTP 500) to the team.
 *
 * Wired from bootstrap/app.php's $exceptions->report(). Laravel already skips
 * the "expected" exceptions (404 / 419 / validation / auth / model-not-found …)
 * before a report callback runs, so this mostly sees genuine bugs; we still
 * defend against any 4xx that slips through. Everything is wrapped so a mail
 * failure can never turn one error into two.
 */
class ExceptionMailer
{
    public static function report(Throwable $e, ?Request $request = null): void
    {
        try {
            $cfg = config('booksy.error_reports', []);

            if (empty($cfg['enabled']) || empty($cfg['to'])) {
                return;
            }

            // Only the configured environments (production by default).
            $envs = $cfg['environments'] ?? ['production'];
            if (! empty($envs) && ! app()->environment($envs)) {
                return;
            }

            // Never email 4xx client errors — they are not server bugs.
            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return;
            }

            // De-duplicate a repeating error so one bug can't flood the inbox.
            $signature = md5(get_class($e) . '|' . $e->getFile() . '|' . $e->getLine() . '|' . $e->getMessage());
            $minutes   = max(1, (int) ($cfg['throttle_minutes'] ?? 10));
            try {
                if (! Cache::add('errmail:' . $signature, 1, now()->addMinutes($minutes))) {
                    return; // already sent recently
                }
            } catch (Throwable $cacheError) {
                // Cache unavailable → still send (better a duplicate than silence).
            }

            $request = $request ?: (app()->bound('request') ? request() : null);

            [$subject, $html] = self::compose($e, $request);

            Mail::html($html, function ($message) use ($cfg, $subject) {
                $message->to($cfg['to'])->subject($subject);
            });
        } catch (Throwable $mailError) {
            // A reporting failure must stay silent for the user — just log it.
            Log::error('ExceptionMailer failed: ' . $mailError->getMessage());
        }
    }

    /** @return array{0:string,1:string} [subject, htmlBody] */
    private static function compose(Throwable $e, ?Request $request): array
    {
        $appName = config('app.name', 'GlowRez');
        $env     = app()->environment();
        $status  = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        $url    = $request?->fullUrl() ?? '(console / no request)';
        $path   = $request ? '/' . ltrim($request->path(), '/') : '—';
        $method = $request?->method() ?? '—';
        $route  = optional($request?->route())->getName() ?: '—';
        $ip     = $request?->ip() ?? '—';
        $agent  = $request?->userAgent() ?? '—';

        $short   = mb_strimwidth(trim($e->getMessage()) ?: '(no message)', 0, 140, '…');
        $subject = "[{$appName} {$status}] " . class_basename($e) . ": {$short}"
                 . ($env !== 'production' ? " ({$env})" : '');

        // Trim the trace so the email stays readable.
        $trace = collect(explode("\n", $e->getTraceAsString()))->take(25)->implode("\n");

        $rows = [
            'Time'        => now()->toDayDateTimeString() . ' (' . config('app.timezone', 'UTC') . ')',
            'Environment' => $env,
            'Status'      => $status,
            'Page URL'    => $url,
            'Path'        => $path,
            'Method'      => $method,
            'Route name'  => $route,
            'Signed-in'   => self::actor(),
            'IP'          => $ip,
            'User agent'  => $agent,
            'Exception'   => get_class($e),
            'Message'     => trim($e->getMessage()) ?: '(no message)',
            'Location'    => $e->getFile() . ':' . $e->getLine(),
        ];

        $rowsHtml = '';
        foreach ($rows as $label => $value) {
            $rowsHtml .= '<tr>'
                . '<td style="padding:6px 12px;font-weight:600;color:#4B5D34;white-space:nowrap;vertical-align:top;border-bottom:1px solid #eee;">'
                . e($label) . '</td>'
                . '<td style="padding:6px 12px;color:#222;word-break:break-word;border-bottom:1px solid #eee;font-family:monospace;font-size:12px;">'
                . e((string) $value) . '</td>'
                . '</tr>';
        }

        $html = <<<HTML
<div style="font-family:Arial,Helvetica,sans-serif;max-width:720px;margin:0 auto;">
    <div style="background:#4B5D34;color:#fff;padding:16px 20px;border-radius:12px 12px 0 0;">
        <div style="font-size:18px;font-weight:700;">⚠️ {$appName} — Server error ({$status})</div>
        <div style="font-size:13px;opacity:.85;margin-top:2px;">{$path}</div>
    </div>
    <table style="width:100%;border-collapse:collapse;background:#fff;border:1px solid #eee;border-top:none;">
        {$rowsHtml}
    </table>
    <div style="margin-top:14px;">
        <div style="font-weight:600;color:#4B5D34;margin-bottom:6px;">Stack trace (first 25 frames)</div>
        <pre style="background:#0f130a;color:#d7e0c6;padding:14px;border-radius:10px;overflow-x:auto;font-size:11px;line-height:1.5;white-space:pre-wrap;">{$trace}</pre>
    </div>
    <div style="color:#999;font-size:11px;margin-top:12px;">
        Automated error report from {$appName}. Reply-to is not monitored.
    </div>
</div>
HTML;

        return [$subject, $html];
    }

    /** Best-effort description of who was signed in when the error hit. */
    private static function actor(): string
    {
        foreach (['company', 'owner', 'staff', 'customer', 'web'] as $guard) {
            try {
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();
                    $name = $user->name_en ?? $user->name ?? $user->email ?? ('#' . $user->getAuthIdentifier());
                    return "{$guard}: {$name} (#{$user->getAuthIdentifier()})";
                }
            } catch (Throwable $ignore) {
                // Guard may not be configured in this context — skip it.
            }
        }

        return 'guest';
    }
}
