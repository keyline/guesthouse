<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use App\Support\AdminNavigation;
use App\Support\AdminPropertyScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request, AdminPropertyScope $scope): View
    {
        $logs = AdminActivityLog::query()
            ->when(
                ! $request->user()->hasRole(User::ROLE_SUPER_ADMIN),
                fn ($query) => $query->whereIn('property_id', $scope->properties()->pluck('id')),
            )
            ->when($request->string('subject_type')->toString(), fn ($query, string $type) => $query->where('subject_type', $type))
            ->when($request->string('action')->toString(), fn ($query, string $action) => $query->where('action', $action))
            ->when($request->integer('property_id'), fn ($query, int $propertyId) => $query->where('property_id', $propertyId))
            ->latest('created_at')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity-log.index', [
            'logs' => $logs,
            'subjectTypes' => AdminActivityLog::query()->select('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type'),
            'properties' => $scope->properties()->pluck('name', 'id'),
            'navItems' => AdminNavigation::make('settings'),
        ]);
    }
}
