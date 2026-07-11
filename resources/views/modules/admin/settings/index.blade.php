<x-layouts.admin title="Settings">
    @if (session('status'))
        <div class="mb-6 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800 shadow-[inset_0_0_0_1px_rgba(22,101,52,0.16)] dark:bg-green-950 dark:text-green-200 dark:shadow-[inset_0_0_0_1px_rgba(187,247,208,0.18)]">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Settings</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                System information and configuration overview.
            </p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Bank</p>
                <h2 class="mt-2 text-2xl font-semibold">Interest settings</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                    Controls the base interest per hour. Shorter investments keep the best hourly yield, while higher amounts receive stronger multipliers.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.settings.bank.update') }}" class="w-full max-w-sm space-y-4">
                @csrf

                <div>
                    <label for="bank_base_hourly_interest_rate" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Bank base interest per hour (%)</label>
                    <input
                        id="bank_base_hourly_interest_rate"
                        type="number"
                        name="bank_base_hourly_interest_rate"
                        value="{{ old('bank_base_hourly_interest_rate', $bankBaseHourlyInterestRate) }}"
                        min="0"
                        max="10"
                        step="0.01"
                        class="mt-2 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                    />
                    @error('bank_base_hourly_interest_rate')
                        <p class="mt-2 text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                    Save bank settings
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Application</p>

            <dl class="mt-6 divide-y divide-[#19140012] dark:divide-[#3E3E3A]">
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">App name</dt>
                    <dd class="text-sm font-medium">{{ $appName }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Environment</dt>
                    <dd class="text-sm font-medium">{{ $environment }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Debug mode</dt>
                    <dd class="text-sm font-medium">
                        @if ($debugMode)
                            <span class="text-[#f53003] dark:text-[#FF4433]">Enabled</span>
                        @else
                            <span class="text-green-600 dark:text-green-400">Disabled</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">URL</dt>
                    <dd class="text-sm font-medium">{{ $appUrl }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Timezone</dt>
                    <dd class="text-sm font-medium">{{ $timezone }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Locale</dt>
                    <dd class="text-sm font-medium">{{ $locale }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">System</p>

            <dl class="mt-6 divide-y divide-[#19140012] dark:divide-[#3E3E3A]">
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">PHP version</dt>
                    <dd class="text-sm font-medium">{{ $phpVersion }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Laravel version</dt>
                    <dd class="text-sm font-medium">{{ app()->version() }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Database</dt>
                    <dd class="text-sm font-medium">{{ $databaseEngine }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Cache driver</dt>
                    <dd class="text-sm font-medium">{{ $cacheDriver }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Session driver</dt>
                    <dd class="text-sm font-medium">{{ $sessionDriver }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Server OS</dt>
                    <dd class="text-sm font-medium">{{ $serverOs }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Packages</p>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($packages as $package)
                <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                    <p class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">{{ $package['name'] }}</p>
                    <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">v{{ $package['version'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
