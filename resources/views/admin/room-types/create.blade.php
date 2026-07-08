@extends('admin.layouts.app')

@section('title', 'New Room Type')
@section('eyebrow', 'Room Inventory')
@section('page-title', 'Add Room Type')

@section('header-actions')
    <a href="{{ route('admin.room-types.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">Back to Room Types</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.room-types.store') }}">
        @csrf
        @include('admin.room-types._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.room-types.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Create Room Type</button>
        </div>
    </form>
@endsection
