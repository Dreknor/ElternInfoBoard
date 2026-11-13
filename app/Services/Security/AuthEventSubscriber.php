<?php

namespace App\Services\Security;

use App\Model\User;
use Grosv\LaravelPasswordlessLogin\Events\LoginLinkExpired;
use Grosv\LaravelPasswordlessLogin\Events\LoginLinkInvalid;
use Grosv\LaravelPasswordlessLogin\Events\LoginLinkSuccessful;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Anmeldeereignisse zentral ins Sicherheitsprotokoll schreiben.
 *
 * Nur Ereignisse rund um Anmeldung und Konto – keine Seitenaufrufe. Die Anmeldeart setzen
 * Controller vor `Auth::login()` mit {@see self::expect()}; Impersonation (`impersonation`)
 * erscheint nicht als `auth.login`, sondern als `impersonation.start|stop`.
 */
class AuthEventSubscriber
{
    private const ATTRIBUTE = 'security_log.login_method';

    /** Anmeldeart für das nächste Login-Ereignis dieses Requests festlegen. */
    public static function expect(string $method): void
    {
        try {
            request()->attributes->set(self::ATTRIBUTE, $method);
        } catch (Throwable) {
            // ohne Request (Konsole) nichts zu tun
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Attempting::class => 'onAttempting',
            Login::class => 'onLogin',
            Failed::class => 'onFailed',
            Lockout::class => 'onLockout',
            Logout::class => 'onLogout',
            CurrentDeviceLogout::class => 'onCurrentDeviceLogout',
            PasswordReset::class => 'onPasswordReset',
            PasswordResetLinkSent::class => 'onPasswordResetLinkSent',
            LoginLinkSuccessful::class => 'onMagicLinkUsed',
            LoginLinkInvalid::class => 'onMagicLinkInvalid',
            LoginLinkExpired::class => 'onMagicLinkExpired',
        ];
    }

    public function onAttempting(Attempting $event): void
    {
        if (! request()->attributes->has(self::ATTRIBUTE)) {
            self::expect('password');
        }
    }

    public function onLogin(Login $event): void
    {
        $method = $this->pullMethod($event);
        if ($method === 'impersonation') {
            return;
        }

        SecurityLog::record('auth.login', (int) $event->user->getAuthIdentifier(), [
            'guard' => $event->guard,
            'method' => $method,
            'remember' => (bool) $event->remember,
        ]);
    }

    public function onFailed(Failed $event): void
    {
        $context = ['guard' => $event->guard, 'method' => 'password'];
        $userId = $event->user?->getAuthIdentifier();

        if ($userId === null) {
            $context += self::identifierContext($this->identifierFrom($event->credentials));
        }

        SecurityLog::record('auth.failed', $userId !== null ? (int) $userId : null, $context);
    }

    public function onLockout(Lockout $event): void
    {
        $identifier = (string) $event->request->input('email', '');
        $userId = $identifier !== '' ? User::where('email', $identifier)->value('id') : null;

        SecurityLog::record('auth.lockout', $userId, $userId === null ? self::identifierContext($identifier) : []);
    }

    public function onLogout(Logout $event): void
    {
        if ($event->user === null) {
            return;
        }

        SecurityLog::record('auth.logout', (int) $event->user->getAuthIdentifier(), ['guard' => $event->guard]);
    }

    /** Sitzung beendet, weil sich das Passwort geändert hat (AuthenticateSession). */
    public function onCurrentDeviceLogout(CurrentDeviceLogout $event): void
    {
        if ($event->user === null) {
            return;
        }

        SecurityLog::record('auth.logout', (int) $event->user->getAuthIdentifier(), [
            'guard' => $event->guard,
            'reason' => 'session_invalidated',
        ]);
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        // ResetsPasswords setzt remember_token selbst neu und meldet danach an.
        self::expect('password_reset');

        if ($event->user instanceof User) {
            AccountSecurity::passwordChanged($event->user, 'auth.password_reset', null, false);
        }
    }

    public function onPasswordResetLinkSent(PasswordResetLinkSent $event): void
    {
        SecurityLog::record('auth.password_reset_link_sent', (int) $event->user->getAuthIdentifier());
    }

    public function onMagicLinkUsed(LoginLinkSuccessful $event): void
    {
        SecurityLog::record('auth.magic_link.used', (int) $event->user->getAuthIdentifier());
    }

    public function onMagicLinkInvalid(LoginLinkInvalid $event): void
    {
        $id = $event->user?->getAuthIdentifier();
        SecurityLog::record('auth.magic_link.invalid', $id !== null ? (int) $id : null, $id === null ? ['unknown_account' => true] : []);
    }

    public function onMagicLinkExpired(LoginLinkExpired $event): void
    {
        SecurityLog::record('auth.magic_link.expired', (int) $event->user->getAuthIdentifier());
    }

    /** Kontext für eine nicht zuordenbare Kennung: nur HMAC-Kurzhash, nie Klartext. */
    public static function identifierContext(?string $identifier): array
    {
        $identifier = trim((string) $identifier);

        return $identifier === '' ? [] : ['identifier_hash' => SecurityLog::identifierHash($identifier)];
    }

    private function pullMethod(Login $event): string
    {
        $request = request();
        $method = $request->attributes->get(self::ATTRIBUTE);
        if (is_string($method) && $method !== '') {
            $request->attributes->remove(self::ATTRIBUTE);

            return $method;
        }

        if ($request->route()?->getName() === config('laravel-passwordless-login.login_route_name')) {
            return 'passwordless';
        }

        try {
            $guard = Auth::guard($event->guard);
            if ($event->remember && method_exists($guard, 'viaRemember') && $guard->viaRemember()) {
                return 'remember';
            }
        } catch (Throwable) {
            // unbekannter Guard
        }

        return 'other';
    }

    private function identifierFrom(array $credentials): ?string
    {
        foreach (['email', 'username', 'name'] as $key) {
            if (isset($credentials[$key]) && is_string($credentials[$key])) {
                return $credentials[$key];
            }
        }

        return null;
    }
}
