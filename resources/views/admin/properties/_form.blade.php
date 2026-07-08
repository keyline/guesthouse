@php
    $checkIn = sprintf('%02d:%02d', intdiv((int) $property->check_in_time_minutes, 60), ((int) $property->check_in_time_minutes) % 60);
    $checkOut = sprintf('%02d:%02d', intdiv((int) $property->check_out_time_minutes, 60), ((int) $property->check_out_time_minutes) % 60);
    $amenityValues = old('amenities', $amenityNames ?: ['', '', '', '', '', '']);
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
            </div>
        </div>
    </section>

    <!-- LOCATION -->
    <section class="border border-slate-200 bg-white">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Location</h3>
        </div>
        <div class="p-4">
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
                    <label class="block text-xs font-bold text-slate-600">Address *</label>
                    <textarea name="address" rows="2" class="mt-1 w-full rounded border border-slate-300 px-2.5 py-1.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">{{ old('address', $property->address) }}</textarea>
                    @error('address')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
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
            <div class="grid gap-2">
                @foreach ($amenityValues as $index => $amenity)
                    <input name="amenities[]" value="{{ $amenity }}" placeholder="e.g., WiFi, Parking, AC..." class="h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                @endforeach
            </div>
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
                    <label class="block text-xs font-bold text-slate-600">Short Description</label>
                    <input name="short_description" maxlength="300" value="{{ old('short_description', $property->short_description) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                </div>

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
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <label for="property-images-input" class="block text-xs font-bold text-slate-600">Upload Images</label>
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG, WEBP. Max 4MB each, up to 8 images.</p>
                </div>
                <label for="property-images-input" class="inline-flex h-9 cursor-pointer items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">
                    Choose Images
                </label>
            </div>
            <input id="property-images-input" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
            <p id="property-images-count" class="mt-2 hidden text-xs font-bold text-slate-500"></p>
            <div id="property-images-preview" class="mt-3 hidden grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"></div>
            @error('images.*')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    </section>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
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
