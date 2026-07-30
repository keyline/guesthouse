@extends('admin.layouts.app')

@section('title', 'Add Company')
@section('eyebrow', 'People')
@section('page-title', 'Add Company')

@section('header-actions')
    <a href="{{ route('admin.corporates.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">Back</a>
    <button type="submit" form="corporate-form" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">Create Company</button>
@endsection

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-black">Not saved yet. Please fix these fields:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="corporate-form" method="POST" action="{{ route('admin.corporates.store') }}">
        @csrf
        @include('admin.corporates._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.corporates.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 text-sm font-bold text-white transition hover:bg-sky-700">Create Company</button>
        </div>
    </form>
@endsection
