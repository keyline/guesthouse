@extends('admin.layouts.app')

@section('title', 'Banquets')
@section('eyebrow', 'Inventory')
@section('page-title', 'Banquet Halls')

@section('header-actions')
    <a href="{{ route('admin.banquets.create') }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white shadow-sm hover:bg-sky-700 transition">+ Add Banquet</a>
@endsection

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex gap-3">
            <div class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm">
                <span class="inline-block h-3 w-3 rounded-full bg-green-500"></span> {{ $banquets->total() }} Total
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase text-slate-500">Banquet Name</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase text-slate-500">Property</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase text-slate-500">Capacity</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase text-slate-500">Base Price</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase text-slate-500">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-bold uppercase text-slate-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($banquets as $banquet)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 text-sm font-bold text-slate-900">{{ $banquet->name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $banquet->property->name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $banquet->capacity_min }}-{{ $banquet->capacity_max }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-slate-900">
                            {{ $banquet->currency }} {{ number_format($banquet->base_price_minor / 100, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @php
                                $statusClass = match($banquet->status) {
                                    'active' => 'bg-green-100 text-green-700',
                                    'draft' => 'bg-yellow-100 text-yellow-700',
                                    'inactive' => 'bg-red-100 text-red-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                                $statusLabel = match($banquet->status) {
                                    'active' => '✓ Active',
                                    'draft' => '⊙ Draft',
                                    'inactive' => '✕ Inactive',
                                    default => $banquet->status,
                                };
                            @endphp
                            <span class="inline-block rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.banquets.edit', $banquet) }}" class="inline-flex items-center gap-1 rounded bg-blue-50 px-3 py-1 text-xs font-bold text-blue-600 hover:bg-blue-100 transition">Edit</a>
                                <form method="POST" action="{{ route('admin.banquets.destroy', $banquet) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this banquet?')" class="inline-flex items-center gap-1 rounded bg-red-50 px-3 py-1 text-xs font-bold text-red-600 hover:bg-red-100 transition">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                            No banquets found. <a href="{{ route('admin.banquets.create') }}" class="font-bold text-sky-600 hover:underline">Create one now</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($banquets->hasPages())
        <div class="mt-6">
            {{ $banquets->links() }}
        </div>
    @endif
@endsection
