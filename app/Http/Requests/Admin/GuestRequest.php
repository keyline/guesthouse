<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
            'phone' => ['nullable', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:40'],
            'nationality' => ['nullable', 'string', 'max:120'],
            'id_document_type' => ['nullable', 'string', 'max:80'],
            'id_document_number' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'guest_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $validated = $this->validated();

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => User::ROLE_CUSTOMER,
            'is_active' => $this->boolean('is_active'),
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'id_document_type' => $validated['id_document_type'] ?? null,
            'id_document_number' => $validated['id_document_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'guest_notes' => $validated['guest_notes'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $attributes['password'] = $validated['password'];
        }

        return $attributes;
    }
}
