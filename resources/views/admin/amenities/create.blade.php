@extends('admin.layouts.app')

@section('title', 'New Amenity')
@section('eyebrow', 'Master Data')
@section('page-title', 'Add Amenity')

@section('header-actions')
    <a href="{{ route('admin.amenities.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">Back to Amenities</a>
    <button type="submit" form="amenity-form" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">Create Amenity</button>
@endsection

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-black">Amenity was not saved. Please fix these fields:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="amenity-form" method="POST" action="{{ route('admin.amenities.store') }}">
        @csrf
        @include('admin.amenities._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.amenities.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 text-sm font-bold text-white transition hover:bg-sky-700">Create Amenity</button>
        </div>
    </form>
@endsection
