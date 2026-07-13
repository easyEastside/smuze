<x-layouts.app title="Docker: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Docker</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Docker-Container-Verwaltung</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} — {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="refreshDocker()" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Aktualisieren
                    </button>
                    <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Zurück zum System
                    </a>
                </div>
            </div>
        </div>

        <div id="dk-loading" class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Verbinde zum Server...</p>
        </div>

        <div id="dk-install-overlay" class="mt-6 hidden">
            <div class="rounded-2xl bg-white p-12 text-center shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                <p class="text-lg font-semibold">Docker ist nicht installiert</p>
                <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Installiere Docker um Container zu verwalten.</p>
                <button type="button" id="btn-install-docker" onclick="installDocker()" class="mt-6 rounded-lg bg-[#1b1b18] px-6 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                    Docker installieren
                </button>
                <div id="dk-install-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
            </div>
        </div>

        <div id="dk-content" class="mt-6 hidden">
            <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
                <div class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Status</p>
                                <span id="dk-status-badge" class="rounded-full px-2.5 py-0.5 text-xs font-medium"></span>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="button" onclick="dockerServiceAction('start')" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Start</button>
                                <button type="button" onclick="dockerServiceAction('stop')" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Stop</button>
                                <button type="button" onclick="dockerServiceAction('restart')" class="rounded-lg bg-yellow-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-yellow-700">Restart</button>
                            </div>
                        </div>
                        <div id="dk-version" class="mt-3 text-xs text-[#706f6c] dark:text-[#A1A09A]"></div>

                        <div class="mt-4 flex items-center gap-2">
                            <button type="button" onclick="dockerDeinstall()" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                Docker deinstallieren
                            </button>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Docker-Info</p>
                        <div id="dk-info-grid" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Container</p>
                                <p id="dk-info-containers" class="mt-1 text-lg font-semibold">-</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Running</p>
                                <p id="dk-info-running" class="mt-1 text-lg font-semibold text-green-600">-</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Images</p>
                                <p id="dk-info-images" class="mt-1 text-lg font-semibold">-</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Docker-Version</p>
                                <p id="dk-info-version" class="mt-1 text-lg font-semibold">-</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Container</p>
                            <div class="flex items-center gap-2">
                                <label class="flex items-center gap-1.5 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                    <input type="checkbox" id="dk-show-all" onchange="loadContainers()" checked class="text-[#f53003]">
                                    Alle
                                </label>
                                <button type="button" onclick="showContainerCreateForm()" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                                    + Container erstellen
                                </button>
                            </div>
                        </div>

                        <div id="dk-containers-empty" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Keine Container gefunden.</div>
                        <div id="dk-containers-table" class="mt-4 hidden overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-[#19140020] text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                        <th class="px-3 py-2 font-medium">Container ID</th>
                                        <th class="px-3 py-2 font-medium">Image</th>
                                        <th class="px-3 py-2 font-medium">Status</th>
                                        <th class="px-3 py-2 font-medium">Ports</th>
                                        <th class="px-3 py-2 font-medium">Name</th>
                                        <th class="px-3 py-2 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody id="dk-containers-tbody"></tbody>
                            </table>
                        </div>

                        <div id="dk-container-create-form" class="mt-6 hidden border-t border-[#19140020] pt-6 dark:border-[#3E3E3A]">
                            <p class="text-sm font-medium">Container erstellen</p>
                            <div class="mt-3 space-y-3">
                                <div>
                                    <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Image</label>
                                    <input type="text" id="dk-create-image" placeholder="nginx:latest" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Container-Name (optional)</label>
                                    <input type="text" id="dk-create-name" placeholder="my-nginx" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Port-Weiterleitung (optional, z. B. 8080:80)</label>
                                    <input type="text" id="dk-create-ports" placeholder="8080:80" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Env-Vars (optional, KEY=VAL, pro Zeile)</label>
                                    <textarea id="dk-create-env" rows="2" placeholder="APP_ENV=production" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]"></textarea>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Volume (optional, /host:/container)</label>
                                    <input type="text" id="dk-create-volume" placeholder="/local/data:/data" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="createContainer()" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Erstellen</button>
                                    <button type="button" onclick="hideContainerCreateForm()" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Abbrechen</button>
                                </div>
                                <div id="dk-create-result" class="hidden rounded-xl p-3 text-sm"></div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Images</p>
                            <button type="button" onclick="showPullForm()" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                                + Image pullen
                            </button>
                        </div>

                        <div id="dk-images-empty" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Keine Images gefunden.</div>
                        <div id="dk-images-table" class="mt-4 hidden overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-[#19140020] text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                        <th class="px-3 py-2 font-medium">Repository</th>
                                        <th class="px-3 py-2 font-medium">Tag</th>
                                        <th class="px-3 py-2 font-medium">Image ID</th>
                                        <th class="px-3 py-2 font-medium">Größe</th>
                                        <th class="px-3 py-2 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody id="dk-images-tbody"></tbody>
                            </table>
                        </div>

                        <div id="dk-pull-form" class="mt-6 hidden border-t border-[#19140020] pt-6 dark:border-[#3E3E3A]">
                            <p class="text-sm font-medium">Image pullen</p>
                            <div class="mt-3 flex gap-2">
                                <input type="text" id="dk-pull-image" placeholder="nginx:latest" class="block flex-1 rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <button type="button" onclick="pullImage()" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Pullen</button>
                            </div>
                            <div id="dk-pull-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Docker Compose</p>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="composeUp()" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Up</button>
                                <button type="button" onclick="composeDown()" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Down</button>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Projekt-Pfad (optional)</label>
                            <div class="mt-1 flex gap-2">
                                <input type="text" id="dk-compose-path" placeholder="/opt/project" class="block flex-1 rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <button type="button" onclick="loadCompose()" class="rounded-lg bg-[#1b1b18] px-3 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Laden</button>
                            </div>
                        </div>
                        <div id="dk-compose-empty" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Keine Compose-Services gefunden.</div>
                        <div id="dk-compose-table" class="mt-4 hidden overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-[#19140020] text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                        <th class="px-3 py-2 font-medium">Name</th>
                                        <th class="px-3 py-2 font-medium">Image</th>
                                        <th class="px-3 py-2 font-medium">Status</th>
                                        <th class="px-3 py-2 font-medium">Ports</th>
                                    </tr>
                                </thead>
                                <tbody id="dk-compose-tbody"></tbody>
                            </table>
                        </div>
                        <div id="dk-compose-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Netzwerke</p>
                        <div id="dk-networks-loading" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Lade Netzwerke...</div>
                        <div id="dk-networks-list" class="mt-4 hidden space-y-1 max-h-80 overflow-y-auto"></div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">System</p>
                        <div class="mt-4 space-y-3">
                            <button type="button" onclick="systemPrune()" class="w-full rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                Docker System Prune
                            </button>
                            <button type="button" onclick="systemPruneAll()" class="w-full rounded-lg border border-red-300 px-4 py-2 text-sm text-red-600 hover:border-red-500 dark:border-red-800 dark:text-red-400">
                                Docker System Prune -a
                            </button>
                        </div>
                        <div id="dk-prune-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                    </div>

                    <div id="dk-result" class="hidden rounded-xl p-3 text-sm"></div>
                </aside>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    function showResult(msg, success) {
        const el = document.getElementById('dk-result');
        el.className = 'rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        el.innerHTML = '';
        el.appendChild(document.createTextNode(msg));
        if (!success) {
            el.appendChild(window.reportError(msg, 'docker'));
        }
        el.classList.remove('hidden');
    }

    function routeSegment(value) {
        return encodeURIComponent(value);
    }

    function refreshDocker() {
        const loading = document.getElementById('dk-loading');
        const content = document.getElementById('dk-content');
        const installOverlay = document.getElementById('dk-install-overlay');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        installOverlay.classList.add('hidden');

        fetch('{{ route('server.docker.status', $server) }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');
                if (!data.success) {
                    loading.textContent = 'Fehler: ' + (data.error || 'Unbekannter Fehler');
                    loading.classList.remove('hidden');
                    return;
                }
                if (!data.installed) {
                    installOverlay.classList.remove('hidden');
                    return;
                }
                content.classList.remove('hidden');
                renderDockerStatus(data);
                loadDockerInfo();
                loadContainers();
                loadImages();
                loadNetworks();
            })
            .catch(err => {
                loading.textContent = 'Verbindungsfehler: ' + err.message;
                loading.classList.remove('hidden');
            });
    }

    function renderDockerStatus(data) {
        const badge = document.getElementById('dk-status-badge');
        const version = document.getElementById('dk-version');
        if (data.active) {
            badge.textContent = 'Aktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        } else {
            badge.textContent = 'Inaktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        }
        let versionText = data.version || '';
        if (data.compose_version) versionText += ' | Compose: ' + data.compose_version;
        version.textContent = versionText;
    }

    function dockerServiceAction(action) {
        const labels = { start: 'starten', stop: 'stoppen', restart: 'neu starten' };
        if (!confirm('Docker ' + labels[action] + '?')) return;

        showResult('Docker wird ' + labels[action] + '...', true);
        fetch('{{ route('server.docker.service', ['server' => $server, 'action' => '__ACTION__']) }}'.replace('__ACTION__', action), { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(refreshDocker, 2000);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function loadDockerInfo() {
        fetch('{{ route('server.docker.info', $server) }}')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                document.getElementById('dk-info-containers').textContent = data.containers_total ?? '-';
                document.getElementById('dk-info-running').textContent = data.containers_running ?? '-';
                document.getElementById('dk-info-images').textContent = data.images_total ?? '-';
                document.getElementById('dk-info-version').textContent = data.server_version || '-';
            });
    }

    function loadContainers() {
        const all = document.getElementById('dk-show-all').checked;
        fetch('{{ route('server.docker.ps', $server) }}?all=' + all)
            .then(r => r.json())
            .then(data => {
                const empty = document.getElementById('dk-containers-empty');
                const table = document.getElementById('dk-containers-table');
                const tbody = document.getElementById('dk-containers-tbody');
                if (!data.success || !data.containers || data.containers.length === 0) {
                    empty.classList.remove('hidden');
                    table.classList.add('hidden');
                    return;
                }
                empty.classList.add('hidden');
                tbody.innerHTML = '';
                for (const c of data.containers) {
                    const status = (c.STATUS || c.Status || '').toLowerCase();
                    const isRunning = status.startsWith('up');
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-[#19140020] dark:border-[#3E3E3A]';
                    tr.innerHTML = `
                        <td class="px-3 py-2 font-mono text-xs">${(c.CONTAINER_ID || c['Container ID'] || '').substring(0, 12)}</td>
                        <td class="px-3 py-2 text-xs">${c.IMAGE || c.Image || ''}</td>
                        <td class="px-3 py-2">
                            <span class="rounded px-2 py-0.5 text-xs font-medium ${isRunning ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-[#19140020] text-[#706f6c] dark:bg-[#3E3E3A] dark:text-[#A1A09A]'}">${c.STATUS || c.Status || ''}</span>
                        </td>
                        <td class="px-3 py-2 text-xs font-mono">${c.PORTS || c.Ports || '-'}</td>
                        <td class="px-3 py-2 text-xs font-medium">${c.NAMES || c.Names || ''}</td>
                        <td class="px-3 py-2">
                            <div class="flex gap-1">
                                ${isRunning
                                    ? `<button onclick="containerAction('stop', '${c.CONTAINER_ID || c['Container ID'] || ''}')" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400">Stop</button>
                                       <button onclick="containerAction('restart', '${c.CONTAINER_ID || c['Container ID'] || ''}')" class="text-xs text-yellow-600 hover:text-yellow-800 dark:text-yellow-400">Restart</button>`
                                    : `<button onclick="containerAction('start', '${c.CONTAINER_ID || c['Container ID'] || ''}')" class="text-xs text-green-600 hover:text-green-800 dark:text-green-400">Start</button>`
                                }
                                <button onclick="showContainerLogs('${c.CONTAINER_ID || c['Container ID'] || ''}')" class="text-xs text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-white">Logs</button>
                                <button onclick="showContainerExec('${c.CONTAINER_ID || c['Container ID'] || ''}')" class="text-xs text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-white">Exec</button>
                                <button onclick="containerRemove('${c.CONTAINER_ID || c['Container ID'] || ''}')" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400">Löschen</button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                }
                table.classList.remove('hidden');
            });
    }

    function containerAction(action, container) {
        const labels = { start: 'starten', stop: 'stoppen', restart: 'neu starten' };
        if (!confirm('Container ' + container.substring(0, 12) + ' ' + labels[action] + '?')) return;
        showResult('Container wird ' + labels[action] + '...', true);

        const urls = {
            start: '{{ route('server.docker.containers.start', ['server' => $server, 'container' => '__CONTAINER__']) }}',
            stop: '{{ route('server.docker.containers.stop', ['server' => $server, 'container' => '__CONTAINER__']) }}',
            restart: '{{ route('server.docker.containers.restart', ['server' => $server, 'container' => '__CONTAINER__']) }}',
        };
        const url = urls[action].replace('__CONTAINER__', routeSegment(container));

        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(loadContainers, 1500);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function containerRemove(container) {
        if (!confirm('Container ' + container.substring(0, 12) + ' wirklich löschen?')) return;
        showResult('Lösche Container...', true);

        fetch('{{ route('server.docker.containers.remove', ['server' => $server, 'container' => '__CONTAINER__']) }}'.replace('__CONTAINER__', routeSegment(container)), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(loadContainers, 1500);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function showContainerLogs(container) {
        const logs = prompt('Anzahl der Log-Zeilen (Standard: 100):', '100');
        if (logs === null) return;
        const tail = parseInt(logs) || 100;
        showResult('Lade Logs...', true);

        fetch('{{ route('server.docker.containers.logs', ['server' => $server, 'container' => '__CONTAINER__']) }}'.replace('__CONTAINER__', routeSegment(container)) + '?tail=' + tail)
            .then(r => r.json())
            .then(data => {
                showResult(data.output || 'Keine Logs vorhanden.', data.success);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function showContainerExec(container) {
        const command = prompt('Befehl ausführen in ' + container.substring(0, 12) + ':', 'bash');
        if (!command) return;
        showResult('Führe Befehl aus...', true);

        fetch('{{ route('server.docker.containers.exec', ['server' => $server, 'container' => '__CONTAINER__']) }}'.replace('__CONTAINER__', routeSegment(container)), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ command }),
        })
            .then(r => r.json())
            .then(data => {
                showResult(data.output || data.message || 'Ausgeführt.', data.success);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function showContainerCreateForm() {
        document.getElementById('dk-container-create-form').classList.remove('hidden');
    }

    function hideContainerCreateForm() {
        document.getElementById('dk-container-create-form').classList.add('hidden');
    }

    function createContainer() {
        const image = document.getElementById('dk-create-image').value.trim();
        const name = document.getElementById('dk-create-name').value.trim();
        const ports = document.getElementById('dk-create-ports').value.trim();
        const envRaw = document.getElementById('dk-create-env').value.trim();
        const volume = document.getElementById('dk-create-volume').value.trim();

        if (!image) {
            showResult('Image-Name ist erforderlich.', false);
            return;
        }

        const result = document.getElementById('dk-create-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        result.textContent = 'Erstelle Container...';
        result.classList.remove('hidden');

        const env = envRaw ? envRaw.split('\n').map(l => l.trim()).filter(l => l) : [];

        fetch('{{ route('server.docker.containers.create', $server) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ image, name, ports, env, volume }),
        })
            .then(r => r.json())
            .then(data => {
                result.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                result.textContent = data.message || 'Container erstellt.';
                if (data.success) {
                    document.getElementById('dk-create-image').value = '';
                    document.getElementById('dk-create-name').value = '';
                    document.getElementById('dk-create-ports').value = '';
                    document.getElementById('dk-create-env').value = '';
                    document.getElementById('dk-create-volume').value = '';
                    setTimeout(() => { hideContainerCreateForm(); loadContainers(); }, 1500);
                }
            })
            .catch(err => {
                result.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
            });
    }

    function loadImages() {
        fetch('{{ route('server.docker.images', $server) }}')
            .then(r => r.json())
            .then(data => {
                const empty = document.getElementById('dk-images-empty');
                const table = document.getElementById('dk-images-table');
                const tbody = document.getElementById('dk-images-tbody');
                if (!data.success || !data.images || data.images.length === 0) {
                    empty.classList.remove('hidden');
                    table.classList.add('hidden');
                    return;
                }
                empty.classList.add('hidden');
                tbody.innerHTML = '';
                for (const img of data.images) {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-[#19140020] dark:border-[#3E3E3A]';
                    tr.innerHTML = `
                        <td class="px-3 py-2 text-xs">${img.REPOSITORY || img.Repository || ''}</td>
                        <td class="px-3 py-2 text-xs">${img.TAG || img.Tag || ''}</td>
                        <td class="px-3 py-2 font-mono text-xs">${(img.IMAGE_ID || img['Image ID'] || '').substring(0, 19)}</td>
                        <td class="px-3 py-2 text-xs">${img.SIZE || img.Size || ''}</td>
                        <td class="px-3 py-2">
                            <button onclick="imageRemove('${img.IMAGE_ID || img['Image ID'] || ''}')" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400">Löschen</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                }
                table.classList.remove('hidden');
            });
    }

    function showPullForm() {
        document.getElementById('dk-pull-form').classList.remove('hidden');
    }

    function pullImage() {
        const image = document.getElementById('dk-pull-image').value.trim();
        if (!image) {
            showResult('Image-Name ist erforderlich.', false);
            return;
        }

        const result = document.getElementById('dk-pull-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        result.textContent = 'Pulle Image ' + image + '...';
        result.classList.remove('hidden');

        fetch('{{ route('server.docker.images.pull', $server) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ image }),
        })
            .then(r => r.json())
            .then(data => {
                result.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                result.textContent = data.message;
                if (data.success) {
                    document.getElementById('dk-pull-image').value = '';
                    setTimeout(() => { document.getElementById('dk-pull-form').classList.add('hidden'); loadImages(); }, 1500);
                }
            })
            .catch(err => {
                result.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
            });
    }

    function imageRemove(image) {
        if (!confirm('Image ' + image.substring(0, 19) + ' wirklich löschen?')) return;
        showResult('Lösche Image...', true);

        fetch('{{ route('server.docker.images.remove', ['server' => $server, 'image' => '__IMAGE__']) }}'.replace('__IMAGE__', routeSegment(image)), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(loadImages, 1500);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function loadNetworks() {
        const loading = document.getElementById('dk-networks-loading');
        const list = document.getElementById('dk-networks-list');
        loading.classList.remove('hidden');
        list.classList.add('hidden');

        fetch('{{ route('server.docker.networks', $server) }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');
                if (!data.success || !data.networks || data.networks.length === 0) {
                    loading.textContent = 'Keine Netzwerke gefunden.';
                    loading.classList.remove('hidden');
                    return;
                }
                list.innerHTML = '';
                for (const net of data.networks) {
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between rounded-lg border border-[#19140020] px-3 py-1.5 text-xs dark:border-[#3E3E3A]';
                    div.innerHTML = `
                        <span class="font-medium">${net.NAME || net.Name || ''}</span>
                        <span class="text-[#706f6c] dark:text-[#A1A09A]">${net.DRIVER || net.Driver || ''}</span>
                    `;
                    list.appendChild(div);
                }
                list.classList.remove('hidden');
            })
            .catch(err => {
                showResult('Fehler: ' + err.message, false);
                loading.classList.add('hidden');
            });
    }

    function loadCompose() {
        const path = document.getElementById('dk-compose-path').value.trim() || null;
        const params = path ? '?project_path=' + encodeURIComponent(path) : '';

        fetch('{{ route('server.docker.compose.ps', $server) }}' + params)
            .then(r => r.json())
            .then(data => {
                const empty = document.getElementById('dk-compose-empty');
                const table = document.getElementById('dk-compose-table');
                const tbody = document.getElementById('dk-compose-tbody');
                if (!data.success || !data.compose_services || data.compose_services.length === 0) {
                    empty.classList.remove('hidden');
                    table.classList.add('hidden');
                    return;
                }
                empty.classList.add('hidden');
                tbody.innerHTML = '';
                for (const svc of data.compose_services) {
                    const status = (svc.STATUS || svc.Status || '').toLowerCase();
                    const isUp = status.startsWith('up');
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-[#19140020] dark:border-[#3E3E3A]';
                    tr.innerHTML = `
                        <td class="px-3 py-2 text-xs font-medium">${svc.NAME || svc.Name || ''}</td>
                        <td class="px-3 py-2 text-xs">${svc.IMAGE || svc.Image || ''}</td>
                        <td class="px-3 py-2">
                            <span class="rounded px-2 py-0.5 text-xs font-medium ${isUp ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-[#19140020] text-[#706f6c] dark:bg-[#3E3E3A] dark:text-[#A1A09A]'}">${svc.STATUS || svc.Status || ''}</span>
                        </td>
                        <td class="px-3 py-2 text-xs font-mono">${svc.PORTS || svc.Ports || '-'}</td>
                    `;
                    tbody.appendChild(tr);
                }
                table.classList.remove('hidden');
            });
    }

    function composeUp() {
        const path = document.getElementById('dk-compose-path').value.trim() || null;
        if (!confirm('Docker Compose starten?')) return;

        showResult('Starte Docker Compose...', true);
        const body = path ? JSON.stringify({ project_path: path }) : '{}';

        fetch('{{ route('server.docker.compose.up', $server) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body,
        })
            .then(r => r.json())
            .then(data => {
                const el = document.getElementById('dk-compose-result');
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.message;
                el.classList.remove('hidden');
                showResult(data.message, data.success);
                if (data.success) setTimeout(loadCompose, 2000);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function composeDown() {
        const path = document.getElementById('dk-compose-path').value.trim() || null;
        if (!confirm('Docker Compose stoppen?')) return;

        showResult('Stoppe Docker Compose...', true);
        const body = path ? JSON.stringify({ project_path: path }) : '{}';

        fetch('{{ route('server.docker.compose.down', $server) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body,
        })
            .then(r => r.json())
            .then(data => {
                const el = document.getElementById('dk-compose-result');
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.message;
                el.classList.remove('hidden');
                showResult(data.message, data.success);
                if (data.success) setTimeout(loadCompose, 2000);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function systemPrune() {
        if (!confirm('Docker System Prune ausführen?\nNicht verwendete Daten werden gelöscht.')) return;
        showResult('Bereinige Docker-System...', true);

        fetch('{{ route('server.docker.system-prune', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                const el = document.getElementById('dk-prune-result');
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.output || data.message;
                el.classList.remove('hidden');
                showResult(data.message, data.success);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function systemPruneAll() {
        if (!confirm('WIRKLICH Docker System Prune -a ausführen?\nALLE ungenutzten Images, Container, Volumes und Netzwerke werden gelöscht!')) return;
        showResult('Bereinige Docker-System (alle)...', true);

        fetch('{{ route('server.docker.system-prune', $server) }}?all=1', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                const el = document.getElementById('dk-prune-result');
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.output || data.message;
                el.classList.remove('hidden');
                showResult(data.message, data.success);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function installDocker() {
        const btn = document.getElementById('btn-install-docker');
        const result = document.getElementById('dk-install-result');
        btn.disabled = true;
        btn.textContent = 'Installiere...';
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.textContent = 'Docker wird installiert. Bitte warten...';
        result.classList.remove('hidden');

        fetch('{{ route('server.docker.install', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                    result.textContent = 'Docker wurde installiert.';
                    window.showToast('Docker wurde installiert.', 'success');
                    setTimeout(refreshDocker, 2000);
                } else {
                    result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                    result.textContent = data.message;
                    btn.disabled = false;
                    btn.textContent = 'Docker installieren';
                }
            })
            .catch(err => {
                result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
                btn.disabled = false;
                btn.textContent = 'Docker installieren';
            });
    }

    function dockerDeinstall() {
        if (!confirm('Docker wirklich deinstallieren?\nAlle Container, Images und Konfigurationen werden gelöscht.')) return;
        showResult('Deinstalliere Docker...', true);

        fetch('{{ route('server.docker.deinstall', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) {
                    window.showToast(data.message, 'success');
                    setTimeout(refreshDocker, 2000);
                }
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    refreshDocker();
    </script>
    @endpush
</x-layouts.app>
