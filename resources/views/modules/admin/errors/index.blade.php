<x-layouts.admin title="Fehlerberichte">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Fehlerberichte</h1>
            <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">Von Benutzern gemeldete Fehler.</p>
        </div>

        <div class="mt-6 overflow-x-auto rounded-2xl bg-white shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
            @if ($reports->isEmpty())
                <div class="p-6 text-sm text-[#706f6c] dark:text-[#A1A09A]">Keine Fehlerberichte vorhanden.</div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#19140020] text-left text-xs font-medium text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                            <th class="px-5 py-3 font-medium">Zeit</th>
                            <th class="px-5 py-3 font-medium">Benutzer</th>
                            <th class="px-5 py-3 font-medium">Quelle</th>
                            <th class="px-5 py-3 font-medium">Fehler</th>
                            <th class="px-5 py-3 text-right font-medium">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#19140020] dark:divide-[#3E3E3A]">
                        @foreach ($reports as $report)
                            <tr class="hover:bg-[#19140008] dark:hover:bg-[#fffaed08]">
                                <td class="whitespace-nowrap px-5 py-3 text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $report->created_at->diffForHumans() }}</td>
                                <td class="px-5 py-3 text-xs">{{ $report->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $report->source ?? '—' }}</td>
                                <td class="max-w-[300px] truncate px-5 py-3 text-xs">{{ $report->message }}</td>
                                <td class="px-5 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" onclick="copyError({{ $report->id }})" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                            Kopieren
                                        </button>
                                        <a href="{{ route('admin.errors.show', $report) }}" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                            Anzeigen
                                        </a>
                                        <form action="{{ route('admin.errors.destroy', $report) }}" method="POST" class="inline" onsubmit="return confirm('Fehlerbericht löschen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs text-[#f53003] hover:border-[#f53003] dark:border-[#3E3E3A] dark:text-[#FF4433] dark:hover:border-[#FF4433]">
                                                Löschen
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </section>

    @push('scripts')
    <script>
    function copyError(id) {
        const row = event.target.closest('tr');
        const text = row ? row.querySelector('td:nth-child(4)')?.textContent?.trim() || '' : '';
        navigator.clipboard.writeText(text).then(() => {
            const btn = event.target;
            const orig = btn.textContent;
            btn.textContent = 'Kopiert!';
            setTimeout(() => btn.textContent = orig, 1500);
        });
    }
    </script>
    @endpush
</x-layouts.admin>
