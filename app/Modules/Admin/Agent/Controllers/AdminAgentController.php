<?php

namespace App\Modules\Admin\Agent\Controllers;

use App\Services\AgentRelease\AgentReleaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAgentController
{
    public function index(AgentReleaseManager $manager): View
    {
        $release = $manager->currentRelease();

        return view('modules.admin.agent.index', [
            'release' => $release,
            'binaryExists' => $manager->binaryExists(),
            'binarySize' => $manager->binaryExists() ? filesize($manager->binaryPath()) : null,
        ]);
    }

    public function build(Request $request, AgentReleaseManager $manager): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:64'],
        ]);

        $result = $manager->build($data['version']);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if (! $result['success']) {
            return redirect()->route('admin.agent')->with('error', 'Build fehlgeschlagen: '.$result['message']);
        }

        return redirect()->route('admin.agent')->with('status', "Agent Release {$result['version']} wurde gebaut und veröffentlicht. Checksum: {$result['checksum']}");
    }
}
