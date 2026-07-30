<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class GuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $guest = $this->route('guest');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($guest?->id)],
            'password' => [$guest ? 'nullable' : 'required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
            'is_active' => ['boolean'],
            'phone_country_code' => ['required', Rule::in(array_keys(PhoneNumber::countryCodes()))],
            'phone_national' => ['required', 'string', 'max:20', 'regex:/^[0-9 ()-]+$/'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:40'],
            'nationality' => ['nullable', 'string', 'max:120'],
            'id_document_type' => ['nullable', 'string', 'max:80'],
            'id_document_number' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'customer_type' => ['required', Rule::in(['individual', 'corporate'])],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'corporate_legal_name' => [Rule::requiredIf($this->input('customer_type') === 'corporate'), 'nullable', 'string', 'max:255'],
            'corporate_trade_name' => ['nullable', 'string', 'max:255'],
            'corporate_gstin' => [Rule::requiredIf($this->input('customer_type') === 'corporate'), 'nullable', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', Rule::unique('corporates', 'gstin')->ignore($guest?->corporate_id)],
            'corporate_pan' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'corporate_contact_name' => ['nullable', 'string', 'max:255'],
            'corporate_email' => ['nullable', 'email', 'max:255'],
            'corporate_phone' => ['nullable', 'string', 'max:40'],
            'corporate_address_line_1' => [Rule::requiredIf($this->input('customer_type') === 'corporate'), 'nullable', 'string', 'max:255'],
            'corporate_address_line_2' => ['nullable', 'string', 'max:255'],
            'corporate_city' => [Rule::requiredIf($this->input('customer_type') === 'corporate'), 'nullable', 'string', 'max:100'],
            'corporate_state' => [Rule::requiredIf($this->input('customer_type') === 'corporate'), 'nullable', 'string', 'max:100'],
            'corporate_postal_code' => [Rule::requiredIf($this->input('customer_type') === 'corporate'), 'nullable', 'string', 'max:20'],
            'corporate_country' => [Rule::requiredIf($this->input('customer_type') === 'corporate'), 'nullable', 'string', 'max:100'],
            'guest_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['phone_country_code', 'phone_national'])) return;
            try {
                $phone = PhoneNumber::normalize($this->string('phone_country_code')->toString(), $this->string('phone_national')->toString());
                $guest = $this->route('guest');
                if (User::query()->where('role', User::ROLE_CUSTOMER)->where('phone_e164', $phone['e164'])->when($guest, fn ($query) => $query->where('id', '!=', $guest->id))->exists()) {
                    $validator->errors()->add('phone_national', 'A guest profile already exists with this mobile number.');
                }
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $validator->errors()->add('phone_national', collect($exception->errors())->flatten()->first());
            }
        }];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $validated = $this->validated();
        $phone = PhoneNumber::normalize($validated['phone_country_code'], $validated['phone_national']);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => User::ROLE_CUSTOMER,
            'is_active' => $this->boolean('is_active'),
            'phone' => $phone['e164'],
            'phone_country_code' => $phone['country_code'],
            'phone_national' => $phone['national'],
            'phone_e164' => $phone['e164'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'id_document_type' => $validated['id_document_type'] ?? null,
            'id_document_number' => $validated['id_document_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'customer_type' => $validated['customer_type'],
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postal_code'],
            'country' => $validated['country'],
            'guest_notes' => $validated['guest_notes'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $attributes['password'] = $validated['password'];
        }

        return $attributes;
    }

    public function corporateAttributes(): array
    {
        $validated = $this->validated();
        return [
            'legal_name' => $validated['corporate_legal_name'], 'trade_name' => $validated['corporate_trade_name'] ?? null,
            'gstin' => strtoupper($validated['corporate_gstin']), 'pan' => strtoupper($validated['corporate_pan'] ?? '') ?: null,
            'contact_name' => $validated['corporate_contact_name'] ?? null, 'email' => $validated['corporate_email'] ?? null,
            'phone' => $validated['corporate_phone'] ?? null, 'address_line_1' => $validated['corporate_address_line_1'],
            'address_line_2' => $validated['corporate_address_line_2'] ?? null, 'city' => $validated['corporate_city'],
            'state' => $validated['corporate_state'], 'postal_code' => $validated['corporate_postal_code'],
            'country' => $validated['corporate_country'] ?? 'India', 'is_active' => true,
        ];
    }
}
