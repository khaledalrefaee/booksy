<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Company;
use App\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Owner → bulk-create companies from an .xlsx/.csv upload.
 *
 * Uses ToCollection (not ToModel) so a bad row is reported and skipped instead
 * of aborting the whole file. Expected heading row (case-insensitive):
 *   name_en | name_ar | email | phone | category | plan | status | password
 * category/plan are matched by their English OR Arabic name. Missing password
 * is auto-generated. Results (created / skipped + reasons) are collected for the
 * controller to surface as toasts.
 */
class CompaniesImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public int $created = 0;

    /** @var array<int, string> */
    public array $errors = [];

    /** @var array<string, int> lower-cased category name => id */
    private array $categoryMap;

    /** @var array<string, int> lower-cased plan name => id */
    private array $planMap;

    public function __construct()
    {
        $this->categoryMap = $this->buildNameMap(Category::all());
        $this->planMap     = $this->buildNameMap(Plan::all());
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $line = $i + 2; // +1 heading row, +1 for 1-based display

            // Blank line — ignore silently.
            if ($this->isBlank($row)) {
                continue;
            }

            $data = [
                'name_en'  => trim((string) $row->get('name_en', '')),
                'name_ar'  => trim((string) $row->get('name_ar', '')),
                'email'    => trim((string) $row->get('email', '')),
                'phone'    => trim((string) $row->get('phone', '')) ?: null,
                'category' => trim((string) $row->get('category', '')),
                'plan'     => trim((string) $row->get('plan', '')),
                'status'   => strtolower(trim((string) $row->get('status', 'pending'))) ?: 'pending',
                'password' => (string) $row->get('password', ''),
            ];

            $validator = Validator::make($data, [
                'name_en' => ['required', 'string', 'max:255'],
                'name_ar' => ['required', 'string', 'max:255'],
                'email'   => ['required', 'email', 'max:255', 'unique:companies,email'],
                'phone'   => ['nullable', 'string', 'max:30'],
                'status'  => ['in:pending,active,suspended'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = __('Row :n: :msg', ['n' => $line, 'msg' => $validator->errors()->first()]);
                continue;
            }

            $categoryId = $this->categoryMap[mb_strtolower($data['category'])] ?? null;
            if (! $categoryId) {
                $this->errors[] = __('Row :n: unknown category ":cat".', ['n' => $line, 'cat' => $data['category']]);
                continue;
            }

            Company::query()->create([
                'name_en'     => $data['name_en'],
                'name_ar'     => $data['name_ar'],
                'email'       => $data['email'],
                'phone'       => $data['phone'],
                'category_id' => $categoryId,
                'plan_id'     => $data['plan'] !== '' ? ($this->planMap[mb_strtolower($data['plan'])] ?? null) : null,
                'status'      => $data['status'],
                'password'    => $data['password'] !== '' ? $data['password'] : Str::password(10),
            ]);

            $this->created++;
        }
    }

    /** @param \Illuminate\Support\Collection<int, object> $models */
    private function buildNameMap(Collection $models): array
    {
        $map = [];
        foreach ($models as $m) {
            if (! empty($m->name_en)) {
                $map[mb_strtolower($m->name_en)] = $m->id;
            }
            if (! empty($m->name_ar)) {
                $map[mb_strtolower($m->name_ar)] = $m->id;
            }
        }

        return $map;
    }

    private function isBlank($row): bool
    {
        foreach (['name_en', 'name_ar', 'email'] as $k) {
            if (trim((string) $row->get($k, '')) !== '') {
                return false;
            }
        }

        return true;
    }
}
