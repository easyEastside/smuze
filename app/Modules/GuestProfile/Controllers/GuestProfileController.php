<?php

namespace App\Modules\GuestProfile\Controllers;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GuestProfileController
{
    public function show(Request $request, User $user): View|RedirectResponse
    {
        if ($request->user()->is($user)) {
            return redirect()->route('profile.show');
        }

        $user->load('roles:id,name');

        $inventoryItems = Purchase::query()
            ->with('shopItem:id,name,short_description,image_path')
            ->whereBelongsTo($user)
            ->where('quantity', '>', 0)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('shop_item_id')
            ->map(fn ($purchases): object => (object) [
                'shopItem' => $purchases->first()->shopItem,
                'total_quantity' => $purchases->sum('quantity'),
                'last_acquired' => $purchases->max('created_at'),
            ])
            ->filter(fn (object $item): bool => $item->shopItem !== null)
            ->values();

        return view('modules.guest-profile.show', [
            'profileUser' => $user,
            'avatarUrl' => $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null,
            'inventoryItems' => $inventoryItems,
        ]);
    }
}
