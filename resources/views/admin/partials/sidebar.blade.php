@php
    use Illuminate\Support\Facades\Route;

    $url = fn (string $route) => Route::has($route) ? route($route) : '#';
    $canManageAdmins = auth()->check() && auth()->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN);
    $navGroups = [
        [
            'title' => 'Main',
            'items' => [
                ['type' => 'link', 'label' => 'Dashboard', 'route' => 'admin.dashboard', 'patterns' => ['admin.dashboard'], 'icon' => 'dashboard'],
            ],
        ],
        [
            'title' => 'Operations',
            'items' => [
                [
                    'type' => 'group',
                    'label' => 'Bookings',
                    'key' => 'bookings',
                    'patterns' => ['admin.bookings.*', 'admin.availability.*'],
                    'icon' => 'booking',
                    'children' => [
                        ['label' => 'Bookings', 'route' => 'admin.bookings.index', 'patterns' => ['admin.bookings.*']],
                        ['label' => 'Reservations', 'route' => 'admin.bookings.index', 'patterns' => []],
                        ['label' => 'Check-in / Check-out', 'route' => 'admin.availability.index', 'patterns' => ['admin.availability.*']],
                        ['label' => 'Payments', 'route' => null, 'patterns' => []],
                        ['label' => 'Cancellations', 'route' => null, 'patterns' => []],
                        ['label' => 'Booking Inquiries', 'route' => null, 'patterns' => []],
                    ],
                ],
                [
                    'type' => 'group',
                    'label' => 'Inventory',
                    'key' => 'inventory',
                    'patterns' => ['admin.properties.*', 'admin.rooms.*', 'admin.online-inventory.*', 'admin.rate-plans.*', 'admin.rate-calendar.*', 'admin.room-types.*', 'admin.availability.*', 'admin.amenities.*', 'admin.banquets.*'],
                    'icon' => 'property',
                    'children' => [
                        ['label' => 'Properties', 'route' => 'admin.properties.index', 'patterns' => ['admin.properties.*']],
                        ['label' => 'Rooms', 'route' => 'admin.rooms.index', 'patterns' => ['admin.rooms.*']],
                        ['label' => 'Online Inventory', 'route' => 'admin.online-inventory.index', 'patterns' => ['admin.online-inventory.*']],
                        ['label' => 'Room Types', 'route' => 'admin.room-types.index', 'patterns' => ['admin.room-types.*']],
                        ['label' => 'Banquet Halls', 'route' => 'admin.banquets.index', 'patterns' => ['admin.banquets.*']],
                        ['label' => 'Room Types & Pricing', 'route' => 'admin.rate-plans.index', 'patterns' => ['admin.rate-plans.*']],
                        ['label' => 'Rate Calendar', 'route' => 'admin.rate-calendar.index', 'patterns' => ['admin.rate-calendar.*']],
                        ['label' => 'Rates & Availability', 'route' => 'admin.availability.index', 'patterns' => ['admin.availability.*']],
                        ['label' => 'Amenities', 'route' => 'admin.amenities.index', 'patterns' => ['admin.amenities.*']],
                        ['label' => 'Policies', 'route' => null, 'patterns' => []],
                    ],
                ],
            ],
        ],
        [
            'title' => 'People',
            'items' => [
                [
                    'type' => 'group',
                    'label' => 'Guests & Users',
                    'key' => 'users',
                    'patterns' => ['admin.guests.*', 'admin.admin-users.*'],
                    'icon' => 'users',
                    'children' => [
                        ['label' => 'Guests', 'route' => 'admin.guests.index', 'patterns' => ['admin.guests.*']],
                        ['label' => 'Users', 'route' => $canManageAdmins ? 'admin.admin-users.index' : null, 'patterns' => ['admin.admin-users.*']],
                        ['label' => 'Roles & Permissions', 'route' => null, 'patterns' => []],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Growth',
            'items' => [
                [
                    'type' => 'group',
                    'label' => 'Marketing',
                    'key' => 'marketing',
                    'patterns' => [],
                    'icon' => 'campaign',
                    'children' => [
                        ['label' => 'Offers & Promotions', 'route' => null, 'patterns' => []],
                        ['label' => 'Coupons', 'route' => null, 'patterns' => []],
                        ['label' => 'Banners', 'route' => null, 'patterns' => []],
                    ],
                ],
                [
                    'type' => 'group',
                    'label' => 'Reports & Analytics',
                    'key' => 'reports',
                    'patterns' => [],
                    'icon' => 'chart',
                    'children' => [
                        ['label' => 'Reports', 'route' => null, 'patterns' => []],
                        ['label' => 'Analytics', 'route' => null, 'patterns' => []],
                    ],
                ],
            ],
        ],
        [
            'title' => 'System',
            'items' => [
                [
                    'type' => 'group',
                    'label' => 'Settings',
                    'key' => 'settings',
                    'patterns' => ['admin.settings.*', 'admin.activity-log.*'],
                    'icon' => 'settings',
                    'children' => [
                        ['label' => 'General Settings', 'route' => 'admin.settings.index', 'patterns' => ['admin.settings.*']],
                        ['label' => 'Activity Log', 'route' => 'admin.activity-log.index', 'patterns' => ['admin.activity-log.*']],
                        ['label' => 'System Config', 'route' => null, 'patterns' => []],
                        ['label' => 'Payment Gateways', 'route' => null, 'patterns' => []],
                    ],
                ],
            ],
        ],
    ];

    $icons = [
        'dashboard' => '<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
        'booking' => '<path d="M6 2a1 1 0 011 1v1h10V3a1 1 0 112 0v1h1a2 2 0 012 2v14a2 2 0 01-2 2H4a2 2 0 01-2-2L2 6a2 2 0 012-2h1V3a1 1 0 011-1zm14 8H4v10h16V10z"/>',
        'property' => '<path d="M3 21V4a1 1 0 011-1h10a1 1 0 011 1v17h2v-9h3v9h1v2H2v-2h1zm4-14v2h2V7H7zm0 4v2h2v-2H7zm0 4v2h2v-2H7zm5-8v2h2V7h-2zm0 4v2h2v-2h-2zm0 4v2h2v-2h-2z"/>',
        'users' => '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
        'campaign' => '<path d="M3 11v2h4l5 5V6l-5 5H3zm13.5 1c0-1.77-1-3.29-2.5-4.03v8.05c1.5-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>',
        'chart' => '<path d="M5 9.2h3V19H5V9.2zM10.5 5h3v14h-3V5zM16 12h3v7h-3v-7z"/>',
        'settings' => '<path d="M19.43 12.98c.04-.32.07-.65.07-.98s-.02-.66-.07-.98l2.11-1.65a.5.5 0 00.12-.64l-2-3.46a.5.5 0 00-.6-.22l-2.49 1a7.28 7.28 0 00-1.69-.98L14.5 2.42A.5.5 0 0014 2h-4a.5.5 0 00-.5.42L9.12 5.07c-.61.24-1.18.56-1.69.98l-2.49-1a.5.5 0 00-.6.22l-2 3.46a.5.5 0 00.12.64l2.11 1.65c-.04.32-.08.65-.08.98s.03.66.08.98l-2.11 1.65a.5.5 0 00-.12.64l2 3.46c.14.24.43.34.68.22l2.49-1c.51.4 1.08.73 1.69.98l.38 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.38-2.65c.61-.24 1.18-.56 1.69-.98l2.49 1c.25.11.54.02.68-.22l2-3.46a.5.5 0 00-.12-.64l-2.27-1.65zM12 15.5A3.5 3.5 0 1112 8a3.5 3.5 0 010 7.5z"/>',
    ];
@endphp

<aside id="adminSidebar" class="admin-sidebar">
    <div class="flex h-16 items-center justify-between border-b border-white/10 px-3">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3 text-white no-underline">
            <span class="admin-brand-mark">E</span>
            <span class="admin-brand-text min-w-0">
                <span class="block text-[11px] font-black uppercase tracking-[0.24em] text-sky-300">EENNRA</span>
                <span class="mt-0.5 block truncate text-sm font-black">Property ERP</span>
            </span>
        </a>

        <button id="adminSidebarToggle" type="button" class="admin-sidebar-toggle hidden lg:inline-flex" aria-label="Collapse sidebar">
            <svg class="h-4 w-4 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>
    </div>

    <nav class="admin-nav-scroll flex-1 overflow-y-auto px-2 py-3">
        @foreach ($navGroups as $group)
            <div class="mb-3">
                <p class="admin-nav-group-label px-2 pb-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">{{ $group['title'] }}</p>
                <div class="grid gap-1">
                    @foreach ($group['items'] as $item)
                        @if ($item['type'] === 'link')
                            @php $active = request()->routeIs(...$item['patterns']); @endphp
                            <a href="{{ $url($item['route']) }}" class="admin-nav-link {{ $active ? 'is-active' : '' }}" title="{{ $item['label'] }}">
                                <span class="admin-nav-icon"><svg fill="currentColor" viewBox="0 0 24 24">{!! $icons[$item['icon']] !!}</svg></span>
                                <span class="admin-nav-label">{{ $item['label'] }}</span>
                            </a>
                        @else
                            @php $active = count($item['patterns']) && request()->routeIs(...$item['patterns']); @endphp
                            <div class="admin-nav-group {{ $active ? 'is-open' : '' }}" data-nav-group="{{ $item['key'] }}">
                                <button type="button" class="admin-nav-parent {{ $active ? 'is-active' : '' }}" title="{{ $item['label'] }}">
                                    <span class="admin-nav-icon"><svg fill="currentColor" viewBox="0 0 24 24">{!! $icons[$item['icon']] !!}</svg></span>
                                    <span class="admin-nav-label">{{ $item['label'] }}</span>
                                    <span class="admin-dropdown-arrow">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                                    </span>
                                </button>
                                <div class="admin-nav-children">
                                    @foreach ($item['children'] as $child)
                                        @php $childActive = count($child['patterns']) && request()->routeIs(...$child['patterns']); @endphp
                                        @if ($child['route'])
                                            <a href="{{ $url($child['route']) }}" class="admin-nav-child {{ $childActive ? 'is-active' : '' }}">{{ $child['label'] }}</a>
                                        @else
                                            <span class="admin-nav-child cursor-not-allowed opacity-45">{{ $child['label'] }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-white/10 p-3">
        @auth
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="admin-sidebar-logout" title="Logout">
                    <span class="admin-nav-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H9"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 20H5a2 2 0 01-2-2V6a2 2 0 012-2h8"></path>
                        </svg>
                    </span>
                    <span class="admin-sidebar-logout-text">Logout</span>
                </button>
            </form>
        @endauth
    </div>
</aside>
