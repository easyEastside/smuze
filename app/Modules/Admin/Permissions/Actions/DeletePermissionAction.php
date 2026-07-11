<?php

namespace App\Modules\Admin\Permissions\Actions;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DeletePermissionAction
{
    public function handle(Permission $permission): void
    {
        $permission->delete();

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
