@php
    $isPercent = old('discount_type', $discount->discount_type) === \App\Models\Discount::TYPE_PERCENT;
    $applyMode = old('apply_mode', $discount->exists && ! $discount->isCoupon() ? 'automatic' : 'coupon');
@endphp

<div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
    <div class="space-y-4 border border-slate-200 bg-white p-5">
        <div>
            <label class="block text-xs font-bold text-slate-600">How does it apply? *</label>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-2 rounded border border-slate-300 p-3 text-sm">
                    <input type="radio" name="apply_mode" value="coupon" data-apply-mode @checked($applyMode === 'coupon')>
                    <span><strong>Guest enters a code</strong><br><span class="text-xs text-slate-500">A coupon like WELCOME10, typed in at booking.</span></span>
                </label>
                <label class="flex cursor-pointer items-start gap-2 rounded border border-slate-300 p-3 text-sm">
                    <input type="radio" name="apply_mode" value="automatic" data-apply-mode @checked($applyMode === 'automatic')>
                    <span><strong>Applies automatically</strong><br><span class="text-xs text-slate-500">An offer applied by itself when its conditions match.</span></span>
                </label>
            </div>
        </div>

        <div data-code-field @if ($applyMode !== 'coupon') hidden @endif>
            <label class="block text-xs font-bold text-slate-600">Coupon code *</label>
            <input name="code" value="{{ old('code', $discount->code) }}" placeholder="WELCOME10" maxlength="40"
                   class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 font-mono text-sm uppercase outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            <p class="mt-1 text-xs text-slate-500">Letters and numbers only. Guests type this on the booking page.</p>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600">Name *</label>
            <input name="name" value="{{ old('name', $discount->name) }}" placeholder="e.g. Monsoon Special" maxlength="120" required
                   class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            <p class="mt-1 text-xs text-slate-500">Shown to guests next to their savings.</p>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600">Description</label>
            <input name="description" value="{{ old('description', $discount->description) }}" maxlength="255"
                   class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-bold text-slate-600">Discount type *</label>
                <select name="discount_type" data-discount-type
                        class="mt-1 h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @foreach (\App\Models\Discount::typeLabels() as $value => $label)
                        <option value="{{ $value }}" @selected(old('discount_type', $discount->discount_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600"><span data-value-label>{{ $isPercent ? 'Percentage off *' : 'Amount off (₹) *' }}</span></label>
                <input name="discount_value" type="number" step="0.01" min="0.01"
                       value="{{ old('discount_value', $discount->exists ? number_format($discount->discount_value / 100, 2, '.', '') : '') }}"
                       class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900" required>
            </div>
            <div data-max-discount-field @unless ($isPercent) hidden @endunless>
                <label class="block text-xs font-bold text-slate-600">Max discount (₹)</label>
                <input name="max_discount" type="number" step="1" min="1"
                       value="{{ old('max_discount', $discount->max_discount_minor ? (int) ($discount->max_discount_minor / 100) : '') }}"
                       placeholder="No cap"
                       class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            </div>
        </div>
    </div>

    <div class="space-y-4 border border-slate-200 bg-white p-5">
        <h3 class="text-sm font-black text-slate-900">Conditions <span class="font-semibold text-slate-500">(all optional)</span></h3>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold text-slate-600">Property</label>
                <select name="property_id"
                        class="mt-1 h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="">All properties</option>
                    @foreach ($properties as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('property_id', $discount->property_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600">Room category</label>
                <select name="room_type_id"
                        class="mt-1 h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="">All categories</option>
                    @foreach ($roomTypes as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('room_type_id', $discount->room_type_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] font-semibold text-slate-400">Limit the discount to one room type, e.g. only Standard Double.</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-bold text-slate-600">Check-in from</label>
                <input name="valid_from" type="date" value="{{ old('valid_from', $discount->valid_from?->toDateString()) }}"
                       class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600">Check-in until</label>
                <input name="valid_until" type="date" value="{{ old('valid_until', $discount->valid_until?->toDateString()) }}"
                       class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600">Minimum nights</label>
            <input name="min_nights" type="number" min="1" max="365" value="{{ old('min_nights', $discount->min_nights) }}" placeholder="Any length of stay"
                   class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            <p class="mt-1 text-xs text-slate-500">Set 3 for a "stay 3 nights+" long-stay offer.</p>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600">Minimum booking amount (₹)</label>
            <input name="min_amount" type="number" min="1" value="{{ old('min_amount', $discount->min_amount_minor ? (int) ($discount->min_amount_minor / 100) : '') }}" placeholder="Any amount"
                   class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600">Maximum total uses</label>
            <input name="max_uses" type="number" min="1" value="{{ old('max_uses', $discount->max_uses) }}" placeholder="Unlimited"
                   class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            <p class="mt-1 text-xs text-slate-500">e.g. 10 = only the first 10 bookings get it.</p>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-apply-mode]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.querySelector('[data-code-field]').hidden = this.value !== 'coupon';
        });
    });

    document.querySelector('[data-discount-type]').addEventListener('change', function () {
        const isPercent = this.value === 'percent';
        document.querySelector('[data-value-label]').textContent = isPercent ? 'Percentage off *' : 'Amount off (₹) *';
        document.querySelector('[data-max-discount-field]').hidden = !isPercent;
    });
</script>
