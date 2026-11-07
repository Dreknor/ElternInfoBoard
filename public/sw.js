/*
 | ElternInfoBoard Service Worker
 |
 | Vereint Web-Push-Benachrichtigungen und ein robustes Offline-Caching
 | (App-Shell + Runtime-Caching). Ersetzt die frühere getrennte
 | serviceworker.js (Offline) / sw.js (Push) Aufteilung.
 */

const CACHE_VERSION = 'eib-v2';
// v3: neues Standard-Logo, alte Icons sollen nicht aus dem Cache kommen
// v2: v1 enthielt fälschlich gecachte JSON-Antworten und wird beim Aktivieren gelöscht
const RUNTIME_CACHE = 'eib-runtime-v3';

// Request-Ziele, die per Stale-while-revalidate gecacht werden dürfen
const STATIC_DESTINATIONS = ['style', 'script', 'image', 'font', 'manifest'];

// App-Shell: nur garantiert vorhandene, statische Assets.
// Wird "best effort" gecacht – fehlende Dateien brechen die Installation NICHT ab.
const APP_SHELL = [
    '/',
    '/img/app_logo.png',
    '/favicon.ico',
    '/img/icons/icon-192.png',
];

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) =>
            // allSettled: einzelne fehlende Ressourcen verhindern die Installation nicht
            Promise.allSettled(APP_SHELL.map((url) => cache.add(url)))
        )
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((names) =>
                Promise.all(
                    names
                        .filter((name) => name !== CACHE_VERSION && name !== RUNTIME_CACHE)
                        .map((name) => caches.delete(name))
                )
            )
            .then(() => self.clients.claim())
    );
});

// Fallback, falls weder Netzwerk noch Cache eine Antwort liefern.
// respondWith() verlangt zwingend ein Response-Objekt – undefined führt zu
// "TypeError: Failed to convert value to 'Response'".
const offlineResponse = () =>
    new Response('Offline – Inhalt nicht verfügbar.', {
        status: 503,
        statusText: 'Service Unavailable',
        headers: { 'Content-Type': 'text/plain; charset=utf-8' },
    });

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Nur GET-Anfragen über das Caching behandeln, fremde Origins ignorieren
    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    // Navigationen (HTML-Seiten): Network-first mit Cache-Fallback
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() =>
                caches
                    .match(request)
                    .then((res) => res || caches.match('/'))
                    .then((res) => res || offlineResponse())
            )
        );
        return;
    }

    // Nur statische Assets cachen. fetch()/AJAX-Aufrufe (destination === '')
    // liefern dynamische Daten (JSON) und dürfen nie aus dem Cache kommen –
    // sonst zeigt z.B. die Anwesenheitsabfrage nach einer Änderung den alten Stand.
    if (!STATIC_DESTINATIONS.includes(request.destination)) {
        return;
    }

    // Statische Assets: Stale-while-revalidate
    event.respondWith(
        caches.match(request).then((cached) => {
            const network = fetch(request)
                .then((response) => {
                    if (response && response.status === 200 && response.type === 'basic') {
                        const copy = response.clone();
                        caches.open(RUNTIME_CACHE).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(() => cached || offlineResponse());
            return cached || network;
        })
    );
});

/* ------------------------------------------------------------------ */
/* Web-Push-Benachrichtigungen                                         */
/* ------------------------------------------------------------------ */

self.addEventListener('push', function (e) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        // Benachrichtigungen nicht unterstützt oder keine Berechtigung
        return;
    }

    if (e.data) {
        const msg = e.data.json();
        e.waitUntil(
            self.registration.showNotification(msg.title, {
                body: msg.body,
                icon: msg.icon,
                actions: msg.actions,
            })
        );
    }
});

self.addEventListener('notificationclick', function (event) {
    // In Service-Worker-Kontext ist "window" nicht verfügbar -> self.location verwenden
    const appUrl = self.location.origin;

    event.notification.close();
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // Bereits geöffnetes Fenster fokussieren, sonst neues öffnen
            for (const client of clientList) {
                if ('focus' in client) {
                    return client.focus();
                }
            }
            return self.clients.openWindow(appUrl);
        })
    );
});
