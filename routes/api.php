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

use App\Http\Controllers\API\FilesController;
use App\Http\Controllers\API\ImageController;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

Route::get('home/{post_id}', function () {
    return redirect(url('/'.'#'.request()->post_id));
});

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
    return response()->json(['token' => $user->createToken($request->device_name)->plainTextToken]);
});




Route::get('files/{media_uuid}', [ImageController::class, 'getFileByUuid']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/token/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Tokens Revoked']);
    });

    /**
     * Listen
     */
    Route::get('listen', [\App\Http\Controllers\API\ListenController::class, 'index']);
    Route::get('liste/{id}', [\App\Http\Controllers\API\ListenController::class, 'show']);
    Route::put('listen/termin/{id}/cancel', [\App\Http\Controllers\API\ListenController::class, 'cancelTermin']);
    Route::put('listen/termin/{id}/reservieren', [\App\Http\Controllers\API\ListenController::class, 'reserveTermin']);
    Route::post('/listen/{id}/eintrag/add', [\App\Http\Controllers\API\ListenController::class, 'addEintrag']);
    Route::put('/listen/eintrag/{id}/stornieren', [\App\Http\Controllers\API\ListenController::class, 'removeEintrag']);
    Route::put('/listen/eintrag/{id}/reservieren', [\App\Http\Controllers\API\ListenController::class, 'reserveEintrag']);

    /**
     * Krankmeldung
     */
    Route::post('krankmeldung', [\App\Http\Controllers\API\KrankmeldungenController::class, 'store']);
    Route::get('krankmeldung', [\App\Http\Controllers\API\KrankmeldungenController::class, 'getDiseses']);
    Route::get('activeDisease', [\App\Http\Controllers\API\KrankmeldungenController::class, 'getActiveDisease']);

    /**
     * Rueckmeldungen
     */
    Route::post('rueckmeldung', [\App\Http\Controllers\API\UserRueckmeldungenController::class, 'store']);

    /**
     * Dateien, Bilder, Downloads
     */
    Route::get('files', [FilesController::class, 'index']);
    Route::get('image/{media_id}', [ImageController::class, 'getImage']);

    /**
     * Termine
     */
    Route::get('termine', [\App\Http\Controllers\API\TerminController::class, 'index']);

    /**
     * Nachrichten
     */
    Route::get('posts', [\App\Http\Controllers\API\NachrichtenController::class, 'index']);
    Route::post('posts/{postID}/reactions', [\App\Http\Controllers\API\NachrichtenController::class, 'updateReaction']);
    Route::post('posts/{post}/read', [\App\Http\Controllers\API\ReadReceiptsController::class, 'store']);


    /**
     * Kontakt
     */
    Route::get('contact', [\App\Http\Controllers\API\ContactController::class, 'index']);
    Route::post('contact', [\App\Http\Controllers\API\ContactController::class, 'send']);

    /**
     * Losungen
     */
    Route::get('losungen', [\App\Http\Controllers\API\LosungController::class, 'getLosung']);
});
