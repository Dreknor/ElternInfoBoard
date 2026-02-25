<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
        Log::debug('Login attempt', ['email' => $request->email, 'device_name' => $request->device_name]);
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        // Normalize email (trim whitespace, convert to lowercase)
        $email = strtolower(trim($request->email));

        // Try to find user (including soft-deleted ones)
        $user = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

        Log::debug('User lookup result', [
            'email' => $email,
            'original_email' => $request->email,
            'user_found' => $user !== null,
            'user_id' => $user?->id,
            'user_deleted' => $user?->trashed(),
            'password_hash_length' => $user ? strlen($user->password) : 0
        ]);

        if (! $user) {
            Log::warning('Login failed: User not found', ['email' => $email]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->trashed()) {
            Log::warning('Login failed: User is soft-deleted', [
                'email' => $email,
                'user_id' => $user->id
            ]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! Hash::check($request->password, $user->password)) {
            Log::warning('Login failed: Invalid password', [
                'email' => $email,
                'user_id' => $user->id
            ]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        Log::info('Login successful', ['email' => $email, 'user_id' => $user->id]);

        return response()->json(['token' => $user->createToken($request->device_name)->plainTextToken]);
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
        return response()->json($request->user());
    }
}
