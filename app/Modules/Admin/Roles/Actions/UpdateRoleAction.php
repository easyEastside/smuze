<?php

namespace App\Modules\Admin\Roles\Actions;

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UpdateRoleAction
{
    /** @param array<string, mixed> $validated */
    public function handle(Role $role, array $validated): Role
    {
        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }
}
