<?php

namespace App\Http\Middleware;

use App\Model\User;
use App\Services\Students\StudentAccountLog;
use App\Support\StudentAccounts;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Freigabeliste für Schülerkonten (docs/konzept-schuelerkonten.md §5.1, Paket S-03).
 *
 * Liegt in den Gruppen `web` und `api` sowie am MCP-Server. Ein Schülerkonto erreicht nur
 * Routen mit dem Namenspräfix `student.` (Web) bzw. `api.v1.student.` (App) und die Abmeldung.
 * Alles andere antwortet mit 404, wie bei einem inaktiven Modul. Unbenannte Routen, Datei-
 * Downloads und Verwaltungsseiten sind damit gesperrt, auch solche, die später hinzukommen.
 *
 * Außerdem meldet sie Schülerkonten ab, wenn das Modul aus ist, das Konto gesperrt wurde oder
 * die Web-Sitzung älter als WEB_SESSION_HOURS ist.
 */
class RestrictStudentAccounts
{
    /** Zeitpunkt der Anmeldung in der Sitzung (für die eigene Sitzungsdauer der Schüler). */
    public const SESSION_LOGIN_AT = 'student_login_at';

    /** Maximale Dauer einer Web-Sitzung eines Schülerkontos. */
    public const WEB_SESSION_HOURS = 12;

    /** Namenspräfixe der Routen, die Schülerkonten offenstehen. */
    private const ALLOWED_PREFIXES = ['student.', 'api.v1.student.'];

    /** Einzelne Routen, die Schülerkonten zusätzlich offenstehen. */
    private const ALLOWED_ROUTES = ['logout', 'auth.ucs.logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->user($request);

        if (! $user instanceof User || ! $user->isStudent()) {
            return $next($request);
        }

        if ($reason = $this->logoutReason($request, $user)) {
            return $this->logout($request, $user, $reason);
        }

        if ($this->allowed($request)) {
            return $next($request);
        }

        // Startseite der Eltern → Startseite der Schüler
        if ($request->isMethod('GET') && ! $this->isApi($request) && in_array(trim($request->path(), '/'), ['', 'home', 'nachrichten'], true)) {
            return redirect()->route('student.home');
        }

        abort(404);
    }

    public static function allowedRouteName(?string $name): bool
    {
        if ($name === null) {
            return false;
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return in_array($name, self::ALLOWED_ROUTES, true);
    }

    private function allowed(Request $request): bool
    {
        return self::allowedRouteName($request->route()?->getName());
    }

    private function user(Request $request): ?User
    {
        $user = Auth::guard('web')->user() ?? $request->user();

        if ($user === null && ($this->isApi($request) || $request->bearerToken())) {
            $user = Auth::guard('sanctum')->user();
        }

        return $user instanceof User ? $user : null;
    }

    private function logoutReason(Request $request, User $user): ?string
    {
        if (! StudentAccounts::enabled()) {
            return 'Für Schülerinnen und Schüler ist kein Zugang freigeschaltet.';
        }

        if ($user->is_active === false) {
            return 'Dein Zugang ist gesperrt. Bitte wende dich an die Schule.';
        }

        if (! $this->isApi($request) && $request->hasSession()) {
            $loginAt = (int) $request->session()->get(self::SESSION_LOGIN_AT, 0);

            if ($loginAt === 0) {
                $request->session()->put(self::SESSION_LOGIN_AT, time());
            } elseif ($loginAt < time() - self::WEB_SESSION_HOURS * 3600) {
                return 'Bitte melde dich erneut an.';
            }
        }

        return null;
    }

    private function logout(Request $request, User $user, string $reason): Response
    {
        if ($this->isApi($request) || ! $request->hasSession()) {
            $token = $user->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }

            return response()->json(['message' => $reason, 'code' => 'student'], 401);
        }

        StudentAccountLog::info('Schülerkonto abgemeldet', $user, ['grund' => $reason]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with([
            'type' => 'warning',
            'Meldung' => $reason,
        ]);
    }

    private function isApi(Request $request): bool
    {
        return $request->is('api/*');
    }
}
