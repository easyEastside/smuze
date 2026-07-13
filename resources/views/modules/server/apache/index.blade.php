<x-layouts.app title="Apache: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Apache</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Apache-Webserver-Verwaltung</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} — {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="refreshApache()" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Aktualisieren
                    </button>
                    <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Zurück zum System
                    </a>
                </div>
            </div>
        </div>

        <div id="ap-loading" class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Verbinde zum Server...</p>
        </div>

        <div id="ap-install-overlay" class="mt-6 hidden">
            <div class="rounded-2xl bg-white p-12 text-center shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                <p class="text-lg font-semibold">Apache ist nicht installiert</p>
                <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Installiere Apache um den Webserver zu verwalten.</p>
                <button type="button" id="btn-install-apache" onclick="installApache()" class="mt-6 rounded-lg bg-[#1b1b18] px-6 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                    Apache installieren
                </button>
                <div id="ap-install-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
            </div>
        </div>

        <div id="ap-content" class="mt-6 hidden">
            <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
                <div class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Status</p>
                                <span id="ap-status-badge" class="rounded-full px-2.5 py-0.5 text-xs font-medium"></span>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="button" onclick="apacheServiceAction('start')" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Start</button>
                                <button type="button" onclick="apacheServiceAction('stop')" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Stop</button>
                                <button type="button" onclick="apacheServiceAction('restart')" class="rounded-lg bg-yellow-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-yellow-700">Restart</button>
                                <button type="button" onclick="apacheServiceAction('reload')" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Reload</button>
                            </div>
                        </div>
                        <div id="ap-version" class="mt-3 text-xs text-[#706f6c] dark:text-[#A1A09A]"></div>

                        <div class="mt-4 flex items-center gap-2">
                            <button type="button" onclick="apacheConfigtest()" class="rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                                Config Test
                            </button>
                            <button type="button" onclick="apacheDeinstall()" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                Apache deinstallieren
                            </button>
                        </div>
                        <div id="ap-configtest-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Sites</p>
                            <button type="button" onclick="showVhostForm()" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                                + VHost erstellen
                            </button>
                        </div>

                        <div id="ap-sites-empty" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Keine Sites konfiguriert.</div>
                        <div id="ap-sites-table" class="mt-4 hidden overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-[#19140020] text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                        <th class="px-3 py-2 font-medium">Name</th>
                                        <th class="px-3 py-2 font-medium">Status</th>
                                        <th class="px-3 py-2 font-medium">ServerName</th>
                                        <th class="px-3 py-2 font-medium">DocumentRoot</th>
                                        <th class="px-3 py-2 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody id="ap-sites-tbody"></tbody>
                            </table>
                        </div>

                        <div id="ap-vhost-form" class="mt-6 hidden border-t border-[#19140020] pt-6 dark:border-[#3E3E3A]">
                            <p class="text-sm font-medium">VHost erstellen</p>
                            <div class="mt-3 space-y-3">
                                <div>
                                    <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Domain</label>
                                    <input type="text" id="ap-domain" placeholder="example.com" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">DocumentRoot</label>
                                    <input type="text" id="ap-docroot" placeholder="/var/www/example" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">ServerAlias (optional)</label>
                                    <input type="text" id="ap-alias" placeholder="www.example.com" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                </div>
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" id="ap-ssl" onchange="toggleSslEmail()" class="text-[#f53003]">
                                        SSL (Let's Encrypt)
                                    </label>
                                </div>
                                <div id="ap-email-group" class="hidden">
                                    <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">E-Mail für Let's Encrypt</label>
                                    <input type="email" id="ap-email" placeholder="admin@example.com" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="createVhost()" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Erstellen</button>
                                    <button type="button" onclick="hideVhostForm()" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Abbrechen</button>
                                </div>
                                <div id="ap-vhost-result" class="hidden rounded-xl p-3 text-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Module</p>
                        <div>
                            <input type="text" id="ap-module-filter" oninput="filterModules()" placeholder="Modul filtern..." class="mt-3 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                        </div>
                        <div id="ap-modules-loading" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Lade Module...</div>
                        <div id="ap-modules-list" class="mt-4 hidden space-y-1 max-h-80 overflow-y-auto"></div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">SSL / Let's Encrypt</p>
                        <div class="mt-4 space-y-3">
                            <button type="button" onclick="installCertbot()" class="w-full rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                Certbot installieren
                            </button>
                            <div>
                                <input type="text" id="ap-ssl-domain" placeholder="Domain" class="block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                            </div>
                            <div>
                                <input type="email" id="ap-ssl-email" placeholder="E-Mail" class="block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                            </div>
                            <button type="button" onclick="obtainSsl()" class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                                SSL-Zertifikat beantragen
                            </button>
                        </div>
                        <div id="ap-ssl-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                    </div>

                    <div id="ap-result" class="hidden rounded-xl p-3 text-sm"></div>
                </aside>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    function showResult(msg, success) {
        const el = document.getElementById('ap-result');
        el.className = 'rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function routeSegment(value) {
        return encodeURIComponent(value);
    }

    function refreshApache() {
        const loading = document.getElementById('ap-loading');
        const content = document.getElementById('ap-content');
        const installOverlay = document.getElementById('ap-install-overlay');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        installOverlay.classList.add('hidden');

        fetch('{{ route('server.apache.status', $server) }}')
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
                renderApacheStatus(data);
                loadSites();
                loadModules();
            })
            .catch(err => {
                loading.textContent = 'Verbindungsfehler: ' + err.message;
                loading.classList.remove('hidden');
            });
    }

    function renderApacheStatus(data) {
        const badge = document.getElementById('ap-status-badge');
        const version = document.getElementById('ap-version');
        if (data.active) {
            badge.textContent = 'Aktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        } else {
            badge.textContent = 'Inaktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        }
        version.textContent = data.version || '';
    }

    function apacheServiceAction(action) {
        const labels = { start: 'starten', stop: 'stoppen', restart: 'neu starten', reload: 'neu laden' };
        if (!confirm('Apache ' + labels[action] + '?')) return;

        showResult('Apache wird ' + labels[action] + '...', true);
        fetch('{{ route('server.apache.service', ['server' => $server, 'action' => '__ACTION__']) }}'.replace('__ACTION__', action), { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(refreshApache, 2000);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function apacheConfigtest() {
        const el = document.getElementById('ap-configtest-result');
        el.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        el.textContent = 'Prüfe Konfiguration...';
        el.classList.remove('hidden');

        fetch('{{ route('server.apache.configtest', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                const ok = 'Syntax OK';
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success || (data.output && data.output.includes(ok)) ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.output || (data.success ? 'Syntax OK' : 'Fehler');
            })
            .catch(err => {
                el.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                el.textContent = 'Fehler: ' + err.message;
            });
    }

    function loadSites() {
        fetch('{{ route('server.apache.sites', $server) }}').then(r => r.json())
            .then(data => {
                const empty = document.getElementById('ap-sites-empty');
                const table = document.getElementById('ap-sites-table');
                const tbody = document.getElementById('ap-sites-tbody');
                if (!data.success || !data.sites || data.sites.length === 0) {
                    empty.classList.remove('hidden');
                    table.classList.add('hidden');
                    return;
                }
                empty.classList.add('hidden');
                tbody.innerHTML = '';
                for (const site of data.sites) {
                    const enabled = site.enabled === 'yes';
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-[#19140020] dark:border-[#3E3E3A]';
                    tr.innerHTML = `
                        <td class="px-3 py-2 font-medium">${site.name}</td>
                        <td class="px-3 py-2">
                            <span class="rounded px-2 py-0.5 text-xs font-medium ${enabled ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-[#19140020] text-[#706f6c] dark:bg-[#3E3E3A] dark:text-[#A1A09A]'}">${enabled ? 'Enabled' : 'Disabled'}</span>
                        </td>
                        <td class="px-3 py-2 text-xs">${site.server_name}</td>
                        <td class="px-3 py-2 text-xs">${site.document_root}</td>
                        <td class="px-3 py-2">
                            <div class="flex gap-1">
                                ${enabled
                                    ? `<button onclick="apacheSiteAction('disable', '${site.name}')" class="text-xs text-yellow-600 hover:text-yellow-800 dark:text-yellow-400">Deaktivieren</button>`
                                    : `<button onclick="apacheSiteAction('enable', '${site.name}')" class="text-xs text-green-600 hover:text-green-800 dark:text-green-400">Aktivieren</button>`
                                }
                                <button onclick="apacheDeleteSite('${site.name}')" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400">Löschen</button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                }
                table.classList.remove('hidden');
            });
    }

    function apacheSiteAction(action, site) {
        const label = action === 'enable' ? 'aktivieren' : 'deaktivieren';
        if (!confirm('Site ' + site + ' ' + label + '?')) return;
        showResult('Site wird ' + label + '...', true);

        const url = action === 'enable'
            ? '{{ route('server.apache.sites.enable', ['server' => $server, 'site' => '__SITE__']) }}'.replace('__SITE__', routeSegment(site))
            : '{{ route('server.apache.sites.disable', ['server' => $server, 'site' => '__SITE__']) }}'.replace('__SITE__', routeSegment(site));

        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(refreshApache, 1500);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function apacheDeleteSite(site) {
        if (!confirm('Site ' + site + ' wirklich löschen?')) return;
        showResult('Lösche Site...', true);

        fetch('{{ route('server.apache.sites.delete', ['server' => $server, 'site' => '__SITE__']) }}'.replace('__SITE__', routeSegment(site)), { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(refreshApache, 1500);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function showVhostForm() {
        document.getElementById('ap-vhost-form').classList.remove('hidden');
    }

    function hideVhostForm() {
        document.getElementById('ap-vhost-form').classList.add('hidden');
    }

    function toggleSslEmail() {
        const ssl = document.getElementById('ap-ssl').checked;
        document.getElementById('ap-email-group').classList.toggle('hidden', !ssl);
    }

    function createVhost() {
        const domain = document.getElementById('ap-domain').value.trim();
        const docRoot = document.getElementById('ap-docroot').value.trim();
        const alias = document.getElementById('ap-alias').value.trim();
        const useSsl = document.getElementById('ap-ssl').checked;
        const email = document.getElementById('ap-email').value.trim();

        const result = document.getElementById('ap-vhost-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        result.textContent = 'Erstelle VHost...';
        result.classList.remove('hidden');

        fetch('{{ route('server.apache.vhost', $server) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ domain, document_root: docRoot, server_alias: alias, use_ssl: useSsl, email }),
        })
            .then(r => r.json())
            .then(data => {
                result.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                result.textContent = data.message;
                if (data.success) {
                    document.getElementById('ap-domain').value = '';
                    document.getElementById('ap-docroot').value = '';
                    document.getElementById('ap-alias').value = '';
                    document.getElementById('ap-ssl').checked = false;
                    document.getElementById('ap-email').value = '';
                    document.getElementById('ap-email-group').classList.add('hidden');
                    setTimeout(() => { hideVhostForm(); refreshApache(); }, 1500);
                }
            })
            .catch(err => {
                result.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
            });
    }

    function loadModules() {
        const loading = document.getElementById('ap-modules-loading');
        const list = document.getElementById('ap-modules-list');
        loading.classList.remove('hidden');
        loading.textContent = 'Lade Module...';
        list.classList.add('hidden');

        fetch('{{ route('server.apache.modules', $server) }}').then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');
                if (!data.success) {
                    loading.textContent = data.message || 'Fehler beim Laden der Module.';
                    loading.classList.remove('hidden');
                    return;
                }
                if (!data.modules || data.modules.length === 0) {
                    loading.textContent = 'Keine Module gefunden.';
                    loading.classList.remove('hidden');
                    return;
                }
                list.innerHTML = '';
                for (const mod of data.modules) {
                    const enabled = mod.enabled === 'enabled';
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between rounded-lg border border-[#19140020] px-3 py-1.5 text-xs dark:border-[#3E3E3A]';
                    div.dataset.moduleName = mod.name;
                    div.innerHTML = `
                        <span class="flex items-center gap-1.5">
                            <span class="size-1.5 rounded-full ${enabled ? 'bg-green-500' : 'bg-[#19140035] dark:bg-[#3E3E3A]'}"></span>
                            ${mod.name}
                        </span>
                        ${enabled
                            ? `<button onclick="apacheModuleAction('disable', '${mod.name}')" class="text-red-600 hover:text-red-800 dark:text-red-400">Deaktivieren</button>`
                            : `<button onclick="apacheModuleAction('enable', '${mod.name}')" class="text-green-600 hover:text-green-800 dark:text-green-400">Aktivieren</button>`
                        }
                    `;
                    list.appendChild(div);
                }
                list.classList.remove('hidden');
            })
            .catch(() => {
                loading.textContent = 'Fehler beim Laden.';
                loading.classList.remove('hidden');
            });
    }

    function filterModules() {
        const filter = document.getElementById('ap-module-filter').value.toLowerCase();
        document.querySelectorAll('#ap-modules-list > div').forEach(div => {
            const name = div.dataset.moduleName || '';
            div.style.display = name.includes(filter) ? '' : 'none';
        });
    }

    function apacheModuleAction(action, mod) {
        const label = action === 'enable' ? 'aktivieren' : 'deaktivieren';
        if (!confirm('Modul ' + mod + ' ' + label + '?')) return;
        showResult('Modul wird ' + label + '...', true);

        const url = action === 'enable'
            ? '{{ route('server.apache.modules.enable', ['server' => $server, 'module' => '__MOD__']) }}'.replace('__MOD__', routeSegment(mod))
            : '{{ route('server.apache.modules.disable', ['server' => $server, 'module' => '__MOD__']) }}'.replace('__MOD__', routeSegment(mod));

        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(loadModules, 1500);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function installApache() {
        const btn = document.getElementById('btn-install-apache');
        const result = document.getElementById('ap-install-result');
        btn.disabled = true;
        btn.textContent = 'Installiere...';
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.textContent = 'Apache wird installiert. Bitte warten...';
        result.classList.remove('hidden');

        fetch('{{ route('server.apache.install', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                    result.textContent = 'Apache wurde installiert.';
                    setTimeout(refreshApache, 2000);
                } else {
                    result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                    result.textContent = data.message;
                    btn.disabled = false;
                    btn.textContent = 'Apache installieren';
                }
            })
            .catch(err => {
                result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
                btn.disabled = false;
                btn.textContent = 'Apache installieren';
            });
    }

    function apacheDeinstall() {
        if (!confirm('Apache wirklich deinstallieren?\nAlle Konfigurationen werden gelöscht.')) return;
        showResult('Deinstalliere Apache...', true);

        fetch('{{ route('server.apache.deinstall', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(refreshApache, 2000);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function installCertbot() {
        showResult('Installiere Certbot...', true);
        fetch('{{ route('server.apache.ssl.install-certbot', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                const el = document.getElementById('ap-ssl-result');
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.message;
                el.classList.remove('hidden');
                showResult(data.message, data.success);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function obtainSsl() {
        const domain = document.getElementById('ap-ssl-domain').value.trim();
        const email = document.getElementById('ap-ssl-email').value.trim();
        if (!domain || !email) {
            showResult('Domain und E-Mail sind erforderlich.', false);
            return;
        }
        showResult('Beantrage SSL-Zertifikat...', true);

        fetch('{{ route('server.apache.ssl.obtain', $server) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ domain, email }),
        })
            .then(r => r.json())
            .then(data => {
                const el = document.getElementById('ap-ssl-result');
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.message;
                el.classList.remove('hidden');
                showResult(data.message, data.success);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    refreshApache();
    </script>
    @endpush
</x-layouts.app>
