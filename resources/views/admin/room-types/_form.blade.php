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
                <label for="code" class="text-sm font-bold text-slate-700">Code</label>
                <input id="code" name="code" value="{{ old('code', $roomType->code) }}" placeholder="DELUXE-DOUBLE" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm uppercase outline-none focus:border-slate-950">
                @error('code')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
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
            </div>
        </section>
    </aside>
</div>
