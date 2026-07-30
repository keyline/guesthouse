@extends('public.booking.layout')

@section('title', $banquet->property?->name.' — '.$banquet->name)

@section('content')
    @php
        $eventTypes = \App\Http\Controllers\Public\BanquetController::eventTypeLabels();
        $amenityIcons = \App\Support\AmenityIconLibrary::all();
        $images = $banquet->images;
        $primary = $images->firstWhere('is_primary', true) ?? $images->first();
        $heroUrl = $primary ? asset('storage/'.$primary->path) : asset('landing/images/formslider3.jpg');
        $location = collect([$banquet->property?->location, $banquet->property?->city])->filter()->unique()->join(', ');
        $setupLabels = ['banquet' => 'Banquet', 'theatre' => 'Theatre', 'cocktail' => 'Cocktail', 'classroom' => 'Classroom', 'u_shape' => 'U-Shape', 'round_table' => 'Round Table'];
        $settings = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::first() : null;
        $guests = $eventContext['guest_count'];
        $fits = $guests ? ($guests >= $banquet->capacity_min && $guests <= $banquet->capacity_max) : null;
    @endphp

    <a href="{{ url('/') }}" class="banquet-back">&larr; Back to home</a>

    <section class="banquet-hero" style="background-image: linear-gradient(180deg, rgba(20,16,8,.15), rgba(20,16,8,.72)), url('{{ $heroUrl }}');">
        <div class="banquet-hero__body">
            <span class="banquet-hero__eyebrow">Banquet &amp; Events</span>
            <h1>{{ $banquet->name }}</h1>
            <p class="banquet-hero__prop"><i class="fa-solid fa-location-dot"></i> {{ $banquet->property?->name }}{{ $location ? ' · '.$location : '' }}</p>
        </div>
    </section>

    {{-- Your event summary (carried from the search) --}}
    @if ($guests || $eventContext['event_type'] || $eventContext['event_date'])
        <section class="banquet-summary">
            <span class="banquet-summary__title">Your enquiry</span>
            <div class="banquet-summary__chips">
                @if ($eventContext['event_type'])<span><i class="fa-solid fa-champagne-glasses"></i> {{ $eventTypes[$eventContext['event_type']] ?? ucfirst($eventContext['event_type']) }}</span>@endif
                @if ($eventContext['event_date'])<span><i class="fa-regular fa-calendar"></i> {{ $eventContext['event_date'] }}</span>@endif
                @if ($eventContext['event_time'])<span><i class="fa-regular fa-clock"></i> {{ $eventContext['event_time'] }}</span>@endif
                @if ($guests)<span class="{{ $fits ? 'is-ok' : 'is-warn' }}"><i class="fa-solid fa-users"></i> {{ $guests }} guests {{ $fits ? '· fits this hall' : '· check capacity' }}</span>@endif
            </div>
        </section>
    @endif

    <div class="banquet-grid">
        {{-- Left: details --}}
        <div class="banquet-details">
            @if ($banquet->description)
                <div class="banquet-block">
                    <h2>About this venue</h2>
                    <p>{{ $banquet->description }}</p>
                </div>
            @endif

            <div class="banquet-block">
                <h2>At a glance</h2>
                <div class="banquet-facts">
                    <div class="banquet-fact"><i class="fa-solid fa-users"></i><div><strong>{{ $banquet->capacity_min }}–{{ $banquet->capacity_max }}</strong><span>Guest capacity</span></div></div>
                    <div class="banquet-fact"><i class="fa-solid fa-indian-rupee-sign"></i><div><strong>₹{{ number_format($banquet->base_price_minor / 100) }}</strong><span>Starting price</span></div></div>
                    <div class="banquet-fact"><i class="fa-solid fa-location-dot"></i><div><strong>{{ $banquet->property?->city ?: '—' }}</strong><span>{{ $banquet->property?->name }}</span></div></div>
                </div>
            </div>

            @if (! empty($banquet->setup_types))
                <div class="banquet-block">
                    <h2>Seating &amp; setup</h2>
                    <div class="banquet-chips">
                        @foreach ($banquet->setup_types as $setup)
                            <span class="banquet-chip">{{ $setupLabels[$setup] ?? \Illuminate\Support\Str::headline($setup) }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($banquet->amenitiesList->isNotEmpty())
                <div class="banquet-block">
                    <h2>Amenities</h2>
                    <ul class="banquet-amenities">
                        @foreach ($banquet->amenitiesList as $amenity)
                            @php($iconPath = $amenityIcons[$amenity->icon]['path'] ?? $amenityIcons['banquet']['path'])
                            <li><span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $iconPath }}"></path></svg></span>{{ $amenity->name }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($banquet->prices->isNotEmpty())
                <div class="banquet-block">
                    <h2>Per-plate pricing</h2>
                    <div class="banquet-price-list">
                        @foreach ($banquet->prices as $price)
                            <div class="banquet-price-row"><span>{{ \Illuminate\Support\Str::headline($price->season ?: 'Standard') }}</span><strong>₹{{ number_format($price->price_per_person) }} / plate</strong></div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($images->count() > 1)
                <div class="banquet-block">
                    <h2>Gallery</h2>
                    <div class="banquet-gallery">
                        @foreach ($images as $image)
                            <div class="banquet-gallery__item" style="background-image: url('{{ asset('storage/'.$image->path) }}');"></div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Right: sticky lead-generation card --}}
        <aside class="banquet-cta">
            <div class="banquet-cta__card">
                @if (session('lead_sent'))
                    <div class="banquet-thanks">
                        <span class="banquet-thanks__tick"><i class="fa-solid fa-check"></i></span>
                        <h3>Enquiry received!</h3>
                        <p>{{ session('lead_sent') }}</p>
                    </div>
                @else
                    <h3 class="banquet-cta__title">Get a callback</h3>
                    <p class="banquet-cta__sub">Share your details and our events team will call you with availability and a custom quote.</p>

                    <form method="POST" action="{{ route('banquet.enquiry', $banquet) }}" class="banquet-lead">
                        @csrf
                        <label class="banquet-lead__field">
                            <span>Full name *</span>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Your name">
                            @error('name')<em>{{ $message }}</em>@enderror
                        </label>
                        <label class="banquet-lead__field">
                            <span>Mobile number *</span>
                            <input type="tel" name="phone" value="{{ old('phone') }}" required inputmode="tel" placeholder="10-digit mobile">
                            @error('phone')<em>{{ $message }}</em>@enderror
                        </label>
                        <label class="banquet-lead__field">
                            <span>Email</span>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com">
                            @error('email')<em>{{ $message }}</em>@enderror
                        </label>
                        <div class="banquet-lead__row">
                            <label class="banquet-lead__field">
                                <span>Event date</span>
                                <input type="text" name="event_date" value="{{ old('event_date', $eventContext['event_date']) }}" placeholder="e.g. 12 Jun 2026">
                            </label>
                            <label class="banquet-lead__field">
                                <span>Guests</span>
                                <input type="number" name="guest_count" min="1" value="{{ old('guest_count', $guests) }}" placeholder="{{ $banquet->capacity_min }}+">
                            </label>
                        </div>
                        <label class="banquet-lead__field">
                            <span>Event type</span>
                            @php($selectedType = old('event_type', $eventContext['event_type']))
                            <select name="event_type">
                                <option value="">Select event type</option>
                                @foreach ($eventTypes as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="btn-reserve banquet-lead__submit">Request a callback</button>
                        <p class="banquet-cta__note">No spam — we only use your details to plan your event.</p>
                    </form>
                @endif
            </div>
        </aside>
    </div>

    <style>
        .banquet-back { display:inline-block; margin:6px 0 18px; color:#8a7a52; font-size:14px; font-weight:700; }
        .banquet-back:hover { color: var(--color-gold-dark); }
        .banquet-hero { position:relative; min-height:340px; display:flex; align-items:flex-end; border-radius:18px; overflow:hidden; background-size:cover; background-position:center; box-shadow:0 18px 44px rgba(28,28,28,.16); }
        .banquet-hero__body { padding:30px 34px; color:#fff; }
        .banquet-hero__eyebrow { display:inline-block; padding:5px 12px; border-radius:999px; background:var(--color-gold); color:var(--color-dark); font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .banquet-hero h1 { margin:12px 0 4px; font-size:40px; font-weight:800; color:#fff; line-height:1.05; }
        .banquet-hero__prop { margin:0; font-size:15px; font-weight:600; color:#f3ead6; }
        .banquet-hero__prop i { color:var(--color-gold); margin-right:5px; }
        .banquet-summary { margin-top:20px; padding:16px 20px; border:1px solid #e9e3d7; border-radius:14px; background:linear-gradient(180deg,#fff,#fbf8f1); }
        .banquet-summary__title { display:block; font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#9a9484; margin-bottom:10px; }
        .banquet-summary__chips { display:flex; flex-wrap:wrap; gap:9px; }
        .banquet-summary__chips span { display:inline-flex; align-items:center; gap:7px; padding:8px 14px; border-radius:999px; background:#fff; border:1px solid #e9e3d7; font-size:13px; font-weight:700; color:#5f5747; }
        .banquet-summary__chips i { color:var(--color-gold-dark); }
        .banquet-summary__chips .is-ok { border-color:#bfe6c9; background:#f1faf3; color:#1c7d3f; }
        .banquet-summary__chips .is-warn { border-color:#f2d9a8; background:#fff8ea; color:#9a7a2e; }
        .banquet-grid { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:28px; margin:28px 0 40px; align-items:start; }
        .banquet-block { padding:22px 24px; border:1px solid #e9e3d7; border-radius:14px; background:#fff; box-shadow:0 6px 20px rgba(28,28,28,.04); margin-bottom:18px; }
        .banquet-block h2 { margin:0 0 12px; font-size:19px; font-weight:800; color:var(--color-dark); }
        .banquet-block p { margin:0; color:#655c4c; font-size:15px; line-height:1.7; }
        .banquet-facts { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; }
        .banquet-fact { display:flex; align-items:center; gap:12px; }
        .banquet-fact i { display:grid; place-items:center; width:42px; height:42px; border-radius:12px; background:#fbf3e0; color:var(--color-gold-dark); font-size:16px; }
        .banquet-fact strong { display:block; font-size:17px; font-weight:800; color:var(--color-dark); }
        .banquet-fact span { font-size:12px; font-weight:600; color:#9a9484; }
        .banquet-chips, .banquet-amenities { display:flex; flex-wrap:wrap; gap:9px; margin:0; padding:0; list-style:none; }
        .banquet-chip { padding:8px 15px; border-radius:999px; background:#fbf3e0; color:#7a5807; font-size:13px; font-weight:800; }
        .banquet-amenities li { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border:1px solid #e9e3d7; border-radius:10px; font-size:13px; font-weight:700; color:#5f5747; }
        .banquet-amenities span { display:grid; place-items:center; width:18px; height:18px; color:var(--color-gold-dark); }
        .banquet-amenities svg { width:16px; height:16px; fill:currentColor; }
        .banquet-price-list { display:grid; gap:8px; }
        .banquet-price-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px dashed #ece5d6; font-size:14px; color:#5f5747; }
        .banquet-price-row:last-child { border-bottom:0; }
        .banquet-gallery { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:10px; }
        .banquet-gallery__item { height:120px; border-radius:10px; background-size:cover; background-position:center; }
        .banquet-cta__card { position:sticky; top:20px; padding:22px; border:1px solid #e9e3d7; border-radius:16px; background:linear-gradient(180deg,#fff,#fbf8f1); box-shadow:0 12px 34px rgba(28,28,28,.08); }
        .banquet-cta__head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding-bottom:16px; margin-bottom:16px; border-bottom:1px solid #efe8da; }
        .banquet-cta__from { margin:0; font-size:12px; font-weight:700; color:#9a9484; text-transform:uppercase; letter-spacing:.06em; }
        .banquet-cta__price { margin:2px 0 0; font-size:32px; font-weight:800; color:var(--color-dark); }
        .banquet-cta__cap-badge { display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:999px; background:#fbf3e0; color:#7a5807; font-size:13px; font-weight:800; white-space:nowrap; }
        .banquet-cta__title { margin:0; font-size:19px; font-weight:800; color:var(--color-dark); }
        .banquet-cta__sub { margin:4px 0 16px; font-size:13px; font-weight:600; color:#8a8272; line-height:1.5; }
        .banquet-lead { display:grid; gap:12px; }
        .banquet-lead__row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .banquet-lead__field { display:block; }
        .banquet-lead__field > span { display:block; margin-bottom:5px; font-size:11px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; color:#8a8272; }
        .banquet-lead__field input, .banquet-lead__field textarea, .banquet-lead__field select { width:100%; border:1px solid #ded8ca; border-radius:10px; background:#fff; padding:10px 12px; font-size:14px; font-weight:600; color:var(--color-dark); outline:none; transition:border-color .15s ease, box-shadow .15s ease; }
        .banquet-lead__field select { height:42px; cursor:pointer; }
        .banquet-lead__field input:focus, .banquet-lead__field textarea:focus, .banquet-lead__field select:focus { border-color:var(--color-gold); box-shadow:0 0 0 3px rgba(203,161,53,.15); }
        .banquet-lead__field textarea { resize:vertical; }
        .banquet-lead__field em { display:block; margin-top:4px; font-style:normal; font-size:11px; font-weight:700; color:#c0392b; }
        .banquet-lead__submit { width:100%; min-height:50px; margin-top:2px; font-size:15px; text-transform:uppercase; }
        .banquet-cta__note { margin:10px 0 0; font-size:11px; font-weight:600; color:#9a9484; text-align:center; line-height:1.5; }
        .banquet-thanks { text-align:center; padding:16px 4px 6px; }
        .banquet-thanks__tick { display:grid; place-items:center; width:52px; height:52px; margin:0 auto 12px; border-radius:50%; background:#eafaf0; color:#1c7d3f; font-size:22px; }
        .banquet-thanks h3 { margin:0; font-size:20px; font-weight:800; color:var(--color-dark); }
        .banquet-thanks p { margin:6px 0 0; font-size:14px; font-weight:600; color:#655c4c; line-height:1.5; }
        @media (max-width: 900px) { .banquet-grid { grid-template-columns:1fr; } .banquet-cta__card { position:static; } .banquet-hero h1 { font-size:30px; } }
    </style>
@endsection
