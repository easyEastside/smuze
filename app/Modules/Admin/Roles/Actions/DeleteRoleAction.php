<?php

namespace App\Modules\Admin\Roles\Actions;

use Spatie\Permission\Models\Role;

class DeleteRoleAction
{
    public function handle(Role $role): void
    {
        $role->delete();
    }
}
