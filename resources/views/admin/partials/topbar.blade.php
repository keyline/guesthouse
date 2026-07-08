@php
    $adminUser = auth()->user();
    $initials = 'AD';
    $roleLabel = 'Admin';
    $scopeLabel = 'Admin Area';

    if ($adminUser) {
        $parts = preg_split('/\s+/', trim($adminUser->name ?: $adminUser->email));
        $initials = strtoupper(substr($parts[0] ?? 'A', 0, 1).substr($parts[1] ?? '', 0, 1));
        $roleLabel = match ($adminUser->role) {
            \App\Models\User::ROLE_SUPER_ADMIN => 'Super Admin',
            \App\Models\User::ROLE_PROPERTY_MANAGER => 'Property Manager',
            default => 'Admin',
        };
        $adminUser->loadMissing('managedProperties');
        $scopeLabel = $adminUser->role === \App\Models\User::ROLE_SUPER_ADMIN
            ? 'All Properties'
            : ($adminUser->managedProperties->isNotEmpty()
                ? $adminUser->managedProperties->pluck('name')->take(2)->join(', ')
                : 'No Property Assigned');
    }

    $propertyScope = app(\App\Support\AdminPropertyScope::class);
    $contextProperties = $propertyScope->properties($adminUser);
    $selectedContextPropertyId = $propertyScope->selectedPropertyId($adminUser);
    $showPropertySwitcher = $adminUser && (
        $adminUser->hasRole(\App\Models\User::ROLE_SUPER_ADMIN) || $contextProperties->count() > 1
    );
@endphp

<header class="admin-topbar">
    <div class="flex min-h-16 items-center gap-3 px-4 py-3 lg:px-5">
        <button id="adminMobileToggle" type="button" class="admin-icon-button lg:hidden" aria-label="Open menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <div class="min-w-0 shrink-0">
            <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">@yield('eyebrow', 'Admin Console')</p>
            <h1 class="mt-0.5 truncate text-lg font-black text-slate-950">@yield('page-title', 'Dashboard')</h1>
        </div>

        <div class="mx-2 hidden max-w-xl flex-1 md:block">
            <label class="relative block">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"></path>
                    </svg>
                </span>
                <input type="search" placeholder="Search bookings, hotels, guests..." class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-sky-400 focus:bg-white focus:ring-2 focus:ring-sky-100">
            </label>
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-2">
            @if ($showPropertySwitcher)
                <form method="POST" action="{{ route('admin.property-context.update') }}" class="hidden lg:block">
                    @csrf
                    <select name="property_id" onchange="this.form.submit()" class="h-10 max-w-[230px] rounded-xl border border-slate-200 bg-white px-3 text-sm font-black text-slate-700 shadow-sm outline-none transition hover:border-sky-300 focus:border-sky-400 focus:ring-2 focus:ring-sky-100">
                        @if ($adminUser->hasRole(\App\Models\User::ROLE_SUPER_ADMIN))
                            <option value="0" @selected($selectedContextPropertyId === null)>All Hotels</option>
                        @endif
                        @foreach ($contextProperties as $property)
                            <option value="{{ $property->id }}" @selected($selectedContextPropertyId === $property->id)>{{ $property->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            <button type="button" class="admin-icon-button hidden sm:inline-flex" title="Notifications">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.17V11a6 6 0 10-12 0v3.17a2 2 0 01-.6 1.43L4 17h5m6 0a3 3 0 11-6 0m6 0H9"></path>
                </svg>
            </button>
            <button type="button" class="admin-icon-button hidden sm:inline-flex" title="Messages">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m8-2a9 9 0 11-4.22-7.63L21 3v5h-5"></path>
                </svg>
            </button>
            <button type="button" class="admin-icon-button hidden sm:inline-flex" title="Language">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5-2.5 3.75-5.5 3.75-9S14.5 5.5 12 3m0 18c-2.5-2.5-3.75-5.5-3.75-9S9.5 5.5 12 3M3.6 9h16.8M3.6 15h16.8"></path>
                </svg>
            </button>

            <details class="relative">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-2 shadow-sm transition hover:border-sky-200 hover:bg-sky-50">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-950 text-xs font-black text-white">{{ $initials }}</span>
                    <span class="hidden min-w-0 text-left lg:block">
                        <span class="block truncate text-sm font-black text-slate-950">{{ $adminUser?->name ?? 'Admin' }}</span>
                        <span class="block truncate text-[11px] font-bold text-slate-500">{{ $roleLabel }} · {{ $scopeLabel }}</span>
                    </span>
                </summary>
                <div class="absolute right-0 z-40 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                    <p class="truncate text-sm font-black text-slate-950">{{ $adminUser?->name ?? 'Admin' }}</p>
                    <p class="mt-0.5 truncate text-xs font-semibold text-slate-500">{{ $adminUser?->email }}</p>
                    <div class="my-3 border-t border-slate-100"></div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Access</p>
                    <p class="mt-1 text-sm font-bold text-slate-800">{{ $roleLabel }} · {{ $scopeLabel }}</p>
                </div>
            </details>
        </div>
    </div>
</header>
