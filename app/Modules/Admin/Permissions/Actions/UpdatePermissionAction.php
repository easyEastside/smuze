<?php

namespace App\Modules\Admin\Permissions\Actions;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class UpdatePermissionAction
{
    /** @param array<string, mixed> $validated */
    public function handle(Permission $permission, array $validated): Permission
    {
        $permission->update(['name' => $validated['name']]);

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        return $permission;
    }
}
