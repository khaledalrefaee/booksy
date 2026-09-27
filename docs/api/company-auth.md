# Company Auth API — دليل الـ Frontend

نفس أسلوب **Customer API** تماماً: نفس الـ envelope، نفس الـ Bearer token، نفس جدول الأكواد (`otp_codes`).

- **Base:** `{{base_url}}/api/company`
- **Headers:** `Accept: application/json` + `Accept-Language: ar|en` (أو `?lang=ar`)
- **Auth:** `Authorization: Bearer <token>` (للـ endpoints المحمية فقط)
- **Postman:** `docs/postman/GlowRez.postman_collection.json` (folder: Company → Auth)

## شكل الرد (ثابت لكل الـ endpoints)

```json
{ "status": true, "message": "…", "data": { } }
```

خطأ عام:

```json
{ "status": false, "message": "…", "data": null }
```

خطأ تحقق (422) — تُضاف `errors`:

```json
{
  "status": false,
  "message": "The email has already been taken.",
  "data": null,
  "errors": { "email": ["The email has already been taken."], "terms": ["…"] }
}
```

| HTTP | المعنى |
|---|---|
| 200 / 201 | نجاح |
| 401 | token مفقود/غير صالح → اذهب لشاشة الدخول |
| 403 | حساب موقوف، أو غير مؤكَّد (Login) |
| 404 | غير موجود |
| 409 | الحساب مؤكَّد مسبقاً (Verify/Resend) |
| 422 | بيانات غير صالحة أو كود خاطئ/منتهي |
| 429 | انتظر (cooldown) أو محاولات كثيرة |

## قواعد الأكواد (OTP)

| القاعدة | القيمة |
|---|---|
| طول الكود | 4 أرقام |
| صلاحية كود التسجيل | 4 دقائق (`expires_in: 240`) |
| صلاحية كود استعادة كلمة المرور | 10 دقائق (`expires_in: 600`) |
| الانتظار بين كودين | 60 ثانية (`resend_after: 60`) |
| الحد الأقصى | 4 أكواد تسجيل / 3 أكواد استعادة لكل 10 دقائق |
| المحاولات الخاطئة | 5 → 429 وتُلغى كل الأكواد الحالية، اطلب كوداً جديداً |
| `reset_token` | 15 دقيقة، **استخدام واحد فقط** |

**التسجيل = كود واحد:** يُرسَل **نفس الكود** إلى الإيميل وإلى الهاتف، والمستخدم يُدخله في **حقل واحد**. نجاحه يؤكّد الإيميل والهاتف معاً.

**method (استعادة كلمة المرور):** `email` أو `sms`. `sms` تعني الهاتف: الرقم السوري (963) يصله SMS، وأي بلد آخر يصله واتساب. الحقل `phone_channel` في الرد يوضّح الناقل الفعلي (`sms` | `whatsapp`) لعرض النص الصحيح ("أرسلنا الكود إلى إيميلك وعبر واتساب…").

> `dev_code` يُرجَع فقط في بيئة التطوير (`APP_ENV=local`) — في الإنتاج دائماً `null`. لا تعتمد عليه في التطبيق.

---

## 0) Categories — فئات الأعمال

`GET /api/company/categories` — **عامة** (بدون token). تُستخدم لتعبئة قائمة "نوع النشاط" في شاشة التسجيل، ثم يُرسَل `id` المختار كـ `category_id` في Register.

**200**

```json
{
  "status": true,
  "message": "Done.",
  "data": {
    "categories": [
      {
        "id": 1,
        "slug": "salon",
        "name": "صالون",
        "name_en": "Salon",
        "name_ar": "صالون",
        "icon": "scissors",
        "icon_url": null,
        "image": null
      },
      {
        "id": 2,
        "slug": "barbershop",
        "name": "صالون حلاقة",
        "name_en": "Barbershop",
        "name_ar": "صالون حلاقة",
        "icon": "scissors",
        "icon_url": null,
        "image": null
      }
    ]
  }
}
```

