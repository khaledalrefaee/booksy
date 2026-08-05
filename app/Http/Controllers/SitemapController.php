<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    /**
     * Dynamic XML sitemap: static marketing/legal pages + every publicly
     * bookable branch + category landing pages. Cached 1h to stay cheap.
     */
    public function index()
    {
        $xml = cache()->remember('sitemap.xml', now()->addHour(), function () {
            $urls = [];

            // ── static, indexable pages (name => [changefreq, priority]) ──
            $static = [
                'front.index'            => ['daily',   '1.0'],
                'front.venues'           => ['daily',   '0.9'],
                'front.business'         => ['weekly',  '0.9'],
                'front.about'            => ['monthly', '0.6'],
                'front.contact'          => ['monthly', '0.6'],
                'front.help'             => ['monthly', '0.6'],
                'front.privacy'          => ['yearly',  '0.3'],
                'front.terms'            => ['yearly',  '0.3'],
                'front.business.privacy' => ['yearly',  '0.3'],
                'front.business.terms'   => ['yearly',  '0.3'],
            ];
            foreach ($static as $name => [$freq, $prio]) {
                $urls[] = ['loc' => route($name), 'changefreq' => $freq, 'priority' => $prio];
            }

            // ── category landing pages ──
            Category::query()->whereNotNull('slug')->get(['slug'])->each(function ($c) use (&$urls) {
                $urls[] = ['loc' => route('front.category', $c->slug), 'changefreq' => 'weekly', 'priority' => '0.7'];
            });

            // ── publicly bookable branches (same visibility rule as /venues) ──
            Branch::query()
                ->marketplace()
                ->whereHas('company', fn ($q) => $q->where('status', 'active'))
                ->get(['id', 'updated_at'])
                ->each(function ($b) use (&$urls) {
                    $urls[] = [
                        'loc'        => route('front.branch', $b->id),
                        'lastmod'    => optional($b->updated_at)->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority'   => '0.8',
                    ];
                });

            return $this->render($urls);
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function render(array $urls): string
    {
        $out  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $out .= '  <url>'."\n";
            $out .= '    <loc>'.htmlspecialchars($u['loc'], ENT_XML1).'</loc>'."\n";
            if (! empty($u['lastmod'])) {
                $out .= '    <lastmod>'.$u['lastmod'].'</lastmod>'."\n";
            }
            $out .= '    <changefreq>'.$u['changefreq'].'</changefreq>'."\n";
            $out .= '    <priority>'.$u['priority'].'</priority>'."\n";
            $out .= '  </url>'."\n";
        }
        $out .= '</urlset>'."\n";

        return $out;
    }
}
