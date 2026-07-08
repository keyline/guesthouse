<div class="grid gap-6 xl:grid-cols-[1.35fr_0.75fr]">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-black">Guest Profile</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div>
                <label for="name" class="text-sm font-bold text-slate-700">Name</label>
                <input id="name" name="name" value="{{ old('name', $guest->name) }}" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                @error('name')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="text-sm font-bold text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $guest->email) }}" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                @error('email')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="phone" class="text-sm font-bold text-slate-700">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone', $guest->phone) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <div>
                <label for="date_of_birth" class="text-sm font-bold text-slate-700">Date of birth</label>
                <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', optional($guest->date_of_birth)->toDateString()) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <div>
                <label for="gender" class="text-sm font-bold text-slate-700">Gender</label>
                <input id="gender" name="gender" value="{{ old('gender', $guest->gender) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <div>
                <label for="nationality" class="text-sm font-bold text-slate-700">Nationality</label>
                <input id="nationality" name="nationality" value="{{ old('nationality', $guest->nationality) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <div>
                <label for="id_document_type" class="text-sm font-bold text-slate-700">ID document type</label>
                <input id="id_document_type" name="id_document_type" value="{{ old('id_document_type', $guest->id_document_type) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <div>
                <label for="id_document_number" class="text-sm font-bold text-slate-700">ID document number</label>
                <input id="id_document_number" name="id_document_number" value="{{ old('id_document_number', $guest->id_document_number) }}" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
            </div>
            <div class="md:col-span-2">
                <label for="address" class="text-sm font-bold text-slate-700">Address</label>
                <textarea id="address" name="address" rows="3" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('address', $guest->address) }}</textarea>
            </div>
        </div>
    </section>

    <aside class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black">Account</h2>
            <div class="mt-5 space-y-4">
                <label class="inline-flex h-11 items-center gap-2 rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $guest->is_active ?? true))>
                    Active account
                </label>
                <div>
                    <label for="password" class="text-sm font-bold text-slate-700">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                    @error('password')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="text-sm font-bold text-slate-700">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm">
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black">Private Notes</h2>
            <textarea name="guest_notes" rows="6" class="mt-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('guest_notes', $guest->guest_notes) }}</textarea>
        </section>
    </aside>
</div>
