@extends('admin.layouts.app')

@section('title', $property->name)
@section('eyebrow', 'Property Profile')
@section('page-title', $property->name)

@section('header-actions')
    <a href="{{ route('admin.properties.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">All Properties</a>
    <a href="{{ route('admin.properties.edit', $property) }}" class="inline-flex h-10 items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-bold text-white hover:bg-sky-700 transition shadow-sm">Edit</a>
@endsection

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[1.4fr_0.8fr]">
        <div class="space-y-6">
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $property->property_type) }}</p>
                        <h2 class="mt-1 text-2xl font-black">{{ $property->name }}</h2>
                        <p class="mt-2 text-sm font-semibold text-slate-500">{{ $property->address }}, {{ $property->city }}, {{ $property->country }}</p>
                    </div>
                    <span class="w-fit rounded-full bg-slate-950 px-3 py-1 text-xs font-black uppercase tracking-wide text-white">{{ $property->status }}</span>
                </div>

                @if ($property->short_description)
                    <p class="mt-5 text-base font-semibold text-slate-700">{{ $property->short_description }}</p>
                @endif

                @if ($property->description)
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $property->description }}</p>
                @endif
            </article>

            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Gallery</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($property->images as $image)
                        <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text ?: $property->name }}" class="aspect-[4/3] w-full rounded-lg border border-slate-200 object-cover">
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 p-6 text-sm font-semibold text-slate-500 sm:col-span-2 xl:col-span-3">
                            No images uploaded yet.
                        </div>
                    @endforelse
                </div>
            </article>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Operating Details</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="font-semibold text-slate-500">Check-in</dt>
                        <dd class="font-black">{{ sprintf('%02d:%02d', intdiv($property->check_in_time_minutes, 60), $property->check_in_time_minutes % 60) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="font-semibold text-slate-500">Check-out</dt>
                        <dd class="font-black">{{ sprintf('%02d:%02d', intdiv($property->check_out_time_minutes, 60), $property->check_out_time_minutes % 60) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="font-semibold text-slate-500">Manager</dt>
                        <dd class="font-black">{{ $property->manager_name ?: 'Not assigned' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Contact</h2>
                <div class="mt-4 space-y-2 text-sm font-semibold text-slate-600">
                    <p>{{ $property->phone ?: 'No phone added' }}</p>
                    <p>{{ $property->email ?: 'No email added' }}</p>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Amenities</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($property->amenities as $amenity)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-700">{{ $amenity->name }}</span>
                    @empty
                        <span class="text-sm font-semibold text-slate-500">No amenities added yet.</span>
                    @endforelse
                </div>
            </section>

            <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" class="rounded-lg border border-rose-200 bg-rose-50 p-5">
                @csrf
                @method('DELETE')
                <h2 class="text-lg font-black text-rose-950">Archive Property</h2>
                <p class="mt-2 text-sm font-semibold text-rose-700">Deleting removes the property record from the admin system.</p>
                <button class="mt-4 h-10 rounded-lg bg-rose-700 px-4 text-sm font-bold text-white" onclick="return confirm('Delete this property?')">Delete Property</button>
            </form>
        </aside>
    </section>
@endsection
