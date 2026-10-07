<?php

/*
 |--------------------------------------------------------------------------
 | Venue-type groups
 |--------------------------------------------------------------------------
 | The 12 categories are too many to scan at a glance, so the storefront
 | folds them into four plain-language groups (salons / spa / beauty / clinics).
 | Used by the homepage category picker and the featured-venues filter, so a
 | group means the same thing — and carries the same colour — everywhere.
 | A category whose slug is not listed here lands in an automatic "other" group.
 */
return [
    'groups' => [
        'salons' => [
            'ar' => 'صالونات', 'en' => 'Salons',
            'color' => '#8A6317', 'slugs' => ['salon', 'barbershop'], 'photo' => 'magnific/hair.jpg',
        ],
        'spa' => [
            'ar' => 'سبا ومساج', 'en' => 'Spa & massage',
            'color' => '#1F6B66', 'slugs' => ['spa', 'massage-center', 'wellness-center'], 'photo' => 'magnific/facial.jpg',
        ],
        'beauty' => [
            'ar' => 'تجميل وأظافر', 'en' => 'Beauty & nails',
            'color' => '#9C3A68', 'slugs' => ['beauty-center', 'nail-studio', 'eyelash-brows'], 'photo' => 'magnific/makeup.jpg',
        ],
        'clinics' => [
            'ar' => 'عيادات ومراكز طبية', 'en' => 'Clinics',
            'color' => '#34559A', 'slugs' => ['aesthetic-clinic', 'dermatology-clinic', 'dental-clinic', 'medical-center'],
        ],
    ],
    'other' => ['ar' => 'أخرى', 'en' => 'Other', 'color' => '#4B5D34'],
];
