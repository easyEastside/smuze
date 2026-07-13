<x-layouts.app title="Dienste: {{ $server->name }}">
    <section class="w-full max-w-4xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Dienste</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Dienstverwaltung</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} — {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
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
    const SERVICES = [
        { key: 'php', label: 'PHP', versionField: 'php_version' },
        { key: 'apache', label: 'Apache', versionField: 'apache_version' },
        { key: 'nginx', label: 'Nginx', versionField: 'nginx_version' },
        { key: 'mysql', label: 'MySQL', versionField: 'mysql_version' },
        { key: 'node', label: 'Node.js', versionField: 'node_version' },
        { key: 'nvm', label: 'nvm', versionField: 'nvm_version' },
        { key: 'npm', label: 'npm', versionField: 'npm_version' },
        { key: 'composer', label: 'Composer', versionField: 'composer_version' },
    ];
    const PHP_VERSIONS = @json($phpVersions);
    function loadServices() {
        const loading = document.getElementById('services-loading');
        const content = document.getElementById('services-content');
        const result = document.getElementById('services-result');
        result.classList.add('hidden');

        loading.textContent = 'Lade Dienstinformationen...';
        loading.classList.remove('hidden');
        content.classList.add('hidden');

        fetch('{{ route('server.agent.metrics', $server) }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');
                renderServicesView(data);
            })
            .catch(err => {
                loading.classList.add('hidden');
                content.innerHTML = '';
                const errorDiv = document.createElement('div');
                errorDiv.className = 'rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                errorDiv.appendChild(document.createTextNode('Verbindungsfehler: ' + err.message));
                errorDiv.appendChild(window.reportError('Verbindungsfehler: ' + err.message, 'services'));
                content.appendChild(errorDiv);
                content.classList.remove('hidden');
            });
    }

    function renderServicesView(data) {
        const content = document.getElementById('services-content');
        const loading = document.getElementById('services-loading');
        loading.classList.add('hidden');

        if (data.error) {
            content.innerHTML = '';
            const errorDiv = document.createElement('div');
            errorDiv.className = 'rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            errorDiv.appendChild(document.createTextNode(data.error));
            errorDiv.appendChild(window.reportError(data.error, 'services'));
            content.appendChild(errorDiv);
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
                    <div class="flex items-center gap-2">
                        ${!installed && svc.key === 'php' ? phpVersionSelect() : ''}
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

    function phpVersionSelect() {
        const options = PHP_VERSIONS.map(version => `<option value="${version}">PHP ${version}</option>`).join('');

        return `<select data-php-version class="rounded-lg border border-[#19140035] bg-transparent px-2 py-1.5 text-xs dark:border-[#3E3E3A]">${options}</select>`;
    }

    function serviceAction(btn) {
        const key = btn.dataset.serviceKey;
        const action = btn.dataset.serviceAction;
        const originalButtonText = btn.textContent;
        const labels = { php: 'PHP', apache: 'Apache', nginx: 'Nginx', mysql: 'MySQL', node: 'Node.js', nvm: 'nvm', npm: 'npm', composer: 'Composer' };
        const label = labels[key] || key;
        const msg = action === 'install' ? `${label} installieren? Dies kann einige Minuten dauern.` : `${label} deinstallieren?`;
        if (!confirm(msg)) return;

        const result = document.getElementById('services-result');
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.classList.remove('hidden');
        result.innerHTML = `
            <div id="services-live-status" class="font-medium">Bereite Ausführung vor...</div>
            <pre id="services-live-log" class="mt-3 max-h-72 overflow-auto whitespace-pre-wrap rounded-lg bg-[#19140008] p-3 font-mono text-xs text-[#706f6c] dark:bg-black/20 dark:text-[#A1A09A]"></pre>
        `;

        const allBtns = document.querySelectorAll('#services-content button[data-service-key]');
        allBtns.forEach(b => { b.disabled = true; b.style.opacity = '0.5'; b.style.cursor = 'wait'; });

        btn.textContent = action === 'install' ? 'Installiert...' : 'Deinstalliert...';
        btn.style.opacity = '1';

        const installUrlTemplate = '{{ route('server.services.install.stream', ['server' => $server, 'service' => '__SERVICE__']) }}';
        const deinstallUrlTemplate = '{{ route('server.services.deinstall.stream', ['server' => $server, 'service' => '__SERVICE__']) }}';
        const finalUrl = (action === 'deinstall' ? deinstallUrlTemplate : installUrlTemplate).replace('__SERVICE__', key);

        readServiceActionStream(finalUrl, label, result, serviceActionPayload(btn))
            .then(data => {
                if (data.success) {
                    result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                    result.innerHTML = '';
                    result.appendChild(document.createTextNode(data.message));
                    result.appendChild(window.reportError(data.message, 'services'));
                }

                const status = document.getElementById('services-live-status');
                if (status) status.textContent = data.message;

        
            })
            .catch(err => {
                result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                result.innerHTML = '';
                result.appendChild(document.createTextNode('Fehler: ' + err.message));
                result.appendChild(window.reportError('Fehler: ' + err.message, 'services'));
                const status = document.getElementById('services-live-status');
                if (status) status.textContent = 'Fehler: ' + err.message;


            })
            .finally(() => {
                btn.textContent = originalButtonText;
                allBtns.forEach(b => { b.disabled = false; b.style.opacity = ''; b.style.cursor = ''; });
            });
    }

    function serviceActionPayload(btn) {
        if (btn.dataset.serviceKey !== 'php' || btn.dataset.serviceAction !== 'install') {
            return {};
        }

        const versionSelect = btn.closest('.flex').querySelector('[data-php-version]');

        return { version: versionSelect ? versionSelect.value : PHP_VERSIONS[0] };
    }

    async function readServiceActionStream(url, label, result, payload = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                Accept: 'application/x-ndjson',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            throw new Error(`Request fehlgeschlagen (${response.status}).`);
        }

        if (!response.body) {
            throw new Error('Live-Stream wird vom Browser nicht unterstützt.');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let finalData = null;

        while (true) {
            const { value, done } = await reader.read();
            buffer += decoder.decode(value || new Uint8Array(), { stream: !done });

            const lines = buffer.split('\n');
            buffer = lines.pop() || '';

            for (const line of lines) {
                if (line.trim() === '') continue;

                const event = JSON.parse(line);
                finalData = handleServiceStreamEvent(event, label, result) || finalData;
            }

            if (done) break;
        }

        if (buffer.trim() !== '') {
            const event = JSON.parse(buffer);
            finalData = handleServiceStreamEvent(event, label, result) || finalData;
        }

        return finalData || { success: false, message: `${label}: Kein Abschlussstatus empfangen.` };
    }

    function handleServiceStreamEvent(event, label, result) {
        const status = document.getElementById('services-live-status');
        const log = document.getElementById('services-live-log');

        if (event.type === 'status' && status) {
            status.textContent = `${label}: ${event.data}`;
            return null;
        }

        if ((event.type === 'stdout' || event.type === 'stderr') && log) {
            appendServiceLog(log, event.data);
            return null;
        }

        if (event.type === 'finished') {
            return event.data;
        }

        return null;
    }

    function appendServiceLog(log, output) {
        const text = String(output || '').replace(/\r/g, '');
        if (text === '') return;

        log.textContent += text;

        if (log.textContent.length > 20000) {
            log.textContent = log.textContent.slice(-20000);
        }

        log.scrollTop = log.scrollHeight;
    }

    loadServices();
    </script>
    @endpush
</x-layouts.app>
