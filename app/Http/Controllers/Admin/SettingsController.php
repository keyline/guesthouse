<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AdminNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::first() ?? new Setting();
        $businessTypes = [
            'hotel_chain' => 'Hotel Chain',
            'guest_house' => 'Guest House',
            'resort' => 'Resort',
            'banquet_hall' => 'Banquet Hall',
            'boutique_hotel' => 'Boutique Hotel',
            'hostel' => 'Hostel',
            'vacation_rental' => 'Vacation Rental',
        ];

        $currencies = [
            'INR' => '₹ Indian Rupee (INR)',
            'USD' => '$ US Dollar (USD)',
            'EUR' => '€ Euro (EUR)',
            'GBP' => '£ British Pound (GBP)',
            'AUD' => 'A$ Australian Dollar (AUD)',
            'CAD' => 'C$ Canadian Dollar (CAD)',
            'SGD' => 'S$ Singapore Dollar (SGD)',
            'AED' => 'د.إ UAE Dirham (AED)',
        ];

        $timezones = [
            'Asia/Kolkata' => 'Asia/Kolkata (IST)',
            'UTC' => 'UTC (GMT)',
            'America/New_York' => 'America/New_York (EST)',
            'Europe/London' => 'Europe/London (GMT)',
            'Asia/Dubai' => 'Asia/Dubai (GST)',
            'Asia/Bangkok' => 'Asia/Bangkok (ICT)',
            'Asia/Singapore' => 'Asia/Singapore (SGT)',
            'Australia/Sydney' => 'Australia/Sydney (AEDT)',
        ];

        return view('admin.settings.index', [
            'settings' => $settings,
            'businessTypes' => $businessTypes,
            'currencies' => $currencies,
            'timezones' => $timezones,
            'navItems' => AdminNavigation::make('settings'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'nullable|string|max:255',
            'site_tagline' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:1000',
            'business_name' => 'nullable|string|max:255',
            'business_type' => 'nullable|string',
            'business_registration_number' => 'nullable|string|max:100',
            'business_license_number' => 'nullable|string|max:100',
            'tax_name' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'currency_code' => 'nullable|string|max:3',
            'currency_symbol' => 'nullable|string|max:5',
            'timezone' => 'nullable|string',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'primary_email' => 'nullable|email',
            'support_email' => 'nullable|email',
            'reservations_email' => 'nullable|email',
            'primary_phone' => 'nullable|string|max:20',
            'support_phone' => 'nullable|string|max:20',
            'reservations_phone' => 'nullable|string|max:20',
            'website_url' => 'nullable|url',
            'bank_account_holder' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_routing_number' => 'nullable|string|max:50',
            'bank_swift_code' => 'nullable|string|max:20',
            'bank_iban' => 'nullable|string|max:50',
            'default_check_in_time' => 'nullable|date_format:H:i',
            'default_check_out_time' => 'nullable|date_format:H:i',
            'minimum_advance_booking_days' => 'nullable|integer|min:0',
            'maximum_advance_booking_days' => 'nullable|integer|min:1',
            'cancellation_policy_days' => 'nullable|integer|min:0',
            'cancellation_policy_description' => 'nullable|string',
            'enable_guest_reviews' => 'boolean',
            'enable_online_payment' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,svg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,ico,svg|max:512',
            'admin_sidebar_color' => [
                Rule::excludeIf(! $request->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN)),
                'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'admin_primary_color' => [
                Rule::excludeIf(! $request->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN)),
                'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'admin_accent_color' => [
                Rule::excludeIf(! $request->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN)),
                'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'admin_sidebar_text_color' => [
                Rule::excludeIf(! $request->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN)),
                'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        $settings = Setting::first() ?? new Setting();

        // Convert time strings to minutes
        if ($request->filled('default_check_in_time')) {
            [$hours, $minutes] = explode(':', $request->input('default_check_in_time'));
            $validated['default_check_in_time'] = intval($hours) * 60 + intval($minutes);
        } else {
            $validated['default_check_in_time'] = 1200; // 14:00
        }

        if ($request->filled('default_check_out_time')) {
            [$hours, $minutes] = explode(':', $request->input('default_check_out_time'));
            $validated['default_check_out_time'] = intval($hours) * 60 + intval($minutes);
        } else {
            $validated['default_check_out_time'] = 1100; // 11:00
        }

        // Set defaults for booking fields
        $validated['minimum_advance_booking_days'] = $validated['minimum_advance_booking_days'] ?? 0;
        $validated['maximum_advance_booking_days'] = $validated['maximum_advance_booking_days'] ?? 365;
        $validated['cancellation_policy_days'] = $validated['cancellation_policy_days'] ?? 7;

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $validated['logo_path'] = $logoPath;
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            $faviconPath = $request->file('favicon')->store('favicons', 'public');
            $validated['favicon_path'] = $faviconPath;
        }

        $settings->fill($validated);
        $settings->save();

        return redirect()->back()->with('status', 'Settings updated successfully!');
    }
}
