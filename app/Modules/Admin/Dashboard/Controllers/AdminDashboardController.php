<?php

namespace App\Modules\Admin\Dashboard\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminDashboardController
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $sessionTable = config('session.table', 'sessions');
        $currentSessionId = $request->session()->getId();

        $userCount = DB::table('users')->count();

        $usersByRole = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name', DB::raw('count(*) as total'))
            ->where('model_has_roles.model_type', $user::class)
            ->groupBy('roles.name')
            ->pluck('total', 'name');

        $rolesCount = Role::count();
        $permissionsCount = Permission::count();

        $recentUsers = DB::table('users')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (object $user) {
                $roleName = DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('model_has_roles.model_id', $user->id)
                    ->where('model_has_roles.model_type', (new User)::class)
                    ->value('roles.name');

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at ? now()->parse($user->created_at)->format('M j, Y') : 'Unknown',
                    'role' => $roleName ?? 'None',
                ];
            });

        $sessions = DB::table($sessionTable)
            ->orderByDesc('last_activity')
            ->limit(10)
            ->get(['id', 'user_id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): array => [
                'id' => $session->id,
                'user_id' => $session->user_id,
                'ip_address' => $session->ip_address ?: 'Unknown',
                'user_agent' => $session->user_agent ?: 'Unknown device',
                'last_activity' => now()->createFromTimestamp((int) $session->last_activity)->diffForHumans(),
                'is_current' => hash_equals((string) $currentSessionId, (string) $session->id),
            ]);

        return view('modules.admin.dashboard.index', [
            'user' => $user,
            'userCount' => $userCount,
            'usersByRole' => $usersByRole,
            'rolesCount' => $rolesCount,
            'permissionsCount' => $permissionsCount,
            'recentUsers' => $recentUsers,
            'sessions' => $sessions,
            'systemStats' => [
                'active_sessions' => DB::table($sessionTable)->count(),
                'queued_jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
        ]);
    }
}
