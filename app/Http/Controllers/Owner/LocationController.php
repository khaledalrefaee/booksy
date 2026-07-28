<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Governorate;
use App\Models\Area;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    // ── Countries ──────────────────────────────────────────────────────────

    public function index(): View
    {
        $countries    = Country::withCount(['governorates', 'governorates as areas_count' => fn ($q) => $q])
            ->orderBy('sort_order')->get();
        $governorates = Governorate::with('country')->withCount('areas')->orderBy('country_id')->orderBy('sort_order')->get();
        $areas        = Area::with('governorate.country')->orderBy('governorate_id')->orderBy('sort_order')->get();

        return view('owner.locations.index', compact('countries', 'governorates', 'areas'));
    }

    public function storeCountry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_en'    => ['required', 'string', 'max:100'],
            'name_ar'    => ['required', 'string', 'max:100'],
            'code'       => ['required', 'string', 'max:3', 'unique:countries,code'],
            'dial_code'  => ['nullable', 'string', 'max:10'],
            'sort_order' => ['integer', 'min:0'],
        ]);
        $country = Country::create($data);
        OwnerAudit::record('location.country.created', $country, null, $data);
        return back()->with('success', __('Country added.'));
    }

    public function updateCountry(Request $request, Country $country): RedirectResponse
    {
        $data = $request->validate([
            'name_en'    => ['required', 'string', 'max:100'],
            'name_ar'    => ['required', 'string', 'max:100'],
            'code'       => ['required', 'string', 'max:3', 'unique:countries,code,' . $country->id],
            'dial_code'  => ['nullable', 'string', 'max:10'],
            'sort_order' => ['integer', 'min:0'],
        ]);
        $country->fill($data);
        OwnerAudit::recordChanges('location.country.updated', $country);
        $country->save();
        return back()->with('success', __('Country updated.'));
    }

    public function destroyCountry(Country $country): RedirectResponse
    {
        OwnerAudit::record('location.country.deleted', $country, $country->only(['name_en', 'name_ar', 'code']));
        $country->delete();
        return back()->with('success', __('Country deleted.'));
    }

    // ── Governorates ───────────────────────────────────────────────────────

    public function storeGovernorate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'name_en'    => ['required', 'string', 'max:100'],
            'name_ar'    => ['required', 'string', 'max:100'],
            'sort_order' => ['integer', 'min:0'],
        ]);
        $governorate = Governorate::create($data);
        OwnerAudit::record('location.governorate.created', $governorate, null, $data);
        return back()->with('success', __('Governorate added.'));
    }

    public function updateGovernorate(Request $request, Governorate $governorate): RedirectResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'name_en'    => ['required', 'string', 'max:100'],
            'name_ar'    => ['required', 'string', 'max:100'],
            'sort_order' => ['integer', 'min:0'],
        ]);
        $governorate->fill($data);
        OwnerAudit::recordChanges('location.governorate.updated', $governorate);
        $governorate->save();
        return back()->with('success', __('Governorate updated.'));
    }

    public function destroyGovernorate(Governorate $governorate): RedirectResponse
    {
        OwnerAudit::record('location.governorate.deleted', $governorate, $governorate->only(['name_en', 'name_ar', 'country_id']));
        $governorate->delete();
        return back()->with('success', __('Governorate deleted.'));
    }

    // ── Areas ──────────────────────────────────────────────────────────────

    public function storeArea(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'name_en'        => ['required', 'string', 'max:100'],
            'name_ar'        => ['required', 'string', 'max:100'],
            'sort_order'     => ['integer', 'min:0'],
        ]);
        $area = Area::create($data);
        OwnerAudit::record('location.area.created', $area, null, $data);
        return back()->with('success', __('Area added.'));
    }

    public function updateArea(Request $request, Area $area): RedirectResponse
    {
        $data = $request->validate([
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'name_en'        => ['required', 'string', 'max:100'],
            'name_ar'        => ['required', 'string', 'max:100'],
            'sort_order'     => ['integer', 'min:0'],
        ]);
        $area->fill($data);
        OwnerAudit::recordChanges('location.area.updated', $area);
        $area->save();
        return back()->with('success', __('Area updated.'));
    }

    public function destroyArea(Area $area): RedirectResponse
    {
        OwnerAudit::record('location.area.deleted', $area, $area->only(['name_en', 'name_ar', 'governorate_id']));
        $area->delete();
        return back()->with('success', __('Area deleted.'));
    }

    // ── AJAX ───────────────────────────────────────────────────────────────

    public function governoratesByCountry(Request $request): JsonResponse
    {
        $request->validate(['country_id' => ['required', 'integer']]);
        return response()->json(
            Governorate::where('country_id', $request->country_id)
                ->orderBy('sort_order')
                ->get(['id', 'name_en', 'name_ar'])
        );
    }
}
