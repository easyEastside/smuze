<x-layouts.admin title="Fehlerbericht #{{ $errorReport->id }}">
    <section class="w-full max-w-4xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Fehlerbericht #{{ $errorReport->id }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="copyAll()" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                        In Zwischenablage kopieren
                    </button>
                    <a href="{{ route('admin.errors') }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Zurück
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-[#706f6c] dark:text-[#A1A09A]">Gemeldet am</dt>
                        <dd class="mt-1 font-medium">{{ $errorReport->created_at->format('d.m.Y H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[#706f6c] dark:text-[#A1A09A]">Benutzer</dt>
                        <dd class="mt-1 font-medium">{{ $errorReport->user?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[#706f6c] dark:text-[#A1A09A]">Quelle</dt>
                        <dd class="mt-1 font-medium">{{ $errorReport->source ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[#706f6c] dark:text-[#A1A09A]">Route/Referer</dt>
                        <dd class="mt-1 break-all font-mono text-xs">{{ $errorReport->route ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Fehlermeldung</p>
                <pre id="error-message" class="mt-4 overflow-x-auto rounded-xl bg-[#19140008] p-4 font-mono text-xs leading-6 text-[#1b1b18] dark:bg-[#fffaed08] dark:text-[#EDEDEC] whitespace-pre-wrap">{{ $errorReport->message }}</pre>
            </div>

            @if ($errorReport->details)
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Details</p>
                    <pre id="error-details" class="mt-4 overflow-x-auto rounded-xl bg-[#19140008] p-4 font-mono text-xs leading-6 text-[#1b1b18] dark:bg-[#fffaed08] dark:text-[#EDEDEC] whitespace-pre-wrap">{{ json_encode($errorReport->details, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
    </section>

    @push('scripts')
    <script>
    function copyAll() {
        const parts = [
            'Fehlerbericht #{{ $errorReport->id }}',
            'Zeit: {{ $errorReport->created_at->format('d.m.Y H:i:s') }}',
            'Benutzer: {{ $errorReport->user?->name ?? '—' }}',
            'Quelle: {{ $errorReport->source ?? '—' }}',
            '',
            'Fehlermeldung:',
            document.getElementById('error-message')?.textContent || '',
        ];

        const detailsEl = document.getElementById('error-details');
        if (detailsEl) {
            parts.push('', 'Details:');
            parts.push(detailsEl.textContent);
        }

        navigator.clipboard.writeText(parts.join('\n')).then(() => {
            const btn = event.target;
            const orig = btn.textContent;
            btn.textContent = 'Kopiert!';
            setTimeout(() => btn.textContent = orig, 1500);
        });
    }
    </script>
    @endpush
</x-layouts.admin>
