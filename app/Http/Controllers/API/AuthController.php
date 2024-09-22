<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Class AuthController
 *
 * Controller for handling authentication related API requests.
 */
class AuthController extends Controller
{
    /**
     * AuthController constructor.
     *
     * Apply authentication middleware except for the login method.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except('login');
    }

    /**
     * Handle user login and generate an authentication token.
     *
     * The token is used to authenticate the user in subsequent requests.
     *
     *
     *
     * @param \Illuminate\Http\Request $request
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
     * @throws \Illuminate\Validation\ValidationException
     *
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        return response()->json(['token' => $user->createToken($request->device_name)->plainTextToken]);
    }

    /**
     * Logout the authenticated user.
     *
     * The user is logged out and the authentication token is revoked.
     *
     * @group Benutzer
     * @responseField message string A message indicating that the user has been logged out.
     *
     * @param \Illuminate\Http\Request $request
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
     * @responseField user object The authenticated user's information.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
