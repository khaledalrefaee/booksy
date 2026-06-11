<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\Request;

class FrontController extends Controller
{
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
        $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email|max:150',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:3000',
        ]);

        return back()->with('success', true);
    }

    public function categoryPage(string $slug, Request $request)
    {
        $category   = Category::where('slug', $slug)->firstOrFail();
        $categories = Category::orderBy('sort_order')->get();

        $query = \App\Models\Branch::with([
            'company.category',
            'images',
            'services' => fn($q) => $q->where('is_active', true),
            'employees' => fn($q) => $q->where('is_active', true),
            'workingHours',
            'reviews',
        ])->whereHas('company', fn($q) => $q->where('status', 'active')
            ->whereHas('category', fn($q2) => $q2->where('slug', $slug)));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%$search%")
                  ->orWhere('name_ar', 'like', "%$search%")
                  ->orWhere('address', 'like', "%$search%")
                  ->orWhereHas('company', fn($cq) => $cq->where('name_en', 'like', "%$search%")->orWhere('name_ar', 'like', "%$search%"));
            });
        }

        $branches = $query->paginate(12)->withQueryString();
        return view('front.category', compact('category', 'categories', 'branches'));
    }

    public function branchesJson(Request $request)
    {
        $isAr   = app()->getLocale() === 'ar';
        $query  = \App\Models\Branch::with([
            'company.category', 'images', 'reviews',
            'services' => fn($q) => $q->where('is_active', true),
        ])->whereHas('company', fn($q) => $q->where('status', 'active'));

        if ($request->filled('category')) {
            $query->whereHas('company.category', fn($q) => $q->where('slug', $request->category));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name_en','like',"%$s%")->orWhere('name_ar','like',"%$s%")
                ->orWhere('address','like',"%$s%")
                ->orWhereHas('company', fn($c) => $c->where('name_en','like',"%$s%")->orWhere('name_ar','like',"%$s%")));
        }

        $branches = $query->paginate(12)->withQueryString();

        $items = $branches->map(function($b) use ($isAr) {
            $img     = $b->images->first();
            $reviews = $b->reviews;
            $avg     = $reviews->count() ? round($reviews->avg('rating'), 1) : null;
            $company = $b->company;
            return [
                'id'           => $b->id,
                'name'         => $isAr ? ($b->name_ar ?? $b->name_en) : ($b->name_en ?? $b->name_ar),
                'company_name' => $isAr ? ($company->name_ar ?? $company->name_en) : ($company->name_en ?? $company->name_ar),
                'company_logo' => $company->logo ? asset('storage/'.$company->logo) : null,
                'image'        => $img ? asset('storage/'.$img->path) : ($company->logo ? asset('storage/'.$company->logo) : null),
                'category'     => $company->category ? ($isAr ? $company->category->name_ar : $company->category->name_en) : null,
                'address'      => $b->address,
                'avg_rating'   => $avg,
                'review_count' => $reviews->count(),
                'svc_count'    => $b->services->count(),
                'url'          => route('front.branch', $b),
            ];
        });

        return response()->json([
            'items'       => $items,
            'total'       => $branches->total(),
            'has_more'    => $branches->hasMorePages(),
            'next_page'   => $branches->nextPageUrl(),
        ]);
    }

    public function mapBranches()
    {
        $isAr = app()->getLocale() === 'ar';
        $branches = \App\Models\Branch::with(['company.category','images','reviews'])
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

    public function index3(Request $request)
    {
        $categories = Category::withCount('companies')->orderBy('sort_order')->get();
        $branches = \App\Models\Branch::with([
            'company.category','images','reviews',
            'services' => fn($q) => $q->where('is_active', true),
        ])->whereHas('company', fn($q) => $q->where('status', 'active'))
          ->paginate(12)->withQueryString();
        return view('front.index3', compact('categories', 'branches'));
    }

    public function index2(Request $request)
    {
        $categories = Category::withCount('companies')->orderBy('sort_order')->get();

        $query = \App\Models\Branch::with([
            'company.category',
            'company' => fn($q) => $q->where('status', 'active'),
            'images',
            'reviews',
            'services' => fn($q) => $q->where('is_active', true),
        ])->whereHas('company', fn($q) => $q->where('status', 'active'));

        if ($request->filled('category')) {
            $query->whereHas('company.category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%$search%")
                  ->orWhere('name_ar', 'like', "%$search%")
                  ->orWhere('address', 'like', "%$search%")
                  ->orWhereHas('company', fn($cq) => $cq->where('name_en', 'like', "%$search%")
                      ->orWhere('name_ar', 'like', "%$search%"));
            });
        }

        $branches = $query->paginate(12)->withQueryString();

        return view('front.index2', compact('categories', 'branches'));
    }

    public function index(Request $request)
    {
        $categories = Category::withCount('companies')->orderBy('sort_order')->get();

        $query = \App\Models\Branch::with([
            'company.category',
            'company' => fn($q) => $q->where('status', 'active'),
            'images',
            'reviews',
            'services' => fn($q) => $q->where('is_active', true),
        ])->whereHas('company', fn($q) => $q->where('status', 'active'));

        if ($request->filled('category')) {
            $query->whereHas('company.category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%$search%")
                  ->orWhere('name_ar', 'like', "%$search%")
                  ->orWhere('address', 'like', "%$search%")
                  ->orWhereHas('company', fn($cq) => $cq->where('name_en', 'like', "%$search%")
                      ->orWhere('name_ar', 'like', "%$search%"));
            });
        }

        $branches = $query->paginate(12)->withQueryString();

        return view('front.index', compact('categories', 'branches'));
    }
}
