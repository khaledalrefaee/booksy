<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default currency
    |--------------------------------------------------------------------------
    */
    'default_currency' => 'SYP',

    /*
    |--------------------------------------------------------------------------
    | Supported currencies
    | code => ['name_en', 'name_ar', 'symbol']
    |--------------------------------------------------------------------------
    */
    'currencies' => [
        'SYP' => ['name_en' => 'Syrian Pound',     'name_ar' => 'ليرة سورية',    'symbol' => 'ل.س'],
        'USD' => ['name_en' => 'US Dollar',         'name_ar' => 'دولار أمريكي',  'symbol' => '$'],
        'EUR' => ['name_en' => 'Euro',              'name_ar' => 'يورو',           'symbol' => '€'],
        'SAR' => ['name_en' => 'Saudi Riyal',       'name_ar' => 'ريال سعودي',    'symbol' => 'ر.س'],
        'AED' => ['name_en' => 'UAE Dirham',        'name_ar' => 'درهم إماراتي',  'symbol' => 'د.إ'],
        'TRY' => ['name_en' => 'Turkish Lira',      'name_ar' => 'ليرة تركية',    'symbol' => '₺'],
        'JOD' => ['name_en' => 'Jordanian Dinar',   'name_ar' => 'دينار أردني',   'symbol' => 'د.أ'],
        'LBP' => ['name_en' => 'Lebanese Pound',    'name_ar' => 'ليرة لبنانية',  'symbol' => 'ل.ل'],
        'EGP' => ['name_en' => 'Egyptian Pound',    'name_ar' => 'جنيه مصري',     'symbol' => 'ج.م'],
        'IQD' => ['name_en' => 'Iraqi Dinar',       'name_ar' => 'دينار عراقي',   'symbol' => 'د.ع'],
        'KWD' => ['name_en' => 'Kuwaiti Dinar',     'name_ar' => 'دينار كويتي',   'symbol' => 'د.ك'],
        'QAR' => ['name_en' => 'Qatari Riyal',      'name_ar' => 'ريال قطري',     'symbol' => 'ر.ق'],
        'GBP' => ['name_en' => 'British Pound',     'name_ar' => 'جنيه إسترليني', 'symbol' => '£'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default dial code (country phone prefix)
    |--------------------------------------------------------------------------
    */
    'default_dial_code' => '+963',

    /*
    |--------------------------------------------------------------------------
    | Supported dial codes
    | dial_code => ['flag', 'name_en', 'name_ar']
    |--------------------------------------------------------------------------
    */
    'dial_codes' => [
        '+963' => ['flag' => '🇸🇾', 'flag_img' => '/images/flags/sy.svg', 'name_en' => 'Syria',        'name_ar' => 'سوريا',      'digits_min' => 9,  'digits_max' => 9],
        '+966' => ['flag' => '🇸🇦',                                        'name_en' => 'Saudi Arabia', 'name_ar' => 'السعودية',   'digits_min' => 9,  'digits_max' => 9],
        '+971' => ['flag' => '🇦🇪',                                        'name_en' => 'UAE',          'name_ar' => 'الإمارات',   'digits_min' => 9,  'digits_max' => 9],
        '+962' => ['flag' => '🇯🇴',                                        'name_en' => 'Jordan',       'name_ar' => 'الأردن',     'digits_min' => 9,  'digits_max' => 9],
        '+961' => ['flag' => '🇱🇧',                                        'name_en' => 'Lebanon',      'name_ar' => 'لبنان',      'digits_min' => 7,  'digits_max' => 8],
        '+964' => ['flag' => '🇮🇶',                                        'name_en' => 'Iraq',         'name_ar' => 'العراق',     'digits_min' => 10, 'digits_max' => 10],
        '+965' => ['flag' => '🇰🇼',                                        'name_en' => 'Kuwait',       'name_ar' => 'الكويت',     'digits_min' => 8,  'digits_max' => 8],
        '+974' => ['flag' => '🇶🇦',                                        'name_en' => 'Qatar',        'name_ar' => 'قطر',        'digits_min' => 8,  'digits_max' => 8],
        '+973' => ['flag' => '🇧🇭',                                        'name_en' => 'Bahrain',      'name_ar' => 'البحرين',    'digits_min' => 8,  'digits_max' => 8],
        '+968' => ['flag' => '🇴🇲',                                        'name_en' => 'Oman',         'name_ar' => 'عمان',       'digits_min' => 8,  'digits_max' => 8],
        '+20'  => ['flag' => '🇪🇬',                                        'name_en' => 'Egypt',        'name_ar' => 'مصر',        'digits_min' => 10, 'digits_max' => 10],
        '+90'  => ['flag' => '🇹🇷',                                        'name_en' => 'Turkey',       'name_ar' => 'تركيا',      'digits_min' => 10, 'digits_max' => 10],
        '+49'  => ['flag' => '🇩🇪',                                        'name_en' => 'Germany',      'name_ar' => 'ألمانيا',    'digits_min' => 6,  'digits_max' => 11],
        '+44'  => ['flag' => '🇬🇧',                                        'name_en' => 'UK',           'name_ar' => 'بريطانيا',   'digits_min' => 10, 'digits_max' => 10],
        '+1'   => ['flag' => '🇺🇸',                                        'name_en' => 'USA',          'name_ar' => 'أمريكا',     'digits_min' => 10, 'digits_max' => 10],
    ],

];
