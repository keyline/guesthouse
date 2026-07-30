@php
    $customerType = old('customer_type', $guest->customer_type ?: 'individual');
    $corporate = $guest->corporate;
    $input = 'h-9 w-full rounded-md border border-slate-300 bg-white px-2.5 text-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
    $label = 'mb-1 block text-[10px] font-black uppercase tracking-wide text-slate-500';
    $states = ['Andaman and Nicobar Islands','Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chandigarh','Chhattisgarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Goa','Gujarat','Haryana','Himachal Pradesh','Jammu and Kashmir','Jharkhand','Karnataka','Kerala','Ladakh','Lakshadweep','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Puducherry','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal'];
    $phoneCodes = \App\Support\PhoneNumber::countryCodes();
    $phoneCode = old('phone_country_code',$guest->phone_country_code ?: '+91');
    $phoneNational = old('phone_national',$guest->phone_national ?: preg_replace('/\D+/', '', $guest->phone ?? ''));
    $showDetails = $guest->exists || $errors->any() || old('name');
@endphp

@unless($guest->exists)
    <section class="admin-card mb-3 overflow-hidden border-blue-200">
        <div class="grid items-center gap-3 p-4 lg:grid-cols-[230px_1fr_auto]">
            <div><span class="text-[9px] font-black uppercase tracking-[.18em] text-blue-600">Step 1 · Find guest</span><h2 class="mt-0.5 text-sm font-black">Start with mobile number</h2><p class="text-[10px] font-semibold text-slate-500">Returning guests are loaded instantly.</p></div>
            <div class="flex h-10 overflow-hidden rounded-lg border border-slate-300 bg-white focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100"><select id="guestLookupCode" name="phone_country_code" class="w-44 border-r border-slate-200 bg-slate-50 px-2 text-xs font-bold">@foreach($phoneCodes as $code=>$caption)<option value="{{ $code }}" @selected($phoneCode===$code)>{{ $caption }}</option>@endforeach</select><input id="guestLookupMobile" name="phone_national" value="{{ $phoneNational }}" inputmode="tel" autocomplete="tel-national" placeholder="10-digit mobile number" class="min-w-0 flex-1 px-3 text-sm font-bold outline-none"></div>
            <button id="guestLookupButton" type="button" class="h-10 rounded-lg bg-blue-600 px-5 text-xs font-black text-white hover:bg-blue-700">Find guest</button>
        </div>
        <div id="guestLookupResult" class="hidden border-t border-slate-100 px-4 py-3"></div>
        @error('phone_national')<p class="px-4 pb-3 text-xs font-bold text-rose-700">{{ $message }}</p>@enderror
    </section>
@endunless

