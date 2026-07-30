@extends('admin.layouts.app')

@section('title', 'Property Rules')
@section('eyebrow', 'Property Management')
@section('page-title', 'Rules · '.$property->name)

@section('header-actions')
    <a href="{{ route('admin.properties.edit', $property) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700">Back to Property</a>
@endsection

@section('content')
@php
    $saved = $draft->rules->keyBy('rule_key');
    $custom = $draft->rules->where('category', 'other')->pluck('guest_message')->join("\n");
    $configuredCount = collect($catalog)->keys()->filter(fn ($key) => filled(old("rules.$key.selection", $saved->get($key)?->selection)))->count();
    $formatMinutes = function (int $minutes): string {
        $hour = intdiv($minutes, 60);
        $minute = $minutes % 60;
        $suffix = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour % 12 ?: 12;
        return sprintf('%d:%02d %s', $displayHour, $minute, $suffix);
    };
@endphp

<style>
    .policy-category summary::-webkit-details-marker{display:none}.policy-category summary{list-style:none}.policy-category[open] .policy-chevron{transform:rotate(180deg)}
    .policy-anchor.is-active{border-color:#0284c7;background:#e0f2fe;color:#075985}
</style>

<div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
    <form method="POST" action="{{ route('admin.properties.rules.update', $property) }}" class="min-w-0 space-y-3" data-refresh-csrf data-csrf-refresh-url="{{ route('csrf-token.refresh') }}">
        @csrf @method('PUT')

        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-black text-slate-950">Property policy</h2>
                        <span class="rounded-full bg-sky-100 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-sky-800">{{ $property->name }} only</span>
                        <span class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-amber-800">Draft v{{ $draft->version }}</span>
                    </div>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Choose only rules that apply to {{ $property->name }}. Other properties are never changed.</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-black text-slate-800"><span data-configured-count>{{ $configuredCount }}</span> of {{ count($catalog) }} configured</p>
                    @if($published)
                        <p class="mt-1 text-[11px] font-bold text-emerald-700">Live v{{ $published->version }} · {{ $published->published_at?->format('d M Y, h:i A') }}</p>
                    @else
                        <p class="mt-1 text-[11px] font-bold text-amber-700">Not published yet</p>
                    @endif
                </div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="text-xs font-bold text-slate-600">Effective from <span class="font-semibold text-slate-400">(optional)</span><input type="date" name="effective_from" value="{{ old('effective_from', $draft->effective_from?->toDateString()) }}" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3"></label>
                <label class="text-xs font-bold text-slate-600">Effective until <span class="font-semibold text-slate-400">(optional)</span><input type="date" name="effective_until" value="{{ old('effective_until', $draft->effective_until?->toDateString()) }}" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3"></label>
            </div>
        </section>

        <nav class="sticky top-[72px] z-20 flex gap-2 overflow-x-auto rounded-xl border border-slate-200 bg-white/95 p-2 shadow-sm backdrop-blur" aria-label="Rule categories">
            @foreach($categories as $categoryKey => $categoryLabel)
                @php
                    $categoryRules = collect($catalog)->filter(fn ($rule) => $rule['category'] === $categoryKey);
                    $categoryConfigured = $categoryRules->keys()->filter(fn ($key) => filled(old("rules.$key.selection", $saved->get($key)?->selection)))->count();
                @endphp
                <button type="button" class="policy-anchor shrink-0 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-left text-[11px] font-black text-slate-600 transition" data-policy-anchor="policy-{{ $categoryKey }}">
                    {{ $categoryLabel }} <span class="ml-1 text-slate-400" data-category-count="{{ $categoryKey }}">{{ $categoryConfigured }}/{{ $categoryRules->count() }}</span>
                </button>
            @endforeach
            <button type="button" class="policy-anchor shrink-0 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-black text-slate-600" data-policy-anchor="policy-other">Other rules</button>
        </nav>

        @foreach($categories as $categoryKey => $categoryLabel)
            @php
                $categoryRules = collect($catalog)->filter(fn ($rule) => $rule['category'] === $categoryKey);
                $categoryConfigured = $categoryRules->keys()->filter(fn ($key) => filled(old("rules.$key.selection", $saved->get($key)?->selection)))->count();
            @endphp
            <details id="policy-{{ $categoryKey }}" class="policy-category scroll-mt-36 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" @if($categoryConfigured || $loop->first) open @endif>
                <summary class="flex cursor-pointer items-center justify-between gap-3 bg-slate-50 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-sky-100 text-sm font-black text-sky-700">{{ $loop->iteration }}</span>
                        <div><h3 class="text-sm font-black text-slate-900">{{ $categoryLabel }}</h3><p class="text-[11px] font-semibold text-slate-500">{{ $categoryConfigured }} of {{ $categoryRules->count() }} configured</p></div>
                    </div>
                    <svg class="policy-chevron h-4 w-4 text-slate-500 transition" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                </summary>
                <div class="divide-y divide-slate-100">
                    @foreach($categoryRules as $key => $definition)
                        @php $existing = $saved->get($key); $selection = old("rules.$key.selection", $existing?->selection); @endphp
                        <div class="grid gap-2 p-3 lg:grid-cols-[180px_210px_minmax(260px,1fr)_88px] lg:items-center" data-policy-row data-category="{{ $categoryKey }}">
                            <label class="text-xs font-black text-slate-800" for="rule-{{ $key }}">{{ $definition['label'] }}</label>
                            <select id="rule-{{ $key }}" name="rules[{{ $key }}][selection]" class="h-9 rounded-lg border border-slate-300 px-2 text-xs font-semibold" data-rule-select data-messages='@json($definition['messages'])'>
                                <option value="">Not applicable / not shown</option>
                                @foreach($definition['options'] as $value => $label)<option value="{{ $value }}" @selected($selection === $value)>{{ $label }}</option>@endforeach
                            </select>
                            <input name="rules[{{ $key }}][message]" value="{{ old("rules.$key.message", $existing?->guest_message ?? ($selection ? $definition['messages'][$selection] : '')) }}" maxlength="500" placeholder="Guest-facing wording" class="h-9 min-w-0 rounded-lg border border-slate-300 px-3 text-xs" data-rule-message>
                            <label class="flex items-center gap-2 text-[11px] font-bold text-slate-600" title="Highlight this rule before the guest books"><input type="hidden" name="rules[{{ $key }}][must_read]" value="0"><input type="checkbox" name="rules[{{ $key }}][must_read]" value="1" @checked(old("rules.$key.must_read", $existing?->is_must_read ?? $definition['must'])) class="h-4 w-4 rounded">Highlight</label>
                        </div>
                    @endforeach
                </div>
            </details>
        @endforeach

        <details id="policy-other" class="policy-category scroll-mt-36 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" @if($custom) open @endif>
            <summary class="flex cursor-pointer items-center justify-between gap-3 bg-slate-50 px-4 py-3">
                <div><h3 class="text-sm font-black text-slate-900">Other rules</h3><p class="text-[11px] font-semibold text-slate-500">Add property-specific wording not covered above</p></div>
                <svg class="policy-chevron h-4 w-4 text-slate-500 transition" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
            </summary>
            <div class="p-4"><label class="text-xs font-black text-slate-700">One guest-facing rule per line<textarea name="custom_rules" rows="4" maxlength="3000" class="mt-2 w-full rounded-lg border border-slate-300 p-3 text-sm" placeholder="Quiet hours are 10 PM to 7 AM.\nOutside visitors must register at reception.">{{ old('custom_rules', $custom) }}</textarea></label></div>
        </details>

        <div class="sticky bottom-3 z-20 flex items-center justify-between rounded-xl border border-slate-200 bg-white/95 p-3 shadow-xl backdrop-blur">
            <p class="hidden text-xs font-semibold text-slate-500 sm:block">Save the draft first, then review and publish.</p>
            <button class="rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-black text-white">Save Draft</button>
        </div>
    </form>

    <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3"><div><p class="text-[10px] font-black uppercase tracking-widest text-indigo-600">Guest preview</p><h2 class="mt-1 text-lg font-black">Property rules</h2></div><span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-600">{{ $draft->rules->count() }} rules</span></div>
            <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">Check-in {{ $formatMinutes($property->check_in_time_minutes) }} · Check-out {{ $formatMinutes($property->check_out_time_minutes) }}</p>
            <div class="mt-4 max-h-[52vh] space-y-4 overflow-y-auto pr-1">
                @forelse($draft->rules->groupBy('category') as $category => $rules)
                    <div><h3 class="text-[10px] font-black uppercase tracking-wide text-slate-500">{{ $categories[$category] ?? 'Other rules' }}</h3><ul class="mt-1.5 list-disc space-y-1 pl-4 text-xs font-semibold leading-5 text-slate-700">@foreach($rules as $rule)<li>{{ $rule->guest_message }}</li>@endforeach</ul></div>
                @empty <p class="rounded-lg bg-amber-50 p-3 text-xs font-bold text-amber-800">Select rules and save the draft to build this preview.</p> @endforelse
            </div>
        </section>
        <form method="POST" action="{{ route('admin.properties.rules.publish', $property) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4" onsubmit="return confirm('Publish this version to guests? The current live version will be archived.')">
            @csrf
            <h3 class="font-black text-emerald-950">Publish reviewed draft</h3>
            <p class="mt-1 text-xs font-semibold leading-5 text-emerald-800">Guests see only published rules. Every booking keeps a snapshot, so later policy changes never rewrite old records.</p>
            <button class="mt-3 w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-black text-white">Publish Version {{ $draft->version }}</button>
        </form>
    </aside>
</div>

<script>
(() => {
    const policyForm = document.querySelector('[data-refresh-csrf]');
    const selects = [...document.querySelectorAll('[data-rule-select]')];
    const refreshCounts = () => {
        document.querySelector('[data-configured-count]').textContent = selects.filter(select => select.value).length;
        document.querySelectorAll('[data-category-count]').forEach(label => {
            const rows = [...document.querySelectorAll(`[data-policy-row][data-category="${label.dataset.categoryCount}"]`)];
            label.textContent = `${rows.filter(row => row.querySelector('[data-rule-select]').value).length}/${rows.length}`;
        });
    };

    selects.forEach(select => select.addEventListener('change', () => {
        const input = select.closest('[data-policy-row]').querySelector('[data-rule-message]');
        const messages = JSON.parse(select.dataset.messages || '{}');
        if (!input.value || Object.values(messages).includes(input.value)) input.value = messages[select.value] || '';
        refreshCounts();
    }));

    document.querySelectorAll('[data-policy-anchor]').forEach(button => button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.policyAnchor);
        if (!target) return;
        target.open = true;
        document.querySelectorAll('.policy-anchor').forEach(anchor => anchor.classList.toggle('is-active', anchor === button));
        target.scrollIntoView({behavior: 'smooth', block: 'start'});
    }));

    policyForm?.addEventListener('submit', async event => {
        if (policyForm.dataset.csrfReady === 'true') return;

        event.preventDefault();
        const submitButton = event.submitter;
        const originalText = submitButton?.textContent;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Securing session…';
        }

        try {
            const response = await fetch(policyForm.dataset.csrfRefreshUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            });
            const payload = await response.json();

            if (!response.ok || !payload.token) throw new Error('Unable to refresh the session token.');

            policyForm.querySelector('input[name="_token"]').value = payload.token;
            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', payload.token);
            policyForm.dataset.csrfReady = 'true';
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
            policyForm.requestSubmit(submitButton || undefined);
        } catch (error) {
            window.alert('Your session could not be refreshed. Reload this page, sign in again if asked, and then save.');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        }
    });
})();
</script>
@endsection
