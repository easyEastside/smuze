<x-layouts.app title="Cronjobs: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Cronjobs</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Zeitgesteuerte Aufgaben</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} - {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('server.cronjobs.sync', $server) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            Auf Server anwenden
                        </button>
                    </form>
                    <button type="button" id="remote-refresh" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Crontab laden
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Neuer Smuze-Cronjob</p>
                    <form method="POST" action="{{ route('server.cronjobs.store', $server) }}" class="mt-4 grid gap-4">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span>Name</span>
                                <input name="name" value="{{ old('name') }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span>Zeitplan</span>
                                <input name="schedule" value="{{ old('schedule', '0 * * * *') }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono dark:border-[#3E3E3A]">
                                <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">5 Felder: Minute Stunde Tag Monat Wochentag. Beispiel: <span class="font-mono">*/5 * * * *</span> läuft alle 5 Minuten.</span>
                            </label>
                        </div>
                        <label class="grid gap-1 text-sm">
                            <span>Command</span>
                            <input name="command" value="{{ old('command') }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono dark:border-[#3E3E3A]">
                        </label>
                        <div class="grid gap-4 md:grid-cols-3">
                            <label class="grid gap-1 text-sm md:col-span-2">
                                <span>Working Directory optional</span>
                                <input name="working_directory" value="{{ old('working_directory') }}" placeholder="/var/www/html" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono dark:border-[#3E3E3A]">
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span>Run as optional</span>
                                <input name="run_as" value="{{ old('run_as') }}" placeholder="www-data" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono dark:border-[#3E3E3A]">
                            </label>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="enabled" value="1" checked class="rounded border-[#19140035] dark:border-[#3E3E3A]">
                            Aktiv
                        </label>
                        <button type="submit" class="justify-self-start rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            Cronjob speichern
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Smuze-Cronjobs</p>
                            <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Diese Jobs kann Smuze bearbeiten und sicher synchronisieren.</p>
                        </div>
                        <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $cronjobs->count() }} Jobs</span>
                    </div>

                    <div id="run-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>

                    <div class="mt-4 space-y-3">
                        @forelse ($cronjobs as $cronjob)
                            <details class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]" @if ($loop->first) open @endif>
                                <summary class="cursor-pointer list-none">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-medium">{{ $cronjob->name }}</p>
                                                <span class="rounded-md px-2 py-0.5 text-xs {{ $cronjob->enabled ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-[#19140008] text-[#706f6c] dark:bg-[#fffaed0a] dark:text-[#A1A09A]' }}">
                                                    {{ $cronjob->enabled ? 'aktiv' : 'inaktiv' }}
                                                </span>
                                            </div>
                                            <p class="mt-1 font-mono text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $cronjob->schedule }} · {{ $cronjob->command }}</p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" data-run-button data-run-url="{{ route('server.cronjobs.run', [$server, $cronjob]) }}" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Jetzt ausführen</button>
                                            <form method="POST" action="{{ route('server.cronjobs.toggle', [$server, $cronjob]) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">{{ $cronjob->enabled ? 'Deaktivieren' : 'Aktivieren' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('server.cronjobs.destroy', [$server, $cronjob]) }}" data-confirm="Cronjob wirklich löschen?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-red-300 px-2 py-1 text-xs text-red-700 hover:border-red-500 dark:border-red-900 dark:text-red-300">Löschen</button>
                                            </form>
                                        </div>
                                    </div>
                                </summary>
                                <form method="POST" action="{{ route('server.cronjobs.update', [$server, $cronjob]) }}" class="mt-4 grid gap-3 border-t border-[#19140020] pt-4 dark:border-[#3E3E3A]">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <input name="name" value="{{ old('name', $cronjob->name) }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                        <label class="grid gap-1">
                                            <input name="schedule" value="{{ old('schedule', $cronjob->schedule) }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A]">
                                            <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Minute Stunde Tag Monat Wochentag</span>
                                        </label>
                                    </div>
                                    <input name="command" value="{{ old('command', $cronjob->command) }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A]">
                                    <div class="grid gap-3 md:grid-cols-3">
                                        <input name="working_directory" value="{{ old('working_directory', $cronjob->working_directory) }}" placeholder="/var/www/html" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A] md:col-span-2">
                                        <input name="run_as" value="{{ old('run_as', $cronjob->run_as) }}" placeholder="www-data" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A]">
                                    </div>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="enabled" value="1" @checked($cronjob->enabled) class="rounded border-[#19140035] dark:border-[#3E3E3A]">
                                        Aktiv
                                    </label>
                                    <button type="submit" class="justify-self-start rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Änderungen speichern</button>
                                </form>
                            </details>
                        @empty
                            <div class="rounded-xl bg-[#19140008] p-4 text-sm text-[#706f6c] dark:bg-[#fffaed08] dark:text-[#A1A09A]">Noch keine Smuze-Cronjobs.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server-Crontab</p>
                        <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Alle bekannten User- und System-Cronjobs. Fremde Einträge sind read-only.</p>
                    </div>
                    <span id="remote-status" class="text-xs text-[#706f6c] dark:text-[#A1A09A]">-</span>
                </div>
                <div id="remote-cronjobs" class="mt-4 space-y-2 text-sm">
                    <div class="text-[#706f6c] dark:text-[#A1A09A]">Crontab wird geladen...</div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    const csrfToken = '{{ csrf_token() }}';
    const remoteRefresh = document.getElementById('remote-refresh');

    remoteRefresh.addEventListener('click', () => loadRemoteCronjobs());

    document.querySelectorAll('[data-run-button]').forEach(button => {
        button.addEventListener('click', () => runCronjob(button));
    });

    document.querySelectorAll('[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    function textElement(tag, className, text) {
        const element = document.createElement(tag);
        element.className = className;
        element.textContent = text;

        return element;
    }

    function renderRemoteCronjobs(entries) {
        const target = document.getElementById('remote-cronjobs');
        target.replaceChildren();

        if (entries.length === 0) {
            target.appendChild(textElement('div', 'text-[#706f6c] dark:text-[#A1A09A]', 'Keine Cronjobs auf dem Server gefunden.'));
            return;
        }

        entries.forEach(entry => {
            const card = document.createElement('div');
            card.className = 'rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]';

            const header = document.createElement('div');
            header.className = 'flex flex-wrap items-center justify-between gap-2';

            const badge = textElement('span', entry.managed ? 'rounded-md bg-green-50 px-2 py-0.5 text-xs text-green-700 dark:bg-green-950 dark:text-green-300' : 'rounded-md bg-[#19140008] px-2 py-0.5 text-xs text-[#706f6c] dark:bg-[#fffaed0a] dark:text-[#A1A09A]', entry.managed ? 'Smuze' : 'Fremd');
            const schedule = textElement('span', 'font-mono text-xs text-[#706f6c] dark:text-[#A1A09A]', entry.schedule || '');
            header.append(badge, schedule);

            const meta = document.createElement('div');
            meta.className = 'mt-2 flex flex-wrap gap-2 text-[11px] text-[#706f6c] dark:text-[#A1A09A]';
            meta.append(
                textElement('span', '', `Quelle: ${entry.source || 'crontab'}`),
                textElement('span', '', `User: ${entry.user || '-'}`),
            );

            const command = textElement('pre', 'mt-2 overflow-x-auto whitespace-pre-wrap font-mono text-xs', entry.command || entry.line || '');

            card.append(header, meta, command);
            target.appendChild(card);
        });
    }

    async function loadRemoteCronjobs() {
        const status = document.getElementById('remote-status');
        status.textContent = 'lädt...';

        try {
            const response = await fetch('{{ route('server.cronjobs.remote', $server) }}', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Crontab konnte nicht geladen werden.');
            }

            renderRemoteCronjobs(data.entries || []);
            status.textContent = new Date().toLocaleTimeString('de-DE');
        } catch (error) {
            const remoteCronjobs = document.getElementById('remote-cronjobs');
            remoteCronjobs.replaceChildren();
            const div = document.createElement('div');
            div.className = 'rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            div.appendChild(document.createTextNode(error.message));
            div.appendChild(window.reportError(error.message, 'cronjobs'));
            remoteCronjobs.appendChild(div);
            status.textContent = 'Fehler';
        }
    }

    async function runCronjob(button) {
        if (!confirm('Cronjob jetzt ausführen?')) return;

        const result = document.getElementById('run-result');
        button.disabled = true;
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.textContent = 'Cronjob läuft...';
        result.classList.remove('hidden');

        try {
            const response = await fetch(button.dataset.runUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || data.stderr || 'Ausführung fehlgeschlagen.');
            }

            result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
            result.replaceChildren(
                document.createTextNode(`Ausgeführt mit Exit-Code ${data.exit_code}`),
                textElement('pre', 'mt-2 overflow-auto whitespace-pre-wrap font-mono text-xs', data.stdout || ''),
            );
        } catch (error) {
            result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            result.replaceChildren();
            result.appendChild(document.createTextNode(error.message));
            result.appendChild(window.reportError(error.message, 'cronjobs'));
        } finally {
            button.disabled = false;
        }
    }

    loadRemoteCronjobs();
    </script>
    @endpush
</x-layouts.app>
