<?php

namespace App\Modules\Admin\Permissions\Controllers;

use App\Modules\Admin\Permissions\Actions\DeletePermissionAction;
use App\Modules\Admin\Permissions\Actions\StorePermissionAction;
use App\Modules\Admin\Permissions\Actions\UpdatePermissionAction;
use App\Modules\Admin\Permissions\Requests\StorePermissionRequest;
use App\Modules\Admin\Permissions\Requests\UpdatePermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class AdminPermissionsController
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->value();

        $permissions = Permission::query()
            ->when($search, fn ($query, $search) => $query
                ->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('modules.admin.permissions.index', [
            'permissions' => $permissions,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('modules.admin.permissions.create');
    }

    public function store(StorePermissionRequest $request, StorePermissionAction $action): RedirectResponse
    {
        $permission = $action->handle(
            validated: $request->validated(),
        );

        return to_route('admin.permissions.index')
            ->with('flash', ['success' => "Permission {$permission->name} created successfully."]);
    }

    public function edit(Permission $permission): View
    {
        return view('modules.admin.permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission, UpdatePermissionAction $action): RedirectResponse
    {
        $action->handle(
            permission: $permission,
            validated: $request->validated(),
        );

        return to_route('admin.permissions.index')
            ->with('flash', ['success' => "Permission {$permission->name} updated successfully."]);
    }

    public function destroy(Permission $permission, DeletePermissionAction $action): RedirectResponse
    {
        $name = $permission->name;

        $action->handle($permission);

        return to_route('admin.permissions.index')
            ->with('flash', ['success' => "Permission {$name} deleted successfully."]);
    }
}
