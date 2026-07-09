<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Support\AdminNavigation;
use App\Support\AmenityIconLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AmenityController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole(\App\Models\User::ROLE_SUPER_ADMIN), 403);

        $amenities = Amenity::query()
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($request->string('category')->toString(), fn ($query, string $category) => $query->where('category', $category))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.amenities.index', [
            'amenities' => $amenities,
            'categories' => $this->categories(),
            'iconOptions' => $this->iconOptions(),
            'iconLibrary' => AmenityIconLibrary::all(),
            'navItems' => AdminNavigation::make('properties'),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->hasRole(\App\Models\User::ROLE_SUPER_ADMIN), 403);

        return view('admin.amenities.create', [
            'amenity' => new Amenity(['icon' => 'banquet', 'category' => 'general', 'is_active' => true]),
            'categories' => $this->categories(),
            'iconOptions' => $this->iconOptions(),
            'iconLibrary' => AmenityIconLibrary::all(),
            'navItems' => AdminNavigation::make('properties'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(\App\Models\User::ROLE_SUPER_ADMIN), 403);

        Amenity::query()->create($this->validatedAttributes($request));

        return redirect()
            ->route('admin.amenities.index')
            ->with('status', 'Amenity created successfully.');
    }

    public function edit(Request $request, Amenity $amenity): View
    {
        abort_unless($request->user()?->hasRole(\App\Models\User::ROLE_SUPER_ADMIN), 403);

        return view('admin.amenities.edit', [
            'amenity' => $amenity,
            'categories' => $this->categories(),
            'iconOptions' => $this->iconOptions(),
            'iconLibrary' => AmenityIconLibrary::all(),
            'navItems' => AdminNavigation::make('properties'),
        ]);
    }

    public function update(Request $request, Amenity $amenity): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(\App\Models\User::ROLE_SUPER_ADMIN), 403);

        $amenity->update($this->validatedAttributes($request, $amenity));

        return redirect()
            ->route('admin.amenities.index')
            ->with('status', 'Amenity updated successfully.');
    }

    public function destroy(Request $request, Amenity $amenity): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(\App\Models\User::ROLE_SUPER_ADMIN), 403);

        $amenity->update(['is_active' => false]);

        return redirect()
            ->route('admin.amenities.index')
            ->with('status', 'Amenity deactivated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAttributes(Request $request, ?Amenity $amenity = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('amenities', 'name')->ignore($amenity)],
            'icon' => ['required', Rule::in(array_keys($this->iconOptions()))],
            'category' => ['required', Rule::in(array_keys($this->categories()))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }

    /**
     * @return array<string, string>
     */
    private function iconOptions(): array
    {
        return AmenityIconLibrary::options();
    }

    /**
     * @return array<string, string>
     */
    private function categories(): array
    {
        return [
            'general' => 'General',
            'connectivity' => 'Connectivity',
            'transport' => 'Transport',
            'comfort' => 'Comfort',
            'food' => 'Food',
            'service' => 'Service',
            'utility' => 'Utility',
            'safety' => 'Safety',
            'accessibility' => 'Accessibility',
            'entertainment' => 'Entertainment',
            'events' => 'Events',
            'leisure' => 'Leisure',
        ];
    }
}
