<?php

namespace App\Modules\Dashboard\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $sessionTable = config('session.table', 'sessions');
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table($sessionTable)
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): array => [
                'id' => $session->id,
                'ip_address' => $session->ip_address ?: 'Unknown',
                'user_agent' => $session->user_agent ?: 'Unknown device',
                'last_activity' => now()->createFromTimestamp((int) $session->last_activity)->diffForHumans(),
                'is_current' => hash_equals((string) $currentSessionId, (string) $session->id),
            ]);

        $passwordResetRequestedAt = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->value('created_at');

        return view('modules.dashboard.index', [
            'user' => $user,
            'sessions' => $sessions,
            'accountStats' => [
                'created_at' => $user->created_at?->format('M j, Y') ?? 'Unknown',
                'updated_at' => $user->updated_at?->diffForHumans() ?? 'Unknown',
                'email_verified' => $user->email_verified_at !== null,
                'password_reset_requested_at' => $passwordResetRequestedAt
                    ? now()->parse($passwordResetRequestedAt)->diffForHumans()
                    : null,
            ],
            'systemStats' => [
                'queued_jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'job_batches' => DB::table('job_batches')->count(),
                'active_sessions' => $sessions->count(),
            ],
        ]);
    }
}
