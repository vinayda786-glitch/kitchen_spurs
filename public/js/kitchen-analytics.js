/**
 * KitchenAnalytics - Lightweight, Resilient Browser Analytics SDK
 * 
 * Features:
 * - Zero external dependencies (< 4KB minified)
 * - Persistent anonymous identity & 30-minute sliding window session tracking
 * - Event queueing & batch delivery (reduces HTTP request overhead)
 * - Beaconing on page unload (navigator.sendBeacon & fetch keepalive)
 * - Offline / failure retry buffer
 * - Fail-safe execution (never breaks host application)
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.KitchenAnalytics = factory();
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    // Storage Keys
    var STORAGE_ANON_ID = 'ka_anon_id';
    var STORAGE_USER_ID = 'ka_user_id';
    var STORAGE_USER_TRAITS = 'ka_user_traits';
    var STORAGE_SESSION_ID = 'ka_session_id';
    var STORAGE_SESSION_LAST_ACTIVE = 'ka_session_last_active';
    var STORAGE_FAILED_QUEUE = 'ka_failed_queue';

    var SESSION_TIMEOUT_MS = 30 * 60 * 1000; // 30 minutes sliding window

    // Helper: UUID v4 generator
    function generateUuid() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    // Helper: Cookie access
    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }

    // Safe LocalStorage wrapper
    var SafeStorage = {
        get: function (key) {
            try {
                return localStorage.getItem(key);
            } catch (e) {
                return getCookie(key);
            }
        },
        set: function (key, value) {
            try {
                localStorage.setItem(key, value);
            } catch (e) {
                setCookie(key, value, 365);
            }
        },
        remove: function (key) {
            try {
                localStorage.removeItem(key);
            } catch (e) {}
            setCookie(key, '', -1);
        }
    };

    // Safe SessionStorage wrapper
    var SafeSession = {
        get: function (key) {
            try {
                return sessionStorage.getItem(key);
            } catch (e) {
                return getCookie(key);
            }
        },
        set: function (key, value) {
            try {
                sessionStorage.setItem(key, value);
            } catch (e) {
                setCookie(key, value, 1);
            }
        },
        remove: function (key) {
            try {
                sessionStorage.removeItem(key);
            } catch (e) {}
            setCookie(key, '', -1);
        }
    };

    // SDK Core Constructor
    function AnalyticsSDK() {
        this.config = {
            endpoint: '/api/v1/analytics/events',
            batchSize: 5,
            flushInterval: 2000,
            debug: false,
            autoPageview: true,
        };

        this.queue = [];
        this.timer = null;
        this.anonymousId = null;
        this.userId = null;
        this.sessionId = null;
        this.initialized = false;
    }

    // Initialize SDK
    AnalyticsSDK.prototype.init = function (options) {
        try {
            if (options) {
                for (var key in options) {
                    if (options.hasOwnProperty(key)) {
                        this.config[key] = options[key];
                    }
                }
            }

            this.anonymousId = this._getOrCreateAnonymousId();
            this.userId = SafeStorage.get(STORAGE_USER_ID) || null;
            this.sessionId = this._getOrCreateSessionId();

            // Set cookie sync for server tracking awareness
            setCookie('ka_aid', this.anonymousId, 365);
            setCookie('ka_sid', this.sessionId, 1);

            // Re-queue any previously failed events from offline storage
            this._recoverFailedQueue();

            // Set up automatic interval flusher
            this._startFlusher();

            // Set up page exit beacon listeners
            this._bindUnloadListeners();

            this.initialized = true;
            this._log('KitchenAnalytics initialized', {
                anonymousId: this.anonymousId,
                sessionId: this.sessionId,
                userId: this.userId
            });

            if (this.config.autoPageview) {
                this.page();
            }
        } catch (err) {
            this._logError('Initialization error', err);
        }
    };

    // Track a Page View
    AnalyticsSDK.prototype.page = function (name, properties) {
        try {
            var props = properties || {};
            props.title = props.title || document.title;
            props.path = props.path || window.location.pathname;
            props.url = props.url || window.location.href;
            if (name) props.name = name;

            this.track('page_view', props);
        } catch (err) {
            this._logError('page() error', err);
        }
    };

    // Track a Custom Business or UI Event
    AnalyticsSDK.prototype.track = function (eventName, properties) {
        try {
            if (!this.initialized) {
                this.init();
            }

            if (!eventName || typeof eventName !== 'string') {
                this._logError('Invalid eventName provided to track()', eventName);
                return;
            }

            // Refresh session sliding window
            this.sessionId = this._getOrCreateSessionId();
            setCookie('ka_sid', this.sessionId, 1);

            var event = {
                id: generateUuid(),
                event_name: eventName,
                event_source: 'browser',
                anonymous_id: this.anonymousId,
                user_id: this.userId,
                session_id: this.sessionId,
                url: window.location.href,
                path: window.location.pathname,
                referrer: document.referrer || null,
                occurred_at: new Date().toISOString(),
                properties: properties || {},
                context: this._gatherContext(),
            };

            this.queue.push(event);
            this._log('Event queued:', eventName, event);

            if (this.queue.length >= this.config.batchSize) {
                this.flush();
            }
        } catch (err) {
            this._logError('track() error', err);
        }
    };

    // Identify User
    AnalyticsSDK.prototype.identify = function (userId, traits) {
        try {
            if (!userId) return;
            this.userId = String(userId);
            SafeStorage.set(STORAGE_USER_ID, this.userId);
            if (traits) {
                SafeStorage.set(STORAGE_USER_TRAITS, JSON.stringify(traits));
            }

            this.track('user_identified', {
                user_id: this.userId,
                traits: traits || {}
            });
            this._log('User identified:', this.userId);
        } catch (err) {
            this._logError('identify() error', err);
        }
    };

    // Reset Identity (e.g. on logout)
    AnalyticsSDK.prototype.reset = function () {
        try {
            this.userId = null;
            SafeStorage.remove(STORAGE_USER_ID);
            SafeStorage.remove(STORAGE_USER_TRAITS);
            this.anonymousId = generateUuid();
            SafeStorage.set(STORAGE_ANON_ID, this.anonymousId);
            setCookie('ka_aid', this.anonymousId, 365);
            this._log('Identity reset, new anonymousId:', this.anonymousId);
        } catch (err) {
            this._logError('reset() error', err);
        }
    };

    // Flush Queued Events to Server
    AnalyticsSDK.prototype.flush = function (useBeacon) {
        var self = this;
        if (this.queue.length === 0) {
            return Promise.resolve();
        }

        var batchToSend = this.queue.slice();
        this.queue = [];

        var payload = JSON.stringify({ batch: batchToSend });

        // If page is unloading and sendBeacon is available, use it for 100% guarantee
        if (useBeacon && typeof navigator !== 'undefined' && navigator.sendBeacon) {
            try {
                var blob = new Blob([payload], { type: 'application/json' });
                var success = navigator.sendBeacon(this.config.endpoint, blob);
                if (success) {
                    this._log('Batch flushed via navigator.sendBeacon', batchToSend.length);
                    return Promise.resolve();
                }
            } catch (e) {
                this._log('sendBeacon failed, falling back to fetch', e);
            }
        }

        // Standard fetch with keepalive support
        return fetch(this.config.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: payload,
            keepalive: true,
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            self._log('Batch successfully sent (' + batchToSend.length + ' events)');
        }).catch(function (error) {
            self._logError('Failed to send batch, buffering to retry', error);
            self._bufferFailedEvents(batchToSend);
        });
    };

    // Gather Environment Context
    AnalyticsSDK.prototype._gatherContext = function () {
        return {
            screen_width: window.screen ? window.screen.width : null,
            screen_height: window.screen ? window.screen.height : null,
            viewport_width: window.innerWidth || null,
            viewport_height: window.innerHeight || null,
            language: navigator.language || navigator.userLanguage || null,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || null,
            device_pixel_ratio: window.devicePixelRatio || 1,
            user_agent: navigator.userAgent || null,
        };
    };

    // Anonymous ID Generator & Retriever
    AnalyticsSDK.prototype._getOrCreateAnonymousId = function () {
        var aid = SafeStorage.get(STORAGE_ANON_ID);
        if (!aid) {
            aid = generateUuid();
            SafeStorage.set(STORAGE_ANON_ID, aid);
        }
        return aid;
    };

    // Session Management with 30-min Sliding Inactivity Window
    AnalyticsSDK.prototype._getOrCreateSessionId = function () {
        var now = Date.now();
        var sid = SafeSession.get(STORAGE_SESSION_ID);
        var lastActive = parseInt(SafeSession.get(STORAGE_SESSION_LAST_ACTIVE) || '0', 10);

        if (!sid || !lastActive || (now - lastActive > SESSION_TIMEOUT_MS)) {
            sid = generateUuid();
            SafeSession.set(STORAGE_SESSION_ID, sid);
        }

        SafeSession.set(STORAGE_SESSION_LAST_ACTIVE, String(now));
        return sid;
    };

    // Buffer failed events for subsequent retry
    AnalyticsSDK.prototype._bufferFailedEvents = function (events) {
        try {
            var existing = [];
            var stored = SafeStorage.get(STORAGE_FAILED_QUEUE);
            if (stored) {
                existing = JSON.parse(stored) || [];
            }
            // Retain up to 50 events max to avoid storage overflow
            var combined = existing.concat(events).slice(-50);
            SafeStorage.set(STORAGE_FAILED_QUEUE, JSON.stringify(combined));
        } catch (e) {}
    };

    // Recover and send previously failed events
    AnalyticsSDK.prototype._recoverFailedQueue = function () {
        try {
            var stored = SafeStorage.get(STORAGE_FAILED_QUEUE);
            if (stored) {
                var failed = JSON.parse(stored);
                if (Array.isArray(failed) && failed.length > 0) {
                    SafeStorage.remove(STORAGE_FAILED_QUEUE);
                    this.queue = failed.concat(this.queue);
                    this._log('Recovered ' + failed.length + ' events from offline buffer');
                }
            }
        } catch (e) {}
    };

    // Background Periodic Flusher
    AnalyticsSDK.prototype._startFlusher = function () {
        var self = this;
        if (this.timer) {
            clearInterval(this.timer);
        }
        this.timer = setInterval(function () {
            if (self.queue.length > 0) {
                self.flush();
            }
        }, this.config.flushInterval);
    };

    // Listeners for Page Exit (Tab close, Refresh, Navigation)
    AnalyticsSDK.prototype._bindUnloadListeners = function () {
        var self = this;
        var onExit = function () {
            if (self.queue.length > 0) {
                self.flush(true);
            }
        };

        if (document.addEventListener) {
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'hidden') {
                    onExit();
                }
            });
            window.addEventListener('pagehide', onExit);
            window.addEventListener('beforeunload', onExit);
        }
    };

    // Debug Logger
    AnalyticsSDK.prototype._log = function () {
        if (this.config.debug && typeof console !== 'undefined' && console.log) {
            var args = Array.prototype.slice.call(arguments);
            args.unshift('[KitchenAnalytics]');
            console.log.apply(console, args);
        }
    };

    AnalyticsSDK.prototype._logError = function () {
        if (this.config.debug && typeof console !== 'undefined' && console.error) {
            var args = Array.prototype.slice.call(arguments);
            args.unshift('[KitchenAnalytics ERROR]');
            console.error.apply(console, args);
        }
    };

    // Return a singleton instance
    var instance = new AnalyticsSDK();
    return instance;
}));

