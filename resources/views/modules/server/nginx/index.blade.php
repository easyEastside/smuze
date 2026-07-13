<x-layouts.app title="Nginx: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Nginx</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Nginx-Webserver-Verwaltung</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} - {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="refreshNginx()" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Aktualisieren</button>
                    <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Zurück zum System</a>
                </div>
            </div>
        </div>

        <div id="ngx-loading" class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Verbinde zum Server...</p>
        </div>

        <div id="ngx-install-overlay" class="mt-6 hidden">
            <div class="rounded-2xl bg-white p-12 text-center shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                <p class="text-lg font-semibold">Nginx ist nicht installiert</p>
                <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Installiere Nginx um den Webserver zu verwalten.</p>
                <button type="button" id="btn-install-nginx" onclick="installNginx()" class="mt-6 rounded-lg bg-[#1b1b18] px-6 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Nginx installieren</button>
                <div id="ngx-install-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
            </div>
        </div>

        <div id="ngx-content" class="mt-6 hidden">
            <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
                <div class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Status</p>
                                <span id="ngx-status-badge" class="rounded-full px-2.5 py-0.5 text-xs font-medium"></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" onclick="nginxServiceAction('start')" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Start</button>
                                <button type="button" onclick="nginxServiceAction('stop')" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Stop</button>
                                <button type="button" onclick="nginxServiceAction('restart')" class="rounded-lg bg-yellow-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-yellow-700">Restart</button>
                                <button type="button" onclick="nginxServiceAction('reload')" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Reload</button>
                            </div>
                        </div>
                        <div id="ngx-version" class="mt-3 text-xs text-[#706f6c] dark:text-[#A1A09A]"></div>
                        <div class="mt-4 flex items-center gap-2">
                            <button type="button" onclick="nginxConfigtest()" class="rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Config Test</button>
                            <button type="button" onclick="nginxDeinstall()" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Nginx deinstallieren</button>
                        </div>
                        <div id="ngx-configtest-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Sites</p>
                            <button type="button" onclick="showVhostForm()" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">+ VHost erstellen</button>
                        </div>
                        <div id="ngx-sites-empty" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Keine Sites konfiguriert.</div>
                        <div id="ngx-sites-table" class="mt-4 hidden overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-[#19140020] text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                        <th class="px-3 py-2 font-medium">Name</th>
                                        <th class="px-3 py-2 font-medium">Status</th>
                                        <th class="px-3 py-2 font-medium">ServerName</th>
                                        <th class="px-3 py-2 font-medium">Root</th>
                                        <th class="px-3 py-2 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody id="ngx-sites-tbody"></tbody>
                            </table>
                        </div>

                        <div id="ngx-vhost-form" class="mt-6 hidden border-t border-[#19140020] pt-6 dark:border-[#3E3E3A]">
                            <p class="text-sm font-medium">VHost erstellen</p>
                            <div class="mt-3 space-y-3">
                                <input type="text" id="ngx-domain" placeholder="example.com" class="block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <input type="text" id="ngx-docroot" placeholder="/var/www/example/public" class="block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <input type="text" id="ngx-alias" placeholder="www.example.com" class="block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" id="ngx-ssl" onchange="toggleSslEmail()" class="text-[#f53003]"> SSL (Let's Encrypt)</label>
                                <div id="ngx-email-group" class="hidden"><input type="email" id="ngx-email" placeholder="admin@example.com" class="block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]"></div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="createVhost()" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Erstellen</button>
                                    <button type="button" onclick="hideVhostForm()" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Abbrechen</button>
                                </div>
                                <div id="ngx-vhost-result" class="hidden rounded-xl p-3 text-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">SSL / Let's Encrypt</p>
                        <div class="mt-4 space-y-3">
                            <button type="button" onclick="installCertbot()" class="w-full rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Certbot installieren</button>
                            <input type="text" id="ngx-ssl-domain" placeholder="Domain" class="block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <input type="email" id="ngx-ssl-email" placeholder="E-Mail" class="block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                            <button type="button" onclick="obtainSsl()" class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">SSL-Zertifikat beantragen</button>
                        </div>
                        <div id="ngx-ssl-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                    </div>
                    <div id="ngx-result" class="hidden rounded-xl p-3 text-sm"></div>
                </aside>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    function showResult(msg, success) {
        const el = document.getElementById('ngx-result');
        el.className = 'rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        el.innerHTML = '';
        el.appendChild(document.createTextNode(msg));
        if (!success) {
            el.appendChild(window.reportError(msg, 'nginx'));
        }
        el.classList.remove('hidden');
    }

    function routeSegment(value) { return encodeURIComponent(value); }

    function refreshNginx() {
        const loading = document.getElementById('ngx-loading');
        const content = document.getElementById('ngx-content');
        const installOverlay = document.getElementById('ngx-install-overlay');
        loading.classList.remove('hidden');
        content.classList.add('hidden');
        installOverlay.classList.add('hidden');

        fetch('{{ route('server.nginx.status', $server) }}')
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
                renderNginxStatus(data);
                loadSites();
            })
            .catch(err => {
                loading.textContent = 'Verbindungsfehler: ' + err.message;
                loading.classList.remove('hidden');
            });
    }

    function renderNginxStatus(data) {
        const badge = document.getElementById('ngx-status-badge');
        document.getElementById('ngx-version').textContent = data.version || '';
        if (data.active) {
            badge.textContent = 'Aktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        } else {
            badge.textContent = 'Inaktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        }
    }

    function nginxServiceAction(action) {
        const labels = { start: 'starten', stop: 'stoppen', restart: 'neu starten', reload: 'neu laden' };
        if (!confirm('Nginx ' + labels[action] + '?')) return;
        showResult('Nginx wird ' + labels[action] + '...', true);
        fetch('{{ route('server.nginx.service', ['server' => $server, 'action' => '__ACTION__']) }}'.replace('__ACTION__', action), { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json()).then(data => { showResult(data.message, data.success); if (data.success) setTimeout(refreshNginx, 2000); })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function nginxConfigtest() {
        const el = document.getElementById('ngx-configtest-result');
        el.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        el.textContent = 'Prüfe Konfiguration...';
        el.classList.remove('hidden');
        fetch('{{ route('server.nginx.configtest', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json()).then(data => {
                const ok = data.output && data.output.includes('test is successful');
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success || ok ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.output || (data.success ? 'Syntax OK' : 'Fehler');
            })
            .catch(err => { el.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200'; el.textContent = 'Fehler: ' + err.message; });
    }

    function loadSites() {
        fetch('{{ route('server.nginx.sites', $server) }}').then(r => r.json()).then(data => {
            const empty = document.getElementById('ngx-sites-empty');
            const table = document.getElementById('ngx-sites-table');
            const tbody = document.getElementById('ngx-sites-tbody');
            if (!data.success || !data.sites || data.sites.length === 0) {
                empty.classList.remove('hidden');
                table.classList.add('hidden');
                tbody.innerHTML = '';
                return;
            }
            empty.classList.add('hidden');
            table.classList.remove('hidden');
            tbody.innerHTML = data.sites.map(site => `
                <tr class="border-b border-[#19140010] dark:border-[#3E3E3A]">
                    <td class="px-3 py-2 font-medium">${site.name}</td>
                    <td class="px-3 py-2">${site.enabled === 'yes' ? 'Aktiv' : 'Inaktiv'}</td>
                    <td class="px-3 py-2">${site.server_name}</td>
                    <td class="px-3 py-2">${site.document_root}</td>
                    <td class="px-3 py-2 text-right space-x-2">
                        ${site.enabled === 'yes' ? `<button onclick="nginxSiteAction('disable', '${site.name}')" class="text-xs text-yellow-600 hover:text-yellow-800 dark:text-yellow-400">Deaktivieren</button>` : `<button onclick="nginxSiteAction('enable', '${site.name}')" class="text-xs text-green-600 hover:text-green-800 dark:text-green-400">Aktivieren</button>`}
                        <button onclick="nginxDeleteSite('${site.name}', '${site.document_root}')" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400">Löschen</button>
                    </td>
                </tr>`).join('');
        }).catch(err => showResult('Fehler: ' + err.message, false));
    }

    function nginxSiteAction(action, site) {
        const url = action === 'enable'
            ? '{{ route('server.nginx.sites.enable', ['server' => $server, 'site' => '__SITE__']) }}'.replace('__SITE__', routeSegment(site))
            : '{{ route('server.nginx.sites.disable', ['server' => $server, 'site' => '__SITE__']) }}'.replace('__SITE__', routeSegment(site));
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json()).then(data => { showResult(data.message, data.success); if (data.success) setTimeout(refreshNginx, 1500); })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function nginxDeleteSite(site, documentRoot) {
        if (!confirm('Site ' + site + ' löschen?')) return;
        fetch('{{ route('server.nginx.sites.delete', ['server' => $server, 'site' => '__SITE__']) }}'.replace('__SITE__', routeSegment(site)), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ delete_project: false, document_root: documentRoot || '' })
        }).then(r => r.json()).then(data => { showResult(data.message, data.success); if (data.success) setTimeout(refreshNginx, 1500); })
          .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function showVhostForm() { document.getElementById('ngx-vhost-form').classList.remove('hidden'); }
    function hideVhostForm() { document.getElementById('ngx-vhost-form').classList.add('hidden'); }
    function toggleSslEmail() { document.getElementById('ngx-email-group').classList.toggle('hidden', !document.getElementById('ngx-ssl').checked); }

    function createVhost() {
        const result = document.getElementById('ngx-vhost-result');
        result.className = 'rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        result.textContent = 'Erstelle VHost...';
        result.classList.remove('hidden');
        fetch('{{ route('server.nginx.vhost', $server) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ domain: document.getElementById('ngx-domain').value, document_root: document.getElementById('ngx-docroot').value, server_alias: document.getElementById('ngx-alias').value, use_ssl: document.getElementById('ngx-ssl').checked, email: document.getElementById('ngx-email').value })
        }).then(r => r.json()).then(data => {
            result.className = 'rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
            result.textContent = data.message || 'Fertig';
            if (data.success) setTimeout(() => { hideVhostForm(); refreshNginx(); }, 1500);
        }).catch(err => { result.className = 'rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200'; result.textContent = 'Fehler: ' + err.message; });
    }

    function installNginx() {
        const btn = document.getElementById('btn-install-nginx');
        const result = document.getElementById('ngx-install-result');
        btn.disabled = true;
        btn.textContent = 'Installiere...';
        result.className = 'mt-4 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        result.textContent = 'Nginx wird installiert. Bitte warten...';
        result.classList.remove('hidden');
        fetch('{{ route('server.nginx.install', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json()).then(data => { result.textContent = data.message; if (data.success) setTimeout(refreshNginx, 2000); })
            .catch(err => { result.textContent = 'Fehler: ' + err.message; })
            .finally(() => { btn.disabled = false; btn.textContent = 'Nginx installieren'; });
    }

    function nginxDeinstall() {
        if (!confirm('Nginx wirklich deinstallieren?\nAlle Konfigurationen werden gelöscht.')) return;
        showResult('Deinstalliere Nginx...', true);
        fetch('{{ route('server.nginx.deinstall', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json()).then(data => { showResult(data.message, data.success); if (data.success) setTimeout(refreshNginx, 2000); })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function installCertbot() {
        fetch('{{ route('server.nginx.ssl.install-certbot', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json()).then(data => showSslResult(data.message, data.success))
            .catch(err => showSslResult('Fehler: ' + err.message, false));
    }

    function obtainSsl() {
        fetch('{{ route('server.nginx.ssl.obtain', $server) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ domain: document.getElementById('ngx-ssl-domain').value, email: document.getElementById('ngx-ssl-email').value })
        }).then(r => r.json()).then(data => showSslResult(data.message, data.success))
          .catch(err => showSslResult('Fehler: ' + err.message, false));
    }

    function showSslResult(message, success) {
        const el = document.getElementById('ngx-ssl-result');
        el.className = 'mt-3 rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        el.innerHTML = '';
        el.appendChild(document.createTextNode(message));
        if (!success) {
            el.appendChild(window.reportError(message, 'nginx.ssl'));
        }
        el.classList.remove('hidden');
    }

    refreshNginx();
    </script>
    @endpush
</x-layouts.app>
