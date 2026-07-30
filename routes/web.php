<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AmenityController;
use App\Http\Controllers\Admin\AvailabilityCalendarController;
use App\Http\Controllers\Admin\BanquetController;
use App\Http\Controllers\Admin\BanquetImageController;
use App\Http\Controllers\Admin\BanquetLeadController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CorporateController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\OnlineInventoryController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyRuleController;
use App\Http\Controllers\Admin\PropertyContextController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PropertyImageController;
use App\Http\Controllers\Admin\RateCalendarController;
use App\Http\Controllers\Admin\RatePlanController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReservationBoardPreviewController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomImageController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StayOperationsController;
use App\Http\Controllers\Auth\AdminSessionController;
use App\Http\Controllers\Auth\CustomerSessionController;
use App\Http\Controllers\Auth\RegisteredCustomerController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Public\BookingEngineController;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $hasLocationColumn = Schema::hasTable('properties') && Schema::hasColumn('properties', 'location');
    $hasShowOnHomeColumn = Schema::hasTable('properties') && Schema::hasColumn('properties', 'show_on_home');

    $properties = Schema::hasTable('properties')
        ? Property::query()
            ->with('images')
            ->where('status', Property::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(array_filter([
                'id',
                'name',
                'property_type',
                'city',
                $hasLocationColumn ? 'location' : null,
                $hasShowOnHomeColumn ? 'show_on_home' : null,
                'address',
            ]))
        : collect();

    $banquetHalls = Schema::hasTable('banquets')
        ? \App\Models\Banquet::query()
            ->with(['property:id,name,city,location,status,address', 'images'])
            ->where('status', \App\Models\Banquet::STATUS_ACTIVE)
            ->whereHas('property', fn ($query) => $query->where('status', Property::STATUS_ACTIVE))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
        : collect();

    return view('landing', [
        'banquetHalls' => $banquetHalls,
        'guestHouseProperties' => $properties
            ->whereIn('property_type', [Property::TYPE_GUEST_HOUSE, Property::TYPE_MIXED])
            ->values(),
        'banquetProperties' => $properties
            ->whereIn('property_type', [Property::TYPE_BANQUET, Property::TYPE_MIXED])
            ->values(),
        'guestHouseFeaturedProperties' => $hasShowOnHomeColumn
            ? $properties
                ->where('show_on_home', true)
                ->whereIn('property_type', [Property::TYPE_GUEST_HOUSE, Property::TYPE_MIXED])
                ->values()
            : collect(),
        'banquetFeaturedProperties' => $hasShowOnHomeColumn
            ? $properties
                ->where('show_on_home', true)
                ->whereIn('property_type', [Property::TYPE_BANQUET, Property::TYPE_MIXED])
                ->values()
            : collect(),
    ]);
});

// Long-running admin edit screens can outlive the CSRF token that was rendered
// with the page. This same-origin endpoint rotates/returns a token for the
// visitor's own session; it does not weaken CSRF validation on write routes.
Route::get('/session/csrf-token', function () {
    return response()
        ->json(['token' => csrf_token()])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
})->middleware('throttle:30,1')->name('csrf-token.refresh');

Route::post('/auth/otp/start', [\App\Http\Controllers\Auth\OtpAuthController::class, 'start'])->middleware('throttle:10,1')->name('otp.start');
Route::post('/auth/otp/verify', [\App\Http\Controllers\Auth\OtpAuthController::class, 'verify'])->middleware('throttle:15,1')->name('otp.verify');
Route::post('/auth/register/complete', [\App\Http\Controllers\Auth\OtpAuthController::class, 'completeRegistration'])->middleware('throttle:10,1')->name('otp.register.complete');

Route::get('/banquets/{banquet}', [\App\Http\Controllers\Public\BanquetController::class, 'show'])->name('banquet.show');
Route::post('/banquets/{banquet}/enquiry', [\App\Http\Controllers\Public\BanquetController::class, 'enquiry'])->middleware('throttle:10,1')->name('banquet.enquiry');