<div id="guestDetailsPanel" class="grid gap-3 xl:grid-cols-[1fr_330px] {{ $showDetails ? '' : 'hidden' }}">
    <div class="space-y-3">
        <section class="admin-card overflow-hidden">
            <div class="admin-card-header"><div><h2 class="text-sm font-black">Guest identity</h2><p class="text-[11px] font-semibold text-slate-500">Personal details used across reservations and stays.</p></div><div class="flex rounded-lg bg-slate-100 p-1"><label class="cursor-pointer"><input type="radio" name="customer_type" value="individual" class="peer sr-only" @checked($customerType === 'individual')><span class="block rounded-md px-3 py-1.5 text-[11px] font-black text-slate-500 peer-checked:bg-white peer-checked:text-blue-700 peer-checked:shadow-sm">Individual</span></label><label class="cursor-pointer"><input type="radio" name="customer_type" value="corporate" class="peer sr-only" @checked($customerType === 'corporate')><span class="block rounded-md px-3 py-1.5 text-[11px] font-black text-slate-500 peer-checked:bg-white peer-checked:text-blue-700 peer-checked:shadow-sm">Corporate guest</span></label></div></div>
            <div class="grid gap-3 p-4 md:grid-cols-2 lg:grid-cols-4">
                <label class="lg:col-span-2"><span class="{{ $label }}">Full legal name *</span><input name="name" value="{{ old('name',$guest->name) }}" required class="{{ $input }}">@error('name')<small class="text-rose-600">{{ $message }}</small>@enderror</label>
                @if($guest->exists)<label><span class="{{ $label }}">Mobile *</span><span class="flex h-9 overflow-hidden rounded-md border border-slate-300"><select name="phone_country_code" class="w-24 border-r border-slate-200 bg-slate-50 px-1 text-[10px] font-bold">@foreach($phoneCodes as $code=>$caption)<option value="{{ $code }}" @selected($phoneCode===$code)>{{ $code }}</option>@endforeach</select><input name="phone_national" value="{{ $phoneNational }}" required inputmode="tel" class="min-w-0 flex-1 px-2 text-xs"></span>@error('phone_national')<small class="text-rose-600">{{ $message }}</small>@enderror</label>@else<div class="rounded-md bg-blue-50 px-3 py-2"><span class="{{ $label }}">Mobile</span><strong id="confirmedMobile" class="text-xs text-blue-800">{{ $phoneCode }} {{ $phoneNational }}</strong></div>@endif
                <label><span class="{{ $label }}">Email *</span><input name="email" type="email" value="{{ old('email',$guest->email) }}" required class="{{ $input }}">@error('email')<small class="text-rose-600">{{ $message }}</small>@enderror</label>
                <label><span class="{{ $label }}">Date of birth</span><input name="date_of_birth" type="date" value="{{ old('date_of_birth',$guest->date_of_birth?->toDateString()) }}" class="{{ $input }}"></label>
                <label><span class="{{ $label }}">Gender</span><select name="gender" class="{{ $input }}"><option value="">Not specified</option>@foreach(['Male','Female','Non-binary','Prefer not to say'] as $gender)<option value="{{ $gender }}" @selected(old('gender',$guest->gender)===$gender)>{{ $gender }}</option>@endforeach</select></label>
                <label><span class="{{ $label }}">Nationality</span><input name="nationality" value="{{ old('nationality',$guest->nationality ?: 'Indian') }}" class="{{ $input }}"></label>
                <label><span class="{{ $label }}">ID type</span><select name="id_document_type" class="{{ $input }}"><option value="">Not captured</option>@foreach(['Passport','Driving Licence','Voter ID','Aadhaar Offline / Masked','Other Government ID'] as $type)<option value="{{ $type }}" @selected(old('id_document_type',$guest->id_document_type)===$type)>{{ $type }}</option>@endforeach</select></label>
                <label><span class="{{ $label }}">ID number</span><input name="id_document_number" value="{{ old('id_document_number',$guest->id_document_number) }}" class="{{ $input }}" autocomplete="off"></label>
            </div>
        </section>

        <section class="admin-card overflow-hidden">
            <div class="admin-card-header"><div><h2 class="text-sm font-black">Residential address</h2><p class="text-[11px] font-semibold text-slate-500">India is selected by default.</p></div></div>
            <div class="grid gap-3 p-4 md:grid-cols-2 lg:grid-cols-4">
                <label class="lg:col-span-2"><span class="{{ $label }}">Address line 1 *</span><input name="address_line_1" value="{{ old('address_line_1',$guest->address_line_1 ?: $guest->address) }}" required class="{{ $input }}">@error('address_line_1')<small class="text-rose-600">{{ $message }}</small>@enderror</label>
                <label class="lg:col-span-2"><span class="{{ $label }}">Address line 2</span><input name="address_line_2" value="{{ old('address_line_2',$guest->address_line_2) }}" class="{{ $input }}"></label>
                <label><span class="{{ $label }}">Country *</span><select name="country" required class="{{ $input }}"><option value="India" @selected(old('country',$guest->country ?: 'India')==='India')>India</option><option value="Other" @selected(old('country',$guest->country)==='Other')>Other</option></select></label>
                <label><span class="{{ $label }}">State / UT *</span><input name="state" list="indiaStates" value="{{ old('state',$guest->state) }}" required class="{{ $input }}"><datalist id="indiaStates">@foreach($states as $state)<option value="{{ $state }}">@endforeach</datalist></label>
                <label><span class="{{ $label }}">City *</span><input name="city" value="{{ old('city',$guest->city) }}" required class="{{ $input }}"></label>
                <label><span class="{{ $label }}">PIN / postal code *</span><input name="postal_code" value="{{ old('postal_code',$guest->postal_code) }}" required inputmode="numeric" class="{{ $input }}"></label>
            </div>
        </section>

        <section id="corporatePanel" class="admin-card overflow-hidden {{ $customerType === 'corporate' ? '' : 'hidden' }}">
            <div class="admin-card-header"><div><h2 class="text-sm font-black">Corporate account</h2><p class="text-[11px] font-semibold text-slate-500">GST billing identity and registered office details.</p></div><span class="rounded bg-blue-50 px-2 py-1 text-[9px] font-black uppercase text-blue-700">B2B</span></div>
            <div class="grid gap-3 p-4 md:grid-cols-2 lg:grid-cols-4">
                <label class="lg:col-span-2"><span class="{{ $label }}">Legal company name *</span><input name="corporate_legal_name" value="{{ old('corporate_legal_name',$corporate?->legal_name) }}" data-corporate-required class="{{ $input }}">@error('corporate_legal_name')<small class="text-rose-600">{{ $message }}</small>@enderror</label>
                <label><span class="{{ $label }}">Trade name</span><input name="corporate_trade_name" value="{{ old('corporate_trade_name',$corporate?->trade_name) }}" class="{{ $input }}"></label>
                <label><span class="{{ $label }}">GSTIN *</span><input name="corporate_gstin" maxlength="15" value="{{ old('corporate_gstin',$corporate?->gstin) }}" data-corporate-required class="{{ $input }} uppercase" placeholder="19ABCDE1234F1Z5">@error('corporate_gstin')<small class="text-rose-600">{{ $message }}</small>@enderror</label>
                <label><span class="{{ $label }}">PAN</span><input name="corporate_pan" maxlength="10" value="{{ old('corporate_pan',$corporate?->pan) }}" class="{{ $input }} uppercase"></label>
                <label><span class="{{ $label }}">Contact person</span><input name="corporate_contact_name" value="{{ old('corporate_contact_name',$corporate?->contact_name) }}" class="{{ $input }}"></label>
                <label><span class="{{ $label }}">Office phone</span><input name="corporate_phone" value="{{ old('corporate_phone',$corporate?->phone) }}" class="{{ $input }}"></label>
                <label><span class="{{ $label }}">Billing email</span><input name="corporate_email" type="email" value="{{ old('corporate_email',$corporate?->email) }}" class="{{ $input }}"></label>
                <label class="lg:col-span-2"><span class="{{ $label }}">Registered office address *</span><input name="corporate_address_line_1" value="{{ old('corporate_address_line_1',$corporate?->address_line_1) }}" data-corporate-required class="{{ $input }}"></label>
                <label class="lg:col-span-2"><span class="{{ $label }}">Office address line 2</span><input name="corporate_address_line_2" value="{{ old('corporate_address_line_2',$corporate?->address_line_2) }}" class="{{ $input }}"></label>
                <label><span class="{{ $label }}">Country *</span><input name="corporate_country" value="{{ old('corporate_country',$corporate?->country ?: 'India') }}" data-corporate-required class="{{ $input }}"></label>
                <label><span class="{{ $label }}">State / UT *</span><input name="corporate_state" list="indiaStates" value="{{ old('corporate_state',$corporate?->state) }}" data-corporate-required class="{{ $input }}"></label>
                <label><span class="{{ $label }}">City *</span><input name="corporate_city" value="{{ old('corporate_city',$corporate?->city) }}" data-corporate-required class="{{ $input }}"></label>
                <label><span class="{{ $label }}">PIN code *</span><input name="corporate_postal_code" value="{{ old('corporate_postal_code',$corporate?->postal_code) }}" data-corporate-required class="{{ $input }}"></label>
            </div>
        </section>
    </div>

    <aside class="space-y-3">
        <section class="admin-card p-4"><div class="flex items-center justify-between"><div><h2 class="text-sm font-black">Account access</h2><p class="text-[10px] font-semibold text-slate-500">Optional guest portal login</p></div><label class="flex items-center gap-2 text-xs font-black text-slate-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$guest->is_active ?? true))> Active</label></div><div class="mt-3 grid gap-2"><label><span class="{{ $label }}">{{ $guest->exists ? 'New password (optional)' : 'Password *' }}</span><input name="password" type="password" autocomplete="new-password" class="{{ $input }}">@error('password')<small class="text-rose-600">{{ $message }}</small>@enderror</label><label><span class="{{ $label }}">Confirm password</span><input name="password_confirmation" type="password" class="{{ $input }}"></label></div></section>
        <section class="admin-card p-4"><h2 class="text-sm font-black">Private notes</h2><p class="mt-0.5 text-[10px] font-semibold text-slate-500">Visible only to authorised staff</p><textarea name="guest_notes" rows="6" class="mt-3 w-full rounded-md border border-slate-300 p-2 text-xs">{{ old('guest_notes',$guest->guest_notes) }}</textarea></section>
        <section class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-[10px] font-semibold leading-4 text-blue-800"><b class="block text-xs">Data handling</b>ID documents should be verified and stored through the secure check-in workflow. Avoid entering full Aadhaar numbers here.</section>
    </aside>
