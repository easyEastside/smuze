<?php

namespace App\Modules\Admin\Roles\Actions;

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StoreRoleAction
{
    /** @param array<string, mixed> $validated */
    public function handle(array $validated): Role
    {
        $role = Role::create(['name' => $validated['name']]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }
}
