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
            'status' => ['required', Rule::in([RoomType::STATUS_ACTIVE, RoomType::STATUS_INACTIVE])],
            'max_adults' => ['required', 'integer', 'min:1', 'max:20'],
            'max_children' => ['required', 'integer', 'min:0', 'max:20'],
            'is_pet_friendly' => ['nullable', 'boolean'],
            'extra_bed_available' => ['nullable', 'boolean'],
            'max_extra_beds' => ['nullable', 'required_if:extra_bed_available,1', 'integer', 'min:1', 'max:5'],
            'extra_bed_charge' => ['nullable', 'required_if:extra_bed_available,1', 'numeric', 'min:0', 'max:100000'],
            'extra_bed_charge_basis' => ['nullable', 'required_if:extra_bed_available,1', Rule::in(['per_night', 'per_stay'])],
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
            'code' => RoomType::uniqueCode($validated['name'], $this->route('room_type')?->id),
            'status' => $validated['status'],
            'max_adults' => $validated['max_adults'],
            'max_children' => $validated['max_children'],
            'is_pet_friendly' => $this->boolean('is_pet_friendly'),
            'extra_bed_available' => $this->boolean('extra_bed_available'),
            'max_extra_beds' => $this->boolean('extra_bed_available') ? (int) $validated['max_extra_beds'] : 0,
            'extra_bed_charge_minor' => $this->boolean('extra_bed_available') ? (int) round(((float) $validated['extra_bed_charge']) * 100) : 0,
            'extra_bed_charge_basis' => $this->boolean('extra_bed_available') ? $validated['extra_bed_charge_basis'] : 'per_night',
            'sort_order' => $validated['sort_order'] ?? 0,
            'description' => $validated['description'] ?? null,
        ];
    }
}
