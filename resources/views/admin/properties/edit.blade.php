@extends('admin.layouts.app')

@section('title', 'Edit Property')
@section('eyebrow', 'Property Management')
@section('page-title', 'Edit '.$property->name)

@section('header-actions')
    <a href="{{ route('admin.properties.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">Back to Properties</a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-black">Property was not saved. Please fix these fields:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.properties.update', $property) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.properties._form')
        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('admin.properties.index') }}" class="rounded bg-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-300 transition">Cancel</a>
            <button class="rounded bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Save Changes</button>
        </div>
    </form>

    @if (session('status'))
        @include('admin.partials.success-modal', ['message' => session('status')])
    @endif
@endsection
