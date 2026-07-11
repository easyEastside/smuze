class ServerSocket {
    constructor() {
        this.socket = null;
        this.token = null;
        this.websocketUrl = null;
        this.serverId = null;
        this.pending = new Map();
        this.requestId = 0;
        this.statusListeners = [];
        this.messageListeners = [];
        this.reconnectTimer = null;
        this.reconnectDelay = 1000;
        this.heartbeatTimer = null;
        this.lastPong = 0;
        this._csrfToken = null;
        this._sessionEndpoint = null;
    }

    async connect(serverId, sessionEndpoint, csrfToken) {
        if (this.serverId === serverId && this.isConnected) {
            return;
        }

        if (this.isConnected) {
            this.disconnect();
        }

        this.serverId = serverId;
        this._sessionEndpoint = sessionEndpoint;
        this._csrfToken = csrfToken;

        try {
            const response = await fetch(sessionEndpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('WebSocket-Session konnte nicht erstellt werden.');
            }

            const data = await response.json();
            this.token = data.token;
            this.websocketUrl = data.websocket_url;

            this._open();
        } catch (err) {
            this._notifyStatus('error', err.message);
        }
    }

    _open() {
        if (this.socket) {
            this.socket.close();
        }

        const url = new URL(this.websocketUrl);
        url.searchParams.set('token', this.token);

        this.socket = new WebSocket(url.toString());

        this.socket.addEventListener('open', () => {
            this._notifyStatus('connected');
            this.reconnectDelay = 1000;
            this._startHeartbeat();
        });

        this.socket.addEventListener('message', (event) => {
            try {
                const payload = JSON.parse(event.data);
                this._handleMessage(payload);
            } catch {
                //
            }
        });

        this.socket.addEventListener('close', () => {
            this._stopHeartbeat();
            this._notifyStatus('disconnected');
            this._scheduleReconnect();
        });

        this.socket.addEventListener('error', () => {
            this._notifyStatus('error');
        });
    }

    _handleMessage(payload) {
        if (payload.channel === 'heartbeat' && payload.type === 'pong') {
            this.lastPong = Date.now();
            return;
        }

        if (payload.requestId && this.pending.has(payload.requestId)) {
            const { resolve, reject } = this.pending.get(payload.requestId);
            this.pending.delete(payload.requestId);

            if (payload.error) {
                reject(new Error(payload.error));
            } else {
                resolve(payload);
            }

            return;
        }

        for (const cb of this.messageListeners) {
            cb(payload);
        }
    }

    _startHeartbeat() {
        this._stopHeartbeat();
        this.lastPong = Date.now();

        this.heartbeatTimer = setInterval(() => {
            if (!this.socket || this.socket.readyState !== WebSocket.OPEN) {
                this._stopHeartbeat();
                return;
            }

            if (Date.now() - this.lastPong > 40000) {
                this.socket.close();
                return;
            }

            this.socket.send(JSON.stringify({ channel: 'heartbeat', type: 'ping' }));
        }, 30000);
    }

    _stopHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
    }

    _scheduleReconnect() {
        if (this.reconnectTimer) {
            return;
        }

        this.reconnectTimer = setTimeout(() => {
            this.reconnectTimer = null;
            this._open();
        }, this.reconnectDelay);

        this.reconnectDelay = Math.min(this.reconnectDelay * 2, 30000);
    }

    onMessage(callback) {
        this.messageListeners.push(callback);

        return () => {
            const i = this.messageListeners.indexOf(callback);
            if (i !== -1) {
                this.messageListeners.splice(i, 1);
            }
        };
    }

    onStatus(callback) {
        this.statusListeners.push(callback);

        return () => {
            const i = this.statusListeners.indexOf(callback);
            if (i !== -1) {
                this.statusListeners.splice(i, 1);
            }
        };
    }

    _notifyStatus(status, message) {
        for (const cb of this.statusListeners) {
            cb(status, message);
        }
    }

    send(channel, type, payload = {}) {
        if (!this.socket || this.socket.readyState !== WebSocket.OPEN) {
            throw new Error('Socket nicht verbunden.');
        }

        this.socket.send(JSON.stringify({ channel, type, ...payload }));
    }

    request(channel, type, payload = {}) {
        return new Promise((resolve, reject) => {
            const id = ++this.requestId;

            const timeout = setTimeout(() => {
                this.pending.delete(id);
                reject(new Error('WebSocket-Request timeout'));
            }, 30000);

            this.pending.set(id, {
                resolve: (data) => {
                    clearTimeout(timeout);
                    resolve(data);
                },
                reject: (err) => {
                    clearTimeout(timeout);
                    reject(err);
                },
            });

            this.socket.send(JSON.stringify({ channel, type, requestId: id, ...payload }));
        });
    }

    disconnect() {
        this._stopHeartbeat();

        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }

        if (this.socket) {
            this.socket.close();
            this.socket = null;
        }
    }

    get isConnected() {
        return this.socket && this.socket.readyState === WebSocket.OPEN;
    }
}

window.SmuzeServerSocket = new ServerSocket();
