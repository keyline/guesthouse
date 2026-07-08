<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AvailabilityCalendarController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\PropertyController;
use App\Http\Controllers\Admin\PropertyContextController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Auth\AdminSessionController;
use App\Http\Controllers\Auth\CustomerSessionController;
use App\Http\Controllers\Auth\RegisteredCustomerController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::get('/admin', function () {
    return Auth::check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('admin.login');
});

Route::get('/guest', function () {
    return redirect()->route('customer.login');
});

Route::get('/account', function () {
    return Auth::check()
        ? redirect()->route('customer.dashboard')
        : redirect()->route('customer.login');
});

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
        Route::resource('properties', PropertyController::class);
        Route::resource('room-types', RoomTypeController::class);
        Route::resource('rooms', RoomController::class);
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

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
});
