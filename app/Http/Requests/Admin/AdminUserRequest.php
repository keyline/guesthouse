<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_SUPER_ADMIN) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $adminUser = $this->route('admin_user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($adminUser?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in([User::ROLE_SUPER_ADMIN, User::ROLE_PROPERTY_MANAGER])],
            'password' => [$adminUser ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'property_ids' => ['array'],
            'property_ids.*' => ['integer', 'exists:properties,id'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function userAttributes(): array
    {
        $attributes = $this->safe()->only(['name', 'email', 'phone', 'role']);
        $attributes['is_active'] = $this->boolean('is_active');

        if ($this->filled('password')) {
            $attributes['password'] = $this->string('password')->toString();
        }

        return $attributes;
    }

    /**
     * @return list<int>
     */
    public function propertyIds(): array
    {
        if ($this->input('role') === User::ROLE_SUPER_ADMIN) {
            return [];
        }

        return collect($this->input('property_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
