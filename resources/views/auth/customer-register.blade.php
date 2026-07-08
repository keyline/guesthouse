<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account | {{ config('app.name', 'Hotel Chain Manager') }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
    <main class="flex min-h-screen items-center justify-center px-5 py-10">
        <div class="w-full max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <a href="{{ url('/') }}" class="text-sm font-bold text-slate-500">Hotel Chain Manager</a>
            <h1 class="mt-3 text-3xl font-black">Create Guest Account</h1>
            <p class="mt-2 text-sm text-slate-500">Use this account to manage bookings and payments.</p>

            <form method="POST" action="{{ route('customer.register.store') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="name" class="text-sm font-bold text-slate-700">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
                    @error('name')
                        <p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="text-sm font-bold text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
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

                <div>
                    <label for="password_confirmation" class="text-sm font-bold text-slate-700">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
                </div>

                <button class="h-11 w-full rounded-lg bg-slate-950 text-sm font-black text-white">Create account</button>
            </form>

            <p class="mt-5 text-center text-sm text-slate-600">
                Already registered?
                <a href="{{ route('customer.login') }}" class="font-bold text-slate-950">Sign in</a>
            </p>
        </div>
    </main>
</body>
</html>
