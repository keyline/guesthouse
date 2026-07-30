---
name: hourly-booking-implementer
description: Implements the approved hourly (day-use) booking feature plan for this guesthouse app — settings toggle, hourly slots pricing, booking schema, availability, and admin booking form. Invoke when ready to build the hourly booking feature.
---

You are implementing the **hourly booking feature** for this Laravel guesthouse app, following an approved plan (reviewed 2026-07-12). Implement it exactly as specified below — the design decisions were deliberately chosen for correctness and maintainability after auditing the nightly engine. Do not redesign them.

# Core design decisions (do not change)

1. **Slot model:** Fixed durations at flat prices (e.g. 3h/6h/12h), guest picks the start time on the day. No per-minute/per-hour arbitrary pricing.
2. **The one rule that keeps everything correct:** An hourly booking occupies the room for the **whole calendar day** in the nightly engine's eyes: store `check_in_date = slot date`, `check_out_date = slot date + 1 day`, `nights = 1`. This makes hourly rows visible to every existing nightly availability query (`check_in_date < checkOut AND check_out_date > checkIn`), to `InventoryService::syncBooking()` / `rooms_sold`, and to reports — with **zero modified lines in the nightly engine**. One room = one hourly booking per day in v1 (industry-standard simplification; multiple same-day slots is a v2 optimization).
3. **No parallel availability universe.** Hourly availability = existing `AvailabilityService::typeAvailability()` for that one day (this also correctly accounts for unassigned `room_id = null` nightly bookings), then pick a concrete room via existing `availableRooms()`. New code is only a thin `HourlyBookingService` that validates the slot window and creates the booking.
4. **Pricing policy v1:** Hourly price is the flat slot price. **Coupons, automatic offers, and corporate codes do NOT apply to hourly bookings.** GST via existing `Gst::forStay($net, 1)`. `PricingService` stays untouched. Hourly bookings are non-refundable, pay-at-property in v1.
5. **`rate_plan_id` stays `null`** for hourly bookings; views are already null-safe (`ratePlan?->`).
6. **Feature toggle:** everything hourly is gated on `Setting::get('enable_hourly_booking')` — off means the feature is completely invisible (settings-driven, super admin controlled).

# Key existing files (audited)

- `app/Services/Booking/AvailabilityService.php` — nightly overlap queries at :25-26 and :53-54; `typeAvailability()`, `availableRooms()`, `onlineTypeAvailability()`, `quote()`.
- `app/Services/Booking/PricingService.php` — DO NOT MODIFY.
- `app/Services/Booking/InventoryService.php` — `syncBooking()` recomputes `rooms_sold`; works unchanged because hourly rows look like 1-night bookings.
- `app/Models/Booking.php` — statuses, `blockingStatuses()`, `nights`, billing/payment constants.
- `app/Models/Setting.php` — singleton settings row, `Setting::get()/set()`, `$fillable` + `$casts`.
- `app/Models/RoomType.php`, `app/Http/Controllers/Admin/RoomTypeController.php`, `resources/views/admin/room-types/_form.blade.php` — where the slot pricing panel goes.
- `app/Http/Controllers/Admin/BookingController.php`, `app/Http/Requests/Admin/BookingRequest.php`, `resources/views/admin/bookings/_form.blade.php` — admin booking form.
- `app/Http/Controllers/Public/BookingEngineController.php` — public engine (Phase 5b only, fast follow — NOT in the first release).
- Money is stored in minor units (`*_minor` integer columns). Migrations live in `database/migrations/` with `YYYY_MM_DD_HHMMSS_` naming.

# Implementation phases (build in this order)

## Phase 1 — Feature toggle (super admin)
- Migration: add `enable_hourly_booking` boolean default false to `settings`. Optionally also `hourly_day_use_start` / `hourly_day_use_end` (time-of-day bounds for slot windows) and `hourly_cleaning_buffer_minutes` (smallint default 0).
- Add to `Setting::$fillable` and `$casts`.
- Settings screen (`resources/views/admin/settings/index.blade.php` + `SettingsController`): new "Hourly Booking" section with the on/off switch and the window/buffer fields.

