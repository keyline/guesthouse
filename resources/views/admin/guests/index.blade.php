@extends('admin.layouts.app')

@section('title', 'Guests')
@section('eyebrow', 'Guest CRM')
@section('page-title', 'Guests')

@section('header-actions')
    <a href="{{ route('admin.guests.create') }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition shadow-sm">Add Guest</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.guests.index') }}" class="grid gap-3 md:grid-cols-[1fr_1fr_auto]">
            <div>
                <label for="search" class="text-sm font-bold text-slate-700">Search</label>
                <input id="search" name="search" value="{{ request('search') }}" placeholder="Name, email, phone" class="mt-2 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <div>
                <label for="active" class="text-sm font-bold text-slate-700">Status</label>
                <select id="active" name="active" class="mt-2 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">All guests</option>
                    <option value="1" @selected(request('active') === '1')>Active</option>
                    <option value="0" @selected(request('active') === '0')>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="h-10 rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Filter</button>
                <a href="{{ route('admin.guests.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Reset</a>
            </div>
        </form>
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Guest</th>
                        <th class="px-5 py-3">Contact</th>
                        <th class="px-5 py-3">Nationality</th>
                        <th class="px-5 py-3">Bookings</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($guests as $guest)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-black">{{ $guest->name }}</td>
                            <td class="px-5 py-4">
                                <span class="block font-semibold text-slate-700">{{ $guest->email }}</span>
                                <span class="text-xs font-semibold text-slate-500">{{ $guest->phone ?: 'No phone' }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $guest->nationality ?: '-' }}</td>
                            <td class="px-5 py-4 font-bold">{{ $guest->bookings_count }}</td>
                            <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ $guest->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="px-5 py-4">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.guests.show', $guest) }}" class="font-bold text-slate-900">Open</a>
                                    <a href="{{ route('admin.guests.edit', $guest) }}" class="font-bold text-slate-600">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center">
                                <h2 class="text-xl font-black">No guests yet</h2>
                                <p class="mt-2 text-sm font-semibold text-slate-500">Registered customers and admin-created guests will appear here.</p>
                                <a href="{{ route('admin.guests.create') }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Add Guest</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $guests->links() }}</div>
@endsection
