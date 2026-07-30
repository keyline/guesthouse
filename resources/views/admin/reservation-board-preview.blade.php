@extends('admin.layouts.app')

@section('title', 'Reservation Board Preview')
@section('eyebrow', 'Front Desk · Preview')
@section('page-title', 'Reservation Board')

@section('content')
    @php
        $statusStyles = [
            \App\Models\Booking::STATUS_PENDING => ['Pending', '#f59e0b', '#fffbeb'],
            \App\Models\Booking::STATUS_CONFIRMED => ['Confirmed', '#2563eb', '#eff6ff'],
            \App\Models\Booking::STATUS_CHECKED_IN => ['In house', '#059669', '#ecfdf5'],
        ];
    @endphp

    <style>
        .rb-shell { --rb-label: 190px; --rb-days: 14; }
        .rb-panel { border: 1px solid #dbe4ef; border-radius: 14px; background: #fff; box-shadow: 0 10px 28px rgba(15,23,42,.05); }
        .rb-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; }
        .rb-summary-card { display:flex; align-items:center; gap:9px; min-height:54px; border:1px solid #e2e8f0; border-radius:10px; padding:8px 11px; background:#fff; }
        .rb-summary-icon { display:grid; width:30px; height:30px; place-items:center; border-radius:8px; background:#eff6ff; color:#2563eb; font-size:12px; font-weight:900; }
        .rb-board-scroll { overflow:auto; border-radius:0 0 14px 14px; }
        .rb-board { min-width:1300px; }
        .rb-grid { display:grid; grid-template-columns:var(--rb-label) repeat(14,minmax(82px,1fr)); }
        .rb-corner,.rb-day { position:sticky; top:0; z-index:12; min-height:48px; border-bottom:1px solid #dbe4ef; border-right:1px solid #edf2f7; background:#f8fafc; }
        .rb-corner { left:0; z-index:14; display:flex; align-items:center; padding:0 16px; font-size:11px; font-weight:900; letter-spacing:.08em; color:#64748b; text-transform:uppercase; }
        .rb-day { display:grid; place-content:center; text-align:center; color:#64748b; font-size:10px; font-weight:800; text-transform:uppercase; }
        .rb-day strong { margin-top:1px; color:#0f172a; font-size:15px; line-height:1; }
        .rb-day.is-today { background:#eff6ff; box-shadow:inset 0 3px 0 #2563eb; }
        .rb-type { display:grid; grid-template-columns:var(--rb-label) 1fr; min-height:29px; border-bottom:1px solid #e2e8f0; background:#f8fafc; }
        .rb-type-name { position:sticky; left:0; z-index:8; display:flex; align-items:center; gap:7px; padding:0 12px; background:#f8fafc; color:#334155; font-size:10px; font-weight:900; letter-spacing:.06em; text-transform:uppercase; }
        .rb-type-line { background:repeating-linear-gradient(90deg,transparent 0,transparent calc((100% / 14) - 1px),#edf2f7 calc((100% / 14) - 1px),#edf2f7 calc(100% / 14)); }
        .rb-room { display:grid; grid-template-columns:var(--rb-label) 1fr; min-height:46px; border-bottom:1px solid #edf2f7; }
        .rb-room:hover { background:#f8fbff; }
        .rb-room-label { position:sticky; left:0; z-index:7; display:flex; align-items:center; justify-content:space-between; gap:7px; border-right:1px solid #e2e8f0; background:#fff; padding:0 12px; }
        .rb-room:hover .rb-room-label { background:#f8fbff; }
        .rb-room-number { display:flex; align-items:center; gap:8px; color:#0f172a; font-size:11px; font-weight:850; }
        .rb-room-dot { width:7px; height:7px; border-radius:3px; background:#22c55e; box-shadow:0 0 0 2px #dcfce7; }
        .rb-room-floor { color:#94a3b8; font-size:9px; font-weight:700; }
        .rb-timeline { position:relative; display:grid; grid-template-columns:repeat(14,minmax(78px,1fr)); min-height:46px; background:repeating-linear-gradient(90deg,transparent 0,transparent calc((100% / 14) - 1px),#edf2f7 calc((100% / 14) - 1px),#edf2f7 calc(100% / 14)); }
        .rb-today-line { position:absolute; inset:0 auto 0 0; z-index:2; width:2px; background:#60a5fa; opacity:.45; pointer-events:none; }
        .rb-booking { position:absolute; top:7px; z-index:4; height:32px; overflow:hidden; border:0; border-left:3px solid var(--booking-color); border-radius:6px; background:var(--booking-bg); color:var(--booking-color); padding:5px 8px; text-align:left; font-family:inherit; cursor:pointer; text-decoration:none; box-shadow:0 2px 8px rgba(15,23,42,.07); transition:transform .15s ease,box-shadow .15s ease; }
        .rb-booking:hover { z-index:6; transform:translateY(-2px); box-shadow:0 8px 18px rgba(15,23,42,.16); }
        .rb-booking-name { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:11px; font-weight:900; }
        .rb-booking-meta { display:block; margin-top:1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:9px; font-weight:700; opacity:.72; }
        @media(max-width:900px){.rb-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
        .rb-modal { width:min(440px,calc(100vw - 32px)); border:0; border-radius:16px; padding:0; box-shadow:0 24px 64px rgba(15,23,42,.28); }
        .rb-modal::backdrop { background:rgba(15,23,42,.45); }
        .rb-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; border-bottom:1px solid #e2e8f0; padding:16px 18px; }
        .rb-modal-status { display:inline-flex; align-items:center; gap:6px; margin-top:6px; border-radius:999px; padding:3px 10px; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.05em; }
        .rb-modal-grid { display:grid; grid-template-columns:1fr 1fr; gap:1px; background:#eef2f7; }
        .rb-modal-cell { background:#fff; padding:10px 18px; }
        .rb-modal-cell.wide { grid-column:1 / -1; }
        .rb-modal-cell dt { font-size:9px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#94a3b8; }
        .rb-modal-cell dd { margin:2px 0 0; font-size:13px; font-weight:800; color:#0f172a; overflow-wrap:anywhere; }
        .rb-modal-foot { display:flex; flex-wrap:wrap; gap:8px; border-top:1px solid #e2e8f0; background:#f8fafc; padding:12px 18px; }
    </style>

    <div class="rb-shell space-y-2">
        <section class="rb-summary">
            @foreach ([
                ['↘', 'Arrivals today', $arrivals, 'Expected check-ins'],
                ['↗', 'Departures today', $departures, 'Expected check-outs'],
                ['●', 'Guests in house', $inHouse, 'Currently staying'],
                ['%', 'Occupancy', $occupancy.'%', 'Across active rooms'],
            ] as [$icon, $label, $value, $hint])
                <article class="rb-summary-card">
                    <span class="rb-summary-icon">{{ $icon }}</span>
                    <span><span class="block text-[9px] font-black uppercase tracking-wider text-slate-500">{{ $label }}</span><strong class="block text-lg font-black leading-tight text-slate-950">{{ $value }}</strong><span class="text-[9px] font-semibold text-slate-400">{{ $hint }}</span></span>
                </article>
            @endforeach
        </section>

        <section class="rb-panel overflow-hidden">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 p-2.5">
                <form method="GET" class="flex flex-1 flex-wrap items-center gap-2">
                    <label class="relative min-w-52 flex-1">
                        <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">⌕</span>
                        <input id="roomSearch" type="search" placeholder="Search room or guest…" class="h-9 w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-xs font-semibold outline-none focus:border-blue-400 focus:bg-white">
                    </label>
                    <input type="date" name="start" value="{{ $start->toDateString() }}" onchange="this.form.submit()" class="h-9 rounded-lg border border-slate-200 px-3 text-xs font-bold text-slate-700">
                </form>
                <div class="flex items-center rounded-lg border border-slate-200 bg-white">
                    <a href="{{ route('admin.reservation-board-preview', ['start' => $start->subDays(14)->toDateString()]) }}" class="grid h-9 w-9 place-items-center border-r border-slate-200 text-slate-600">‹</a>
                    <a href="{{ route('admin.reservation-board-preview') }}" class="grid h-9 place-items-center px-3 text-[10px] font-black uppercase tracking-wide text-slate-600">Today</a>
                    <a href="{{ route('admin.reservation-board-preview', ['start' => $start->addDays(14)->toDateString()]) }}" class="grid h-9 w-9 place-items-center border-l border-slate-200 text-slate-600">›</a>
                </div>
                <a href="{{ route('admin.bookings.create') }}" class="inline-flex h-9 items-center rounded-lg bg-blue-600 px-3 text-xs font-black text-white shadow-sm hover:bg-blue-700">+ New reservation</a>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50/70 px-4 py-2">
                <div><strong class="text-xs font-black text-slate-900">{{ $selectedProperty?->name ?? 'All Properties' }}</strong><span class="ml-2 text-[10px] font-semibold text-slate-500">{{ $start->format('d M') }} – {{ $end->format('d M Y') }}</span></div>
                <div class="flex flex-wrap gap-3">
                    @foreach ($statusStyles as [$label, $color, $bg])
                        <span class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500"><i class="h-2 w-2 rounded-full" style="background:{{ $color }}"></i>{{ $label }}</span>
                    @endforeach
                </div>
            </div>

            <div class="rb-board-scroll">
                <div class="rb-board">
                    <div class="rb-grid">
                        <div class="rb-corner">Rooms</div>
                        @foreach ($dates as $date)
                            <div class="rb-day {{ $date->isToday() ? 'is-today' : '' }}"><span>{{ $date->format('D') }}</span><strong>{{ $date->format('d') }}</strong><span>{{ $date->format('M') }}</span></div>
                        @endforeach
                    </div>

                    @forelse ($roomsByType as $typeName => $rooms)
                        <div class="rb-room-group" data-search-group>
                            <div class="rb-type"><div class="rb-type-name"><span>⌄</span>{{ $typeName }} <span class="ml-auto rounded-full bg-slate-200 px-2 py-0.5 text-[9px]">{{ $rooms->count() }}</span></div><div class="rb-type-line"></div></div>
                            @foreach ($rooms as $room)
                                <div class="rb-room" data-search-row="{{ strtolower($room->room_number.' '.$typeName.' '.$room->bookings->pluck('guest_name')->join(' ')) }}">
                                    <div class="rb-room-label"><span class="rb-room-number"><i class="rb-room-dot"></i>{{ $room->room_number }}</span><span class="rb-room-floor">{{ $room->floor ? 'Floor '.$room->floor : '' }}</span></div>
                                    <div class="rb-timeline">
                                        @if ($dates->contains(fn ($date) => $date->isToday()))
                                            @php $todayOffset = $start->diffInDays(\Carbon\CarbonImmutable::today()); @endphp
                                            <span class="rb-today-line" style="left:calc({{ $todayOffset }} * (100% / 14))"></span>
                                        @endif
                                        @foreach ($room->bookings as $booking)
                                            @php
                                                $offset = max(0, $start->diffInDays(\Carbon\CarbonImmutable::parse($booking->check_in_date), false));
                                                $bookingEnd = min(14, $start->diffInDays(\Carbon\CarbonImmutable::parse($booking->check_out_date), false));
                                                $span = max(1, $bookingEnd - $offset);
                                                [$statusLabel, $statusColor, $statusBg] = $statusStyles[$booking->status] ?? ['Reserved', '#475569', '#f1f5f9'];
                                            @endphp
                                            <button type="button" class="rb-booking" style="left:calc({{ $offset }} * (100% / 14) + 3px);width:calc({{ $span }} * (100% / 14) - 6px);--booking-color:{{ $statusColor }};--booking-bg:{{ $statusBg }}" title="{{ $booking->guest_name }} · {{ $booking->check_in_date->format('d M') }}–{{ $booking->check_out_date->format('d M') }} · {{ $statusLabel }}"
                                                data-booking="{{ json_encode([
                                                    'guest' => $booking->guest_name,
                                                    'number' => $booking->booking_number,
                                                    'statusLabel' => $statusLabel,
                                                    'statusColor' => $statusColor,
                                                    'statusBg' => $statusBg,
                                                    'room' => $room->room_number,
                                                    'roomType' => $typeName,
                                                    'checkIn' => $booking->check_in_date->format('D, d M Y'),
                                                    'checkOut' => $booking->check_out_date->format('D, d M Y'),
                                                    'nights' => $booking->nights,
                                                    'guests' => $booking->adults.' adult'.($booking->adults === 1 ? '' : 's').($booking->children ? ' · '.$booking->children.' child'.($booking->children === 1 ? '' : 'ren') : ''),
                                                    'total' => $booking->formattedTotal(),
                                                    'source' => ucwords(str_replace('_', ' ', $booking->source)),
                                                    'phone' => $booking->guest_phone,
                                                    'email' => $booking->guest_email,
                                                    'requests' => $booking->special_requests,
                                                    'stayUrl' => route('admin.bookings.stay', $booking),
                                                    'showUrl' => route('admin.bookings.show', $booking),
                                                ], JSON_UNESCAPED_SLASHES) }}">
                                                <span class="rb-booking-name">{{ $booking->guest_name }}</span><span class="rb-booking-meta">{{ $booking->booking_number }} · {{ $statusLabel }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="p-12 text-center"><h2 class="text-lg font-black">No rooms available</h2><p class="mt-1 text-sm text-slate-500">Select another property or add rooms to preview the reservation board.</p></div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <dialog id="bookingModal" class="rb-modal">
        <div class="rb-modal-head">
            <div>
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Reservation <span data-field="number"></span></p>
                <h2 class="mt-0.5 text-lg font-black text-slate-950" data-field="guest"></h2>
                <span class="rb-modal-status" data-field="statusPill"><i class="h-2 w-2 rounded-full" data-field="statusDot"></i><span data-field="statusLabel"></span></span>
            </div>
            <button type="button" data-modal-close class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100" aria-label="Close">✕</button>
        </div>
        <dl class="rb-modal-grid">
            <div class="rb-modal-cell"><dt>Room</dt><dd><span data-field="room"></span> <span class="text-[10px] font-bold text-slate-400" data-field="roomType"></span></dd></div>
            <div class="rb-modal-cell"><dt>Guests</dt><dd data-field="guests"></dd></div>
            <div class="rb-modal-cell"><dt>Check-in</dt><dd data-field="checkIn"></dd></div>
            <div class="rb-modal-cell"><dt>Check-out</dt><dd data-field="checkOut"></dd></div>
            <div class="rb-modal-cell"><dt>Nights</dt><dd data-field="nights"></dd></div>
            <div class="rb-modal-cell"><dt>Total price</dt><dd data-field="total"></dd></div>
            <div class="rb-modal-cell"><dt>Source</dt><dd data-field="source"></dd></div>
            <div class="rb-modal-cell"><dt>Phone</dt><dd data-field="phone"></dd></div>
            <div class="rb-modal-cell wide"><dt>Email</dt><dd data-field="email"></dd></div>
            <div class="rb-modal-cell wide" data-row="requests"><dt>Special requests</dt><dd data-field="requests"></dd></div>
        </dl>
        <div class="rb-modal-foot">
            <a data-field="stayUrl" href="#" class="inline-flex h-9 items-center rounded-lg bg-blue-600 px-3 text-xs font-black text-white hover:bg-blue-700">Check-in / stay screen</a>
            <a data-field="showUrl" href="#" class="inline-flex h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 hover:bg-slate-50">Full booking page</a>
            <button type="button" data-modal-close class="ml-auto inline-flex h-9 items-center rounded-lg px-3 text-xs font-black text-slate-500 hover:bg-slate-100">Close</button>
        </div>
    </dialog>

    <script>
        (() => {
            const modal = document.getElementById('bookingModal');
            const field = name => modal.querySelector(`[data-field="${name}"]`);

            document.querySelectorAll('.rb-booking[data-booking]').forEach(chip => chip.addEventListener('click', () => {
                const booking = JSON.parse(chip.dataset.booking);
                ['number', 'guest', 'statusLabel', 'room', 'roomType', 'guests', 'checkIn', 'checkOut', 'nights', 'total', 'source'].forEach(key => field(key).textContent = booking[key] ?? '—');
                field('phone').textContent = booking.phone || '—';
                field('email').textContent = booking.email || '—';
                field('requests').textContent = booking.requests || '';
                modal.querySelector('[data-row="requests"]').style.display = booking.requests ? '' : 'none';
                field('statusPill').style.background = booking.statusBg;
                field('statusPill').style.color = booking.statusColor;
                field('statusDot').style.background = booking.statusColor;
                field('stayUrl').href = booking.stayUrl;
                field('showUrl').href = booking.showUrl;
                modal.showModal();
            }));

            modal.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => modal.close()));
            modal.addEventListener('click', event => { if (event.target === modal) modal.close(); });
        })();

        document.getElementById('roomSearch')?.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            document.querySelectorAll('[data-search-group]').forEach(group => {
                let visible = 0;
                group.querySelectorAll('[data-search-row]').forEach(row => {
                    const matches = !query || row.dataset.searchRow.includes(query);
                    row.style.display = matches ? '' : 'none';
                    if (matches) visible++;
                });
                group.style.display = visible ? '' : 'none';
            });
        });
    </script>
@endsection
