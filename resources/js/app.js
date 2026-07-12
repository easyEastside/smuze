import { FitAddon } from '@xterm/addon-fit';
import { Terminal } from '@xterm/xterm';

window.SmuzeTerminal = { FitAddon, Terminal };

document.addEventListener('click', (event) => {
    const navbarToggle = event.target.closest('[data-navbar-toggle]');

    if (navbarToggle) {
        toggleNavbarMenu(navbarToggle);

        return;
    }

    closeNavbarMenus(event.target.closest('[data-navbar-menu]'));

    const addButton = event.target.closest('[data-survey-add-option]');

    if (addButton) {
        const container = document.querySelector('[data-survey-options]');

        if (! container) {
            return;
        }

        reindexSurveyOptions(container);

        const index = container.querySelectorAll('[data-survey-option]').length;
        const wrapper = document.createElement('div');

        wrapper.dataset.surveyOption = '';
        wrapper.innerHTML = `
            <label for="option_${index}" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Option ${index + 1}</label>
            <div class="mt-1 flex gap-2">
                <input type="text" name="questions[0][options][${index}][label]" id="option_${index}" class="w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]" />
                <button type="button" data-survey-remove-option class="rounded-lg border border-[#19140035] px-3 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Remove</button>
            </div>
        `;

        container.append(wrapper);
        wrapper.querySelector('input')?.focus();

        return;
    }

    const removeButton = event.target.closest('[data-survey-remove-option]');

    if (! removeButton) {
        return;
    }

    const container = document.querySelector('[data-survey-options]');

    if (! container || container.querySelectorAll('[data-survey-option]').length <= 2) {
        return;
    }

    removeButton.closest('[data-survey-option]')?.remove();
    reindexSurveyOptions(container);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeNavbarMenus();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    initializeFloatingCommandLog();
});

function toggleNavbarMenu(toggle) {
    const menuName = toggle.dataset.navbarToggle;
    const menu = document.querySelector(`[data-navbar-menu="${menuName}"]`);

    if (! menu) {
        return;
    }

    const isOpening = menu.classList.contains('hidden');

    closeNavbarMenus(menu);

    menu.classList.toggle('hidden', ! isOpening);
    toggle.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
}

