<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Book Your Stay') | {{ config('app.name') }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4">
            <a href="{{ url('/') }}" class="text-sm font-black uppercase tracking-[0.2em] text-sky-700">EENNRA <span class="text-slate-400">Guest Houses</span></a>
            <nav class="flex items-center gap-4 text-sm font-bold text-slate-600">
                <a href="{{ route('book.search') }}" class="hover:text-sky-700">Book a Stay</a>
                @auth
                    <a href="{{ route('customer.dashboard') }}" class="hover:text-sky-700">My Bookings</a>
                @else
                    <a href="{{ route('customer.login') }}" class="hover:text-sky-700">Sign in</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs font-semibold text-slate-500">
        Bookings are confirmed by the property. Need help? Call +91 98300 98300.
    </footer>
</body>
</html>
