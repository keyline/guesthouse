<div class="grid gap-6 xl:grid-cols-[1.35fr_0.75fr]">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-black">Room Type Master Details</h2>
                <p class="mt-1 text-xs font-semibold text-slate-500">Global type reusable across all properties. Rooms under each property will select from this master.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-slate-600">Master data</span>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div>
                <label for="name" class="text-sm font-bold text-slate-700">Name</label>
                <input id="name" name="name" value="{{ old('name', $roomType->name) }}" required placeholder="Deluxe Double" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
                @error('name')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="code" class="text-sm font-bold text-slate-700">Code <span class="text-xs font-semibold text-slate-400">· generated automatically</span></label>
                <input id="code" value="{{ old('code', $roomType->code) }}" readonly tabindex="-1" placeholder="deluxe-double" class="mt-2 h-11 w-full cursor-default rounded-lg border border-slate-200 bg-slate-50 px-3 font-mono text-sm lowercase text-slate-600 outline-none">
                <p class="mt-1 text-[11px] font-semibold text-slate-400">Lowercase URL-safe identifier. Duplicate codes receive a number automatically.</p>
            </div>

            <div>
                <label for="status" class="text-sm font-bold text-slate-700">Status</label>
                <select id="status" name="status" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $roomType->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="sort_order" class="text-sm font-bold text-slate-700">Sort Order</label>
                <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $roomType->sort_order ?? 0) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>

            <div class="md:col-span-2">
                <label for="description" class="text-sm font-bold text-slate-700">Description</label>
                <textarea id="description" name="description" rows="4" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-950">{{ old('description', $roomType->description) }}</textarea>
            </div>
        </div>
    </section>

    <aside class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black">Capacity</h2>
            <p class="mt-1 text-xs font-semibold text-slate-500">Default capacity only. Property/room/day pricing will be handled separately.</p>
            <div class="mt-5 grid gap-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="max_adults" class="text-sm font-bold text-slate-700">Adults</label>
                        <input id="max_adults" name="max_adults" type="number" min="1" max="20" value="{{ old('max_adults', $roomType->max_adults ?? 2) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                    </div>
                    <div>
                        <label for="max_children" class="text-sm font-bold text-slate-700">Children</label>
                        <input id="max_children" name="max_children" type="number" min="0" max="20" value="{{ old('max_children', $roomType->max_children ?? 0) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                    </div>
                </div>
                <label class="group mt-4 flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50 p-3 transition hover:border-sky-300 hover:bg-sky-50/50">
                    <span class="flex min-w-0 items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-100 text-xl" aria-hidden="true">🐾</span>
                        <span class="min-w-0">
                            <span class="block text-sm font-black text-slate-800">Pet friendly</span>
                            <span class="mt-0.5 block text-[11px] font-semibold leading-4 text-slate-500">Guests may stay with permitted pets, subject to property policy.</span>
                        </span>
                    </span>
                    <input type="checkbox" name="is_pet_friendly" value="1" @checked(old('is_pet_friendly', $roomType->is_pet_friendly))
                           aria-label="This room category is pet friendly"
                           style="width:22px;height:22px;min-width:22px;accent-color:var(--admin-primary);cursor:pointer;">
                </label>

                <div class="mt-3 rounded-lg border border-slate-200 bg-white p-3" data-extra-bed-policy>
                    <label class="flex cursor-pointer items-center justify-between gap-4">
                        <span class="flex min-w-0 items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-100 text-xl" aria-hidden="true">🛏️</span><span><span class="block text-sm font-black text-slate-800">Extra bed available</span><span class="mt-0.5 block text-[11px] font-semibold leading-4 text-slate-500">Allow an additional rollaway bed or mattress for this category.</span></span></span>
                        <input type="checkbox" name="extra_bed_available" value="1" data-extra-bed-toggle @checked(old('extra_bed_available', $roomType->extra_bed_available)) aria-label="Extra bed is available" style="width:22px;height:22px;min-width:22px;accent-color:var(--admin-primary);cursor:pointer;">
                    </label>
                    <div class="mt-3 grid gap-3 border-t border-slate-100 pt-3" data-extra-bed-fields>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label for="max_extra_beds" class="text-[10px] font-black uppercase tracking-wide text-slate-500">Maximum per room</label><input id="max_extra_beds" name="max_extra_beds" type="number" min="1" max="5" value="{{ old('max_extra_beds', $roomType->max_extra_beds ?: 1) }}" class="mt-1 h-9 w-full rounded-lg border border-slate-300 px-3 text-sm"></div>
                            <div><label for="extra_bed_charge_basis" class="text-[10px] font-black uppercase tracking-wide text-slate-500">Charge basis</label><select id="extra_bed_charge_basis" name="extra_bed_charge_basis" class="mt-1 h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm"><option value="per_night" @selected(old('extra_bed_charge_basis', $roomType->extra_bed_charge_basis ?: 'per_night') === 'per_night')>Per night</option><option value="per_stay" @selected(old('extra_bed_charge_basis', $roomType->extra_bed_charge_basis) === 'per_stay')>Per stay</option></select></div>
                        </div>
                        <div><label for="extra_bed_charge" class="text-[10px] font-black uppercase tracking-wide text-slate-500">Charge per extra bed (₹)</label><div class="relative mt-1"><span class="absolute left-3 top-2 text-sm font-black text-slate-500">₹</span><input id="extra_bed_charge" name="extra_bed_charge" type="number" min="0" max="100000" step="0.01" value="{{ old('extra_bed_charge', number_format(($roomType->extra_bed_charge_minor ?? 0) / 100, 2, '.', '')) }}" class="h-9 w-full rounded-lg border border-slate-300 pl-8 pr-3 text-sm"><p class="mt-1 text-[10px] font-semibold text-slate-400">Enter 0 when the extra bed is complimentary.</p></div></div>
                        @error('max_extra_beds')<p class="text-xs font-bold text-rose-700">{{ $message }}</p>@enderror @error('extra_bed_charge')<p class="text-xs font-bold text-rose-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </section>
    </aside>
</div>

<script>
    (() => {
        const name = document.getElementById('name');
        const code = document.getElementById('code');
        if (!name || !code) return;
        const existing = new Set(@json(array_values($existingCodes ?? [])));
        const slug = value => value.toLowerCase().trim().normalize('NFKD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40) || 'room-type';
        const refresh = () => {
            const base = slug(name.value); let candidate = base; let number = 2;
            while (existing.has(candidate)) {
                const suffix = `-${number++}`;
                candidate = base.slice(0, 40 - suffix.length).replace(/-+$/g, '') + suffix;
            }
            code.value = candidate;
        };
        name.addEventListener('input', refresh);
        refresh();

        const extraBedToggle = document.querySelector('[data-extra-bed-toggle]');
        const extraBedFields = document.querySelector('[data-extra-bed-fields]');
        const refreshExtraBed = () => { if (extraBedFields) extraBedFields.hidden = !extraBedToggle?.checked; };
        extraBedToggle?.addEventListener('change', refreshExtraBed);
        refreshExtraBed();
    })();
</script>
