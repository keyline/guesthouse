<div class="border border-slate-200 bg-white">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-2">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600">Amenity Master Details</h3>
    </div>
    <div class="grid gap-3 p-4 md:grid-cols-4">
        <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-600">Amenity Name *</label>
            <input name="name" value="{{ old('name', $amenity->name) }}" placeholder="e.g., Wi-Fi, Parking, Security" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            @php $selectedIcon = old('icon', $amenity->icon ?: 'banquet'); @endphp
            <label class="block text-xs font-bold text-slate-600">Icon *</label>
            <input id="amenity-icon-input" type="hidden" name="icon" value="{{ $selectedIcon }}">
            <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 p-2">
                <div class="flex flex-wrap items-center gap-2">
                    <div id="amenity-icon-preview" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-sky-600 text-white">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="{{ $iconLibrary[$selectedIcon]['path'] ?? $iconLibrary['banquet']['path'] }}"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p id="amenity-icon-label" class="truncate text-xs font-black text-slate-800">{{ $iconOptions[$selectedIcon] ?? 'Banquet' }}</p>
                        <p class="text-[11px] font-semibold text-slate-500">Free built-in icon browser</p>
                    </div>
                    <input id="amenity-icon-search" type="search" placeholder="Search icon..." class="h-8 w-full rounded border border-slate-300 bg-white px-2 text-xs font-semibold outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 sm:w-44">
                </div>

                <div id="amenity-icon-browser" class="mt-2 grid max-h-48 gap-2 overflow-y-auto pr-1 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($iconLibrary as $value => $icon)
                        <button
                            type="button"
                            data-amenity-icon-option
                            data-icon-value="{{ $value }}"
                            data-icon-label="{{ $icon['label'] }}"
                            data-icon-path="{{ $icon['path'] }}"
                            data-icon-search="{{ strtolower($icon['label'].' '.$icon['tags'].' '.$value) }}"
                            class="flex items-center gap-2 rounded-lg border px-2 py-2 text-left transition {{ $selectedIcon === $value ? 'border-sky-300 bg-sky-50 ring-1 ring-sky-200' : 'border-slate-200 bg-white hover:border-sky-200 hover:bg-slate-50' }}">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md {{ $selectedIcon === $value ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-600' }}">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="{{ $icon['path'] }}"></path>
                                </svg>
                            </span>
                            <span class="truncate text-xs font-black text-slate-700">{{ $icon['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            @error('icon')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600">Category *</label>
            <select name="category" class="mt-1 h-9 w-full rounded border border-slate-300 bg-white px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $amenity->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600">Sort Order</label>
            <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $amenity->sort_order ?? 0) }}" class="mt-1 h-9 w-full rounded border border-slate-300 px-2.5 text-sm outline-none focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
            @error('sort_order')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-3">
            <label class="mt-6 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-sky-600 focus:ring-sky-600" @checked(old('is_active', $amenity->is_active ?? true))>
                Active and selectable on property page
            </label>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('amenity-icon-input');
            const preview = document.getElementById('amenity-icon-preview');
            const label = document.getElementById('amenity-icon-label');
            const search = document.getElementById('amenity-icon-search');
            const options = Array.from(document.querySelectorAll('[data-amenity-icon-option]'));

            if (! input || ! preview || ! label || options.length === 0) {
                return;
            }

            const setIcon = (button) => {
                input.value = button.dataset.iconValue;
                label.textContent = button.dataset.iconLabel;
                preview.innerHTML = `<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="${button.dataset.iconPath}"></path></svg>`;

                options.forEach((option) => {
                    const selected = option === button;
                    option.classList.toggle('border-sky-300', selected);
                    option.classList.toggle('bg-sky-50', selected);
                    option.classList.toggle('ring-1', selected);
                    option.classList.toggle('ring-sky-200', selected);
                    option.classList.toggle('border-slate-200', ! selected);
                    option.classList.toggle('bg-white', ! selected);

                    const iconBox = option.querySelector('span');
                    iconBox?.classList.toggle('bg-sky-600', selected);
                    iconBox?.classList.toggle('text-white', selected);
                    iconBox?.classList.toggle('bg-slate-100', ! selected);
                    iconBox?.classList.toggle('text-slate-600', ! selected);
                });
            };

            options.forEach((button) => button.addEventListener('click', () => setIcon(button)));

            search?.addEventListener('input', () => {
                const query = search.value.trim().toLowerCase();
                options.forEach((button) => {
                    button.classList.toggle('hidden', query.length > 0 && ! button.dataset.iconSearch.includes(query));
                });
            });
        });
    </script>
@endonce
