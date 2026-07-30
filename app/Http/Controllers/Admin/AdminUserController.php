<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserRequest;
use App\Models\Property;
use App\Models\User;
use App\Support\AdminNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $adminUsers = User::query()
            ->with('managedProperties')
            ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_PROPERTY_MANAGER])
            ->when($request->string('role')->toString(), fn ($query, string $role) => $query->where('role', $role))
            ->when($request->string('status')->toString() !== '', fn ($query) => $query->where('is_active', $request->string('status')->toString() === 'active'))
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.admin-users.index', [
            'adminUsers' => $adminUsers,
            'navItems' => AdminNavigation::make('admin-users'),
            'roles' => $this->roles(),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-users.create', [
            'adminUser' => new User([
                'role' => User::ROLE_PROPERTY_MANAGER,
                'is_active' => true,
            ]),
            'assignedPropertyIds' => [],
            'properties' => $this->propertyOptions(),
            'roles' => $this->roles(),
            'navItems' => AdminNavigation::make('admin-users'),
        ]);
    }

    public function store(AdminUserRequest $request): RedirectResponse
    {
        $adminUser = DB::transaction(function () use ($request): User {
            $adminUser = User::query()->create($request->userAttributes());
            $adminUser->managedProperties()->sync($request->propertyIds());

            return $adminUser;
        });

        return redirect()
            ->route('admin.admin-users.show', $adminUser)
            ->with('status', 'Admin user created successfully.');
    }

    public function show(User $adminUser): View
    {
        $this->ensureAdminUser($adminUser);
        $adminUser->load('managedProperties');

        return view('admin.admin-users.show', [
            'adminUser' => $adminUser,
            'navItems' => AdminNavigation::make('admin-users'),
            'roles' => $this->roles(),
        ]);
    }

    public function edit(User $adminUser): View
    {
        $this->ensureAdminUser($adminUser);
        $adminUser->load('managedProperties');

        return view('admin.admin-users.edit', [
            'adminUser' => $adminUser,
            'assignedPropertyIds' => $adminUser->managedProperties->pluck('id')->all(),
            'properties' => $this->propertyOptions(),
            'roles' => $this->roles(),
            'navItems' => AdminNavigation::make('admin-users'),
        ]);
    }

    public function update(AdminUserRequest $request, User $adminUser): RedirectResponse
    {
        $this->ensureAdminUser($adminUser);

        DB::transaction(function () use ($request, $adminUser): void {
            $adminUser->update($request->userAttributes());
            $adminUser->managedProperties()->sync($request->propertyIds());
        });

        return redirect()
            ->route('admin.admin-users.index')
            ->with('status', 'Admin user updated successfully.');
    }

    public function destroy(User $adminUser): RedirectResponse
    {
        $this->ensureAdminUser($adminUser);

        abort_if($adminUser->is(auth()->user()), 422, 'You cannot deactivate your own account.');

        $adminUser->update(['is_active' => false]);
        $adminUser->managedProperties()->detach();

        return redirect()
            ->route('admin.admin-users.index')
            ->with('status', 'Admin user deactivated successfully.');
    }

    private function ensureAdminUser(User $adminUser): void
    {
        abort_unless($adminUser->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_PROPERTY_MANAGER]), 404);
    }

    /**
     * @return array<string, string>
     */
    private function roles(): array
    {
        return [
            User::ROLE_SUPER_ADMIN => 'Super Admin',
            User::ROLE_PROPERTY_MANAGER => 'Property Manager',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function propertyOptions(): array
    {
        return Property::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
