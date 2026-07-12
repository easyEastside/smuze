<x-layouts.app title="Dienste: {{ $server->name }}">
    <div id="websocket-status-bar" class="fixed inset-x-0 top-0 z-50 h-1 bg-red-600 transition-colors duration-200" title="WebSocket getrennt"></div>
    <section class="w-full max-w-4xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Dienste</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Dienstverwaltung</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} — {{ $server->username }}@{{ $server->host }}:{{ $server->port }}
                    </p>
                </div>
                <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                    Zurück zum System
                </a>
            </div>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Installierte Dienste</p>
            <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Die aktuellen Versionen werden beim Öffnen geladen.</p>

            <div id="services-loading" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Lade Dienstinformationen...
            </div>
            <div id="services-content" class="mt-4 hidden"></div>
            <div id="services-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
        </div>
    </section>

    @push('scripts')
    <script>
    function wsInit() {
        SmuzeServerSocket.onStatus((status) => {
            const bar = document.getElementById('websocket-status-bar');
            if (!bar) return;
            const connected = status === 'connected';
            bar.classList.toggle('bg-green-500', connected);
            bar.classList.toggle('bg-red-600', !connected);
            bar.title = connected ? 'WebSocket verbunden' : 'WebSocket getrennt';
        });

        SmuzeServerSocket.connect({{ $server->id }}, '{{ route('server.socket.session', $server) }}', '{{ csrf_token() }}');
    }

    if (typeof SmuzeServerSocket !== 'undefined') {
        wsInit();
    } else {
        document.addEventListener('DOMContentLoaded', wsInit);
    }

    const SERVICES = [
        { key: 'php', label: 'PHP', versionField: 'php_version' },
        { key: 'apache', label: 'Apache', versionField: 'apache_version' },
        { key: 'mysql', label: 'MySQL', versionField: 'mysql_version' },
        { key: 'node', label: 'Node.js', versionField: 'node_version' },
        { key: 'nvm', label: 'nvm', versionField: 'nvm_version' },
        { key: 'npm', label: 'npm', versionField: 'node_version' },
        { key: 'composer', label: 'Composer', versionField: 'composer_version' },
    ];
    const systemCacheKey = 'smuze:server:{{ $server->id }}:system-info';

    function cachedSystemData() {
        try {
            const cached = JSON.parse(sessionStorage.getItem(systemCacheKey) || 'null');

            if (!cached || !cached.data || Date.now() - cached.cached_at > 60000) {
                return null;
            }

            return cached.data;
        } catch {
            return null;
        }
    }

    function cacheSystemData(data) {
        try {
            sessionStorage.setItem(systemCacheKey, JSON.stringify({ data, cached_at: Date.now() }));
        } catch {
            // Ignore unavailable storage.
        }
    }

    function loadServices() {
        const loading = document.getElementById('services-loading');
        const content = document.getElementById('services-content');
        const result = document.getElementById('services-result');
        result.classList.add('hidden');

        const cached = cachedSystemData();

        if (cached) {
            renderServicesView(cached);
            loading.textContent = 'Aktualisiere Dienstinformationen...';
        } else {
            loading.textContent = 'Lade Dienstinformationen...';
            loading.classList.remove('hidden');
            content.classList.add('hidden');
        }

        const doHttp = () => {
            fetch('{{ route('server.system.refresh', $server) }}')
                .then(r => r.json())
                .then(data => {
                    loading.classList.add('hidden');
                    if (data.error) {
                        content.innerHTML = `<div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">${data.error}</div>`;
                        content.classList.remove('hidden');
                        return;
                    }
                    cacheSystemData(data);
                    renderServicesView(data);
                })
                .catch(err => {
                    loading.classList.add('hidden');
                    content.innerHTML = `<div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">Verbindungsfehler: ${err.message}</div>`;
                    content.classList.remove('hidden');
                });
        };

        if (typeof SmuzeServerSocket !== 'undefined' && SmuzeServerSocket.isConnected) {
            SmuzeServerSocket.request('system', 'refresh')
                .then(p => p.data)
                .then(data => {
                    loading.classList.add('hidden');
                    if (data.error) {
                        content.innerHTML = `<div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">${data.error}</div>`;
                        content.classList.remove('hidden');
                        return;
                    }
                    cacheSystemData(data);
                    renderServicesView(data);
                })
                .catch(doHttp);
        } else {
            doHttp();
        }
    }

    function renderServicesView(data) {
        const content = document.getElementById('services-content');
        const loading = document.getElementById('services-loading');
        loading.classList.add('hidden');

        if (data.error) {
            content.innerHTML = `<div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">${data.error}</div>`;
            content.classList.remove('hidden');
            return;
        }

        let html = '<div class="space-y-2">';
        for (const svc of SERVICES) {
            const version = data[svc.versionField];
            const installed = !!version;
            html += `
                <div class="flex items-center justify-between rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                    <div class="flex items-center gap-3">
                        <span class="size-3 shrink-0 rounded-full ${installed ? 'bg-green-500' : 'bg-[#19140035] dark:bg-[#3E3E3A]'}"></span>
                        <div>
                            <p class="text-sm font-medium">${svc.label}</p>
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">${installed ? version : 'Nicht installiert'}</p>
                        </div>
                    </div>
                    <div>
                        ${installed
                            ? `<button data-service-key="${svc.key}" data-service-action="deinstall" onclick="serviceAction(this)" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Deinstallieren</button>`
                            : `<button data-service-key="${svc.key}" data-service-action="install" onclick="serviceAction(this)" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Installieren</button>`
                        }
                    </div>
                </div>
            `;
        }
        html += '</div>';
        content.innerHTML = html;
        content.classList.remove('hidden');
    }

    function serviceAction(btn) {
        const key = btn.dataset.serviceKey;
        const action = btn.dataset.serviceAction;
        const labels = { php: 'PHP', apache: 'Apache', mysql: 'MySQL', node: 'Node.js', nvm: 'nvm', npm: 'npm', composer: 'Composer' };
        const label = labels[key] || key;
        const msg = action === 'install' ? `${label} installieren? Dies kann einige Minuten dauern.` : `${label} deinstallieren?`;
        if (!confirm(msg)) return;

        const result = document.getElementById('services-result');
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.classList.remove('hidden');

        const allBtns = document.querySelectorAll('#services-content button[data-service-key]');
        allBtns.forEach(b => { b.disabled = true; b.style.opacity = '0.5'; b.style.cursor = 'wait'; });

        btn.textContent = 'Warte...';
        btn.style.opacity = '1';
        result.textContent = `${label}: Befehl wird ausgeführt. Bitte warten...`;

        const urlTemplate = '{{ route('server.services.install', ['server' => $server, 'service' => '__SERVICE__']) }}';
        const url = urlTemplate.replace('__SERVICE__', key);
        const finalUrl = action === 'deinstall' ? url.replace('install', 'deinstall') : url;

        fetch(finalUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                allBtns.forEach(b => { b.disabled = false; b.style.opacity = ''; b.style.cursor = ''; });
                if (data.success) {
                    result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                    setTimeout(() => loadServices(), 2000);
                } else {
                    result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                }
                result.textContent = data.message;
            })
            .catch(err => {
                allBtns.forEach(b => { b.disabled = false; b.style.opacity = ''; b.style.cursor = ''; });
                result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
            });
    }

    loadServices();
    </script>
    @endpush
</x-layouts.app>
