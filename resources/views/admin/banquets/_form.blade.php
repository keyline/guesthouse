@php
    $selectedAmenityIds = collect(old('amenities', $selectedAmenityIds ?? []))->map(fn ($id) => (int) $id)->all();
    $amenityIconLibrary = $amenityIconLibrary ?? \App\Support\AmenityIconLibrary::all();
@endphp

<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-black">Banquet Details</h2>
    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <div>
            <label class="text-sm font-bold text-slate-700">Property</label>
            @if($propertyContext)
                <div class="mt-2 flex h-11 items-center justify-between rounded-lg border border-sky-200 bg-sky-50 px-3">
                    <span class="min-w-0 truncate text-sm font-black text-sky-900">🏨 {{ $propertyContext->name }}</span>
                    <span class="ml-2 shrink-0 rounded-full bg-white px-2 py-1 text-[9px] font-black uppercase tracking-wide text-sky-700 ring-1 ring-sky-200">{{ $banquet->exists ? 'Assigned' : 'Top selection' }}</span>
                </div>
                <p class="mt-1 text-[10px] font-semibold text-slate-500">{{ $banquet->exists ? 'Property assignment is fixed to protect existing pricing and bookings.' : 'Taken from the property selected in the top banner.' }}</p>
            @else
                <div class="mt-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800">Select one property from the top banner before creating this banquet.</div>
            @endif
            @error('property_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="name" class="text-sm font-bold text-slate-700">Banquet Name</label>
            <input id="name" name="name" value="{{ old('name', $banquet->name) }}" placeholder="e.g., Maharaja Hall" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
            @error('name')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <label for="description" class="text-sm font-bold text-slate-700">Description</label>
            <textarea id="description" name="description" rows="3" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-950">{{ old('description', $banquet->description) }}</textarea>
            @error('description')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="capacity_min" class="text-sm font-bold text-slate-700">Minimum Capacity</label>
            <input id="capacity_min" name="capacity_min" type="number" inputmode="numeric" min="1" step="1" value="{{ old('capacity_min') !== null ? old('capacity_min') : ($banquet->capacity_min ?: 50) }}" placeholder="50" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
            @error('capacity_min')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="capacity_max" class="text-sm font-bold text-slate-700">Maximum Capacity</label>
            <input id="capacity_max" name="capacity_max" type="number" inputmode="numeric" min="1" step="1" value="{{ old('capacity_max') !== null ? old('capacity_max') : ($banquet->capacity_max ?: 500) }}" placeholder="500" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
            @error('capacity_max')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="base_price_minor" class="text-sm font-bold text-slate-700">Base Price per Person (₹)</label>
            <input id="base_price_minor" name="base_price_minor" type="number" inputmode="numeric" min="0" step="1" value="{{ old('base_price_minor') !== null ? old('base_price_minor') : ($banquet->base_price_minor ?: 5000) }}" placeholder="5000" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
            @error('base_price_minor')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>
        <input type="hidden" name="currency" value="INR">

        <div>
            <label for="status" class="text-sm font-bold text-slate-700">Status</label>
            <select id="status" name="status" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $banquet->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>
    </div>
</section>

<section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-black">Setup Types</h2>
    <div class="mt-5 grid gap-4 sm:grid-cols-2 md:grid-cols-3">
        @php $selectedSetupTypes = old('setup_types', $banquet->setup_types ?? []) @endphp
        @foreach($setupTypes as $key => $label)
            <label class="flex items-center gap-3 rounded-lg border border-slate-300 p-3 cursor-pointer hover:bg-sky-50 transition">
                <input type="checkbox" name="setup_types[]" value="{{ $key }}" @checked(in_array($key, $selectedSetupTypes)) class="rounded">
                <span class="text-sm font-bold text-slate-700">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @error('setup_types')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
</section>

<section class="mt-6 border border-slate-200 bg-white">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Amenities</h3>
    </div>
    <div class="p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-xs font-bold text-slate-700">Select banquet amenities from master list</p>
                <p class="mt-0.5 text-[11px] font-semibold text-slate-500">These amenities describe the features available for this banquet hall.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-slate-600">Master data</span>
        </div>

        @if (($amenities ?? collect())->isNotEmpty())
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($amenities as $amenity)
                    @php
                        $isSelected = in_array((int) $amenity->id, $selectedAmenityIds, true);
                        $iconPath = $amenityIconLibrary[$amenity->icon]['path'] ?? $amenityIconLibrary['banquet']['path'];
                    @endphp
                    <label class="group relative cursor-pointer">
                        <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}" class="peer sr-only" @checked($isSelected)>
                        <span class="flex min-h-12 items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm transition peer-checked:border-sky-300 peer-checked:bg-sky-50 peer-checked:ring-1 peer-checked:ring-sky-200 hover:border-sky-200 hover:bg-slate-50">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-600 transition peer-checked:bg-sky-600 peer-checked:text-white group-hover:bg-sky-100 group-hover:text-sky-700">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="{{ $iconPath }}"></path>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-xs font-black text-slate-800">{{ $amenity->name }}</span>
                                <span class="block truncate text-[11px] font-semibold capitalize text-slate-500">{{ str_replace('_', ' ', $amenity->category) }}</span>
                            </span>
                        </span>
                        <span class="pointer-events-none absolute right-2 top-2 hidden h-5 w-5 place-items-center rounded-full bg-sky-600 text-white shadow-sm peer-checked:grid">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </span>
                    </label>
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm font-semibold text-slate-500">
                No amenities found in the master table yet.
            </div>
        @endif

        @error('amenities')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        @error('amenities.*')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
    </div>
</section>

<section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-black">Banquet Photos</h2>
    <div class="mt-5">
        <input id="banquet_images" type="file" name="banquet_images[]" multiple accept="image/*" class="hidden" />
        <button type="button" id="add_photo_btn" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Photos
        </button>
        <p class="mt-2 text-sm text-slate-500">Supported formats: JPG, PNG, GIF, WebP (Max 5MB each)</p>
    </div>

    <div id="photo_previews" class="mt-6">
        @if ($banquet->images->count())
            <div class="mb-6">
                <h3 class="text-sm font-bold text-slate-700 mb-3">Current Photos ({{ $banquet->images->count() }})</h3>
                <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($banquet->images as $image)
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
    const fileInput = document.getElementById('banquet_images');
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
                fetch(`/admin/banquet-images/${imageId}`, {
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
