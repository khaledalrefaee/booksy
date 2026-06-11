<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ResolvesOwnerCompany;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeLeaveController extends Controller
{
    use ResolvesOwnerCompany;

    public function index(Request $request): View
    {
        $q            = trim($request->input('q', ''));
        $sortField    = in_array($request->input('sort'), ['start_date', 'created_at']) ? $request->input('sort') : 'start_date';
        $sortDir      = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $filterStatus = $request->input('status', '');

        $query = EmployeeLeave::query()->with(['employee', 'company']);

        if ($q !== '') {
            $query->whereHas('employee', function ($sub) use ($q) {
                $sub->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%");
            });
        }

        if ($filterStatus !== '' && in_array($filterStatus, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $filterStatus);
        }

        $query->orderBy($sortField, $sortDir);

        $leaves = $query->paginate(15)->withQueryString();

        // Stats across all leaves (not filtered) — keep full counts
        $allLeaves = EmployeeLeave::query();
        $statsPending  = (clone $allLeaves)->where('status', 'pending')->count();
        $statsApproved = (clone $allLeaves)->where('status', 'approved')->count();
        $statsRejected = (clone $allLeaves)->where('status', 'rejected')->count();

        return view('owner.employee-leaves.index', compact(
            'leaves', 'q', 'sortField', 'sortDir', 'filterStatus',
            'statsPending', 'statsApproved', 'statsRejected'
        ));
    }

    public function create(Employee $employee): View
    {
        $this->authorizeEmployee($employee);

        return view('owner.employee-leaves.create', compact('employee'));
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($employee);

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'gte:start_date'],
            'reason'     => ['nullable', 'string', 'max:500'],
        ]);

        $employee->leaves()->create([
            'company_id' => $employee->company_id,
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'reason'     => $data['reason'] ?? null,
            'status'     => 'pending',
        ]);

        return redirect()
            ->route('owner.employee-leaves.index')
            ->with('success', __('Leave request submitted.'));
    }

    public function updateStatus(Request $request, EmployeeLeave $employeeLeave): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'notes'  => ['nullable', 'string', 'max:500'],
        ]);

        $employeeLeave->update($data);

        return redirect()
            ->route('owner.employee-leaves.index')
            ->with('success', __('Leave request updated.'));
    }

    public function destroy(EmployeeLeave $employeeLeave): RedirectResponse
    {
        $employeeLeave->delete();

        return redirect()
            ->route('owner.employee-leaves.index')
            ->with('success', __('Leave request deleted.'));
    }
}
