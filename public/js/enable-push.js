/*
 | Web-Push-Registrierung
 |
 | Der Service Worker wird beim Laden registriert. Die Berechtigungsabfrage
 | erfolgt dagegen nur nach einer Nutzeraktion (Klick auf ein Element mit
 | [data-push-enable]) – iOS/Safari blockiert requestPermission() ohne Geste.
 | Ist die Berechtigung bereits erteilt, wird das Abo still erneuert.
 |
 | Status-Anzeige: Elemente mit [data-push-status] erhalten einen Text,
 | Elemente mit [data-push-state="<status>"] werden passend ein-/ausgeblendet.
 | Mögliche Status: granted, default, denied, ios-install, unsupported
 |
 | Geräteliste in den Einstellungen: Zeilen mit [data-push-endpoint] des eigenen
 | Abos zeigen [data-push-current]. Wird das eigene Abo entfernt, meldet sich der
 | Browser ab und erneuert es nicht mehr still, bis Push wieder aktiviert wird.
 */

(function () {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isStandalone = window.navigator.standalone === true
        || window.matchMedia('(display-mode: standalone)').matches;

    const STATUS_TEXT = {
        'granted': 'Push-Benachrichtigungen sind auf diesem Gerät aktiv.',
        'default': 'Push-Benachrichtigungen sind auf diesem Gerät noch nicht aktiviert.',
        'denied': 'Push-Benachrichtigungen wurden für diese Seite blockiert. Bitte in den Browser- bzw. Geräte-Einstellungen erlauben.',
        'ios-install': 'Auf dem iPhone/iPad funktionieren Push-Benachrichtigungen nur, wenn die Seite über „Teilen“ → „Zum Home-Bildschirm“ installiert und von dort geöffnet wird.',
        'unsupported': 'Dieser Browser unterstützt keine Push-Benachrichtigungen.',
    };

    const OPT_OUT_KEY = 'elterninfo-push-removed';

    function optedOut() {
        try {
            return localStorage.getItem(OPT_OUT_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function setOptedOut(value) {
        try {
            if (value) {
                localStorage.setItem(OPT_OUT_KEY, '1');
            } else {
                localStorage.removeItem(OPT_OUT_KEY);
            }
        } catch (e) {
            // ohne Speicher: Abo wird beim nächsten Besuch wieder erneuert
        }
    }

    function pushSupported() {
        return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    }

    function getStatus() {
        if (!pushSupported()) {
            return (isIOS && !isStandalone) ? 'ios-install' : 'unsupported';
        }
        if (Notification.permission === 'granted' && optedOut()) {
            return 'default';
        }
        return Notification.permission;
    }

    function renderStatus() {
        const status = getStatus();
        document.querySelectorAll('[data-push-status]').forEach((el) => {
            el.textContent = STATUS_TEXT[status];
        });
        document.querySelectorAll('[data-push-state]').forEach((el) => {
            const states = el.getAttribute('data-push-state').split(/\s+/);
            el.classList.toggle('hidden', !states.includes(status));
        });
    }

    function initSW() {
        if (!('serviceWorker' in navigator)) {
            return;
        }

        navigator.serviceWorker.register('/sw.js')
            .then(() => {
                if (pushSupported() && Notification.permission === 'granted' && !optedOut()) {
                    subscribeUser();
                }
                markCurrentDevice();
            })
            .catch((err) => {
                console.log(err);
            });
    }

    function requestPermission() {
        // Muss synchron im Klick-Handler aufgerufen werden (iOS).
        return new Promise((resolve, reject) => {
            const permissionResult = Notification.requestPermission(resolve);
            if (permissionResult) {
                permissionResult.then(resolve, reject);
            }
        });
    }

    function enablePush() {
        if (!pushSupported()) {
            renderStatus();
            return Promise.resolve(getStatus());
        }

        setOptedOut(false);

        return requestPermission()
            .then((permission) => {
                renderStatus();
                if (permission !== 'granted') {
                    return permission;
                }
                return subscribeUser().then(() => permission);
            });
    }

    function subscribeUser() {
        const vapid = document.querySelector('meta[name=vapidPublicKey]').getAttribute('content');

        return navigator.serviceWorker.ready
            .then((registration) => registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapid),
            }))
            .then((pushSubscription) => storePushSubscription(pushSubscription))
            .catch((err) => {
                console.log(err);
            });
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    // Plattform des Abos: auf Geräten mit installierter App sendet der Server keinen Browser-Push
    function platform() {
        if (isIOS) {
            return 'ios';
        }
        return /Android/i.test(navigator.userAgent) ? 'android' : 'desktop';
    }

    function storePushSubscription(pushSubscription) {
        const token = document.querySelector('meta[name=csrf-token]').getAttribute('content');

        return fetch('/push', {
            method: 'POST',
            body: JSON.stringify(Object.assign(pushSubscription.toJSON(), { platform: platform() })),
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': token,
            },
        })
            .then((res) => res.json())
            .catch((err) => {
                console.log(err);
            });
    }

    function currentSubscription() {
        if (!pushSupported()) {
            return Promise.resolve(null);
        }
        return navigator.serviceWorker.ready
            .then((registration) => registration.pushManager.getSubscription())
            .catch(() => null);
    }

    function markCurrentDevice() {
        currentSubscription().then((subscription) => {
            if (!subscription) {
                return;
            }
            document.querySelectorAll('[data-push-endpoint]').forEach((row) => {
                const current = row.getAttribute('data-push-endpoint') === subscription.endpoint;
                row.toggleAttribute('data-push-is-current', current);
                row.querySelectorAll('[data-push-current]').forEach((el) => el.classList.toggle('hidden', !current));
            });
        });
    }

    // Eigenes Abo entfernen: zuerst im Browser abmelden, sonst würde es beim nächsten Laden erneuert
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form.id || !form.id.startsWith('push-remove-')) {
            return;
        }
        const button = document.querySelector('[form="' + form.id + '"]');
        const row = button ? button.closest('[data-push-endpoint]') : null;
        if (!row || !row.hasAttribute('data-push-is-current')) {
            return;
        }
        event.preventDefault();
        setOptedOut(true);
        currentSubscription()
            .then((subscription) => subscription ? subscription.unsubscribe() : null)
            .catch((err) => console.log(err))
            .finally(() => form.submit());
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-push-enable]');
        if (!trigger) {
            return;
        }
        event.preventDefault();
        trigger.disabled = true;
        enablePush().finally(() => {
            trigger.disabled = false;
        });
    });

    window.ElternInfoPush = {
        enable: enablePush,
        status: getStatus,
    };

    renderStatus();
    initSW();
})();
