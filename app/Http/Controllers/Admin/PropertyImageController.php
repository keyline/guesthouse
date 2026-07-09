<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Support\AdminPropertyScope;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PropertyImageController extends Controller
{
    public function destroy(Request $request, Property $property, PropertyImage $image, AdminPropertyScope $scope): RedirectResponse|Response
    {
        abort_unless($scope->canAccessProperty($property->id), 404);
        abort_unless($image->property_id === $property->id, 404);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        if ($image->is_primary && $property->images()->exists()) {
            $property->images()->oldest('sort_order')->oldest('id')->first()?->update(['is_primary' => true]);
        }

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return back()->with('status', 'Image removed successfully.');
    }
}
