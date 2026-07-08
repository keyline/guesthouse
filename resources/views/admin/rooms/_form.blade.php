<section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-black">Room Details</h2>
    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <div>
            <label for="property_id" class="text-sm font-bold text-slate-700">Property</label>
            <select id="property_id" name="property_id" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                <option value="">Select property</option>
                @foreach ($properties as $id => $name)
                    <option value="{{ $id }}" @selected((int) old('property_id', request('property_id', $room->property_id)) === $id)>{{ $name }}</option>
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
            <label for="room_number" class="text-sm font-bold text-slate-700">Room number</label>
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
