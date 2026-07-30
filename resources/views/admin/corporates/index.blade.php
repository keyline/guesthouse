@extends('admin.layouts.app')

@section('title', 'Companies')
@section('eyebrow', 'People')
@section('page-title', 'Companies (Corporate Tie-ups)')

@section('header-actions')
    <a href="{{ route('admin.corporates.create') }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
        + Add Company
    </a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="overflow-hidden border border-slate-200 bg-white">
        <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Booking code</th>
                    <th class="px-4 py-3">Rate agreement</th>
                    <th class="px-4 py-3 text-center">Bookings</th>
                    <th class="px-4 py-3 text-right">To be billed</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($corporates as $corporate)
                    @php $due = $unbilled[$corporate->id] ?? null; @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.corporates.show', $corporate) }}" class="font-black text-slate-900 hover:underline">{{ $corporate->displayName() }}</a>
                            <p class="text-xs font-semibold text-slate-500">GSTIN {{ $corporate->gstin }}</p>
                        </td>
                        <td class="px-4 py-3">
                            @if ($corporate->booking_code)
                                <span class="inline-flex rounded bg-amber-100 px-2 py-1 font-mono text-xs font-black text-amber-800">{{ $corporate->booking_code }}</span>
                            @else
                                <span class="text-xs font-semibold text-slate-400">Front desk only</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs font-semibold text-slate-600">
                            @if ($corporate->discount_type)
                                <p>{{ $corporate->discount_type === \App\Models\Discount::TYPE_PERCENT
                                    ? rtrim(rtrim(number_format($corporate->discount_value / 100, 2), '0'), '.').'% off'
                                    : '₹'.number_format($corporate->discount_value / 100).' off' }}</p>
                            @endif
                            <p class="text-slate-400">Negotiated prices on company page</p>
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-bold text-slate-700">{{ $corporate->bookings_count }}</td>
                        <td class="px-4 py-3 text-right text-sm font-bold {{ $due ? 'text-amber-700' : 'text-slate-400' }}">
                            {{ $due ? '₹'.number_format($due->due_minor / 100, 2).' ('.$due->stays.')' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-1 text-xs font-black {{ $corporate->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $corporate->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.corporates.edit', $corporate) }}" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-slate-800">Edit</a>
                                <form method="POST" action="{{ route('admin.corporates.toggle', $corporate) }}">
                                    @csrf
                                    <button class="rounded bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-300">
                                        {{ $corporate->is_active ? 'Pause' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center">
                            <p class="text-sm font-semibold text-slate-600">No company tie-ups yet.</p>
                            <p class="mt-1 text-xs text-slate-500">Add a company with a negotiated price or discount — its employees book with the company code.</p>
                            <a href="{{ route('admin.corporates.create') }}" class="mt-3 inline-block rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white hover:bg-slate-800">Add Company</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $corporates->links() }}
    </div>
@endsection
