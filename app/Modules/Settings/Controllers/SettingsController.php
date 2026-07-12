<?php

namespace App\Modules\Settings\Controllers;

use App\Modules\Settings\Requests\UpdateSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController
{
    public function edit(Request $request): View
    {
        return view('modules.settings.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $request->user()->update([
            'show_floating_terminal' => $request->boolean('show_floating_terminal'),
            'write_debug_logs' => $request->boolean('write_debug_logs'),
        ]);

        return redirect()->route('settings.edit')->with('status', 'Settings updated.');
    }
}
