<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\API\ImageController;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

Route::post('/token/create', function (Request $request) {
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

    return $user->createToken($request->device_name)->plainTextToken;
});
Route::get('image/{media_id}', [ImageController::class, 'getImage']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('posts', [\App\Http\Controllers\API\NachrichtenController::class, 'index']);
    Route::post('posts/{postID}/reactions', [\App\Http\Controllers\API\NachrichtenController::class, 'updateReaction']);
    Route::post('posts/{post}/read', [\App\Http\Controllers\API\ReadReceiptsController::class, 'store']);


    Route::get('termine', [\App\Http\Controllers\API\TerminController::class, 'index']);

    Route::get('contact', [\App\Http\Controllers\API\ContactController::class, 'index']);
    Route::post('contact', [\App\Http\Controllers\API\ContactController::class, 'send']);
});
