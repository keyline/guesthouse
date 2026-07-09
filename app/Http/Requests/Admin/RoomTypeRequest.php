<?php

namespace App\Http\Requests\Admin;

use App\Models\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomTypeRequest extends FormRequest
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
        $roomType = $this->route('room_type');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique(RoomType::class, 'code')
                    ->ignore($roomType?->id),
            ],
            'status' => ['required', Rule::in([RoomType::STATUS_ACTIVE, RoomType::STATUS_INACTIVE])],
            'max_adults' => ['required', 'integer', 'min:1', 'max:20'],
            'max_children' => ['required', 'integer', 'min:0', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'description' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'code' => ($validated['code'] ?? null) ? RoomType::makeCode($validated['code']) : RoomType::makeCode($validated['name']),
            'status' => $validated['status'],
            'max_adults' => $validated['max_adults'],
            'max_children' => $validated['max_children'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'description' => $validated['description'] ?? null,
        ];
    }
}
