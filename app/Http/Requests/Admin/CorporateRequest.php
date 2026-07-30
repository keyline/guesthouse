<?php

namespace App\Http\Requests\Admin;

use App\Models\Corporate;
use App\Models\Discount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorporateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'booking_code' => strtoupper(trim((string) $this->input('booking_code'))) ?: null,
            'gstin' => strtoupper(trim((string) $this->input('gstin'))),
            'pan' => strtoupper(trim((string) $this->input('pan'))) ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $corporate = $this->route('corporate');

        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'gstin' => ['required', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', Rule::unique(Corporate::class, 'gstin')->ignore($corporate)],
            'pan' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'booking_code' => [
                'nullable', 'string', 'regex:/^[A-Z0-9-]{4,20}$/',
                Rule::unique(Corporate::class, 'booking_code')->ignore($corporate),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (Discount::query()->where('code', $value)->exists()) {
                        $fail('This code is already used by a coupon — pick a different one.');
                    }
                },
            ],
            'default_billing' => ['nullable', Rule::in([\App\Models\Booking::BILLING_GUEST, \App\Models\Booking::BILLING_CORPORATE])],
            'discount_type' => ['nullable', Rule::in(array_keys(Discount::typeLabels()))],
            'discount_value' => [
                'nullable', 'required_with:discount_type', 'numeric', 'min:0.01',
                $this->input('discount_type') === Discount::TYPE_PERCENT ? 'max:100' : 'max:99999999',
            ],
            'rates' => ['nullable', 'array'],
            'rates.*' => ['nullable', 'numeric', 'min:1', 'max:99999999'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gstin.regex' => 'Enter a valid 15-character GSTIN.',
            'pan.regex' => 'Enter a valid 10-character PAN.',
            'booking_code.regex' => 'The booking code must be 4–20 letters, numbers or hyphens.',
            'discount_value.max' => 'A percentage discount cannot be more than 100%.',
        ];
    }

    /**
     * Validated input mapped to corporate columns; rupees → minor units,
     * percent → basis points.
     *
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $hasDiscount = filled($this->validated('discount_type'));

        return [
            'legal_name' => $this->validated('legal_name'),
            'trade_name' => $this->validated('trade_name') ?: null,
            'gstin' => $this->validated('gstin'),
            'pan' => $this->validated('pan'),
            'booking_code' => $this->validated('booking_code'),
            'default_billing' => $this->validated('default_billing') ?: \App\Models\Booking::BILLING_GUEST,
            'discount_type' => $hasDiscount ? $this->validated('discount_type') : null,
            'discount_value' => $hasDiscount
                ? (int) round(((float) $this->validated('discount_value')) * 100)
                : null,
            'contact_name' => $this->validated('contact_name') ?: null,
            'email' => $this->validated('email') ?: null,
            'phone' => $this->validated('phone') ?: null,
            'address_line_1' => $this->validated('address_line_1'),
            'address_line_2' => $this->validated('address_line_2') ?: null,
            'city' => $this->validated('city'),
            'state' => $this->validated('state'),
            'postal_code' => $this->validated('postal_code'),
            'country' => $this->validated('country') ?: 'India',
        ];
    }

    /**
     * Rate-card input as room_type_id => price_minor, blanks removed.
     *
     * @return array<int, int>
     */
    public function rateCard(): array
    {
        return collect($this->validated('rates', []))
            ->filter(fn ($price) => filled($price))
            ->mapWithKeys(fn ($price, $roomTypeId) => [(int) $roomTypeId => (int) round(((float) $price) * 100)])
            ->all();
    }
}
