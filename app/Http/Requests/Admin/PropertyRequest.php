<?php

namespace App\Http\Requests\Admin;

use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyRequest extends FormRequest
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
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'property_type' => ['nullable', Rule::in([Property::TYPE_GUEST_HOUSE, Property::TYPE_BANQUET, Property::TYPE_MIXED])],
            'status' => ['nullable', Rule::in([Property::STATUS_DRAFT, Property::STATUS_ACTIVE, Property::STATUS_INACTIVE])],
            'city' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'base_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'currency' => ['nullable', 'string', 'size:3'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'show_on_home' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:10000'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['nullable', 'integer', 'exists:amenities,id'],
            'images' => ['array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function propertyAttributes(): array
    {
        $validated = $this->validated();
        $name = $validated['name'] ?? $this->input('name') ?: 'Untitled Property';
        $status = $validated['status'] ?? Property::STATUS_DRAFT;

        return [
            'name' => $name,
            'property_type' => $validated['property_type'] ?? Property::TYPE_GUEST_HOUSE,
            'status' => $status,
            'city' => $validated['city'] ?? 'Unassigned',
            'location' => $validated['location'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? 'India',
            'postal_code' => $validated['postal_code'] ?? null,
            'address' => $validated['address'] ?? 'Address pending',
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'manager_name' => $validated['manager_name'] ?? null,
            'check_in_time_minutes' => $this->timeToMinutes($validated['check_in_time'] ?? '12:00'),
            'check_out_time_minutes' => $this->timeToMinutes($validated['check_out_time'] ?? '11:00'),
            'base_price_minor' => (int) round(((float) ($validated['base_price'] ?? 0)) * 100),
            'currency' => strtoupper($validated['currency'] ?? 'INR'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'show_on_home' => $this->boolean('show_on_home'),
            'description' => $validated['description'] ?? null,
            'published_at' => $status === Property::STATUS_ACTIVE ? now() : null,
        ];
    }

    /**
     * @return list<int>
     */
    public function amenityIds(): array
    {
        return collect($this->validated('amenities', []))
            ->filter()
            ->map(fn (mixed $amenity) => (int) $amenity)
            ->unique()
            ->values()
            ->all();
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
