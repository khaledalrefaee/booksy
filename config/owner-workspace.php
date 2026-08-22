<?php

/*
 * Company Workspace tab registry — single source of truth for the owner
 * per-company management hub (resources/views/owner/companies/show.blade.php).
 *
 * The shell renders the tab nav from this list and builds the JS URL map;
 * each tab is a lazy-loaded partial served by an Owner\Workspace controller.
 *
 *   key        route/controller slug (owner.companies.ws.{key})
 *   label      i18n label (passed through __())
 *   icon       feather icon name
 *   permission owner-can key required to see/open the tab
 *   feature    company plan feature key; if set and the company lacks it the
 *              tab still opens but shows a graceful "feature locked" notice
 *              (handled inside the tab partial via $ws->feature()).
 */

return [

    'tabs' => [
        'overview'     => ['label' => 'Overview',     'icon' => 'grid',        'permission' => 'company-workspace.view', 'feature' => null],
        'branches'     => ['label' => 'Branches',     'icon' => 'map-pin',     'permission' => 'company-workspace.view', 'feature' => null],
        'services'     => ['label' => 'Services',     'icon' => 'scissors',    'permission' => 'company-workspace.view', 'feature' => null],
        'team'         => ['label' => 'Team',         'icon' => 'users',       'permission' => 'company-workspace.view', 'feature' => null],
        'payroll'      => ['label' => 'Payroll',      'icon' => 'dollar-sign', 'permission' => 'payroll.manage',         'feature' => 'payroll'],
        'appointments' => ['label' => 'Appointments', 'icon' => 'calendar',    'permission' => 'company-workspace.view', 'feature' => null],
        'customers'    => ['label' => 'Customers',    'icon' => 'user',        'permission' => 'operations.view',        'feature' => null],
        'finance'      => ['label' => 'Finance',      'icon' => 'credit-card', 'permission' => 'finance.manage',         'feature' => 'finance'],
        'inventory'    => ['label' => 'Inventory',    'icon' => 'package',     'permission' => 'inventory.manage',       'feature' => 'inventory'],
        'insights'     => ['label' => 'Insights',     'icon' => 'bar-chart-2', 'permission' => 'reports.view',           'feature' => 'reports'],
        'settings'     => ['label' => 'Settings',     'icon' => 'settings',    'permission' => 'company-workspace.view', 'feature' => null],
    ],

];
