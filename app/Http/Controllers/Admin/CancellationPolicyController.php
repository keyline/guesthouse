<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CancellationPolicy;
use App\Support\AdminNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CancellationPolicyController extends Controller
{
    public function index(): View
    {
        return view('admin.cancellation-policies.index', [
            'policies' => CancellationPolicy::query()
                ->withCount('ratePlans')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'navItems' => AdminNavigation::make('rooms'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePolicy($request);

        CancellationPolicy::query()->create($validated + [
            'code' => str($validated['name'])->slug('_')->limit(40, '')->toString(),
            'sort_order' => (int) CancellationPolicy::query()->max('sort_order') + 1,
        ]);

        return redirect()
            ->route('admin.cancellation-policies.index')
            ->with('status', $validated['name'].' policy created. Existing bookings keep the terms they were sold under.');
    }

    public function update(Request $request, CancellationPolicy $cancellationPolicy): RedirectResponse
    {
        $validated = $this->validatePolicy($request, $cancellationPolicy);

        $cancellationPolicy->update($validated);

        return redirect()
            ->route('admin.cancellation-policies.index')
            ->with('status', $cancellationPolicy->name.' updated — applies to new bookings only.');
    }

    public function toggle(CancellationPolicy $cancellationPolicy): RedirectResponse
    {
        $cancellationPolicy->update(['is_active' => ! $cancellationPolicy->is_active]);

        return redirect()
            ->route('admin.cancellation-policies.index')
            ->with('status', $cancellationPolicy->name.' is now '.($cancellationPolicy->is_active ? 'active' : 'inactive').'.');
    }

    /**
     * @return array{name: string, description: ?string, tiers: list<array{hours_before: int, refund_percent: int}>}
     */
    private function validatePolicy(Request $request, ?CancellationPolicy $existing = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'tiers' => ['array', 'max:'.CancellationPolicy::MAX_TIERS],
            'tiers.*.hours_before' => ['nullable', 'integer', 'min:1', 'max:8760'],
            'tiers.*.refund_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // A row counts only when both halves are filled; an empty list is a
        // valid non-refundable policy.
        $tiers = collect($validated['tiers'] ?? [])
            ->filter(fn (array $tier) => filled($tier['hours_before'] ?? null) && filled($tier['refund_percent'] ?? null))
            ->map(fn (array $tier) => [
                'hours_before' => (int) $tier['hours_before'],
                'refund_percent' => (int) $tier['refund_percent'],
            ])
            ->unique('hours_before')
            ->sortByDesc('hours_before')
            ->values()
            ->all();

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'tiers' => $tiers,
        ];
    }
}
