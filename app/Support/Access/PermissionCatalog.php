<?php

namespace App\Support\Access;

use App\Models\Permission;
use App\Models\Role;

/**
 * Presentation layer over the raw permission slugs: it groups them into the
 * handful of "modules" an owner actually reasons about (Appointments, Customers…)
 * and reduces each module to a single friendly level — Manage / View / No access.
 *
 * This is what powers the read-only Permissions Summary and the Advanced panel,
 * so the owner never sees a wall of raw slugs. The Advanced panel writes back the
 * same four levels, which {@see EmployeeAccessWriter} translates into grant/deny
 * override rows.
 */
class PermissionCatalog
{
    public const LEVEL_DEFAULT = 'default';
    public const LEVEL_NONE    = 'none';
    public const LEVEL_VIEW    = 'view';
    public const LEVEL_MANAGE  = 'manage';

    /**
     * Ordered summary modules. `view` / `manage` are the representative slugs;
     * either may be null when a module has only one of them.
     *
     * @var list<array{key:string, en:string, ar:string, view:?string, manage:?string}>
     */
    public const MODULES = [
        ['key' => 'appointments', 'en' => 'Appointments', 'ar' => 'المواعيد',        'view' => 'appointments.view', 'manage' => 'appointments.manage'],
        ['key' => 'waitlist',     'en' => 'Waitlist',     'ar' => 'قائمة الانتظار', 'view' => 'waitlist.view',     'manage' => 'waitlist.manage'],
        ['key' => 'customers',    'en' => 'Customers',    'ar' => 'العملاء',         'view' => 'customers.view',    'manage' => 'customers.manage'],
        ['key' => 'services',     'en' => 'Services',     'ar' => 'الخدمات',         'view' => 'services.view',     'manage' => 'services.manage'],
        ['key' => 'inventory',    'en' => 'Inventory',    'ar' => 'المخزون',         'view' => 'inventory.view',    'manage' => 'inventory.manage'],
        ['key' => 'cash',         'en' => 'Cash & Payments', 'ar' => 'الصندوق والمدفوعات', 'view' => 'cash.view',   'manage' => 'cash.manage'],
        ['key' => 'attendance',   'en' => 'Attendance',   'ar' => 'الحضور',          'view' => 'attendance.view',   'manage' => 'attendance.manage'],
        ['key' => 'employees',    'en' => 'Employees',    'ar' => 'الموظفون',        'view' => 'employees.view',    'manage' => 'employees.manage'],
        ['key' => 'reports',      'en' => 'Reports',      'ar' => 'التقارير',        'view' => 'reports.view',      'manage' => null],
    ];

    /** @return list<array{key:string, en:string, ar:string, view:?string, manage:?string}> */
    public function modules(): array
    {
        return self::MODULES;
    }

    /** slug => id, cached for the request. */
    public function slugToId(): array
    {
        static $map = null;

        return $map ??= Permission::query()->pluck('id', 'slug')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Reduce a set of owned permission slugs to a per-module level.
     *
     * @param  iterable<string>  $ownedSlugs
     * @return array<string,string>  moduleKey => manage|view|none
     */
    public function summaryFromSlugs(iterable $ownedSlugs, bool $fullAccess = false): array
    {
        $owned = collect($ownedSlugs)->flip();
        $summary = [];

        foreach (self::MODULES as $m) {
            if ($fullAccess) {
                $summary[$m['key']] = self::LEVEL_MANAGE;
                continue;
            }
            if ($m['manage'] && $owned->has($m['manage'])) {
                $summary[$m['key']] = self::LEVEL_MANAGE;
            } elseif ($m['view'] && ($owned->has($m['view']) || $owned->has($m['view'].'_own'))) {
                $summary[$m['key']] = self::LEVEL_VIEW;
            } else {
                $summary[$m['key']] = self::LEVEL_NONE;
            }
        }

        return $summary;
    }

    /**
     * Reverse of {@see EmployeeAccessWriter}: turn stored grant/deny override rows
     * back into friendly module levels for pre-filling the edit form. Only modules
     * that actually carry an override are returned (others stay "default").
     *
     * @param  array<int,string>  $effectsByPermId  permissionId => grant|deny
     * @return array<string,string>  moduleKey => none|view|manage
     */
    public function levelsFromEffects(array $effectsByPermId): array
    {
        $idToEffect = $effectsByPermId;
        $slugToId = $this->slugToId();
        $levels = [];

        foreach (self::MODULES as $m) {
            $vId = $m['view'] ? ($slugToId[$m['view']] ?? null) : null;
            $mId = $m['manage'] ? ($slugToId[$m['manage']] ?? null) : null;
            $vEff = $vId ? ($idToEffect[$vId] ?? null) : null;
            $mEff = $mId ? ($idToEffect[$mId] ?? null) : null;

            if ($mEff === 'grant') {
                $levels[$m['key']] = self::LEVEL_MANAGE;
            } elseif ($mEff === 'deny') {
                $levels[$m['key']] = $vEff === 'grant' ? self::LEVEL_VIEW : self::LEVEL_NONE;
            } elseif ($vEff === 'grant') {
                $levels[$m['key']] = self::LEVEL_VIEW; // module without a manage slug (e.g. reports)
            } elseif ($vEff === 'deny') {
                $levels[$m['key']] = self::LEVEL_NONE;
            }
        }

        return $levels;
    }

    /** Per-role default summary keyed by role id — fed to the form as JSON. */
    public function roleSummaries(): array
    {
        return Role::with('permissions:id,slug')->get()
            ->mapWithKeys(fn (Role $role) => [
                $role->id => $this->summaryFromSlugs($role->permissions->pluck('slug')),
            ])->all();
    }
}
