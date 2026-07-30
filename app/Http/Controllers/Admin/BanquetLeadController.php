<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BanquetLead;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BanquetLeadController extends Controller
{
    public function index(Request $request, AdminPropertyScope $scope): View
    {
        $base = fn () => $scope->apply(BanquetLead::query());

        $leads = $base()
            ->with(['banquet', 'property'])
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.banquet-leads.index', [
            'leads' => $leads,
            'statuses' => $this->statusLabels(),
            'counts' => [
                'new' => (clone $base())->where('status', BanquetLead::STATUS_NEW)->count(),
                'contacted' => (clone $base())->where('status', BanquetLead::STATUS_CONTACTED)->count(),
                'closed' => (clone $base())->where('status', BanquetLead::STATUS_CLOSED)->count(),
            ],
            'navItems' => AdminNavigation::make('marketing'),
        ]);
    }

    public function updateStatus(Request $request, BanquetLead $banquetLead, AdminPropertyScope $scope): RedirectResponse
    {
        abort_unless($banquetLead->property_id === null || $scope->canAccessProperty($banquetLead->property_id), 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys($this->statusLabels()))],
        ]);

        $banquetLead->update(['status' => $validated['status']]);

        return back()->with('status', 'Lead marked as '.$this->statusLabels()[$validated['status']].'.');
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            BanquetLead::STATUS_NEW => 'New',
            BanquetLead::STATUS_CONTACTED => 'Contacted',
            BanquetLead::STATUS_CLOSED => 'Closed',
        ];
    }
}
