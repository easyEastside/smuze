<x-layouts.app title="Dateien: {{ $server->name }}">
    <section class="w-full max-w-7xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Datei-Manager</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Server-Dateien</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} - {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="loadDirectory(currentPath)" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Aktualisieren</button>
                    <button type="button" onclick="promptCreateDirectory()" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Ordner</button>
                    <button type="button" onclick="promptCreateFile()" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Datei</button>
                </div>
            </div>
        </div>

        <div id="files-result" class="mt-6 hidden rounded-xl p-3 text-sm"></div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(420px,0.75fr)]">
            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Verzeichnis</p>
                        <div id="breadcrumb" class="mt-1 flex flex-wrap gap-1 text-xs text-[#706f6c] dark:text-[#A1A09A]"></div>
                    </div>
                    <span id="files-status" class="text-xs text-[#706f6c] dark:text-[#A1A09A]">-</span>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <form id="path-form" class="flex min-w-full flex-wrap items-center gap-2">
                        <input id="path-input" type="text" value="/var/www" class="min-w-0 flex-1 rounded-lg border border-[#19140035] bg-transparent px-3 py-1.5 font-mono text-sm dark:border-[#3E3E3A]" placeholder="/pfad/zum/ordner">
                        <button type="submit" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Öffnen</button>
                    </form>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" onclick="loadDirectory('/')" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs dark:border-[#3E3E3A]">/</button>
                    <button type="button" onclick="loadDirectory('/var/www')" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs dark:border-[#3E3E3A]">/var/www</button>
                    <button type="button" onclick="loadDirectory('/home')" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs dark:border-[#3E3E3A]">/home</button>
                    <button type="button" onclick="loadDirectory('/etc')" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs dark:border-[#3E3E3A]">/etc</button>
                    <button type="button" onclick="loadDirectory('/tmp')" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs dark:border-[#3E3E3A]">/tmp</button>
                    <button type="button" onclick="loadDirectory('/root')" class="rounded-lg border border-[#19140035] px-2 py-1 text-xs dark:border-[#3E3E3A]">/root</button>
                </div>

                <form id="upload-form" class="mt-4 flex flex-wrap items-center gap-2">
                    <input type="file" name="file" class="max-w-full rounded-lg border border-[#19140035] px-3 py-1.5 text-sm dark:border-[#3E3E3A]">
                    <button type="submit" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Upload</button>
                    <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Max. 25 MB</span>
                </form>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-[#19140020] text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                            <tr>
                                <th class="py-2 pr-3 font-medium">Name</th>
                                <th class="py-2 pr-3 text-right font-medium">Größe</th>
                                <th class="py-2 pr-3 font-medium">Rechte</th>
                                <th class="py-2 pr-3 font-medium">Geändert</th>
                                <th class="py-2 text-right font-medium">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody id="files-body" class="divide-y divide-[#19140020] dark:divide-[#3E3E3A]">
                            <tr><td colspan="5" class="py-4 text-[#706f6c] dark:text-[#A1A09A]">Lade Dateien...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Editor</p>
                        <p id="editor-path" class="mt-1 break-all font-mono text-xs text-[#706f6c] dark:text-[#A1A09A]">Keine Datei ausgewählt.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="download-button" onclick="downloadSelectedFile()" class="hidden rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Download</button>
                        <button type="button" id="save-button" onclick="saveSelectedFile()" class="hidden rounded-lg bg-[#1b1b18] px-2 py-1 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Speichern</button>
                    </div>
                </div>
                <textarea id="editor" class="mt-4 hidden h-[560px] w-full resize-y rounded-xl border border-[#19140020] bg-[#19140008] p-3 font-mono text-xs outline-none dark:border-[#3E3E3A] dark:bg-black/20" spellcheck="false"></textarea>
                <div id="editor-empty" class="mt-4 rounded-xl bg-[#19140008] p-4 text-sm text-[#706f6c] dark:bg-[#fffaed08] dark:text-[#A1A09A]">
                    Wähle eine Textdatei aus der Liste aus. Große oder binäre Dateien bitte herunterladen.
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    const csrfToken = '{{ csrf_token() }}';
    let currentPath = '/var/www';
    let selectedFile = null;

    document.getElementById('path-form').addEventListener('submit', event => {
        event.preventDefault();
        const path = document.getElementById('path-input').value.trim();
        if (path) loadDirectory(path);
    });

    document.getElementById('upload-form').addEventListener('submit', event => {
        event.preventDefault();
        uploadFile(event.target);
    });

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;',
        }[char]));
    }

    function showResult(success, message) {
        const result = document.getElementById('files-result');
        result.className = 'mt-6 rounded-xl p-3 text-sm ' + (success
            ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200'
            : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        result.innerHTML = '';
        result.appendChild(document.createTextNode(message));
        if (!success) {
            result.appendChild(window.reportError(message, 'files'));
        }
        result.classList.remove('hidden');
    }

    function formatBytes(bytes) {
        bytes = Number(bytes || 0);
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1024 / 1024).toFixed(1) + ' MB';
    }

    function joinPath(directory, name) {
        return directory.replace(/\/$/, '') + '/' + name.replace(/^\/+/, '');
    }

    function parentPath(path) {
        const normalized = ('/' + String(path || '/').split('/').filter(Boolean).join('/')).replace(/\/+/g, '/');
        const parts = normalized.split('/').filter(Boolean);
        parts.pop();

        return '/' + parts.join('/');
    }

    function renderBreadcrumb(path) {
        const breadcrumb = document.getElementById('breadcrumb');
        const parts = path.split('/').filter(Boolean);
        let built = '';
        breadcrumb.innerHTML = `<button type="button" onclick="loadDirectory('/')" class="hover:text-[#f53003]">/</button>` + parts.map(part => {
            built += '/' + part;
            return `<span>/</span><button type="button" onclick="loadDirectory('${escapeHtml(built)}')" class="font-mono hover:text-[#f53003]">${escapeHtml(part)}</button>`;
        }).join('');
    }

    async function requestJson(url, options = {}) {
        const response = await fetch(url, {
            headers: { Accept: 'application/json', ...(options.headers || {}) },
            ...options,
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.error || data.stderr || 'Datei-Aktion fehlgeschlagen.');
        }

        return data.data;
    }

    async function loadDirectory(path) {
        const status = document.getElementById('files-status');
        status.textContent = 'lädt...';

        try {
            const data = await requestJson(`{{ route('server.files.list', $server) }}?path=${encodeURIComponent(path)}`);
            currentPath = data.path;
            document.getElementById('path-input').value = currentPath;
            renderBreadcrumb(currentPath);
            renderFiles(data.entries || []);
            status.textContent = new Date().toLocaleTimeString('de-DE');
        } catch (error) {
            status.textContent = 'Fehler';
            showResult(false, error.message);
        }
    }

    function renderFiles(entries) {
        const body = document.getElementById('files-body');
        if (currentPath !== '/') {
            entries = [{ name: '..', path: parentPath(currentPath), type: 'parent', size: 0, mode: '', modified: '' }, ...entries];
        }

        if (entries.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="py-4 text-[#706f6c] dark:text-[#A1A09A]">Verzeichnis ist leer.</td></tr>';
            return;
        }

        body.innerHTML = entries.map(entry => {
            const isDirectory = entry.type === 'directory' || entry.type === 'parent';
            const typeLabel = entry.type === 'parent' ? 'UP' : (isDirectory ? 'ORDNER' : (entry.type === 'symlink' ? 'LINK' : 'DATEI'));
            const typeClass = entry.type === 'parent'
                ? 'border-[#19140035] bg-[#19140008] text-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#fffaed08] dark:text-[#A1A09A]'
                : (isDirectory
                    ? 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200'
                    : (entry.type === 'symlink'
                        ? 'border-sky-300 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200'
                        : 'border-[#19140020] bg-white text-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#A1A09A]'));
            const rowClass = isDirectory ? 'bg-amber-50/35 hover:bg-amber-50 dark:bg-amber-950/10 dark:hover:bg-amber-950/25' : 'hover:bg-[#19140005] dark:hover:bg-[#fffaed08]';
            const nameClass = isDirectory ? 'font-semibold text-[#1b1b18] dark:text-[#EDEDEC]' : 'text-[#1b1b18] dark:text-[#EDEDEC]';
            const primaryAction = isDirectory
                ? `loadDirectory('${escapeHtml(entry.path)}')`
                : `readFile('${escapeHtml(entry.path)}')`;
            const actions = entry.type === 'parent' ? '' : `
                <button type="button" onclick="promptRename('${escapeHtml(entry.path)}')" class="rounded border border-[#19140035] px-2 py-1 text-xs dark:border-[#3E3E3A]">Rename</button>
                <button type="button" onclick="promptChmod('${escapeHtml(entry.path)}', '${escapeHtml(entry.mode || '')}')" class="rounded border border-[#19140035] px-2 py-1 text-xs dark:border-[#3E3E3A]">Chmod</button>
                <button type="button" onclick="deletePath('${escapeHtml(entry.path)}', ${isDirectory ? 'true' : 'false'})" class="rounded border border-red-300 px-2 py-1 text-xs text-red-700 dark:border-red-900 dark:text-red-300">Delete</button>
            `;

            return `
                <tr class="${rowClass}">
                    <td class="max-w-[360px] py-2 pr-3">
                        <button type="button" onclick="${primaryAction}" class="flex max-w-full items-center gap-3 text-left hover:text-[#f53003]">
                            <span class="inline-flex min-w-16 justify-center rounded-md border px-2 py-0.5 text-[10px] font-semibold tracking-wide ${typeClass}">${typeLabel}</span>
                            <span class="truncate font-mono text-xs ${nameClass}" title="${escapeHtml(entry.path)}">${escapeHtml(entry.name)}</span>
                        </button>
                    </td>
                    <td class="py-2 pr-3 text-right text-xs text-[#706f6c] dark:text-[#A1A09A]">${isDirectory ? '-' : formatBytes(entry.size)}</td>
                    <td class="py-2 pr-3 font-mono text-xs text-[#706f6c] dark:text-[#A1A09A]">${escapeHtml(entry.mode || '')}</td>
                    <td class="py-2 pr-3 text-xs text-[#706f6c] dark:text-[#A1A09A]">${escapeHtml(entry.modified || '')}</td>
                    <td class="py-2 text-right"><div class="flex flex-wrap justify-end gap-1">${actions}</div></td>
                </tr>
            `;
        }).join('');
    }

    async function readFile(path) {
        try {
            const data = await requestJson(`{{ route('server.files.read', $server) }}?path=${encodeURIComponent(path)}`);
            selectedFile = data.path;
            document.getElementById('editor-path').textContent = data.path + ' · ' + formatBytes(data.size);
            document.getElementById('editor').value = data.content || '';
            document.getElementById('editor').classList.remove('hidden');
            document.getElementById('editor-empty').classList.add('hidden');
            document.getElementById('save-button').classList.remove('hidden');
            document.getElementById('download-button').classList.remove('hidden');
        } catch (error) {
            selectedFile = path;
            document.getElementById('editor-path').textContent = path;
            document.getElementById('editor').classList.add('hidden');
            document.getElementById('editor-empty').classList.remove('hidden');
            document.getElementById('save-button').classList.add('hidden');
            document.getElementById('download-button').classList.remove('hidden');
            showResult(false, error.message);
        }
    }

    async function saveSelectedFile() {
        if (!selectedFile) return;
        try {
            await requestJson('{{ route('server.files.write', $server) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
                body: JSON.stringify({ path: selectedFile, content: document.getElementById('editor').value }),
            });
            showResult(true, 'Datei gespeichert.');
            loadDirectory(currentPath);
        } catch (error) {
            showResult(false, error.message);
        }
    }

    function downloadSelectedFile() {
        if (!selectedFile) return;
        window.location.href = `{{ route('server.files.download', $server) }}?path=${encodeURIComponent(selectedFile)}`;
    }

    async function promptCreateDirectory() {
        const name = prompt('Ordnername');
        if (!name) return;
        await mutatePath('{{ route('server.files.directories.store', $server) }}', { path: joinPath(currentPath, name) }, 'Ordner erstellt.');
    }

    async function promptCreateFile() {
        const name = prompt('Dateiname');
        if (!name) return;
        await mutatePath('{{ route('server.files.files.store', $server) }}', { path: joinPath(currentPath, name) }, 'Datei erstellt.');
    }

    async function promptRename(path) {
        const name = prompt('Neuer Name', path.split('/').pop());
        if (!name) return;
        const directory = path.split('/').slice(0, -1).join('/') || '/';
        await mutatePath('{{ route('server.files.rename', $server) }}', { path, new_path: joinPath(directory, name) }, 'Umbenannt.');
    }

    async function promptChmod(path, currentMode) {
        const suggested = modeToOctal(currentMode) || '0644';
        const mode = prompt('Neuer chmod-Modus, z. B. 0644 oder 0755', suggested);
        if (!mode) return;
        await mutatePath('{{ route('server.files.chmod', $server) }}', { path, mode }, 'Rechte geändert.');
    }

    function modeToOctal(mode) {
        if (!mode || mode.length < 10) return '';
        const permissions = mode.slice(1, 10);
        let result = '';
        for (let index = 0; index < permissions.length; index += 3) {
            const chunk = permissions.slice(index, index + 3);
            let value = 0;
            if (chunk[0] === 'r') value += 4;
            if (chunk[1] === 'w') value += 2;
            if (chunk[2] === 'x' || chunk[2] === 's' || chunk[2] === 't') value += 1;
            result += value;
        }

        return '0' + result;
    }

    async function deletePath(path, isDirectory) {
        if (!confirm(`${path} wirklich löschen?`)) return;
        await mutatePath('{{ route('server.files.delete', $server) }}', { path, recursive: isDirectory }, 'Gelöscht.', 'DELETE');
    }

    async function mutatePath(url, payload, message, method = 'POST') {
        try {
            await requestJson(url, {
                method,
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            showResult(true, message);
            loadDirectory(currentPath);
        } catch (error) {
            showResult(false, error.message);
        }
    }

    async function uploadFile(form) {
        const formData = new FormData(form);
        formData.append('directory', currentPath);

        try {
            await requestJson('{{ route('server.files.upload', $server) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData,
            });
            form.reset();
            showResult(true, 'Upload abgeschlossen.');
            loadDirectory(currentPath);
        } catch (error) {
            showResult(false, error.message);
        }
    }

    loadDirectory(currentPath);
    </script>
    @endpush
</x-layouts.app>
