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

    function pushSupported() {
        return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    }

    function getStatus() {
        if (!pushSupported()) {
            return (isIOS && !isStandalone) ? 'ios-install' : 'unsupported';
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
                if (pushSupported() && Notification.permission === 'granted') {
                    subscribeUser();
                }
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

    function storePushSubscription(pushSubscription) {
        const token = document.querySelector('meta[name=csrf-token]').getAttribute('content');

        return fetch('/push', {
            method: 'POST',
            body: JSON.stringify(pushSubscription),
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
