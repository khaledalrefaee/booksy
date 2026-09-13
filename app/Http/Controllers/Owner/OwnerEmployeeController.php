<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Services\Owner\OwnerAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * GlowRez internal team management (/owner/employees). Employees live in the
 * owners table and authenticate through the owner guard; this screen creates
 * them, sets their role + fine-grained permissions, and enables/disables
 * their login. Guarded by owner.can:employees.manage.
 */
class OwnerEmployeeController extends Controller
{
    private function actor(): Owner
    {
        return Auth::guard('owner')->user();
    }

    /** Roles this actor is allowed to assign (a non-super-admin can't mint one). */
    private function assignableRoles(): array
    {
        $roles = array_keys((array) config('owner-permissions.role_meta', []));

        if (! $this->actor()->isSuperAdmin()) {
            $roles = array_values(array_diff($roles, ['super_admin']));
        }

        return $roles;
    }

    /** Flat list of every real permission key from the catalog. */
    private function catalogKeys(): array
    {
        return collect((array) config('owner-permissions.catalog', []))
            ->flatMap(fn ($group) => array_keys($group['permissions'] ?? []))
            ->values()
            ->all();
    }

    /**
     * Cleans a submitted permission array: only real catalog keys, and — for a
     * non-super-admin actor — only keys the actor themselves holds, so no one
     * can grant more power than they have.
     */
    private function sanitizePermissions(array $submitted): array
    {
        $allowed = $this->catalogKeys();

        if (! $this->actor()->isSuperAdmin()) {
            $allowed = array_values(array_filter($allowed, fn ($k) => $this->actor()->hasPermission($k)));
        }

        return array_values(array_intersect($allowed, $submitted));
    }

