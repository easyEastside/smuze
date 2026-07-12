import 'dotenv/config';

import crypto from 'node:crypto';
import fs from 'node:fs';
import { mkdir, rm, writeFile } from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import process from 'node:process';
import pty from 'node-pty';
import { WebSocketServer } from 'ws';

const port = Number.parseInt(process.env.TERMINAL_WS_PORT || '8081', 10);
const appUrl = (process.env.APP_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const terminalSecret = process.env.TERMINAL_SHARED_SECRET || process.env.APP_KEY || '';

if (! terminalSecret) {
    throw new Error('TERMINAL_SHARED_SECRET or APP_KEY must be configured.');
}

const wss = new WebSocketServer({ port });

function send(ws, payload) {
    if (ws.readyState === ws.OPEN) {
        ws.send(JSON.stringify(payload));
    }
}

async function resolveSession(token) {
    const response = await fetch(`${appUrl}/internal/terminal/sessions/${encodeURIComponent(token)}`, {
        headers: {
            Accept: 'application/json',
            'X-Terminal-Secret': terminalSecret,
        },
    });

    if (! response.ok) {
        throw new Error(`Terminal session could not be resolved (${response.status}).`);
    }

    return response.json();
}

async function writeKeyFile(keyContent) {
    const dir = path.join(os.tmpdir(), 'smuze-terminal-keys');
    await mkdir(dir, { recursive: true, mode: 0o700 });

    const file = path.join(dir, `${crypto.randomUUID()}.key`);
    await writeFile(file, keyContent, { mode: 0o600 });
    await fs.promises.chmod(file, 0o600);

    return file;
}

function buildSshArgs(server, keyFile, options = {}) {
    const args = [
        '-o', 'StrictHostKeyChecking=no',
        '-o', 'UserKnownHostsFile=/dev/null',
        '-o', 'LogLevel=ERROR',
        '-o', 'ControlMaster=auto',
        '-o', `ControlPath=${server.control_path}`,
        '-o', 'ControlPersist=10m',
        '-p', String(server.port),
    ];

    if (options.tty !== false) {
        args.unshift('-tt');
    }

    if (server.auth_type === 'key' && keyFile) {
        args.push('-i', keyFile, '-o', 'IdentitiesOnly=yes');
    }

    if (server.auth_type === 'password') {
        args.push('-o', 'PreferredAuthentications=password,keyboard-interactive', '-o', 'PubkeyAuthentication=no');
    }

    args.push(`${server.username}@${server.host}`);

    return args;
}

function parseKeyValueMetrics(raw) {
    const metrics = {};

    for (const line of raw.split('\n')) {
        const trimmed = line.trim();
        const separator = trimmed.indexOf('=');

        if (separator === -1) {
            continue;
        }

        const key = trimmed.slice(0, separator).toLowerCase();
        const value = trimmed.slice(separator + 1);

        if ([
            'cpu_percent',
            'ram_total_mb',
            'ram_used_mb',
            'ram_percent',
            'disk_total_mb',
            'disk_used_mb',
            'disk_percent',
        ].includes(key)) {
            metrics[key] = Number.parseInt(value, 10);
        } else {
            metrics[key] = value || null;
        }
    }

    return metrics;
}

function shellQuote(value) {
    return `'${String(value).replaceAll("'", "'\\''")}'`;
}

function metricsScript() {
    return `while true; do
echo __SMUZE_METRICS_BEGIN__
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
printf 'HOSTNAME=%s\\n' "$(hostname)"
OS_VALUE=$(lsb_release -ds 2>/dev/null || awk -F= '/^PRETTY_NAME=/ {gsub(/"/, "", $2); print $2}' /etc/os-release 2>/dev/null)
printf 'OS=%s\\n' "$OS_VALUE"
printf 'UPTIME=%s\\n' "$(uptime -p 2>/dev/null || uptime)"
awk '{printf "LOAD=%s\\n", $1}' /proc/loadavg
read CPU_PREV CPU_IDLE_PREV <<EOF
$(awk '/^cpu / {total=0; for (i=2; i<=NF; i++) total+=$i; print total, $5}' /proc/stat)
EOF
sleep 0.2
read CPU_CURR CPU_IDLE_CURR <<EOF
$(awk '/^cpu / {total=0; for (i=2; i<=NF; i++) total+=$i; print total, $5}' /proc/stat)
EOF
TOTAL_DELTA=$((CPU_CURR - CPU_PREV))
IDLE_DELTA=$((CPU_IDLE_CURR - CPU_IDLE_PREV))
if [ "$TOTAL_DELTA" -gt 0 ] 2>/dev/null; then CPU_PCT=$((100 * (TOTAL_DELTA - IDLE_DELTA) / TOTAL_DELTA)); else CPU_PCT=0; fi
printf 'CPU_PERCENT=%s\\n' "$CPU_PCT"
free -m | awk '/^Mem:/ {printf "RAM_TOTAL_MB=%s\\nRAM_USED_MB=%s\\nRAM_PERCENT=%.0f\\n", $2, $3, ($3/$2)*100}'
df -Pm / | awk 'NR==2 {printf "DISK_TOTAL_MB=%s\\nDISK_USED_MB=%s\\nDISK_PERCENT=%s\\n", $2, $3, $5}' | tr -d '%'
printf 'PHP_VERSION=%s\\n' "$(php -v 2>/dev/null | head -1)"
printf 'APACHE_VERSION=%s\\n' "$(apache2 -v 2>/dev/null | sed -n '1p')"
printf 'MYSQL_VERSION=%s\\n' "$(mysql --version 2>/dev/null)"
printf 'NODE_VERSION=%s\\n' "$(node --version 2>/dev/null)"
printf 'NVM_VERSION=%s\\n' "$(bash -c 'export NVM_DIR="$HOME/.nvm"; [ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh" && nvm --version' 2>/dev/null)"
printf 'COMPOSER_VERSION=%s\\n' "$(composer --version 2>/dev/null | head -1)"
echo __SMUZE_METRICS_END__
sleep 2
done`;
}

function handleTerminalConnection(ws, server, cols, rows, keyFile) {
    let passwordWritten = false;
    const terminal = pty.spawn('ssh', buildSshArgs(server, keyFile || server.key_path), {
        name: 'xterm-256color',
        cols: Number.isFinite(cols) ? cols : 120,
        rows: Number.isFinite(rows) ? rows : 34,
        cwd: os.homedir(),
        env: {
            ...process.env,
            TERM: 'xterm-256color',
        },
    });

    terminal.onData((data) => {
        if (server.password && ! passwordWritten && /password:/i.test(data)) {
            passwordWritten = true;
            terminal.write(`${server.password}\r`);
        }

        send(ws, { channel: 'terminal', type: 'output', data });
    });

    terminal.onExit(({ exitCode, signal }) => {
        send(ws, { channel: 'terminal', type: 'exit', exit_code: exitCode, signal });
    });

    return {
        write(data) {
            terminal.write(data);
        },
        resize(cols, rows) {
            terminal.resize(cols, rows);
        },
        kill() {
            terminal.kill();
        },
    };
}

function handleMetricsConnection(ws, server, keyFile) {
    const sshArgs = [...buildSshArgs(server, keyFile || server.key_path, { tty: false }), 'sh', '-lc', shellQuote(metricsScript())];
    let terminal = null;
    let retryTimer = null;
    let lastOutput = '';

    const start = () => {
        let passwordWritten = false;
        let buffer = '';

        terminal = pty.spawn('ssh', sshArgs, {
            name: 'xterm-256color',
            cols: 120,
            rows: 40,
            cwd: os.homedir(),
            env: {
                ...process.env,
                TERM: 'xterm-256color',
            },
        });

        terminal.onData((data) => {
            if (server.password && ! passwordWritten && /password:/i.test(data)) {
                passwordWritten = true;
                terminal.write(`${server.password}\r`);

                return;
            }

            buffer += data.replace(/\r/g, '');
            lastOutput = buffer.slice(-500);

            while (buffer.includes('__SMUZE_METRICS_BEGIN__') && buffer.includes('__SMUZE_METRICS_END__')) {
                const begin = buffer.indexOf('__SMUZE_METRICS_BEGIN__') + '__SMUZE_METRICS_BEGIN__'.length;
                const end = buffer.indexOf('__SMUZE_METRICS_END__');
                const rawMetrics = buffer.slice(begin, end);
                buffer = buffer.slice(end + '__SMUZE_METRICS_END__'.length);
                lastOutput = '';
                send(ws, { channel: 'metrics', type: 'metrics', data: parseKeyValueMetrics(rawMetrics), collected_at: new Date().toISOString() });
            }
        });

        terminal.onExit(({ exitCode, signal }) => {
            terminal = null;
            send(ws, { channel: 'metrics', type: 'metrics_error', exit_code: exitCode, signal, message: lastOutput.trim() || null });

            if (ws.readyState === ws.OPEN) {
                retryTimer = setTimeout(start, 5000);
            }
        });
    };

    start();

    return {
        kill() {
            if (retryTimer) {
                clearTimeout(retryTimer);
                retryTimer = null;
            }

            if (terminal) {
                terminal.kill();
                terminal = null;
            }
        },
    };
}

async function proxyModuleRequest(ws, serverId, module, action, requestId, params) {
    try {
        const url = new URL(`${appUrl}/internal/servers/${serverId}/proxy/${module}/${action}`);
        url.searchParams.set('params', JSON.stringify(params || {}));

        const response = await fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Terminal-Secret': terminalSecret,
            },
        });

        if (! response.ok) {
            send(ws, { channel: module, action, requestId, error: `Proxy request failed (${response.status}).` });
            return;
        }

        const data = await response.json();
        send(ws, { channel: module, action, requestId, data });
    } catch (err) {
        send(ws, { channel: module, action, requestId, error: err.message });
    }
}

