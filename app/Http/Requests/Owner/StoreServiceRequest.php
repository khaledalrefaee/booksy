<?php

namespace App\Http\Requests\Owner;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $currencies = array_keys(config('booksy.currencies', ['SYP' => []]));

        return [
            'service_category_id' => ['required', 'integer', 'exists:service_categories,id'],
            'service_type'        => ['nullable', Rule::in(Service::TYPES)],
            'name_en'             => ['required', 'string', 'max:255'],
            'name_ar'             => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string', 'max:10000'],
            'price'               => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'price_type'          => ['nullable', Rule::in(Service::PRICE_TYPES)],
            'price_to'            => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'gte:price', 'required_if:price_type,range'],
            'currency'            => ['required', 'string', Rule::in($currencies)],
            'duration_minutes'    => ['required', 'integer', 'min:1', 'max:1440'],
            'image'               => ['nullable', 'image', 'max:4096'],
            'is_active'           => ['sometimes', 'boolean'],
            'is_bookable_online'  => ['sometimes', 'boolean'],
            'is_popular'          => ['sometimes', 'boolean'],
            'is_recommended'      => ['sometimes', 'boolean'],
            'discount_type'       => ['nullable', 'in:percent,fixed'],
            'discount_value'      => ['nullable', 'numeric', 'min:0'],
            'discount_starts_at'  => ['nullable', 'date'],
            'discount_ends_at'    => ['nullable', 'date', 'after_or_equal:discount_starts_at'],
        ];
    }
}
