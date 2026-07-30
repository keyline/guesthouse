@extends('admin.layouts.app')

@section('title', 'Edit '.($discount->isCoupon() ? 'Coupon' : 'Offer'))
@section('eyebrow', 'Marketing')
@section('page-title', 'Edit: '.$discount->name)

@section('header-actions')
    <a href="{{ route('admin.discounts.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">Back</a>
    <button type="submit" form="discount-form" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-sky-700">Save changes</button>
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

    @if ($discount->times_used > 0)
        <div class="mb-4 rounded border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800">
            Used {{ $discount->times_used }} {{ Str::plural('time', $discount->times_used) }} so far. Existing bookings keep the price they were given.
        </div>
    @endif

    <form id="discount-form" method="POST" action="{{ route('admin.discounts.update', $discount) }}">
        @csrf
        @method('PUT')
        @include('admin.discounts._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.discounts.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 text-sm font-bold text-white transition hover:bg-sky-700">Save changes</button>
        </div>
    </form>
@endsection
