<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * يجمع كامل بيانات شركة واحدة اعتماداً على مانيفست config/data-export.php.
 *
 * القاعدة: يُصدَّر الكيان إذا كانت ميزته مفعّلة، أو كان فيه بيانات فعلية
 * (حتى لا تضيع بيانات ميزة أُطفئت لاحقاً). الكيانات الأساسية (feature = null)
 * تُصدَّر دائماً.
 */
class CompanyDataExporter
{
    /** @var array<int,int> */
    private ?array $branchIds = null;

    public function __construct(private Company $company)
    {
    }

    /**
     * البيانات المجمّعة، جاهزة للإكسل و JSON.
     *
     * @return array<string, array{label:string, headings:array<int,string>, rows:array<int,array<string,mixed>>}>
     */
    public function collect(): array
    {
        $out = [];

        foreach ((array) config('data-export.entities', []) as $key => $def) {
            $builder = $this->builderFor($def);
            if ($builder === null) {
                continue;
            }

            $featureOn = empty($def['feature']) || $this->company->hasFeature($def['feature']);

            // قاعدة "مفعّل أو فيه بيانات": تجاهل فقط ما هو مُطفأ وفارغ.
            if (! $featureOn && (clone $builder)->count() === 0) {
                continue;
            }

            $columns = $this->columnsFor($def);

            $rows = $builder->get()
                ->map(fn (Model $m) => $this->rowArray($m, $columns))
                ->all();

            $out[$key] = [
                'label'    => $def['label'] ?? $key,
                'headings' => $columns,
                'rows'     => $rows,
            ];
        }

        return $out;
    }

    /**
     * صورة JSON كاملة قابلة لإعادة الاستيراد لاحقاً.
     */
    public function toJsonPayload(): array
    {
        return [
            'meta' => [
                'format_version' => 1,
                'exported_at'    => now()->toIso8601String(),
                'company_id'     => $this->company->id,
                'app'            => config('app.name'),
            ],
            'company'  => $this->companyProfile(),
            'entities' => $this->collect(),
        ];
    }

    /**
     * ملف تعريف الشركة نفسها (بدون أعمدة حساسة).
     */
    private function companyProfile(): array
    {
        $columns = $this->safeColumns($this->company->getTable(), null);

        return $this->rowArray($this->company, $columns);
    }

    /**
     * بناء الاستعلام المُنطَّق حسب استراتيجية الربط.
     */
    private function builderFor(array $def): ?Builder
    {
        $model = $def['model'] ?? null;
        if (! $model || ! class_exists($model)) {
            return null;
        }

        /** @var Model $instance */
        $instance = new $model;
        $query    = $model::query();

        switch ($def['scope'] ?? 'company') {
            case 'through_branches':
                if (! Schema::hasColumn($instance->getTable(), 'branch_id')) {
                    return null;
                }
                return $query->whereIn('branch_id', $this->branchIds());

            case 'company':
            default:
                if (! Schema::hasColumn($instance->getTable(), 'company_id')) {
                    return null;
                }
                return $query->where('company_id', $this->company->id);
        }
    }

    /**
     * أعمدة الكيان: صريحة من المانيفست إن وُجدت، وإلا كل أعمدة الجدول
     * تلقائياً (ناقص القائمة السوداء) — هكذا أي حقل جديد يُصدَّر لوحده.
     *
     * @return array<int,string>
     */
    private function columnsFor(array $def): array
    {
        /** @var Model $instance */
        $instance = new $def['model'];

        return $this->safeColumns($instance->getTable(), $def['columns'] ?? null);
    }

    /**
     * @param  array<int,string>|null  $explicit
     * @return array<int,string>
     */
    private function safeColumns(string $table, ?array $explicit): array
    {
        $blacklist = (array) config('data-export.blacklist', []);
        $columns   = $explicit ?? Schema::getColumnListing($table);

        return array_values(array_filter(
            $columns,
            fn (string $col) => ! in_array($col, $blacklist, true)
        ));
    }

    /**
     * تحويل نموذج إلى صف مسطّح قابل للكتابة في إكسل/JSON.
     *
     * @param  array<int,string>  $columns
     * @return array<string,mixed>
     */
    private function rowArray(Model $model, array $columns): array
    {
        $row = [];

        foreach ($columns as $col) {
            $row[$col] = $this->stringify($model->getAttribute($col));
        }

        return $row;
    }

    private function stringify(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_object($value)) {
            // كائنات أخرى (Collections/Value objects) → JSON أو نص.
            return method_exists($value, '__toString')
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }

    /**
     * @return array<int,int>
     */
    private function branchIds(): array
    {
        return $this->branchIds ??= $this->company->branches()->pluck('id')->all();
    }
}
