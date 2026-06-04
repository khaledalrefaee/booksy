# Booksy

تطبيق إدارة الحجوزات والصالونات مبني على Laravel مع لوحة مالك أصيلة.

## نظرة عامة

هذا المشروع يقدّم:
- إدارة الفروع والخدمات
- عرض المواعيد والتفاصيل
- لوحة تحكم مالك بسيطة
- بيانات تجريبية جاهزة للتشغيل

## تركيب المشروع

1. تثبيت الاعتماديات:
   ```powershell
   composer install
   npm install
   ```
2. نسخ ملف البيئة وإنشاء مفتاح التطبيق:
   ```powershell
   copy .env.example .env
   php artisan key:generate
   ```
3. إنشاء قاعدة البيانات وتشغيل الهجرات والبيانات التجريبية:
   ```powershell
   php artisan migrate --seed
   ```
4. تشغيل بيئة التطوير:
   ```powershell
   npm run dev
   ```

## بيانات تجريبية

يتم إنشاء بيانات عينة تلقائياً بواسطة `DemoOwnerSeeder`، بما في ذلك:
- البريد الإلكتروني: `owner@booksy.demo`
- كلمة المرور: `password`

## بنية المشروع

- `app/Http/Controllers/Owner` - متحكمات لوحة المالك
- `app/Services/Owner` - منطق سياق المالك وإحصاءات اللوحة
- `bootstrap/app.php` - إعداد Laravel الجديد بدون RouteServiceProvider
- `routes/web.php` - مسارات واجهة المستخدم
- `database/seeders/DemoOwnerSeeder.php` - بيانات تجريبية للمالك
- `resources/js` و `resources/css` - واجهة المستخدم الأمامية مع Vite

## تحسينات تم تنفيذها

- تحديث بيانات المشروع في `composer.json`
- نقل مكتبة `axios` إلى الاعتماديات الفعلية في `package.json`
- إضافة استدعاء `DemoOwnerSeeder` إلى `DatabaseSeeder`
- تنظيف README ليصبح موجزاً وواضحاً

## أوامر مفيدة

- `composer validate --no-check-publish`
- `php artisan route:list`
- `npm run build`

## الترخيص

مرخّص بموجب رخصة MIT.
