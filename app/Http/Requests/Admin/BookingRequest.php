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
            'room_ids' => ['required', 'array', 'min:1', 'max:10'],
            'room_ids.*' => [
                'integer', 'distinct',
                Rule::exists(Room::class, 'id')
                    ->where('property_id', $this->integer('property_id')),
            ],
            'user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('role', User::ROLE_CUSTOMER)],
            'status' => ['required', Rule::in(array_keys(self::statusOptionsFor($this->route('booking'))))],
            'source' => ['required', Rule::in(array_keys($this->sources()))],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['required', 'integer', 'min:0', 'max:20'],
            'total_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'corporate_id' => ['nullable', 'integer', Rule::exists(\App\Models\Corporate::class, 'id')->where('is_active', true)],
            'billing' => ['nullable', Rule::in([Booking::BILLING_GUEST, Booking::BILLING_CORPORATE])],
            'payment_status' => ['nullable', Rule::in([Booking::PAYMENT_UNPAID, Booking::PAYMENT_PAID])],
            'currency' => ['required', 'string', 'size:3'],
            'special_requests' => ['nullable', 'string', 'max:3000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_ids.required' => 'Select at least one room from the board.',
            'room_ids.min' => 'Select at least one room from the board.',
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

                if ($this->input('billing') === Booking::BILLING_CORPORATE && ! $this->filled('corporate_id')) {
                    $validator->errors()->add('billing', 'Pick the company to bill this stay to.');

                    return;
                }

                $booking = $this->route('booking');
                $checkIn = CarbonImmutable::parse($this->input('check_in_date'));
                $checkOut = CarbonImmutable::parse($this->input('check_out_date'));
                $isBlocking = in_array($this->input('status'), Booking::blockingStatuses(), true);
                $availability = app(AvailabilityService::class);
                $requestedRoomIds = collect($this->input('room_ids', []))->filter()->map(fn ($id) => (int) $id)->unique();
                $rooms = Room::query()->whereKey($requestedRoomIds)
                    ->whereHas('roomType', fn ($query) => $query->where('status', RoomType::STATUS_ACTIVE))->get();

                if ($rooms->count() !== $requestedRoomIds->count()) {
                    $validator->errors()->add('room_ids', 'One or more selected room categories are inactive.');
                    return;
                }

                if ($isBlocking) {
                    foreach ($rooms as $room) {
                        if (! $availability->roomIsAvailable($room, $checkIn, $checkOut, $booking?->id)) {
                            $validator->errors()->add('room_ids', "Room {$room->room_number} is not available for the selected dates.");

                            return;
                        }
                    }

                    foreach ($rooms->groupBy('room_type_id') as $roomTypeId => $roomsOfType) {
                        $capacity = $availability->typeAvailability(
                            $this->integer('property_id'),
                            (int) $roomTypeId,
                            $checkIn,
                            $checkOut,
                            $booking?->id,
                        );

                        if ($capacity < $roomsOfType->count()) {
                            $validator->errors()->add('room_ids', 'These rooms cannot be sold for the selected dates (sold out or stop-sell).');

                            return;
                        }
                    }
                }

                if (! $this->filled('total_amount')) {
                    foreach ($rooms->unique('room_type_id') as $room) {
                        $ratePlan = RatePlan::defaultFor($this->integer('property_id'), $room->room_type_id);

                        if (! $ratePlan || $availability->quote($ratePlan, $checkIn, $checkOut) === null) {
                            $validator->errors()->add('total_amount', "No nightly rate is set for room {$room->room_number} on these dates — enter a total price manually.");

                            return;
                        }
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

        $firstRoom = Room::query()->findOrFail(collect($validated['room_ids'])->filter()->first());
        $ratePlan = RatePlan::defaultFor((int) $validated['property_id'], $firstRoom->room_type_id);

        $totalMinor = isset($validated['total_amount']) && $validated['total_amount'] !== null
            ? (int) round(((float) $validated['total_amount']) * 100)
            : (int) ($ratePlan ? app(AvailabilityService::class)->quote($ratePlan, $checkIn, $checkOut) : 0);

        return [
            'property_id' => $validated['property_id'],
            'room_type_id' => $firstRoom->room_type_id,
            'room_id' => $firstRoom->id,
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
            'corporate_id' => $validated['corporate_id'] ?? null,
            'billing' => $validated['billing'] ?? Booking::BILLING_GUEST,
            'payment_status' => $validated['payment_status'] ?? ($this->route('booking')?->payment_status ?: Booking::PAYMENT_UNPAID),
            'currency' => strtoupper($validated['currency']),
            'special_requests' => $validated['special_requests'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'cancelled_at' => $validated['status'] === Booking::STATUS_CANCELLED ? now() : null,
        ];
    }

    /**
     * Statuses a staff member may set from the booking form. Checked in / checked out
     * are reachable only through the stay workflow, and cancellation through its own action.
     *
     * @return array<string, string>
     */
    public static function statusOptionsFor(?Booking $booking): array
    {
        $labels = Booking::statusLabels();

        if ($booking && in_array($booking->status, [Booking::STATUS_CHECKED_IN, Booking::STATUS_CHECKED_OUT], true)) {
            return [$booking->status => $labels[$booking->status]];
        }

        $options = [
            Booking::STATUS_PENDING => $labels[Booking::STATUS_PENDING],
            Booking::STATUS_CONFIRMED => $labels[Booking::STATUS_CONFIRMED],
        ];

        if ($booking?->status === Booking::STATUS_CANCELLED) {
            $options[Booking::STATUS_CANCELLED] = $labels[Booking::STATUS_CANCELLED];
        }

        return $options;
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