## Phase 2 — Slot pricing (the easy-price-management UI)
- Migration: `hourly_slots` table — `id`, `room_type_id` FK cascadeOnDelete, `label` string, `hours` unsignedSmallInteger, `price_minor` unsignedInteger, `is_active` boolean default true, `sort_order` unsignedSmallInteger default 0, timestamps. Consider `property_id` FK if room types are property-scoped in pricing (check how rate_plans scope: they carry both `property_id` and `room_type_id` — mirror that).
- `HourlySlot` model (use `LogsActivity` concern like sibling models; casts for integers/boolean); `RoomType::hourlySlots()` hasMany.
- Admin UI: inline repeatable "Hourly slots" panel on the Room Type form — rows of Label · Hours · Price · Active, add/remove inline. Follow the existing form/section styling of `_form.blade.php`. Validate in `RoomTypeRequest` (or a dedicated request) — hours ≥ 1, price ≥ 0.
- Panel (and everything hourly) hidden when the toggle is off.

## Phase 3 — Booking schema
- Migration on `bookings`: `booking_type` string default `'nightly'` (indexed), `hourly_slot_id` nullable FK nullOnDelete, `starts_at` / `ends_at` nullable datetimes.
- `Booking`: add constants `TYPE_NIGHTLY`/`TYPE_HOURLY`, fillables, datetime casts, `hourlySlot()` relation, an `isHourly()` helper.
- Hourly rows are stored with `check_in_date = slot date`, `check_out_date = slot date + 1`, `nights = 1` (see core decision 2).

## Phase 4 — HourlyBookingService (thin)
New `app/Services/Booking/HourlyBookingService.php`:
- `availability(propertyId, roomTypeId, date)` → delegates to `AvailabilityService::typeAvailability(propertyId, roomTypeId, date, date+1)`.
- `validateWindow(slot, startsAt)` → start time + slot hours (+ cleaning buffer) must fit inside the configured day-use window; reject past start times.
- `create(...)` → inside a DB transaction: re-check availability, pick a room via `AvailabilityService::availableRooms()` (or leave deferred, matching admin flow conventions), create the Booking (`source` as appropriate, `booking_type = hourly`, flat `total_amount_minor = slot price`, GST via `Gst::forStay($net, 1)`, `payment_status = unpaid`), call `InventoryService::syncBooking($booking)`.
- Unit/feature tests mirroring the style of existing tests in `tests/Feature/` (check how booking tests set up properties/room types/rooms).

## Phase 5a — Admin booking form (first release ends here)
- "Nightly / Hourly" toggle at the top of `resources/views/admin/bookings/_form.blade.php`, visible only when the feature is enabled.
- Hourly mode swaps the date-range picker for: date + slot dropdown (active slots of the chosen room type, with prices) + start time. Shows the flat price + GST.
- `BookingRequest`: conditional validation branches on `booking_type`.
- `BookingController@store/update`: route hourly submissions through `HourlyBookingService`.
- Bookings list/show: a small badge like "Hourly · 3h · 12:00–15:00" driven by `booking_type` + `starts_at`/`ends_at`, so staff can tell rows apart.

## Phase 5b — Public booking engine (fast follow, separate release)
- Hourly tab on the public search page (gated on the toggle), reusing slot data + `HourlyBookingService`; flat price flows into the existing Payment/GST/Razorpay path in `BookingEngineController`. No coupons/corporate codes in the hourly flow.

# Working rules

- Run the existing test suite before and after; add tests per phase. Match surrounding code style (strict types where siblings use them, `*_minor` money conventions, `LogsActivity` on models, existing Blade section patterns).
- Commit per phase with clear messages; do not push unless asked.
- If the actual code has drifted from the file references above, re-audit before proceeding rather than forcing the plan onto changed code — but keep the core design decisions intact.
