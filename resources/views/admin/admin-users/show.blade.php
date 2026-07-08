@extends('admin.layouts.app')

@section('title', $adminUser->name)
@section('eyebrow', 'Access Control')
@section('page-title', $adminUser->name)

@section('header-actions')
    <a href="{{ route('admin.admin-users.index') }}" class="border-slate-300 bg-white text-slate-700">All Admin Users</a>
    <a href="{{ route('admin.admin-users.edit', $adminUser) }}">Edit Admin User</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-[1fr_320px]">
        <section class="rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Account Summary</h3>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Name</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $adminUser->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Email</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $adminUser->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Phone</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $adminUser->phone ?: 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Role</p>
                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $roles[$adminUser->role] ?? $adminUser->role }}</p>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Status</p>
                    <span class="mt-1 inline-flex rounded px-2 py-1 text-xs font-black {{ $adminUser->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                        {{ $adminUser->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </section>

        <aside class="rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Property Access</h3>
            </div>
            <div class="p-5">
                @if ($adminUser->role === \App\Models\User::ROLE_SUPER_ADMIN)
                    <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-bold text-sky-800">
                        Super admin has access to all properties.
                    </div>
                @elseif ($adminUser->managedProperties->isNotEmpty())
                    <div class="grid gap-2">
                        @foreach ($adminUser->managedProperties as $property)
                            <a href="{{ route('admin.properties.show', $property) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:border-sky-300 hover:bg-sky-50">
                                {{ $property->name }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">
                        No properties assigned.
                    </div>
                @endif

                @if (! $adminUser->is(auth()->user()))
                    <form method="POST" action="{{ route('admin.admin-users.destroy', $adminUser) }}" class="mt-5 rounded-lg border border-rose-200 bg-rose-50 p-4">
                        @csrf
                        @method('DELETE')
                        <p class="text-sm font-black text-rose-800">Deactivate Admin User</p>
                        <p class="mt-1 text-xs font-semibold text-rose-700">This keeps history intact but removes login access.</p>
                        <button class="mt-3 rounded bg-rose-600 px-3 py-2 text-sm font-bold text-white hover:bg-rose-700">Deactivate</button>
                    </form>
                @endif
            </div>
        </aside>
    </div>
@endsection
