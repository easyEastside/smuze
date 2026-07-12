<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('access-admin');

        Role::findOrCreate('user');
        Role::findOrCreate('moderator');
        Role::findOrCreate('admin')->givePermissionTo('access-admin');
        Role::findOrCreate('super-admin');

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
