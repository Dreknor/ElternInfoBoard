<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use App\Services\Security\AccountSecurity;
use App\Services\Security\AuthEventSubscriber;
use App\Services\Security\SecurityLog;
use Illuminate\Validation\ValidationException;

/**
 * Class AuthController
 *
 * Controller for handling authentication related API requests.
 */
class AuthController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum', except: ['login']),
        ];
    }

    /**
     * Handle user login and generate an authentication token.
     *
     * The token is used to authenticate the user in subsequent requests.
     *
     *
     *
     *
     * @bodyParam email string required The email address of the user.
     * @bodyParam password string required The password of the user.
     * @bodyParam device_name string required The name of the device.
     *
     * @responseField  token string The authentication token.
     *
     * @group Benutzer
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        // Normalize email (trim whitespace, convert to lowercase)
        $email = strtolower(trim($request->email));

        // Try to find user (including soft-deleted ones)
        $user = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

        // Deaktivierte oder gelöschte Konten erhalten kein Token (wie die Web-Anmeldung und /api/v1/token).
        // Protokoll ohne E-Mail-Adresse und Gerätenamen (SecurityLog).
        $reason = match (true) {
            ! $user => 'invalid_credentials',
            $user->trashed() => 'deleted',
            ! Hash::check($request->password, $user->password) => 'invalid_credentials',
            $user->is_active === false => 'inactive',
            default => null,
        };

        if ($reason !== null) {
            SecurityLog::record('auth.failed', $user?->id, [
                'guard' => 'sanctum', 'method' => 'password', 'client' => 'legacy_api', 'reason' => $reason,
            ] + ($user ? [] : AuthEventSubscriber::identifierContext($email)));

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        SecurityLog::record('auth.login', $user->id, ['guard' => 'sanctum', 'method' => 'password', 'client' => 'legacy_api']);

        $token = $user->createToken($request->device_name);
        AccountSecurity::tokenIssued($token);
        $minutes = config('sanctum.expiration');

        // Zusätzliche Felder sind abwärtskompatibel (ältere Apps lesen nur `token`).
        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $minutes ? now()->addMinutes($minutes)->toIso8601String() : null,
            'must_change_password' => (bool) $user->changePassword,
        ]);
    }

    /**
     * Logout the authenticated user.
     *
     * The user is logged out and the authentication token is revoked.
     *
     * @group Benutzer
     *
     * @responseField message string A message indicating that the user has been logged out.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        AccountSecurity::tokenRevoked($request->user()->currentAccessToken(), 'logout');
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * User information.
     *
     * Get the authenticated user's information.
     *
     * @group Benutzer
     *
     * @responseField user object The authenticated user's information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user();

        // Nur die für die App tatsächlich benötigten Felder zurückgeben (DSGVO Datensparsamkeit).
        // Interne Felder wie changePassword, track_login, lastEmail etc. werden nicht exponiert.
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'uuid' => $user->uuid,
            'benachrichtigung' => $user->benachrichtigung,
            'publicMail' => $user->publicMail,
            'publicPhone' => $user->publicPhone,
            'releaseCalendar' => $user->releaseCalendar,
            'calendar_prefix' => $user->calendar_prefix,
        ]);
    }
}