Route::get('/book', [BookingEngineController::class, 'search'])->name('book.search');
Route::post('/book', [BookingEngineController::class, 'store'])->middleware('throttle:10,1')->name('book.store');
Route::get('/book/pay/{bookingNumber}', [BookingEngineController::class, 'pay'])->name('book.pay');
Route::post('/book/pay/verify', [BookingEngineController::class, 'verifyPayment'])->middleware('throttle:20,1')->name('book.pay.verify');
Route::get('/book/cancel/{bookingNumber}', [BookingEngineController::class, 'cancelForm'])->name('book.cancel');
Route::post('/book/cancel/{bookingNumber}', [BookingEngineController::class, 'cancel'])->middleware('throttle:10,1')->name('book.cancel.store');
Route::get('/book/confirmed/{bookingNumber}', [BookingEngineController::class, 'confirmation'])->name('book.confirmation');

Route::redirect('/login', '/admin/login')->name('login');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminSessionController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');

    Route::get('/login/customer', [CustomerSessionController::class, 'create'])->name('customer.login');
    Route::post('/login/customer', [CustomerSessionController::class, 'store'])->middleware('throttle:5,1')->name('customer.login.store');
    Route::get('/register', [RegisteredCustomerController::class, 'create'])->name('customer.register');
    Route::post('/register', [RegisteredCustomerController::class, 'store'])->middleware('throttle:5,1')->name('customer.register.store');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:'.User::ROLE_SUPER_ADMIN.','.User::ROLE_PROPERTY_MANAGER])
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/property-context', [PropertyContextController::class, 'update'])->name('property-context.update');
        Route::get('/availability', AvailabilityCalendarController::class)->name('availability.index');
        Route::get('/reservation-board-preview', ReservationBoardPreviewController::class)->name('reservation-board-preview');
        Route::get('/bookings/room-board-status', [BookingController::class, 'roomBoardStatus'])->name('bookings.room-board-status');
        Route::get('/bookings/pending-refunds', [BookingController::class, 'pendingRefunds'])->name('bookings.pending-refunds');
        Route::post('/bookings/{booking}/settle-refund', [BookingController::class, 'settleRefund'])->name('bookings.settle-refund');
        Route::post('/bookings/{booking}/assign-room', [BookingController::class, 'assignRoom'])->name('bookings.assign-room');
        Route::get('/bookings/{booking}/stay', [StayOperationsController::class, 'show'])->name('bookings.stay');
        Route::post('/bookings/{booking}/guests', [StayOperationsController::class, 'addGuest'])->name('bookings.guests.store');
        Route::patch('/bookings/{booking}/guests/{bookingGuest}', [StayOperationsController::class, 'updateGuest'])->name('bookings.guests.update');
        Route::delete('/bookings/{booking}/guests/{bookingGuest}', [StayOperationsController::class, 'removeGuest'])->name('bookings.guests.destroy');
        Route::post('/bookings/{booking}/guests/{bookingGuest}/documents', [StayOperationsController::class, 'addDocument'])->name('bookings.guests.documents.store');
        Route::post('/bookings/{booking}/check-in', [StayOperationsController::class, 'checkIn'])->name('bookings.check-in');
        Route::post('/bookings/{booking}/check-out', [StayOperationsController::class, 'checkOut'])->name('bookings.check-out');
        Route::resource('bookings', BookingController::class);
        Route::get('/guests/lookup', [GuestController::class, 'lookup'])->name('guests.lookup');
        Route::resource('guests', GuestController::class);
        Route::post('/properties/{property}/toggle-status', [PropertyController::class, 'toggleStatus'])
            ->name('properties.toggle-status');
        Route::post('/properties/{property}/toggle-home', [PropertyController::class, 'toggleHome'])
            ->name('properties.toggle-home');
        Route::get('/properties/{property}/rules', [PropertyRuleController::class, 'edit'])->name('properties.rules.edit');
        Route::put('/properties/{property}/rules', [PropertyRuleController::class, 'update'])->name('properties.rules.update');
        Route::post('/properties/{property}/rules/publish', [PropertyRuleController::class, 'publish'])->name('properties.rules.publish');
        Route::resource('properties', PropertyController::class);
        Route::delete('/properties/{property}/images/{image}', [PropertyImageController::class, 'destroy'])
            ->name('properties.images.destroy');
        Route::resource('amenities', AmenityController::class)->except(['show']);
        Route::post('/room-types/{roomType}/toggle-status', [RoomTypeController::class, 'toggleStatus'])->name('room-types.toggle-status');
        Route::put('/room-types/{roomType}/properties/{property}', [RoomTypeController::class, 'updatePropertyConfiguration'])->name('room-types.properties.update');
        Route::resource('room-types', RoomTypeController::class);
        Route::patch('/rooms/{room}/quick', [RoomController::class, 'quickUpdate'])->name('rooms.quick-update');
        Route::resource('rooms', RoomController::class);
        Route::get('/online-inventory', [OnlineInventoryController::class, 'index'])->name('online-inventory.index');
        Route::put('/online-inventory', [OnlineInventoryController::class, 'update'])->name('online-inventory.update');
        Route::get('/room-pricing', [RatePlanController::class, 'index'])->name('rate-plans.index');
        Route::post('/rate-plans', [RatePlanController::class, 'store'])->name('rate-plans.store');
        Route::put('/rate-plans/{ratePlan}', [RatePlanController::class, 'update'])->name('rate-plans.update');
        Route::post('/rate-plans/{ratePlan}/toggle', [RatePlanController::class, 'toggle'])->name('rate-plans.toggle');
        Route::get('/cancellation-policies', [\App\Http\Controllers\Admin\CancellationPolicyController::class, 'index'])->name('cancellation-policies.index');
        Route::post('/cancellation-policies', [\App\Http\Controllers\Admin\CancellationPolicyController::class, 'store'])->name('cancellation-policies.store');
        Route::put('/cancellation-policies/{cancellationPolicy}', [\App\Http\Controllers\Admin\CancellationPolicyController::class, 'update'])->name('cancellation-policies.update');
        Route::post('/cancellation-policies/{cancellationPolicy}/toggle', [\App\Http\Controllers\Admin\CancellationPolicyController::class, 'toggle'])->name('cancellation-policies.toggle');
        Route::post('/discounts/{discount}/toggle', [DiscountController::class, 'toggle'])->name('discounts.toggle');
        Route::resource('discounts', DiscountController::class)->except(['show']);
        Route::post('/corporates/{corporate}/toggle', [CorporateController::class, 'toggle'])->name('corporates.toggle');
        Route::resource('corporates', CorporateController::class);
        Route::get('/rate-calendar', [RateCalendarController::class, 'index'])->name('rate-calendar.index');
        Route::put('/rate-calendar', [RateCalendarController::class, 'update'])->name('rate-calendar.update');
        Route::delete('/room-images/{roomImage}', [RoomImageController::class, 'destroy'])
            ->name('room-images.destroy');
        Route::get('/banquet-leads', [BanquetLeadController::class, 'index'])->name('banquet-leads.index');
        Route::patch('/banquet-leads/{banquetLead}', [BanquetLeadController::class, 'updateStatus'])->name('banquet-leads.update-status');
        Route::resource('banquets', BanquetController::class);
        Route::delete('/banquet-images/{banquetImage}', [BanquetImageController::class, 'destroy'])
            ->name('banquet-images.destroy');
        Route::resource('admin-users', AdminUserController::class)
            ->middleware('role:'.User::ROLE_SUPER_ADMIN);
        Route::get('/reports', [ReportController::class, 'index'])
            ->middleware('role:'.User::ROLE_SUPER_ADMIN)
            ->name('reports.index');
        Route::get('/activity-log', ActivityLogController::class)->name('activity-log.index');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-email', [SettingsController::class, 'sendTestEmail'])->middleware('throttle:5,1')->name('settings.test-email');
        Route::post('/logout', [AdminSessionController::class, 'destroy'])->name('logout');
    });

Route::prefix('account')
    ->name('customer.')
    ->middleware(['auth', 'role:'.User::ROLE_CUSTOMER])
    ->group(function () {
        Route::get('/dashboard', CustomerDashboardController::class)->name('dashboard');
        Route::post('/logout', [CustomerSessionController::class, 'destroy'])->name('logout');
});

Route::redirect('/dashboard', '/admin/dashboard');
