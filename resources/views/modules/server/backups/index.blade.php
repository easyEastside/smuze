<x-layouts.app title="Backups: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Backups</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Backup-Verwaltung</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} - {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Neue Backup-Konfiguration</p>
                    <form method="POST" action="{{ route('server.backups.store', $server) }}" class="mt-4 grid gap-4" x-data="{ type: 'mysql', storage: 'local', hasSchedule: false }">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span>Name</span>
                                <input name="name" value="{{ old('name') }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span>Typ</span>
                                <select name="type" x-model="type" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                                    <option value="mysql">MySQL</option>
                                    <option value="files">Dateien</option>
                                    <option value="both">MySQL + Dateien</option>
                                </select>
                            </label>
                        </div>

                        <label class="grid gap-1 text-sm">
                            <span>Ziele</span>
                            <template x-if="type === 'mysql' || type === 'both'">
                                <div class="mb-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">MySQL-Datenbanken (eine pro Zeile)</div>
                            </template>
                            <template x-if="type === 'files' || type === 'both'">
                                <div class="mb-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">Verzeichnisse (eine pro Zeile)</div>
                            </template>
                            <textarea name="targets" rows="3" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A" placeholder="{{ old('targets', "database\n/var/www") }}">{{ is_array(old('targets')) ? implode("\n", old('targets')) : old('targets') }}</textarea>
                        </label>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span>Speicher</span>
                                <select name="storage" x-model="storage" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                                    <option value="local">Lokal (Server)</option>
                                    <option value="s3">S3-kompatibel</option>
                                </select>
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span>Aufbewahrung (Tage)</span>
                                <input type="number" name="retention_days" value="{{ old('retention_days', 7) }}" min="1" max="365" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                            </label>
                        </div>

                        <template x-if="storage === 's3'">
                            <div class="grid gap-4 rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                                <p class="text-sm font-medium">S3-Konfiguration</p>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="grid gap-1 text-sm">
                                        <span>Bucket</span>
                                        <input name="s3_config[bucket]" value="{{ old('s3_config.bucket') }}" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                                    </label>
                                    <label class="grid gap-1 text-sm">
                                        <span>Region</span>
                                        <input name="s3_config[region]" value="{{ old('s3_config.region') }}" placeholder="eu-central-1" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                                    </label>
                                </div>
                                <label class="grid gap-1 text-sm">
                                    <span>Endpoint (optional, z.B. für MinIO)</span>
                                    <input name="s3_config[endpoint]" value="{{ old('s3_config.endpoint') }}" placeholder="https://s3.example.com" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                                </label>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="grid gap-1 text-sm">
                                        <span>Access Key ID</span>
                                        <input name="s3_config[access_key_id]" value="{{ old('s3_config.access_key_id') }}" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                                    </label>
                                    <label class="grid gap-1 text-sm">
                                        <span>Secret Access Key</span>
                                        <input type="password" name="s3_config[secret_access_key]" value="{{ old('s3_config.secret_access_key') }}" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                                    </label>
                                </div>
                            </div>
                        </template>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="enabled" value="1" checked class="rounded border-[#19140035] dark:border-[#3E3E3A]">
                                Aktiv
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" x-model="hasSchedule" class="rounded border-[#19140035] dark:border-[#3E3E3A]">
                                Zeitplan verwenden
                            </label>
                        </div>

                        <template x-if="hasSchedule">
                            <label class="grid gap-1 text-sm">
                                <span>Zeitplan (Cron)</span>
                                <input name="schedule" value="{{ old('schedule', '0 3 * * *') }}" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono dark:border-[#3E3E3A]">
                                <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">5 Felder: Minute Stunde Tag Monat Wochentag. Beispiel: <span class="font-mono">0 3 * * *</span> läuft täglich um 3 Uhr.</span>
                            </label>
                        </template>

                        <button type="submit" class="justify-self-start rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            Backup-Konfiguration speichern
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Backup-Konfigurationen</p>
                            <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Verwalte deine Backup-Jobs und führe sie manuell aus.</p>
                        </div>
                        <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $backups->count() }} Konfigurationen</span>
                    </div>

                    <div id="run-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>

                    <div class="mt-4 space-y-3">
                        @forelse ($backups as $backup)
                            <details class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                                <summary class="cursor-pointer list-none">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-medium">{{ $backup->name }}</p>
                                                <span class="rounded-md px-2 py-0.5 text-xs {{ $backup->enabled ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-[#19140008] text-[#706f6c] dark:bg-[#fffaed0a] dark:text-[#A1A09A]' }}">
                                                    {{ $backup->enabled ? 'aktiv' : 'inaktiv' }}
                                                </span>
                                                <span class="rounded-md bg-[#19140008] px-2 py-0.5 text-xs text-[#706f6c] dark:bg-[#fffaed0a] dark:text-[#A1A09A]">
                                                    {{ $backup->type }}
                                                </span>
                                                <span class="rounded-md bg-[#19140008] px-2 py-0.5 text-xs text-[#706f6c] dark:bg-[#fffaed0a] dark:text-[#A1A09A]">
                                                    {{ $backup->storage }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                Ziele: {{ is_array($backup->targets) ? implode(', ', $backup->targets) : $backup->targets }}
                                                @if ($backup->schedule)
                                                    · Zeitplan: <span class="font-mono">{{ $backup->schedule }}</span>
                                                @endif
                                                · Retention: {{ $backup->retention_days }} Tage
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($backup->last_status)
                                                <span class="rounded-md px-2 py-0.5 text-xs {{ $backup->last_status === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : ($backup->last_status === 'running' ? 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300' : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300') }}">
                                                    {{ $backup->last_status }}
                                                </span>
                                            @endif
                                            <button type="button" data-run-backup data-run-url="{{ route('server.backups.run', [$server, $backup]) }}" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                                Jetzt ausführen
                                            </button>
                                            <form method="POST" action="{{ route('server.backups.toggle', [$server, $backup]) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">{{ $backup->enabled ? 'Deaktivieren' : 'Aktivieren' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('server.backups.destroy', [$server, $backup]) }}" data-confirm="Backup-Konfiguration wirklich löschen?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-red-300 px-2 py-1 text-xs text-red-700 hover:border-red-500 dark:border-red-900 dark:text-red-300">Löschen</button>
                                            </form>
                                        </div>
                                    </div>
                                </summary>
                                <form method="POST" action="{{ route('server.backups.update', [$server, $backup]) }}" class="mt-4 grid gap-3 border-t border-[#19140020] pt-4 dark:border-[#3E3E3A]">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <input name="name" value="{{ old('name', $backup->name) }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                        <select name="type" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                            <option value="mysql" @selected($backup->type === 'mysql')>MySQL</option>
                                            <option value="files" @selected($backup->type === 'files')>Dateien</option>
                                            <option value="both" @selected($backup->type === 'both')>MySQL + Dateien</option>
                                        </select>
                                    </div>
                                    <textarea name="targets" rows="2" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A]">{{ is_array($backup->targets) ? implode("\n", $backup->targets) : $backup->targets }}</textarea>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <select name="storage" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                            <option value="local" @selected($backup->storage === 'local')>Lokal (Server)</option>
                                            <option value="s3" @selected($backup->storage === 's3')>S3-kompatibel</option>
                                        </select>
                                        <input type="number" name="retention_days" value="{{ old('retention_days', $backup->retention_days) }}" min="1" max="365" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                    </div>
                                    <input name="schedule" value="{{ old('schedule', $backup->schedule) }}" placeholder="Cron (optional)" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A]">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="enabled" value="1" @checked($backup->enabled) class="rounded border-[#19140035] dark:border-[#3E3E3A]">
                                        Aktiv
                                    </label>
                                    <button type="submit" class="justify-self-start rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Änderungen speichern</button>
                                </form>

                                @if ($backup->archives->isNotEmpty())
                                    <div class="mt-4 border-t border-[#19140020] pt-4 dark:border-[#3E3E3A]">
                                        <p class="mb-2 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Letzte Archive</p>
                                        <div class="space-y-2">
                                            @foreach ($backup->archives->take(5) as $archive)
                                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[#19140020] px-3 py-2 text-xs dark:border-[#3E3E3A]">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="rounded-md px-1.5 py-0.5 text-[11px] {{ $archive->status === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : ($archive->status === 'running' ? 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300' : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300') }}">
                                                            {{ $archive->status }}
                                                        </span>
                                                        <span>{{ $archive->filename }}</span>
                                                        @if ($archive->size_bytes)
                                                            <span class="text-[#706f6c] dark:text-[#A1A09A]">({{ number_format($archive->size_bytes / 1024 / 1024, 1) }} MB)</span>
                                                        @endif
                                                        <span class="text-[#706f6c] dark:text-[#A1A09A]">{{ $archive->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    @if ($archive->status === 'success')
                                                        <div class="flex gap-1">
                                                            <button type="button" data-restore-archive data-restore-url="{{ route('server.backups.archives.restore', [$server, $archive]) }}" class="rounded border border-[#19140035] px-2 py-0.5 hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Wiederherstellen</button>
                                                            <button type="button" data-delete-archive data-delete-url="{{ route('server.backups.archives.destroy', [$server, $archive]) }}" class="rounded border border-red-300 px-2 py-0.5 text-red-700 hover:border-red-500 dark:border-red-900 dark:text-red-300">Löschen</button>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </details>
                        @empty
                            <div class="rounded-xl bg-[#19140008] p-4 text-sm text-[#706f6c] dark:bg-[#fffaed08] dark:text-[#A1A09A]">Noch keine Backup-Konfigurationen.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Hinweise</p>
                    <div class="mt-4 space-y-3 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">
                        <p><strong class="text-[#1b1b18] dark:text-[#EDEDEC]">MySQL:</strong> Es werden <span class="font-mono">mysqldump</span>-Dumps der angegebenen Datenbanken erstellt.</p>
                        <p><strong class="text-[#1b1b18] dark:text-[#EDEDEC]">Dateien:</strong> Angegebene Verzeichnisse werden als <span class="font-mono">tar.gz</span> archiviert.</p>
                        <p><strong class="text-[#1b1b18] dark:text-[#EDEDEC]">Retention:</strong> Nach der angegebenen Anzahl Tage werden alte Backups automatisch gelöscht.</p>
                        <p><strong class="text-[#1b1b18] dark:text-[#EDEDEC]">S3:</strong> Die Zugangsdaten werden verschlüsselt in der Datenbank gespeichert.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
    const csrfToken = '{{ csrf_token() }}';

    document.querySelectorAll('[data-run-backup]').forEach(button => {
        button.addEventListener('click', () => runBackup(button));
    });

    document.querySelectorAll('[data-restore-archive]').forEach(button => {
        button.addEventListener('click', () => restoreArchive(button));
    });

    document.querySelectorAll('[data-delete-archive]').forEach(button => {
        button.addEventListener('click', () => deleteArchive(button));
    });

    document.querySelectorAll('[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    async function runBackup(button) {
        if (!confirm('Backup jetzt ausführen?')) return;

        const result = document.getElementById('run-result');
        button.disabled = true;
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.textContent = 'Backup läuft...';
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
                throw new Error(data.message || 'Backup fehlgeschlagen.');
            }

            result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
            result.textContent = 'Backup erfolgreich: ' + data.message;

            setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            result.replaceChildren();
            result.appendChild(document.createTextNode(error.message));
            result.appendChild(window.reportError(error.message, 'backups'));
        } finally {
            button.disabled = false;
        }
    }

    async function restoreArchive(button) {
        if (!confirm('Backup wirklich wiederherstellen? Dies überschreibt vorhandene Daten.')) return;

        const result = document.getElementById('run-result');
        button.disabled = true;
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.textContent = 'Stelle Backup wieder her...';
        result.classList.remove('hidden');

        try {
            const response = await fetch(button.dataset.restoreUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Wiederherstellung fehlgeschlagen.');
            }

            result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
            result.textContent = 'Backup wiederhergestellt: ' + data.message;
        } catch (error) {
            result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            result.replaceChildren();
            result.appendChild(document.createTextNode(error.message));
            result.appendChild(window.reportError(error.message, 'backups'));
        } finally {
            button.disabled = false;
        }
    }

    async function deleteArchive(button) {
        if (!confirm('Archiv wirklich löschen?')) return;

        try {
            const response = await fetch(button.dataset.deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Löschen fehlgeschlagen.');
            }

            window.location.reload();
        } catch (error) {
            alert(error.message);
        }
    }

    </script>
    @endpush
</x-layouts.app>
