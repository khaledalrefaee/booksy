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
        $categories = Category::withCount('companies')->orderBy('sort_order')->get();

        $query = Company::with(['category', 'branches.images', 'branches.reviews'])
            ->where('status', 'active')
            ->whereHas('category', fn($q) => $q->where('slug', $slug));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%$search%")
                  ->orWhere('name_ar', 'like', "%$search%");
            });
        }

        $companies = $query->paginate(12)->withQueryString();

        return view('front.category', compact('category', 'categories', 'companies'));
    }

    public function index(Request $request)
    {
        $categories = Category::withCount('companies')->orderBy('sort_order')->get();

        $query = Company::with(['category', 'branches.images', 'branches.reviews'])
            ->where('status', 'active');

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%$search%")
                  ->orWhere('name_ar', 'like', "%$search%");
            });
        }

        $companies = $query->paginate(12)->withQueryString();

        return view('front.index', compact('categories', 'companies'));
    }
}
