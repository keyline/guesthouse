@php
    $totalAmount = old('total_amount', $booking->exists ? number_format($booking->total_amount_minor / 100, 2, '.', '') : '');
@endphp

<div class="grid gap-6 xl:grid-cols-[1.35fr_0.75fr]">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-black">Stay Details</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div>
                <label for="property_id" class="text-sm font-bold text-slate-700">Property</label>
                <select id="property_id" name="property_id" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">Select property</option>
                    @foreach ($properties as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('property_id', $booking->property_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('property_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="room_type_id" class="text-sm font-bold text-slate-700">Room type</label>
                <select id="room_type_id" name="room_type_id" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">Select room type</option>
                    @foreach ($roomTypes as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('room_type_id', $booking->room_type_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('room_type_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="room_id" class="text-sm font-bold text-slate-700">Room <span class="font-semibold text-slate-400">(optional — assign at check-in)</span></label>
                <select id="room_id" name="room_id" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">Assign at check-in</option>
                    @foreach ($rooms as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('room_id', $booking->room_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('room_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="rate_plan_id" class="text-sm font-bold text-slate-700">Rate plan</label>
                <select id="rate_plan_id" name="rate_plan_id" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">Manual price</option>
                    @foreach ($ratePlans as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('rate_plan_id', $booking->rate_plan_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('rate_plan_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="text-sm font-bold text-slate-700">Status</label>
                <select id="status" name="status" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $booking->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="check_in_date" class="text-sm font-bold text-slate-700">Check-in</label>
                <input id="check_in_date" name="check_in_date" type="date" value="{{ old('check_in_date', optional($booking->check_in_date)->toDateString() ?: $booking->check_in_date) }}" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                @error('check_in_date')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="check_out_date" class="text-sm font-bold text-slate-700">Check-out</label>
                <input id="check_out_date" name="check_out_date" type="date" value="{{ old('check_out_date', optional($booking->check_out_date)->toDateString() ?: $booking->check_out_date) }}" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                @error('check_out_date')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="adults" class="text-sm font-bold text-slate-700">Adults</label>
                    <input id="adults" name="adults" type="number" min="1" max="20" value="{{ old('adults', $booking->adults ?? 1) }}" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                </div>
                <div>
                    <label for="children" class="text-sm font-bold text-slate-700">Children</label>
                    <input id="children" name="children" type="number" min="0" max="20" value="{{ old('children', $booking->children ?? 0) }}" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                </div>
            </div>

            <div>
                <label for="source" class="text-sm font-bold text-slate-700">Source</label>
                <select id="source" name="source" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    @foreach ($sources as $value => $label)
                        <option value="{{ $value }}" @selected(old('source', $booking->source) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="user_id" class="text-sm font-bold text-slate-700">Linked guest account</label>
                <select id="user_id" name="user_id" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">No linked account</option>
                    @foreach ($guests as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('user_id', $booking->user_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('user_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    <aside class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black">Guest</h2>
            <div class="mt-5 space-y-4">
                <div>
                    <label for="guest_name" class="text-sm font-bold text-slate-700">Guest name</label>
                    <input id="guest_name" name="guest_name" value="{{ old('guest_name', $booking->guest_name) }}" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                    @error('guest_name')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="guest_email" class="text-sm font-bold text-slate-700">Email</label>
                    <input id="guest_email" name="guest_email" type="email" value="{{ old('guest_email', $booking->guest_email) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                </div>
                <div>
                    <label for="guest_phone" class="text-sm font-bold text-slate-700">Phone</label>
                    <input id="guest_phone" name="guest_phone" value="{{ old('guest_phone', $booking->guest_phone) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black">Amount</h2>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <div>
                    <label for="total_amount" class="text-sm font-bold text-slate-700">Total</label>
                    <input id="total_amount" name="total_amount" type="number" min="0" step="0.01" value="{{ $totalAmount }}" placeholder="Auto from rate plan" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                    @error('total_amount')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                    <p class="mt-1 text-[11px] font-semibold text-slate-500">Leave blank to price from the selected rate plan.</p>
                </div>
                <div>
                    <label for="currency" class="text-sm font-bold text-slate-700">Currency</label>
                    <input id="currency" name="currency" maxlength="3" value="{{ old('currency', $booking->currency ?: 'INR') }}" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm uppercase">
                </div>
            </div>
        </section>
    </aside>
</div>

<section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-black">Notes</h2>
    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <div>
            <label for="special_requests" class="text-sm font-bold text-slate-700">Special requests</label>
            <textarea id="special_requests" name="special_requests" rows="4" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('special_requests', $booking->special_requests) }}</textarea>
        </div>
        <div>
            <label for="internal_notes" class="text-sm font-bold text-slate-700">Internal notes</label>
            <textarea id="internal_notes" name="internal_notes" rows="4" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('internal_notes', $booking->internal_notes) }}</textarea>
        </div>
    </div>
</section>
