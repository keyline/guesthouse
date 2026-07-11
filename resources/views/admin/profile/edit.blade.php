@extends('admin.layouts.app')

@section('title', 'Edit Profile')
@section('eyebrow', 'Account')
@section('page-title', 'Edit Profile')

@section('header-actions')
    <a href="{{ route('admin.dashboard') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">Back to Dashboard</a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-black">Profile was not saved. Please fix these fields:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <section class="rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Personal Details</h3>
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
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Security</h3>
            </div>
            <div class="grid gap-4 p-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-600">New Password</label>
                    <input name="password" type="password" autocomplete="new-password" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600">
                    <p class="mt-1 text-xs font-semibold text-slate-500">Leave blank to keep your current password.</p>
                    @error('password')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600">Confirm New Password</label>
                    <input name="password_confirmation" type="password" autocomplete="new-password" class="mt-1 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600">
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Access</h3>
            </div>
            <div class="grid gap-4 p-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-600">Role</label>
                    <div class="mt-1 flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-700">
                        {{ $adminUser->hasRole(\App\Models\User::ROLE_SUPER_ADMIN) ? 'Super Admin' : 'Property Manager' }}
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600">Property Access</label>
                    <div class="mt-1 min-h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700">
                        @if ($adminUser->hasRole(\App\Models\User::ROLE_SUPER_ADMIN))
                            All Properties
                        @elseif ($adminUser->managedProperties->isNotEmpty())
                            {{ $adminUser->managedProperties->pluck('name')->join(', ') }}
                        @else
                            No Property Assigned
                        @endif
                    </div>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Access is managed by a super admin and cannot be edited here.</p>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white transition hover:bg-sky-700">Save Changes</button>
        </div>
    </form>

    @if (session('status'))
        @include('admin.partials.success-modal', ['message' => session('status')])
    @endif
@endsection
