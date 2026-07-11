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

function buildSshArgs(server, keyFile) {
    const args = [
        '-tt',
        '-o', 'StrictHostKeyChecking=no',
        '-o', 'UserKnownHostsFile=/dev/null',
        '-o', 'LogLevel=ERROR',
        '-o', 'ControlMaster=auto',
        '-o', `ControlPath=${server.control_path}`,
        '-o', 'ControlPersist=10m',
        '-p', String(server.port),
    ];

    if (server.auth_type === 'key' && keyFile) {
        args.push('-i', keyFile, '-o', 'IdentitiesOnly=yes');
    }

    if (server.auth_type === 'password') {
        args.push('-o', 'PreferredAuthentications=password,keyboard-interactive', '-o', 'PubkeyAuthentication=no');
    }

    args.push(`${server.username}@${server.host}`);

    return args;
}

wss.on('connection', async (ws, request) => {
    let terminal = null;
    let keyFile = null;
    let passwordWritten = false;

    const cleanup = async () => {
        if (terminal) {
            terminal.kill();
            terminal = null;
        }

        if (keyFile) {
            await rm(keyFile, { force: true });
            keyFile = null;
        }
    };

    try {
        const url = new URL(request.url || '/', `ws://${request.headers.host}`);
        const token = url.searchParams.get('token');
        const cols = Number.parseInt(url.searchParams.get('cols') || '120', 10);
        const rows = Number.parseInt(url.searchParams.get('rows') || '34', 10);

        if (! token) {
            throw new Error('Missing terminal token.');
        }

        const { server } = await resolveSession(token);

        if (server.auth_type === 'key' && server.key_content) {
            keyFile = await writeKeyFile(server.key_content);
        }

        const sshArgs = buildSshArgs(server, keyFile || server.key_path);

        terminal = pty.spawn('ssh', sshArgs, {
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

            send(ws, { type: 'output', data });
        });

        terminal.onExit(({ exitCode, signal }) => {
            send(ws, { type: 'exit', exit_code: exitCode, signal });
            ws.close();
        });

        ws.on('message', (message) => {
            if (! terminal) {
                return;
            }

            let payload;

            try {
                payload = JSON.parse(message.toString());
            } catch {
                return;
            }

            if (payload.type === 'input' && typeof payload.data === 'string') {
                terminal.write(payload.data);
            }

            if (payload.type === 'resize') {
                const nextCols = Number.parseInt(payload.cols, 10);
                const nextRows = Number.parseInt(payload.rows, 10);

                if (Number.isFinite(nextCols) && Number.isFinite(nextRows) && nextCols > 0 && nextRows > 0) {
                    terminal.resize(nextCols, nextRows);
                }
            }
        });

        ws.on('close', cleanup);
        ws.on('error', cleanup);
    } catch (error) {
        send(ws, { type: 'error', message: error.message });
        await cleanup();
        ws.close();
    }
});

process.on('SIGINT', () => process.exit(0));
process.on('SIGTERM', () => process.exit(0));

console.log(`Terminal WebSocket server listening on ws://127.0.0.1:${port}`);
