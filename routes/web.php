<?php

use App\Http\Controllers\Auth\ExpiredPasswordController;
use App\Http\Controllers\ICalController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReinigungsTaskController;
use App\Http\Controllers\VertretungsplanController;
use App\Http\Controllers\BenutzerController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\DatenschutzController;
use App\Http\Controllers\ElternratController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\KrankmeldungenController;
use App\Http\Controllers\ListenController;
use App\Http\Controllers\ListenTerminController;
use App\Http\Controllers\NachrichtenController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\ReinigungController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\RueckmeldungenController;
use App\Http\Controllers\SchickzeitenController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TerminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRueckmeldungenController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes(['register' => false]);
Route::get('image/{media_id}', [ImageController::class, 'getImage']);
Route::get('{uuid}/ical', [ICalController::class, 'createICal']);

Route::group([
    'middleware' => ['auth'],
],
    function () {



    Route::get('password/expired', [ExpiredPasswordController::class,'expired'])
        ->name('password.expired');
    Route::post('password/post_expired', [ExpiredPasswordController::class,'postExpired'])
        ->name('password.post_expired');

    Route::middleware(['password_expired'])->group(function () {
        Route::get('settings/scan', [FileController::class, 'showScan'])->middleware('can:scan files');
        Route::delete('settings/removeFiles', [FileController::class, 'removeOldFiles'])->middleware('can:scan files');
        Route::get('settings/file/{file}/destroy', [FileController::class, 'destroy'])->middleware('can:scan files');
        Route::get('settings/post/{post}/destroy', [NachrichtenController::class, 'deleteTrashed'])->middleware('can:scan files');

        //Vertretungsplan
        Route::get('vertretungsplan', [VertretungsplanController::class, 'index'])->middleware('can:view vertretungsplan');

        //Datenschutz
        Route::get('datenschutz', [DatenschutzController::class, 'show']);

        //Push
        Route::post('/push', [PushController::class, 'store']);
        Route::get('/push2', [PushController::class, 'store']);

        //make a push notification.
        Route::get('/push', [PushController::class, 'push'])->name('push');


        //Schickzeiten
        Route::get('schickzeiten', [SchickzeitenController::class, 'index']);
        Route::get('verwaltung/schickzeiten', [SchickzeitenController::class, 'indexVerwaltung'])->middleware('can:edit schickzeiten');
        Route::get('schickzeiten/download', [SchickzeitenController::class, 'download'])->middleware('can:download schickzeiten');
        Route::post('schickzeiten/child/create', [SchickzeitenController::class, 'createChild']);
        Route::post('verwaltung/schickzeiten/child/create', [SchickzeitenController::class, 'createChildVerwaltung'])->middleware('can:edit schickzeiten');
        Route::get('schickzeiten/edit/{day}/{child}', [SchickzeitenController::class, 'edit']);
        Route::get('verwaltung/schickzeiten/edit/{day}/{child}/{parent}', [SchickzeitenController::class, 'editVerwaltung'])->middleware('can:edit schickzeiten');
        Route::post('schickzeiten', [SchickzeitenController::class, 'store']);
        Route::post('verwaltung/schickzeiten/{parent}', [SchickzeitenController::class, 'storeVerwaltung'])->middleware('can:edit schickzeiten');
        Route::delete('schickzeiten/{day}/{child}', [SchickzeitenController::class, 'destroy']);
        Route::delete('verwaltung/schickzeiten/{day}/{child}/{parent}', [SchickzeitenController::class, 'destroyVerwaltung'])->middleware('can:edit schickzeiten');

        //Krankmeldung
        Route::get('krankmeldung', [KrankmeldungenController::class, 'index']);
        Route::post('krankmeldung', [KrankmeldungenController::class, 'store']);
        Route::get('krankmeldung/test', [KrankmeldungenController::class, 'dailyReport']);

        //Termine
        Route::resource('termin', TerminController::class);

        Route::post('/rueckmeldung/{posts_id}', [UserRueckmeldungenController::class, 'sendRueckmeldung']);
        Route::get('/userrueckmeldung/edit/{userRueckmeldungen}', [UserRueckmeldungenController::class, 'edit']);
        Route::put('/userrueckmeldung/{userRueckmeldungen}', [UserRueckmeldungenController::class, 'update']);

        //Rückmeldungen
        Route::post('/rueckmeldung/{posts_id}/create', [RueckmeldungenController::class, 'store']);
        Route::put('/rueckmeldung/{posts_id}/create', [RueckmeldungenController::class, 'update']);
        Route::get('rueckmeldungen/{posts_id}/createImageUpload', [RueckmeldungenController::class, 'createImageRueckmeldung']);
        Route::get('rueckmeldungen/{posts_id}/createDiskussion', [RueckmeldungenController::class, 'createDiskussionRueckmeldung']);

        //show posts
        Route::get('/home', [NachrichtenController::class, 'index']);
        Route::get('/archiv', [NachrichtenController::class, 'postsArchiv']);
        Route::get('/', [NachrichtenController::class, 'index']);
        //Route::get('pdf/{archiv?}', [NachrichtenController::class, 'pdf']);

        Route::get('posts/{post}/react/{reaction}', [ReactionController::class, 'react']);

        //Umfragen
        Route::middleware('permission:create polls')->group(function () {
            Route::post('poll/{post}/create', [PollController::class, 'store']);
            Route::put('poll/{poll}/update', [PollController::class, 'update']);
        });
        Route::post('poll/{post}/vote', [PollController::class, 'vote']);

        //KioskAnsicht
        //Route::get('kiosk/{bereich?}', [NachrichtenController::class, 'kioskView']);
        Route::get('kiosk/{bereich?}', [KioskController::class, 'kioskView']);

        //Terminlisten
        Route::get('listen', [ListenController::class, 'index']);
        Route::post('listen', [ListenController::class, 'store']);
        Route::get('listen/create', [ListenController::class, 'create']);
        Route::get('listen/{terminListe}', [ListenController::class, 'show']);
        Route::get('listen/{terminListe}/edit', [ListenController::class, 'edit']);
        Route::put('listen/{terminListe}', [ListenController::class, 'update']);
        Route::get('listen/{liste}/activate', [ListenController::class, 'activate']);
        Route::get('listen/{liste}/refresh', [ListenController::class, 'refresh']);
        Route::get('listen/{liste}/archiv', [ListenController::class, 'archiv']);
        Route::get('listen/{liste}/deactivate', [ListenController::class, 'deactivate']);
        Route::get('listen/{liste}/export', [ListenController::class, 'pdf']);
        Route::get('listen/{terminListe}/auswahl', [ListenController::class, 'auswahl']);
        Route::post('eintragungen/{liste}/store', [ListenTerminController::class, 'store']);
        Route::put('eintragungen/{listen_termine}', [ListenTerminController::class, 'update']);
        Route::delete('eintragungen/{listen_termine}', [ListenTerminController::class, 'destroy']);
        Route::delete('eintragungen/absagen/{listen_termine}', [ListenTerminController::class, 'absagen']);

        //Reinigungsplan
        Route::get('reinigung', [ReinigungController::class, 'index']);
        Route::delete('reinigung/task/', [ReinigungsTaskController::class, 'destroy']);
        Route::post('reinigung/task/', [ReinigungsTaskController::class, 'store']);
        Route::post('reinigung/{Bereich}', [ReinigungController::class, 'store']);
        Route::get('reinigung/create/{Bereich}/{Datum}', [ReinigungController::class, 'create']);


        //Edit and create posts
        Route::get('/posts/create', [NachrichtenController::class, 'create']);
        Route::get('/posts/edit/{posts}', [NachrichtenController::class, 'edit']);
        Route::get('/posts/edit/{posts}/{kiosk?}', [NachrichtenController::class, 'edit']);
        Route::get('/posts/touch/{posts}', [NachrichtenController::class, 'touch']);
        Route::get('/posts/release/{posts}', [NachrichtenController::class, 'release']);
        Route::get('/posts/stick/{post}', [NachrichtenController::class, 'stickPost'])->middleware(['permission:make sticky']);
        Route::get('/posts/archiv/{posts}', [NachrichtenController::class, 'archiv']);
        Route::put('/posts/{posts}/{kiosk?}', [NachrichtenController::class, 'update']);
        Route::post('/posts/', [NachrichtenController::class, 'store']);

        Route::delete('posts/{posts}', [NachrichtenController::class, 'destroy']);
        Route::delete('rueckmeldung/{rueckmeldung}', [RueckmeldungenController::class, 'destroy']);

        Route::post('rueckmeldung/{posts}/saveFile', [FileController::class, 'saveFileRueckmeldung']);

        //Comment posts
        Route::post('nachricht/{posts}/comment/create', [NachrichtenController::class, 'storeComment']);
        Route::get('rueckmeldungen/{rueckmeldungen}/commentable', [RueckmeldungenController::class, 'updateCommentable']);

        //user-Verwaltung
        Route::get('/einstellungen', [BenutzerController::class, 'show']);
        Route::put('/einstellungen', [BenutzerController::class, 'update']);

        //Downloads
        Route::get('/files', [FileController::class, 'index']);
        Route::post('/files', [FileController::class, 'store'])->middleware(['permission:upload files']);
        Route::get('/files/create', [FileController::class, 'create'])->middleware(['permission:upload files']);
        Route::delete('file/{file}', [FileController::class, 'delete']);

        //changelog
        Route::resource('changelog', ChangelogController::class);

        //Suche
        Route::post('search', [SearchController::class, 'search']);

        //Routen für Benutzerverwaltung

        Route::middleware('permission:edit user|import user')->group(function () {
            Route::get('email/{daily}/{id?}', [NachrichtenController::class, 'email']);
            /*             Route::get('email/daily', [NachrichtenController::class, 'emailDaily']);
            */

            Route::get('users/import', [ImportController::class, 'importForm'])->middleware(['permission:import user']);
            Route::post('users/import', [ImportController::class, 'import'])->middleware(['permission:import user']);

            Route::delete('users/{id}', [UserController::class, 'destroy']);

            Route::resource('users', UserController::class);
            //Route::get('users/{user}/delete', [UserController::class, 'destroy']);
                //Route::get('sendErinnerung', [RueckmeldungenController::class, 'sendErinnerung']);
                //Route::get('/daily', [NachrichtenController::class, 'emailDaily']);
        });

        //Gruppenverwaltung
        Route::get('/groups', [GroupsController::class, 'index']);
        Route::post('/groups', [GroupsController::class, 'store'])->middleware(['permission:view groups']);

        //Routen zur Rechteverwaltung
        Route::middleware('permission:edit permission')->group(function () {
            Route::get('roles', [RolesController::class, 'edit']);
            Route::put('roles', [RolesController::class, 'update']);
            Route::post('roles', [RolesController::class, 'store']);
            Route::post('roles/permission', [RolesController::class, 'storePermission']);
        });

        //Routen zur Rechteverwaltung
        Route::middleware('permission:edit settings')->group(function () {
            Route::get('settings', [SettingsController::class, 'module']);
            Route::get('settings/modul/{modul}', [SettingsController::class, 'change_status']);
        });

        Route::group(['middlewareGroups' => ['can:loginAsUser']], function () {
            Route::get('showUser/{id}', [UserController::class, 'loginAsUser']);
        });

        Route::get('logoutAsUser', function () {
            if (session()->has('ownID')) {
                Auth::loginUsingId(session()->pull('ownID'));
            }

            return redirect(url('/'));
        });

        //Elternratsbereich
        Route::middleware('permission:view elternrat')->group(function () {
            Route::resource('elternrat', ElternratController::class);
            Route::delete('elternrat/file/{file}', [ElternratController::class, 'deleteFile']);
            Route::delete('elternrat/comment/{comment}', [ElternratController::class, 'deleteComment']);
            Route::get('elternrat/add/file', [ElternratController::class, 'addFile']);
            Route::post('elternrat/file', [ElternratController::class, 'storeFile']);
            Route::post('beitrag/{discussion}/comment/create', [ElternratController::class, 'storeComment']);
            Route::get('elternrat/discussion/create', [ElternratController::class, 'create']);
            Route::post('elternrat/discussion', [ElternratController::class, 'store']);
            Route::get('elternrat/discussion/edit/{discussion}', [ElternratController::class, 'edit']);
            Route::put('elternrat/discussion/{discussion}', [ElternratController::class, 'update']);
        });
    });

    //Feedback
    Route::get('feedback', [FeedbackController::class, 'show']);
    Route::post('feedback', [FeedbackController::class, 'send']);
});
