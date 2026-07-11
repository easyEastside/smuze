<?php

namespace App\Modules\Admin\Permissions\Actions;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class StorePermissionAction
{
    /** @param array<string, mixed> $validated */
    public function handle(array $validated): Permission
    {
        $permission = Permission::create(['name' => $validated['name']]);

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        return $permission;
    }
}
