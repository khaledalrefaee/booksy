<?php

/*
 * Owner-panel roles and their permissions.
 *
 * '*' grants everything. Permission keys follow "module.action".
 * New pages must check a key from here via Gate 'owner-can' even if,
 * for now, every admin is a super_admin — the check points are what matter.
 *
 * Catalog of keys enforced in routes/owner.php and owner views:
 *
 *   companies.manage        create / edit / delete companies, change status & subscription
 *   companies.impersonate   log in as a company
 *   plans.manage            create / edit / delete subscription plans
 *   billing.view            subscriptions + subscription payments pages
 *   billing.record-payment  record / edit subscription payments
 *   billing.void-payment    void subscription payments
 *   coupons.manage          subscription coupons CRUD
 *   reviews.moderate        hide / unhide customer reviews
 *   notifications.send      broadcast announcements to companies
 *   reports.view            growth + revenue reports
 *   audit-log.view          read the owner audit log
 *   operations.view         cross-tenant customers & invoices directories
 *   locations.manage        countries / governorates / areas CRUD
 */

return [

    'roles' => [

        'super_admin' => ['*'],

        // Future roles — fill in when the first non-owner admin is hired.
        // 'operations' => ['companies.manage', 'operations.view', 'locations.manage'],
        // 'support'    => ['operations.view', 'audit-log.view'],
        // 'accountant' => ['billing.view', 'billing.record-payment', 'reports.view'],
    ],

];
