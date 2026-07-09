<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-black">Room Details</h2>
    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <div>
            <label for="property_id" class="text-sm font-bold text-slate-700">Property</label>
            <select id="property_id" name="property_id" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                <option value="">Select property</option>
                @foreach ($properties as $property)
                    @php
                        $statusLabel = match($property->status) {
                            'active' => '✓ Active',
                            'draft' => '⊙ Draft',
                            'inactive' => '✕ Inactive',
                            default => $property->status,
                        };
                    @endphp
                    <option value="{{ $property->id }}" @selected((int) old('property_id', request('property_id', $room->property_id)) === $property->id)>{{ $property->name }} — {{ $statusLabel }}</option>
                @endforeach
            </select>
            @error('property_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="room_type_id" class="text-sm font-bold text-slate-700">Room type</label>
            <select id="room_type_id" name="room_type_id" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                <option value="">Select room type</option>
                @foreach ($roomTypes as $id => $name)
                    <option value="{{ $id }}" @selected((int) old('room_type_id', request('room_type_id', $room->room_type_id)) === $id)>{{ $name }}</option>
                @endforeach
            </select>
            @error('room_type_id')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="room_number" class="text-sm font-bold text-slate-700">Room Number / Room Name</label>
            <input id="room_number" name="room_number" value="{{ old('room_number', $room->room_number) }}" required placeholder="101" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
            @error('room_number')<p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="floor" class="text-sm font-bold text-slate-700">Floor</label>
            <input id="floor" name="floor" value="{{ old('floor', $room->floor) }}" placeholder="1st Floor" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-slate-950">
        </div>

        <div>
            <label for="status" class="text-sm font-bold text-slate-700">Status</label>
            <select id="status" name="status" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $room->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap items-end gap-4">
            <label class="inline-flex h-11 items-center gap-2 rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700">
                <input type="checkbox" name="is_smoking" value="1" @checked(old('is_smoking', $room->is_smoking))>
                Smoking
            </label>
            <label class="inline-flex h-11 items-center gap-2 rounded-lg border border-slate-300 px-3 text-sm font-bold text-slate-700">
                <input type="checkbox" name="is_accessible" value="1" @checked(old('is_accessible', $room->is_accessible))>
                Accessible
            </label>
        </div>

        <div class="md:col-span-2">
            <label for="notes" class="text-sm font-bold text-slate-700">Internal notes</label>
            <textarea id="notes" name="notes" rows="4" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-950">{{ old('notes', $room->notes) }}</textarea>
        </div>
    </div>
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
