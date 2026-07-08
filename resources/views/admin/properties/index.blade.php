@extends('admin.layouts.app')

@section('title', 'Properties')
@section('eyebrow', 'Portfolio')
@section('page-title', 'Properties')

@section('header-actions')
    <a href="{{ route('admin.properties.create') }}" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">
        + Add Property
    </a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <!-- FILTERS -->
    <div class="mb-4 border border-slate-200 bg-white p-3">
        <form method="GET" action="{{ route('admin.properties.index') }}" class="grid gap-2 md:grid-cols-[1fr_1fr_auto]">
            <div>
                <label class="block text-xs font-bold text-slate-600">City</label>
                <input name="city" value="{{ request('city') }}" placeholder="Search..." class="mt-1 h-8 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600">Status</label>
                <select name="status" class="mt-1 h-8 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="">All</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white hover:bg-slate-800 transition">Filter</button>
                <a href="{{ route('admin.properties.index') }}" class="rounded bg-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-300 transition">Reset</a>
            </div>
        </form>
    </div>

    <!-- PROPERTIES TABLE -->
    <div class="border border-slate-200 bg-white">
        <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                    <th class="px-4 py-2">Property Name</th>
                    <th class="px-4 py-2">Location</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($properties as $property)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-bold text-slate-900">{{ $property->name }}</p>
                            <p class="text-xs text-slate-500">{{ $property->short_description ? substr($property->short_description, 0, 50) : 'No description' }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $property->city }}, {{ $property->state ?? '' }} {{ $property->country }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-600">{{ str_replace('_', ' ', $property->property_type) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $statusClass = [
                                    'active' => 'bg-emerald-100 text-emerald-800',
                                    'draft' => 'bg-amber-100 text-amber-800',
                                    'inactive' => 'bg-slate-100 text-slate-600',
                                ][$property->status] ?? 'bg-slate-100 text-slate-600';
                            @endphp
                            <span class="inline-block rounded px-2 py-1 text-xs font-bold {{ $statusClass }}">{{ ucfirst($property->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.properties.show', $property) }}" class="rounded bg-sky-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-sky-700 transition">View</a>
                                <a href="{{ route('admin.properties.edit', $property) }}" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800 transition">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center">
                            <p class="text-sm font-semibold text-slate-600">No properties found</p>
                            <a href="{{ route('admin.properties.create') }}" class="mt-3 inline-block rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white hover:bg-slate-800">Create First Property</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    @if (method_exists($properties, 'links'))
        <div class="mt-4">
            {{ $properties->links() }}
        </div>
    @endif
@endsection
