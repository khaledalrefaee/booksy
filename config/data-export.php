<?php

/*
|--------------------------------------------------------------------------
| Company data export manifest
|--------------------------------------------------------------------------
|
| تعريف واحد لكل كيان تُصدَّر بياناته عند تنزيل الشركة لبياناتها.
| إضافة ميزة جديدة مستقبلاً = سطر واحد هنا فقط، والمحرّك يتكفّل بالباقي
| (شيت في الإكسل + قسم في JSON + تطبيق فلتر التفعيل + اختيار الأعمدة).
|
| ملاحظة: هذا الملف يجب أن يبقى بيانات صِرفة (بدون closures) حتى يعمل
| `php artisan config:cache` بدون كسر.
|
*/

return [

    /*
    | أعمدة لا تُصدَّر أبداً من أي جدول (حساسة أو داخلية).
    */
    'blacklist' => [
        'password',
        'remember_token',
        'api_token',
        'idempotency_key',
        'booking_idempotency_key',
        'must_change_password',
    ],

    /*
    | استراتيجيات ربط الكيان بالشركة:
    |   'company'          → where company_id = :id
    |   'through_branches' → whereIn branch_id, [فروع الشركة]
    */
    /*
    | ترتيب المصفوفة = ترتيب الاستيراد (الآباء قبل الأبناء). المفاتيح الإضافية
    | يستخدمها المستورِد فقط (التصدير يتجاهلها):
    |   remap        : ['col' => 'entityKey'|'@company'] استبدال الـ FK بالمعرّف الجديد.
    |                  إن لم يوجد المعرّف القديم بالخريطة → تُفرَّغ الخانة (أو يُتخطّى الصف
    |                  إن كان العمود إلزامياً).
    |   nullify      : أعمدة تُفرَّغ عند الاستيراد (FK لكيانات غير مُصدَّرة/خاصة بالشركة).
    |   unique_suffix: أعمدة عامة فريدة (unique) — تُلحَق لاحقة عند التعارض بدل الفشل.
    */
    'entities' => [

        'branches' => [
            'label'         => 'الفروع',
            'model'         => \App\Models\Branch::class,
            'scope'         => 'company',
            'feature'       => null,            // أساسي — يُصدَّر دائماً
            'remap'         => ['company_id' => '@company'],
            'nullify'       => ['qr_code'],
            'unique_suffix' => ['slug'],         // slug فريد عالمياً → لاحقة عند التعارض
        ],

        'service_categories' => [
            'label'         => 'أقسام الخدمات',
            'model'         => \App\Models\ServiceCategory::class,
            'scope'         => 'company',
            'feature'       => null,
            'remap'         => ['company_id' => '@company'],
            'unique_suffix' => ['slug'],         // slug فريد عالمياً
        ],

        'employees' => [
            'label'   => 'الموظفين',
            'model'   => \App\Models\Employee::class,
            'scope'   => 'company',
            'feature' => null,
            // role_id يبقى كما هو: الأدوار عامّة مشتركة بين كل الشركات.
            'remap'   => ['company_id' => '@company', 'branch_id' => 'branches'],
        ],

        'services' => [
            'label'   => 'الخدمات',
            'model'   => \App\Models\Service::class,
            'scope'   => 'through_branches',
            'feature' => null,
            'remap'   => ['branch_id' => 'branches', 'service_category_id' => 'service_categories'],
        ],

        'products' => [
            'label'   => 'المنتجات / المستودع',
            'model'   => \App\Models\Product::class,
            'scope'   => 'company',
            'feature' => 'inventory',
            'remap'   => ['company_id' => '@company', 'branch_id' => 'branches'],
            'nullify' => ['product_category_id'], // أقسام المنتجات غير مُصدَّرة
        ],

        'appointments' => [
            'label'   => 'المواعيد',
            'model'   => \App\Models\Appointment::class,
            'scope'   => 'company',
            'feature' => null,
            'remap'   => [
                'company_id'             => '@company',
                'branch_id'              => 'branches',
                'service_id'             => 'services',
                'employee_id'            => 'employees',
                'handled_by_employee_id' => 'employees',
            ],
            // customer/resource غير مُصدَّرين؛ الاسم/الهاتف محفوظان denormalized.
            'nullify' => ['customer_id', 'resource_id', 'status_changed_by_id'],
        ],

        'waitlist' => [
            'label'   => 'قائمة الانتظار',
            'model'   => \App\Models\WaitlistEntry::class,
            'scope'   => 'company',
            'feature' => 'waitlist',
            'remap'   => ['company_id' => '@company', 'branch_id' => 'branches'],
            'nullify' => ['customer_id'],
        ],

        'invoices' => [
            'label'         => 'الفواتير',
            'model'         => \App\Models\Invoice::class,
            'scope'         => 'company',
            'feature'       => 'finance',
            'remap'         => ['company_id' => '@company', 'branch_id' => 'branches', 'appointment_id' => 'appointments'],
            'nullify'       => ['created_by_id'],
            'unique_suffix' => ['invoice_number'], // فريد عالمياً → لاحقة عند التعارض
        ],

        'reviews' => [
            'label'   => 'التقييمات',
            'model'   => \App\Models\Review::class,
            'scope'   => 'through_branches',
            'feature' => null,
            'remap'   => ['branch_id' => 'branches', 'appointment_id' => 'appointments'],
            'nullify' => ['customer_id', 'reviewable_id'],
        ],
    ],
];
