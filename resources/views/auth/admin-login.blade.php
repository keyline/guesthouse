<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | {{ config('app.name', 'Hotel Chain Manager') }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-950 text-white antialiased">
    <main class="grid min-h-screen lg:grid-cols-[1fr_460px]">
        <section class="hidden bg-[radial-gradient(circle_at_top_left,#2563eb,transparent_32%),linear-gradient(135deg,#020617,#0f172a)] p-10 lg:flex lg:flex-col lg:justify-between">
            <a href="{{ url('/') }}" class="text-sm font-bold uppercase tracking-[0.25em] text-sky-300">Hotel Chain Manager</a>
            <div>
                <p class="text-sm font-semibold text-sky-200">Admin Command Desk</p>
                <h1 class="mt-4 max-w-2xl text-5xl font-black tracking-tight">Secure operations for every property, room, and guest.</h1>
                <p class="mt-5 max-w-xl text-lg text-slate-300">Manage bookings, availability, payments, and service missions from one protected console.</p>
            </div>
            <p class="text-sm text-slate-400">Use authorized staff accounts only.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10">
            <div class="w-full max-w-md rounded-lg border border-white/10 bg-white p-6 text-slate-950 shadow-2xl">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-slate-500">Admin Login</p>
                    <h2 class="mt-2 text-3xl font-black">Welcome back</h2>
                </div>

                <form method="POST" action="{{ route('admin.login.store') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="text-sm font-bold text-slate-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
                        @error('email')
                            <p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="text-sm font-bold text-slate-700">Password</label>
                        <input id="password" name="password" type="password" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
                        @error('password')
                            <p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                        Remember this device
                    </label>

                    <button class="h-11 w-full rounded-lg bg-slate-950 text-sm font-black text-white">Sign in to admin</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
