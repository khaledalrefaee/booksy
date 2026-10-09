<?php

/*
 |--------------------------------------------------------------------------
 | Pre-launch lead capture
 |--------------------------------------------------------------------------
 | Everything the public /welcome → /for-business → /join funnel and the
 | /owner/leads CRM need to know. Labels are bilingual ('ar' / 'en') and are
 | resolved by App\Support\LeadCatalog — add an option here and it appears in
 | the form, the filters, the table and the email with no other change.
 |
 | Deliberately NOT guessed: the notification inbox, the WhatsApp number and the
 | Instagram URL come from .env only. When one is empty the matching button /
 | email is simply not rendered or sent (and the owner page says so).
 */
return [

    // Inbox that receives "new lead" emails. Unset ⇒ no email is sent.
    'notification_email' => env('LEADS_NOTIFICATION_EMAIL', env('GLOWREZ_LEADS_NOTIFICATION_EMAIL')),

    // Language of the notification email body (the team reads Arabic).
    'email_locale' => env('LEADS_EMAIL_LOCALE', 'ar'),

    // Public contact points (international format, digits only: 9639XXXXXXXX).
    'whatsapp_number' => env('LEADS_WHATSAPP_NUMBER', env('GLOWREZ_WHATSAPP_NUMBER')),
    'instagram_url'   => env('LEADS_INSTAGRAM_URL', env('GLOWREZ_INSTAGRAM_URL')),

    // Show the 3-way gateway on "/" for first-time visitors (customers can still
    // pass through with ?enter=customer). Off by default: "/" stays the marketplace.
    'gateway_on_root' => (bool) env('LEADS_GATEWAY_ON_ROOT', false),

    // Pre-launch browse-only mode: the public pages hide Log in / Register for customers and businesses
    // (visitors can browse and register INTEREST instead). Routes stay alive; set false at launch.
    'browse_only' => (bool) env('LEADS_BROWSE_ONLY', true),

    // Used to turn a local number (09xx…) into an international one.
    'default_country_code' => env('LEADS_COUNTRY_CODE', '963'),

    // First-touch attribution is remembered this long (cookie), so a visitor who
    // comes back later via a plain link still counts toward the original campaign.
    'attribution_days' => 30,

    // Form anti-spam
    'min_fill_seconds' => 3,      // faster than this ⇒ a script, not a person
    'max_form_age_days' => 7,     // a stale cached page must be reloaded
    'rate_limit' => [
        'per_ten_minutes' => 5,   // submissions per IP
        'per_day'         => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipeline
    |--------------------------------------------------------------------------
    | tone → maps to the owner UI badge colours (bm-badge-*).
    */
    'statuses' => [
        'new'            => ['ar' => 'جديد',               'en' => 'New',            'tone' => 'gold'],
        'contacted'      => ['ar' => 'تم التواصل',          'en' => 'Contacted',      'tone' => 'info'],
        'qualified'      => ['ar' => 'مهتم مؤهل',           'en' => 'Qualified',      'tone' => 'active'],
        'demo_scheduled' => ['ar' => 'تم تحديد Demo',       'en' => 'Demo scheduled', 'tone' => 'active'],
        'negotiation'    => ['ar' => 'قيد التفاوض',         'en' => 'Negotiation',    'tone' => 'warn'],
        'converted'      => ['ar' => 'انضم إلى GlowRez',    'en' => 'Converted',      'tone' => 'success'],
        'not_interested' => ['ar' => 'غير مهتم',            'en' => 'Not interested', 'tone' => 'muted'],
        'lost'           => ['ar' => 'مغلق / غير متاح',     'en' => 'Lost',           'tone' => 'muted'],
    ],

    // A returning lead in one of these is re-opened as "new".
    'reopen_statuses' => ['not_interested', 'lost'],

    'business_types' => [
        'women_salon'   => ['ar' => 'صالون نسائي',    'en' => 'Women’s salon'],
        'men_salon'     => ['ar' => 'صالون رجالي',    'en' => 'Barbershop'],
        'beauty_center' => ['ar' => 'مركز تجميل',     'en' => 'Beauty center'],
        'spa'           => ['ar' => 'سبا',            'en' => 'Spa'],
        'clinic'        => ['ar' => 'عيادة',          'en' => 'Clinic'],
        'laser'         => ['ar' => 'مركز ليزر',      'en' => 'Laser center'],
        'nails_lashes'  => ['ar' => 'أظافر ورموش',    'en' => 'Nails & lashes'],
        'other'         => ['ar' => 'نوع آخر',        'en' => 'Other'],
    ],

    'interests' => [
        'bookings'            => ['ar' => 'الحجوزات',         'en' => 'Bookings'],
        'employees'           => ['ar' => 'الموظفين',         'en' => 'Employees'],
        'attendance'          => ['ar' => 'الحضور والانصراف', 'en' => 'Attendance'],
        'payroll'             => ['ar' => 'الرواتب',          'en' => 'Payroll'],
        'finance'             => ['ar' => 'الحسابات والمالية', 'en' => 'Finance'],
        'inventory'           => ['ar' => 'المخزون',          'en' => 'Inventory'],
        'marketing'           => ['ar' => 'التسويق',          'en' => 'Marketing'],
        'reports'             => ['ar' => 'التقارير',         'en' => 'Reports'],
        'customer_management' => ['ar' => 'إدارة العملاء',    'en' => 'Customer management'],
        'marketplace'         => ['ar' => 'الظهور في GlowRez', 'en' => 'Marketplace listing'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Where the lead came from
    |--------------------------------------------------------------------------
    | `aliases` normalise whatever ends up in ?source= (ig, wa, fb, …) to one of
    | the keys below, so reports never split "IG" and "instagram" in two.
    */
    'sources' => [
        'instagram' => ['ar' => 'Instagram',       'en' => 'Instagram'],
        'whatsapp'  => ['ar' => 'WhatsApp',        'en' => 'WhatsApp'],
        'facebook'  => ['ar' => 'Facebook',        'en' => 'Facebook'],
        'qr'        => ['ar' => 'رمز QR',          'en' => 'QR code'],
        'field'     => ['ar' => 'مندوب ميداني',    'en' => 'Field sales'],
        'website'   => ['ar' => 'الموقع',          'en' => 'Website'],
        'referral'  => ['ar' => 'ترشيح',           'en' => 'Referral'],
        'direct'    => ['ar' => 'مباشر',           'en' => 'Direct'],
        'other'     => ['ar' => 'أخرى',            'en' => 'Other'],
    ],

    'source_aliases' => [
        'ig' => 'instagram', 'insta' => 'instagram', 'instagram' => 'instagram',
        'wa' => 'whatsapp', 'whatsapp' => 'whatsapp', 'whats' => 'whatsapp',
        'fb' => 'facebook', 'facebook' => 'facebook', 'meta' => 'facebook',
        'qr' => 'qr', 'qrcode' => 'qr',
        'field' => 'field', 'sales' => 'field', 'rep' => 'field', 'fieldsales' => 'field',
        'web' => 'website', 'website' => 'website', 'site' => 'website', 'organic' => 'website', 'google' => 'website',
        'referral' => 'referral', 'ref' => 'referral', 'friend' => 'referral',
        'direct' => 'direct',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cities (Syrian governorates). Free text is also accepted ("other").
    |--------------------------------------------------------------------------
    */
    'cities' => [
        'damascus'     => ['ar' => 'دمشق',          'en' => 'Damascus'],
        'rif_dimashq'  => ['ar' => 'ريف دمشق',      'en' => 'Rural Damascus'],
        'aleppo'       => ['ar' => 'حلب',           'en' => 'Aleppo'],
        'homs'         => ['ar' => 'حمص',           'en' => 'Homs'],
        'hama'         => ['ar' => 'حماة',          'en' => 'Hama'],
        'latakia'      => ['ar' => 'اللاذقية',      'en' => 'Latakia'],
        'tartus'       => ['ar' => 'طرطوس',         'en' => 'Tartus'],
        'idlib'        => ['ar' => 'إدلب',          'en' => 'Idlib'],
        'deir_ez_zor'  => ['ar' => 'دير الزور',     'en' => 'Deir ez-Zor'],
        'raqqa'        => ['ar' => 'الرقة',         'en' => 'Raqqa'],
        'hasakah'      => ['ar' => 'الحسكة',        'en' => 'Al-Hasakah'],
        'daraa'        => ['ar' => 'درعا',          'en' => 'Daraa'],
        'suwayda'      => ['ar' => 'السويداء',      'en' => 'As-Suwayda'],
        'quneitra'     => ['ar' => 'القنيطرة',      'en' => 'Quneitra'],
    ],

];
