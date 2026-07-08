@extends('admin.layouts.app')

@section('title', 'Admin Users')
@section('eyebrow', 'Access Control')
@section('page-title', 'Admin Users')

@section('header-actions')
    <a href="{{ route('admin.admin-users.create') }}">+ Add Admin User</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 border border-slate-200 bg-white p-3">
        <form method="GET" action="{{ route('admin.admin-users.index') }}" class="grid gap-2 lg:grid-cols-[1fr_220px_180px_auto]">
            <div>
                <label class="block text-xs font-bold text-slate-600">Search</label>
                <input name="search" value="{{ request('search') }}" placeholder="Name or email..." class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600">Role</label>
                <select name="role" class="mt-1 h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="">All roles</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600">Status</label>
                <select name="status" class="mt-1 h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white hover:bg-slate-800 transition">Filter</button>
                <a href="{{ route('admin.admin-users.index') }}" class="rounded bg-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-300 transition">Reset</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden border border-slate-200 bg-white">
        <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Properties</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($adminUsers as $adminUser)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-black text-slate-900">{{ $adminUser->name }}</p>
                            <p class="text-xs font-semibold text-slate-500">{{ $adminUser->email }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm font-bold text-slate-700">{{ $roles[$adminUser->role] ?? $adminUser->role }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            @if ($adminUser->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                <span class="font-bold text-slate-800">All Properties</span>
                            @elseif ($adminUser->managedProperties->isNotEmpty())
                                {{ $adminUser->managedProperties->pluck('name')->take(3)->join(', ') }}
                                @if ($adminUser->managedProperties->count() > 3)
                                    <span class="font-bold">+{{ $adminUser->managedProperties->count() - 3 }} more</span>
                                @endif
                            @else
                                <span class="text-amber-700">No property assigned</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-1 text-xs font-black {{ $adminUser->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $adminUser->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.admin-users.show', $adminUser) }}" class="rounded bg-sky-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-sky-700 transition">View</a>
                                <a href="{{ route('admin.admin-users.edit', $adminUser) }}" class="rounded bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800 transition">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center">
                            <p class="text-sm font-semibold text-slate-600">No admin users found</p>
                            <a href="{{ route('admin.admin-users.create') }}" class="mt-3 inline-block rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white hover:bg-slate-800">Create Admin User</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $adminUsers->links() }}
    </div>
@endsection
