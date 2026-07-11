<?php

namespace App\Modules\Admin\Users\Controllers;

use App\Models\User;
use App\Modules\Admin\Users\Actions\DeleteUserAction;
use App\Modules\Admin\Users\Actions\StoreUserAction;
use App\Modules\Admin\Users\Actions\UpdateUserAction;
use App\Modules\Admin\Users\Requests\StoreUserRequest;
use App\Modules\Admin\Users\Requests\UpdateUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminUsersController
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->value();
        $roleFilter = $request->integer('role');

        $users = User::query()
            ->with('roles')
            ->when($search, fn ($query, $search) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->when($roleFilter, fn ($query, $roleId) => $query
                ->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId)))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('modules.admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'search' => $search,
            'roleFilter' => $roleFilter,
        ]);
    }

    public function create(): View
    {
        return view('modules.admin.users.create', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, StoreUserAction $action): RedirectResponse
    {
        $user = $action->handle(
            validated: $request->validated(),
            avatar: $request->file('avatar'),
        );

        return to_route('admin.users.index')
            ->with('flash', ['success' => "User {$user->name} created successfully."]);
    }

    public function show(User $user): View
    {
        $user->load('roles');

        return view('modules.admin.users.show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('modules.admin.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->handle(
            user: $user,
            validated: $request->validated(),
            avatar: $request->file('avatar'),
            removeAvatar: $request->boolean('remove_avatar'),
        );

        return to_route('admin.users.index')
            ->with('flash', ['success' => "User {$user->name} updated successfully."]);
    }

    public function destroy(User $user, DeleteUserAction $action): RedirectResponse
    {
        $name = $user->name;

        $action->handle($user);

        return to_route('admin.users.index')
            ->with('flash', ['success' => "User {$name} deleted successfully."]);
    }
}
