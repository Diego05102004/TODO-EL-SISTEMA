(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }
    root.CasalaiLongPoll = api;
})(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    class Client {
        constructor(options) {
            if (!options || typeof options.endpoint !== 'string') {
                throw new TypeError('Se requiere endpoint para HTTP long polling.');
            }

            this.endpoint = options.endpoint;
            this.credentials = options.credentials || 'same-origin';
            this.headers = options.headers || {};
            this.fetch = options.fetch || globalThis.fetch.bind(globalThis);
            this.onMessage = options.onMessage || function () {};
            this.onError = options.onError || function () {};
            this.timeoutMs = options.timeoutMs || 25000;
            this.minRetryMs = options.minRetryMs || 1000;
            this.maxRetryMs = options.maxRetryMs || 30000;
            this.cursor = 0;
            this.initialRequest = true;
            this.csrfToken = '';
            this.running = false;
            this.controllers = new Set();
            this.loopPromise = null;
            this.failures = 0;
            this.pendingActions = new Map();
            this.generation = 0;
        }

        start() {
            if (this.running) {
                return this.loopPromise;
            }
            this.running = true;
            this.generation += 1;
            this.loopPromise = this.pollLoop(this.generation);
            return this.loopPromise;
        }

        stop() {
            this.running = false;
            this.generation += 1;
            this.controllers.forEach((controller) => controller.abort());
        }

        restart() {
            this.stop();
            this.cursor = 0;
            this.initialRequest = true;
            return this.start();
        }

        async pollLoop(generation) {
            while (this.running && generation === this.generation) {
                try {
                    const data = await this.request('notifications', {
                        after_id: String(this.cursor),
                        initial: this.initialRequest ? '1' : '0'
                    });
                    if (!this.running || generation !== this.generation) {
                        break;
                    }
                    this.failures = 0;
                    this.initialRequest = false;
                    if (Number.isSafeInteger(Number(data.cursor)) && Number(data.cursor) >= 0) {
                        this.cursor = Number(data.cursor);
                    }
                    this.onMessage(data);
                } catch (error) {
                    if (!this.running || generation !== this.generation) {
                        break;
                    }
                    this.failures += 1;
                    this.onError(error);
                    await this.delay(this.retryDelay());
                }
            }
        }

        retryDelay() {
            const exponential = Math.min(
                this.minRetryMs * Math.pow(2, Math.min(this.failures - 1, 10)),
                this.maxRetryMs
            );
            return Math.round(exponential * (0.8 + Math.random() * 0.4));
        }

        delay(milliseconds) {
            return new Promise(function (resolve) {
                setTimeout(resolve, milliseconds);
            });
        }

        async request(action, parameters, method, body) {
            const url = new URL(this.endpoint, this.getBaseUrl());
            url.searchParams.set('action', action);
            Object.keys(parameters || {}).forEach(function (key) {
                url.searchParams.set(key, parameters[key]);
            });

            const controller = new AbortController();
            this.controllers.add(controller);
            const timeout = setTimeout(() => controller.abort(), this.timeoutMs);
            const headers = Object.assign({ Accept: 'application/json' }, this.headers);
            if (method === 'POST') {
                headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
                headers['X-CSRF-Token'] = this.csrfToken;
            }

            try {
                const response = await this.fetch(url.toString(), {
                    method: method || 'GET',
                    credentials: this.credentials,
                    cache: 'no-store',
                    headers: headers,
                    body: body,
                    signal: controller.signal
                });
                const data = await response.json();
                if (!response.ok || data.success === false) {
                    const error = new Error(data.error || 'Solicitud HTTP fallida.');
                    error.status = response.status;
                    throw error;
                }
                if (typeof data.csrf_token === 'string') {
                    this.csrfToken = data.csrf_token;
                }
                return data;
            } finally {
                clearTimeout(timeout);
                this.controllers.delete(controller);
            }
        }

        getBaseUrl() {
            if (typeof location !== 'undefined' && location.href) {
                return location.href;
            }
            return 'http://localhost/';
        }

        action(action, parameters) {
            if (this.pendingActions.has(action)) {
                return this.pendingActions.get(action);
            }

            const request = this.request(action, parameters || {}).then((data) => {
                this.onMessage(data);
                return data;
            }).catch((error) => {
                this.onError(error);
                throw error;
            }).finally(() => {
                this.pendingActions.delete(action);
            });
            this.pendingActions.set(action, request);
            return request;
        }

        markAsRead(notificationId) {
            if (!this.csrfToken) {
                return Promise.reject(new Error('El token CSRF aún no está disponible.'));
            }
            const body = new URLSearchParams({
                id_notificacion: String(notificationId),
                csrf_token: this.csrfToken
            }).toString();
            return this.request('mark_read', {}, 'POST', body).then((data) => {
                this.onMessage(data);
                return data;
            }).catch((error) => {
                this.onError(error);
                throw error;
            });
        }

        getCartCount() {
            return this.action('cart_count');
        }

        getExchangeRate() {
            return this.action('exchange_rate');
        }
    }

    return { Client: Client };
});
