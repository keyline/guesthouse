<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiscountRequest;
use App\Models\Discount;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $discounts = Discount::query()
            ->with(['property', 'roomType'])
            ->where(fn ($query) => $query
                ->whereNull('property_id')
                ->orWhereIn('property_id', $scope->properties()->pluck('id')))
            ->when($request->string('kind')->toString() === 'coupon', fn ($query) => $query->whereNotNull('code'))
            ->when($request->string('kind')->toString() === 'automatic', fn ($query) => $query->whereNull('code'))
            ->orderByDesc('status')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.discounts.index', [
            'discounts' => $discounts,
            'navItems' => AdminNavigation::make('marketing'),
        ]);
    }

    public function create(AdminPropertyScope $scope): View
    {
        return view('admin.discounts.create', [
            'discount' => new Discount([
                'discount_type' => Discount::TYPE_PERCENT,
                'status' => Discount::STATUS_ACTIVE,
            ]),
            'properties' => $scope->properties()->pluck('name', 'id')->all(),
            'roomTypes' => $this->roomTypes(),
            'navItems' => AdminNavigation::make('marketing'),
        ]);
    }

    public function store(DiscountRequest $request): RedirectResponse
    {
        $discount = Discount::query()->create(
            $request->attributesForModel() + ['status' => Discount::STATUS_ACTIVE]
        );

        return redirect()
            ->route('admin.discounts.index')
            ->with('status', ($discount->isCoupon() ? 'Coupon' : 'Offer').' "'.$discount->label().'" created.');
    }

    public function edit(Discount $discount, AdminPropertyScope $scope): View
    {
        $this->authorizeDiscount($discount, $scope);

        return view('admin.discounts.edit', [
            'discount' => $discount,
            'properties' => $scope->properties()->pluck('name', 'id')->all(),
            'roomTypes' => $this->roomTypes(),
            'navItems' => AdminNavigation::make('marketing'),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function roomTypes(): array
    {
        return \App\Models\RoomType::query()
            ->where('status', \App\Models\RoomType::STATUS_ACTIVE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function update(DiscountRequest $request, Discount $discount, AdminPropertyScope $scope): RedirectResponse
    {
        $this->authorizeDiscount($discount, $scope);

        $discount->update($request->attributesForModel());

        return redirect()
            ->route('admin.discounts.index')
            ->with('status', ($discount->isCoupon() ? 'Coupon' : 'Offer').' "'.$discount->label().'" updated.');
    }

    public function toggle(Discount $discount, AdminPropertyScope $scope): RedirectResponse
    {
        $this->authorizeDiscount($discount, $scope);

        $discount->update([
            'status' => $discount->status === Discount::STATUS_ACTIVE
                ? Discount::STATUS_INACTIVE
                : Discount::STATUS_ACTIVE,
        ]);

        return redirect()
            ->route('admin.discounts.index')
            ->with('status', '"'.$discount->label().'" is now '.$discount->status.'.');
    }

    public function destroy(Discount $discount, AdminPropertyScope $scope): RedirectResponse
    {
        $this->authorizeDiscount($discount, $scope);

        // Bookings keep their snapshot label; the FK is set null on delete.
        $discount->delete();

        return redirect()
            ->route('admin.discounts.index')
            ->with('status', '"'.$discount->label().'" deleted.');
    }

    private function authorizeDiscount(Discount $discount, AdminPropertyScope $scope): void
    {
        abort_unless(
            $discount->property_id === null || $scope->canAccessProperty($discount->property_id),
            404,
        );
    }
}
