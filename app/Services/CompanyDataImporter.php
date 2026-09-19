<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Company;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * يستورد نسخة JSON (المنتَجة من CompanyDataExporter) إلى شركة جديدة.
 *
 * الاستراتيجية:
 *  - إدراج خام (DB::table) لأن القيم المُصدَّرة بصيغة التخزين أصلاً.
 *  - كل شيء داخل transaction واحدة (الكل أو لا شيء).
 *  - ID remapping: لكل كيان خريطة oldId→newId، وتُستبدل الـ FK حسب المانيفست.
 *  - أعمدة nullify (روابط لكيانات غير مُصدَّرة) تُفرَّغ.
 *  - أعمدة unique_suffix تأخذ لاحقة عند التعارض بدل فشل الصف.
 */
class CompanyDataImporter
{
    /** @var array<string, array<int|string, int>> entityKey => [oldId => newId] */
    private array $idMaps = [];

    /** @var array<string, mixed> */
    private array $report = [];

    private int $newCompanyId = 0;

    /**
     * @param  array  $payload  محتوى ملف JSON مفكوكاً.
     * @return array{company: \App\Models\Company, report: array}
     */
    public function import(array $payload): array
    {
        if (($payload['meta']['format_version'] ?? null) !== 1) {
            throw new RuntimeException('ملف غير صالح أو بإصدار غير مدعوم (format_version != 1).');
        }
        if (empty($payload['company']) || ! is_array($payload['company'])) {
            throw new RuntimeException('الملف لا يحتوي على بيانات الشركة.');
        }

        return DB::transaction(function () use ($payload) {
            $company = $this->createCompany($payload['company']);
            $this->newCompanyId = $company->id;

            foreach ((array) config('data-export.entities', []) as $key => $def) {
                $this->importEntity($key, $def, $payload['entities'][$key] ?? null);
            }

            return [
                'company' => $company,
                'report'  => $this->report,
            ];
        });
    }

    /**
     * إنشاء الشركة من ملف البروفايل (إدراج خام مع معالجة القيود).
     */
    private function createCompany(array $src): Company
    {
        $table = (new Company)->getTable();
        $row   = $this->onlyRealColumns($src, $table);

        // نُسقِط الحقول التي يجب توليدها من جديد.
        unset($row['id'], $row['deleted_at'], $row['closure_reason'], $row['remember_token']);

        // البريد فريد — نضمن عدم التعارض (حتى مع المحذوفة softly).
        $email = $row['email'] ?? ('import+' . uniqid() . '@example.com');
        $base = $email; $i = 1;
        while (Company::withTrashed()->where('email', $email)->exists()) {
            $email = $this->suffixEmail($base, $i++);
        }
        $row['email'] = $email;

        // كلمة مرور مؤقتة (غير مُصدَّرة) — الأونر يعيد تعيينها لاحقاً.
        $row['password'] = Hash::make(Str::random(40));

        // category_id إلزامي — نُبقيه إن وُجد وإلا أول تصنيف.
        if (empty($row['category_id']) || ! Category::whereKey($row['category_id'])->exists()) {
            $row['category_id'] = Category::query()->value('id');
        }

        // plan_id اختياري — نُفرِّغه إن لم توجد الخطة.
        if (! empty($row['plan_id']) && ! Plan::whereKey($row['plan_id'])->exists()) {
            $row['plan_id'] = null;
        }

        $row['status']     = 'active';
        $row['created_at'] = $row['created_at'] ?? now();
        $row['updated_at'] = now();

        $id = DB::table($table)->insertGetId($row);

        $this->report['company'] = ['id' => $id, 'email' => $email];

        return Company::findOrFail($id);
    }

    /**
     * استيراد كيان واحد مع remap/nullify/unique_suffix.
     */
    private function importEntity(string $key, array $def, ?array $data): void
    {
        $this->idMaps[$key] = [];

        $rows = $data['rows'] ?? [];
        if (empty($rows)) {
            $this->report[$key] = ['imported' => 0, 'skipped' => 0];
            return;
        }

        $table    = (new $def['model'])->getTable();
        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $oldId  = $row['id'] ?? null;
            $insert = $this->prepareRow($def, $row, $table);

            if ($insert === null) {
                $skipped++;
                continue;
            }

            try {
                $newId = DB::table($table)->insertGetId($insert);
                if ($oldId !== null) {
                    $this->idMaps[$key][$oldId] = $newId;
                }
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                $this->report['_errors'][] = "{$key}#{$oldId}: " . $e->getMessage();
            }
        }

        $this->report[$key] = ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * تجهيز صف للإدراج: remap للـ FK، nullify، لاحقة الفريد، وتنظيف الأعمدة.
     *
     * @return array|null  null = تخطَّ الصف (فشل remap لعمود إلزامي).
     */
    private function prepareRow(array $def, array $row, string $table): ?array
    {
        unset($row['id'], $row['deleted_at']);

        // remap للمفاتيح الأجنبية.
        foreach ($def['remap'] ?? [] as $col => $targetEntity) {
            if ($targetEntity === '@company') {
                $row[$col] = $this->newCompanyId;
                continue;
            }

            $old = $row[$col] ?? null;
            if ($old === null || $old === '') {
                $row[$col] = null;
                continue;
            }

            // إن لم نجد المعرّف الجديد → نُفرِّغ الخانة (أفضل من رابط معطوب).
            $row[$col] = $this->idMaps[$targetEntity][$old] ?? null;
        }

        // تفريغ الأعمدة غير القابلة للربط.
        foreach ($def['nullify'] ?? [] as $col) {
            if (array_key_exists($col, $row)) {
                $row[$col] = null;
            }
        }

        // نُبقي فقط أعمدة الجدول الحقيقية.
        $row = $this->onlyRealColumns($row, $table);

        // كلمة مرور مؤقتة لأي جدول يتطلبها (مثل الموظفين) — غير مُصدَّرة لأسباب أمنية.
        if (Schema::hasColumn($table, 'password') && empty($row['password'])) {
            $row['password'] = Hash::make(Str::random(40));
        }

        // أعمدة فريدة عالمياً → لاحقة عند التعارض.
        foreach ($def['unique_suffix'] ?? [] as $col) {
            if (! isset($row[$col]) || $row[$col] === null || $row[$col] === '') {
                continue;
            }
            $base = (string) $row[$col];
            $val  = $base;
            $n    = 1;
            while (DB::table($table)->where($col, $val)->exists()) {
                $val = $base . '-R' . $n++;
                if ($n > 100) {
                    break;
                }
            }
            $row[$col] = $val;
        }

        if (! array_key_exists('created_at', $row) && Schema::hasColumn($table, 'created_at')) {
            $row['created_at'] = now();
        }
        if (Schema::hasColumn($table, 'updated_at')) {
            $row['updated_at'] = $row['updated_at'] ?? now();
        }

        return $row;
    }

    /**
     * @return array<string,mixed>
     */
    private function onlyRealColumns(array $row, string $table): array
    {
        $cols = Schema::getColumnListing($table);

        return array_intersect_key($row, array_flip($cols));
    }

    private function suffixEmail(string $email, int $n): string
    {
        if (! str_contains($email, '@')) {
            return $email . '+import' . $n;
        }

        [$local, $domain] = explode('@', $email, 2);

        return $local . '+import' . $n . '@' . $domain;
    }
}
