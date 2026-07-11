<?php

namespace App\Modules\Admin\Settings\Controllers;

use App\Models\Setting;
use App\Modules\Admin\Settings\Requests\UpdateBankSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController
{
    public function show(Request $request): View
    {
        $packages = collect([
            ['name' => 'Laravel Framework', 'version' => app()->version()],
            ['name' => 'Laravel Boost', 'version' => '2.4.11'],
            ['name' => 'Laravel Pest', 'version' => '4.7.5'],
            ['name' => 'Laravel Pint', 'version' => '1.29.3'],
            ['name' => 'Tailwind CSS', 'version' => '4.3.2'],
            ['name' => 'PHPUnit', 'version' => '12.5.30'],
        ]);

        $config = config('app');

        return view('modules.admin.settings.index', [
            'phpVersion' => PHP_VERSION,
            'environment' => app()->environment(),
            'debugMode' => $config['debug'],
            'appName' => $config['name'],
            'appUrl' => $config['url'],
            'timezone' => $config['timezone'],
            'locale' => $config['locale'],
            'databaseEngine' => 'SQLite',
            'cacheDriver' => config('cache.default'),
            'sessionDriver' => config('session.driver'),
            'serverOs' => PHP_OS,
            'packages' => $packages,
            'bankBaseHourlyInterestRate' => Setting::bankBaseHourlyInterestRate(),
        ]);
    }

    public function updateBank(UpdateBankSettingsRequest $request): RedirectResponse
    {
        Setting::setBankBaseHourlyInterestRate($request->float('bank_base_hourly_interest_rate'));

        return redirect()->route('admin.settings')->with('status', 'Bank settings saved successfully.');
    }
}
