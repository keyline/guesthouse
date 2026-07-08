<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminPropertyScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyContextController extends Controller
{
    public function update(Request $request, AdminPropertyScope $scope): RedirectResponse
    {
        $properties = $scope->properties($request->user());
        $allowedIds = $properties->pluck('id')->all();

        $validated = $request->validate([
            'property_id' => [
                'nullable',
                'integer',
                Rule::in($request->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN) ? array_merge([0], $allowedIds) : $allowedIds),
            ],
        ]);

        $propertyId = (int) ($validated['property_id'] ?? 0);

        if ($propertyId === 0 && $request->user()->hasRole(\App\Models\User::ROLE_SUPER_ADMIN)) {
            $request->session()->forget(AdminPropertyScope::SESSION_KEY);
        } else {
            $request->session()->put(AdminPropertyScope::SESSION_KEY, $propertyId);
        }

        return back();
    }
}
