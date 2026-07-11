<x-layouts.app title="Dashboard: {{ $server->name }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.min.css" />

    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server Dashboard</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $server->name }}</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->username }}@{{ $server->host }}:{{ $server->port }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-test-connection" onclick="testConnection()" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Verbindung testen
                    </button>
                    <a href="{{ route('server.edit', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Bearbeiten
                    </a>
                    <a href="{{ route('server.index') }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Zurück
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <div class="flex flex-wrap gap-1 border-b border-[#19140020] dark:border-[#3E3E3A]">
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium data-[active=true]:bg-white data-[active=true]:text-[#1b1b18] data-[active=true]:shadow-[inset_0_1px_0_0_#f53003] dark:data-[active=true]:bg-[#161615] dark:data-[active=true]:text-[#EDEDEC]" data-tab="system" data-active="true">
                    System
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="services">
                    Dienste
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="firewall">
                    Firewall
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="apache">
                    Apache
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="mysql">
                    MySQL
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="github">
                    GitHub
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="terminal">
                    Terminal
                </button>
            </div>
        </div>

        {{-- System Tab --}}
        <div id="tab-system" class="tab-content mt-6" data-tab="system">
            <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
                <div class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Systeminformationen</p>
                            <button type="button" onclick="refreshDashboard()" class="rounded-lg border border-[#19140035] px-3 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                Aktualisieren
                            </button>
                        </div>

                        <div id="dashboard-loading" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Verbinde zum Server...
                        </div>

                        <div id="dashboard-content" class="mt-6 hidden">
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Hostname</p>
                                    <p id="sys-hostname" class="mt-1 text-sm font-medium">-</p>
                                </div>
                                <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Betriebssystem</p>
                                    <p id="sys-os" class="mt-1 text-sm font-medium">-</p>
                                </div>
                                <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Uptime</p>
                                    <p id="sys-uptime" class="mt-1 text-sm font-medium">-</p>
                                </div>
                                <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Load</p>
                                    <p id="sys-load" class="mt-1 text-sm font-medium">-</p>
                                </div>
                            </div>

                            <div class="mt-6 space-y-4">
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium">CPU</span>
                                        <span id="cpu-text" class="text-[#706f6c] dark:text-[#A1A09A]">-</span>
                                    </div>
                                    <div id="cpu-bar" class="mt-1 h-3 rounded-full bg-[#19140020] dark:bg-[#3E3E3A]">
                                        <div class="h-full rounded-full transition-all" style="width: 0%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium">RAM</span>
                                        <span id="ram-text" class="text-[#706f6c] dark:text-[#A1A09A]">-</span>
                                    </div>
                                    <div id="ram-bar" class="mt-1 h-3 rounded-full bg-[#19140020] dark:bg-[#3E3E3A]">
                                        <div class="h-full rounded-full transition-all" style="width: 0%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium">Disk</span>
                                        <span id="disk-text" class="text-[#706f6c] dark:text-[#A1A09A]">-</span>
                                    </div>
                                    <div id="disk-bar" class="mt-1 h-3 rounded-full bg-[#19140020] dark:bg-[#3E3E3A]">
                                        <div class="h-full rounded-full transition-all" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                                <p class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Details</p>
                                <pre id="sys-details" class="mt-2 overflow-x-auto text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]"></pre>
                            </div>
                        </div>

                        <div id="dashboard-error" class="mt-4 hidden rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200"></div>
                    </div>

                    {{-- System Actions --}}
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">System-Aktionen</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" onclick="systemAction('{{ route('server.update-packages', $server) }}', 'apt update ausführen?')" class="rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                                APT Update
                            </button>
                            <button type="button" onclick="systemAction('{{ route('server.upgrade-packages', $server) }}', 'System-Upgrade ausführen? Dies kann einige Minuten dauern.')" class="rounded-lg bg-[#f59e0b] px-4 py-2 text-sm font-medium text-white hover:bg-[#d97706]">
                                APT Upgrade
                            </button>
                            <button type="button" onclick="systemAction('{{ route('server.restart', $server) }}', 'Server neu starten?')" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                                Neustart
                            </button>
                            <button type="button" onclick="systemAction('{{ route('server.stop', $server) }}', 'Server herunterfahren?')" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                Herunterfahren
                            </button>
                        </div>
                        <div id="action-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                    </div>
                </div>

                <aside class="space-y-6">
                    {{-- Server Details --}}
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server Details</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-[#706f6c] dark:text-[#A1A09A]">Status</dt>
                                <dd id="conn-status" class="font-medium text-[#706f6c] dark:text-[#A1A09A]">Unbekannt</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-[#706f6c] dark:text-[#A1A09A]">Auth</dt>
                                <dd class="font-medium">{{ $server->auth_type === 'key' ? 'SSH-Key' : 'Passwort' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-[#706f6c] dark:text-[#A1A09A]">Sudo</dt>
                                <dd class="font-medium">{{ $server->use_sudo ? 'Ja' : 'Nein' }}</dd>
                            </div>
                            @if ($server->notes)
                                <div class="flex justify-between">
                                    <dt class="text-[#706f6c] dark:text-[#A1A09A]">Notizen</dt>
                                    <dd class="max-w-[160px] text-right font-medium">{{ $server->notes }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Service Status --}}
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Installierte Dienste</p>
                        <div id="services-list" class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center gap-2 text-[#706f6c] dark:text-[#A1A09A]">-</div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        {{-- Services Tab --}}
        <div id="tab-services" class="tab-content mt-6 hidden" data-tab="services">
            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Dienstverwaltung</p>
                <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Installiere oder deinstalliere Dienste auf dem Server.</p>

                <div id="services-tab-loading" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Lade...</div>
                <div id="services-tab-content" class="mt-4 hidden space-y-2"></div>
                <div id="services-tab-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
            </div>
        </div>

        {{-- Firewall Tab --}}
        <div id="tab-firewall" class="tab-content mt-6 hidden" data-tab="firewall">
            <div>
                <div id="fw-dash-loading" class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Lade Firewall-Status...</p>
                </div>

                <div id="fw-dash-install" class="hidden">
                    <div class="rounded-2xl bg-white p-8 text-center shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                        <p class="text-lg font-semibold">UFW ist nicht installiert</p>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Installiere UFW um die Firewall zu verwalten.</p>
                        <button type="button" id="btn-install-ufw-dash" onclick="installUfwDash(this)" class="mt-6 rounded-lg bg-[#1b1b18] px-6 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            UFW installieren
                        </button>
                        <div id="fw-dash-install-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
                    </div>
                </div>

                <div id="fw-dash-content" class="hidden">
                    <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
                        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Firewall-Regeln</p>
                                    <span id="fw-dash-badge" class="rounded-full px-2.5 py-0.5 text-xs font-medium"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="fw-dash-enable" onclick="fwDashAction('{{ route('server.firewall.enable', $server) }}', 'UFW aktivieren?')" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Aktivieren</button>
                                    <button type="button" id="fw-dash-disable" onclick="fwDashAction('{{ route('server.firewall.disable', $server) }}', 'UFW wirklich deaktivieren?')" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Deaktivieren</button>
                                </div>
                            </div>

                            <div id="fw-dash-empty" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Keine Firewall-Regeln vorhanden.</div>

                            <div id="fw-dash-table" class="mt-4 hidden overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-[#19140020] text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                            <th class="px-3 py-2 font-medium">Nr.</th>
                                            <th class="px-3 py-2 font-medium">Aktion</th>
                                            <th class="px-3 py-2 font-medium">Port</th>
                                            <th class="px-3 py-2 font-medium">Proto</th>
                                            <th class="px-3 py-2 font-medium">Quelle</th>
                                            <th class="px-3 py-2 font-medium"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="fw-dash-tbody"></tbody>
                                </table>
                            </div>

                            <div id="fw-dash-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
                        </div>

                        <aside class="space-y-6">
                            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Port verwalten</p>
                                <div class="mt-4">
                                    <input type="number" id="fw-dash-port" min="1" max="65535" placeholder="Port" class="block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button type="button" onclick="fwDashPortAction('allow')" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Freigeben</button>
                                    <button type="button" onclick="fwDashPortAction('deny')" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Blocken</button>
                                </div>
                            </div>

                            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Schnellzugriff</p>
                                <div class="mt-4 flex flex-col gap-2">
                                    @foreach ([['l' => 'SSH (22)', 'p' => '22'], ['l' => 'HTTP (80)', 'p' => '80'], ['l' => 'HTTPS (443)', 'p' => '443'], ['l' => 'MySQL (3306)', 'p' => '3306'], ['l' => 'PostgreSQL (5432)', 'p' => '5432']] as $preset)
                                        <button type="button" onclick="fwDashPreset('{{ $preset['p'] }}')" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">{{ $preset['l'] }}</button>
                                    @endforeach
                                </div>
                                <div id="fw-dash-preset-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>

        {{-- Apache Tab --}}
        <div id="tab-apache" class="tab-content mt-6 hidden" data-tab="apache">
            <div>
                <div id="ap-dash-loading" class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Lade Apache-Status...</p>
                </div>

                <div id="ap-dash-install" class="hidden">
                    <div class="rounded-2xl bg-white p-8 text-center shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                        <p class="text-lg font-semibold">Apache ist nicht installiert</p>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Installiere Apache um den Webserver zu verwalten.</p>
                        <button type="button" id="btn-install-apache-dash" onclick="installApacheDash(this)" class="mt-6 rounded-lg bg-[#1b1b18] px-6 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            Apache installieren
                        </button>
                        <div id="ap-dash-install-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
                    </div>
                </div>

                <div id="ap-dash-content" class="hidden">
                    <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
                        <div class="space-y-6">
                            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Apache</p>
                                        <span id="ap-dash-badge" class="rounded-full px-2.5 py-0.5 text-xs font-medium"></span>
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <button type="button" onclick="apDashService('start')" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Start</button>
                                        <button type="button" onclick="apDashService('stop')" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700">Stop</button>
                                        <button type="button" onclick="apDashService('restart')" class="rounded-lg bg-yellow-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-yellow-700">Restart</button>
                                        <button type="button" onclick="apDashService('reload')" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Reload</button>
                                    </div>
                                </div>
                                <div id="ap-dash-version" class="mt-3 text-xs text-[#706f6c] dark:text-[#A1A09A]"></div>
                                <div class="mt-3 flex gap-2">
                                    <button type="button" onclick="apDashConfigtest()" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Config Test</button>
                                </div>
                                <div id="ap-dash-configtest-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                            </div>

                            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Sites</p>
                                <div id="ap-dash-sites-empty" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Keine Sites konfiguriert.</div>
                                <div id="ap-dash-sites-table" class="mt-4 hidden overflow-x-auto">
                                    <table class="w-full text-left text-sm">
                                        <thead>
                                            <tr class="border-b border-[#19140020] text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                                <th class="px-3 py-2 font-medium">Name</th>
                                                <th class="px-3 py-2 font-medium">Status</th>
                                                <th class="px-3 py-2 font-medium">ServerName</th>
                                                <th class="px-3 py-2 font-medium"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="ap-dash-sites-tbody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <aside class="space-y-6">
                            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Module</p>
                                <div id="ap-dash-modules-loading" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Lade Module...</div>
                                <div id="ap-dash-modules-list" class="mt-4 hidden space-y-1 max-h-72 overflow-y-auto"></div>
                            </div>
                            <div id="ap-dash-result" class="hidden rounded-xl p-3 text-sm"></div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>

        {{-- Placeholder tabs for future phases --}}
        @foreach (['mysql', 'github', 'terminal'] as $tab)
            <div id="tab-{{ $tab }}" class="tab-content mt-6 hidden" data-tab="{{ $tab }}">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        @switch($tab)
                            @case('mysql') MySQL-Datenbank-Verwaltung — in Kürze verfügbar. @break
                            @case('github') GitHub-Deployment — in Kürze verfügbar. @break
                            @case('terminal') SSH-Terminal — in Kürze verfügbar. @break
                        @endswitch
                    </p>
                </div>
            </div>
        @endforeach
    </section>

    @push('scripts')
    <script>
    let refreshInterval = null;
    let currentServerId = {{ $server->id }};

    function formatBytes(mb) {
        if (mb >= 1024) return (mb / 1024).toFixed(1) + ' GB';
        return mb + ' MB';
    }

    function getBarColor(pct) {
        if (pct >= 90) return '#f53003';
        if (pct >= 70) return '#f59e0b';
        return '#22c55e';
    }

    function updateUsage(barId, textId, pct, used, total, label) {
        const bar = document.getElementById(barId);
        const text = document.getElementById(textId);
        if (!bar || !text) return;

        const fill = bar.querySelector('div');
        fill.style.width = pct + '%';
        fill.style.backgroundColor = getBarColor(pct);

        const formattedUsed = formatBytes(used);
        const formattedTotal = formatBytes(total);
        text.textContent = `${pct}% | ${formattedUsed} / ${formattedTotal}`;
    }

    function refreshDashboard() {
        const loading = document.getElementById('dashboard-loading');
        const content = document.getElementById('dashboard-content');
        const error = document.getElementById('dashboard-error');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        error.classList.add('hidden');

        fetch('{{ route('server.dashboard.refresh', $server) }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');

                if (data.error) {
                    error.textContent = data.error;
                    error.classList.remove('hidden');
                    return;
                }

                window.lastDashboardData = data;

                document.getElementById('sys-hostname').textContent = data.hostname || '-';
                document.getElementById('sys-os').textContent = data.os || '-';
                document.getElementById('sys-uptime').textContent = data.uptime || '-';
                document.getElementById('sys-load').textContent = data.load || '-';

                if (data.cpu_percent !== null) {
                    updateUsage('cpu-bar', 'cpu-text', data.cpu_percent, data.cpu_percent, 100, 'CPU');
                } else {
                    document.getElementById('cpu-text').textContent = '-';
                }

                if (data.ram_percent !== null && data.ram_total_mb !== null) {
                    updateUsage('ram-bar', 'ram-text', data.ram_percent, data.ram_used_mb, data.ram_total_mb, 'RAM');
                } else {
                    document.getElementById('ram-text').textContent = '-';
                }

                if (data.disk_percent !== null && data.disk_total_mb !== null) {
                    updateUsage('disk-bar', 'disk-text', data.disk_percent, data.disk_used_mb, data.disk_total_mb, 'Disk');
                } else {
                    document.getElementById('disk-text').textContent = '-';
                }

                const details = [];
                if (data.php_version) details.push('PHP: ' + data.php_version);
                if (data.apache_version) details.push('Apache: ' + data.apache_version);
                if (data.mysql_version) details.push('MySQL: ' + data.mysql_version);
                if (data.node_version) details.push('Node.js: ' + data.node_version);
                if (data.nvm_version) details.push('nvm: ' + data.nvm_version);
                if (data.composer_version) details.push('Composer: ' + data.composer_version);
                document.getElementById('sys-details').textContent = details.join('\n') || 'Keine Detailinformationen verfügbar.';

                // Update services list
                const servicesList = document.getElementById('services-list');
                servicesList.innerHTML = '';
                const services = [
                    { key: 'php', label: 'PHP', version: data.php_version },
                    { key: 'apache', label: 'Apache', version: data.apache_version },
                    { key: 'mysql', label: 'MySQL', version: data.mysql_version },
                    { key: 'node', label: 'Node.js', version: data.node_version },
                    { key: 'nvm', label: 'nvm', version: data.nvm_version },
                    { key: 'composer', label: 'Composer', version: data.composer_version },
                ];
                for (const svc of services) {
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between';
                    if (svc.version) {
                        div.innerHTML = `
                            <span class="flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-green-500"></span>
                                ${svc.label}
                            </span>
                            <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">${svc.version}</span>
                        `;
                    } else {
                        div.innerHTML = `
                            <span class="flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-[#19140035] dark:bg-[#3E3E3A]"></span>
                                ${svc.label}
                            </span>
                            <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Nicht installiert</span>
                        `;
                    }
                    servicesList.appendChild(div);
                }

                document.getElementById('conn-status').textContent = 'Online';
                document.getElementById('conn-status').className = 'font-medium text-green-500';

                content.classList.remove('hidden');
            })
            .catch(err => {
                loading.classList.add('hidden');
                error.textContent = 'Verbindungsfehler: ' + err.message;
                error.classList.remove('hidden');
                document.getElementById('conn-status').textContent = 'Offline';
                document.getElementById('conn-status').className = 'font-medium text-red-500';
            });
    }

    function testConnection() {
        const btn = document.getElementById('btn-test-connection');
        btn.disabled = true;
        btn.textContent = 'Teste...';

        fetch('{{ route('server.dashboard.test-connection', $server) }}')
            .then(r => r.json())
            .then(data => {
                const status = document.getElementById('conn-status');
                if (data.success) {
                    status.textContent = 'Online (' + data.latency_ms + ' ms)';
                    status.className = 'font-medium text-green-500';
                } else {
                    status.textContent = 'Offline';
                    status.className = 'font-medium text-red-500';
                }
                btn.textContent = 'Verbindung testen';
                btn.disabled = false;
            })
            .catch(() => {
                btn.textContent = 'Fehler';
                btn.disabled = false;
            });
    }

    function systemAction(url, confirmMsg) {
        if (!confirm(confirmMsg)) return;

        const result = document.getElementById('action-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm';
        result.textContent = 'Führe Befehl aus...';
        result.classList.remove('hidden');

        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.className = 'mt-3 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                } else {
                    result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                }
                result.textContent = data.message;
            })
            .catch(err => {
                result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
            });
    }

    // Services tab
    const SERVICE_DEFS = [
        { key: 'php', label: 'PHP', versionField: 'php_version' },
        { key: 'apache', label: 'Apache', versionField: 'apache_version' },
        { key: 'mysql', label: 'MySQL', versionField: 'mysql_version' },
        { key: 'node', label: 'Node.js', versionField: 'node_version' },
        { key: 'nvm', label: 'nvm', versionField: 'nvm_version' },
        { key: 'npm', label: 'npm', versionField: 'node_version' },
        { key: 'composer', label: 'Composer', versionField: 'composer_version' },
    ];

    function renderServicesTab(data) {
        const loading = document.getElementById('services-tab-loading');
        const content = document.getElementById('services-tab-content');
        if (!loading || !content) return;

        loading.classList.add('hidden');
        content.innerHTML = '';

        for (const svc of SERVICE_DEFS) {
            const version = data[svc.versionField];
            const installed = !!version;
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]';
            div.innerHTML = `
                <div class="flex items-center gap-3">
                    <span class="size-3 shrink-0 rounded-full ${installed ? 'bg-green-500' : 'bg-[#19140035] dark:bg-[#3E3E3A]'}"></span>
                    <div>
                        <p class="text-sm font-medium">${svc.label}</p>
                        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">${installed ? version : 'Nicht installiert'}</p>
                    </div>
                </div>
                <div>
                    ${installed
                        ? `<button data-service-key="${svc.key}" data-service-action="deinstall" onclick="serviceTabAction(this)" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Deinstallieren</button>`
                        : `<button data-service-key="${svc.key}" data-service-action="install" onclick="serviceTabAction(this)" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Installieren</button>`
                    }
                </div>
            `;
            content.appendChild(div);
        }

        content.classList.remove('hidden');
    }

    function loadServicesTab(refreshData) {
        const loading = document.getElementById('services-tab-loading');
        if (!loading) return;

        if (refreshData) {
            loading.classList.add('hidden');
            renderServicesTab(refreshData);
            return;
        }

        loading.textContent = 'Lade...';
        loading.classList.remove('hidden');

        fetch('{{ route('server.dashboard.refresh', $server) }}')
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    loading.textContent = data.error;
                    return;
                }
                renderServicesTab(data);
            })
            .catch(() => {
                loading.textContent = 'Fehler beim Laden.';
            });
    }

    function serviceTabAction(btn) {
        const key = btn.dataset.serviceKey;
        const action = btn.dataset.serviceAction;
        const labels = { php: 'PHP', apache: 'Apache', mysql: 'MySQL', node: 'Node.js', nvm: 'nvm', npm: 'npm', composer: 'Composer' };
        const label = labels[key] || key;
        const msg = action === 'install' ? `${label} installieren? Dies kann einige Minuten dauern.` : `${label} deinstallieren?`;
        if (!confirm(msg)) return;

        const result = document.getElementById('services-tab-result');
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.classList.remove('hidden');

        const allBtns = document.querySelectorAll('#services-tab-content button[data-service-key]');
        allBtns.forEach(b => { b.disabled = true; b.style.opacity = '0.5'; b.style.cursor = 'wait'; });

        btn.textContent = 'Warte...';
        btn.style.opacity = '1';
        result.textContent = `${label}: Befehl wird ausgeführt. Bitte warten...`;

        const url = '{{ route('server.services.install', ['server' => $server, 'service' => '__SERVICE__']) }}'.replace('__SERVICE__', key);
        const finalUrl = action === 'deinstall' ? url.replace('install', 'deinstall') : url;

        fetch(finalUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                allBtns.forEach(b => { b.disabled = false; b.style.opacity = ''; b.style.cursor = ''; });
                if (data.success) {
                    result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                    setTimeout(() => loadServicesTab(), 2000);
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

    // Firewall Tab
    function loadFirewallTab() {
        const loading = document.getElementById('fw-dash-loading');
        const install = document.getElementById('fw-dash-install');
        const content = document.getElementById('fw-dash-content');

        loading.classList.remove('hidden');
        install.classList.add('hidden');
        content.classList.add('hidden');

        fetch('{{ route('server.firewall.status', $server) }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');
                if (!data.success) {
                    loading.textContent = 'Fehler: ' + (data.error || 'Unbekannter Fehler');
                    loading.classList.remove('hidden');
                    return;
                }
                if (!data.installed) {
                    install.classList.remove('hidden');
                    return;
                }
                content.classList.remove('hidden');
                renderFwDashStatus(data.active);
                loadFwDashRules();
            })
            .catch(err => {
                loading.textContent = 'Verbindungsfehler: ' + err.message;
                loading.classList.remove('hidden');
            });
    }

    function renderFwDashStatus(active) {
        const badge = document.getElementById('fw-dash-badge');
        const enableBtn = document.getElementById('fw-dash-enable');
        const disableBtn = document.getElementById('fw-dash-disable');
        if (active) {
            badge.textContent = 'Aktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            enableBtn.style.display = 'none';
            disableBtn.style.display = '';
        } else {
            badge.textContent = 'Inaktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            enableBtn.style.display = '';
            disableBtn.style.display = 'none';
        }
    }

    function loadFwDashRules() {
        fetch('{{ route('server.firewall.rules', $server) }}')
            .then(r => r.json())
            .then(data => {
                const empty = document.getElementById('fw-dash-empty');
                const table = document.getElementById('fw-dash-table');
                const tbody = document.getElementById('fw-dash-tbody');
                if (!data.success || !data.rules || data.rules.length === 0) {
                    empty.classList.remove('hidden');
                    table.classList.add('hidden');
                    return;
                }
                empty.classList.add('hidden');
                tbody.innerHTML = '';
                for (const rule of data.rules) {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-[#19140020] dark:border-[#3E3E3A]';
                    tr.innerHTML = `
                        <td class="px-3 py-2">${rule.number}</td>
                        <td class="px-3 py-2"><span class="rounded px-2 py-0.5 text-xs font-medium ${rule.action === 'ALLOW' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'}">${rule.action}</span></td>
                        <td class="px-3 py-2">${rule.port}</td>
                        <td class="px-3 py-2">${rule.protocol || '-'}</td>
                        <td class="px-3 py-2">${rule.source}</td>
                        <td class="px-3 py-2"><button onclick="fwDashDeleteRule(${rule.number}, '${rule.port}')" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">Löschen</button></td>
                    `;
                    tbody.appendChild(tr);
                }
                table.classList.remove('hidden');
            });
    }

    function fwDashShowResult(msg, success) {
        const el = document.getElementById('fw-dash-result');
        el.className = 'mt-4 rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function fwDashAction(url, confirmMsg) {
        if (confirmMsg && !confirm(confirmMsg)) return;
        fwDashShowResult('Führe Befehl aus...', true);
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                fwDashShowResult(data.message, data.success);
                if (data.success) setTimeout(loadFirewallTab, 1000);
            })
            .catch(err => fwDashShowResult('Fehler: ' + err.message, false));
    }

    function fwDashPortAction(action) {
        const port = document.getElementById('fw-dash-port').value.trim();
        if (!port) { fwDashShowResult('Bitte einen Port eingeben.', false); return; }
        if (action === 'deny' && port === '22' && !confirm('Du blockierst den SSH-Port (22)! Verbindung kann abbrechen.\n\nPort 22 wirklich blocken?')) return;
        fwDashShowResult('Führe Befehl aus...', true);
        const url = action === 'allow'
            ? '{{ route('server.firewall.allow', $server) }}'
            : '{{ route('server.firewall.deny', $server) }}';
        fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ port: port, protocol: 'tcp' }) })
            .then(r => r.json())
            .then(data => {
                fwDashShowResult(data.message, data.success);
                document.getElementById('fw-dash-port').value = '';
                if (data.success) setTimeout(loadFirewallTab, 1000);
            })
            .catch(err => fwDashShowResult('Fehler: ' + err.message, false));
    }

    function fwDashDeleteRule(number, port) {
        if (!confirm('Regel ' + number + ' (Port ' + port + ') wirklich löschen?')) return;
        fwDashShowResult('Lösche Regel...', true);
        fetch('{{ route('server.firewall.destroy', ['server' => $server, 'rule' => '__RULE__']) }}'.replace('__RULE__', number), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                fwDashShowResult(data.message, data.success);
                if (data.success) setTimeout(loadFirewallTab, 1000);
            })
            .catch(err => fwDashShowResult('Fehler: ' + err.message, false));
    }

    function fwDashPreset(port) {
        const el = document.getElementById('fw-dash-preset-result');
        el.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        el.textContent = 'Öffne Port ' + port + '...';
        el.classList.remove('hidden');
        fetch('{{ route('server.firewall.allow', $server) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ port: port, protocol: 'tcp' }) })
            .then(r => r.json())
            .then(data => {
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.message;
                if (data.success) setTimeout(loadFirewallTab, 1000);
            })
            .catch(err => {
                el.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                el.textContent = 'Fehler: ' + err.message;
            });
    }

    function installUfwDash(btn) {
        const result = document.getElementById('fw-dash-install-result');
        btn.disabled = true;
        btn.textContent = 'Installiere...';
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.textContent = 'UFW wird installiert...';
        result.classList.remove('hidden');
        fetch('{{ route('server.services.install', ['server' => $server, 'service' => 'ufw']) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                    result.textContent = 'UFW wurde installiert.';
                    setTimeout(loadFirewallTab, 1500);
                } else {
                    result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                    result.textContent = data.message;
                    btn.disabled = false;
                    btn.textContent = 'UFW installieren';
                }
            })
            .catch(err => {
                result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
                btn.disabled = false;
                btn.textContent = 'UFW installieren';
            });
    }

    // Apache Tab
    function loadApacheTab() {
        const loading = document.getElementById('ap-dash-loading');
        const install = document.getElementById('ap-dash-install');
        const content = document.getElementById('ap-dash-content');

        loading.classList.remove('hidden');
        install.classList.add('hidden');
        content.classList.add('hidden');

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
                    install.classList.remove('hidden');
                    return;
                }
                content.classList.remove('hidden');
                renderApDashStatus(data);
                loadApDashSites();
                loadApDashModules();
            })
            .catch(err => {
                loading.textContent = 'Verbindungsfehler: ' + err.message;
                loading.classList.remove('hidden');
            });
    }

    function renderApDashStatus(data) {
        const badge = document.getElementById('ap-dash-badge');
        const version = document.getElementById('ap-dash-version');
        if (data.active) {
            badge.textContent = 'Aktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        } else {
            badge.textContent = 'Inaktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        }
        version.textContent = data.version || '';
    }

    function apDashShowResult(msg, success) {
        const el = document.getElementById('ap-dash-result');
        el.className = 'rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function apDashService(action) {
        const labels = { start: 'starten', stop: 'stoppen', restart: 'neu starten', reload: 'neu laden' };
        if (!confirm('Apache ' + labels[action] + '?')) return;
        apDashShowResult('Apache wird ' + labels[action] + '...', true);
        fetch('{{ route('server.apache.service', ['server' => $server, 'action' => '__ACTION__']) }}'.replace('__ACTION__', action), { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                apDashShowResult(data.message, data.success);
                if (data.success) setTimeout(loadApacheTab, 2000);
            })
            .catch(err => apDashShowResult('Fehler: ' + err.message, false));
    }

    function apDashConfigtest() {
        const el = document.getElementById('ap-dash-configtest-result');
        el.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        el.textContent = 'Prüfe Konfiguration...';
        el.classList.remove('hidden');
        fetch('{{ route('server.apache.configtest', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.output || (data.success ? 'Syntax OK' : 'Fehler');
            })
            .catch(err => {
                el.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                el.textContent = 'Fehler: ' + err.message;
            });
    }

    function loadApDashSites() {
        fetch('{{ route('server.apache.sites', $server) }}')
            .then(r => r.json())
            .then(data => {
                const empty = document.getElementById('ap-dash-sites-empty');
                const table = document.getElementById('ap-dash-sites-table');
                const tbody = document.getElementById('ap-dash-sites-tbody');
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
                        <td class="px-3 py-2 font-medium text-xs">${site.name}</td>
                        <td class="px-3 py-2">
                            <span class="rounded px-2 py-0.5 text-xs font-medium ${enabled ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-[#19140020] text-[#706f6c] dark:bg-[#3E3E3A] dark:text-[#A1A09A]'}">${enabled ? 'Enabled' : 'Disabled'}</span>
                        </td>
                        <td class="px-3 py-2 text-xs">${site.server_name}</td>
                        <td class="px-3 py-2">
                            ${enabled
                                ? `<button onclick="apDashSiteAction('disable', '${site.name}')" class="text-xs text-yellow-600 hover:text-yellow-800 dark:text-yellow-400">Deaktivieren</button>`
                                : `<button onclick="apDashSiteAction('enable', '${site.name}')" class="text-xs text-green-600 hover:text-green-800 dark:text-green-400">Aktivieren</button>`
                            }
                        </td>
                    `;
                    tbody.appendChild(tr);
                }
                table.classList.remove('hidden');
            });
    }

    function apDashSiteAction(action, site) {
        const label = action === 'enable' ? 'aktivieren' : 'deaktivieren';
        if (!confirm('Site ' + site + ' ' + label + '?')) return;
        apDashShowResult('Site wird ' + label + '...', true);
        const url = action === 'enable'
            ? '{{ route('server.apache.sites.enable', ['server' => $server, 'site' => '__SITE__']) }}'.replace('__SITE__', site)
            : '{{ route('server.apache.sites.disable', ['server' => $server, 'site' => '__SITE__']) }}'.replace('__SITE__', site);
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                apDashShowResult(data.message, data.success);
                if (data.success) setTimeout(loadApacheTab, 1500);
            })
            .catch(err => apDashShowResult('Fehler: ' + err.message, false));
    }

    function loadApDashModules() {
        const loading = document.getElementById('ap-dash-modules-loading');
        const list = document.getElementById('ap-dash-modules-list');
        loading.classList.remove('hidden');
        list.classList.add('hidden');
        fetch('{{ route('server.apache.modules', $server) }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');
                if (!data.success || !data.modules) return;
                list.innerHTML = '';
                for (const mod of data.modules) {
                    const enabled = mod.enabled === 'enabled';
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between rounded-lg border border-[#19140020] px-3 py-1.5 text-xs dark:border-[#3E3E3A]';
                    div.innerHTML = `
                        <span class="flex items-center gap-1.5">
                            <span class="size-1.5 rounded-full ${enabled ? 'bg-green-500' : 'bg-[#19140035] dark:bg-[#3E3E3A]'}"></span>
                            ${mod.name}
                        </span>
                        ${enabled
                            ? `<button onclick="apDashModuleAction('disable', '${mod.name}')" class="text-red-600 hover:text-red-800 dark:text-red-400">Deaktivieren</button>`
                            : `<button onclick="apDashModuleAction('enable', '${mod.name}')" class="text-green-600 hover:text-green-800 dark:text-green-400">Aktivieren</button>`
                        }
                    `;
                    list.appendChild(div);
                }
                list.classList.remove('hidden');
            })
            .catch(() => { loading.textContent = 'Fehler beim Laden.'; });
    }

    function apDashModuleAction(action, mod) {
        const label = action === 'enable' ? 'aktivieren' : 'deaktivieren';
        if (!confirm('Modul ' + mod + ' ' + label + '?')) return;
        apDashShowResult('Modul wird ' + label + '...', true);
        const url = action === 'enable'
            ? '{{ route('server.apache.modules.enable', ['server' => $server, 'module' => '__MOD__']) }}'.replace('__MOD__', mod)
            : '{{ route('server.apache.modules.disable', ['server' => $server, 'module' => '__MOD__']) }}'.replace('__MOD__', mod);
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                apDashShowResult(data.message, data.success);
                if (data.success) setTimeout(loadApDashModules, 1500);
            })
            .catch(err => apDashShowResult('Fehler: ' + err.message, false));
    }

    function installApacheDash(btn) {
        const result = document.getElementById('ap-dash-install-result');
        btn.disabled = true;
        btn.textContent = 'Installiere...';
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.textContent = 'Apache wird installiert...';
        result.classList.remove('hidden');
        fetch('{{ route('server.apache.install', $server) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                    result.textContent = 'Apache wurde installiert.';
                    setTimeout(loadApacheTab, 2000);
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

    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.dataset.active = 'false';
                b.classList.remove('bg-white', 'text-[#1b1b18]', 'shadow-[inset_0_1px_0_0_#f53003]', 'dark:bg-[#161615]', 'dark:text-[#EDEDEC]');
                b.classList.add('text-[#706f6c]', 'dark:text-[#A1A09A]');
            });
            this.dataset.active = 'true';
            this.classList.remove('text-[#706f6c]', 'dark:text-[#A1A09A]');
            this.classList.add('bg-white', 'text-[#1b1b18]', 'shadow-[inset_0_1px_0_0_#f53003]', 'dark:bg-[#161615]', 'dark:text-[#EDEDEC]');

            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.add('hidden'));
            const tab = document.getElementById('tab-' + this.dataset.tab);
            if (tab) tab.classList.remove('hidden');

            if (this.dataset.tab === 'services') {
                loadServicesTab();
            } else if (this.dataset.tab === 'firewall') {
                loadFirewallTab();
            } else if (this.dataset.tab === 'apache') {
                loadApacheTab();
            }
        });
    });

    // Auto-refresh every 30s
    refreshDashboard();
    refreshInterval = setInterval(refreshDashboard, 30000);
    </script>
    @endpush
</x-layouts.app>
