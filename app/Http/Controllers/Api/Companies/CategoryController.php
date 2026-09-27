<?php

namespace App\Http\Controllers\Api\Companies;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

/**
 * Business categories (salon, spa, clinic…) — public, so the app can fill the
 * sign-up form's category picker and send the chosen `id` as `category_id`
 * to POST /api/company/register. Same order as the web registration page.
 */
class CategoryController extends ApiController
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Category $category) => $this->present($category));

        return $this->success(['categories' => $categories]);
    }

    /** The public shape of a category returned to the app. */
    private function present(Category $category): array
    {
        // `icon` is either a Feather icon name ("scissors") or an uploaded
        // file path ("categories/…png") — expose each in its own field.
        $iconIsFile = $category->icon && str_contains($category->icon, '/');

        return [
            'id'       => $category->id,
            'slug'     => $category->slug,
            'name'     => $category->localizedName(),
            'name_en'  => $category->name_en,
            'name_ar'  => $category->name_ar,
            'icon'     => $iconIsFile ? null : $category->icon,
            'icon_url' => $iconIsFile ? asset('storage/'.$category->icon) : null,
            'image'    => $category->image ? asset('storage/'.$category->image) : null,
        ];
    }
}
