@php
    $checkIn = sprintf('%02d:%02d', intdiv((int) $property->check_in_time_minutes, 60), ((int) $property->check_in_time_minutes) % 60);
    $checkOut = sprintf('%02d:%02d', intdiv((int) $property->check_out_time_minutes, 60), ((int) $property->check_out_time_minutes) % 60);
    $selectedAmenityIds = collect(old('amenities', $selectedAmenityIds ?? []))->map(fn ($id) => (int) $id)->all();
    $amenityIconLibrary = $amenityIconLibrary ?? \App\Support\AmenityIconLibrary::all();
@endphp

<div class="space-y-3">
    <!-- BASIC INFORMATION -->
    <section class="border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Basic Information</h3>
        </div>
        <div class="p-4">
            <div class="grid gap-3 md:grid-cols-4">
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-600">Property Name *</label>
                    <input name="name" value="{{ old('name', $property->name) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600">Type</label>
                    <select name="property_type" class="mt-1 h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('property_type', $property->property_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600">Status</label>
                    <select name="status" class="mt-1 h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $property->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600">Sort Order</label>
                    <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $property->sort_order ?? 0) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600">Home Page</label>
                    <label class="mt-1 inline-flex h-9 items-center gap-2 rounded border border-slate-300 bg-white px-2.5 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="show_on_home" value="1" @checked(old('show_on_home', $property->show_on_home)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-600">
                        Show on home page
                    </label>
                </div>
            </div>
        </div>
    </section>

    <!-- LOCATION -->
    <section class="border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Location</h3>
        </div>
        <div class="p-4">
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_390px]">
                <div class="grid gap-3 md:grid-cols-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600">City *</label>
                        <input name="city" value="{{ old('city', $property->city) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                        @error('city')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600">State</label>
                        <input name="state" value="{{ old('state', $property->state) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600">Country *</label>
                        <input name="country" value="{{ old('country', $property->country ?: 'India') }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600">Postal Code</label>
                        <input name="postal_code" value="{{ old('postal_code', $property->postal_code) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-xs font-bold text-slate-600">Search Location</label>
                        <input name="location" value="{{ old('location', $property->location) }}" placeholder="Golpark, Kolkata" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                        <p class="mt-1 text-[11px] font-semibold text-slate-500">This text appears in the home page location dropdown.</p>
                        @error('location')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-xs font-bold text-slate-600">Address *</label>
                        <textarea name="address" rows="5" class="mt-1 w-full rounded border border-slate-300 px-2.5 py-1.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">{{ old('address', $property->address) }}</textarea>
                        @error('address')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <aside class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-700">Google Map</h4>
                            <p class="mt-0.5 text-[11px] font-semibold text-slate-500">Auto marker from property address</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-black text-emerald-700 ring-1 ring-emerald-100">Live</span>
                    </div>

                    <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-inner">
                        <iframe
                            id="property-map-preview"
                            title="Property location on Google Maps"
                            class="h-52 w-full"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                    <p id="property-map-address" class="mt-2 min-h-8 text-xs font-semibold leading-4 text-slate-600">
                        Enter address details to preview the map marker.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button id="property-map-refresh" type="button" class="inline-flex h-8 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">
                            Refresh map
                        </button>
                        <a id="property-map-open" href="https://www.google.com/maps" target="_blank" rel="noopener" class="inline-flex h-8 items-center rounded-lg bg-slate-900 px-3 text-xs font-black text-white shadow-sm transition hover:bg-sky-700">
                            Open Google Maps
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <!-- CONTACT & OPERATIONS -->
    <section class="border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Contact & Operations</h3>
        </div>
        <div class="p-4">
            <div class="grid gap-3 md:grid-cols-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600">Phone</label>
                    <input name="phone" value="{{ old('phone', $property->phone) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600">Email</label>
                    <input name="email" type="email" value="{{ old('email', $property->email) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600">Manager Name</label>
                    <input name="manager_name" value="{{ old('manager_name', $property->manager_name) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>

                <div></div>

                <div>
                    <label class="block text-xs font-bold text-slate-600">Check-in Time</label>
                    <input name="check_in_time" type="time" value="{{ old('check_in_time', $checkIn) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600">Check-out Time</label>
                    <input name="check_out_time" type="time" value="{{ old('check_out_time', $checkOut) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>

                <div></div>

                <div></div>
            </div>
        </div>
    </section>

    <!-- AMENITIES -->
    <section class="border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Amenities</h3>
        </div>
        <div class="p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-xs font-bold text-slate-700">Select property amenities from master list</p>
                    <p class="mt-0.5 text-[11px] font-semibold text-slate-500">Shared property facilities and services only. Configure in-room amenities separately under each Room Type property card.</p>
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

            @error('amenities.*')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    </section>

    <!-- DESCRIPTION -->
    <section class="border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Description</h3>
        </div>
        <div class="p-4">
            <div class="grid gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-600">Full Description</label>
                    <textarea name="description" rows="3" class="mt-1 w-full rounded border border-slate-300 px-2.5 py-1.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">{{ old('description', $property->description) }}</textarea>
                </div>
            </div>
        </div>
    </section>

    <!-- IMAGES -->
    <section class="border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Images</h3>
        </div>
        <div class="p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <label for="property-images-input" class="block text-xs font-bold text-slate-600">Upload Images</label>
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG, WEBP. Max 4MB each, up to 8 images.</p>
                </div>
                <label for="property-images-input" class="inline-flex h-9 cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">
                    Choose Images
                </label>
            </div>
            <input id="property-images-input" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">

            @if ($property->exists && $property->images->isNotEmpty())
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($property->images as $image)
                        <div class="group relative flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-2 shadow-sm" data-property-image-card>
                            <div class="grid h-16 w-20 shrink-0 place-items-center overflow-hidden rounded-md border border-slate-200 bg-white">
                                <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text ?: $property->name }}" class="h-full w-full object-cover">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-black text-slate-800">{{ $image->alt_text ?: $property->name }}</p>
                                <p class="mt-0.5 text-[11px] font-bold text-slate-500">{{ $image->is_primary ? 'Primary image' : 'Uploaded image' }}</p>
                            </div>
                            <button type="button" data-property-image-delete-url="{{ route('admin.properties.images.destroy', [$property, $image]) }}" class="absolute right-1.5 top-1.5 grid h-6 w-6 place-items-center rounded-full bg-slate-900 text-xs font-black text-white opacity-90 shadow-sm transition hover:bg-rose-600" aria-label="Remove image">x</button>
                        </div>
                    @endforeach
                </div>
            @endif

            <p id="property-images-count" class="mt-2 hidden text-xs font-bold text-slate-500"></p>
            <div id="property-images-preview" class="mt-3 hidden grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"></div>
            @error('images.*')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    </section>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mapFrame = document.getElementById('property-map-preview');
            const mapAddress = document.getElementById('property-map-address');
            const mapOpen = document.getElementById('property-map-open');
            const mapRefresh = document.getElementById('property-map-refresh');
            const mapFields = ['name', 'location', 'address', 'city', 'state', 'postal_code', 'country']
                .map((field) => document.querySelector(`[name="${field}"]`))
                .filter(Boolean);

            const buildMapQuery = () => {
                const values = Object.fromEntries(
                    mapFields.map((field) => [field.name, field.value.trim()]).filter(([, value]) => value.length > 0)
                );

                const locationParts = [
                    values.address,
                    values.location,
                    values.city,
                    values.state,
                    values.postal_code,
                    values.country,
                ].filter(Boolean);

                if (locationParts.length > 0) {
                    return locationParts.join(', ');
                }

                return values.name || '';
            };

            const updateMap = () => {
                if (! mapFrame) {
                    return;
                }

                const query = buildMapQuery();
                const encodedQuery = encodeURIComponent(query || 'India');
                mapFrame.src = `https://www.google.com/maps?q=${encodedQuery}&output=embed`;

                if (mapAddress) {
                    mapAddress.textContent = query
                        ? query
                        : 'Enter address details to preview the map marker.';
                }

                if (mapOpen) {
                    mapOpen.href = `https://www.google.com/maps/search/?api=1&query=${encodedQuery}`;
                }
            };

            let mapTimer = null;
            const scheduleMapUpdate = () => {
                window.clearTimeout(mapTimer);
                mapTimer = window.setTimeout(updateMap, 400);
            };

            mapFields.forEach((field) => {
                field.addEventListener('input', scheduleMapUpdate);
                field.addEventListener('change', scheduleMapUpdate);
            });

            if (mapRefresh) {
                mapRefresh.addEventListener('click', updateMap);
            }

            updateMap();

            document.querySelectorAll('[data-property-image-delete-url]').forEach((button) => {
                button.addEventListener('click', async () => {
                    if (! window.confirm('Remove this image?')) {
                        return;
                    }

                    button.disabled = true;
                    button.classList.add('opacity-50');

                    const response = await fetch(button.dataset.propertyImageDeleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                        },
                    });

                    if (response.ok) {
                        button.closest('[data-property-image-card]')?.remove();
                        return;
                    }

                    button.disabled = false;
                    button.classList.remove('opacity-50');
                    window.alert('Image could not be removed. Please try again.');
                });
            });

            const input = document.getElementById('property-images-input');
            const preview = document.getElementById('property-images-preview');
            const count = document.getElementById('property-images-count');

            if (! input || ! preview) {
                return;
            }

            let selectedFiles = [];

            const formatBytes = (bytes) => {
                if (! bytes) {
                    return '0 KB';
                }

                const units = ['B', 'KB', 'MB'];
                const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
                const value = bytes / Math.pow(1024, index);

                return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
            };

            const syncInputFiles = (files) => {
                selectedFiles = files;

                if (typeof DataTransfer === 'undefined') {
                    return;
                }

                try {
                    const transfer = new DataTransfer();
                    files.forEach((file) => transfer.items.add(file));
                    input.files = transfer.files;
                } catch (error) {
                    // Some browsers restrict programmatic file-list assignment.
                    // The previews still render; users can reselect files before saving if needed.
                }
            };

            const renderPreviews = () => {
                const files = selectedFiles;
                preview.innerHTML = '';
                preview.classList.toggle('hidden', files.length === 0);

                if (count) {
                    count.classList.toggle('hidden', files.length === 0);
                    count.textContent = files.length === 1 ? '1 image selected' : `${files.length} images selected`;
                }

                files.forEach((file, index) => {
                    const card = document.createElement('div');
                    card.className = 'group relative flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-2 shadow-sm';

                    const thumb = document.createElement('div');
                    thumb.className = 'grid h-16 w-20 shrink-0 place-items-center overflow-hidden rounded-md border border-slate-200 bg-white';

                    const img = document.createElement('img');
                    img.className = 'h-full w-full object-cover';
                    img.alt = file.name;
                    thumb.appendChild(img);

                    const body = document.createElement('div');
                    body.className = 'min-w-0 flex-1';

                    const title = document.createElement('p');
                    title.className = 'truncate text-xs font-black text-slate-800';
                    title.textContent = file.name;

                    const size = document.createElement('p');
                    size.className = 'mt-0.5 text-[11px] font-bold text-slate-500';
                    size.textContent = formatBytes(file.size);

                    const dimensions = document.createElement('p');
                    dimensions.className = 'mt-0.5 text-[11px] font-semibold text-slate-400';
                    dimensions.textContent = 'Reading dimensions...';

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'absolute right-1.5 top-1.5 grid h-6 w-6 place-items-center rounded-full bg-slate-900 text-xs font-black text-white opacity-90 shadow-sm transition hover:bg-rose-600';
                    remove.setAttribute('aria-label', `Remove ${file.name}`);
                    remove.textContent = 'x';
                    remove.addEventListener('click', () => {
                        const updatedFiles = selectedFiles.filter((_, fileIndex) => fileIndex !== index);
                        syncInputFiles(updatedFiles);
                        renderPreviews();
                    });

                    body.append(title, size, dimensions);
                    card.append(thumb, body, remove);
                    preview.appendChild(card);

                    const imageUrl = URL.createObjectURL(file);
                    img.onload = () => {
                        dimensions.textContent = `${img.naturalWidth} x ${img.naturalHeight}px`;
                        URL.revokeObjectURL(imageUrl);
                    };
                    img.onerror = () => {
                        dimensions.textContent = 'Dimensions unavailable';
                        URL.revokeObjectURL(imageUrl);
                    };
                    img.src = imageUrl;
                });
            };

            input.addEventListener('change', () => {
                const newFiles = Array.from(input.files).filter((file) => file.type.startsWith('image/'));
                selectedFiles = [...selectedFiles, ...newFiles].slice(0, 8);
                renderPreviews();
                syncInputFiles(selectedFiles);
            });
        });
    </script>
@endonce
