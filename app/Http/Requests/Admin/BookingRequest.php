<?php

namespace App\Http\Requests\Admin;

use App\Models\Booking;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use App\Support\AdminPropertyScope;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BookingRequest extends FormRequest
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
            'property_id' => ['required', 'integer', Rule::exists(Property::class, 'id')],
            'room_type_id' => [
                'required',
                'integer',
                Rule::exists(RoomType::class, 'id'),
            ],
            'room_id' => [
                'nullable',
                'integer',
                Rule::exists(Room::class, 'id')
                    ->where('property_id', $this->integer('property_id'))
                    ->where('room_type_id', $this->integer('room_type_id')),
            ],
            'rate_plan_id' => [
                'nullable',
                'integer',
                Rule::exists(RatePlan::class, 'id')
                    ->where('property_id', $this->integer('property_id'))
                    ->where('room_type_id', $this->integer('room_type_id'))
                    ->where('status', RatePlan::STATUS_ACTIVE),
            ],
            'user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('role', User::ROLE_CUSTOMER)],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'source' => ['required', Rule::in(array_keys($this->sources()))],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['required', 'integer', 'min:0', 'max:20'],
            'total_amount' => ['nullable', 'required_without:rate_plan_id', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['required', 'string', 'size:3'],
            'special_requests' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
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

                    return;
                }

                $booking = $this->route('booking');
                $checkIn = CarbonImmutable::parse($this->input('check_in_date'));
                $checkOut = CarbonImmutable::parse($this->input('check_out_date'));
                $isBlocking = in_array($this->input('status'), Booking::blockingStatuses(), true);
                $availability = app(AvailabilityService::class);

                if ($room = Room::query()->find($this->integer('room_id'))) {
                    $roomIsFree = $availability->roomIsAvailable($room, $checkIn, $checkOut, $booking?->id);

                    if (! $roomIsFree && $isBlocking) {
                        $validator->errors()->add('room_id', 'This room is not available for the selected dates.');

                        return;
                    }
                }

                if ($isBlocking) {
                    $capacity = $availability->typeAvailability(
                        $this->integer('property_id'),
                        $this->integer('room_type_id'),
                        $checkIn,
                        $checkOut,
                        $booking?->id,
                    );

                    if ($capacity < 1) {
                        $validator->errors()->add('room_type_id', 'No rooms of this type are available for the selected dates (sold out or stop-sell).');

                        return;
                    }
                }

                if (! $this->filled('total_amount') && ! $this->filled('rate_plan_id')) {
                    return;
                }

                if (! $this->filled('total_amount')) {
                    $ratePlan = RatePlan::query()->find($this->integer('rate_plan_id'));

                    if ($ratePlan && $availability->quote($ratePlan, $checkIn, $checkOut) === null) {
                        $validator->errors()->add('rate_plan_id', 'This rate plan is closed for one or more of the selected nights — enter a price manually or pick different dates.');
                    }
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
        $checkIn = CarbonImmutable::parse($validated['check_in_date']);
        $checkOut = CarbonImmutable::parse($validated['check_out_date']);

        $ratePlan = isset($validated['rate_plan_id'])
            ? RatePlan::query()->find($validated['rate_plan_id'])
            : null;

        $totalMinor = isset($validated['total_amount']) && $validated['total_amount'] !== null
            ? (int) round(((float) $validated['total_amount']) * 100)
            : (int) app(AvailabilityService::class)->quote($ratePlan, $checkIn, $checkOut);

        return [
            'property_id' => $validated['property_id'],
            'room_type_id' => $validated['room_type_id'],
            'room_id' => $validated['room_id'] ?? null,
            'rate_plan_id' => $ratePlan?->id,
            'user_id' => $validated['user_id'] ?? null,
            'status' => $validated['status'],
            'source' => $validated['source'],
            'guest_name' => $validated['guest_name'],
            'guest_email' => $validated['guest_email'] ?? null,
            'guest_phone' => $validated['guest_phone'] ?? null,
            'check_in_date' => $checkIn->toDateString(),
            'check_out_date' => $checkOut->toDateString(),
            'nights' => $checkIn->diffInDays($checkOut),
            'adults' => $validated['adults'],
            'children' => $validated['children'],
            'total_amount_minor' => $totalMinor,
            'currency' => strtoupper($validated['currency']),
            'special_requests' => $validated['special_requests'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'cancelled_at' => $validated['status'] === Booking::STATUS_CANCELLED ? now() : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function statuses(): array
    {
        return [
            Booking::STATUS_PENDING => 'Pending',
            Booking::STATUS_CONFIRMED => 'Confirmed',
            Booking::STATUS_CHECKED_IN => 'Checked in',
            Booking::STATUS_CHECKED_OUT => 'Checked out',
            Booking::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function sources(): array
    {
        return [
            Booking::SOURCE_DIRECT => 'Direct',
            Booking::SOURCE_PHONE => 'Phone',
            Booking::SOURCE_WALK_IN => 'Walk-in',
            Booking::SOURCE_ONLINE => 'Online',
        ];
    }
}
