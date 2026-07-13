<x-layouts.admin title="Agent">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Agent Release</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Build and publish a new agent version. The agent is compiled from source and made available for download.
            </p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Current Release</p>

            <dl class="mt-6 divide-y divide-[#19140012] dark:divide-[#3E3E3A]">
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Version</dt>
                    <dd class="text-sm font-medium">{{ $release['version'] ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Checksum (SHA-256)</dt>
                    <dd class="max-w-[200px] truncate text-sm font-mono text-[#706f6c] dark:text-[#A1A09A]" title="{{ $release['checksum'] ?? '-' }}">{{ $release['checksum'] ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Built at</dt>
                    <dd class="text-sm font-medium">{{ isset($release['built_at']) ? \Illuminate\Support\Carbon::parse($release['built_at'])->format('Y-m-d H:i:s') : '-' }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Binary</dt>
                    <dd class="text-sm font-medium">
                        @if ($binaryExists)
                            <span class="text-green-600 dark:text-green-400">Vorhanden ({{ number_format($binarySize / 1024 / 1024, 1) }} MB)</span>
                        @else
                            <span class="text-[#f53003] dark:text-[#FF4433]">Nicht vorhanden</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Download</dt>
                    <dd class="text-sm font-medium">
                        <a href="{{ url('/agent/download') }}" class="text-[#f53003] hover:underline dark:text-[#FF4433]">/agent/download</a>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Build &amp; Release</p>
            <p class="mt-2 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Compile the Go agent from source and publish it as the current release. The binary is served via <code class="text-xs">/agent/download</code>.
            </p>

            <form method="POST" action="{{ route('admin.agent.build') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="version" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Version</label>
                    <input
                        id="version"
                        type="text"
                        name="version"
                        value="{{ old('version', $release['version'] ?? '') }}"
                        placeholder="0.5.0"
                        class="mt-2 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]"
                    />
                    @error('version')
                        <p class="mt-2 text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                    Agent neu kompilieren und veröffentlichen
                </button>
            </form>
        </div>
    </div>
</x-layouts.admin>
