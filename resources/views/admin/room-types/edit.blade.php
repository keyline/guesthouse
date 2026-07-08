@extends('admin.layouts.app')

@section('title', 'Edit Room Type')
@section('eyebrow', 'Room Inventory')
@section('page-title', 'Edit '.$roomType->name)

@section('header-actions')
    <a href="{{ route('admin.room-types.show', $roomType) }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">View Room Type</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.room-types.update', $roomType) }}">
        @csrf
        @method('PUT')
        @include('admin.room-types._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.room-types.show', $roomType) }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Save Changes</button>
        </div>
    </form>
@endsection
