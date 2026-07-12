<?php

namespace App\Modules\Admin\Roles\Controllers;

use App\Modules\Admin\Roles\Actions\DeleteRoleAction;
use App\Modules\Admin\Roles\Actions\StoreRoleAction;
use App\Modules\Admin\Roles\Actions\UpdateRoleAction;
use App\Modules\Admin\Roles\Requests\StoreRoleRequest;
use App\Modules\Admin\Roles\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminRolesController
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->value();

        $roles = Role::query()
            ->withCount('permissions')
            ->when($search, fn ($query, $search) => $query
                ->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->through(function (Role $role) {
                $role->users_count = DB::table('model_has_roles')
                    ->where('role_id', $role->id)
                    ->count();

                return $role;
            })
            ->withQueryString();

        return view('modules.admin.roles.index', [
            'roles' => $roles,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('modules.admin.roles.create', [
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function store(StoreRoleRequest $request, StoreRoleAction $action): RedirectResponse
    {
        $role = $action->handle(
            validated: $request->validated(),
        );

        return to_route('admin.roles.index')
            ->with('flash', ['success' => "Role {$role->name} created successfully."]);
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('modules.admin.roles.edit', [
            'role' => $role,
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        if ($role->name === 'super-admin') {
            return to_route('admin.roles.index')
                ->with('flash', ['error' => 'The super-admin role cannot be modified.']);
        }

        $action->handle(
            role: $role,
            validated: $request->validated(),
        );

        return to_route('admin.roles.index')
            ->with('flash', ['success' => "Role {$role->name} updated successfully."]);
    }

    public function destroy(Role $role, DeleteRoleAction $action): RedirectResponse
    {
        if ($role->name === 'super-admin') {
            return to_route('admin.roles.index')
                ->with('flash', ['error' => 'The super-admin role cannot be deleted.']);
        }

        $userCount = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->count();

        if ($userCount > 0) {
            return to_route('admin.roles.index')
                ->with('flash', ['error' => "Cannot delete {$role->name}: {$userCount} user(s) are assigned to this role."]);
        }

        $name = $role->name;

        $action->handle($role);

        return to_route('admin.roles.index')
            ->with('flash', ['success' => "Role {$name} deleted successfully."]);
    }
}
