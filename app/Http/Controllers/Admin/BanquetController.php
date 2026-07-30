<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Banquet;
use App\Models\BanquetImage;
use App\Models\Property;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use App\Support\AmenityIconLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BanquetController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $banquets = $scope->apply(Banquet::query())
            ->with(['property'])
            ->when($request->integer('property_id'), fn ($query, int $propertyId) => $query->where('property_id', $propertyId))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('property_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.banquets.index', [
            'banquets' => $banquets,
            'properties' => $this->properties($scope),
            'statuses' => $this->statuses(),
            'navItems' => AdminNavigation::make('banquets'),
        ]);
    }

    public function create(AdminPropertyScope $scope): View
    {
        $propertyContext = $scope->properties()->firstWhere('id', $scope->selectedPropertyId());
        return view('admin.banquets.create', [
            'banquet' => new Banquet([
                'property_id' => $scope->selectedPropertyId(),
                'status' => Banquet::STATUS_DRAFT,
                'currency' => 'INR',
            ]),
            'properties' => $this->properties($scope),
            'propertyContext' => $propertyContext,
            'statuses' => $this->statuses(),
            'amenities' => $this->getAmenities(),
            'amenityIconLibrary' => AmenityIconLibrary::all(),
            'selectedAmenityIds' => [],
            'setupTypes' => $this->setupTypes(),
            'navItems' => AdminNavigation::make('banquets'),
        ]);
    }

    public function store(Request $request, AdminPropertyScope $scope): RedirectResponse
    {
        $propertyId = $scope->selectedPropertyId();
        if (! $propertyId || ! $scope->canAccessProperty($propertyId)) {
            return back()->withInput()->withErrors(['property_id' => 'Select one property from the top banner before creating a banquet.']);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity_min' => 'required|integer|min:1',
            'capacity_max' => 'required|integer|min:1',
            'base_price_minor' => 'required|integer|min:0',
            'currency' => 'required|string|in:INR,USD,EUR',
            'setup_types' => 'required|array|min:1',
            'amenities' => 'nullable|array',
            'amenities.*' => 'nullable|integer|exists:amenities,id',
            'status' => 'required|in:draft,active,inactive',
            'banquet_images' => 'nullable|array|max:10',
            'banquet_images.*' => 'image|max:5120|mimes:jpg,jpeg,png,webp',
        ]);
        $validated['property_id'] = $propertyId;

        $banquet = Banquet::create($validated);
        $this->handleImageUpload($request, $banquet);
        $this->handleAmenities($request, $banquet);

        return redirect()
            ->route('admin.banquets.edit', $banquet)
            ->with('status', 'Banquet created successfully.');
    }

    public function show(Banquet $banquet, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($banquet->property_id), 404);

        return redirect()->route('admin.banquets.edit', $banquet);
    }

    public function edit(Banquet $banquet, AdminPropertyScope $scope): View
    {
        abort_unless($scope->canAccessProperty($banquet->property_id), 404);

        $banquet->load('property');
        return view('admin.banquets.edit', [
            'banquet' => $banquet,
            'properties' => $this->properties($scope),
            'propertyContext' => $banquet->property,
            'statuses' => $this->statuses(),
            'amenities' => $this->getAmenities(),
            'amenityIconLibrary' => AmenityIconLibrary::all(),
            'selectedAmenityIds' => $banquet->amenitiesList()->pluck('amenities.id')->all(),
            'setupTypes' => $this->setupTypes(),
            'navItems' => AdminNavigation::make('banquets'),
        ]);
    }

    public function update(Request $request, Banquet $banquet, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($banquet->property_id), 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity_min' => 'required|integer|min:1',
            'capacity_max' => 'required|integer|min:1',
            'base_price_minor' => 'required|integer|min:0',
            'currency' => 'required|string|in:INR,USD,EUR',
            'setup_types' => 'required|array|min:1',
            'amenities' => 'nullable|array',
            'amenities.*' => 'nullable|integer|exists:amenities,id',
            'status' => 'required|in:draft,active,inactive',
            'banquet_images' => 'nullable|array|max:10',
            'banquet_images.*' => 'image|max:5120|mimes:jpg,jpeg,png,webp',
        ]);

        $banquet->update($validated);
        $this->handleImageUpload($request, $banquet);
        $this->handleAmenities($request, $banquet);

        return redirect()
            ->route('admin.banquets.edit', $banquet)
            ->with('status', 'Banquet updated successfully.');
    }

    public function destroy(Banquet $banquet, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($scope->canAccessProperty($banquet->property_id), 404);
        $banquet->delete();

        return redirect()
            ->route('admin.banquets.index')
            ->with('status', 'Banquet deleted successfully.');
    }

    /**
     * @return array<int, Property>
     */
    private function properties(AdminPropertyScope $scope): array
    {
        return $scope->properties()->keyBy('id')->all();
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            Banquet::STATUS_DRAFT => 'Draft',
            Banquet::STATUS_ACTIVE => 'Active',
            Banquet::STATUS_INACTIVE => 'Inactive',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Amenity>
     */
    private function getAmenities()
    {
        return Amenity::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('type', 'banquet')
                    ->orWhere('type', 'both');
            })
            ->orderBy('sort_order')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    private function setupTypes(): array
    {
        return [
            'banquet' => 'Banquet (Rounds)',
            'theatre' => 'Theatre (Classroom)',
            'cocktail' => 'Cocktail (Standing)',
            'u_shape' => 'U-Shape (Meeting)',
            'classroom' => 'Classroom (Rows)',
            'cabaret' => 'Cabaret (Tables + Stage)',
        ];
    }

    private function handleImageUpload(Request $request, Banquet $banquet): void
    {
        if (!$request->hasFile('banquet_images')) {
            return;
        }

        $files = $request->file('banquet_images');
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $path = $file->store("banquets/{$banquet->id}", 'public');
                BanquetImage::create([
                    'banquet_id' => $banquet->id,
                    'path' => $path,
                    'alt_text' => $banquet->name,
                    'sort_order' => $banquet->images()->max('sort_order') + 1 ?? 1,
                ]);
            }
        }
    }

    private function handleAmenities(Request $request, Banquet $banquet): void
    {
        $amenityIds = collect($request->input('amenities', []))
            ->filter()
            ->map(fn (mixed $amenity) => (int) $amenity)
            ->unique()
            ->values()
            ->all();

        $banquet->amenitiesList()->sync($amenityIds);
    }
}