</div>

<script>
    (() => {
        const radios = document.querySelectorAll('[name="customer_type"]');
        const panel = document.getElementById('corporatePanel');
        function updateCorporatePanel() {
            const corporate = document.querySelector('[name="customer_type"]:checked')?.value === 'corporate';
            panel?.classList.toggle('hidden', !corporate);
            panel?.querySelectorAll('[data-corporate-required]').forEach(input => input.required = corporate);
        }
        radios.forEach(radio => radio.addEventListener('change', updateCorporatePanel));
        updateCorporatePanel();
        document.querySelectorAll('[name="corporate_gstin"],[name="corporate_pan"]').forEach(input => input.addEventListener('input', () => input.value = input.value.toUpperCase().replace(/\s/g,'')));

        const lookupButton = document.getElementById('guestLookupButton');
        lookupButton?.addEventListener('click', async () => {
            const code = document.getElementById('guestLookupCode').value;
            const mobile = document.getElementById('guestLookupMobile').value;
            const result = document.getElementById('guestLookupResult');
            lookupButton.disabled = true; lookupButton.textContent = 'Checking…';
            try {
                const response = await fetch(`{{ route('admin.guests.lookup') }}?country_code=${encodeURIComponent(code)}&mobile=${encodeURIComponent(mobile)}`, {headers:{'Accept':'application/json'}});
                const data = await response.json();
                result.classList.remove('hidden');
                if (!response.ok) {
                    const message = Object.values(data.errors || {}).flat()[0] || 'Enter a valid mobile number.';
                    result.innerHTML = `<p class="text-xs font-bold text-rose-700">${message}</p>`;
                } else if (data.found) {
                    document.getElementById('guestDetailsPanel').classList.add('hidden');
                    document.getElementById('createGuestActions')?.classList.add('hidden');
                    document.getElementById('createGuestActions')?.classList.remove('flex');
                    result.innerHTML = `<div class="flex flex-wrap items-center justify-between gap-3"><div><span class="text-[9px] font-black uppercase text-emerald-600">Existing guest found</span><h3 class="text-sm font-black text-slate-950">${data.guest.name}</h3><p class="text-[11px] font-semibold text-slate-500">${data.guest.phone} · ${data.guest.email} · ${data.guest.bookings_count} booking(s)</p></div><div class="flex gap-2"><a href="${data.guest.show_url}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-black">Open profile</a><a href="{{ route('admin.bookings.create') }}?user_id=${data.guest.id}" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-black text-white">Create booking</a></div></div>`;
                } else {
                    document.getElementById('guestDetailsPanel').classList.remove('hidden');
                    document.getElementById('createGuestActions')?.classList.remove('hidden');
                    document.getElementById('createGuestActions')?.classList.add('flex');
                    document.getElementById('confirmedMobile').textContent = data.phone_e164;
                    result.innerHTML = `<p class="text-xs font-bold text-blue-700">New mobile number. Complete the guest details below.</p>`;
                    document.querySelector('[name="name"]')?.focus();
                }
            } catch (error) { result.classList.remove('hidden'); result.innerHTML = '<p class="text-xs font-bold text-rose-700">Unable to check right now. Please try again.</p>'; }
            finally { lookupButton.disabled = false; lookupButton.textContent = 'Find guest'; }
        });
    })();
</script>
