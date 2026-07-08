@extends('admin.layouts.app')

@section('title', 'New Booking')
@section('eyebrow', 'Booking Desk')
@section('page-title', 'Create Booking')

@section('header-actions')
    <a href="{{ route('admin.availability.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Availability</a>
    <a href="{{ route('admin.bookings.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Back to Bookings</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.bookings.store') }}">
        @csrf
        @include('admin.bookings._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.bookings.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Create Booking</button>
        </div>
    </form>
@endsection
