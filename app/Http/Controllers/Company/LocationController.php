<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function governorates(Request $request): JsonResponse
    {
        $request->validate(['country_id' => ['required', 'integer']]);

        $govs = Governorate::where('country_id', $request->country_id)
            ->orderBy('sort_order')
            ->get(['id', 'name_en', 'name_ar']);

        return response()->json($govs->map(fn ($g) => [
            'id'    => $g->id,
            'label' => app()->getLocale() === 'ar' ? ($g->name_ar ?: $g->name_en) : ($g->name_en ?: $g->name_ar),
        ]));
    }

    public function areas(Request $request): JsonResponse
    {
        $request->validate(['governorate_id' => ['required', 'integer']]);

        $areas = Area::where('governorate_id', $request->governorate_id)
            ->orderBy('sort_order')
            ->get(['id', 'name_en', 'name_ar']);

        return response()->json($areas->map(fn ($a) => [
            'id'    => $a->id,
            'label' => app()->getLocale() === 'ar' ? ($a->name_ar ?: $a->name_en) : ($a->name_en ?: $a->name_ar),
        ]));
    }
}
