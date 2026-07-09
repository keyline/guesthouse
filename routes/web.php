<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AmenityController;
use App\Http\Controllers\Admin\AvailabilityCalendarController;
use App\Http\Controllers\Admin\BanquetController;
use App\Http\Controllers\Admin\BanquetImageController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyContextController;
use App\Http\Controllers\Admin\PropertyImageController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomImageController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\AdminSessionController;
use App\Http\Controllers\Auth\CustomerSessionController;
use App\Http\Controllers\Auth\RegisteredCustomerController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
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

    return view('landing', [
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
        Route::post('/property-context', [PropertyContextController::class, 'update'])->name('property-context.update');
        Route::get('/availability', AvailabilityCalendarController::class)->name('availability.index');
        Route::resource('bookings', BookingController::class);
        Route::resource('guests', GuestController::class);
        Route::post('/properties/{property}/toggle-status', [PropertyController::class, 'toggleStatus'])
            ->name('properties.toggle-status');
        Route::post('/properties/{property}/toggle-home', [PropertyController::class, 'toggleHome'])
            ->name('properties.toggle-home');
        Route::resource('properties', PropertyController::class);
        Route::delete('/properties/{property}/images/{image}', [PropertyImageController::class, 'destroy'])
            ->name('properties.images.destroy');
        Route::resource('amenities', AmenityController::class)->except(['show']);
        Route::resource('room-types', RoomTypeController::class);
        Route::resource('rooms', RoomController::class);
        Route::delete('/room-images/{roomImage}', [RoomImageController::class, 'destroy'])
            ->name('room-images.destroy');
        Route::resource('banquets', BanquetController::class);
        Route::delete('/banquet-images/{banquetImage}', [BanquetImageController::class, 'destroy'])
            ->name('banquet-images.destroy');
        Route::resource('admin-users', AdminUserController::class)
            ->middleware('role:'.User::ROLE_SUPER_ADMIN);
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
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
