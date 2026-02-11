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

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\FilesController;
use App\Http\Controllers\API\ImageController;
use Illuminate\Support\Facades\Route;

/*
 * Vertretungsplan aus MitarbeiterBoard
 */

Route::post('vertretungen/', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'store']);
Route::put('vertretungen/{id}', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'update']);
Route::delete('vertretungen/{id}', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'destroy']);
Route::post('news/', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'storeNews']);
Route::delete('news/{id}', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'deleteNews']);
Route::post('week/', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'storeWeek']);
Route::put('week/{id}', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'updateWeek']);
Route::delete('week/{id}', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'deleteWeek']);
Route::post('absences/', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'storeAbsence']);
Route::put('absences/{id}', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'updateAbsence']);
Route::delete('absences/{id}', [\App\Http\Controllers\API\VertretungsplanConnectController::class, 'deleteAbsence']);

/*
 * Stundenplan Import API
 */
Route::post('stundenplan/import', [\App\Http\Controllers\API\StundenplanImportController::class, 'import']);
Route::get('stundenplan/status', [\App\Http\Controllers\API\StundenplanImportController::class, 'status']);

/*
 * Stundenplan Query API (with authentication)
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('stundenplan/classes', [\App\Http\Controllers\API\StundenplanController::class, 'getClasses']);
    Route::get('stundenplan/teachers', [\App\Http\Controllers\API\StundenplanController::class, 'getTeachers']);
    Route::get('stundenplan/rooms', [\App\Http\Controllers\API\StundenplanController::class, 'getRooms']);
    Route::get('stundenplan/class/{classId}', [\App\Http\Controllers\API\StundenplanController::class, 'getTimetableByClass']);
    Route::get('stundenplan/teacher/{teacherId}', [\App\Http\Controllers\API\StundenplanController::class, 'getTimetableByTeacher']);
    Route::get('stundenplan/room/{roomId}', [\App\Http\Controllers\API\StundenplanController::class, 'getTimetableByRoom']);
});

Route::get('home/{post_id}', function () {
    return redirect(url('/'.'#'.request()->post_id));
});

Route::post('/token/create', [AuthController::class, 'login']);

Route::get('files/{media_uuid}', [ImageController::class, 'getFileByUuid']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('me', [AuthController::class, 'me']);
    Route::post('/token/logout', [AuthController::class, 'logout']);

    /**
     * Notifications
     */
    Route::get('notifications', [\App\Http\Controllers\API\NotificationController::class, 'index']);
    Route::post('notification/read', [\App\Http\Controllers\API\NotificationController::class, 'read']);
    Route::post('notification/readAllByType', [\App\Http\Controllers\API\NotificationController::class, 'readAllByType']);
    Route::post('notification/readAll', [\App\Http\Controllers\API\NotificationController::class, 'readAll']);

    /**
     * Listen
     */
    Route::get('listen', [\App\Http\Controllers\API\ListenController::class, 'index']);
    Route::get('liste/{liste}', [\App\Http\Controllers\API\ListenController::class, 'show']);
    Route::put('listen/termin/{termin}/cancel', [\App\Http\Controllers\API\ListenController::class, 'cancelTermin']);
    Route::put('listen/termin/{termin}/reservieren', [\App\Http\Controllers\API\ListenController::class, 'reserveTermin']);
    Route::post('/listen/{liste}/eintrag/add', [\App\Http\Controllers\API\ListenController::class, 'addEintrag']);
    Route::put('/listen/eintrag/{eintrag}/stornieren', [\App\Http\Controllers\API\ListenController::class, 'removeEintrag']);
    Route::put('/listen/eintrag/{eintrag}/reservieren', [\App\Http\Controllers\API\ListenController::class, 'reserveEintrag']);

    /**
     * Krankmeldung
     */
    Route::post('krankmeldung', [\App\Http\Controllers\API\KrankmeldungenController::class, 'store']);
    Route::get('krankmeldung', [\App\Http\Controllers\API\KrankmeldungenController::class, 'getDiseses']);
    Route::get('activeDisease', [\App\Http\Controllers\API\KrankmeldungenController::class, 'getActiveDisease']);

    /**
     * Rueckmeldungen
     */
    Route::get('rueckmeldung/{post_id}', [\App\Http\Controllers\API\UserRueckmeldungenController::class, 'index']);
    Route::post('rueckmeldung', [\App\Http\Controllers\API\UserRueckmeldungenController::class, 'store']);

    /**
     * Abfragen
     */
    Route::get('abfrage/{post_id}', [\App\Http\Controllers\API\AbfragenController::class, 'getFields']);
    Route::post('abfrage/{post}', [\App\Http\Controllers\API\AbfragenController::class, 'storeAnswer']);

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
    Route::get('posts/{post}', [\App\Http\Controllers\API\NachrichtenController::class, 'show']);
    Route::post('posts/{post}/reactions', [\App\Http\Controllers\API\NachrichtenController::class, 'updateReaction']);
    Route::post('posts/{post}/read', [\App\Http\Controllers\API\ReadReceiptsController::class, 'store']);

    /**
     * Comments
     */
    Route::get('posts/{post}/comments', [\App\Http\Controllers\API\CommentController::class, 'index']);
    Route::post('posts/{post}/comments', [\App\Http\Controllers\API\CommentController::class, 'store']);
    Route::delete('comments/{comment}', [\App\Http\Controllers\API\CommentController::class, 'destroy']);

    /**
     * Polls
     */
    Route::get('posts/{post}/poll', [\App\Http\Controllers\API\PollController::class, 'show']);
    Route::post('posts/{post}/poll/vote', [\App\Http\Controllers\API\PollController::class, 'vote']);

    /**
     * Kontakt
     */
    Route::get('contact', [\App\Http\Controllers\API\ContactController::class, 'index']);
    Route::post('contact', [\App\Http\Controllers\API\ContactController::class, 'send']);

    /**
     * Losungen
     */
    Route::get('losungen', [\App\Http\Controllers\API\LosungController::class, 'getLosung']);

    /**
     * Vertretungsplan
     */
    Route::get('vertretungsplan', [\App\Http\Controllers\API\VertretungsplanController::class, 'index']);

    /**
     * Care / Anwesenheit
     */
    Route::get('care/present', [\App\Http\Controllers\API\CareController::class, 'getPresentChildren']);
    Route::get('care/sick', [\App\Http\Controllers\API\CareController::class, 'getSickChildren']);
    Route::get('care/overview', [\App\Http\Controllers\API\CareController::class, 'getCareOverview']);

});
