<?php

namespace App\Support;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminPropertyScope
{
    public const SESSION_KEY = 'admin.selected_property_id';

    public function __construct(private readonly Request $request)
    {
    }

    /**
     * @return Collection<int, Property>
     */
    public function properties(?User $user = null): Collection
    {
        $user ??= $this->request->user();

        if (! $user) {
            return collect();
        }

        if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
            return Property::query()->orderBy('name')->get();
        }

        return $user->managedProperties()->orderBy('name')->get();
    }

    public function selectedPropertyId(?User $user = null): ?int
    {
        $user ??= $this->request->user();

        if (! $user) {
            return null;
        }

        if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $selected = $this->request->session()->get(self::SESSION_KEY);

            return $selected ? (int) $selected : null;
        }

        $allowedIds = $this->properties($user)->pluck('id');

        if ($allowedIds->isEmpty()) {
            return null;
        }

        $selected = (int) $this->request->session()->get(self::SESSION_KEY, 0);

        if (! $selected || ! $allowedIds->contains($selected)) {
            $selected = (int) $allowedIds->first();
            $this->request->session()->put(self::SESSION_KEY, $selected);
        }

        return $selected;
    }

    public function canAccessProperty(int $propertyId, ?User $user = null): bool
    {
        $user ??= $this->request->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
            return true;
        }

        return $this->properties($user)->pluck('id')->contains($propertyId);
    }

    public function apply(Builder $query, string $column = 'property_id', ?User $user = null): Builder
    {
        $user ??= $this->request->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $selected = $this->selectedPropertyId($user);

            return $selected ? $query->where($column, $selected) : $query;
        }

        $selected = $this->selectedPropertyId($user);

        return $selected ? $query->where($column, $selected) : $query->whereRaw('1 = 0');
    }

    public function applyAccessible(Builder $query, string $column = 'property_id', ?User $user = null): Builder
    {
        $user ??= $this->request->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
            return $query;
        }

        $propertyIds = $this->properties($user)->pluck('id');

        return $propertyIds->isNotEmpty()
            ? $query->whereIn($column, $propertyIds)
            : $query->whereRaw('1 = 0');
    }
}
