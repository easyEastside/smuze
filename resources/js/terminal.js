import { Terminal } from '@xterm/xterm';
import { FitAddon } from '@xterm/addon-fit';
import '@xterm/xterm/css/xterm.css';

const root = document.getElementById('terminal-root');

if (root) {
    const status = document.getElementById('terminal-status');
    const connectButton = document.getElementById('terminal-connect');
    const disconnectButton = document.getElementById('terminal-disconnect');
    const fitAddon = new FitAddon();
    let socket = null;

    const terminal = new Terminal({
        cursorBlink: true,
        convertEol: true,
        fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
        fontSize: 14,
        theme: {
            background: '#000000',
            foreground: '#d8f3dc',
            cursor: '#ffffff',
        },
    });

    terminal.loadAddon(fitAddon);
    terminal.open(root);
    fitAddon.fit();
    terminal.writeln('Bereit. Klicke auf "Verbinden", um eine interaktive Shell zu starten.');

    terminal.onData((data) => {
        if (socket?.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ type: 'input', data }));
        }
    });

    connectButton?.addEventListener('click', connect);
    disconnectButton?.addEventListener('click', disconnect);
    window.addEventListener('resize', () => {
        fitAddon.fit();
        sendResize();
    });

    async function connect() {
        if (socket && socket.readyState === WebSocket.OPEN) {
            return;
        }

        setStatus('Verbinde...', 'text-yellow-200');
        terminal.clear();
        terminal.writeln('Terminal-Token wird angefordert...');

        try {
            const response = await fetch(root.dataset.terminalTokenUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': root.dataset.csrfToken,
                },
            });

            if (! response.ok) {
                throw new Error(`Token konnte nicht erstellt werden (${response.status})`);
            }

            const data = await response.json();
            socket = new WebSocket(data.url);

            socket.addEventListener('open', () => {
                setStatus('Verbunden', 'text-green-200');
                connectButton?.classList.add('hidden');
                disconnectButton?.classList.remove('hidden');
                terminal.focus();
                fitAddon.fit();
                sendResize();
            });

            socket.addEventListener('message', (event) => {
                const message = JSON.parse(event.data);

                if (message.type === 'output') {
                    terminal.write(message.data || '');
                }

                if (message.type === 'error') {
                    terminal.writeln(`\r\nFehler: ${message.data || 'Unbekannter Fehler'}`);
                }
            });

            socket.addEventListener('close', () => {
                setStatus('Getrennt', 'text-red-200');
                connectButton?.classList.remove('hidden');
                disconnectButton?.classList.add('hidden');
                terminal.writeln('\r\n[Verbindung getrennt]');
                socket = null;
            });

            socket.addEventListener('error', () => {
                setStatus('Fehler', 'text-red-200');
                terminal.writeln('\r\n[WebSocket-Fehler]');
            });
        } catch (error) {
            setStatus('Fehler', 'text-red-200');
            terminal.writeln(`\r\n${error.message}`);
        }
    }

    function disconnect() {
        if (socket?.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({ type: 'close' }));
            socket.close();
        }
    }

    function sendResize() {
        if (socket?.readyState !== WebSocket.OPEN) {
            return;
        }

        socket.send(JSON.stringify({
            type: 'resize',
            cols: terminal.cols,
            rows: terminal.rows,
        }));
    }

    function setStatus(text, className) {
        if (! status) {
            return;
        }

        status.textContent = text;
        status.className = `rounded-full border border-white/15 px-3 py-1 ${className}`;
    }
}
