<?php

/*
|--------------------------------------------------------------------------
| GlowRez platform settings — schema
|--------------------------------------------------------------------------
| The single description of every platform setting: its type, limits and
| default. Values live in the `platform_settings` table ("group.key" → text);
| this file drives validation, casting, defaults and the Owner settings tabs,
| so adding a setting means adding one line here (plus its input in the tab).
|
| Types:  string · text · url · email · phone · bool · enum · date · time ·
|         days · int · version
| Options: max (length), options (enum), hosts (URL host allow-list, suffix
|          match), relative (URL may be a site-relative "/path"), https (https
|          only), default.
|
| Nothing here is a made-up GlowRez fact: text/link defaults are null, so the
| public site shows only what an owner has actually entered. The bool defaults
| are visibility switches that only matter once a value exists.
*/

$store = fn (string $label, array $hosts) => ['label' => $label, 'type' => 'url', 'max' => 500, 'https' => true, 'hosts' => $hosts];

return [

    'cache_key' => 'platform_settings.v1',

    // Week order used by the working-days picker (Saturday-first, like the region).
    'weekdays' => ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'],

    // Hosts a "WhatsApp" URL may point to.
    'whatsapp_hosts' => ['wa.me', 'api.whatsapp.com', 'chat.whatsapp.com', 'whatsapp.com'],

    // Quick-add presets for the Links tab. hosts = allowed URL hosts (suffix match).
    'link_presets' => [
        'instagram' => ['label' => 'Instagram', 'hosts' => ['instagram.com']],
        'facebook'  => ['label' => 'Facebook',  'hosts' => ['facebook.com', 'fb.com', 'fb.me']],
        'tiktok'    => ['label' => 'TikTok',    'hosts' => ['tiktok.com']],
        'linkedin'  => ['label' => 'LinkedIn',  'hosts' => ['linkedin.com']],
        'youtube'   => ['label' => 'YouTube',   'hosts' => ['youtube.com', 'youtu.be']],
        'whatsapp'  => ['label' => 'WhatsApp',  'hosts' => ['wa.me', 'api.whatsapp.com', 'chat.whatsapp.com', 'whatsapp.com']],
        'telegram'  => ['label' => 'Telegram',  'hosts' => ['t.me', 'telegram.me']],
        'x'         => ['label' => 'X',         'hosts' => ['x.com', 'twitter.com']],
    ],

    'max_links' => 30,

    'fields' => [

        // ── Tab 1 · General ───────────────────────────────────────────────
        'general.name_ar'         => ['label' => 'Platform name (Arabic)', 'type' => 'string', 'max' => 120],
        'general.name_en'         => ['label' => 'Platform name (English)', 'type' => 'string', 'max' => 120],
        'general.company_name_ar' => ['label' => 'Official company name (Arabic)', 'type' => 'string', 'max' => 160],
        'general.company_name_en' => ['label' => 'Official company name (English)', 'type' => 'string', 'max' => 160],
        'general.website_url'     => ['label' => 'Official website', 'type' => 'url',    'max' => 500],
        'general.email'           => ['label' => 'Official email', 'type' => 'email'],
        'general.phone'           => ['label' => 'Official phone', 'type' => 'phone'],
        'general.description_ar'  => ['label' => 'Short description (Arabic)', 'type' => 'text',   'max' => 300],
        'general.description_en'  => ['label' => 'Short description (English)', 'type' => 'text',   'max' => 300],
        'general.default_locale'  => ['label' => 'Default language', 'type' => 'enum',   'options' => ['ar', 'en']],
        'general.logo'            => ['label' => 'Logo', 'type' => 'string', 'max' => 255],   // storage path on the public disk

        // ── Tab 2 · Support & customer service ────────────────────────────
        'support.email'               => ['label' => 'Support email', 'type' => 'email'],
        'support.email_visible'       => ['label' => 'Show support email', 'type' => 'bool', 'default' => true],
        'support.phone'               => ['label' => 'Support phone', 'type' => 'phone'],
        'support.phone_visible'       => ['label' => 'Show support phone', 'type' => 'bool', 'default' => true],
        'support.whatsapp'            => ['label' => 'Support WhatsApp number', 'type' => 'phone'],
        'support.whatsapp_link'       => ['label' => 'Direct WhatsApp link', 'type' => 'url', 'max' => 500, 'https' => true, 'hosts' => ['wa.me', 'api.whatsapp.com', 'chat.whatsapp.com', 'whatsapp.com']],
        'support.whatsapp_visible'    => ['label' => 'Show WhatsApp', 'type' => 'bool', 'default' => true],
        'support.help_center_url'     => ['label' => 'Help center link', 'type' => 'url', 'max' => 500, 'relative' => true],
        'support.help_center_visible' => ['label' => 'Show help center link', 'type' => 'bool', 'default' => true],
        'support.faq_url'             => ['label' => 'FAQ link', 'type' => 'url', 'max' => 500, 'relative' => true],
        'support.faq_visible'         => ['label' => 'Show FAQ link', 'type' => 'bool', 'default' => true],
        'support.contact_url'         => ['label' => 'Contact the GlowRez team link', 'type' => 'url', 'max' => 500, 'relative' => true],
        'support.contact_visible'     => ['label' => 'Show contact link', 'type' => 'bool', 'default' => true],
        'support.hours_from'          => ['label' => 'Opens at', 'type' => 'time'],
        'support.hours_to'            => ['label' => 'Closes at', 'type' => 'time'],
        'support.days'                => ['label' => 'Working days', 'type' => 'days'],
        'support.default_message_ar'  => ['label' => 'Default support message (Arabic)', 'type' => 'text', 'max' => 500],
        'support.default_message_en'  => ['label' => 'Default support message (English)', 'type' => 'text', 'max' => 500],
        // Team-only: never exposed by any public page or API.
        'support.internal_email'      => ['label' => 'Internal support email', 'type' => 'email'],
        'support.internal_phone'      => ['label' => 'Internal support phone', 'type' => 'phone'],
        'support.internal_notes'      => ['label' => 'Internal notes', 'type' => 'text', 'max' => 1000],

        // ── Tab 5 · Public appearance & footer ────────────────────────────
        'visibility.show_official_email' => ['label' => 'Show the official email', 'type' => 'bool', 'default' => true],
        'visibility.show_official_phone' => ['label' => 'Show the official phone', 'type' => 'bool', 'default' => true],
        'visibility.show_social'         => ['label' => 'Show social links', 'type' => 'bool', 'default' => true],
        'visibility.show_support_links'  => ['label' => 'Show support links', 'type' => 'bool', 'default' => true],
        'visibility.contact_link_enabled'=> ['label' => 'Enable the "Contact us" link', 'type' => 'bool', 'default' => true],
        'visibility.contact_cta_ar'      => ['label' => 'Contact button text (Arabic)', 'type' => 'string', 'max' => 40],
        'visibility.contact_cta_en'      => ['label' => 'Contact button text (English)', 'type' => 'string', 'max' => 40],
        'visibility.footer_tagline_ar'   => ['label' => 'Footer tagline (Arabic)', 'type' => 'text', 'max' => 240],
        'visibility.footer_tagline_en'   => ['label' => 'Footer tagline (English)', 'type' => 'text', 'max' => 240],
        'visibility.footer_legal_ar'     => ['label' => 'Intellectual property notice (Arabic)', 'type' => 'text', 'max' => 300],
        'visibility.footer_legal_en'     => ['label' => 'Intellectual property notice (English)', 'type' => 'text', 'max' => 300],
        'visibility.copyright_ar'        => ['label' => 'Copyright text (Arabic)', 'type' => 'string', 'max' => 200],
        'visibility.copyright_en'        => ['label' => 'Copyright text (English)', 'type' => 'string', 'max' => 200],
        'visibility.privacy_url'         => ['label' => 'Privacy policy link', 'type' => 'url', 'max' => 500, 'relative' => true],
        'visibility.terms_url'           => ['label' => 'Terms and conditions link', 'type' => 'url', 'max' => 500, 'relative' => true],

        // ── Tab 6 · System & maintenance ──────────────────────────────────
        'system.mode'               => ['label' => 'Site mode', 'type' => 'enum', 'options' => ['normal', 'maintenance', 'paused'], 'default' => 'normal'],
        'system.scope'              => ['label' => 'What the mode closes', 'type' => 'enum', 'options' => ['public', 'all'], 'default' => 'public'],
        'system.message_ar'         => ['label' => 'Maintenance message (Arabic)', 'type' => 'text', 'max' => 400],
        'system.message_en'         => ['label' => 'Maintenance message (English)', 'type' => 'text', 'max' => 400],
        'system.status_page_url'    => ['label' => 'Status page link', 'type' => 'url', 'max' => 500],
        'system.changelog_url'      => ['label' => 'Changelog link', 'type' => 'url', 'max' => 500],
        'system.update_notice'      => ['label' => 'New-update notice', 'type' => 'bool', 'default' => false],
        'system.show_version'       => ['label' => 'Show the version publicly', 'type' => 'bool', 'default' => false],

        // ── Tab 4 · Releases (what the dashboard RECORDS, not what is deployed) ──
        'release.web_version'      => ['label' => 'Website version', 'type' => 'version'],
        'release.web_min_version'  => ['label' => 'Minimum supported version', 'type' => 'version'],
        'release.web_updated_on'   => ['label' => 'Last updated', 'type' => 'date'],
        'release.web_notes_ar'     => ['label' => 'Release notes (Arabic)', 'type' => 'text', 'max' => 2000],
        'release.web_notes_en'     => ['label' => 'Release notes (English)', 'type' => 'text', 'max' => 2000],

        'release.android_status'     => ['label' => 'Android app status', 'type' => 'enum', 'options' => ['in_development', 'available', 'unavailable'], 'default' => 'in_development'],
        'release.android_version'    => ['label' => 'Android version', 'type' => 'version'],
        'release.android_build'      => ['label' => 'Android build number', 'type' => 'int', 'min' => 1, 'max' => 2147483647],
        'release.android_min_version'=> ['label' => 'Android minimum supported version', 'type' => 'version'],
        'release.android_play_url'   => $store('Google Play link', ['play.google.com']),
        'release.android_apk_url'    => ['label' => 'Direct APK link', 'type' => 'url', 'max' => 500, 'https' => true],
        'release.android_updated_on' => ['label' => 'Android last updated', 'type' => 'date'],
        'release.android_notes_ar'   => ['label' => 'Android release notes (Arabic)', 'type' => 'text', 'max' => 2000],
        'release.android_notes_en'   => ['label' => 'Android release notes (English)', 'type' => 'text', 'max' => 2000],

        'release.ios_status'     => ['label' => 'iOS app status', 'type' => 'enum', 'options' => ['in_development', 'available', 'unavailable'], 'default' => 'in_development'],
        'release.ios_version'    => ['label' => 'iOS version', 'type' => 'version'],
        'release.ios_build'      => ['label' => 'iOS build number', 'type' => 'int', 'min' => 1, 'max' => 2147483647],
        'release.ios_min_version'=> ['label' => 'iOS minimum supported version', 'type' => 'version'],
        'release.ios_store_url'  => $store('App Store link', ['apps.apple.com', 'itunes.apple.com']),
        'release.ios_updated_on' => ['label' => 'iOS last updated', 'type' => 'date'],
        'release.ios_notes_ar'   => ['label' => 'iOS release notes (Arabic)', 'type' => 'text', 'max' => 2000],
        'release.ios_notes_en'   => ['label' => 'iOS release notes (English)', 'type' => 'text', 'max' => 2000],
    ],

    // Tab → groups it owns (the controller only ever writes these).
    'tabs' => [
        'general'    => ['general'],
        'support'    => ['support'],
        'releases'   => ['release'],
        'visibility' => ['visibility'],
        'system'     => ['system'],
    ],

    // Settings only people with `platform.maintenance.manage` may change.
    'maintenance_keys' => ['system.mode', 'system.scope', 'system.message_ar', 'system.message_en'],

    // Icons a custom link may use (rendered by App\Support\Platform\PlatformIcons).
    'custom_icons' => [
        'globe', 'link', 'mail', 'phone', 'message-circle', 'help-circle', 'send', 'rss',
        'book-open', 'video', 'camera', 'map-pin', 'heart', 'star', 'shopping-bag', 'file-text',
    ],
];
