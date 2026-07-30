@extends('admin.layouts.app')

@section('title', 'Settings')
@section('eyebrow', 'Configuration')
@section('page-title', 'System Settings')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
            ✓ {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf

        <!-- TABS -->
        <div class="mb-4 overflow-x-auto border-b border-slate-200 bg-white">
            <div class="flex min-w-max gap-0">
                <button type="button" onclick="switchTab('general', this)" class="tab-btn active px-4 py-3 border-b-2 border-slate-900 font-semibold text-slate-900 text-sm" data-tab="general">
                    General
                </button>
                <button type="button" onclick="switchTab('business', this)" class="tab-btn px-4 py-3 border-b-2 border-transparent font-semibold text-slate-600 text-sm hover:text-slate-900" data-tab="business">
                    Business
                </button>
                <button type="button" onclick="switchTab('contact', this)" class="tab-btn px-4 py-3 border-b-2 border-transparent font-semibold text-slate-600 text-sm hover:text-slate-900" data-tab="contact">
                    Contact
                </button>
                <button type="button" onclick="switchTab('email', this)" class="tab-btn px-4 py-3 border-b-2 border-transparent font-semibold text-slate-600 text-sm hover:text-slate-900" data-tab="email">
                    Email / SMTP
                </button>
                <button type="button" onclick="switchTab('banking', this)" class="tab-btn px-4 py-3 border-b-2 border-transparent font-semibold text-slate-600 text-sm hover:text-slate-900" data-tab="banking">
                    Banking
                </button>
                <button type="button" onclick="switchTab('booking', this)" class="tab-btn px-4 py-3 border-b-2 border-transparent font-semibold text-slate-600 text-sm hover:text-slate-900" data-tab="booking">
                    Booking
                </button>
                <button type="button" onclick="switchTab('policies', this)" class="tab-btn px-4 py-3 border-b-2 border-transparent font-semibold text-slate-600 text-sm hover:text-slate-900" data-tab="policies">
                    Policies
                </button>
                @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN))
                    <button type="button" onclick="switchTab('appearance', this)" class="tab-btn px-4 py-3 border-b-2 border-transparent font-semibold text-slate-600 text-sm hover:text-slate-900" data-tab="appearance">
                        Appearance
                    </button>
                @endif
            </div>
        </div>

        <!-- TAB CONTENT -->
        <div class="space-y-4">
            <!-- GENERAL TAB -->
            <div id="general" class="tab-content space-y-4">
                <!-- BRANDING -->
                <section class="border border-slate-200 bg-white rounded-lg overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Branding & Site Identity</h3>
                    </div>
                    <div class="p-4">
                        <div class="grid gap-4">
                            <!-- Site Name -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Site Name / Brand Name *</label>
                                <input type="text" name="site_name" value="{{ old('site_name', $settings->site_name) }}" placeholder="e.g., EENNRA Hotel Chain" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                @error('site_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <!-- Icon, Logo & Favicon Row -->
                            <div class="grid gap-3 md:grid-cols-3">
                                <div>
                                    <input type="file" name="icon" accept="image/jpeg,image/png,image/svg+xml,image/webp" id="icon-input" class="hidden" onchange="previewImage(event, 'icon-preview')">
                                    <label id="icon-upload-card" for="icon-input" class="group flex min-h-[76px] cursor-pointer items-center justify-between gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50/80 p-3 transition hover:border-sky-400 hover:bg-sky-50">
                                        <span class="flex min-w-0 items-center gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-md border border-slate-200 bg-white text-xl text-slate-400">+</span><span class="min-w-0"><span class="block text-xs font-black uppercase tracking-wide text-slate-700">Icon</span><span id="icon-upload-status" data-initial-status="{{ $settings->icon_path ? 'Click to change' : 'Click to upload' }}" class="mt-0.5 block text-xs font-bold text-slate-600">{{ $settings->icon_path ? 'Click to change' : 'Click to upload' }}</span><span class="mt-0.5 block truncate text-[11px] text-slate-500">Navigation/app mark · Square · Max 1MB</span></span></span>
                                        <span id="icon-preview" data-upload-preview="icon" class="ml-auto grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-md border border-slate-200 bg-white text-slate-400 shadow-sm">@if($settings->icon_path)<img src="{{ asset('storage/'.$settings->icon_path) }}" alt="Icon" class="max-h-full max-w-full object-contain p-1.5">@else<span class="text-[9px] font-bold uppercase">Preview</span>@endif</span>
                                    </label>
                                    <button id="icon-clear-button" type="button" onclick="removeImage('icon', event)" class="mt-1 hidden text-[11px] font-bold text-slate-500 hover:text-red-600">Clear selected file</button>
                                    @error('icon')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <!-- Logo Upload -->
                                <div>
                                    <input type="file" name="logo" accept="image/jpeg,image/png,image/svg,image/webp" id="logo-input" class="hidden" onchange="previewImage(event, 'logo-preview')">
                                    <label id="logo-upload-card" for="logo-input" class="group flex min-h-[76px] cursor-pointer items-center justify-between gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50/80 p-3 transition hover:border-sky-400 hover:bg-sky-50">
                                        <span class="flex min-w-0 items-center gap-3">
                                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-md border border-slate-200 bg-white text-slate-400 transition group-hover:border-sky-200 group-hover:text-sky-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                </svg>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block text-xs font-black uppercase tracking-wide text-slate-700">Logo</span>
                                                <span id="logo-upload-status" data-initial-status="{{ $settings->logo_path ? 'Click to change' : 'Click to upload' }}" class="mt-0.5 block text-xs font-bold text-slate-600">{{ $settings->logo_path ? 'Click to change' : 'Click to upload' }}</span>
                                                <span class="mt-0.5 block truncate text-[11px] font-medium text-slate-500">Website & invoice · PNG, JPG, SVG, WEBP · Max 2MB</span>
                                            </span>
                                        </span>
                                        <span id="logo-preview" data-upload-preview="logo" class="ml-auto grid h-14 w-20 shrink-0 place-items-center overflow-hidden rounded-md border border-slate-200 bg-white text-slate-400 shadow-sm transition group-hover:border-sky-200">
                                            @if ($settings->logo_path)
                                                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo" class="max-h-full max-w-full object-contain p-1.5">
                                            @else
                                                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Preview</span>
                                            @endif
                                        </span>
                                    </label>
                                    <button id="logo-clear-button" type="button" onclick="removeImage('logo', event)" class="mt-1 hidden text-[11px] font-bold text-slate-500 hover:text-red-600">Clear selected file</button>
                                </div>

                                <!-- Favicon Upload -->
                                <div>
                                    <input type="file" name="favicon" accept="image/jpeg,image/png,image/ico,image/svg" id="favicon-input" class="hidden" onchange="previewImage(event, 'favicon-preview')">
                                    <label id="favicon-upload-card" for="favicon-input" class="group flex min-h-[76px] cursor-pointer items-center justify-between gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50/80 p-3 transition hover:border-sky-400 hover:bg-sky-50">
                                        <span class="flex min-w-0 items-center gap-3">
                                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-md border border-slate-200 bg-white text-slate-400 transition group-hover:border-sky-200 group-hover:text-sky-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                </svg>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block text-xs font-black uppercase tracking-wide text-slate-700">Favicon</span>
                                                <span id="favicon-upload-status" data-initial-status="{{ $settings->favicon_path ? 'Click to change' : 'Click to upload' }}" class="mt-0.5 block text-xs font-bold text-slate-600">{{ $settings->favicon_path ? 'Click to change' : 'Click to upload' }}</span>
                                                <span class="mt-0.5 block truncate text-[11px] font-medium text-slate-500">Browser icon · PNG, ICO, SVG · Max 512KB</span>
                                            </span>
                                        </span>
                                        <span id="favicon-preview" data-upload-preview="favicon" class="ml-auto grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-md border border-slate-200 bg-white text-slate-400 shadow-sm transition group-hover:border-sky-200">
                                            @if ($settings->favicon_path)
                                                <img src="{{ asset('storage/' . $settings->favicon_path) }}" alt="Favicon" class="max-h-full max-w-full object-contain p-1.5">
                                            @else
                                                <span class="text-[9px] font-bold uppercase tracking-wide text-slate-400">Preview</span>
                                            @endif
                                        </span>
                                    </label>
                                    <button id="favicon-clear-button" type="button" onclick="removeImage('favicon', event)" class="mt-1 hidden text-[11px] font-bold text-slate-500 hover:text-red-600">Clear selected file</button>
                                </div>
                            </div>

                            <!-- Tagline -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Tagline</label>
                                <input type="text" name="site_tagline" value="{{ old('site_tagline', $settings->site_tagline) }}" placeholder="e.g., Your Home Away From Home" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <!-- Site Description -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Site Description</label>
                                <textarea name="site_description" rows="3" class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">{{ old('site_description', $settings->site_description) }}</textarea>
                            </div>

                            <!-- Currency & Timezone Row -->
                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Currency</label>
                                    <select name="currency_code" class="h-9 w-full rounded border border-slate-300 bg-white px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                        @foreach ($currencies as $code => $label)
                                            <option value="{{ $code }}" @selected(old('currency_code', $settings->currency_code) === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Timezone</label>
                                    <select name="timezone" class="h-9 w-full rounded border border-slate-300 bg-white px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                        @foreach ($timezones as $tz => $label)
                                            <option value="{{ $tz }}" @selected(old('timezone', $settings->timezone) === $tz)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Website URL</label>
                                    <input type="url" name="website_url" value="{{ old('website_url', $settings->website_url) }}" placeholder="https://example.com" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SEO -->
                <section class="border border-slate-200 bg-white rounded-lg overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">SEO & Meta Information</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600">Meta Description</label>
                                <textarea name="meta_description" rows="2" placeholder="Brief description for search engines..." class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">{{ old('meta_description', $settings->meta_description) }}</textarea>
                                <p class="mt-1 text-xs text-slate-500">Recommended: 50-160 characters</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Meta Keywords</label>
                                <textarea name="meta_keywords" rows="2" placeholder="keyword1, keyword2, keyword3..." class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">{{ old('meta_keywords', $settings->meta_keywords) }}</textarea>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- BUSINESS TAB -->
            <div id="business" class="tab-content hidden space-y-4">
                <section class="border border-slate-200 bg-white rounded-lg overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Business Information</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-600">Business Name *</label>
                                <input type="text" name="business_name" value="{{ old('business_name', $settings->business_name) }}" placeholder="Legal business name" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Business Type</label>
                                <select name="business_type" class="h-9 w-full rounded border border-slate-300 bg-white px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                    @foreach ($businessTypes as $code => $label)
                                        <option value="{{ $code }}" @selected(old('business_type', $settings->business_type) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Registration Number</label>
                                <input type="text" name="business_registration_number" value="{{ old('business_registration_number', $settings->business_registration_number) }}" placeholder="e.g., CIN, Company Registration No" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Business License Number</label>
                                <input type="text" name="business_license_number" value="{{ old('business_license_number', $settings->business_license_number) }}" placeholder="Hotel license number" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Tax Name (GST, VAT, etc.)</label>
                                <input type="text" name="tax_name" value="{{ old('tax_name', $settings->tax_name) }}" placeholder="e.g., GST" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Tax ID / GSTIN</label>
                                <input type="text" name="tax_id" value="{{ old('tax_id', $settings->tax_id) }}" placeholder="27AABCT1234A1Z5" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="border border-slate-200 bg-white rounded-lg overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Business Address</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-600">Address Line 1</label>
                                <input type="text" name="address_line_1" value="{{ old('address_line_1', $settings->address_line_1) }}" placeholder="Street address" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-600">Address Line 2</label>
                                <input type="text" name="address_line_2" value="{{ old('address_line_2', $settings->address_line_2) }}" placeholder="Apartment, floor, building (optional)" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">City</label>
                                <input type="text" name="city" value="{{ old('city', $settings->city) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">State / Province</label>
                                <input type="text" name="state_province" value="{{ old('state_province', $settings->state_province) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Postal Code</label>
                                <input type="text" name="postal_code" value="{{ old('postal_code', $settings->postal_code) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Country</label>
                                <input type="text" name="country" value="{{ old('country', $settings->country) }}" placeholder="e.g., India" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- CONTACT TAB -->
            <div id="contact" class="tab-content hidden space-y-4">
                <section class="border border-slate-200 bg-white rounded-lg overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Contact Information</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-600">Primary Email</label>
                                <input type="email" name="primary_email" value="{{ old('primary_email', $settings->primary_email) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Primary Phone</label>
                                <input type="tel" name="primary_phone" value="{{ old('primary_phone', $settings->primary_phone) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Support Email</label>
                                <input type="email" name="support_email" value="{{ old('support_email', $settings->support_email) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Support Phone</label>
                                <input type="tel" name="support_phone" value="{{ old('support_phone', $settings->support_phone) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Reservations Email</label>
                                <input type="email" name="reservations_email" value="{{ old('reservations_email', $settings->reservations_email) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Reservations Phone</label>
                                <input type="tel" name="reservations_phone" value="{{ old('reservations_phone', $settings->reservations_phone) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- EMAIL / SMTP TAB -->
            <div id="email" class="tab-content hidden space-y-4">
                <section class="border border-slate-200 bg-white rounded-lg overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Outgoing Email (SMTP)</h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="flex items-start gap-3 rounded-lg border border-sky-100 bg-sky-50 px-4 py-3">
                            <span class="text-lg">✉️</span>
                            <p class="text-xs font-semibold text-sky-800">Booking confirmations, OTPs and notifications are sent through this SMTP server. These settings work with <a href="https://www.brevo.com/" target="_blank" rel="noopener" class="font-black underline">Brevo</a> — create a free account, then find your credentials under <strong>Brevo → SMTP &amp; API → SMTP</strong>. Defaults below are pre-filled for Brevo.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-600">SMTP Host</label>
                                <input type="text" name="smtp_host" value="{{ old('smtp_host', $settings->smtp_host) }}" placeholder="smtp-relay.brevo.com" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600">Port</label>
                                    <input type="number" name="smtp_port" value="{{ old('smtp_port', $settings->smtp_port) }}" placeholder="587" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600">Encryption</label>
                                    <select name="smtp_encryption" class="h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                        <option value="tls" @selected(old('smtp_encryption', $settings->smtp_encryption ?: 'tls') === 'tls')>TLS (587)</option>
                                        <option value="ssl" @selected(old('smtp_encryption', $settings->smtp_encryption ?: 'tls') === 'ssl')>SSL (465)</option>
                                        <option value="none" @selected(old('smtp_encryption', $settings->smtp_encryption ?: 'tls') === 'none')>None</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600">SMTP Login / Username</label>
                                <input type="text" name="smtp_username" value="{{ old('smtp_username', $settings->smtp_username) }}" placeholder="you@example.com" autocomplete="off" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                <p class="mt-1 text-[11px] font-semibold text-slate-400">Your Brevo SMTP login (shown on the SMTP page).</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600">SMTP Password / Key</label>
                                <input type="password" name="smtp_password" value="" placeholder="{{ $settings->smtp_password ? '•••••••• (unchanged)' : 'Your Brevo SMTP key' }}" autocomplete="new-password" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                <p class="mt-1 text-[11px] font-semibold text-slate-400">Leave blank to keep the current key. Use the Brevo <strong>SMTP key</strong>, not your login password.</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-600">"From" Email Address</label>
                                <input type="email" name="notification_email_sender" value="{{ old('notification_email_sender', $settings->notification_email_sender) }}" placeholder="reservations@yourhotel.com" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                <p class="mt-1 text-[11px] font-semibold text-slate-400">Must be a sender you've verified in Brevo, or delivery will fail.</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-4">
                            <button type="button" onclick="sendTestEmail(this)" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 hover:bg-slate-50">Send test email</button>
                            <span data-test-email-result class="text-xs font-bold"></span>
                            <span class="text-[11px] font-semibold text-slate-400">Save your settings first, then send a test to your own inbox.</span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- BANKING TAB -->
            <div id="banking" class="tab-content hidden space-y-4">
                <section class="border border-slate-200 bg-white rounded-lg overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Bank Account Details</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-600">Account Holder Name</label>
                                <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder', $settings->bank_account_holder) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Bank Name</label>
                                <input type="text" name="bank_name" value="{{ old('bank_name', $settings->bank_name) }}" placeholder="e.g., HDFC Bank" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Account Number</label>
                                <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $settings->bank_account_number) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Routing Number (US/Intl)</label>
                                <input type="text" name="bank_routing_number" value="{{ old('bank_routing_number', $settings->bank_routing_number) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">SWIFT Code</label>
                                <input type="text" name="bank_swift_code" value="{{ old('bank_swift_code', $settings->bank_swift_code) }}" placeholder="e.g., HDFCINBB" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">IBAN</label>
                                <input type="text" name="bank_iban" value="{{ old('bank_iban', $settings->bank_iban) }}" placeholder="e.g., DE89370400440532013000" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- BOOKING TAB -->
            <div id="booking" class="tab-content hidden space-y-4">
                <section class="border border-slate-200 bg-white rounded-lg overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Booking & Reservation Settings</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-600">Default Check-in Time (HH:MM)</label>
                                <input type="time" name="default_check_in_time" value="{{ sprintf('%02d:%02d', intdiv($settings->default_check_in_time, 60), $settings->default_check_in_time % 60) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Default Check-out Time (HH:MM)</label>
                                <input type="time" name="default_check_out_time" value="{{ sprintf('%02d:%02d', intdiv($settings->default_check_out_time, 60), $settings->default_check_out_time % 60) }}" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Min Advance Booking (days)</label>
                                <input type="number" name="minimum_advance_booking_days" value="{{ old('minimum_advance_booking_days', $settings->minimum_advance_booking_days) }}" min="0" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Max Advance Booking (days)</label>
                                <input type="number" name="maximum_advance_booking_days" value="{{ old('maximum_advance_booking_days', $settings->maximum_advance_booking_days) }}" min="1" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-600">Cancellation Policy (Days)</label>
                                <input type="number" name="cancellation_policy_days" value="{{ old('cancellation_policy_days', $settings->cancellation_policy_days) }}" min="0" class="h-9 w-full rounded border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">
                                <p class="mt-1 text-xs text-slate-500">Days before check-in for free cancellation</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-600">Cancellation Policy Description</label>
                                <textarea name="cancellation_policy_description" rows="3" class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">{{ old('cancellation_policy_description', $settings->cancellation_policy_description) }}</textarea>
                            </div>

                            <div class="md:col-span-2 space-y-2">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="enable_guest_reviews" value="1" @checked(old('enable_guest_reviews', $settings->enable_guest_reviews)) class="w-4 h-4 rounded border-slate-300 text-sky-600 focus:ring-sky-600">
                                    <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">Enable Guest Reviews</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="enable_online_payment" value="1" @checked(old('enable_online_payment', $settings->enable_online_payment)) class="w-4 h-4 rounded border-slate-300 text-sky-600 focus:ring-sky-600">
                                    <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">Enable Online Payment</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            @if (auth()->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN))
                <div id="appearance" class="tab-content hidden space-y-4">
                    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Admin navigation theme</h3>
                            <p class="mt-1 text-xs text-slate-500">These colors apply to the admin navigation for every user.</p>
                        </div>
                        <div class="grid gap-5 p-5 lg:grid-cols-[1fr_260px]">
                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach ([
                                    ['admin_sidebar_color', 'Sidebar', '#53647f'],
                                    ['admin_primary_color', 'Active item', '#2563eb'],
                                    ['admin_accent_color', 'Accent', '#7dd3fc'],
                                    ['admin_sidebar_text_color', 'Font color', '#cbd5e1'],
                                ] as [$field, $label, $default])
                                    @php $colorValue = old($field, $settings->{$field} ?: $default); @endphp
                                    <label class="block">
                                        <span class="mb-1.5 block text-xs font-bold text-slate-600">{{ $label }}</span>
                                        <span class="flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white p-1.5 pr-3 focus-within:border-sky-500 focus-within:ring-2 focus-within:ring-sky-100">
                                            <input type="color" name="{{ $field }}" value="{{ $colorValue }}" data-theme-color="{{ $field }}" class="h-7 w-9 cursor-pointer rounded border-0 bg-transparent p-0">
                                            <span class="theme-color-value text-xs font-bold uppercase text-slate-600">{{ $colorValue }}</span>
                                        </span>
                                        @error($field)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                                    </label>
                                @endforeach
                                <div class="sm:col-span-2 xl:col-span-4">
                                    <button type="button" id="resetAdminTheme" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Restore default palette</button>
                                </div>
                            </div>
                            <div id="themePreview" class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-sm">
                                <div class="theme-preview-sidebar h-full min-h-52 p-3 text-white" style="background: {{ old('admin_sidebar_color', $settings->admin_sidebar_color ?: '#53647f') }}">
                                    <div class="mb-5 flex items-center gap-2 border-b border-white/15 pb-3">
                                        <span class="theme-preview-mark grid h-8 w-8 place-items-center rounded-lg text-xs font-black">{{ mb_strtoupper(mb_substr($settings->site_name ?: 'P', 0, 1)) }}</span>
                                        <span class="text-xs font-black">{{ $settings->site_name ?: 'Property Manager' }}</span>
                                    </div>
                                    <p class="theme-preview-muted mb-2 text-[9px] font-black uppercase tracking-widest opacity-50">Operations</p>
                                    <div class="theme-preview-active flex h-9 items-center rounded-lg px-3 text-xs font-bold">Reservations</div>
                                    <div class="theme-preview-muted mt-1 flex h-9 items-center px-3 text-xs font-semibold opacity-70">Guests</div>
                                    <div class="theme-preview-muted mt-1 flex h-9 items-center px-3 text-xs font-semibold opacity-70">Housekeeping</div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            @endif

            <!-- POLICIES TAB -->
            <div id="policies" class="tab-content hidden space-y-4">
                <section class="border border-slate-200 bg-white rounded-lg overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Legal Policies & Terms</h3>
                    </div>
                    <div class="p-5">
                        <div class="grid gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600">Terms & Conditions</label>
                                <textarea name="terms_and_conditions" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">{{ old('terms_and_conditions', $settings->terms_and_conditions) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Privacy Policy</label>
                                <textarea name="privacy_policy" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">{{ old('privacy_policy', $settings->privacy_policy) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600">Refund Policy</label>
                                <textarea name="refund_policy" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600 transition">{{ old('refund_policy', $settings->refund_policy) }}</textarea>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- SAVE BUTTON -->
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Cancel</a>
            <button type="submit" class="rounded-lg bg-sky-600 px-6 py-2 text-sm font-bold text-white hover:bg-sky-700 transition shadow-sm">Save Settings</button>
        </div>
    </form>

    <script>
        function sendTestEmail(btn) {
            const result = document.querySelector('[data-test-email-result]');
            btn.disabled = true; const label = btn.textContent; btn.textContent = 'Sending…';
            result.textContent = ''; result.className = 'text-xs font-bold';
            fetch(@json(route('admin.settings.test-email')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token()),
                },
                body: JSON.stringify({}),
            })
            .then(async (r) => ({ ok: r.ok, data: await r.json().catch(() => ({})) }))
            .then(({ ok, data }) => {
                result.textContent = data.message || (ok ? 'Sent.' : 'Failed.');
                result.classList.add(ok ? 'text-emerald-600' : 'text-rose-600');
            })
            .catch(() => { result.textContent = 'Request failed.'; result.classList.add('text-rose-600'); })
            .finally(() => { btn.disabled = false; btn.textContent = label; });
        }

        function switchTab(tabName, button) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('border-slate-900', 'text-slate-900', 'font-semibold');
                el.classList.add('border-transparent', 'text-slate-600');
            });

            // Show selected tab
            document.getElementById(tabName).classList.remove('hidden');

            // Update button state
            button.classList.add('border-slate-900', 'text-slate-900', 'font-semibold');
            button.classList.remove('border-transparent', 'text-slate-600');
        }

        const themeDefaults = {
            admin_sidebar_color: '#53647f',
            admin_primary_color: '#2563eb',
            admin_accent_color: '#7dd3fc',
            admin_sidebar_text_color: '#cbd5e1',
        };

        function refreshThemePreview() {
            const values = Object.fromEntries([...document.querySelectorAll('[data-theme-color]')].map(input => [input.name, input.value]));
            const preview = document.querySelector('.theme-preview-sidebar');
            const active = document.querySelector('.theme-preview-active');
            const mark = document.querySelector('.theme-preview-mark');
            if (!preview) return;
            preview.style.background = values.admin_sidebar_color;
            preview.style.color = values.admin_sidebar_text_color;
            active.style.background = values.admin_primary_color;
            active.style.boxShadow = `inset 3px 0 0 ${values.admin_accent_color}`;
            mark.style.background = `linear-gradient(135deg, ${values.admin_accent_color}, ${values.admin_primary_color})`;
            preview.querySelectorAll('.theme-preview-muted').forEach(el => el.style.color = values.admin_sidebar_text_color);
            document.querySelectorAll('[data-theme-color]').forEach(input => input.closest('label').querySelector('.theme-color-value').textContent = input.value);
        }

        document.querySelectorAll('[data-theme-color]').forEach(input => input.addEventListener('input', refreshThemePreview));
        document.getElementById('resetAdminTheme')?.addEventListener('click', () => {
            Object.entries(themeDefaults).forEach(([name, value]) => {
                const input = document.querySelector(`[name="${name}"]`);
                if (input) input.value = value;
            });
            refreshThemePreview();
        });
        refreshThemePreview();

        const initialUploadPreviews = {};

        document.querySelectorAll('[data-upload-preview]').forEach((previewEl) => {
            initialUploadPreviews[previewEl.dataset.uploadPreview] = previewEl.innerHTML;
        });

        function previewImage(event, previewId) {
            const file = event.target.files[0];
            const inputType = event.target.id.replace('-input', '');
            const previewEl = document.getElementById(previewId);
            const statusEl = document.getElementById(inputType + '-upload-status');
            const clearButton = document.getElementById(inputType + '-clear-button');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewEl.innerHTML = `<img src="${e.target.result}" alt="Preview" class="max-h-full max-w-full object-contain p-1.5">`;
                    statusEl.textContent = file.name;
                    clearButton.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }

        function removeImage(inputType, clickEvent) {
            clickEvent.preventDefault();
            clickEvent.stopPropagation();

            const inputEl = document.getElementById(inputType + '-input');
            const previewEl = document.getElementById(inputType + '-preview');
            const statusEl = document.getElementById(inputType + '-upload-status');
            const clearButton = document.getElementById(inputType + '-clear-button');

            inputEl.value = '';
            previewEl.innerHTML = initialUploadPreviews[inputType] || '';
            statusEl.textContent = statusEl.dataset.initialStatus || 'Click to upload';
            clearButton.classList.add('hidden');
        }
    </script>
@endsection
