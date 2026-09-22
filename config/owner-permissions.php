<?php

/*
 * Owner-panel roles, their default permissions, and the full permission
 * catalog used to build the per-employee access UI.
 *
 * Access is resolved as:  (role default set)  ∪  (owner.permissions overrides)
 * so it is permission-based and expandable — never hard-coded per page.
 * '*' grants everything. Permission keys follow "module.action".
 *
 * To add a capability: add its key under `catalog`, then reference it in a
 * route via ->middleware('owner.can:key') or in a view via
 * @can('owner-can', 'key'). Assign it to roles below and/or per employee.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Role → default permissions
    |--------------------------------------------------------------------------
    | These are the starting point for each role. Individual employees can be
    | granted extra keys on top (owners.permissions). super_admin = everything.
    */
    'roles' => [

        'super_admin' => ['*'],

        'admin' => [
            'owner-dashboard.view',
            'employees.manage',
            'companies.manage',
            'companies.impersonate',
            'company-workspace.view',
            'appointments.manage',
            'catalog.manage',
            'attendance.manage',
            'finance.manage',
            'payroll.manage',
            'inventory.manage',
            'plans.manage',
            'billing.view',
            'billing.record-payment',
            'billing.void-payment',
            'coupons.manage',
            'reviews.moderate',
            'photos.review',
            'notifications.send',
            'reports.view',
            'audit-log.view',
            'operations.view',
            'locations.manage',
            'field-visits.view.all',
            'field-visits.review',
        ],

        'sales_manager' => [
            'owner-dashboard.view',
            'field-visits.create',
            'field-visits.view.own',
            'field-visits.view.all',
            'field-visits.review',
            'employees.manage',
            'reports.view',
            'operations.view',
        ],

        // Field reps live in the /employee area only — no owner-dashboard.view.
        'field_sales' => [
            'field-visits.create',
            'field-visits.view.own',
        ],

        'marketing' => [
            'owner-dashboard.view',
            'notifications.send',
            'reviews.moderate',
            'photos.review',
            'reports.view',
        ],

        'support' => [
            'owner-dashboard.view',
            'operations.view',
            'company-workspace.view',
            'audit-log.view',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role metadata (for the employees UI: label, plain description, icon)
    |--------------------------------------------------------------------------
    | Labels/descriptions are translation keys — run through __() in views.
    */
    'role_meta' => [
        'super_admin'   => ['label' => 'Super admin',   'desc' => 'Full, unrestricted access to everything.',                 'icon' => 'shield'],
        'admin'         => ['label' => 'Admin',          'desc' => 'Manages the platform, companies, billing and the team.',   'icon' => 'sliders'],
        'sales_manager' => ['label' => 'Sales manager',  'desc' => 'Leads the sales team, reviews field visits and reports.',  'icon' => 'trending-up'],
        'field_sales'   => ['label' => 'Field sales',    'desc' => 'Visits venues in the field and logs each visit.',         'icon' => 'map-pin'],
        'marketing'     => ['label' => 'Marketing',      'desc' => 'Runs announcements, campaigns and reviews.',               'icon' => 'send'],
        'support'       => ['label' => 'Support',        'desc' => 'Helps companies and looks up operations data.',            'icon' => 'life-buoy'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission catalog (grouped) — drives the access checkboxes UI
    |--------------------------------------------------------------------------
    | Each label is a translation key. Add new keys here to expand the system.
    */
    'catalog' => [

        'access' => [
            'label' => 'Access',
            'permissions' => [
                'owner-dashboard.view' => 'Open the Owner dashboard',
            ],
        ],

        'team' => [
            'label' => 'Team',
            'permissions' => [
                'employees.manage' => 'Manage employees & permissions',
            ],
        ],

        'field_sales' => [
            'label' => 'Field sales',
            'permissions' => [
                'field-visits.create'   => 'Log field visits',
                'field-visits.view.own' => 'See own field visits',
                'field-visits.view.all' => 'See all reps’ visits & reports',
                'field-visits.review'   => 'Review & approve flagged visits',
            ],
        ],

        'companies' => [
            'label' => 'Companies',
            'permissions' => [
                'companies.manage'       => 'Create / edit / suspend companies',
                'companies.impersonate'  => 'Log in as a company',
                'company-workspace.view' => 'Open a company workspace',
                'appointments.manage'    => 'Manage appointments in workspace',
                'catalog.manage'         => 'Edit services & pricing in workspace',
                'attendance.manage'      => 'Attendance actions in workspace',
                'finance.manage'         => 'Finance actions in workspace',
                'payroll.manage'         => 'Payroll actions in workspace',
                'inventory.manage'       => 'Inventory actions in workspace',
                'operations.view'        => 'Cross-tenant customers & invoices',
            ],
        ],

        'billing' => [
            'label' => 'Billing',
            'permissions' => [
                'plans.manage'          => 'Manage subscription plans',
                'billing.view'          => 'View subscriptions & payments',
                'billing.record-payment' => 'Record / edit payments',
                'billing.void-payment'  => 'Void payments',
                'coupons.manage'        => 'Manage coupons',
            ],
        ],

        'growth' => [
            'label' => 'Growth & content',
            'permissions' => [
                'reviews.moderate'   => 'Moderate reviews',
                'photos.review'      => 'Review & approve branch photos',
                'notifications.send' => 'Send announcements & emails',
                'reports.view'       => 'View growth & revenue reports',
            ],
        ],

        'platform' => [
            'label' => 'Platform',
            'permissions' => [
                'audit-log.view'  => 'Read the audit log',
                'locations.manage' => 'Manage countries / areas',
            ],
        ],
    ],

];
