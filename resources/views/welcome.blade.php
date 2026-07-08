<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Hotel Chain Manager') }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
    <div class="min-h-screen">
        <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-slate-200 bg-white px-5 py-6 lg:block">
            <div class="mb-8">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hotel Chain Manager</p>
                <h1 class="mt-2 text-xl font-bold">Owner Console</h1>
            </div>

            <nav class="space-y-1 text-sm font-medium">
                <a class="block rounded-md bg-slate-950 px-3 py-2 text-white" href="#">Dashboard</a>
                <a class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" href="#">Bookings</a>
                <a class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" href="#">Properties</a>
                <a class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" href="#">Rooms</a>
                <a class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" href="#">Guests</a>
                <a class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" href="#">Reports</a>
                <a class="block rounded-md px-3 py-2 text-slate-600 hover:bg-slate-100" href="#">Settings</a>
            </nav>
        </aside>

        <main class="lg:pl-64">
            <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/90 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Today, {{ now()->format('M j, Y') }}</p>
                        <h2 class="text-2xl font-bold tracking-tight">Chain Overview</h2>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <select class="h-10 rounded-md border border-slate-300 bg-white px-3 text-sm">
                            <option>All properties</option>
                            <option>City Center Hotel</option>
                            <option>Airport Suites</option>
                            <option>Beach Resort</option>
                        </select>
                        <button class="h-10 rounded-md bg-slate-950 px-4 text-sm font-semibold text-white">New Booking</button>
                    </div>
                </div>
            </header>

            <section class="px-4 py-6 sm:px-6 lg:px-8">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-white p-5">
                        <p class="text-sm font-medium text-slate-500">Occupancy</p>
                        <p class="mt-3 text-3xl font-bold">74%</p>
                        <p class="mt-1 text-sm text-emerald-700">+8% vs last week</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-5">
                        <p class="text-sm font-medium text-slate-500">Tonight's Bookings</p>
                        <p class="mt-3 text-3xl font-bold">128</p>
                        <p class="mt-1 text-sm text-slate-500">22 check-ins pending</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-5">
                        <p class="text-sm font-medium text-slate-500">Available Rooms</p>
                        <p class="mt-3 text-3xl font-bold">46</p>
                        <p class="mt-1 text-sm text-amber-700">12 need housekeeping</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-5">
                        <p class="text-sm font-medium text-slate-500">Revenue Today</p>
                        <p class="mt-3 text-3xl font-bold">$18,420</p>
                        <p class="mt-1 text-sm text-emerald-700">ADR $146</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
                    <section class="rounded-lg border border-slate-200 bg-white">
                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <h3 class="font-semibold">Recent Bookings</h3>
                            <button class="text-sm font-semibold text-slate-700">View all</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[720px] text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3">Guest</th>
                                        <th class="px-5 py-3">Property</th>
                                        <th class="px-5 py-3">Room</th>
                                        <th class="px-5 py-3">Dates</th>
                                        <th class="px-5 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr>
                                        <td class="px-5 py-4 font-medium">Anika Roy</td>
                                        <td class="px-5 py-4">City Center Hotel</td>
                                        <td class="px-5 py-4">Deluxe 402</td>
                                        <td class="px-5 py-4">Jul 7 - Jul 9</td>
                                        <td class="px-5 py-4"><span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Checked in</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-5 py-4 font-medium">Rohan Mehta</td>
                                        <td class="px-5 py-4">Airport Suites</td>
                                        <td class="px-5 py-4">Suite 1208</td>
                                        <td class="px-5 py-4">Jul 7 - Jul 8</td>
                                        <td class="px-5 py-4"><span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">Arriving</span></td>
                                    </tr>
                                    <tr>
                                        <td class="px-5 py-4 font-medium">Maya Sen</td>
                                        <td class="px-5 py-4">Beach Resort</td>
                                        <td class="px-5 py-4">Villa 03</td>
                                        <td class="px-5 py-4">Jul 8 - Jul 12</td>
                                        <td class="px-5 py-4"><span class="rounded-full bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700">Confirmed</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5">
                        <h3 class="font-semibold">Operations Queue</h3>
                        <div class="mt-4 space-y-3">
                            <div class="rounded-md border border-slate-200 p-4">
                                <p class="font-medium">Housekeeping</p>
                                <p class="mt-1 text-sm text-slate-500">12 rooms waiting, 4 high priority.</p>
                            </div>
                            <div class="rounded-md border border-slate-200 p-4">
                                <p class="font-medium">Maintenance</p>
                                <p class="mt-1 text-sm text-slate-500">3 open issues across 2 properties.</p>
                            </div>
                            <div class="rounded-md border border-slate-200 p-4">
                                <p class="font-medium">Payments</p>
                                <p class="mt-1 text-sm text-slate-500">7 deposits due before check-in.</p>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
