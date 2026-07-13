<x-layouts.app title="GitHub: {{ $server->name }}">
    <section class="w-full max-w-4xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">GitHub</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">GitHub-Deployment</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} — {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                    Zurück zum System
                </a>
            </div>
        </div>

        <div id="gh-content" class="mt-6">
            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">GitHub-Projekt bereitstellen</p>
                <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                    Klone ein öffentliches GitHub-Repository nach /var/www. Die Webserver-Konfiguration erfolgt anschließend separat über Apache oder Nginx.
                </p>

                <div class="mt-6 space-y-4">
                    <div>
                        <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Git-URL</label>
                        <input type="url" id="gh-url" onkeyup="syncTargetName()" placeholder="https://github.com/owner/projekt.git" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                        <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Hier die öffentliche GitHub-URL eintragen.</p>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Zielordner</label>
                        <div class="mt-1 flex items-center">
                            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-[#19140035] bg-[#f5f5f4] px-3 py-2 text-sm text-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#1b1b18] dark:text-[#A1A09A]">/var/www/</span>
                            <input type="text" id="gh-target" onkeyup="updatePreview()" placeholder="projektname" class="block w-full rounded-r-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                        </div>
                        <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Wird automatisch aus der URL vorgeschlagen. Bestehende Ordner werden nicht überschrieben.</p>
                    </div>

                    <div id="gh-preview" class="rounded-xl border border-[#19140020] bg-[#f5f5f4] p-4 text-xs leading-5 text-[#706f6c] dark:border-[#3E3E3A] dark:bg-[#1b1b18] dark:text-[#A1A09A]">
                        Vorschau wird automatisch generiert...
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="deployProject()" id="gh-deploy-btn" class="rounded-lg bg-[#1b1b18] px-6 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            Projekt klonen
                        </button>
                    </div>

                    <div id="gh-deploy-result" class="hidden rounded-xl p-3 text-sm"></div>

                    <div id="gh-output" class="hidden">
                        <p class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Ausgabe</p>
                        <pre id="gh-output-text" class="mt-2 max-h-64 overflow-y-auto rounded-xl border border-[#19140020] bg-[#f5f5f4] p-4 text-xs leading-5 dark:border-[#3E3E3A] dark:bg-[#1b1b18]"></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    let lastAutoTarget = '';

    function syncTargetName() {
        const url = document.getElementById('gh-url').value.trim();
        const target = document.getElementById('gh-target');
        const current = target.value.trim();
        if (current && current !== lastAutoTarget) return;
        const match = url.match(/\/\/github\.com\/[^/]+\/([^/]+?)(?:\.git)?(?:\/)?$/);
        if (match) {
            lastAutoTarget = match[1];
            target.value = lastAutoTarget;
        }
        updatePreview();
    }

    function updatePreview() {
        const el = document.getElementById('gh-preview');
        const target = document.getElementById('gh-target').value.trim() || '&lt;zielordner&gt;';
        let html = '<strong>Vor dem Start wird eingerichtet:</strong><br>';
        html += 'Projekt wird geklont nach: <code>/var/www/' + target + '</code>.';
        el.innerHTML = html;
    }

    function showResult(msg, success) {
        const el = document.getElementById('gh-deploy-result');
        el.className = 'rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function setOutput(text) {
        const el = document.getElementById('gh-output');
        const textEl = document.getElementById('gh-output-text');
        el.classList.remove('hidden');
        textEl.textContent = text;
    }

    function deployProject() {
        const url = document.getElementById('gh-url').value.trim();
        const target = document.getElementById('gh-target').value.trim();

        if (!url) { showResult('Bitte eine Git-URL eingeben.', false); return; }
        if (!target) { showResult('Bitte einen Zielordner eingeben.', false); return; }

        const btn = document.getElementById('gh-deploy-btn');
        btn.disabled = true;
        btn.textContent = 'Klonen läuft...';
        showResult('Starte Deployment...', true);
        setOutput('GitHub URL: ' + url + '\nZiel: /var/www/' + target + '\n\nBitte warten...');

        fetch('{{ route('server.github.deploy', $server) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ repo_url: url, target_name: target }),
        })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = 'Projekt klonen';
                setOutput(data.message || '');
                showResult(data.message || 'Deployment abgeschlossen.', data.success);
            })
            .catch(err => {
                btn.disabled = false;
                btn.textContent = 'Projekt klonen';
                showResult('Fehler: ' + err.message, false);
            });
    }

    updatePreview();
    </script>
    @endpush
</x-layouts.app>
