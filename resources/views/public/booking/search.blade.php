@extends('public.booking.layout')

@section('title', 'Book Your Stay')

@section('content')
    <h1 class="booking-title">Book your stay</h1>
    <p class="booking-subtitle">Live availability and instant reservation. Pay at the property.</p>

    @php
        $searchRooms = min(5, max(1, (int) request('search_rooms', 1)));
        $searchAdults = min(20, max(1, (int) request('adults', 2)));
        $searchChildren = min(20, max(0, (int) request('children', 0)));
    @endphp
    <form method="GET" action="{{ route('book.search') }}" class="booking-search" data-booking-search>
        <div class="booking-search__field booking-search__property">
            <label for="property_id">Property</label>
            <select id="property_id" name="property_id" required>
                <option value="">Select property</option>
                @foreach ($properties as $option)
                    <option value="{{ $option->id }}" @selected($property && $property->id === $option->id)>{{ $option->name }} — {{ $option->city }}</option>
                @endforeach
            </select>
        </div>
        <div class="booking-search__field">
            <label for="check_in">Check in</label>
            <input id="check_in" type="date" name="check_in" min="{{ now()->toDateString() }}" value="{{ $checkIn->toDateString() }}" required>
        </div>
        <div class="booking-search__field">
            <label for="check_out">Check out</label>
            <input id="check_out" type="date" name="check_out" min="{{ now()->addDay()->toDateString() }}" value="{{ $checkOut->toDateString() }}" required>
        </div>
        <div class="booking-search__field booking-guests" data-guest-picker>
            <label>Rooms &amp; guests</label>
            <button type="button" class="booking-guests__trigger" data-guest-trigger aria-expanded="false">
                <strong data-guest-summary>{{ $searchRooms }} {{ Str::plural('Room', $searchRooms) }}, {{ $searchAdults }} {{ Str::plural('Adult', $searchAdults) }}</strong>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>
            <input type="hidden" name="search_rooms" value="{{ $searchRooms }}" data-counter-input="rooms">
            <input type="hidden" name="adults" value="{{ $searchAdults }}" data-counter-input="adults">
            <input type="hidden" name="children" value="{{ $searchChildren }}" data-counter-input="children">

            <div class="booking-guests__popover" data-guest-popover hidden>
                <div class="booking-counter">
                    <div><strong>Rooms</strong><small>Maximum 5 per booking</small></div>
                    <div class="booking-counter__control">
                        <button type="button" data-counter-minus="rooms" aria-label="Remove a room">−</button>
                        <span data-counter-value="rooms">{{ $searchRooms }}</span>
                        <button type="button" data-counter-plus="rooms" aria-label="Add a room">+</button>
                    </div>
                </div>
                <div class="booking-counter">
                    <div><strong>Adults</strong><small>18 years and above</small></div>
                    <div class="booking-counter__control">
                        <button type="button" data-counter-minus="adults" aria-label="Remove an adult">−</button>
                        <span data-counter-value="adults">{{ $searchAdults }}</span>
                        <button type="button" data-counter-plus="adults" aria-label="Add an adult">+</button>
                    </div>
                </div>
                <div class="booking-counter">
                    <div><strong>Children</strong><small>0–17 years</small></div>
                    <div class="booking-counter__control">
                        <button type="button" data-counter-minus="children" aria-label="Remove a child">−</button>
                        <span data-counter-value="children">{{ $searchChildren }}</span>
                        <button type="button" data-counter-plus="children" aria-label="Add a child">+</button>
                    </div>
                </div>
                <button type="button" class="booking-guests__done" data-guest-done>Done</button>
            </div>
        </div>
        <div class="booking-search__submit">
            <button type="submit" class="btn-reserve">Search</button>
        </div>
    </form>

    @if ($property && $property->amenities->isNotEmpty())
        @php
            $propertyAmenityIcons = \App\Support\AmenityIconLibrary::all();
        @endphp
        <section class="property-facilities" aria-labelledby="propertyFacilitiesTitle">
            <div class="property-facilities__heading">
                <div>
                    <span>Included at {{ $property->name }}</span>
                    <h2 id="propertyFacilitiesTitle">Property facilities</h2>
                </div>
                <strong>{{ $property->amenities->count() }} {{ Str::plural('facility', $property->amenities->count()) }}</strong>
            </div>
            <ul class="property-facilities__list" aria-label="Facilities available at this property">
                @foreach ($property->amenities as $amenity)
                    @php
                        $propertyAmenityIcon = $propertyAmenityIcons[$amenity->icon]['path'] ?? $propertyAmenityIcons['banquet']['path'];
                    @endphp
                    <li title="{{ $amenity->name }}" tabindex="0">
                        <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $propertyAmenityIcon }}"></path></svg></span>
                        <small>{{ $amenity->name }}</small>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($publishedRuleSet?->rules->isNotEmpty())
        @php
            $publicRuleCategories = \App\Support\PropertyRuleCatalog::categories();
            $publicRuleGroups = $publishedRuleSet->rules->groupBy('category');
            $mustReadRules = $publishedRuleSet->rules->where('is_must_read', true)->take(3);
            if ($mustReadRules->isEmpty()) $mustReadRules = $publishedRuleSet->rules->take(2);
            $formatRuleTime = function (int $minutes): string {
                $hour = intdiv($minutes, 60);
                return sprintf('%d:%02d %s', $hour % 12 ?: 12, $minutes % 60, $hour >= 12 ? 'PM' : 'AM');
            };
        @endphp
        <section class="property-rules-card" aria-labelledby="propertyRulesTitle">
            <div class="property-rules-card__head">
                <div><span>Before you book</span><h2 id="propertyRulesTitle">Property rules</h2></div>
                <p><strong>Check-in</strong> {{ $formatRuleTime($property->check_in_time_minutes) }} <i></i> <strong>Check-out</strong> {{ $formatRuleTime($property->check_out_time_minutes) }}</p>
            </div>
            <ul>@foreach($mustReadRules as $rule)<li>{{ $rule->guest_message }}</li>@endforeach</ul>
            <div class="property-rules-card__actions">
                @foreach($publicRuleGroups->keys()->take(4) as $category)
                    <button type="button" class="property-rules-card__chip" data-property-rules-open data-rules-target="rules-{{ $category }}">{{ $publicRuleCategories[$category] ?? 'Other rules' }}</button>
                @endforeach
                <button type="button" class="property-rules-card__all" data-property-rules-open>Read all property rules <span aria-hidden="true">→</span></button>
            </div>
        </section>
        <dialog id="propertyRulesModal" class="property-rules-modal">
            <header><div><span>{{ $property->name }}</span><h2>House rules &amp; information</h2></div><button type="button" data-property-rules-close aria-label="Close">✕</button></header>
            <nav class="property-rules-modal__nav" aria-label="House rule categories">
                @foreach($publicRuleGroups as $category => $rules)
                    <button type="button" data-rules-jump="rules-{{ $category }}">{{ $publicRuleCategories[$category] ?? 'Other rules' }} <small>{{ $rules->count() }}</small></button>
                @endforeach
            </nav>
            <div class="property-rules-modal__content">
                <div class="property-rules-modal__times"><span><small>Check-in</small>{{ $formatRuleTime($property->check_in_time_minutes) }}</span><span><small>Check-out</small>{{ $formatRuleTime($property->check_out_time_minutes) }}</span></div>
                @foreach($publicRuleGroups as $category => $rules)
                    <section id="rules-{{ $category }}" data-rules-section><h3>{{ $publicRuleCategories[$category] ?? 'Other rules' }}</h3><ul>@foreach($rules as $rule)<li>{{ $rule->guest_message }}</li>@endforeach</ul></section>
                @endforeach
            </div>
        </dialog>
    @endif

    @if ($errors->any())
        <div class="booking-alert">{{ $errors->first() }}</div>
    @endif

    <dialog id="otpModal" class="otp-modal">
        <div class="otp-modal__head">
            <div>
                <h3>Sign in to book</h3>
                <p>Quick OTP login with your mobile number — no password needed.</p>
            </div>
            <button type="button" class="otp-modal__close" data-otp-close aria-label="Close">✕</button>
        </div>
        <div class="otp-modal__body">
            <div class="otp-step active" data-otp-step="phone">
                <label for="otpMobile">Mobile number</label>
                <div class="otp-phone-row">
                    <select id="otpCode">
                        @foreach (\App\Support\PhoneNumber::countryCodes() as $code => $caption)
                            <option value="{{ $code }}" @selected($code === '+91')>{{ $caption }}</option>
                        @endforeach
                    </select>
                    <input id="otpMobile" inputmode="tel" autocomplete="tel" placeholder="Mobile number">
                </div>
                <button type="button" class="btn-reserve" data-otp-send>Continue</button>
                <p class="otp-error" data-otp-error-phone hidden></p>
                <p class="otp-note">Prefer a password? <a href="{{ route('customer.login') }}">Sign in with email &amp; password</a></p>
            </div>

            <div class="otp-step" data-otp-step="verify">
                <p class="otp-welcome" data-otp-welcome hidden></p>
                <label for="otpInput">Enter the 6-digit code sent to <span data-otp-phone-label></span></label>
                <input id="otpInput" class="otp-code-input" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="••••••">
                <div data-otp-register hidden>
                    <div class="otp-field">
                        <label for="otpName">Your name *</label>
                        <input id="otpName" autocomplete="name" placeholder="Full name">
                    </div>
                    <div class="otp-field">
                        <label for="otpEmail">Email (optional — for your invoice)</label>
                        <input id="otpEmail" type="email" autocomplete="email" placeholder="you@example.com">
                    </div>
                </div>
                <p class="otp-dev-hint" data-otp-dev hidden></p>
                <button type="button" class="btn-reserve" data-otp-verify>Verify &amp; continue</button>
                <p class="otp-error" data-otp-error-verify hidden></p>
                <p class="otp-note"><button type="button" data-otp-resend disabled>Resend code</button> · <button type="button" data-otp-back>Change number</button></p>
            </div>
        </div>
    </dialog>

    @if ($searched)
        <div class="rooms-heading">
            <h2>Rooms</h2>
            <p>{{ $property->name }} · {{ $checkIn->format('d M') }} – {{ $checkOut->format('d M Y') }} · {{ $nights }} {{ Str::plural('night', $nights) }}</p>
        </div>

        <div class="coupon-box" style="margin:0 0 18px;padding:14px 16px;border:1px dashed #c9a227;border-radius:10px;background:#fffdf5;">
            @if ($corporate)
                <p style="margin:0;font-weight:600;color:#0a7d33;">
                    <i class="fa-solid fa-building"></i>
                    Corporate rate — <strong>{{ $corporate->displayName() }}</strong> applied. Company prices are shown below; you can also bill the stay to your company.
                    <a href="{{ route('book.search', ['property_id' => $property->id, 'check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString()]) }}"
                       style="margin-left:8px;font-weight:500;">Remove</a>
                </p>
            @elseif ($coupon)
                <p style="margin:0;font-weight:600;color:#0a7d33;">
                    <i class="fa-solid fa-tag"></i>
                    Coupon <strong>{{ $coupon->code }}</strong> applied — the discount is shown on eligible rooms below.
                    <a href="{{ route('book.search', ['property_id' => $property->id, 'check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString()]) }}"
                       style="margin-left:8px;font-weight:500;">Remove</a>
                </p>
            @else
                <form method="GET" action="{{ route('book.search') }}" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:0;">
                    <input type="hidden" name="property_id" value="{{ $property->id }}">
                    <input type="hidden" name="check_in" value="{{ $checkIn->toDateString() }}">
                    <input type="hidden" name="check_out" value="{{ $checkOut->toDateString() }}">
                    <label for="couponInput" style="font-weight:600;margin:0;">Coupon / company code</label>
                    <input id="couponInput" name="coupon" value="{{ $couponCode }}" placeholder="Enter code"
                           style="text-transform:uppercase;padding:8px 12px;border:1px solid #d5d5d5;border-radius:8px;max-width:180px;">
                    <button type="submit" class="btn-reserve" style="padding:8px 18px;">Apply</button>
                    @if ($couponError)
                        <span style="color:#c0392b;font-size:14px;">{{ $couponError }}</span>
                    @endif
                </form>
            @endif
        </div>

        @if ($results->isNotEmpty())
            <form id="reserveForm" method="POST" action="{{ route('book.store') }}">
                @csrf
                <input type="hidden" name="check_in" value="{{ $checkIn->toDateString() }}">
                <input type="hidden" name="check_out" value="{{ $checkOut->toDateString() }}">
                @if ($coupon || $corporate)
                    <input type="hidden" name="coupon_code" value="{{ $corporate?->booking_code ?? $coupon->code }}">
                @endif

                @foreach ($results as $result)
                    @foreach ($result['plans'] as $row)
                        <input type="hidden"
                               name="rooms[{{ $row['plan']->id }}]"
                               value="{{ (int) old('rooms.'.$row['plan']->id, 0) }}"
                               data-plan-input="{{ $row['plan']->id }}"
                               data-type="{{ $result['roomType']->id }}"
                               data-price="{{ $row['totalMinor'] }}"
                               data-tax="{{ $row['gst']['tax_minor'] }}"
                               data-label="{{ $result['roomType']->name }} · {{ $row['plan']->name }}">
                    @endforeach
                @endforeach

                <div class="rooms-list">
                        @foreach ($results as $index => $result)
                            @php
                                $roomType = $result['roomType'];
                                $galleryImages = $result['galleryImages'];
                                $soldOut = $result['sellable'] < 1;
                                $oldTypeQty = collect($result['plans'])->sum(fn ($row) => (int) old('rooms.'.$row['plan']->id, 0));
                                $selectedPlan = collect($result['plans'])->first(fn ($row) => (int) old('rooms.'.$row['plan']->id, 0) > 0) ?? $result['plans']->first();
                                $checkedPlanId = $selectedPlan['plan']->id ?? null;
                                $unavailableReason = $result['unavailableReason'] ?? 'Sold out for these dates';
                            @endphp
                            <section class="room-panel active {{ $soldOut ? 'room-panel--sold' : '' }}" data-type-panel="{{ $roomType->id }}" data-type="{{ $roomType->id }}" data-max="{{ min(5, $result['sellable']) }}">
                                <div class="room-panel__media">
                                    <div class="room-gallery" data-room-gallery aria-label="{{ $roomType->name }} photos">
                                        @if($soldOut)<div class="room-sold-stamp" aria-label="{{ $unavailableReason }}">{{ $result['plans']->isEmpty() ? 'Unavailable' : 'Sold out' }}</div>@endif
                                        @forelse ($galleryImages as $photoIndex => $image)
                                            <img src="{{ asset('storage/'.$image->path) }}"
                                                 alt="{{ $image->alt_text ?: $roomType->name.' room photo '.($photoIndex + 1) }}"
                                                 class="room-gallery__slide {{ $photoIndex === 0 ? 'active' : '' }}"
                                                 loading="{{ $photoIndex === 0 ? 'eager' : 'lazy' }}"
                                                 data-gallery-slide>
                                        @empty
                                            <div class="room-gallery__placeholder" role="img" aria-label="No room photo available">
                                                <i class="fa-regular fa-image" aria-hidden="true"></i>
                                                <span>Photo coming soon</span>
                                            </div>
                                        @endforelse

                                        @if ($galleryImages->count() > 1)
                                            <button type="button" class="room-gallery__arrow room-gallery__arrow--prev" data-gallery-prev aria-label="Previous photo">&#8249;</button>
                                            <button type="button" class="room-gallery__arrow room-gallery__arrow--next" data-gallery-next aria-label="Next photo">&#8250;</button>
                                            <div class="room-gallery__dots" aria-label="Choose a room photo">
                                                @foreach ($galleryImages as $photoIndex => $image)
                                                    <button type="button" class="{{ $photoIndex === 0 ? 'active' : '' }}" data-gallery-dot="{{ $photoIndex }}" aria-label="Show photo {{ $photoIndex + 1 }}"></button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="room-panel__facilities">
                                        @php($amenityIcons = \App\Support\AmenityIconLibrary::all())
                                        @if ($roomType->description)
                                            <button type="button" class="room-more-info" data-more-info>More Info</button>
                                            <p class="room-description" data-description>{{ $roomType->description }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="room-panel__body">
                                    <div class="room-panel__title">
                                        <h3>{{ $roomType->name }}</h3>
                                        <span class="room-badge">{{ $roomType->max_adults }}{{ $roomType->max_children ? ' + '.$roomType->max_children : '' }} guests</span>
                                        <span class="room-availability {{ $soldOut ? 'room-availability--sold' : ($result['sellable'] <= 2 ? 'room-availability--low' : 'room-availability--ok') }}">
                                            {{ $soldOut ? $unavailableReason : ($result['sellable'] <= 2 ? 'Only '.$result['sellable'].' left!' : $result['sellable'].' rooms available') }}
                                        </span>
                                    </div>

                                    @if ($roomType->amenities->isNotEmpty() || $roomType->extra_bed_available || $roomType->is_pet_friendly)
                                        <ul class="room-amenity-icons" aria-label="Room amenities">
                                            @foreach ($roomType->amenities as $amenity)
                                                @php($iconPath = $amenityIcons[$amenity->icon]['path'] ?? $amenityIcons['banquet']['path'])
                                                <li tabindex="0" data-tooltip="{{ $amenity->name }}{{ $amenity->pivot?->availability_mode === 'on_request' ? ' · Available on request' : '' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $iconPath }}"></path></svg></li>
                                            @endforeach
                                            @if($roomType->extra_bed_available)<li tabindex="0" data-tooltip="Up to {{ $roomType->max_extra_beds }} extra {{ Str::plural('bed',$roomType->max_extra_beds) }}{{ $roomType->extra_bed_charge_minor ? ' · ₹'.number_format($roomType->extra_bed_charge_minor / 100).' '.$roomType->extra_bed_charge_basis : ' · Complimentary' }}"><i class="fa-solid fa-bed" aria-hidden="true"></i></li>@endif
                                            @if($roomType->is_pet_friendly)<li tabindex="0" class="room-amenity-icons__emoji" data-tooltip="Pets permitted subject to property policy">🐾</li>@endif
                                        </ul>
                                    @endif

                                    <div class="room-panel__section">
                                        <h5>Sleeps:</h5>
                                        <p><i class="fa-solid fa-bed"></i> Up to {{ $roomType->max_adults }} {{ Str::plural('adult', $roomType->max_adults) }}{{ $roomType->max_children ? ' + '.$roomType->max_children.' '.Str::plural('child', $roomType->max_children) : '' }} per room</p>
                                        @if($roomType->extra_bed_available)<p class="mt-1"><i class="fa-solid fa-plus"></i> Up to {{ $roomType->max_extra_beds }} extra {{ Str::plural('bed', $roomType->max_extra_beds) }} · {{ $roomType->extra_bed_charge_minor ? '₹'.number_format($roomType->extra_bed_charge_minor / 100).' '.str_replace('_', ' ', $roomType->extra_bed_charge_basis) : 'Complimentary' }}</p>@endif
                                    </div>

                                    <div class="room-panel__section">
                                        <h5>Meals:</h5>
                                        <div class="meal-options">
                                            @foreach ($result['plans'] as $row)
                                                <label>
                                                    <input type="radio" name="meal-{{ $roomType->id }}" value="{{ $row['plan']->id }}"
                                                           data-plan-radio data-price="{{ $row['totalMinor'] }}"
                                                           @checked($row['plan']->id === $checkedPlanId)>
                                                    <span>{{ \App\Models\RatePlan::mealPlans()[$row['plan']->meal_plan] ?? strtoupper($row['plan']->meal_plan) }}
                                                        <span class="meal-note">· {{ $row['plan']->effectiveCancellationPolicy()?->shortLabel() ?? ($row['plan']->is_refundable ? 'Free cancellation' : 'Non-refundable') }}</span>
                                                    </span>
                                                    <span class="meal-price">
                                                        @if ($row['discountMinor'] > 0)
                                                            <s style="opacity:.55;font-weight:400;">₹{{ number_format($row['originalMinor'] / 100) }}</s>
                                                        @endif
                                                        ₹{{ number_format($row['totalMinor'] / 100) }} <span class="meal-note">+ {{ \App\Services\Payments\Gst::ratePercent($row['gst']['rate_bp']) }}% GST</span>
                                                        @if ($row['discountMinor'] > 0)
                                                            <span class="meal-note" style="display:block;color:#0a7d33;font-weight:600;">You save ₹{{ number_format($row['discountMinor'] / 100) }}{{ $row['discountName'] ? ' · '.$row['discountName'] : '' }}</span>
                                                        @endif
                                                    </span>
                                                </label>
                                            @endforeach
                                            @if($result['plans']->isEmpty())
                                                <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">Online pricing is not configured for this category yet. Please contact the property for availability.</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="room-panel__section">
                                        <h5>Payment</h5>
                                        @if ($corporate?->billsToCompany())
                                            <p class="room-payment-note"><i class="fa-regular fa-building"></i> Billed to {{ $corporate->displayName() }} — no payment needed now.<br>
                                                <i class="fa-regular fa-envelope"></i> You’ll get a booking confirmation on your phone.</p>
                                        @else
                                            <p class="room-payment-note"><i class="fa-regular fa-credit-card"></i> Pay securely online to confirm your booking instantly.<br>
                                                <i class="fa-regular fa-envelope"></i> Free cancellation as per the plan selected above.</p>
                                        @endif
                                    </div>

                                    <div class="room-panel__reserve">
                                        <div class="room-reserve__stay">
                                            <strong data-stay-label>{{ min(20, max(1, (int) request('adults', 2))) }} {{ Str::plural('Adult', (int) request('adults', 2)) }} / {{ $nights }} {{ Str::plural('night', $nights) }}</strong>
                                            {{ $checkIn->format('d.m.Y') }} – {{ $checkOut->format('d.m.Y') }}
                                        </div>
                                        <div class="room-reserve__price" @if($result['plans']->isEmpty()) hidden @endif>
                                            <strong data-panel-price></strong>
                                            <span data-panel-price-note></span>
                                        </div>
                                        @if(!$soldOut)<select class="room-qty" data-type-qty="{{ $roomType->id }}" aria-label="Number of rooms">
                                            @foreach (range(0, min(5, $result['sellable'])) as $qty)
                                                <option value="{{ $qty }}" @selected($qty === $oldTypeQty)>{{ $qty }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn-reserve" data-panel-reserve>Reserve</button>
                                        @else
                                            <input type="hidden" value="0" data-type-qty="{{ $roomType->id }}">
                                            <span class="room-sold-message">Unavailable</span>
                                        @endif
                                    </div>
                                </div>
                            </section>
                        @endforeach
                </div>

                {{-- No guest form: identity comes from the OTP-verified account (filled after login),
                     occupancy from the search, and payment defaults to online — MakeMyTrip/OYO-style. --}}
                @php($selectedPaymentMode = old('payment_mode', $corporate?->billsToCompany() ? 'bill_to_company' : 'pay_online'))
                <input type="hidden" name="guest_name" value="{{ old('guest_name', auth()->user()?->name) }}">
                <input type="hidden" name="guest_phone" value="{{ old('guest_phone', auth()->user()?->phone) }}">
                <input type="hidden" name="guest_email" value="{{ old('guest_email', auth()->user()?->email) }}">
                <input type="hidden" name="adults" value="{{ old('adults', min(20, max(1, (int) request('adults', 2)))) }}">
                <input type="hidden" name="children" value="{{ old('children', min(20, max(0, (int) request('children', 0)))) }}">
                <input type="hidden" name="payment_mode" value="{{ $selectedPaymentMode }}">

                @if($publishedRuleSet)
                    <label class="booking-rule-accept"><input type="checkbox" name="property_rules_accepted" value="1" @checked(old('property_rules_accepted')) required><span>I have read and accept <button type="button" data-property-rules-open>the property rules</button>.</span></label>
                @endif

                <div id="stickyBar" class="booking-sticky" hidden>
                    <div class="booking-sticky__inner">
                        <div class="booking-sticky__rooms">
                            <strong id="stickyRooms"></strong>
                            <span>{{ $nights }} {{ Str::plural('night', $nights) }} · GST included · secure online payment</span>
                        </div>
                        <p class="booking-sticky__total" id="stickyTotal"></p>
                        <button type="submit" class="btn-reserve">Reserve</button>
                    </div>
                </div>
            </form>
        @else
            <div class="confirm-card" style="text-align:center;">
                <h2>No rooms available online for these dates.</h2>
                <p class="addr">Try different dates, or call the property directly — walk-in inventory may still be open.</p>
            </div>
        @endif
    @endif
@endsection

@section('scripts')
    <script>
        (() => {
            const modal = document.getElementById('propertyRulesModal');
            if (!modal) return;
            const jumpTo = targetId => {
                const target = targetId && modal.querySelector(`#${CSS.escape(targetId)}`);
                if (target) target.scrollIntoView({block: 'start'});
                modal.querySelectorAll('[data-rules-jump]').forEach(button => button.classList.toggle('is-active', button.dataset.rulesJump === targetId));
            };
            document.querySelectorAll('[data-property-rules-open]').forEach(button => button.addEventListener('click', () => {
                if (!modal.open) modal.showModal();
                requestAnimationFrame(() => jumpTo(button.dataset.rulesTarget));
            }));
            modal.querySelectorAll('[data-rules-jump]').forEach(button => button.addEventListener('click', () => jumpTo(button.dataset.rulesJump)));
            modal.querySelector('[data-property-rules-close]')?.addEventListener('click', () => modal.close());
            modal.addEventListener('click', event => { if (event.target === modal) modal.close(); });
        })();
        document.getElementById('check_in')?.addEventListener('change', function () {
            const checkOut = document.getElementById('check_out');
            if (!this.value || !checkOut) return;
            const next = new Date(this.value); next.setDate(next.getDate() + 1);
            const min = next.toISOString().slice(0, 10);
            checkOut.min = min;
            if (!checkOut.value || checkOut.value <= this.value) checkOut.value = min;
        });

        (() => {
            const picker = document.querySelector('[data-guest-picker]');
            if (!picker) return;
            const trigger = picker.querySelector('[data-guest-trigger]');
            const popover = picker.querySelector('[data-guest-popover]');
            const limits = {rooms: [1, 5], adults: [1, 20], children: [0, 20]};

            const update = (key, change = 0) => {
                const input = picker.querySelector(`[data-counter-input="${key}"]`);
                const [min, max] = limits[key];
                input.value = Math.min(max, Math.max(min, Number(input.value) + change));
                picker.querySelector(`[data-counter-value="${key}"]`).textContent = input.value;
                const rooms = Number(picker.querySelector('[data-counter-input="rooms"]').value);
                const adults = Number(picker.querySelector('[data-counter-input="adults"]').value);
                const children = Number(picker.querySelector('[data-counter-input="children"]').value);
                picker.querySelector('[data-guest-summary]').textContent = `${rooms} Room${rooms > 1 ? 's' : ''}, ${adults} Adult${adults > 1 ? 's' : ''}${children ? `, ${children} Child${children > 1 ? 'ren' : ''}` : ''}`;
            };
            const close = () => { popover.hidden = true; trigger.setAttribute('aria-expanded', 'false'); };
            trigger.addEventListener('click', () => {
                popover.hidden = !popover.hidden;
                trigger.setAttribute('aria-expanded', String(!popover.hidden));
            });
            picker.querySelectorAll('[data-counter-minus]').forEach(button => button.addEventListener('click', () => update(button.dataset.counterMinus, -1)));
            picker.querySelectorAll('[data-counter-plus]').forEach(button => button.addEventListener('click', () => update(button.dataset.counterPlus, 1)));
            picker.querySelector('[data-guest-done]').addEventListener('click', close);
            document.addEventListener('click', event => { if (!picker.contains(event.target)) close(); });
            document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
        })();

        (() => {
            const form = document.getElementById('reserveForm');
            if (!form) return;

            // ---- OTP sign-in gate (MakeMyTrip-style) ----
            let isCustomer = @json(auth()->user()?->hasRole(\App\Models\User::ROLE_CUSTOMER) === true);
            const modal = document.getElementById('otpModal');
            const el = selector => modal.querySelector(selector);
            let knownUser = false; let resendTimer = null;

            form.addEventListener('submit', event => {
                if (!isCustomer) {
                    event.preventDefault();
                    openModal();
                }
            });

            function openModal() {
                showStep('phone');
                el('[data-otp-error-phone]').hidden = true;
                modal.showModal();
                el('#otpMobile').focus();
            }

            function showStep(step) {
                modal.querySelectorAll('[data-otp-step]').forEach(section =>
                    section.classList.toggle('active', section.dataset.otpStep === step));
            }

            function fail(target, message) {
                const error = el(`[data-otp-error-${target}]`);
                error.textContent = message;
                error.hidden = false;
            }

            async function postJson(url, body) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(body),
                });
                const data = await response.json().catch(() => ({}));
                return {ok: response.ok, data};
            }

            function startResendCountdown() {
                const button = el('[data-otp-resend]');
                let seconds = 30;
                button.disabled = true;
                button.textContent = `Resend code (${seconds}s)`;
                clearInterval(resendTimer);
                resendTimer = setInterval(() => {
                    seconds--;
                    if (seconds <= 0) { clearInterval(resendTimer); button.disabled = false; button.textContent = 'Resend code'; }
                    else button.textContent = `Resend code (${seconds}s)`;
                }, 1000);
            }

            async function sendOtp() {
                el('[data-otp-error-phone]').hidden = true;
                el('[data-otp-error-verify]').hidden = true;
                const button = el('[data-otp-send]');
                button.disabled = true; button.textContent = 'Sending…';
                const {ok, data} = await postJson(@json(route('otp.start')), {
                    country_code: el('#otpCode').value,
                    mobile: el('#otpMobile').value,
                });
                button.disabled = false; button.textContent = 'Continue';
                if (!ok) return fail('phone', Object.values(data.errors || {}).flat()[0] || data.message || 'Could not send the code — check the number.');

                knownUser = data.exists;
                el('[data-otp-phone-label]').textContent = data.phone_e164;
                el('[data-otp-welcome]').hidden = !data.exists;
                if (data.exists) el('[data-otp-welcome]').textContent = `Welcome back, ${data.first_name}! Your details are already saved.`;
                el('[data-otp-register]').hidden = data.exists;
                el('[data-otp-dev]').hidden = !data.dev_otp;
                if (data.dev_otp) {
                    el('[data-otp-dev]').textContent = `Development OTP: ${data.dev_otp} · auto-filled · valid for 5 minutes`;
                    el('#otpInput').value = data.dev_otp;
                } else {
                    el('#otpInput').value = '';
                }
                showStep('verify');
                el('#otpInput').focus();
                startResendCountdown();
            }

            async function verifyOtp() {
                el('[data-otp-error-verify]').hidden = true;
                const button = el('[data-otp-verify]');
                button.disabled = true; button.textContent = 'Verifying…';
                const {ok, data} = await postJson(@json(route('otp.verify')), {
                    country_code: el('#otpCode').value,
                    mobile: el('#otpMobile').value,
                    otp: el('#otpInput').value,
                    name: el('#otpName').value || null,
                    email: el('#otpEmail').value || null,
                });
                button.disabled = false; button.textContent = 'Verify & continue';
                if (!ok) {
                    if (data.requires_name) el('[data-otp-register]').hidden = false;
                    return fail('verify', Object.values(data.errors || {}).flat()[0] || data.message || 'Verification failed — try again.');
                }

                // Session was refreshed on login — adopt the new CSRF token.
                document.querySelector('meta[name="csrf-token"]').content = data.csrf;
                form.querySelector('input[name="_token"]').value = data.csrf;

                const guestName = form.querySelector('[name="guest_name"]');
                const guestPhone = form.querySelector('[name="guest_phone"]');
                const guestEmail = form.querySelector('[name="guest_email"]');
                if (!guestName.value) guestName.value = data.user.name;
                if (!guestPhone.value) guestPhone.value = data.user.phone || '';
                if (!guestEmail.value) guestEmail.value = data.user.email || '';

                isCustomer = true;
                modal.close();
                form.requestSubmit();
            }

            el('[data-otp-send]').addEventListener('click', sendOtp);
            el('[data-otp-resend]').addEventListener('click', sendOtp);
            el('[data-otp-verify]').addEventListener('click', verifyOtp);
            el('[data-otp-back]').addEventListener('click', () => showStep('phone'));
            el('[data-otp-close]').addEventListener('click', () => modal.close());
            modal.addEventListener('click', event => { if (event.target === modal) modal.close(); });
            el('#otpMobile').addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); sendOtp(); } });
            el('#otpInput').addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); verifyOtp(); } });

            const planInputs = [...form.querySelectorAll('[data-plan-input]')];
            const stickyBar = document.getElementById('stickyBar');
            const inr = minor => '₹' + (minor / 100).toLocaleString('en-IN');

            // Left sidebar tabs.
            document.querySelectorAll('[data-type-tab]').forEach(tab => tab.addEventListener('click', () => {
                document.querySelectorAll('[data-type-tab]').forEach(other => other.classList.toggle('active', other === tab));
                document.querySelectorAll('[data-type-panel]').forEach(panel =>
                    panel.classList.toggle('active', panel.dataset.typePanel === tab.dataset.typeTab));
            }));

            document.querySelectorAll('[data-more-info]').forEach(button => button.addEventListener('click', function () {
                this.closest('.room-panel__facilities').querySelector('[data-description]').classList.toggle('open');
            }));

            // Room-category galleries (up to three images).
            document.querySelectorAll('[data-room-gallery]').forEach(gallery => {
                const slides = [...gallery.querySelectorAll('[data-gallery-slide]')];
                const dots = [...gallery.querySelectorAll('[data-gallery-dot]')];
                if (slides.length < 2) return;

                let current = 0;
                const show = index => {
                    current = (index + slides.length) % slides.length;
                    slides.forEach((slide, position) => slide.classList.toggle('active', position === current));
                    dots.forEach((dot, position) => {
                        dot.classList.toggle('active', position === current);
                        dot.setAttribute('aria-current', position === current ? 'true' : 'false');
                    });
                };

                gallery.querySelector('[data-gallery-prev]')?.addEventListener('click', () => show(current - 1));
                gallery.querySelector('[data-gallery-next]')?.addEventListener('click', () => show(current + 1));
                dots.forEach(dot => dot.addEventListener('click', () => show(Number(dot.dataset.galleryDot))));
            });

            // Write the panel's radio + quantity into the hidden cart inputs.
            function syncPanel(panel) {
                const typeId = panel.dataset.type;
                const qty = parseInt(panel.querySelector('[data-type-qty]').value, 10) || 0;
                const radio = panel.querySelector('[data-plan-radio]:checked');
                planInputs.filter(input => input.dataset.type === typeId)
                    .forEach(input => input.value = (radio && input.dataset.planInput === radio.value) ? qty : 0);

                const priceMinor = parseInt(radio?.dataset.price ?? '0', 10);
                panel.querySelector('[data-panel-price]').textContent = inr(qty > 0 ? qty * priceMinor : priceMinor);
                panel.querySelector('[data-panel-price-note]').textContent = qty > 0
                    ? `for ${qty} room${qty > 1 ? 's' : ''} · taxes at property`
                    : 'per room · taxes at property';
            }

            function refreshCart() {
                let roomCount = 0; let totalMinor = 0; let taxMinor = 0; const parts = []; const perType = {};
                planInputs.forEach(input => {
                    const qty = parseInt(input.value, 10) || 0;
                    if (!qty) return;
                    roomCount += qty;
                    totalMinor += qty * parseInt(input.dataset.price, 10);
                    taxMinor += qty * parseInt(input.dataset.tax, 10);
                    parts.push(`${qty} × ${input.dataset.label}`);
                    perType[input.dataset.type] = (perType[input.dataset.type] || 0) + qty;
                });

                document.querySelectorAll('[data-type-pill]').forEach(pill => {
                    const qty = perType[pill.dataset.typePill] || 0;
                    pill.hidden = qty === 0;
                    pill.textContent = qty;
                });

                const hasSelection = roomCount > 0;
                stickyBar.hidden = !hasSelection;
                if (hasSelection) {
                    document.getElementById('stickyRooms').textContent = `${roomCount} room${roomCount > 1 ? 's' : ''} · incl. ${inr(taxMinor)} GST`;
                    document.getElementById('stickyTotal').textContent = inr(totalMinor + taxMinor);
                }
            }

            document.querySelectorAll('[data-type-panel]').forEach(panel => {
                panel.querySelectorAll('[data-plan-radio], [data-type-qty]').forEach(control =>
                    control.addEventListener('change', () => { syncPanel(panel); refreshCart(); }));

                panel.querySelector('[data-panel-reserve]')?.addEventListener('click', () => {
                    const qtySelect = panel.querySelector('[data-type-qty]');
                    if ((parseInt(qtySelect.value, 10) || 0) === 0 && parseInt(panel.dataset.max, 10) > 0) {
                        qtySelect.value = '1';
                    }
                    syncPanel(panel); refreshCart();
                    stickyBar.scrollIntoView({behavior: 'smooth', block: 'end'});
                });

                syncPanel(panel);
            });
            refreshCart();
        })();
    </script>
@endsection
