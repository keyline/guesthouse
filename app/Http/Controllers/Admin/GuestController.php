<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuestRequest;
use App\Models\Booking;
use App\Models\User;
use App\Support\AdminNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request): View
    {
        $guests = User::query()
            ->where('role', User::ROLE_CUSTOMER)
            ->withCount('bookings')
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.guests.index', [
            'guests' => $guests,
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function create(): View
    {
        return view('admin.guests.create', [
            'guest' => new User([
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
            ]),
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function store(GuestRequest $request): RedirectResponse
    {
        $guest = User::query()->create($request->attributesForModel());

        $this->linkHistoricalBookings($guest);

        return redirect()
            ->route('admin.guests.show', $guest)
            ->with('status', 'Guest created successfully.');
    }

    public function show(User $guest): View
    {
        $this->ensureCustomer($guest);

        $guest->load(['bookings.property', 'bookings.roomType', 'bookings.room']);

        return view('admin.guests.show', [
            'guest' => $guest,
            'stats' => $this->stats($guest),
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function edit(User $guest): View
    {
        $this->ensureCustomer($guest);

        return view('admin.guests.edit', [
            'guest' => $guest,
            'navItems' => AdminNavigation::make('guests'),
        ]);
    }

    public function update(GuestRequest $request, User $guest): RedirectResponse
    {
        $this->ensureCustomer($guest);

        $guest->update($request->attributesForModel());
        $this->linkHistoricalBookings($guest);

        return redirect()
            ->route('admin.guests.show', $guest)
            ->with('status', 'Guest updated successfully.');
    }

    public function destroy(User $guest): RedirectResponse
    {
        $this->ensureCustomer($guest);

        $guest->update(['is_active' => false]);

        return redirect()
            ->route('admin.guests.index')
            ->with('status', 'Guest deactivated successfully.');
    }

    private function ensureCustomer(User $guest): void
    {
        abort_unless($guest->role === User::ROLE_CUSTOMER, 404);
    }

    private function linkHistoricalBookings(User $guest): void
    {
        Booking::query()
            ->whereNull('user_id')
            ->where('guest_email', $guest->email)
            ->update(['user_id' => $guest->id]);

        $lastBooking = $guest->bookings()->max('check_in_date');

        if ($lastBooking) {
            $guest->forceFill(['last_booking_at' => $lastBooking])->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(User $guest): array
    {
        $bookings = $guest->bookings;

        return [
            'bookings' => $bookings->count(),
            'nights' => $bookings->sum('nights'),
            'spent' => $bookings->sum('total_amount_minor'),
            'lastBooking' => $bookings->max('check_in_date'),
        ];
    }
}