| الحقل | المعنى |
|---|---|
| `id` | **يُرسَل كـ `category_id` في Register** |
| `name` | الاسم حسب لغة الطلب (`Accept-Language`) |
| `name_en` / `name_ar` | الاسمان |
| `icon` | اسم أيقونة [Feather](https://feathericons.com) مثل `scissors`، أو `null` إذا كانت الأيقونة صورة مرفوعة |
| `icon_url` | رابط الأيقونة إذا كانت صورة مرفوعة، وإلا `null` |
| `image` | رابط صورة الفئة أو `null` |

الترتيب نفس ترتيب صفحة التسجيل في الويب (`sort_order`).

**Frontend:** اطلبها عند فتح شاشة التسجيل، اعرض `name` (مع `icon` / `icon_url`)، واحفظ `id` المختار لإرساله كـ `category_id`.

## 1) Register

`POST /api/company/register` — نفس حقول تسجيل الويب.

| الحقل | مطلوب | ملاحظات |
|---|---|---|
| `name_en` | ✔ | ≤255 |
| `name_ar` | — | ≤255 |
| `owner_name` | ✔ | ≤255 |
| `email` | ✔ | **فريد** |
| `phone` | ✔ | **فريد** — E.164 مع `+` مثل `+963991234567` |
| `category_id` | ✔ | `id` من `GET /api/company/categories` |
| `password` | ✔ | 8 أحرف على الأقل |
| `password_confirmation` | ✔ | مطابق لـ `password` |
| `terms` | ✔ | `1` / `true` / `yes` |

```json
{
  "name_en": "Glow Studio",
  "name_ar": "استوديو جلو",
  "owner_name": "Khaled",
  "email": "owner@glow.test",
  "phone": "+963991234567",
  "category_id": 1,
  "password": "secret123",
  "password_confirmation": "secret123",
  "terms": true
}
```

**201**

```json
{
  "status": true,
  "message": "Verification code sent to your email and phone.",
  "data": {
    "company_id": 123,
    "email_verified": false,
    "phone_verified": false,
    "email": "ow***@glow.test",
    "phone": "*********4567",
    "phone_channel": "sms",
    "expires_in": 240,
    "resend_after": 60,
    "dev_code": "8615"
  }
}
```

(`dev_code` يظهر في local فقط؛ في الإنتاج `null`.)

**422** إيميل أو رقم مسجّل مسبقاً:

```json
{
  "status": false,
  "message": "This phone number is already registered.",
  "data": null,
  "errors": { "phone": ["This phone number is already registered."] }
}
```

الحساب يُنشأ **غير مؤكَّد** (`email_verified: false`, `phone_verified: false`) — **لا يوجد token** قبل التحقق.

**Frontend:**
1. المستخدم يرسل النموذج.
2. خزّن `company_id` واعرض شاشة OTP بحقل واحد: "أرسلنا كوداً إلى `email` وعبر `phone_channel` إلى `phone`".
3. شغّل عدّاد `resend_after` قبل تفعيل زر "إعادة الإرسال".
4. عند 422 اعرض `errors` تحت الحقول.

## 2) Verify Registration OTP

`POST /api/company/verify`

```json
{ "company_id": 123, "code": "8615" }
```

**200** — `email_verified = true` و `phone_verified = true` والحساب verified، ويصدر `token`.

```json
{
  "status": true,
  "message": "Account verified successfully.",
  "data": {
    "token": "yIo0Ei64…(64 chars)",
    "token_type": "Bearer",
    "company": {
      "id": 123,
      "name": "Glow Studio",
      "name_en": "Glow Studio",
      "name_ar": "استوديو جلو",
      "owner_name": "Khaled",
      "email": "owner@glow.test",
      "phone": "+963991234567",
      "category": { "id": 1, "name": "Salon" },
      "logo": null,
      "status": "pending",
      "verified": true,
      "email_verified": true,
      "phone_verified": true,
      "email_verified_at": "2026-09-26T22:10:00+03:00",
      "phone_verified_at": "2026-09-26T22:10:00+03:00",
      "submitted_for_review_at": null,
      "created_at": "2026-09-26T22:09:12+03:00"
    }
  }
}
```

أخطاء: `422` كود خاطئ/منتهي · `429` خمس محاولات خاطئة (تُلغى الأكواد، اطلب كوداً جديداً) · `409` مؤكَّد مسبقاً.

**Frontend:** خزّن `token` بأمان (Keychain/Keystore) ثم انتقل للوحة الشركة.
`status: "pending"` = الحساب مؤكَّد لكنه لم يُنشر بعد (النشر بموافقة الإدارة فقط) — التحقق يظهر في `verified` / `email_verified` / `phone_verified`.

## 3) Resend Registration OTP

`POST /api/company/resend`

```json
{ "company_id": 123 }
```

يرسل كوداً جديداً (نفسه) إلى الإيميل والهاتف معاً. **200** بنفس `data` الخاص بـ Register.

**429** (cooldown):

```json
{
  "status": false,
  "message": "Please wait 42 seconds before requesting a new code.",
  "data": { "retry_after": 42 }
}
```

**Frontend:** عطّل الزر لمدة `retry_after` ثانية.

## 4) Login

`POST /api/company/login` — **email + password** (نفس الويب؛ لا يوجد دخول بالهاتف).

```json
{ "email": "owner@glow.test", "password": "secret123" }
```

**200** — نفس `data` الخاص بـ Verify (`token`, `token_type`, `company`)، والرسالة `"Signed in successfully."`.

**422** بيانات خاطئة:

```json
{
  "status": false,
  "message": "These credentials do not match our records.",
  "data": null,
  "errors": { "email": ["These credentials do not match our records."] }
}
```

**403** الحساب لم يُؤكَّد بعد:

```json
{
  "status": false,
  "message": "Please verify your account to continue.",
  "data": {
    "verification_required": true,
    "company_id": 123,
    "email": "ow***@glow.test",
    "phone": "*********4567",
    "phone_channel": "sms"
  }
}
```

**Frontend:** عند `verification_required` → استدعِ **Resend** بـ `company_id` ثم افتح شاشة OTP → **Verify**.
**403** بدون `data` = حساب موقوف، اعرض `message`.

## 5) Forgot Password (Email / SMS)

`POST /api/company/password/forgot`

```json
{ "method": "email", "value": "owner@glow.test" }
```

```json
{ "method": "sms", "value": "963991234567" }
```

(الهاتف مقبول مع `+` أو بدونها.)

**200** — **نفس الرد دائماً** سواء كان الحساب موجوداً أم لا (حماية من كشف الحسابات):

```json
{
  "status": true,
  "message": "If the details match an account, a verification code has been sent.",
  "data": { "method": "email", "expires_in": 600, "dev_code": null }
}
```

**422** إذا `method` ليست `email|sms` أو `value` لا يطابقها.

**Frontend:** احفظ `method` و`value` وافتح شاشة الكود.

## 6) Verify Forgot Password OTP

`POST /api/company/password/verify`

```json
{ "method": "email", "value": "owner@glow.test", "code": "4821" }
```

**200**

```json
{
  "status": true,
  "message": "Code verified successfully.",
  "data": { "reset_token": "…64 chars…", "expires_in": 900 }
}
```

أخطاء: `422` كود خاطئ/منتهي · `429` محاولات كثيرة. كلمة المرور **لا تتغير** في هذه الخطوة.

## 7) Reset Password

`POST /api/company/password/reset`

```json
{
  "reset_token": "…",
  "password": "new-password-1",
  "password_confirmation": "new-password-1"
}
```

**200**

```json
{
  "status": true,
  "message": "Your password has been reset. You can now sign in.",
  "data": null
}
```

**422** token غير صالح/منتهي/مستخدم مسبقاً، أو كلمة مرور قصيرة/غير متطابقة.

بعد النجاح: الـ `reset_token` يُلغى، **وكل الأجهزة المسجّلة تخرج** (يُلغى الـ token القديم). **Frontend:** امسح الـ token المحلي واذهب لشاشة Login.

## 8) Me

`GET /api/company/me` (Bearer) → `data.company` بنفس الشكل أعلاه.

## 9) Logout

`POST /api/company/logout` (Bearer)

```json
{ "status": true, "message": "You have been signed out.", "data": null }
```

**Frontend:** امسح الـ token. أي 401 لاحقاً = ارجع لشاشة الدخول.

> ملاحظة: كل تسجيل دخول/تحقق يُصدر token جديداً ويُبطل السابق (جهاز واحد نشط) — نفس سلوك Customer API.

---

# Profile — تعديل البيانات والشعار

كلها محمية: `Authorization: Bearer <token>`. وكلها ترجّع `data.company` بنفس شكل **Me**.

## 10) Update Profile

`POST /api/company/profile` — **multipart/form-data** (بسبب ملف الشعار).

نفس حقول صفحة الملف الشخصي في الويب. **كل الحقول اختيارية — أرسل فقط ما تغيّر.**

| الحقل | ملاحظات |
|---|---|
| `name_en` | ≤255، لا يكون فارغاً إذا أُرسل |
| `name_ar` | ≤255، لا يكون فارغاً إذا أُرسل |
| `email` | إيميل صالح و**فريد** (إرسال إيميلك الحالي مسموح) |
| `phone` | **فريد** — E.164 مع `+` مثل `+963991234567` |
| `logo` | ملف صورة `jpeg/jpg/png/webp` حتى **2MB** — اختياري |

مثال (form-data):

```text
name_en = Glow Studio
logo    = <file: logo.png>
```

**200**

```json
{
  "status": true,
  "message": "Profile updated successfully.",
  "data": {
    "company": {
      "id": 123,
      "name": "Glow Studio",
      "name_en": "Glow Studio",
      "name_ar": "استوديو جلو",
      "owner_name": "Khaled",
      "email": "owner@glow.test",
      "phone": "+963991234567",
      "category": { "id": 1, "name": "Salon" },
      "logo": "https://…/storage/companies/logos/B7RBhB7Yw3….png",
      "status": "active",
      "verified": true,
      "email_verified": true,
      "phone_verified": true,
      "email_verified_at": "…",
      "phone_verified_at": "…",
      "submitted_for_review_at": null,
      "created_at": "…"
    }
  }
}
```

**422**

```json
{
  "status": false,
  "message": "This phone number is already registered.",
  "data": null,
  "errors": {
    "phone": ["This phone number is already registered."],
    "logo": ["The logo field must not be greater than 2048 kilobytes."]
  }
}
```

**Frontend:** أرسل الطلب كـ `multipart/form-data`، ثم حدّث بيانات الشركة المحلية من `data.company` (ومنها رابط `logo` الجديد).

## 11) Upload Logo

`POST /api/company/logo` — **multipart/form-data**، حقل واحد مطلوب:

| الحقل | ملاحظات |
|---|---|
| `logo` | ✔ صورة `jpeg/jpg/png/webp` حتى 2MB |

يستبدل الشعار (القديم يُحذف تلقائياً). **200** → `data.company.logo` = رابط الصورة الجديدة.

## 12) Delete Logo

`DELETE /api/company/logo` — بدون body. **200** → `data.company.logo` = `null`.
