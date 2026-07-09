@extends('admin.layouts.app')

@section('title', 'Amenities')
@section('eyebrow', 'Master Data')
@section('page-title', 'Amenities')

@section('header-actions')
    <a href="{{ route('admin.amenities.create') }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
        + Add Amenity
    </a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 border border-slate-200 bg-white p-3">
        <form method="GET" action="{{ route('admin.amenities.index') }}" class="grid gap-2 md:grid-cols-[1fr_240px_auto]">
            <div>
                <label class="block text-xs font-bold text-slate-600">Search</label>
                <input name="search" value="{{ request('search') }}" placeholder="Amenity name..." class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600">Category</label>
                <select name="category" class="mt-1 h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="">All categories</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white transition hover:bg-slate-800">Filter</button>
                <a href="{{ route('admin.amenities.index') }}" class="rounded bg-slate-200 px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-300">Reset</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden border border-slate-200 bg-white">
        <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                    <th class="px-4 py-3">Amenity</th>
                    <th class="px-4 py-3">Icon</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($amenities as $amenity)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-black text-slate-900">{{ $amenity->name }}</p>
                            <p class="text-xs font-semibold text-slate-500">Sort {{ $amenity->sort_order }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm font-bold text-slate-700">
                            <div class="flex items-center gap-2">
                                <span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-slate-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="{{ $iconLibrary[$amenity->icon]['path'] ?? $iconLibrary['banquet']['path'] }}"></path>
                                    </svg>
                                </span>
                                <span>{{ $iconOptions[$amenity->icon] ?? $amenity->icon }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-600">{{ $categories[$amenity->category] ?? ucfirst($amenity->category) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-1 text-xs font-black {{ $amenity->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $amenity->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.amenities.edit', $amenity) }}" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-slate-800">Edit</a>
                                @if ($amenity->is_active)
                                    <form method="POST" action="{{ route('admin.amenities.destroy', $amenity) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-rose-100 hover:text-rose-700">Deactivate</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center">
                            <p class="text-sm font-semibold text-slate-600">No amenities found.</p>
                            <a href="{{ route('admin.amenities.create') }}" class="mt-3 inline-block rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white hover:bg-slate-800">Create Amenity</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $amenities->links() }}
    </div>
@endsection
