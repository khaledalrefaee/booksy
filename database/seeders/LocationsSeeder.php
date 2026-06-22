<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Governorate;
use App\Models\Area;
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if already seeded
        if (Country::count() > 0) {
            return;
        }

        $syria = Country::create([
            'name_en'    => 'Syria',
            'name_ar'    => 'سوريا',
            'code'       => 'SY',
            'dial_code'  => '+963',
            'sort_order' => 1,
        ]);

        $governorates = [
            ['name_en' => 'Damascus',       'name_ar' => 'دمشق',          'sort_order' => 1,  'areas' => [
                ['en' => 'Old City',             'ar' => 'المدينة القديمة'],
                ['en' => 'Mezzeh',               'ar' => 'المزة'],
                ['en' => 'Kafr Sousa',           'ar' => 'كفر سوسة'],
                ['en' => 'Malki',                'ar' => 'المالكي'],
                ['en' => 'Abu Rummaneh',         'ar' => 'أبو رمانة'],
                ['en' => 'Arnous',               'ar' => 'أرنوس'],
                ['en' => 'Bab Touma',            'ar' => 'باب توما'],
                ['en' => 'Bab Sharqi',           'ar' => 'باب شرقي'],
                ['en' => 'Yarmouk',              'ar' => 'اليرموك'],
                ['en' => 'Qassaa',               'ar' => 'القصاع'],
                ['en' => 'Salihiyeh',            'ar' => 'الصالحية'],
                ['en' => 'Muhajireen',           'ar' => 'المهاجرين'],
                ['en' => 'Rawda',                'ar' => 'الروضة'],
                ['en' => 'Mazraa',               'ar' => 'المزرعة'],
                ['en' => 'Shaghour',             'ar' => 'الشاغور'],
                ['en' => 'Qanawat',              'ar' => 'قنوات'],
                ['en' => 'Tadamun',              'ar' => 'التضامن'],
                ['en' => 'Nahr Aisha',           'ar' => 'نهر عيشة'],
                ['en' => 'Barzeh',               'ar' => 'برزة'],
                ['en' => 'Jobar',                'ar' => 'جوبر'],
                ['en' => 'Qaboun',               'ar' => 'القابون'],
                ['en' => 'Dweila',               'ar' => 'الدويلعة'],
                ['en' => 'Rukn al-Din',          'ar' => 'ركن الدين'],
            ]],
            ['name_en' => 'Rural Damascus',  'name_ar' => 'ريف دمشق',     'sort_order' => 2,  'areas' => [
                ['en' => 'Douma',                'ar' => 'دوما'],
                ['en' => 'Harasta',              'ar' => 'حرستا'],
                ['en' => 'Darayya',              'ar' => 'داريا'],
                ['en' => 'Jaramana',             'ar' => 'جرمانا'],
                ['en' => 'Qudsaya',              'ar' => 'قدسيا'],
                ['en' => 'Al-Tall',              'ar' => 'التل'],
                ['en' => 'Sednaya',              'ar' => 'صيدنايا'],
                ['en' => 'Zabadani',             'ar' => 'الزبداني'],
                ['en' => 'Yalda',                'ar' => 'يلدا'],
                ['en' => 'Babbila',              'ar' => 'ببيلا'],
                ['en' => 'Sahnaya',              'ar' => 'سحنايا'],
                ['en' => 'Ghouta',               'ar' => 'الغوطة'],
                ['en' => 'Kafr Batna',           'ar' => 'كفر بطنا'],
                ['en' => 'Saqba',                'ar' => 'سقبا'],
                ['en' => 'Irbin',                'ar' => 'عربين'],
                ['en' => 'Aqraba',               'ar' => 'عقربا'],
            ]],
            ['name_en' => 'Aleppo',          'name_ar' => 'حلب',           'sort_order' => 3,  'areas' => [
                ['en' => 'Old Aleppo',           'ar' => 'حلب القديمة'],
                ['en' => 'Aziziyeh',             'ar' => 'العزيزية'],
                ['en' => 'Sulaymaniyeh',         'ar' => 'السليمانية'],
                ['en' => 'Hamadaniyeh',          'ar' => 'الحمدانية'],
                ['en' => 'Sabaa Bahrat',         'ar' => 'سبع بحرات'],
                ['en' => 'Sheikh Maqsoud',       'ar' => 'الشيخ مقصود'],
                ['en' => 'Mashhad',              'ar' => 'المشهد'],
                ['en' => 'Salhin',               'ar' => 'الصاخور'],
                ['en' => 'Bab al-Hadid',         'ar' => 'باب الحديد'],
                ['en' => 'Bab al-Nayrab',        'ar' => 'باب النيرب'],
                ['en' => 'Syriac',               'ar' => 'السريان'],
                ['en' => 'Khalidiyeh',           'ar' => 'الخالدية'],
                ['en' => 'Midan',                'ar' => 'الميدان'],
                ['en' => 'Jamiyat al-Zahra',     'ar' => 'جمعية الزهراء'],
                ['en' => 'Al-Furqan',            'ar' => 'الفرقان'],
                ['en' => 'Hamdaniyeh',           'ar' => 'الحمدانية'],
                ['en' => 'Al Nile St',           'ar' => 'شارع النيل'],
                ['en' => 'Bustan al-Qasr',       'ar' => 'بستان القصر'],
            ]],
            ['name_en' => 'Homs',            'name_ar' => 'حمص',           'sort_order' => 4,  'areas' => [
                ['en' => 'Old Homs',             'ar' => 'حمص القديمة'],
                ['en' => 'Waer',                 'ar' => 'الوعر'],
                ['en' => 'Karm al-Zaytoun',      'ar' => 'كرم الزيتون'],
                ['en' => 'Bab al-Sibaa',         'ar' => 'باب السباع'],
                ['en' => 'Zahraa',               'ar' => 'الزهراء'],
                ['en' => 'Ghouta',               'ar' => 'الغوطة'],
                ['en' => 'Tadmur (Palmyra)',      'ar' => 'تدمر'],
                ['en' => 'Rastan',               'ar' => 'الرستن'],
                ['en' => 'Talbiseh',             'ar' => 'تلبيسة'],
                ['en' => 'Al-Qusayr',            'ar' => 'القصير'],
                ['en' => 'Mukharram',            'ar' => 'المخرم'],
            ]],
            ['name_en' => 'Hama',            'name_ar' => 'حماة',          'sort_order' => 5,  'areas' => [
                ['en' => 'Hama City',            'ar' => 'مدينة حماة'],
                ['en' => 'Masyaf',               'ar' => 'مصياف'],
                ['en' => 'Suqaylabiyah',         'ar' => 'السقيلبية'],
                ['en' => 'Salamiyah',            'ar' => 'سلمية'],
                ['en' => 'Muhardah',             'ar' => 'محردة'],
                ['en' => 'Kafr Nabudah',         'ar' => 'كفر نبودة'],
                ['en' => 'Shayzar',              'ar' => 'شيزر'],
            ]],
            ['name_en' => 'Latakia',         'name_ar' => 'اللاذقية',     'sort_order' => 6,  'areas' => [
                ['en' => 'Latakia City',         'ar' => 'مدينة اللاذقية'],
                ['en' => 'Jableh',               'ar' => 'جبلة'],
                ['en' => 'Al-Haffa',             'ar' => 'الحفة'],
                ['en' => 'Qardaha',              'ar' => 'القرداحة'],
                ['en' => 'Kasab',                'ar' => 'كسب'],
                ['en' => 'Slunfeh',              'ar' => 'صلنفة'],
                ['en' => 'Al-Qusaybeh',          'ar' => 'القصيبة'],
            ]],
            ['name_en' => 'Tartus',          'name_ar' => 'طرطوس',        'sort_order' => 7,  'areas' => [
                ['en' => 'Tartus City',          'ar' => 'مدينة طرطوس'],
                ['en' => 'Baniyas',              'ar' => 'بانياس'],
                ['en' => 'Safita',               'ar' => 'صافيتا'],
                ['en' => 'Dreikish',             'ar' => 'دريكيش'],
                ['en' => 'Al-Shaykh Badr',       'ar' => 'الشيخ بدر'],
                ['en' => 'Arwad Island',         'ar' => 'جزيرة أرواد'],
            ]],
            ['name_en' => 'Idlib',           'name_ar' => 'إدلب',         'sort_order' => 8,  'areas' => [
                ['en' => 'Idlib City',           'ar' => 'مدينة إدلب'],
                ['en' => 'Jisr al-Shughur',      'ar' => 'جسر الشغور'],
                ['en' => 'Maarat al-Numan',      'ar' => 'معرة النعمان'],
                ['en' => 'Saraqib',              'ar' => 'سراقب'],
                ['en' => 'Ariha',                'ar' => 'أريحا'],
                ['en' => 'Harim',                'ar' => 'حارم'],
                ['en' => 'Salqin',               'ar' => 'سلقين'],
            ]],
            ['name_en' => 'Daraa',           'name_ar' => 'درعا',          'sort_order' => 9,  'areas' => [
                ['en' => 'Daraa City',           'ar' => 'مدينة درعا'],
                ['en' => 'Al-Sanamayn',          'ar' => 'الصنمين'],
                ['en' => 'Nawa',                 'ar' => 'نوى'],
                ['en' => 'Izra',                 'ar' => 'إزرع'],
                ['en' => 'Sheikh Meskeen',       'ar' => 'الشيخ مسكين'],
                ['en' => 'Tafas',                'ar' => 'طفس'],
                ['en' => 'Bosra',                'ar' => 'بصرى الشام'],
            ]],
            ['name_en' => 'As-Suwayda',      'name_ar' => 'السويداء',     'sort_order' => 10, 'areas' => [
                ['en' => 'As-Suwayda City',      'ar' => 'مدينة السويداء'],
                ['en' => 'Shahba',               'ar' => 'شهبا'],
                ['en' => 'Salkhad',              'ar' => 'صلخد'],
                ['en' => 'Qanawat',              'ar' => 'قنوات'],
            ]],
            ['name_en' => 'Quneitra',        'name_ar' => 'القنيطرة',     'sort_order' => 11, 'areas' => [
                ['en' => 'Quneitra City',        'ar' => 'مدينة القنيطرة'],
                ['en' => 'Fiq',                  'ar' => 'فيق'],
                ['en' => 'Khan Arnabeh',         'ar' => 'خان أرنبة'],
            ]],
            ['name_en' => 'Deir ez-Zor',     'name_ar' => 'دير الزور',   'sort_order' => 12, 'areas' => [
                ['en' => 'Deir ez-Zor City',     'ar' => 'مدينة دير الزور'],
                ['en' => 'Al-Mayadin',           'ar' => 'الميادين'],
                ['en' => 'Al-Bukamal',           'ar' => 'البوكمال'],
                ['en' => 'Al-Quriyah',           'ar' => 'القورية'],
            ]],
            ['name_en' => 'Al-Hasakah',      'name_ar' => 'الحسكة',       'sort_order' => 13, 'areas' => [
                ['en' => 'Al-Hasakah City',      'ar' => 'مدينة الحسكة'],
                ['en' => 'Qamishli',             'ar' => 'القامشلي'],
                ['en' => 'Ras al-Ayn',           'ar' => 'رأس العين'],
                ['en' => 'Al-Malikiyah',         'ar' => 'المالكية'],
                ['en' => 'Al-Shaddadi',          'ar' => 'الشدادي'],
            ]],
            ['name_en' => 'Raqqa',           'name_ar' => 'الرقة',        'sort_order' => 14, 'areas' => [
                ['en' => 'Raqqa City',           'ar' => 'مدينة الرقة'],
                ['en' => 'Tabqa',                'ar' => 'الطبقة'],
                ['en' => 'Tal Abyad',            'ar' => 'تل أبيض'],
                ['en' => 'Suluk',                'ar' => 'سلوك'],
            ]],
        ];

        foreach ($governorates as $i => $gov) {
            $areas = $gov['areas'];
            unset($gov['areas']);

            $governorate = $syria->governorates()->create($gov);

            foreach ($areas as $j => $area) {
                $governorate->areas()->create([
                    'name_en'    => $area['en'],
                    'name_ar'    => $area['ar'],
                    'sort_order' => $j + 1,
                ]);
            }
        }
    }
}
