<?php

namespace App\Http\Requests\Admin;

use App\Models\Discount;
use App\Support\AdminPropertyScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->input('apply_mode') === 'coupon'
                ? strtoupper(trim((string) $this->input('code')))
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'apply_mode' => ['required', Rule::in(['coupon', 'automatic'])],
            'code' => [
                'exclude_unless:apply_mode,coupon',
                'required', 'string', 'max:40', 'alpha_num:ascii',
                Rule::unique(Discount::class, 'code')->ignore($this->route('discount')),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (\App\Models\Corporate::query()->where('booking_code', $value)->exists()) {
                        $fail('This code is already used by a company tie-up — pick a different one.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'property_id' => ['nullable', 'integer'],
            'room_type_id' => ['nullable', 'integer', Rule::exists(\App\Models\RoomType::class, 'id')],
            'discount_type' => ['required', Rule::in(array_keys(Discount::typeLabels()))],
            'discount_value' => [
                'required', 'numeric', 'min:0.01',
                $this->input('discount_type') === Discount::TYPE_PERCENT ? 'max:100' : 'max:99999999',
            ],
            'max_discount' => ['nullable', 'numeric', 'min:1', 'max:99999999'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'min_nights' => ['nullable', 'integer', 'min:1', 'max:365'],
            'min_amount' => ['nullable', 'numeric', 'min:1', 'max:99999999'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Enter the code guests will type in, e.g. WELCOME10.',
            'code.unique' => 'That coupon code is already in use.',
            'discount_value.max' => 'A percentage discount cannot be more than 100%.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $propertyId = $this->input('property_id');

                if ($propertyId && ! app(AdminPropertyScope::class)->canAccessProperty((int) $propertyId)) {
                    $validator->errors()->add('property_id', 'You do not have access to that property.');
                }
            },
        ];
    }

    /**
     * Validated input mapped to database columns: rupees become minor units,
     * a percentage becomes basis points.
     *
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $isPercent = $this->input('discount_type') === Discount::TYPE_PERCENT;

        return [
            'property_id' => $this->input('property_id') ?: null,
            'room_type_id' => $this->input('room_type_id') ?: null,
            'code' => $this->validated('code'),
            'name' => $this->validated('name'),
            'description' => $this->validated('description') ?: null,
            'discount_type' => $this->validated('discount_type'),
            // Percent → basis points, rupees → minor units: both are ×100.
            'discount_value' => (int) round(((float) $this->validated('discount_value')) * 100),
            'max_discount_minor' => $isPercent && $this->filled('max_discount')
                ? (int) round(((float) $this->validated('max_discount')) * 100)
                : null,
            'valid_from' => $this->validated('valid_from') ?: null,
            'valid_until' => $this->validated('valid_until') ?: null,
            'min_nights' => $this->validated('min_nights') ?: null,
            'min_amount_minor' => $this->filled('min_amount')
                ? (int) round(((float) $this->validated('min_amount')) * 100)
                : null,
            'max_uses' => $this->validated('max_uses') ?: null,
        ];
    }
}