wss.on('connection', async (ws, request) => {
    let metricsConnection = null;
    let terminalConnection = null;
    let keyFile = null;
    let server = null;
    let pendingMessages = [];

    const cleanup = async () => {
        if (metricsConnection) {
            metricsConnection.kill();
            metricsConnection = null;
        }

        if (terminalConnection) {
            terminalConnection.kill();
            terminalConnection = null;
        }

        if (keyFile) {
            await rm(keyFile, { force: true });
            keyFile = null;
        }
    };

    const handleMessage = (payload) => {
        if (payload.channel === 'heartbeat' && payload.type === 'ping') {
            send(ws, { channel: 'heartbeat', type: 'pong' });
            return;
        }

        if (payload.channel === 'metrics' && payload.type === 'subscribe' && ! metricsConnection && server) {
            metricsConnection = handleMetricsConnection(ws, server, keyFile);
        }

        if (payload.channel === 'metrics' && payload.type === 'unsubscribe' && metricsConnection) {
            metricsConnection.kill();
            metricsConnection = null;
        }

        if (payload.channel === 'terminal' && payload.type === 'open' && server) {
            if (terminalConnection) {
                terminalConnection.kill();
            }

            const cols = Number.parseInt(payload.cols || 120, 10);
            const rows = Number.parseInt(payload.rows || 34, 10);
            terminalConnection = handleTerminalConnection(ws, server, cols, rows, keyFile);
        }

        if (payload.channel === 'terminal' && payload.type === 'input' && terminalConnection && typeof payload.data === 'string') {
            terminalConnection.write(payload.data);
        }

        if (payload.requestId && ['system', 'apache', 'mysql', 'firewall', 'services', 'github'].includes(payload.channel) && payload.action && server) {
            proxyModuleRequest(ws, server.id, payload.channel, payload.action, payload.requestId, payload.params);
        }

        if (payload.channel === 'terminal' && payload.type === 'resize' && terminalConnection) {
            const cols = Number.parseInt(payload.cols, 10);
            const rows = Number.parseInt(payload.rows, 10);

            if (Number.isFinite(cols) && Number.isFinite(rows) && cols > 0 && rows > 0) {
                terminalConnection.resize(cols, rows);
            }
        }
    };

    try {
        const url = new URL(request.url || '/', `ws://${request.headers.host}`);
        const token = url.searchParams.get('token');

        if (! token) {
            throw new Error('Missing terminal token.');
        }

        // Register message handler immediately (before async session resolve)
        ws.on('message', (message) => {
            let payload;

            try {
                payload = JSON.parse(message.toString());
            } catch {
                return;
            }

            if (! server) {
                pendingMessages.push(payload);
                return;
            }

            handleMessage(payload);
        });

        ws.on('close', cleanup);
        ws.on('error', cleanup);

        // Resolve session (async)
        const { server: resolvedServer } = await resolveSession(token);
        server = resolvedServer;

        if (server.auth_type === 'key' && server.key_content) {
            keyFile = await writeKeyFile(server.key_content);
        }

        // Process buffered messages (e.g., terminal open, metrics subscribe)
        const buffered = pendingMessages;
        pendingMessages = [];
        for (const payload of buffered) {
            handleMessage(payload);
        }
    } catch (error) {
        send(ws, { type: 'error', message: error.message });
        await cleanup();
        ws.close();
    }
});

process.on('SIGINT', () => process.exit(0));
process.on('SIGTERM', () => process.exit(0));

console.log(`Terminal WebSocket server listening on ws://127.0.0.1:${port}`);
