<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\CategoryUploadedImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $q         = trim($request->input('q', ''));
        $sortField = in_array($request->input('sort'), ['name', 'sort_order', 'created_at']) ? $request->input('sort') : 'sort_order';
        $sortDir   = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $query = Category::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%");
            });
        }

        if ($sortField === 'name') {
            $query->orderByRaw("COALESCE(NULLIF(name_en,''), name_ar) {$sortDir}");
        } else {
            $query->orderBy($sortField, $sortDir);
        }

        $categories = $query->paginate(15)->withQueryString();

        return view('owner.category.index', compact('categories', 'q', 'sortField', 'sortDir'));
    }

  

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'icon' => ['nullable', 'image', 'max:4096'],
        ]);
    
      
    
        $slugSource =
            $validated['name_en'] ?? Str::uuid()->toString();
    
        $data = [
            'name_en' => $request -> name_en,
            'name_ar' => $request -> name_ar,
    
            'slug' => Str::slug($slugSource),
               
        ];
        $data['sort_order'] = $request->input('sort_order', null);
    
        if ($request->hasFile('image')) {
            $data['image'] = CategoryUploadedImage::storeImage(
                $request->file('image')
            );
        }
    
        if ($request->hasFile('icon')) {
            $data['icon'] = CategoryUploadedImage::storeIcon(
                $request->file('icon')
            );
        }
    
        $category = Category::query()->create($data);
    
      if (!$request->filled('sort_order')) {
            $category->update([
                'sort_order' => $category->id,
            ]);
        }
        
        return redirect()
            ->route('owner.categories.index')
            ->with('success', __('Category created successfully.'));
    }



    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'image', 'max:4096'],
            'icon' => ['nullable', 'image', 'max:4096'],
        ]);

    
        $category->name_en = $validated['name_en'] ?? null;
        $category->name_ar = $validated['name_ar'] ?? null;

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $category->image = CategoryUploadedImage::storeImage($request->file('image'));
        }
        if ($request->hasFile('icon')) {
            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }
            $category->icon = CategoryUploadedImage::storeIcon($request->file('icon'));
        }

        if (isset($validated['sort_order']) && $validated['sort_order'] !== null) {
            $category->sort_order = (int) $validated['sort_order'];
        }
        $category->save();

        return redirect()->route('owner.categories.index')
            ->with('success', __('Category updated successfully.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        if ($category->icon) {
            Storage::disk('public')->delete($category->icon);
        }

        $category->delete();

        return redirect()->route('owner.categories.index')
            ->with('success', __('Category deleted successfully.'));
    }
}
