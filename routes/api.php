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
 * Vertretungsplan aus MitarbeiterBoard (API-Key-Auth, Rate-Limit: 30/min)
 */
Route::middleware('throttle:external-api')->group(function () {
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
     * Stundenplan Import API (API-Key-Auth)
     */
    Route::post('stundenplan/import', [\App\Http\Controllers\API\StundenplanImportController::class, 'import']);
    Route::get('stundenplan/status', [\App\Http\Controllers\API\StundenplanImportController::class, 'status']);
});

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

Route::middleware('auth:sanctum')->group(function () {

    Route::get('me', [AuthController::class, 'me']);
    Route::post('/token/logout', [AuthController::class, 'logout']);

    Route::get('files/{media_uuid}/download', [ImageController::class, 'getFileByUuid'])->name('api.files.download');

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
    Route::get('files', [FilesController::class, 'index'])->name('api.files.index');
    Route::get('files/mime-types', [FilesController::class, 'mimeTypes'])->name('api.files.mime-types');
    Route::get('files/stats', [FilesController::class, 'stats'])->name('api.files.stats');
    Route::get('files/{uuid}', [FilesController::class, 'show'])->name('api.files.show');
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

    /**
     * Parent / Eltern
     */
    Route::get('parent/children', [\App\Http\Controllers\API\ParentController::class, 'getChildren']);
    Route::get('parent/attendance-queries', [\App\Http\Controllers\API\ParentController::class, 'getAttendanceQueries']);
    Route::post('parent/attendance-queries/bulk', [\App\Http\Controllers\API\ParentController::class, 'bulkUpdateAttendanceQueries']);
    Route::get('parent/children/check-in-status', [\App\Http\Controllers\API\ParentController::class, 'getChildrenCheckInStatus']);
    Route::put('parent/check-in/{checkInId}/confirm', [\App\Http\Controllers\API\ParentController::class, 'confirmAttendance']);
    Route::put('parent/check-in/{checkInId}/decline', [\App\Http\Controllers\API\ParentController::class, 'declineAttendance']);
    Route::get('parent/schickzeiten', [\App\Http\Controllers\API\ParentController::class, 'getSchickzeiten']);
    Route::post('parent/schickzeiten', [\App\Http\Controllers\API\ParentController::class, 'storeSchickzeit']);
    Route::put('parent/schickzeiten/{schickzeitId}', [\App\Http\Controllers\API\ParentController::class, 'updateSchickzeit']);
    Route::delete('parent/schickzeiten/{schickzeitId}', [\App\Http\Controllers\API\ParentController::class, 'deleteSchickzeit']);
    Route::get('parent/child-notices', [\App\Http\Controllers\API\ParentController::class, 'getChildNotices']);
    Route::post('parent/child-notices', [\App\Http\Controllers\API\ParentController::class, 'storeChildNotice']);
    Route::delete('parent/child-notices/{noticeId}', [\App\Http\Controllers\API\ParentController::class, 'deleteChildNotice']);
    Route::get('parent/child-mandates', [\App\Http\Controllers\API\ParentController::class, 'getChildMandates']);
    Route::post('parent/child-mandates', [\App\Http\Controllers\API\ParentController::class, 'storeChildMandate']);
    Route::put('parent/child-mandates/{mandateId}', [\App\Http\Controllers\API\ParentController::class, 'updateChildMandate']);
    Route::delete('parent/child-mandates/{mandateId}', [\App\Http\Controllers\API\ParentController::class, 'deleteChildMandate']);
    Route::get('parent/krankmeldungen', [\App\Http\Controllers\API\ParentController::class, 'getKrankmeldungen']);
    Route::get('parent/krankmeldungen/history', [\App\Http\Controllers\API\ParentController::class, 'getKrankmeldungenHistory']);

    /**
     * Pflichtstunden
     */
    Route::get('pflichtstunden', [\App\Http\Controllers\API\PflichtstundeController::class, 'index']);
    Route::get('pflichtstunden/stats', [\App\Http\Controllers\API\PflichtstundeController::class, 'stats']);
    Route::post('pflichtstunden', [\App\Http\Controllers\API\PflichtstundeController::class, 'store']);
    Route::put('pflichtstunden/{pflichtstunde}', [\App\Http\Controllers\API\PflichtstundeController::class, 'update']);
    Route::delete('pflichtstunden/{pflichtstunde}', [\App\Http\Controllers\API\PflichtstundeController::class, 'destroy']);

    /**
     * User Settings (App-specific settings)
     */
    Route::get('user/settings', [\App\Http\Controllers\API\UserSettingsController::class, 'index']);
    Route::post('user/settings', [\App\Http\Controllers\API\UserSettingsController::class, 'store']);
    Route::patch('user/settings', [\App\Http\Controllers\API\UserSettingsController::class, 'update']);
    Route::delete('user/settings', [\App\Http\Controllers\API\UserSettingsController::class, 'destroy']);
    Route::get('user/settings/default', [\App\Http\Controllers\API\UserSettingsController::class, 'defaults']);

});
