@php
    $selectedRole = old('role', $adminUser->role ?: \App\Models\User::ROLE_PROPERTY_MANAGER);
    $selectedPropertyIds = collect(old('property_ids', $assignedPropertyIds ?? []))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="grid gap-4">
    <section class="rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Account Details</h3>
        </div>
        <div class="grid gap-4 p-4 md:grid-cols-2">
            <div>
                <label class="block text-xs font-bold text-slate-600">Full Name *</label>
                <input name="name" value="{{ old('name', $adminUser->name) }}" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600">
                @error('name')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600">Email *</label>
                <input name="email" type="email" value="{{ old('email', $adminUser->email) }}" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600">
                @error('email')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600">Phone</label>
                <input name="phone" value="{{ old('phone', $adminUser->phone) }}" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600">
                @error('phone')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600">Role *</label>
                <select id="admin-user-role" name="role" class="mt-1 h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600">
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $adminUser->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-600">
                <span>
                    <span class="block text-sm font-bold text-slate-800">Active account</span>
                    <span class="text-xs font-semibold text-slate-500">Inactive users cannot log in.</span>
                </span>
            </label>
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Security</h3>
        </div>
        <div class="grid gap-4 p-4 md:grid-cols-2">
            <div>
                <label class="block text-xs font-bold text-slate-600">Password {{ $adminUser->exists ? '' : '*' }}</label>
                <input name="password" type="password" autocomplete="new-password" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600">
                @if ($adminUser->exists)
                    <p class="mt-1 text-xs font-semibold text-slate-500">Leave blank to keep current password.</p>
                @endif
                @error('password')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600">Confirm Password {{ $adminUser->exists ? '' : '*' }}</label>
                <input name="password_confirmation" type="password" autocomplete="new-password" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600">
            </div>
        </div>
    </section>

    <section id="property-assignment-panel" class="rounded-lg border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Property Access</h3>
                <p class="mt-1 text-xs font-semibold text-slate-500">Assign one or multiple properties for this admin user.</p>
            </div>
            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-black text-sky-700">{{ count($properties) }} properties</span>
        </div>

        <div class="p-4">
            @if (count($properties) > 0)
                <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($properties as $id => $name)
                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 transition hover:border-sky-300 hover:bg-sky-50">
                            <input type="checkbox" name="property_ids[]" value="{{ $id }}" @checked(in_array((int) $id, $selectedPropertyIds, true)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-600">
                            <span class="truncate text-sm font-bold text-slate-700">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    Add properties first, then assign them to property managers.
                </div>
            @endif
            @error('property_ids')<p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
            @error('property_ids.*')<p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
        </div>
    </section>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const role = document.getElementById('admin-user-role');
            const panel = document.getElementById('property-assignment-panel');

            if (! role || ! panel) {
                return;
            }

            const syncPropertyPanel = () => {
                panel.classList.toggle('opacity-50', role.value === '{{ \App\Models\User::ROLE_SUPER_ADMIN }}');
            };

            role.addEventListener('change', syncPropertyPanel);
            syncPropertyPanel();
        });
    </script>
@endonce
