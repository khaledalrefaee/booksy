<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    /**
     * New production homepage (customer marketplace).
     * One query builds every card; curated subsets + a full dataset feed
     * the grid, the rails and the client-side filter/geo engine.
     */
    public function home(Request $request)
    {
        $isAr = app()->getLocale() === 'ar';

        $categories = Category::withCount('companies')->orderBy('sort_order')->get();

        $branches = \App\Models\Branch::query()
            ->with([
                'company.category',
                'images',
                'governorate',
                'area',
                'services' => fn($q) => $q->where('is_active', true),
            ])
            ->withCount(['reviews', 'appointments'])
            ->withAvg('reviews', 'rating')
            ->marketplace()
            ->whereHas('company', fn($q) => $q->where('status', 'active'))
            ->orderByDesc('appointments_count')
            ->limit(48)
            ->get();

        $cards = $this->decorateCards($branches->map(fn ($b) => $this->branchToCard($b, $isAr)));

        $topRated    = $cards->where('reviews', '>=', 1)->sortByDesc('rating')->take(10)->values();
        $newest      = $cards->sortByDesc('created_ts')->take(10)->values();
        $recommended = $cards->sortByDesc('score')->take(10)->values();
        $nearby      = $cards->whereNotNull('lat')->take(12)->values();

        // Homepage is a curated landing page — NOT the full listing. We surface a
        // small "Featured" set (best composite score) and hand the long tail off
        // to the dedicated /venues explore page.
        $featured    = $cards->sortByDesc('score')->take(8)->values();

        $ratingAvg = $cards->whereNotNull('rating')->avg('rating');

        $stats = [
            'rating'    => $ratingAvg ? round($ratingAvg, 1) : 4.8,
            'bookings'  => \App\Models\Appointment::count(),
            'venues'    => Company::where('status', 'active')->count(),
            'customers' => \App\Models\Customer::count(),
        ];

        $currency = $isAr ? 'ل.س' : 'SYP';
        $cities   = $this->cityList($isAr);

        // The /venues page now exists; hero search + "View all" hand off to it.
        $venuesUrl = route('front.venues');

        return view('front.home', compact(
            'categories', 'featured', 'topRated', 'newest', 'recommended',
            'nearby', 'stats', 'currency', 'isAr', 'cities', 'venuesUrl'
        ));
    }

    /* ─────────────────  Shared marketplace helpers  ───────────────── */

    /** Normalise a Branch into the card object every venue card expects. */
    public static function branchToCard($b, bool $isAr): object
    {
        $img      = $b->images->first();
        $services = $b->services;
        $prices   = $services->pluck('price')->filter(fn ($p) => $p > 0);
        $topSvc   = $services->take(3)
            ->map(fn ($s) => $isAr ? ($s->name_ar ?? $s->name_en) : ($s->name_en ?? $s->name_ar))
            ->filter()->values();
        $company  = $b->company;

        return (object) [
            'id'         => $b->id,
            'name'       => $isAr ? ($b->name_ar ?? $b->name_en) : ($b->name_en ?? $b->name_ar),
            'company'    => $isAr ? ($company->name_ar ?? $company->name_en) : ($company->name_en ?? $company->name_ar),
            'logo'       => $company->logo ? asset('storage/'.$company->logo) : null,
            'image'      => $img ? asset('storage/'.$img->path) : null,
            'category'   => $company->category ? ($isAr ? $company->category->name_ar : $company->category->name_en) : null,
            'cat_slug'   => $company->category?->slug,
            'city'       => $b->governorate?->localizedName() ?? $b->area?->localizedName(),
            'lat'        => $b->latitude !== null ? (float) $b->latitude : null,
            'lng'        => $b->longitude !== null ? (float) $b->longitude : null,
            'rating'     => $b->reviews_count ? round((float) $b->reviews_avg_rating, 1) : null,
            'reviews'    => (int) $b->reviews_count,
            'bookings'   => (int) $b->appointments_count,
            'min_price'  => $prices->isNotEmpty() ? (float) $prices->min() : null,
            'services'   => $topSvc,
            'svc_count'  => $services->count(),
            'created_ts' => $b->created_at?->timestamp ?? 0,
            'is_new'     => $b->created_at ? $b->created_at->gt(now()->subDays(30)) : false,
            'url'        => route('front.branch', $b),
        ];
    }

    /** Add composite score + one priority badge (hot > top > rec > new) to a card collection. */
    private function decorateCards($cards)
    {
        $cards = $cards->map(function ($c) {
            $c->score = round(
                ($c->rating ?? 0) * 2
                + min($c->bookings, 60) / 12
                + ($c->reviews > 0 ? 1.5 : 0)
                + $c->svc_count * 0.3,
                2
            );
            return $c;
        });

        $hotIds = $cards->where('bookings', '>', 0)->sortByDesc('bookings')->take(8)->pluck('id')->all();
        $topIds = $cards->where('reviews', '>=', 1)->where('rating', '>=', 4.6)->sortByDesc('rating')->take(8)->pluck('id')->all();
        $recIds = $cards->sortByDesc('score')->take(8)->pluck('id')->all();

        $cards->each(function ($c) use ($hotIds, $topIds, $recIds) {
            if (in_array($c->id, $hotIds, true) && $c->bookings > 0)  { $c->badge = 'hot'; }
            elseif (in_array($c->id, $topIds, true))                  { $c->badge = 'top'; }
            elseif (in_array($c->id, $recIds, true))                  { $c->badge = 'rec'; }
            elseif ($c->is_new)                                       { $c->badge = 'new'; }
            else                                                      { $c->badge = null; }
        });

        return $cards;
    }

    /** City autocomplete list — real governorate rows, else the Syrian governorate fallback. */
    private function cityList(bool $isAr)
    {
        $cities = \App\Models\Governorate::orderBy('sort_order')->get()
            ->map(fn ($g) => $g->localizedName())->filter()->values();
        if ($cities->isEmpty()) {
            $cities = collect($isAr
                ? ['دمشق','ريف دمشق','حلب','حمص','حماة','اللاذقية','طرطوس','درعا','السويداء','القنيطرة','دير الزور','الرقة','الحسكة','إدلب']
                : ['Damascus','Rif Dimashq','Aleppo','Homs','Hama','Latakia','Tartus','Daraa','As-Suwayda','Quneitra','Deir ez-Zor','Raqqa','Al-Hasakah','Idlib']);
        }
        return $cities;
    }

    /** Public: /venues explore + results page. */
    public function venues(Request $request)
    {
        return $this->results($request, null);
    }

    /**
     * Unified marketplace results engine — powers BOTH /venues and /category/{slug}.
     * Handles search, city, category + sort, server-side pagination, and an AJAX
     * "load more" partial. One page, one component, consistent UX + scalable.
     */
    private function results(Request $request, ?Category $category = null)
    {
        $isAr   = app()->getLocale() === 'ar';
        $sort   = in_array($request->get('sort'), ['new','rating','booked'], true) ? $request->get('sort') : 'featured';
        $search = trim((string) $request->get('search', ''));
        $city   = trim((string) $request->get('city', ''));

        $query = \App\Models\Branch::query()
            ->with(['company.category','images','governorate','area','services' => fn($q) => $q->where('is_active', true)])
            ->withCount(['reviews','appointments'])
            ->withAvg('reviews','rating')
            ->marketplace()
            ->whereHas('company', fn($q) => $q->where('status','active'));

        $activeCategory = $category?->slug ?? ($request->filled('category') ? $request->get('category') : null);
        if ($activeCategory) {
            $query->whereHas('company.category', fn($q) => $q->where('slug', $activeCategory));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name_en','like',"%$search%")->orWhere('name_ar','like',"%$search%")
                  ->orWhere('address','like',"%$search%")
                  ->orWhereHas('company', fn($c) => $c->where('name_en','like',"%$search%")->orWhere('name_ar','like',"%$search%"))
                  ->orWhereHas('services', fn($s) => $s->where('is_active',true)
                        ->where(fn($q2) => $q2->where('name_en','like',"%$search%")->orWhere('name_ar','like',"%$search%")));
            });
        }
        if ($city !== '') {
            $query->where(function ($q) use ($city) {
                $q->where('address','like',"%$city%")
                  ->orWhereHas('governorate', fn($g) => $g->where('name_en','like',"%$city%")->orWhere('name_ar','like',"%$city%"))
                  ->orWhereHas('area', fn($a) => $a->where('name_en','like',"%$city%")->orWhere('name_ar','like',"%$city%"));
            });
        }

        match ($sort) {
            'new'    => $query->orderByDesc('created_at'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
            'booked' => $query->orderByDesc('appointments_count'),
            default  => $query->orderByDesc('appointments_count')->orderByDesc('reviews_avg_rating'),
        };

        $branches = $query->paginate(12)->withQueryString();
        $cards    = $this->decorateCards($branches->getCollection()->map(fn ($b) => $this->branchToCard($b, $isAr)));
        $currency = $isAr ? 'ل.س' : 'SYP';

        // AJAX "load more" → return just the rendered cards + paging flags.
        if ($request->boolean('partial')) {
            $html = view('front.partials.venue-cards-loop', compact('cards','currency','isAr'))->render();
            return response()->json([
                'html'     => $html,
                'hasMore'  => $branches->hasMorePages(),
                'nextPage' => $branches->currentPage() + 1,
            ]);
        }

        $categories = Category::withCount('companies')->orderBy('sort_order')->get();
        $cities     = $this->cityList($isAr);

        return view('front.venues', compact(
            'branches', 'cards', 'categories', 'category', 'cities',
            'currency', 'isAr', 'sort', 'search', 'city', 'activeCategory'
        ));
    }

    public function show(\App\Models\Company $company)
    {
        $company->load([
            'category',
            'branches.images',
            'branches.workingHours',
            'branches.services.serviceCategory',
            'branches.employees.serviceCategories',
            'branches.employees.role',
            'branches.reviews.customer',
            'socialLinks',
        ]);

        $branch      = $company->branches->first();
        $allImages   = $company->branches->flatMap(fn($b) => $b->images);
        $serviceCategories = $branch
            ? $branch->services
                ->where('is_active', true)
                ->groupBy('service_category_id')
            : collect();
        $employees   = $branch ? $branch->employees->where('is_active', true) : collect();
        $reviews     = $branch ? $branch->reviews->sortByDesc('created_at') : collect();
        $avgRating   = $reviews->avg('rating') ?? 0;

        return view('front.show', compact(
            'company', 'branch', 'allImages',
            'serviceCategories', 'employees', 'reviews', 'avgRating'
        ));
    }

    public function branchShow(\App\Models\Branch $branch)
    {
        $branch->load([
            'company.category',
            'company.socialLinks',
            'images',
            'workingHours',
            'services' => fn($q) => $q->where('is_active', true)->with('serviceCategory'),
            'employees' => fn($q) => $q->where('is_active', true)->with(['role', 'serviceCategories']),
            'reviews.customer',
        ]);
        $company = $branch->company;
        $allImages = $branch->images;
        $servicesByCategory = $branch->services->groupBy('service_category_id');
        $employees = $branch->employees;
        $reviews = $branch->reviews->sortByDesc('created_at');
        $avgRating = $reviews->avg('rating') ?? 0;
        $stars = round($avgRating * 2) / 2;
        $totalRev = $reviews->count();
        return view('front.branch', compact(
            'branch', 'company', 'allImages', 'servicesByCategory',
            'employees', 'reviews', 'avgRating', 'stars', 'totalRev'
        ));
    }

    public function privateBooking(string $slug)
    {
        $branch = \App\Models\Branch::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        abort_unless($branch->company->hasFeature('private_booking'), 404);

        $branch->load([
            'company.category',
            'company.socialLinks',
            'images',
            'workingHours',
            'services' => fn($q) => $q->where('is_active', true)->with('serviceCategory'),
            'employees' => fn($q) => $q->where('is_active', true)->with(['role', 'serviceCategories']),
            'reviews.customer',
        ]);

        $company = $branch->company;
        $allImages = $branch->images;
        $servicesByCategory = $branch->services->groupBy('service_category_id');
        $employees = $branch->employees;
        $reviews = $branch->reviews->sortByDesc('created_at');
        $avgRating = $reviews->avg('rating') ?? 0;
        $stars = round($avgRating * 2) / 2;
        $totalRev = $reviews->count();

        return view('front.private-booking', compact(
            'branch', 'company', 'allImages', 'servicesByCategory',
            'employees', 'reviews', 'avgRating', 'stars', 'totalRev'
        ));
    }

    public function about()
    {
        return view('front.about');
    }

    public function contact()
    {
        return view('front.contact');
    }

    public function contactSend(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email|max:150',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:3000',
        ]);

        // Deliver to the platform inbox. A mail-transport failure must never turn
        // a valid submission into an error for the visitor — we log and move on.
        $inbox = config('mail.contact_to') ?: config('mail.from.address');

        try {
            \Illuminate\Support\Facades\Mail::to($inbox)->send(
                new \App\Mail\ContactMessageMail($data['name'], $data['email'], $data['subject'], $data['message'])
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Contact form delivery failed: '.$e->getMessage(), [
                'from' => $data['email'],
            ]);
        }

        return back()->with('success', true);
    }

    /** /category/{slug} — same unified results engine, pre-filtered by category. */
    public function categoryPage(string $slug, Request $request)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        return $this->results($request, $category);
    }

    public function mapBranches()
    {
        $isAr = app()->getLocale() === 'ar';
        $branches = \App\Models\Branch::with(['company.category','images','reviews'])
            ->marketplace()
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->whereHas('company', fn($q) => $q->where('status','active'))
            ->get();

        return response()->json($branches->map(function($b) use ($isAr) {
            $reviews = $b->reviews;
            $avg     = $reviews->count() ? round($reviews->avg('rating'),1) : null;
            $img     = $b->images->first();
            $company = $b->company;
            return [
                'id'           => $b->id,
                'lat'          => (float)$b->latitude,
                'lng'          => (float)$b->longitude,
                'name'         => $isAr ? ($b->name_ar ?? $b->name_en) : ($b->name_en ?? $b->name_ar),
                'company_name' => $isAr ? ($company->name_ar ?? $company->name_en) : ($company->name_en ?? $company->name_ar),
                'company_logo' => $company->logo ? asset('storage/'.$company->logo) : null,
                'image'        => $img ? asset('storage/'.$img->path) : null,
                'category'     => $company->category ? ($isAr ? $company->category->name_ar : $company->category->name_en) : null,
                'address'      => $b->address,
                'avg_rating'   => $avg,
                'review_count' => $reviews->count(),
                'url'          => route('front.branch', $b),
            ];
        }));
    }

    /**
     * Business Landing — marketing site aimed at salon/studio owners.
     * All numeric proof comes from real DB counts (never fabricated). The
     * testimonial quotes below are PLACEHOLDERS: replace with real, consented
     * customer quotes before launch.
     */
    public function business(Request $request)
    {
        // Static marketing page — all copy lives in the view (no DB needed).
        return view('front.business');
    }

}
