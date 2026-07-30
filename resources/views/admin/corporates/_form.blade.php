<div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
    <div class="space-y-6">
        <div class="space-y-4 border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-black text-slate-900">Company details</h3>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-600">Legal name *</label>
                    <input name="legal_name" value="{{ old('legal_name', $corporate->legal_name) }}" required maxlength="255"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600">Trade name</label>
                    <input name="trade_name" value="{{ old('trade_name', $corporate->trade_name) }}" maxlength="255" placeholder="Shown to guests"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600">GSTIN *</label>
                    <input name="gstin" value="{{ old('gstin', $corporate->gstin) }}" required maxlength="15" placeholder="22AAAAA0000A1Z5"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 font-mono text-sm uppercase outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600">PAN</label>
                    <input name="pan" value="{{ old('pan', $corporate->pan) }}" maxlength="10" placeholder="AAAAA0000A"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 font-mono text-sm uppercase outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600">Contact person</label>
                    <input name="contact_name" value="{{ old('contact_name', $corporate->contact_name) }}" maxlength="255"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600">Billing email</label>
                    <input name="email" type="email" value="{{ old('email', $corporate->email) }}" maxlength="255"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600">Phone</label>
                    <input name="phone" value="{{ old('phone', $corporate->phone) }}" maxlength="40"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-600">Registered office address *</label>
                    <input name="address_line_1" value="{{ old('address_line_1', $corporate->address_line_1) }}" required maxlength="255"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <input name="address_line_2" value="{{ old('address_line_2', $corporate->address_line_2) }}" maxlength="255" placeholder="Line 2 (optional)"
                           class="mt-2 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600">City *</label>
                    <input name="city" value="{{ old('city', $corporate->city) }}" required maxlength="100"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600">State *</label>
                    <input name="state" value="{{ old('state', $corporate->state) }}" required maxlength="100"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600">PIN code *</label>
                    <input name="postal_code" value="{{ old('postal_code', $corporate->postal_code) }}" required maxlength="20"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600">Country</label>
                    <input name="country" value="{{ old('country', $corporate->country ?: 'India') }}" maxlength="100"
                           class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>
            </div>
        </div>

        <div class="space-y-4 border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-black text-slate-900">Negotiated room prices <span class="font-semibold text-slate-500">(per night, optional)</span></h3>
            <p class="text-xs text-slate-500">Set a fixed nightly price for the room types this company gets. Leave a row blank to remove it — those room types fall back to the blanket discount, or the normal rate. A negotiated price above the normal rate simply charges the normal rate.</p>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                        <th class="py-2">Room type</th>
                        <th class="py-2">Normal price / night</th>
                        <th class="py-2">Company price / night (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($roomTypes as $row)
                        @php $existing = $rateCard[$row['roomType']->id] ?? null; @endphp
                        <tr>
                            <td class="py-2 font-bold text-slate-800">{{ $row['roomType']->name }}</td>
                            <td class="py-2 text-slate-500">{{ $row['reference_minor'] !== null ? '₹'.number_format($row['reference_minor'] / 100) : 'No rate set' }}</td>
                            <td class="py-2">
                                <input name="rates[{{ $row['roomType']->id }}]" type="number" step="1" min="1" placeholder="—"
                                       value="{{ old('rates.'.$row['roomType']->id, $existing !== null ? (int) ($existing / 100) : '') }}"
                                       class="h-9 w-36 rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-slate-500">No active room types yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4 border border-slate-200 bg-white p-5 self-start">
        <h3 class="text-sm font-black text-slate-900">Booking tie-up</h3>

        <div>
            <label class="block text-xs font-bold text-slate-600">Company booking code</label>
            <input name="booking_code" value="{{ old('booking_code', $corporate->booking_code) }}" maxlength="20" placeholder="e.g. ACME2026"
                   class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 font-mono text-sm uppercase outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            <p class="mt-1 text-xs text-slate-500">Employees type this on the booking page to get the company price. Leave blank to keep the tie-up offline (front desk only).</p>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600">Who pays for employee stays?</label>
            <div class="mt-1 space-y-2 text-sm">
                <label class="flex cursor-pointer items-start gap-2 rounded border border-slate-300 p-3">
                    <input type="radio" name="default_billing" value="{{ \App\Models\Booking::BILLING_GUEST }}"
                           @checked(old('default_billing', $corporate->default_billing ?? \App\Models\Booking::BILLING_GUEST) !== \App\Models\Booking::BILLING_CORPORATE)>
                    <span><strong>Guest pays</strong><br><span class="text-xs text-slate-500">The employee settles their own bill (online or at the property).</span></span>
                </label>
                <label class="flex cursor-pointer items-start gap-2 rounded border border-slate-300 p-3">
                    <input type="radio" name="default_billing" value="{{ \App\Models\Booking::BILLING_CORPORATE }}"
                           @checked(old('default_billing', $corporate->default_billing) === \App\Models\Booking::BILLING_CORPORATE)>
                    <span><strong>Bill to company</strong><br><span class="text-xs text-slate-500">Stays are invoiced to the company. Pre-selected for employees — they can still choose to pay themselves.</span></span>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600">Blanket discount</label>
            <div class="mt-1 grid grid-cols-2 gap-2">
                <select name="discount_type"
                        class="h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="">None</option>
                    @foreach (\App\Models\Discount::typeLabels() as $value => $label)
                        <option value="{{ $value }}" @selected(old('discount_type', $corporate->discount_type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input name="discount_value" type="number" step="0.01" min="0.01" placeholder="Value"
                       value="{{ old('discount_value', $corporate->discount_value ? number_format($corporate->discount_value / 100, 2, '.', '') : '') }}"
                       class="h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            </div>
            <p class="mt-1 text-xs text-slate-500">Applies to room types without a negotiated price. Percentage (e.g. 10) or fixed ₹ off per stay.</p>
        </div>
    </div>
</div>
