@extends('admin.layouts.app')

@section('title', 'Cancellation Policies')
@section('eyebrow', 'Pricing & Policies')
@section('page-title', 'Cancellation Policies')

@section('header-actions')
    <a href="{{ route('admin.rate-plans.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Room Pricing</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ $errors->first() }}</div>
    @endif

    <p class="mb-5 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-bold text-sky-900">
        A policy is a few simple rules: "cancel before N hours of check-in → refund P%". Each booking freezes its
        policy at the moment it is made, so editing a policy here changes <strong>new bookings only</strong> — never
        the terms an existing guest already agreed to.
    </p>

    <section class="space-y-4">
        @foreach ($policies as $policy)
            <details class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <summary class="flex cursor-pointer flex-wrap items-center gap-3">
                    <span class="text-base font-black">{{ $policy->name }}</span>
                    <span class="rounded-full {{ $policy->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }} px-2.5 py-1 text-xs font-black">{{ $policy->is_active ? 'Active' : 'Inactive' }}</span>
                    <span class="text-xs font-bold text-slate-500">{{ $policy->shortLabel() }} · used by {{ $policy->rate_plans_count }} {{ Str::plural('rate plan', $policy->rate_plans_count) }}</span>
                    <form method="POST" action="{{ route('admin.cancellation-policies.toggle', $policy) }}" class="ml-auto">
                        @csrf
                        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">{{ $policy->is_active ? 'Deactivate' : 'Activate' }}</button>
                    </form>
                </summary>

                <div class="mt-4 grid gap-6 lg:grid-cols-2">
                    <form method="POST" action="{{ route('admin.cancellation-policies.update', $policy) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-slate-500">Name</label>
                            <input name="name" value="{{ $policy->name }}" required maxlength="80" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm font-bold">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-slate-500">Description (optional)</label>
                            <input name="description" value="{{ $policy->description }}" maxlength="255" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-black uppercase tracking-wide text-slate-500">Refund rules (leave all empty = non-refundable)</label>
                            @foreach (range(0, \App\Models\CancellationPolicy::MAX_TIERS - 1) as $row)
                                @php($tier = $policy->sortedTiers()[$row] ?? null)
                                <div class="mt-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                    <span>Until</span>
                                    <input type="number" name="tiers[{{ $row }}][hours_before]" value="{{ $tier['hours_before'] ?? '' }}" min="1" max="8760" class="h-9 w-20 rounded-lg border border-slate-300 px-2 text-sm font-bold">
                                    <span>hours before check-in →</span>
                                    <input type="number" name="tiers[{{ $row }}][refund_percent]" value="{{ $tier['refund_percent'] ?? '' }}" min="1" max="100" class="h-9 w-20 rounded-lg border border-slate-300 px-2 text-sm font-bold">
                                    <span>% refund</span>
                                </div>
                            @endforeach
                        </div>
                        <button class="h-10 rounded-lg bg-sky-600 px-4 text-sm font-bold text-white hover:bg-sky-700 transition">Save policy</button>
                    </form>

                    <div class="rounded-lg bg-slate-50 p-4">
                        <h3 class="text-xs font-black uppercase tracking-wide text-slate-500">What the guest reads</h3>
                        <ul class="mt-2 space-y-1 text-sm font-bold text-slate-700">
                            @foreach ($policy->describeLines() as $line)
                                <li>· {{ $line }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </details>
        @endforeach

        <details class="rounded-lg border border-dashed border-slate-300 bg-white p-5">
            <summary class="cursor-pointer text-sm font-black text-sky-700">+ New policy</summary>
            <form method="POST" action="{{ route('admin.cancellation-policies.store') }}" class="mt-4 max-w-xl space-y-3">
                @csrf
                <div>
                    <label class="text-xs font-black uppercase tracking-wide text-slate-500">Name</label>
                    <input name="name" value="{{ old('name') }}" required maxlength="80" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm font-bold" placeholder="e.g. Festive season">
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-wide text-slate-500">Description (optional)</label>
                    <input name="description" value="{{ old('description') }}" maxlength="255" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">
                </div>
                <div>
                    <label class="text-xs font-black uppercase tracking-wide text-slate-500">Refund rules (leave all empty = non-refundable)</label>
                    @foreach (range(0, \App\Models\CancellationPolicy::MAX_TIERS - 1) as $row)
                        <div class="mt-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                            <span>Until</span>
                            <input type="number" name="tiers[{{ $row }}][hours_before]" min="1" max="8760" class="h-9 w-20 rounded-lg border border-slate-300 px-2 text-sm font-bold">
                            <span>hours before check-in →</span>
                            <input type="number" name="tiers[{{ $row }}][refund_percent]" min="1" max="100" class="h-9 w-20 rounded-lg border border-slate-300 px-2 text-sm font-bold">
                            <span>% refund</span>
                        </div>
                    @endforeach
                </div>
                <button class="h-10 rounded-lg bg-sky-600 px-4 text-sm font-bold text-white hover:bg-sky-700 transition">Create policy</button>
            </form>
        </details>
    </section>
@endsection
