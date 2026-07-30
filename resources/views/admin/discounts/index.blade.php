@extends('admin.layouts.app')

@section('title', 'Offers & Coupons')
@section('eyebrow', 'Marketing')
@section('page-title', 'Offers & Coupons')

@section('header-actions')
    <a href="{{ route('admin.discounts.create') }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">
        + New Offer / Coupon
    </a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-2 text-sm font-bold">
        @foreach (['' => 'All', 'coupon' => 'Coupons', 'automatic' => 'Automatic offers'] as $value => $label)
            <a href="{{ route('admin.discounts.index', array_filter(['kind' => $value])) }}"
               class="rounded px-3 py-1.5 {{ request('kind', '') === $value ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-700 hover:bg-slate-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden border border-slate-200 bg-white">
        <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">How it applies</th>
                    <th class="px-4 py-3">Discount</th>
                    <th class="px-4 py-3">Conditions</th>
                    <th class="px-4 py-3 text-center">Used</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($discounts as $discount)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-black text-slate-900">{{ $discount->name }}</p>
                            <p class="text-xs font-semibold text-slate-500">{{ $discount->property?->name ?? 'All properties' }} · {{ $discount->roomType?->name ?? 'All categories' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            @if ($discount->isCoupon())
                                <span class="inline-flex rounded bg-amber-100 px-2 py-1 font-mono text-xs font-black text-amber-800">{{ $discount->code }}</span>
                            @else
                                <span class="inline-flex rounded bg-sky-100 px-2 py-1 text-xs font-black text-sky-800">Automatic</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-bold text-slate-700">
                            @if ($discount->discount_type === \App\Models\Discount::TYPE_PERCENT)
                                {{ rtrim(rtrim(number_format($discount->discount_value / 100, 2), '0'), '.') }}% off
                                @if ($discount->max_discount_minor)
                                    <span class="block text-xs font-semibold text-slate-500">up to ₹{{ number_format($discount->max_discount_minor / 100) }}</span>
                                @endif
                            @else
                                ₹{{ number_format($discount->discount_value / 100) }} off
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs font-semibold text-slate-600">
                            @if ($discount->valid_from || $discount->valid_until)
                                <p>Check-in {{ $discount->valid_from?->format('d M Y') ?? 'anytime' }} – {{ $discount->valid_until?->format('d M Y') ?? 'no end' }}</p>
                            @endif
                            @if ($discount->min_nights)
                                <p>Min {{ $discount->min_nights }} {{ Str::plural('night', $discount->min_nights) }}</p>
                            @endif
                            @if ($discount->min_amount_minor)
                                <p>Min booking ₹{{ number_format($discount->min_amount_minor / 100) }}</p>
                            @endif
                            @if (! $discount->valid_from && ! $discount->valid_until && ! $discount->min_nights && ! $discount->min_amount_minor)
                                <p class="text-slate-400">No conditions</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-bold text-slate-700">
                            {{ $discount->times_used }}{{ $discount->max_uses ? ' / '.$discount->max_uses : '' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-1 text-xs font-black {{ $discount->status === \App\Models\Discount::STATUS_ACTIVE ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ ucfirst($discount->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.discounts.edit', $discount) }}" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-slate-800">Edit</a>
                                <form method="POST" action="{{ route('admin.discounts.toggle', $discount) }}">
                                    @csrf
                                    <button class="rounded bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-300">
                                        {{ $discount->status === \App\Models\Discount::STATUS_ACTIVE ? 'Pause' : 'Activate' }}
                                    </button>
                                </form>
                                @if ($discount->times_used === 0)
                                    <form method="POST" action="{{ route('admin.discounts.destroy', $discount) }}" onsubmit="return confirm('Delete this {{ $discount->isCoupon() ? 'coupon' : 'offer' }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-rose-100 hover:text-rose-700">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center">
                            <p class="text-sm font-semibold text-slate-600">No offers or coupons yet.</p>
                            <p class="mt-1 text-xs text-slate-500">Create a coupon code guests can type in, or an automatic offer that applies by itself.</p>
                            <a href="{{ route('admin.discounts.create') }}" class="mt-3 inline-block rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white hover:bg-slate-800">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $discounts->links() }}
    </div>
@endsection
