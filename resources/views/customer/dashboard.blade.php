<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Bookings | {{ config('app.name', 'Hotel Chain Manager') }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
    <header class="border-b border-slate-200 bg-white px-5 py-4">
        <div class="mx-auto flex max-w-6xl items-center justify-between">
            <a href="{{ url('/') }}" class="font-black">Hotel Chain Manager</a>
            <form method="POST" action="{{ route('customer.logout') }}">
                @csrf
                <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold">Logout</button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-5 py-8">
        <p class="text-sm font-semibold text-slate-500">Customer Portal</p>
        <h1 class="mt-2 text-3xl font-black">Welcome, {{ auth()->user()->name }}</h1>
        <section class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">Total bookings</p>
                <p class="mt-2 text-3xl font-black">{{ $bookings->count() }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">Total nights</p>
                <p class="mt-2 text-3xl font-black">{{ $bookings->sum('nights') }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">Upcoming</p>
                <p class="mt-2 text-3xl font-black">{{ $bookings->where('check_in_date', '>=', now()->startOfDay())->count() }}</p>
            </div>
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-black">Booking history</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Booking</th>
                            <th class="px-5 py-3">Property</th>
                            <th class="px-5 py-3">Room</th>
                            <th class="px-5 py-3">Dates</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($bookings as $booking)
                            <tr>
                                <td class="px-5 py-4 font-black">{{ $booking->booking_number }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $booking->property->name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $booking->room->room_number }} / {{ $booking->roomType->name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $booking->check_in_date->format('M j') }} - {{ $booking->check_out_date->format('M j, Y') }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ str_replace('_', ' ', ucfirst($booking->status)) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center font-semibold text-slate-500">Your booking history will appear here after your first reservation.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
