<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PropertyRequest;
use App\Models\Amenity;
use App\Models\Property;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use App\Support\AmenityIconLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $properties = $scope->applyAccessible(Property::query(), 'id')
            ->with(['amenities', 'images'])
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('city')->toString(), fn ($query, string $city) => $query->where('city', 'like', '%'.$city.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.properties.index', [
            'properties' => $properties,
            'navItems' => AdminNavigation::make('properties'),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()?->hasRole(\App\Models\User::ROLE_SUPER_ADMIN), 403);

        return view('admin.properties.create', [
            'property' => new Property([
                'property_type' => Property::TYPE_GUEST_HOUSE,
                'status' => Property::STATUS_DRAFT,
                'country' => 'India',
                'currency' => 'INR',
                'check_in_time_minutes' => 720,
                'check_out_time_minutes' => 660,
            ]),
            'amenities' => $this->amenities(),
            'amenityIconLibrary' => AmenityIconLibrary::all(),
            'selectedAmenityIds' => [],
            'navItems' => AdminNavigation::make('properties'),
            'statuses' => $this->statuses(),
            'types' => $this->types(),
        ]);
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(\App\Models\User::ROLE_SUPER_ADMIN), 403);

        $property = DB::transaction(function () use ($request): Property {
            $property = Property::query()->create($request->propertyAttributes());

            $this->syncAmenities($property, $request->amenityIds());
            $this->storeImages($property, $request);

            return $property;
        });

        return redirect()
            ->route('admin.properties.index')
            ->with('status', 'Property created successfully.');
    }

    public function show(Property $property, AdminPropertyScope $scope): View
    {
        abort_unless($scope->canAccessProperty($property->id), 404);
        $property->load(['amenities', 'images']);

        return view('admin.properties.show', [
            'property' => $property,
            'navItems' => AdminNavigation::make('properties'),
        ]);
    }

    public function edit(Property $property, AdminPropertyScope $scope): View
    {
        abort_unless($scope->canAccessProperty($property->id), 404);
        $property->load(['amenities', 'images']);

        return view('admin.properties.edit', [
            'property' => $property,
            'amenities' => $this->amenities(),
            'amenityIconLibrary' => AmenityIconLibrary::all(),
            'selectedAmenityIds' => $property->amenities->pluck('id')->all(),
            'navItems' => AdminNavigation::make('properties'),
            'statuses' => $this->statuses(),
            'types' => $this->types(),
        ]);
    }

    public function update(PropertyRequest $request, Property $property, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($property->id), 404);

        DB::transaction(function () use ($request, $property): void {
            $attributes = $request->propertyAttributes();

            if ($property->name !== $attributes['name']) {
                $attributes['slug'] = Property::uniqueSlug($attributes['name'], $property->id);
            }

            $property->update($attributes);
            $this->syncAmenities($property, $request->amenityIds());
            $this->storeImages($property, $request);
        });

        return redirect()
            ->route('admin.properties.edit', $property)
            ->with('status', 'Property updated successfully.');
    }

    public function toggleStatus(Property $property, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($property->id), 404);

        $property->update([
            'status' => $property->status === Property::STATUS_ACTIVE
                ? Property::STATUS_DRAFT
                : Property::STATUS_ACTIVE,
            'published_at' => $property->status === Property::STATUS_ACTIVE ? null : now(),
        ]);

        return back()->with('status', 'Property status updated successfully.');
    }

    public function toggleHome(Property $property, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($property->id), 404);

        $property->update([
            'show_on_home' => ! $property->show_on_home,
        ]);

        return back()->with('status', 'Home page visibility updated successfully.');
    }

    public function destroy(Property $property, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless(request()->user()?->hasRole(\App\Models\User::ROLE_SUPER_ADMIN), 403);
        abort_unless($scope->canAccessProperty($property->id), 404);

        $property->delete();

        return redirect()
            ->route('admin.properties.index')
            ->with('status', 'Property deleted successfully.');
    }

    /**
     * @param  list<int>  $amenityIds
     */
    private function syncAmenities(Property $property, array $amenityIds): void
    {
        $property->amenities()->sync($amenityIds);
    }

    private function amenities()
    {
        return Amenity::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function storeImages(Property $property, PropertyRequest $request): void
    {
        foreach ($request->file('images', []) as $index => $image) {
            $path = $image->store('properties/'.$property->id, 'public');

            $property->images()->create([
                'path' => $path,
                'alt_text' => $property->name,
                'is_primary' => $property->images()->count() === 0 && $index === 0,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            Property::STATUS_DRAFT => 'Draft',
            Property::STATUS_ACTIVE => 'Active',
            Property::STATUS_INACTIVE => 'Inactive',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function types(): array
    {
        return [
            Property::TYPE_GUEST_HOUSE => 'Guest House',
            Property::TYPE_BANQUET => 'Banquet',
            Property::TYPE_MIXED => 'Guest House + Banquet',
        ];
    }
}
