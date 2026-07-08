<?php

namespace App\Http\Requests\Admin;

use App\Models\Property;
use App\Models\RoomType;
use App\Support\AdminPropertyScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'property_id' => ['required', 'integer', Rule::exists(Property::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique(RoomType::class, 'code')
                    ->where('property_id', $this->integer('property_id'))
                    ->ignore($roomType?->id),
            ],
            'status' => ['required', Rule::in([RoomType::STATUS_ACTIVE, RoomType::STATUS_INACTIVE])],
            'max_adults' => ['required', 'integer', 'min:1', 'max:20'],
            'max_children' => ['required', 'integer', 'min:0', 'max:20'],
            'base_occupancy' => ['required', 'integer', 'min:1', 'max:20'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'currency' => ['required', 'string', 'size:3'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'description' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (! app(AdminPropertyScope::class)->canAccessProperty($this->integer('property_id'), $this->user())) {
                    $validator->errors()->add('property_id', 'You do not have access to this property.');
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $validated = $this->validated();

        return [
            'property_id' => $validated['property_id'],
            'name' => $validated['name'],
            'code' => ($validated['code'] ?? null) ? RoomType::makeCode($validated['code']) : RoomType::makeCode($validated['name']),
            'status' => $validated['status'],
            'max_adults' => $validated['max_adults'],
            'max_children' => $validated['max_children'],
            'base_occupancy' => $validated['base_occupancy'],
            'base_price_minor' => (int) round(((float) $validated['base_price']) * 100),
            'currency' => strtoupper($validated['currency']),
            'sort_order' => $validated['sort_order'] ?? 0,
            'description' => $validated['description'] ?? null,
        ];
    }
}
