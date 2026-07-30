@php
    $floorOptions = \App\Support\FloorOptions::all();
    $currentFloor = old('floor', $room->floor);
    $labelCls = 'text-[11px] font-black uppercase tracking-wide text-slate-500';
    $inputCls = 'mt-1.5 h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100';
@endphp
<section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 border-b border-slate-100 pb-3">
        <h2 class="text-base font-black text-slate-900">Room Details</h2>
        @if ($selectedProperty)
            <span class="font-bold text-slate-300">·</span>
            <span class="text-base font-black text-sky-700">{{ $selectedProperty->name }}</span>
            @php
                $propertyAddress = collect([
                    $selectedProperty->location,
                    $selectedProperty->city,
                    $selectedProperty->state,
                ])->filter(fn ($part) => filled($part))->unique()->join(', ');
            @endphp
            @if ($propertyAddress)
                <span class="text-xs font-semibold text-slate-400">{{ $propertyAddress }}</span>
            @endif
        @else
            <span class="rounded-md bg-amber-50 px-2 py-1 text-xs font-black text-amber-800">Select a property from the top banner</span>
        @endif
    </div>
    <input type="hidden" name="property_id" value="{{ $selectedProperty?->id }}">
    @error('property_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
    <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div>
            <label for="room_type_id" class="{{ $labelCls }}">Room type</label>
            <select id="room_type_id" name="room_type_id" required class="{{ $inputCls }} bg-white">
                <option value="">Select room type</option>
                @foreach ($roomTypes as $id => $name)
                    <option value="{{ $id }}" @selected((int) old('room_type_id', request('room_type_id', $room->room_type_id)) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            @error('room_type_id')<p class="mt-1.5 text-xs font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="room_number" class="{{ $labelCls }}">Room Number / Room Name</label>
            <input id="room_number" name="room_number" value="{{ old('room_number', $room->room_number) }}" required placeholder="101" class="{{ $inputCls }}">
            @error('room_number')<p class="mt-1.5 text-xs font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="floor" class="{{ $labelCls }}">Floor</label>
            <select id="floor" name="floor" class="{{ $inputCls }} bg-white">
                <option value="">Select floor</option>
                @foreach ($floorOptions as $floor)
                    <option value="{{ $floor }}" @selected($currentFloor === $floor)>{{ $floor }}</option>
                @endforeach
                @if ($currentFloor && ! in_array($currentFloor, $floorOptions, true))
                    <option value="{{ $currentFloor }}" selected>{{ $currentFloor }} (current)</option>
                @endif
            </select>
            @error('floor')<p class="mt-1.5 text-xs font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="status" class="{{ $labelCls }}">Status</label>
            <select id="status" name="status" class="{{ $inputCls }} bg-white">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $room->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap items-center gap-2 md:col-span-2">
            <label class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700">
                <input type="checkbox" name="is_smoking" value="1" @checked(old('is_smoking', $room->is_smoking))>
                🚬 Smoking
            </label>
            <label class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700">
                <input type="checkbox" name="is_accessible" value="1" @checked(old('is_accessible', $room->is_accessible))>
                ♿ Accessible
            </label>
        </div>

        <div class="md:col-span-2">
            <label for="notes" class="{{ $labelCls }}">Internal notes</label>
            <textarea id="notes" name="notes" rows="3" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100">{{ old('notes', $room->notes) }}</textarea>
        </div>
    </div>
</section>

<section class="mt-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><h2 class="text-sm font-black">Room amenity exceptions</h2><p class="mt-0.5 text-[11px] font-semibold text-slate-500">Amenities come from the room category. Change a value only when this physical room is different.</p></div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($room->room_type_id)
                <a href="{{ route('admin.room-types.show', $room->room_type_id) }}#property-config-{{ $selectedProperty?->id }}" class="inline-flex h-8 items-center rounded-md bg-sky-600 px-3 text-[11px] font-black text-white transition hover:bg-sky-700">Manage category amenities</a>
            @else
                <a href="{{ route('admin.room-types.index') }}" class="inline-flex h-8 items-center rounded-md bg-sky-600 px-3 text-[11px] font-black text-white transition hover:bg-sky-700">Choose a room type first</a>
            @endif
            <a href="{{ route('admin.amenities.index') }}" class="inline-flex h-8 items-center rounded-md border border-slate-300 bg-white px-3 text-[11px] font-black text-slate-700 transition hover:bg-slate-50">Amenity master</a>
        </div>
    </div>
    <div class="mt-3 overflow-x-auto"><table class="w-full text-left"><thead><tr class="border-b border-slate-200 text-[9px] uppercase tracking-wide text-slate-400"><th class="py-2">Room amenity</th><th class="w-72">Status for this room</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($amenities as $amenity) @php($override=$amenityOverrides->get($amenity->id)) @php($isInherited=in_array((int)$amenity->id,$inheritedAmenityIds ?? [],true))
        <tr><td class="py-2 text-xs font-bold text-slate-700">{{ $amenity->name }} <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase text-slate-500">{{ $isInherited ? 'Included by category' : 'Optional room feature' }}</span></td>
            <td class="py-1.5"><select name="amenity_state[{{ $amenity->id }}]" aria-label="{{ $amenity->name }} status" class="h-8 w-full rounded-md border border-slate-300 bg-white px-2 text-[11px] font-bold text-slate-700">
                <option value="inherit" @selected(! $override)>{{ $isInherited ? 'Use category default — Available' : 'Use default — Not included' }}</option>
                <option value="present" @selected($override?->state === 'present')>Available in this room</option>
                <option value="missing" @selected($override?->state === 'missing')>Not available in this room</option>
            </select></td></tr>
        @empty<tr><td colspan="2" class="py-4 text-center text-xs text-slate-500">No room amenities are configured for this room category. Add them from Room Types.</td></tr>@endforelse
    </tbody></table></div>
    <p class="mt-2 text-[10px] font-semibold text-slate-500">Need another room amenity? Add it in Amenity master, then select it under Manage category amenities. Parking, lift, pool and other shared facilities remain on the Property page.</p>
</section>

<section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-black">Room Photos</h2>

    <div class="mt-5">
        <input id="room_images" type="file" name="room_images[]" multiple accept="image/*" class="hidden" />
        <button type="button" id="add_photo_btn" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Photo
        </button>
        <p class="mt-2 text-sm text-slate-500">Supported formats: JPG, PNG, GIF, WebP (Max 5MB each)</p>
    </div>

    <div id="photo_previews" class="mt-6">
        @if ($room->images->count())
            <div class="mb-6">
                <h3 class="text-sm font-bold text-slate-700 mb-3">Current Photos ({{ $room->images->count() }})</h3>
                <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($room->images as $image)
                        <div class="group relative rounded-lg border border-slate-300 overflow-hidden bg-slate-100" data-existing-image="{{ $image->id }}">
                            <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $image->alt_text }}" class="h-40 w-full object-cover">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <button type="button" class="delete-existing-image bg-red-600 hover:bg-red-700 text-white rounded-full p-2" data-image-id="{{ $image->id }}">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="absolute bottom-0 left-0 right-0 bg-black/75 text-white text-xs p-2">
                                <p class="truncate">{{ basename($image->path) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div id="new_photo_previews" class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"></div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addPhotoBtn = document.getElementById('add_photo_btn');
    const fileInput = document.getElementById('room_images');
    const newPhotoPreviews = document.getElementById('new_photo_previews');

    addPhotoBtn.addEventListener('click', function(e) {
        e.preventDefault();
        fileInput.click();
    });

    fileInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);

        files.forEach((file, index) => {
            if (!file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    const previewId = 'preview_' + Date.now() + '_' + index;

                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'group relative rounded-lg border border-slate-300 overflow-hidden bg-slate-100';
                    previewDiv.id = previewId;
                    previewDiv.innerHTML = `
                        <img src="${event.target.result}" alt="Preview" class="h-40 w-full object-cover">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <button type="button" class="delete-new-image bg-red-600 hover:bg-red-700 text-white rounded-full p-2">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 bg-black/75 text-white text-xs p-2">
                            <p class="truncate">${file.name}</p>
                            <p class="text-slate-300">${img.width} × ${img.height} · ${fileSize}MB</p>
                        </div>
                    `;

                    previewDiv.querySelector('.delete-new-image').addEventListener('click', function(e) {
                        e.preventDefault();
                        previewDiv.remove();
                    });

                    newPhotoPreviews.appendChild(previewDiv);
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });
    });

    // Delete existing image
    document.querySelectorAll('.delete-existing-image').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const imageId = this.dataset.imageId;
            const container = this.closest('[data-existing-image]');

            if (confirm('Delete this photo?')) {
                const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
                fetch(`/admin/room-images/${imageId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    }
                }).then(response => {
                    if (response.ok) {
                        container.remove();
                    }
                });
            }
        });
    });
});
</script>
