<x-layouts.app title="Deployments: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Deployments</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Laravel Deployment</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} - {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Neues Deployment</p>
                    <form method="POST" action="{{ route('server.deployments.store', $server) }}" class="mt-4 grid gap-4">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span>Name</span>
                                <input name="name" value="{{ old('name') }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span>GitHub Repository</span>
                                <input name="repo_url" value="{{ old('repo_url') }}" required placeholder="https://github.com/acme/app.git" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                            </label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span>Zielpfad</span>
                                <input name="target_path" value="{{ old('target_path', '/var/www/app') }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A]">
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span>Domain optional</span>
                                <input name="domain" value="{{ old('domain') }}" placeholder="example.com" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                            </label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="grid gap-1 text-sm">
                                <span>Webserver</span>
                                <select name="webserver" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                                    <option value="none" @selected(old('webserver') === 'none')>Keinen VHost erstellen</option>
                                    <option value="apache" @selected(old('webserver', 'apache') === 'apache')>Apache</option>
                                    <option value="nginx" @selected(old('webserver') === 'nginx')>Nginx</option>
                                </select>
                            </label>
                            <label class="grid gap-1 text-sm">
                                <span>PHP-Version</span>
                                <select name="php_version" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 dark:border-[#3E3E3A]">
                                    <option value="8.5" @selected(old('php_version', '8.5') === '8.5')>PHP 8.5</option>
                                    <option value="8.4" @selected(old('php_version') === '8.4')>PHP 8.4</option>
                                </select>
                            </label>
                        </div>

                        <div class="grid gap-3 rounded-xl border border-[#19140020] p-4 text-sm dark:border-[#3E3E3A]">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="write_env" value="1" @checked(old('write_env', '1') === '1') class="rounded border-[#19140035] dark:border-[#3E3E3A]">
                                .env schreiben
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="install_node" value="1" @checked(old('install_node')) class="rounded border-[#19140035] dark:border-[#3E3E3A]">
                                Node/npm sicherstellen
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="run_build" value="1" @checked(old('run_build')) class="rounded border-[#19140035] dark:border-[#3E3E3A]">
                                npm install && npm run build ausführen
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="run_migrations" value="1" @checked(old('run_migrations')) class="rounded border-[#19140035] dark:border-[#3E3E3A]">
                                php artisan migrate --force ausführen
                            </label>
                        </div>

                        <label class="grid gap-1 text-sm">
                            <span>.env Werte</span>
                            <textarea name="env" rows="6" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A]" placeholder="APP_ENV=production&#10;APP_DEBUG=false">{{ old('env', "APP_ENV=production\nAPP_DEBUG=false") }}</textarea>
                        </label>

                        <button type="submit" class="justify-self-start rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            Deployment speichern
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Gespeicherte Deployments</p>
                            <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Starte ein Deployment oder passe die Konfiguration an.</p>
                        </div>
                        <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $deployments->count() }} Deployments</span>
                    </div>

                    <div id="deployment-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>

                    <div class="mt-4 space-y-3">
                        @forelse ($deployments as $deployment)
                            <details class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                                <summary class="cursor-pointer list-none">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-medium">{{ $deployment->name }}</p>
                                                @if ($deployment->last_status)
                                                    <span class="rounded-md px-2 py-0.5 text-xs {{ $deployment->last_status === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : ($deployment->last_status === 'running' ? 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300' : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300') }}">
                                                        {{ $deployment->last_status }}
                                                    </span>
                                                @endif
                                                <span class="rounded-md bg-[#19140008] px-2 py-0.5 text-xs text-[#706f6c] dark:bg-[#fffaed0a] dark:text-[#A1A09A]">{{ $deployment->webserver }}</span>
                                            </div>
                                            <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                                <span class="font-mono">{{ $deployment->target_path }}</span>
                                                @if ($deployment->domain)
                                                    · {{ $deployment->domain }}
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" data-run-deployment data-run-url="{{ route('server.deployments.run', [$server, $deployment]) }}" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                                Deployen
                                            </button>
                                            <form method="POST" action="{{ route('server.deployments.destroy', [$server, $deployment]) }}" data-confirm="Deployment wirklich löschen?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-red-300 px-2 py-1 text-xs text-red-700 hover:border-red-500 dark:border-red-900 dark:text-red-300">Löschen</button>
                                            </form>
                                        </div>
                                    </div>
                                </summary>

                                <form method="POST" action="{{ route('server.deployments.update', [$server, $deployment]) }}" class="mt-4 grid gap-3 border-t border-[#19140020] pt-4 dark:border-[#3E3E3A]">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <input name="name" value="{{ old('name', $deployment->name) }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                        <input name="repo_url" value="{{ old('repo_url', $deployment->repo_url) }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                    </div>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <input name="target_path" value="{{ old('target_path', $deployment->target_path) }}" required class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A]">
                                        <input name="domain" value="{{ old('domain', $deployment->domain) }}" placeholder="Domain optional" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                    </div>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <select name="webserver" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                            <option value="none" @selected($deployment->webserver === 'none')>Kein VHost</option>
                                            <option value="apache" @selected($deployment->webserver === 'apache')>Apache</option>
                                            <option value="nginx" @selected($deployment->webserver === 'nginx')>Nginx</option>
                                        </select>
                                        <select name="php_version" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 text-sm dark:border-[#3E3E3A]">
                                            <option value="8.5" @selected($deployment->php_version === '8.5')>PHP 8.5</option>
                                            <option value="8.4" @selected($deployment->php_version === '8.4')>PHP 8.4</option>
                                        </select>
                                    </div>
                                    <div class="grid gap-2 text-sm md:grid-cols-2">
                                        <label class="flex items-center gap-2"><input type="checkbox" name="write_env" value="1" @checked($deployment->write_env) class="rounded border-[#19140035] dark:border-[#3E3E3A]">.env schreiben</label>
                                        <label class="flex items-center gap-2"><input type="checkbox" name="install_node" value="1" @checked($deployment->install_node) class="rounded border-[#19140035] dark:border-[#3E3E3A]">Node/npm</label>
                                        <label class="flex items-center gap-2"><input type="checkbox" name="run_build" value="1" @checked($deployment->run_build) class="rounded border-[#19140035] dark:border-[#3E3E3A]">Build</label>
                                        <label class="flex items-center gap-2"><input type="checkbox" name="run_migrations" value="1" @checked($deployment->run_migrations) class="rounded border-[#19140035] dark:border-[#3E3E3A]">Migrationen</label>
                                    </div>
                                    <textarea name="env" rows="4" class="rounded-lg border border-[#19140035] bg-transparent px-3 py-2 font-mono text-sm dark:border-[#3E3E3A]">{{ collect($deployment->env ?? [])->map(fn ($value, $key) => $key.'='.$value)->implode("\n") }}</textarea>
                                    <button type="submit" class="justify-self-start rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Änderungen speichern</button>
                                </form>

                                @if ($deployment->runs->isNotEmpty())
                                    <div class="mt-4 border-t border-[#19140020] pt-4 dark:border-[#3E3E3A]">
                                        <p class="mb-2 text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Letzte Ausführungen</p>
                                        <div class="space-y-2">
                                            @foreach ($deployment->runs as $run)
                                                <div class="rounded-lg border border-[#19140020] px-3 py-2 text-xs dark:border-[#3E3E3A]">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <span class="rounded-md px-1.5 py-0.5 text-[11px] {{ $run->status === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : ($run->status === 'running' ? 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300' : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300') }}">{{ $run->status }}</span>
                                                        <span class="text-[#706f6c] dark:text-[#A1A09A]">{{ $run->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    @if ($run->error_output)
                                                        <pre class="mt-2 overflow-x-auto whitespace-pre-wrap rounded bg-[#19140008] p-2 font-mono text-[11px] dark:bg-[#fffaed08]">{{ $run->error_output }}</pre>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </details>
                        @empty
                            <div class="rounded-xl bg-[#19140008] p-4 text-sm text-[#706f6c] dark:bg-[#fffaed08] dark:text-[#A1A09A]">Noch keine Deployments.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Ablauf</p>
                    <div class="mt-4 space-y-3 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">
                        <p><strong class="text-[#1b1b18] dark:text-[#EDEDEC]">Code:</strong> Existiert der Zielpfad schon, wird <span class="font-mono">git pull</span> ausgeführt, sonst <span class="font-mono">git clone</span>.</p>
                        <p><strong class="text-[#1b1b18] dark:text-[#EDEDEC]">Laravel:</strong> Composer install, App-Key, Cache-Aufbau und optional Migrationen.</p>
                        <p><strong class="text-[#1b1b18] dark:text-[#EDEDEC]">Webserver:</strong> Bei Domain wird ein VHost auf <span class="font-mono">public/</span> erstellt. SSL bleibt separat über Apache/Nginx möglich.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    const deploymentCsrfToken = '{{ csrf_token() }}';
    const deploymentServerId = {{ $server->id }};

    document.querySelectorAll('[data-run-deployment]').forEach(button => {
        button.addEventListener('click', () => runDeployment(button));
    });

    document.querySelectorAll('[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!confirm(form.dataset.confirm || 'Fortfahren?')) {
                event.preventDefault();
            }
        });
    });

    async function runDeployment(button) {
        const resultBox = document.getElementById('deployment-result');
        button.disabled = true;
        button.textContent = 'Läuft...';
        setDeploymentResult(resultBox, 'Deployment läuft...', 'info');

        try {
            const response = await fetch(button.dataset.runUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': deploymentCsrfToken,
                },
            });
            const data = await response.json();
            const message = deploymentErrorMessage(data);
            setDeploymentResult(resultBox, message || 'Deployment abgeschlossen.', data.success ? 'success' : 'error');

            if (!data.success) {
                appendDeploymentErrorActions(resultBox, message || 'Deployment fehlgeschlagen.', data);
            }
        } catch (error) {
            const message = 'Deployment konnte nicht gestartet werden: ' + error.message;
            setDeploymentResult(resultBox, message, 'error');
            appendDeploymentErrorActions(resultBox, message, { run: null });
        } finally {
            button.disabled = false;
            button.textContent = 'Deployen';
        }
    }

    function deploymentErrorMessage(data) {
        if (!data || data.success) {
            return data?.message || '';
        }

        return data.run?.error_output || data.run?.output || data.message || 'Deployment fehlgeschlagen.';
    }

    function setDeploymentResult(element, message, type) {
        element.classList.remove('hidden', 'bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700', 'bg-yellow-50', 'text-yellow-700');
        element.classList.add(type === 'success' ? 'bg-green-50' : (type === 'error' ? 'bg-red-50' : 'bg-yellow-50'));
        element.classList.add(type === 'success' ? 'text-green-700' : (type === 'error' ? 'text-red-700' : 'text-yellow-700'));
        element.replaceChildren(document.createTextNode(message));
    }

    function appendDeploymentErrorActions(element, message, data) {
        const actions = document.createElement('span');
        actions.className = 'mt-3 flex flex-wrap gap-2';

        const copyButton = deploymentErrorButton('Fehler kopieren');
        copyButton.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(message);
                copyButton.textContent = 'Kopiert';
                window.showToast?.('Fehler kopiert.', 'success');
                setTimeout(() => { copyButton.textContent = 'Fehler kopieren'; }, 1500);
            } catch (error) {
                window.showToast?.('Fehler konnte nicht kopiert werden.', 'error');
            }
        });
        actions.appendChild(copyButton);

        const sendButton = deploymentErrorButton('Fehler senden');
        sendButton.addEventListener('click', async () => {
            sendButton.disabled = true;
            sendButton.textContent = 'Sende...';

            try {
                const response = await fetch('/errors/report', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': deploymentCsrfToken,
                    },
                    body: JSON.stringify({
                        message,
                        source: 'deployments.run',
                        details: {
                            server_id: deploymentServerId,
                            run_id: data.run?.id || null,
                            status: data.run?.status || null,
                            exit_code: data.run?.exit_code || null,
                        },
                    }),
                });

                const result = await response.json();
                sendButton.textContent = result.success ? 'Gesendet' : 'Senden fehlgeschlagen';
                window.showToast?.(result.success ? 'Fehler gesendet.' : 'Fehler konnte nicht gesendet werden.', result.success ? 'success' : 'error');
            } catch (error) {
                sendButton.textContent = 'Senden fehlgeschlagen';
                window.showToast?.('Fehler konnte nicht gesendet werden.', 'error');
            } finally {
                setTimeout(() => {
                    sendButton.disabled = false;
                    sendButton.textContent = 'Fehler senden';
                }, 2000);
            }
        });
        actions.appendChild(sendButton);

        element.appendChild(actions);
    }

    function deploymentErrorButton(label) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'rounded-lg border border-red-300 bg-white/70 px-2 py-1 text-xs font-medium text-red-800 hover:border-red-500 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200';
        button.textContent = label;

        return button;
    }
    </script>
    @endpush
</x-layouts.app>
