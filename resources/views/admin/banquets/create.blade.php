@extends('admin.layouts.app')

@section('title', 'New Banquet')
@section('eyebrow', 'Inventory')
@section('page-title', 'Add Banquet')

@section('header-actions')
    <a href="{{ route('admin.banquets.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">Back to Banquets</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.banquets.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.banquets._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.banquets.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Create Banquet</button>
        </div>
    </form>

    @if (session('status'))
        @include('admin.partials.success-modal', ['message' => session('status')])
    @endif
@endsection
