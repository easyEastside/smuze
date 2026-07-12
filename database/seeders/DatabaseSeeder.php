<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AchievementSeeder::class,
        ]);

        $password = '00000000';

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make($password),
        ])->assignRole('user');

        User::factory()->create([
            'name' => 'Moderator User',
            'email' => 'moderator@example.com',
            'password' => Hash::make($password),
        ])->assignRole('moderator');

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make($password),
        ])->assignRole('admin');

        User::factory()->create([
            'name' => 'Super Admin User',
            'email' => 'super-admin@example.com',
            'password' => Hash::make($password),
        ])->assignRole('super-admin');
    }
}
