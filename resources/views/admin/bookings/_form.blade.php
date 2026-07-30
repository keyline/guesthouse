@php
    $totalAmount = old('total_amount', $booking->exists ? number_format($booking->total_amount_minor / 100, 2, '.', '') : '');
    $selectedPropertyId = (int) old('property_id', $booking->property_id);
    $selectedPropertyName = $properties[$selectedPropertyId] ?? null;
    $selectedRoomIds = collect(old('room_ids', $booking->room_id ? [$booking->room_id] : []))->map(fn ($id) => (int) $id)->all();
@endphp

<section class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-200 bg-white px-4 py-3 shadow-sm">
    <div class="flex flex-wrap items-center gap-4 text-xs"><span class="flex items-center gap-2 font-black text-blue-700"><b class="grid h-6 w-6 place-items-center rounded-full bg-blue-600 text-[10px] text-white">1</b> Find guest</span><span class="text-slate-300">›</span><span class="flex items-center gap-2 font-black text-slate-700"><b class="grid h-6 w-6 place-items-center rounded-full bg-slate-200 text-[10px]">2</b> Pick rooms</span><span class="text-slate-300">›</span><span class="flex items-center gap-2 font-black text-slate-700"><b class="grid h-6 w-6 place-items-center rounded-full bg-slate-200 text-[10px]">3</b> Confirm price</span></div>
    <button id="walkInDefaults" type="button" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[10px] font-black text-emerald-700" title="Sets source to Walk-in and dates to today for one night">⚡ Walk-in defaults</button>
</section>

