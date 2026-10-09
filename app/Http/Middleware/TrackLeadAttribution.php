<?php

namespace App\Http\Middleware;

use App\Services\Leads\LeadAttribution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Funnel pages (/welcome, /for-business, /join): remember where the visitor came
 * from and count the unique visit. Usage: ->middleware('lead.track:join').
 * Only plain page loads are counted — never POSTs, XHR or link-preview crawlers.
 */
class TrackLeadAttribution
{
    public function __construct(private LeadAttribution $attribution)
    {
    }

    public function handle(Request $request, Closure $next, string $page = 'welcome'): Response
    {
        if ($request->isMethod('GET') && ! $request->ajax()) {
            $attr = $this->attribution->capture($request);
            $this->attribution->recordVisit($request, $page, $attr);
        }

        return $next($request);
    }
}