    /** Employees the actor is allowed to act on (can't touch super admins unless one). */
    private function findManageable(string $id): Owner
    {
        $employee = Owner::query()->findOrFail($id);

        if ($employee->role === 'super_admin' && ! $this->actor()->isSuperAdmin()) {
            abort(403);
        }

        return $employee;
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $role   = (string) $request->query('role', '');
        $status = (string) $request->query('status', '');

        $employees = Owner::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) =>
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")))
            ->when($role !== '', fn ($q) => $q->where('role', $role))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'disabled', fn ($q) => $q->where('is_active', false))
            ->withCount('fieldVisits')
            ->orderByRaw("CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END")
            ->orderByDesc('last_activity_at')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('owner.team.index', [
            'employees' => $employees,
            'roleMeta'  => config('owner-permissions.role_meta'),
            'search'    => $search,
            'role'      => $role,
            'status'    => $status,
            'stats'     => [
                'total'    => Owner::count(),
                'active'   => Owner::where('is_active', true)->count(),
                'disabled' => Owner::where('is_active', false)->count(),
                'field'    => Owner::where('role', 'field_sales')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('owner.team.create', [
            'roles'    => $this->assignableRoles(),
            'roleMeta' => config('owner-permissions.role_meta'),
            'catalog'  => config('owner-permissions.catalog'),
            'roleDefaults' => config('owner-permissions.roles'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'max:190', 'unique:owners,email'],
            'phone'         => ['nullable', 'string', 'max:40'],
            'role'          => ['required', Rule::in($this->assignableRoles())],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'is_active'     => ['nullable', 'boolean'],
            'password'      => ['nullable', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);

        // The manager may set a password directly; otherwise a one-time password
        // is generated and shown once. A manually-set password only forces a
        // first-login change if the manager ticked that box.
        $manualPassword = filled($data['password'] ?? null);
        $password       = $manualPassword ? $data['password'] : $this->generateTempPassword();
        $mustChange     = $manualPassword ? $request->boolean('must_change_password') : true;

        $employee = new Owner();
        $employee->forceFill([
            'name'                 => $data['name'],
            'email'                => Str::lower($data['email']),
            'phone'                => $data['phone'] ?? null,
            'role'                 => $data['role'],
            'permissions'          => $this->sanitizePermissions($request->input('permissions', [])),
            'is_active'            => $request->boolean('is_active', true),
            'password'             => $password, // hashed by cast
            'must_change_password' => $mustChange,
            'created_by'           => $this->actor()->id,
        ])->save();

        OwnerAudit::record('employee.created', $employee, new: [
            'role'      => $employee->role,
            'is_active' => $employee->is_active,
        ], label: $employee->name);

        $redirect = redirect()->route('owner.team.index')
            ->with('success', __('Employee created.'));

        // Only reveal the password when we generated it — if the manager set it
        // themselves, they already have it.
        if (! $manualPassword) {
            $redirect->with('temp_credentials', [
                'name'     => $employee->name,
                'email'    => $employee->email,
                'password' => $password,
            ]);
        }

        return $redirect;
    }

    public function edit(string $id): View
    {
        $employee = $this->findManageable($id);

        return view('owner.team.edit', [
            'employee'     => $employee,
            'roles'        => $this->assignableRoles(),
            'roleMeta'     => config('owner-permissions.role_meta'),
            'catalog'      => config('owner-permissions.catalog'),
            'roleDefaults' => config('owner-permissions.roles'),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $employee = $this->findManageable($id);

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'max:190', Rule::unique('owners', 'email')->ignore($employee->id)],
            'phone'         => ['nullable', 'string', 'max:40'],
            'role'          => ['required', Rule::in($this->assignableRoles())],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'is_active'     => ['nullable', 'boolean'],
        ]);

        // A super admin's role stays untouched by non-super-admins (blocked
        // above already), and no one may demote the last super admin.
        $employee->fill([
            'name'  => $data['name'],
            'email' => Str::lower($data['email']),
            'phone' => $data['phone'] ?? null,
            'role'  => $data['role'],
        ]);
        $employee->permissions = $this->sanitizePermissions($request->input('permissions', []));

        // Don't let someone lock themselves out.
        if ($employee->id !== $this->actor()->id) {
            $employee->is_active = $request->boolean('is_active', true);
        }

        OwnerAudit::recordChanges('employee.updated', $employee, label: $employee->name);
        $employee->save();

        return redirect()->route('owner.team.index')
            ->with('success', __('Employee updated.'));
    }

    public function toggleActive(string $id): RedirectResponse
    {
        $employee = $this->findManageable($id);

        if ($employee->id === $this->actor()->id) {
            return back()->with('error', __('You cannot disable your own account.'));
        }

        $employee->is_active   = ! $employee->is_active;
        $employee->disabled_at = $employee->is_active ? null : now();
        $employee->save();

        OwnerAudit::record(
            $employee->is_active ? 'employee.enabled' : 'employee.disabled',
            $employee,
            label: $employee->name,
        );

        return back()->with('success', $employee->is_active
            ? __('Account enabled.')
            : __('Account disabled.'));
    }

    public function resetPassword(string $id): RedirectResponse
    {
        $employee = $this->findManageable($id);

        $tempPassword = $this->generateTempPassword();
        $employee->forceFill([
            'password'             => $tempPassword,
            'must_change_password' => true,
        ])->save();

        OwnerAudit::record('employee.password.reset', $employee, label: $employee->name);

        return back()
            ->with('success', __('Password reset. Share the new temporary password.'))
            ->with('temp_credentials', [
                'name'     => $employee->name,
                'email'    => $employee->email,
                'password' => $tempPassword,
            ]);
    }

    public function destroy(string $id): RedirectResponse
    {
        $employee = $this->findManageable($id);

        if ($employee->id === $this->actor()->id) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        if ($employee->role === 'super_admin' && Owner::where('role', 'super_admin')->count() <= 1) {
            return back()->with('error', __('You cannot delete the last super admin.'));
        }

        OwnerAudit::record('employee.deleted', $employee, old: [
            'name'  => $employee->name,
            'email' => $employee->email,
            'role'  => $employee->role,
        ], label: $employee->name);

        $employee->delete();

        return redirect()->route('owner.team.index')
            ->with('success', __('Employee deleted.'));
    }

    /** Readable one-time password, e.g. "Glow-7F3K-92". */
    private function generateTempPassword(): string
    {
        return 'Glow-'.Str::upper(Str::random(4)).'-'.random_int(10, 99);
    }
}
