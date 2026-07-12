<?php

namespace App\Modules\Profile\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReadProfile
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Request $request): array
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table(config('session.table', 'sessions'))
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

        return [
            'user' => $user,
            'avatarUrl' => $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null,
            'sessions' => $sessions,
            'accountStats' => [
                'created_at' => $user->created_at?->format('M j, Y') ?? 'Unknown',
                'updated_at' => $user->updated_at?->diffForHumans() ?? 'Unknown',
                'email_verified' => $user->email_verified_at !== null,
                'password_reset_requested_at' => $passwordResetRequestedAt
                    ? now()->parse($passwordResetRequestedAt)->diffForHumans()
                    : null,
            ],
        ];
    }
}
