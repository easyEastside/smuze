<x-layouts.app title="MySQL: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">MySQL</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">MySQL-Datenbank-Verwaltung</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} — {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="refreshMysql()" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Aktualisieren
                    </button>
                    <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Zurück zum System
                    </a>
                </div>
            </div>
        </div>

        <div id="my-loading" class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Verbinde zum Server...</p>
        </div>

        <div id="my-install-overlay" class="mt-6 hidden">
            <div class="rounded-2xl bg-white p-12 text-center shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                <p class="text-lg font-semibold">MySQL ist nicht installiert</p>
                <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Installiere MySQL um die Datenbank zu verwalten.</p>
                <button type="button" id="btn-install-mysql" onclick="installMysql()" class="mt-6 rounded-lg bg-[#1b1b18] px-6 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                    MySQL installieren
                </button>
                <div id="my-install-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
            </div>
        </div>

        <div id="my-content" class="mt-6 hidden">
            <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
                <div class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Status</p>
                                <span id="my-status-badge" class="rounded-full px-2.5 py-0.5 text-xs font-medium"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="mysqlServiceAction('start')" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Start</button>
                                <button type="button" onclick="mysqlServiceAction('stop')" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Stop</button>
                                <button type="button" onclick="mysqlServiceAction('restart')" class="rounded-lg bg-yellow-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-yellow-700">Restart</button>
                            </div>
                        </div>
                        <div id="my-version" class="mt-2 text-xs text-[#706f6c] dark:text-[#A1A09A]"></div>
                        <div class="mt-3 flex gap-2">
                            <button type="button" onclick="mysqlDeinstall()" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                MySQL deinstallieren
                            </button>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Datenbanken</p>
                            <button type="button" onclick="showCreateDbForm()" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                                + Datenbank
                            </button>
                        </div>

                        <div id="my-dbs-empty" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Keine Datenbanken.</div>
                        <div id="my-dbs-table" class="mt-4 hidden space-y-2"></div>

                        <div id="my-create-db-form" class="mt-4 hidden border-t border-[#19140020] pt-4 dark:border-[#3E3E3A]">
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Datenbankname</label>
                                    <input type="text" id="my-db-name" placeholder="my_database" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                </div>
                                <button type="button" onclick="createDatabase()" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Erstellen</button>
                                <button type="button" onclick="hideCreateDbForm()" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Abbrechen</button>
                            </div>
                            <div id="my-db-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Benutzer</p>
                            <button type="button" onclick="showCreateUserForm()" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                                + Benutzer
                            </button>
                        </div>

                        <div id="my-users-empty" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Keine Benutzer.</div>
                        <div id="my-users-table" class="mt-4 hidden overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-[#19140020] text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                        <th class="px-3 py-2 font-medium">Benutzer</th>
                                        <th class="px-3 py-2 font-medium">Host</th>
                                        <th class="px-3 py-2 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody id="my-users-tbody"></tbody>
                            </table>
                        </div>

                        <div id="my-create-user-form" class="mt-4 hidden border-t border-[#19140020] pt-4 dark:border-[#3E3E3A]">
                            <p class="text-sm font-medium mb-3">Benutzer erstellen</p>
                            <div class="grid grid-cols-3 gap-2">
                                <input type="text" id="my-username" placeholder="Benutzername" class="rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <input type="text" id="my-host" placeholder="Host (z.B. localhost)" value="localhost" class="rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                <input type="password" id="my-password" placeholder="Passwort" class="rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                            </div>
                            <div class="mt-3 flex gap-2">
                                <button type="button" onclick="createUser()" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Erstellen</button>
                                <button type="button" onclick="hideCreateUserForm()" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Abbrechen</button>
                            </div>
                            <div id="my-user-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                        </div>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Schnellzugriff</p>
                        <div class="mt-4 flex flex-col gap-2">
                            <button type="button" onclick="quickCreateDb('app')" class="rounded-lg border border-[#19140035] px-3 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Datenbank "app" anlegen</button>
                            <button type="button" onclick="quickCreateDb('test')" class="rounded-lg border border-[#19140035] px-3 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Datenbank "test" anlegen</button>
                        </div>
                        <div id="my-quick-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                    </div>

                    <div id="my-result" class="hidden rounded-xl p-3 text-sm"></div>
                </aside>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    function showResult(msg, success) {
        const el = document.getElementById('my-result');
        el.className = 'rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        el.innerHTML = '';
        el.appendChild(document.createTextNode(msg));
        if (!success) {
            el.appendChild(window.reportError(msg, 'mysql'));
        }
        el.classList.remove('hidden');
    }

    function routeSegment(value) {
        return encodeURIComponent(value);
    }

    function refreshMysql() {
        const loading = document.getElementById('my-loading');
        const content = document.getElementById('my-content');
        const installOverlay = document.getElementById('my-install-overlay');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        installOverlay.classList.add('hidden');

        fetch('{{ route('server.mysql.status', $server) }}')
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
                renderMysqlStatus(data);
                loadDatabases();
                loadUsers();
            })
            .catch(err => {
                loading.textContent = 'Verbindungsfehler: ' + err.message;
                loading.classList.remove('hidden');
            });
    }

    function renderMysqlStatus(data) {
        const badge = document.getElementById('my-status-badge');
        const version = document.getElementById('my-version');
        badge.textContent = data.active ? 'Aktiv' : 'Inaktiv';
        badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium ' + (data.active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200');
        version.textContent = data.version || '';
    }

    function mysqlServiceAction(action) {
        const labels = { start: 'starten', stop: 'stoppen', restart: 'neu starten' };
        if (!confirm('MySQL ' + labels[action] + '?')) return;
        showResult('MySQL wird ' + labels[action] + '...', true);
        fetch('{{ route('server.mysql.service', ['server' => $server, 'action' => '__ACTION__']) }}'.replace('__ACTION__', action), { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(refreshMysql, 2000);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function mysqlDeinstall() {
        if (!confirm('MySQL wirklich deinstallieren?\nAlle Datenbanken werden gelöscht.')) return;
        showResult('Deinstalliere MySQL...', true);
        fetch('{{ route('server.mysql.deinstall', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) {
                    window.showToast(data.message, 'success');
                    setTimeout(refreshMysql, 2000);
                }
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function loadDatabases() {
        fetch('{{ route('server.mysql.databases', $server) }}').then(r => r.json())
            .then(data => {
                const empty = document.getElementById('my-dbs-empty');
                const table = document.getElementById('my-dbs-table');
                if (!data.success || !data.databases || data.databases.length === 0) {
                    empty.classList.remove('hidden');
                    table.classList.add('hidden');
                    table.innerHTML = '';
                    return;
                }
                empty.classList.add('hidden');
                table.innerHTML = '';
                for (const db of data.databases) {
                    table.appendChild(databaseRow(db));
                }
                table.classList.remove('hidden');
            });
    }

    function databaseRow(db) {
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]';

        const label = document.createElement('div');
        label.className = 'flex items-center gap-2';
        const dot = document.createElement('span');
        dot.className = 'size-2 rounded-full bg-blue-500';
        const name = document.createElement('span');
        name.className = 'text-sm font-medium';
        name.textContent = db;
        label.appendChild(dot);
        label.appendChild(name);
        row.appendChild(label);

        const actions = document.createElement('div');
        actions.className = 'flex items-center gap-1';

        const tables = document.createElement('button');
        tables.type = 'button';
        tables.textContent = 'Tabellen';
        tables.className = 'text-xs text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-white';
        tables.addEventListener('click', () => showTables(db));
        actions.appendChild(tables);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.textContent = 'Löschen';
        remove.className = 'text-xs text-red-600 hover:text-red-800 dark:text-red-400';
        remove.addEventListener('click', () => dropDatabase(db));
        actions.appendChild(remove);

        row.appendChild(actions);

        return row;
    }

    function showCreateDbForm() {
        document.getElementById('my-create-db-form').classList.remove('hidden');
    }

    function hideCreateDbForm() {
        document.getElementById('my-create-db-form').classList.add('hidden');
        document.getElementById('my-db-result').classList.add('hidden');
    }

    function createDatabase() {
        const name = document.getElementById('my-db-name').value.trim();
        if (!name) { showResult('Bitte einen Datenbanknamen eingeben.', false); return; }
        const result = document.getElementById('my-db-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        result.textContent = 'Erstelle Datenbank...';
        result.classList.remove('hidden');

        fetch('{{ route('server.mysql.databases.create', $server) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ db_name: name }),
        })
            .then(r => r.json())
            .then(data => {
                result.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                result.textContent = data.message;
                if (data.success) {
                    document.getElementById('my-db-name').value = '';
                    setTimeout(() => { hideCreateDbForm(); loadDatabases(); }, 1500);
                }
            })
            .catch(err => {
                result.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
            });
    }

    function dropDatabase(db) {
        if (!confirm('Datenbank "' + db + '" wirklich löschen? Dies kann nicht rückgängig gemacht werden.')) return;
        showResult('Lösche Datenbank...', true);
        fetch('{{ route('server.mysql.databases.destroy', ['server' => $server, 'database' => '__DB__']) }}'.replace('__DB__', routeSegment(db)), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(loadDatabases, 1500);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function showTables(db) {
        showResult('Lade Tabellen für ' + db + '...', true);
        fetch('{{ route('server.mysql.databases.tables', ['server' => $server, 'database' => '__DB__']) }}'.replace('__DB__', routeSegment(db)))
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.tables || data.tables.length === 0) {
                    showResult('Keine Tabellen in ' + db, true);
                    return;
                }
                showResult('Tabellen in ' + db + ': ' + data.tables.join(', '), true);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function loadUsers() {
        fetch('{{ route('server.mysql.users', $server) }}').then(r => r.json())
            .then(data => {
                const empty = document.getElementById('my-users-empty');
                const table = document.getElementById('my-users-table');
                const tbody = document.getElementById('my-users-tbody');
                if (!data.success || !data.users || data.users.length === 0) {
                    empty.classList.remove('hidden');
                    table.classList.add('hidden');
                    tbody.innerHTML = '';
                    return;
                }
                empty.classList.add('hidden');
                tbody.innerHTML = '';
                for (const user of data.users) {
                    tbody.appendChild(userRow(user));
                }
                table.classList.remove('hidden');
            });
    }

    function userRow(user) {
        const row = document.createElement('tr');
        row.className = 'border-b border-[#19140020] dark:border-[#3E3E3A]';

        row.appendChild(tableCell(user.username, 'px-3 py-2 font-medium'));
        row.appendChild(tableCell(user.host, 'px-3 py-2 text-xs text-[#706f6c] dark:text-[#A1A09A]'));

        const actions = document.createElement('td');
        actions.className = 'px-3 py-2';
        const actionGroup = document.createElement('div');
        actionGroup.className = 'flex gap-1';

        actionGroup.appendChild(userActionButton('Passwort', 'text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400', () => userAction('password', user.username, user.host)));
        actionGroup.appendChild(userActionButton('Grant All', 'text-xs text-green-600 hover:text-green-800 dark:text-green-400', () => userAction('grant', user.username, user.host)));
        actionGroup.appendChild(userActionButton('Löschen', 'text-xs text-red-600 hover:text-red-800 dark:text-red-400', () => userAction('drop', user.username, user.host)));

        actions.appendChild(actionGroup);
        row.appendChild(actions);

        return row;
    }

    function tableCell(value, className) {
        const cell = document.createElement('td');
        cell.className = className;
        cell.textContent = value || '-';

        return cell;
    }

    function userActionButton(label, className, callback) {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.className = className;
        button.addEventListener('click', callback);

        return button;
    }

    function showCreateUserForm() {
        document.getElementById('my-create-user-form').classList.remove('hidden');
    }

    function hideCreateUserForm() {
        document.getElementById('my-create-user-form').classList.add('hidden');
        document.getElementById('my-user-result').classList.add('hidden');
    }

    function createUser() {
        const username = document.getElementById('my-username').value.trim();
        const host = document.getElementById('my-host').value.trim();
        const password = document.getElementById('my-password').value.trim();
        if (!username || !host || !password) { showResult('Bitte alle Felder ausfüllen.', false); return; }
        const result = document.getElementById('my-user-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        result.textContent = 'Erstelle Benutzer...';
        result.classList.remove('hidden');

        fetch('{{ route('server.mysql.users.create', $server) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ username, host, password }),
        })
            .then(r => r.json())
            .then(data => {
                result.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                result.textContent = data.message;
                if (data.success) {
                    document.getElementById('my-username').value = '';
                    document.getElementById('my-password').value = '';
                    setTimeout(() => { hideCreateUserForm(); loadUsers(); }, 1500);
                }
            })
            .catch(err => {
                result.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
            });
    }

    function userAction(action, username, host) {
        if (action === 'drop' && !confirm('Benutzer ' + username + '@' + host + ' wirklich löschen?')) return;
        if (action === 'grant' && !confirm('Alle Rechte an ' + username + '@' + host + ' erteilen?')) return;
        if (action === 'password') {
            const pw = prompt('Neues Passwort für ' + username + '@' + host + ':');
            if (!pw) return;
            showResult('Setze Passwort...', true);
            fetch('{{ route('server.mysql.users.password', ['server' => $server, 'username' => '__USER__', 'host' => '__HOST__']) }}'.replace('__USER__', routeSegment(username)).replace('__HOST__', routeSegment(host)), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ password: pw }),
            })
                .then(r => r.json())
                .then(data => {
                    showResult(data.message, data.success);
                    if (data.success) setTimeout(loadUsers, 1500);
                })
                .catch(err => showResult('Fehler: ' + err.message, false));
            return;
        }
        const url = action === 'grant'
            ? '{{ route('server.mysql.users.grant', ['server' => $server, 'username' => '__USER__', 'host' => '__HOST__']) }}'.replace('__USER__', routeSegment(username)).replace('__HOST__', routeSegment(host))
            : '{{ route('server.mysql.users.destroy', ['server' => $server, 'username' => '__USER__', 'host' => '__HOST__']) }}'.replace('__USER__', routeSegment(username)).replace('__HOST__', routeSegment(host));
        const method = action === 'drop' ? 'DELETE' : 'POST';

        showResult('Führe Befehl aus...', true);
        fetch(url, { method, headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(loadUsers, 1500);
            })
            .catch(err => showResult('Fehler: ' + err.message, false));
    }

    function quickCreateDb(name) {
        const el = document.getElementById('my-quick-result');
        el.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        el.textContent = 'Erstelle Datenbank "' + name + '"...';
        el.classList.remove('hidden');
        fetch('{{ route('server.mysql.databases.create', $server) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ db_name: name }),
        })
            .then(r => r.json())
            .then(data => {
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.message;
                if (data.success) setTimeout(loadDatabases, 1500);
            })
            .catch(err => {
                el.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                el.textContent = 'Fehler: ' + err.message;
            });
    }

    function installMysql() {
        const btn = document.getElementById('btn-install-mysql');
        const result = document.getElementById('my-install-result');
        btn.disabled = true;
        btn.textContent = 'Installiere...';
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.textContent = 'MySQL wird installiert. Bitte warten...';
        result.classList.remove('hidden');
        fetch('{{ route('server.mysql.install', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                    result.textContent = 'MySQL wurde installiert.';
                    window.showToast('MySQL wurde installiert.', 'success');
                    setTimeout(refreshMysql, 2000);
                } else {
                    result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                    result.textContent = data.message;
                    btn.disabled = false;
                    btn.textContent = 'MySQL installieren';
                }
            })
            .catch(err => {
                result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
                btn.disabled = false;
                btn.textContent = 'MySQL installieren';
            });
    }

    refreshMysql();
    </script>
    @endpush
</x-layouts.app>