<style>
    .room-card { position:relative; display:flex; min-width:118px; cursor:pointer; flex-direction:column; gap:1px; border:1.5px solid #e2e8f0; border-radius:10px; background:#fff; padding:8px 10px; transition:border-color .12s ease, box-shadow .12s ease; }
    .room-card:hover { border-color:#93c5fd; }
    .room-card:has(input:checked) { border-color:#2563eb; background:#eff6ff; box-shadow:0 0 0 1px #2563eb; }
    .room-card.is-occupied { cursor:not-allowed; border-style:dashed; background:#f8fafc; opacity:.65; }
    .room-card.is-occupied:hover { border-color:#e2e8f0; }
    .room-card-number { font-size:14px; font-weight:900; color:#0f172a; }
    .room-card-type { font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; }
    .room-card-floor { display:none; font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; }
    #roomBoard[data-grouping="category"] .room-card-type { display:none; }
    #roomBoard[data-grouping="category"] .room-card-floor { display:block; }
    .room-card-rate { font-size:10px; font-weight:800; color:#334155; }
    .room-card-status { position:absolute; right:8px; top:8px; display:inline-flex; align-items:center; gap:4px; font-size:8px; font-weight:900; text-transform:uppercase; letter-spacing:.06em; }
    .room-card-status i { width:6px; height:6px; border-radius:99px; }
    .room-card[data-available="1"] .room-card-status { color:#059669; }
    .room-card[data-available="1"] .room-card-status i { background:#10b981; }
    .room-card[data-available="0"] .room-card-status { color:#94a3b8; }
    .room-card[data-available="0"] .room-card-status i { background:#94a3b8; }
    .room-group-toggle { display:inline-flex; gap:2px; border:1px solid #cbd5e1; border-radius:7px; background:#f8fafc; padding:2px; }
    .room-group-toggle button { height:24px; border:0; border-radius:5px; background:transparent; padding:0 8px; color:#64748b; font-size:9px; font-weight:900; text-transform:uppercase; letter-spacing:.04em; cursor:pointer; }
    .room-group-toggle button.is-active { background:#fff; color:#0369a1; box-shadow:0 1px 3px rgba(15,23,42,.14); }
</style>

<div class="grid gap-3 xl:grid-cols-[1.45fr_0.65fr]">
    <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between"><div><h2 class="text-base font-black">Stay & room selection</h2><p class="text-[10px] font-semibold text-slate-500">Pick the dates, then tap one or more vacant rooms below.</p></div><span class="rounded bg-slate-100 px-2 py-1 text-[9px] font-black uppercase text-slate-500">Step 2</span></div>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="text-sm font-bold text-slate-700">Property</label>
                <input type="hidden" id="property_id" name="property_id" value="{{ $selectedPropertyId ?: '' }}">
                <div class="mt-2 flex h-11 w-full items-center rounded-lg border px-3 text-sm font-bold {{ $selectedPropertyName ? 'border-slate-300 bg-slate-50 text-slate-800' : 'border-amber-300 bg-amber-50 text-amber-800' }}">
                    {{ $selectedPropertyName ?: 'Select a property from the top panel first' }}
                </div>
                @error('property_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
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

            <div class="md:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="text-sm font-bold text-slate-700">Rooms <span class="cursor-help text-slate-400" title="Tap a card to select the room. Pick several cards for a family or group.">ⓘ</span></label>
                        <span class="room-group-toggle" role="group" aria-label="Arrange rooms">
                            <button type="button" class="is-active" data-room-grouping="floor" aria-pressed="true" title="Group physical rooms by floor">By floor</button>
                            <button type="button" data-room-grouping="category" aria-pressed="false" title="Group physical rooms by room category">By category</button>
                        </span>
                    </div>
                    <span class="flex items-center gap-3 text-[10px] font-bold text-slate-500"><span class="flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-emerald-500"></i>Vacant</span><span class="flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-slate-400"></i>Occupied</span></span>
                </div>
                @error('room_ids')<p class="mt-1 text-xs font-semibold text-rose-700">{{ $message }}</p>@enderror
                @error('room_ids.*')<p class="mt-1 text-xs font-semibold text-rose-700">{{ $message }}</p>@enderror
                <div id="roomBoard" class="mt-2 space-y-3"
                     data-status-url="{{ route('admin.bookings.room-board-status') }}"
                     data-booking-id="{{ $booking->exists ? $booking->id : '' }}">
                    @forelse ($roomBoard as $floorLabel => $cards)
                        <div data-room-group>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $floorLabel }}</p>
                            <div class="mt-1.5 flex flex-wrap gap-2">
                                @foreach ($cards as $card)
                                    <label class="room-card {{ $card['available'] ? '' : 'is-occupied' }}" data-room-card="{{ $card['id'] }}" data-floor="{{ $card['floor'] ?: 'Other rooms' }}" data-category="{{ $card['type'] }}" data-room-number="{{ $card['room_number'] }}" data-available="{{ $card['available'] ? 1 : 0 }}" data-stay-total="{{ $card['stay_total_minor'] ?? '' }}">
                                        <input type="checkbox" name="room_ids[]" value="{{ $card['id'] }}" class="sr-only" @checked(in_array($card['id'], $selectedRoomIds)) @disabled(! $card['available'])>
                                        <span class="room-card-number">{{ $card['room_number'] }}</span>
                                        <span class="room-card-type">{{ $card['type'] }}</span>
                                        <span class="room-card-floor">{{ $card['floor'] ?: 'Floor not assigned' }}</span>
                                        <span class="room-card-rate" data-rate-label>{{ $card['rate_label'] }}</span>
                                        <span class="room-card-status"><i></i><span data-status-label>{{ $card['available'] ? 'Vacant' : 'Occupied' }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-xs font-bold text-amber-800">No rooms found for this property. Add rooms first, or select another property from the top panel.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <label for="status" class="text-sm font-bold text-slate-700">Status</label>
                <select id="status" name="status" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $booking->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] font-semibold text-slate-500">Check-in and check-out are done from the booking&rsquo;s stay screen, after ID verification.</p>
            </div>

            <div>
                <label for="source" class="text-sm font-bold text-slate-700">Source</label>
                <select id="source" name="source" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    @foreach ($sources as $value => $label)
                        <option value="{{ $value }}" @selected(old('source', $booking->source) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
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
                <label for="user_id" class="text-sm font-bold text-slate-700">Linked guest account</label>
                <select id="user_id" name="user_id" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">No linked account</option>
                    @foreach ($guests as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('user_id', $booking->user_id) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
                @error('user_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="corporate_id" class="text-sm font-bold text-slate-700">Company (corporate rate)</label>
                <select id="corporate_id" name="corporate_id" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">No company — normal pricing</option>
                    @foreach ($corporates as $id => $company)
                        <option value="{{ $id }}" data-default-billing="{{ $company['default_billing'] }}" @selected((int) old('corporate_id', $booking->corporate_id) === $id)>{{ $company['name'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] font-semibold text-slate-500">Auto-priced bookings use the company&rsquo;s negotiated rate. A typed total always wins.</p>
                @error('corporate_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>

            <div id="billingChoice" @if (! old('corporate_id', $booking->corporate_id)) hidden @endif>
                <label class="text-sm font-bold text-slate-700">Billing</label>
                <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5">
                        <input type="radio" name="billing" value="{{ \App\Models\Booking::BILLING_GUEST }}" @checked(old('billing', $booking->billing ?? \App\Models\Booking::BILLING_GUEST) !== \App\Models\Booking::BILLING_CORPORATE)>
                        <span class="font-bold">Guest pays</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5">
                        <input type="radio" name="billing" value="{{ \App\Models\Booking::BILLING_CORPORATE }}" @checked(old('billing', $booking->billing) === \App\Models\Booking::BILLING_CORPORATE)>
                        <span class="font-bold">Bill to company</span>
                    </label>
                </div>
                @error('billing')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>

            @if ($booking->exists && $booking->payment_status !== \App\Models\Booking::PAYMENT_REFUNDED)
                <div>
                    <label for="payment_status" class="text-sm font-bold text-slate-700">Payment status</label>
                    <select id="payment_status" name="payment_status" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                        <option value="{{ \App\Models\Booking::PAYMENT_UNPAID }}" @selected(old('payment_status', $booking->payment_status) !== \App\Models\Booking::PAYMENT_PAID)>Unpaid</option>
                        <option value="{{ \App\Models\Booking::PAYMENT_PAID }}" @selected(old('payment_status', $booking->payment_status) === \App\Models\Booking::PAYMENT_PAID)>Paid / settled</option>
                    </select>
                    <p class="mt-1 text-[11px] font-semibold text-slate-500">Mark paid once the guest — or their company — settles the bill.</p>
                </div>
            @endif
        </div>
    </section>

    <aside class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between"><h2 class="text-base font-black">Guest</h2><span class="rounded bg-blue-50 px-2 py-1 text-[9px] font-black uppercase text-blue-600">Step 1</span></div>
            <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50/60 p-3">
                <span class="text-[9px] font-black uppercase tracking-wider text-blue-600">Returning guest lookup</span>
                <div class="mt-1.5 flex h-9 overflow-hidden rounded-md border border-slate-300 bg-white"><select id="bookingGuestCode" class="w-28 border-r border-slate-200 bg-slate-50 px-1 text-[10px] font-bold">@foreach(\App\Support\PhoneNumber::countryCodes() as $code=>$caption)<option value="{{ $code }}" @selected($code==='+91')>{{ $code }}</option>@endforeach</select><input id="bookingGuestMobile" inputmode="tel" placeholder="Mobile number" class="min-w-0 flex-1 px-2 text-xs font-bold outline-none"><button id="bookingGuestLookup" type="button" class="bg-blue-600 px-3 text-[10px] font-black text-white">Find</button></div>
                <p id="bookingGuestLookupResult" class="mt-1.5 text-[10px] font-semibold text-slate-500">Search before entering a new guest.</p>
            </div>
            <div class="mt-4 space-y-4">
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

        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between"><h2 class="text-base font-black">Price</h2><span class="rounded bg-slate-100 px-2 py-1 text-[9px] font-black uppercase text-slate-500">Step 3</span></div>
            <p id="roomSelectionSummary" class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-[11px] font-bold text-slate-600">No room selected yet.</p>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div>
                    <label for="total_amount" class="text-sm font-bold text-slate-700">Total price <span class="cursor-help text-slate-400" title="Fills in automatically from the selected rooms. For multiple rooms a typed total is split evenly across them.">ⓘ</span></label>
                    <input id="total_amount" name="total_amount" type="number" min="0" step="0.01" value="{{ $totalAmount }}" placeholder="Auto from rooms" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                    @error('total_amount')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                    <p class="mt-1 text-[11px] font-semibold text-slate-500">Fills in automatically from the selected rooms. You can adjust it before saving.</p>
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

<script>
    document.getElementById('corporate_id')?.addEventListener('change', function () {
        const billing = document.getElementById('billingChoice');
        if (!billing) return;
        billing.hidden = !this.value;
        if (!this.value) {
            billing.querySelector('input[value="guest"]').checked = true;
            return;
        }
        // Pre-select the company's usual billing; staff can still override.
        const preferred = this.options[this.selectedIndex]?.dataset.defaultBilling || 'guest';
        const radio = billing.querySelector('input[value="' + preferred + '"]');
        if (radio) radio.checked = true;
    });
</script>

<script>
    (() => {
        const board = document.getElementById('roomBoard');
        const checkIn = document.getElementById('check_in_date');
        const checkOut = document.getElementById('check_out_date');
        const totalInput = document.getElementById('total_amount');
        const summary = document.getElementById('roomSelectionSummary');
        const groupingButtons = [...document.querySelectorAll('[data-room-grouping]')];

        function renderGrouping(mode) {
            const cards = [...board.querySelectorAll('[data-room-card]')];
            if (!cards.length) return;

            const attribute = mode === 'category' ? 'category' : 'floor';
            board.dataset.grouping = mode;
            cards.sort((left, right) => {
                const groupOrder = left.dataset[attribute].localeCompare(right.dataset[attribute], undefined, {numeric:true});
                return groupOrder || left.dataset.roomNumber.localeCompare(right.dataset.roomNumber, undefined, {numeric:true});
            });

            const groups = new Map();
            cards.forEach(card => {
                const key = card.dataset[attribute] || (attribute === 'floor' ? 'Other rooms' : 'Other category');
                if (!groups.has(key)) groups.set(key, []);
                groups.get(key).push(card);
            });

            board.replaceChildren();
            groups.forEach((groupCards, key) => {
                const group = document.createElement('div');
                group.dataset.roomGroup = '';
                const heading = document.createElement('p');
                heading.className = 'text-[10px] font-black uppercase tracking-wider text-slate-400';
                heading.textContent = attribute === 'floor' && key !== 'Other rooms' ? `Floor ${key}` : key;
                const list = document.createElement('div');
                list.className = 'mt-1.5 flex flex-wrap gap-2';
                groupCards.forEach(card => list.appendChild(card));
                group.append(heading, list);
                board.appendChild(group);
            });

            groupingButtons.forEach(button => {
                const active = button.dataset.roomGrouping === mode;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', String(active));
            });
            try { sessionStorage.setItem('booking-room-grouping', mode); } catch (e) {}
        }

        groupingButtons.forEach(button => button.addEventListener('click', () => renderGrouping(button.dataset.roomGrouping)));
        let initialGrouping = 'floor';
        try { initialGrouping = sessionStorage.getItem('booking-room-grouping') || 'floor'; } catch (e) {}
        renderGrouping(initialGrouping === 'category' ? 'category' : 'floor');

        const nights = () => {
            const start = new Date(checkIn.value); const end = new Date(checkOut.value);
            const diff = Math.round((end - start) / 86400000);
            return Number.isFinite(diff) && diff > 0 ? diff : 0;
        };

        function recalcTotal() {
            const selected = [...board.querySelectorAll('input[name="room_ids[]"]:checked')];
            if (!selected.length) { summary.textContent = 'No room selected yet.'; return; }

            let sum = 0; let allPriced = true;
            selected.forEach(input => {
                const total = input.closest('[data-room-card]').dataset.stayTotal;
                if (total === '') allPriced = false; else sum += parseInt(total, 10);
            });

            const parts = [`${selected.length} room${selected.length > 1 ? 's' : ''}`, `${nights()} night${nights() > 1 ? 's' : ''}`];
            if (allPriced) {
                totalInput.value = (sum / 100).toFixed(2);
                parts.push(`auto price ${(sum / 100).toLocaleString('en-IN', {minimumFractionDigits: 2})}`);
            } else {
                parts.push('no rate set — enter the total price manually');
            }
            summary.textContent = parts.join(' · ');
        }

        async function refreshBoard() {
            const propertyId = document.getElementById('property_id')?.value;
            if (!propertyId || !checkIn.value || !checkOut.value || nights() < 1) return;

            const params = new URLSearchParams({
                property_id: propertyId, check_in: checkIn.value, check_out: checkOut.value,
            });
            if (board.dataset.bookingId) params.set('booking_id', board.dataset.bookingId);

            try {
                const response = await fetch(`${board.dataset.statusUrl}?${params}`, {headers: {'Accept': 'application/json'}});
                if (!response.ok) return;
                const data = await response.json();
                data.rooms.forEach(room => {
                    const card = board.querySelector(`[data-room-card="${room.id}"]`);
                    if (!card) return;
                    const input = card.querySelector('input');
                    card.dataset.available = room.available ? '1' : '0';
                    card.dataset.stayTotal = room.stay_total_minor ?? '';
                    card.classList.toggle('is-occupied', !room.available);
                    card.querySelector('[data-status-label]').textContent = room.available ? 'Vacant' : 'Occupied';
                    card.querySelector('[data-rate-label]').textContent = room.rate_label;
                    input.disabled = !room.available;
                    if (!room.available) input.checked = false;
                });
            } catch (e) { /* keep the server-rendered board on network errors */ }
            recalcTotal();
        }

        board.addEventListener('change', recalcTotal);
        checkIn.addEventListener('change', refreshBoard);
        checkOut.addEventListener('change', refreshBoard);
        recalcTotal();

        document.getElementById('walkInDefaults')?.addEventListener('click', () => {
            const today = new Date(); const tomorrow = new Date(today); tomorrow.setDate(today.getDate()+1);
            const local = date => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
            checkIn.value = local(today);
            checkOut.value = local(tomorrow);
            document.getElementById('source').value = 'walk_in';
            refreshBoard();
        });

        document.getElementById('bookingGuestLookup')?.addEventListener('click', async function () {
            const code = document.getElementById('bookingGuestCode').value;
            const mobile = document.getElementById('bookingGuestMobile').value;
            const result = document.getElementById('bookingGuestLookupResult');
            this.disabled = true; this.textContent = '…';
            try {
                const response = await fetch(`{{ route('admin.guests.lookup') }}?country_code=${encodeURIComponent(code)}&mobile=${encodeURIComponent(mobile)}`, {headers:{'Accept':'application/json'}});
                const data = await response.json();
                if (!response.ok) result.innerHTML = `<span class="text-rose-700">${Object.values(data.errors || {}).flat()[0] || 'Invalid number'}</span>`;
                else if (data.found) {
                    document.getElementById('user_id').value = data.guest.id;
                    document.getElementById('guest_name').value = data.guest.name;
                    document.getElementById('guest_email').value = data.guest.email || '';
                    document.getElementById('guest_phone').value = data.guest.phone;
                    result.innerHTML = `<span class="font-black text-emerald-700">✓ ${data.guest.name}</span> · ${data.guest.bookings_count} previous booking(s)`;
                } else {
                    document.getElementById('user_id').value = '';
                    document.getElementById('guest_phone').value = data.phone_e164;
                    result.innerHTML = '<span class="font-black text-blue-700">New guest</span> · Enter name and email below.';
                    document.getElementById('guest_name').focus();
                }
            } catch(e) { result.innerHTML = '<span class="text-rose-700">Lookup failed. Try again.</span>'; }
            finally { this.disabled = false; this.textContent = 'Find'; }
        });
    })();
</script>
