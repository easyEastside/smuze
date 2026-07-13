<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

test('admin can view agent page', function () {
    $admin = User::factory()->create()->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.agent'))
        ->assertSuccessful()
        ->assertSee('Agent Release')
        ->assertSee('Build');
});

test('admin can build agent release', function () {
    $dir = storage_path('app/agent');
    $versionPath = $dir.'/version.json';
    $binaryPath = $dir.'/smuze-agent';

    $originalVersion = file_exists($versionPath) ? file_get_contents($versionPath) : null;
    $originalBinary = file_exists($binaryPath) ? file_get_contents($binaryPath) : null;

    $admin = User::factory()->create()->assignRole('admin');

    try {
        $this->actingAs($admin)
            ->post(route('admin.agent.build'), [
                'version' => '9.9.9',
            ])
            ->assertRedirect(route('admin.agent'))
            ->assertSessionHas('status');
    } finally {
        if ($originalVersion !== null) {
            file_put_contents($versionPath, $originalVersion);
        } elseif (file_exists($versionPath)) {
            unlink($versionPath);
        }

        if ($originalBinary !== null) {
            file_put_contents($binaryPath, $originalBinary);
            chmod($binaryPath, 0755);
        } elseif (file_exists($binaryPath)) {
            unlink($binaryPath);
        }

        $buildPath = storage_path('app/agent/smuze-agent.build');
        if (file_exists($buildPath)) {
            unlink($buildPath);
        }
    }
});

test('non-admin cannot access agent page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.agent'))
        ->assertForbidden();
});

test('guest cannot access agent page', function () {
    $this->get(route('admin.agent'))->assertRedirect(route('login', absolute: false));
});
