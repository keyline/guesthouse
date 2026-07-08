@extends('admin.layouts.app')

@section('title', 'New Guest')
@section('eyebrow', 'Guest CRM')
@section('page-title', 'Add Guest')

@section('header-actions')
    <a href="{{ route('admin.guests.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Back to Guests</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.guests.store') }}">
        @csrf
        @include('admin.guests._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.guests.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Create Guest</button>
        </div>
    </form>
@endsection
