@extends('admin.layouts.app')

@section('title', 'Edit Admin User')
@section('eyebrow', 'Access Control')
@section('page-title', 'Edit '.$adminUser->name)

@section('header-actions')
    <a href="{{ route('admin.admin-users.show', $adminUser) }}" class="border-slate-300 bg-white text-slate-700">View Admin User</a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-black">Admin user was not saved. Please fix these fields:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.admin-users.update', $adminUser) }}">
        @csrf
        @method('PUT')
        @include('admin.admin-users._form')

        <div class="mt-5 flex justify-end gap-3">
            <a href="{{ route('admin.admin-users.show', $adminUser) }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Save Changes</button>
        </div>
    </form>
@endsection
