@extends('admin.layouts.app')

@section('title', 'New Room')
@section('eyebrow', 'Room Inventory')
@section('page-title', 'Add Room')

@section('header-actions')
    <a href="{{ route('admin.rooms.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">Back to Rooms</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.rooms.store') }}">
        @csrf
        @include('admin.rooms._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.rooms.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Create Room</button>
        </div>
    </form>
@endsection
