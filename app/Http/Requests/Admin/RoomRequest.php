<?php

namespace App\Http\Requests\Admin;

use App\Models\Property;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\AdminPropertyScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RoomRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $room = $this->route('room');
        $propertyId = $room instanceof Room
            ? $room->property_id
            : app(AdminPropertyScope::class)->selectedPropertyId($this->user());

        // Property is controlled by the shared header context, never by a
        // hidden/stale browser value.
        $this->merge(['property_id' => $propertyId]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $room = $this->route('room');

        return [
            'property_id' => ['required', 'integer', Rule::exists(Property::class, 'id')],
            'room_type_id' => [
                'required',
                'integer',
                Rule::exists(RoomType::class, 'id')->where('status', RoomType::STATUS_ACTIVE),
            ],
            'room_number' => [
                'required',
                'string',
                'max:60',
                Rule::unique(Room::class, 'room_number')
                    ->where('property_id', $this->integer('property_id'))
                    ->ignore($room?->id),
            ],
            'floor' => ['nullable', 'string', 'max:60'],
            'status' => ['required', Rule::in([Room::STATUS_AVAILABLE, Room::STATUS_MAINTENANCE, Room::STATUS_BLOCKED])],
            'is_smoking' => ['boolean'],
            'is_accessible' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'amenity_present' => ['nullable', 'array'],
            'amenity_present.*' => ['integer', Rule::exists('amenities', 'id')->where('is_active', true)],
            'amenity_missing' => ['nullable', 'array'],
            'amenity_missing.*' => ['integer', Rule::exists('amenities', 'id')->where('is_active', true)],
            'amenity_state' => ['nullable', 'array'],
            'amenity_state.*' => ['nullable', Rule::in(['inherit', 'present', 'missing'])],
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
            'room_type_id' => $validated['room_type_id'],
            'room_number' => $validated['room_number'],
            'floor' => $validated['floor'] ?? null,
            'status' => $validated['status'],
            'is_smoking' => $this->boolean('is_smoking'),
            'is_accessible' => $this->boolean('is_accessible'),
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