function closeNavbarMenus(exceptMenu = null) {
    document.querySelectorAll('[data-navbar-menu]').forEach((menu) => {
        if (menu === exceptMenu) {
            return;
        }

        menu.classList.add('hidden');

        const toggle = document.querySelector(`[data-navbar-toggle="${menu.dataset.navbarMenu}"]`);

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

function reindexSurveyOptions(container) {
    container.querySelectorAll('[data-survey-option]').forEach((option, index) => {
        const label = option.querySelector('label');
        const input = option.querySelector('input');

        if (label) {
            label.setAttribute('for', `option_${index}`);
            label.textContent = `Option ${index + 1}`;
        }

        if (input) {
            input.id = `option_${index}`;
            input.name = `questions[0][options][${index}][label]`;
        }
    });
}

function initializeFloatingCommandLog() {
    const panel = document.getElementById('floating-command-log');

    if (! panel) {
        return;
    }

    const container = panel.querySelector('[data-command-log-output]');
    const body = panel.querySelector('[data-command-log-body]');
    const status = panel.querySelector('[data-command-log-status]');
    const toggles = panel.querySelectorAll('[data-command-log-toggle]');
    const clearButton = panel.querySelector('[data-command-log-clear]');
    const connectButton = panel.querySelector('[data-command-log-connect]');
    const disconnectButton = panel.querySelector('[data-command-log-disconnect]');
    const endButton = panel.querySelector('[data-command-log-end]');
    const promptEl = panel.querySelector('[data-command-log-prompt]');
    const inputEl = panel.querySelector('[data-command-log-input]');
    const serverId = panel.dataset.serverId;
    const debugEnabled = panel.dataset.debugEnabled === '1';
    const sessionEndpoint = panel.dataset.sessionEndpoint;
    const csrfToken = panel.dataset.csrfToken;
    const storageKey = `smuze:server:${serverId}:command-log`;
    const collapsedKey = `smuze:server:${serverId}:command-log-collapsed`;
    const connectedKey = `smuze:server:${serverId}:command-log-connected`;

    if (! container || ! body || ! status || ! serverId || ! sessionEndpoint || ! csrfToken || ! window.SmuzeTerminal) {
        return;
    }

    const term = new window.SmuzeTerminal.Terminal({
        cursorBlink: true,
        convertEol: true,
        fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
        fontSize: 12,
        theme: {
            background: '#0b0f14',
            foreground: '#d6deeb',
            cursor: '#f53003',
            selectionBackground: '#334155',
        },
    });
    const fitAddon = new window.SmuzeTerminal.FitAddon();
    let socket = null;
    let isConnected = false;

    const readLog = () => {
        try {
            return localStorage.getItem(storageKey) || '';
        } catch {
            return '';
        }
    };

    const writeLog = (value) => {
        try {
            localStorage.setItem(storageKey, value.slice(-50000));
        } catch {
            // Ignore unavailable storage.
        }
    };

    const setShouldReconnect = (value) => {
        try {
            localStorage.setItem(connectedKey, value ? '1' : '0');
        } catch {
            // Ignore unavailable storage.
        }
    };

    const appendRaw = (value) => {
        const nextLog = `${readLog()}${value}`;

        writeLog(nextLog);
        term.write(value);
    };

    const setConnectionStatus = (label, connected = false) => {
        isConnected = connected;
        status.textContent = label;
        connectButton?.classList.toggle('hidden', connected);
        disconnectButton?.classList.toggle('hidden', ! connected);
        endButton?.classList.toggle('hidden', ! connected);
    };

    const setCollapsed = (collapsed) => {
        body.classList.toggle('hidden', collapsed);
        toggles.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

            if (toggle.textContent.trim() === 'Minimieren' || toggle.textContent.trim() === 'Öffnen') {
                toggle.textContent = collapsed ? 'Öffnen' : 'Minimieren';
            }
        });

        try {
            localStorage.setItem(collapsedKey, collapsed ? '1' : '0');
        } catch {
            // Ignore unavailable storage.
        }
    };

    const append = (message, level = 'info') => {
        if (String(message || '').trim() === '') {
            return;
        }

        const timestamp = new Date().toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const prefix = level === 'error' ? 'ERR' : level === 'success' ? 'OK ' : 'CMD';
        const line = `[${timestamp}] ${prefix} ${message}\r\n`;

        appendRaw(line);
        panel.classList.remove('hidden');
    };

    const renderPrompt = () => {
        if (! promptEl) return;
        promptEl.textContent = `${username}@${host} : `;
    };

    const username = panel.dataset.serverUsername || 'user';
    const host = panel.dataset.serverHost || 'host';
    renderPrompt();

    const resizeTerminal = () => {
        if (body.classList.contains('hidden')) {
            return;
        }

        fitAddon.fit();

        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ channel: 'terminal', type: 'resize', cols: term.cols, rows: term.rows }));
        }
    };

    const disconnect = () => {
        setShouldReconnect(false);

        if (socket) {
            socket.close();
        }

        socket = null;
        setConnectionStatus('Getrennt');
        if (inputEl) {
            inputEl.disabled = true;
            inputEl.placeholder = 'Nicht verbunden...';
            inputEl.value = '';
        }
    };

    const endSession = () => {
        setShouldReconnect(false);

        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ channel: 'terminal', type: 'end' }));
        }

        append('SSH-Terminal-Session wird beendet...');
        if (inputEl) {
            inputEl.disabled = true;
            inputEl.placeholder = 'Nicht verbunden...';
            inputEl.value = '';
        }
        disconnect();
    };

    const connect = () => {
        if (isConnected || socket) {
            return;
        }

        setCollapsed(false);
        setShouldReconnect(true);
        setConnectionStatus('Verbinde...');
        append('SSH-Terminal wird verbunden...');

        fetch(sessionEndpoint, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        })
            .then((response) => {
                if (! response.ok) {
                    throw new Error('Terminal-Session konnte nicht erstellt werden.');
                }

                return response.json();
            })
            .then((data) => {
                const url = new URL(data.websocket_url);
                url.searchParams.set('token', data.token);
                url.searchParams.set('cols', term.cols);
                url.searchParams.set('rows', term.rows);

                socket = new WebSocket(url.toString());
                socket.addEventListener('open', () => {
                    setConnectionStatus('Verbunden', true);
                    if (inputEl) {
                        inputEl.disabled = false;
                        inputEl.placeholder = '';
                        inputEl.focus();
                    }
                    socket.send(JSON.stringify({ channel: 'terminal', type: 'open', cols: term.cols, rows: term.rows }));
                });
                socket.addEventListener('message', (event) => {
                    const payload = JSON.parse(event.data);

                    if (payload.channel === 'terminal' && payload.type === 'output') {
                        appendRaw(payload.data);
                    }

                    if (payload.channel === 'terminal' && payload.type === 'replay') {
                        appendRaw(payload.data);
                    }

                    if (payload.channel === 'terminal' && payload.type === 'attached') {
                        setConnectionStatus('Verbunden', true);
                    }

                    if (payload.type === 'error') {
                        setConnectionStatus('Fehler');
                        append(payload.message || 'Terminal-Fehler.', 'error');
                    }

                    if (payload.channel === 'terminal' && payload.type === 'exit') {
                        setShouldReconnect(false);
                        setConnectionStatus('Beendet');
                        append(`Session beendet (Exit ${payload.exit_code ?? 'unbekannt'}).`);
                    }
                });
                socket.addEventListener('close', () => {
                    socket = null;
                    setConnectionStatus('Getrennt');
                    if (inputEl) {
                        inputEl.disabled = true;
                        inputEl.placeholder = 'Nicht verbunden...';
                    }
                });
                socket.addEventListener('error', () => {
                    setConnectionStatus('Fehler');
                    if (inputEl) {
                        inputEl.disabled = true;
                        inputEl.placeholder = 'Nicht verbunden...';
                    }
                    append('WebSocket-Verbindung fehlgeschlagen. Läuft npm run terminal oder composer run dev?', 'error');
                });
            })
            .catch((error) => {
                socket = null;
                setConnectionStatus('Fehler');
                append(`Fehler: ${error.message}`, 'error');
            });
    };

    term.loadAddon(fitAddon);
    term.open(container);
    term.write(readLog());

    const resizeObserver = new ResizeObserver(resizeTerminal);
    resizeObserver.observe(container);

    term.onData(() => {
        inputEl?.focus();
    });

    inputEl?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const cmd = inputEl.value;
            if (cmd.trim() === '') return;
            if (socket && socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify({ channel: 'terminal', type: 'input', data: cmd + '\r' }));
            }
            inputEl.value = '';
        }
    });

    panel.classList.remove('hidden');
    setCollapsed(readStoredBoolean(collapsedKey));
    resizeTerminal();

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const collapsed = ! body.classList.contains('hidden');

            setCollapsed(collapsed);

            if (! collapsed) {
                resizeTerminal();
                inputEl?.focus();
            }
        });
    });

    connectButton?.addEventListener('click', connect);
    disconnectButton?.addEventListener('click', disconnect);
    endButton?.addEventListener('click', endSession);

    clearButton?.addEventListener('click', () => {
        writeLog('');
        term.clear();
        status.textContent = 'Logs geleert';
    });

    window.SmuzeCommandLog = {
        open() {
            panel.classList.remove('hidden');
            setCollapsed(false);
            resizeTerminal();
            term.focus();
        },
        write(message, level = 'info') {
            append(message, level);
        },
        debug(message, level = 'info') {
            if (debugEnabled) {
                append(message, level);
            }
        },
        get debugEnabled() {
            return debugEnabled;
        },
        status(message) {
            status.textContent = message;
        },
        connect,
        disconnect,
        endSession,
        clear() {
            writeLog('');
            term.clear();
            status.textContent = 'Logs geleert';
        },
    };
}

function readStoredBoolean(key) {
    try {
        return localStorage.getItem(key) === '1';
    } catch {
        return false;
    }
}
