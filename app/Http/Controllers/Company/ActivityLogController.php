<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    private function companyQuery()
    {
        $company = Auth::guard('company')->user();

        return ActivityLog::query()
            ->where('causer_type', 'company')
            ->where('causer_id', $company->id);
    }

    public function index(Request $request): View
    {
        $query = $this->companyQuery()->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q
                ->where('description', 'like', "%$s%")
                ->orWhere('causer_name', 'like', "%$s%")
            );
        }

        if ($request->filled('subject')) {
            $query->where('subject_type', 'like', '%' . $request->input('subject') . '%');
        }

        $logs = $query->paginate(10)->withQueryString();
        $totalCount = $this->companyQuery()->count();

        return view('company.activity-log.index', compact('logs', 'totalCount'));
    }

    public function destroySelected(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = $this->companyQuery()
            ->whereIn('id', $data['ids'])
            ->delete();

        return back()->with('success', __(':count log(s) deleted.', ['count' => $deleted]));
    }

    public function destroyAll(): RedirectResponse
    {
        $deleted = $this->companyQuery()->delete();

        return redirect()->route('company.activity-log.index')
            ->with('success', __(':count log(s) deleted.', ['count' => $deleted]));
    }
}
