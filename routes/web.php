<?php

use App\Http\Controllers\ActiveDiseaseController;
use App\Http\Controllers\Auth\ExpiredPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BenutzerController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\DatenschutzController;
use App\Http\Controllers\ElternratController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\ICalController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\KrankmeldungenController;
use App\Http\Controllers\ListenController;
use App\Http\Controllers\ListenEintragungenController;
use App\Http\Controllers\ListenTerminController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LosungController;
use App\Http\Controllers\NachrichtenController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ReadReceiptsController;
use App\Http\Controllers\ReinigungController;
use App\Http\Controllers\ReinigungsTaskController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\RueckmeldungenController;
use App\Http\Controllers\SchickzeitenController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SiteBlockController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TerminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRueckmeldungenController;
use App\Http\Controllers\VertretungsplanController;
use App\Repositories\WordpressRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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

Route::get('login/keycloak', [LoginController::class, 'redirectToKeycloak'])->name('login.keycloak');
Route::get('login/keycloak/callback', [LoginController::class, 'handleKeycloakCallback']);
Auth::routes(['register' => false]);
Route::get('image/{media_id}', [ImageController::class, 'getImage']);
Route::get('{uuid}/ical', [ICalController::class, 'createICal']);
Route::get('ical/publicEvents', [ICalController::class, 'publicICal']);


//Apple Touch Icon
Route::get('apple-touch-icon-precomposed.png', function () {
    return response()->file(public_path('img/'.config('app.logo_small')));
});
Route::get('apple-touch-icon.png', function () {
    return response()->file(public_path('img/'.config('app.logo_small')));
});

Route::middleware('auth')->group(function () {

    Route::get('password/expired', [ExpiredPasswordController::class, 'expired'])
        ->name('password.expired');
    Route::post('password/post_expired', [ExpiredPasswordController::class, 'postExpired'])
        ->name('password.post_expired');

    Route::middleware(['password_expired'])->group(function () {
        Route::get('settings/scan', [FileController::class, 'showScan'])->middleware('can:scan files');
        Route::delete('settings/removeFiles', [FileController::class, 'removeOldFiles'])->middleware('can:scan files');
        Route::delete('settings/removeUnusedFiles', [FileController::class, 'deleteUnusedFiles'])->middleware('can:scan files');
        Route::get('settings/file/{file}/destroy', [FileController::class, 'destroy'])->middleware('can:scan files');
        Route::get('settings/post/{post}/destroy', [NachrichtenController::class, 'deleteTrashed'])->middleware('can:scan files');

        //Routen für die Verwaltung der Rückmeldungen
        Route::get('rueckmeldungen/{rueckmeldung}/show', [RueckmeldungenController::class, 'show']);

        Route::middleware('permission:manage rueckmeldungen')->group(function () {
            Route::get('rueckmeldungen', [RueckmeldungenController::class, 'index']);
            Route::get('rueckmeldungen/{rueckmeldung}/download/{user_id}', [RueckmeldungenController::class, 'download']);
        });
        Route::get('rueckmeldungen/{rueckmeldung}/download', [RueckmeldungenController::class, 'downloadAll']);

        //Vertretungsplan
        Route::get('vertretungsplan', [VertretungsplanController::class, 'index'])->middleware('can:view vertretungsplan');

        //Datenschutz
        Route::get('datenschutz', [DatenschutzController::class, 'show']);



        //make a push notification.
        Route::post('/notification/read', [NotificationController::class, 'read'])->name('notification.read');
        Route::get('/notification/read/all', [NotificationController::class, 'readAll'])->name('notification.readAll');
        Route::post('markNotificationAsRead',[ NotificationController::class, 'readByType']);
        Route::post('/push', [PushController::class, 'store']);

        Route::get('/push/test', [PushController::class, 'testPush'])->name('push.test');


        //Schickzeiten
        Route::get('schickzeiten', [SchickzeitenController::class, 'index']);
        Route::get('schickzeiten/{user}/trash/{child}', [SchickzeitenController::class, 'deleteChild']);
        Route::get('verwaltung/schickzeiten/{parent}/trash/{child}', [SchickzeitenController::class, 'deleteChildVerwaltung'])->middleware('can:edit schickzeiten');
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
        Route::get('krankmeldung/download', [KrankmeldungenController::class, 'download']);
        Route::post('krankmeldung', [KrankmeldungenController::class, 'store']);
        Route::get('krankmeldung/disaese/activate/{disease}', [ActiveDiseaseController::class, 'activate'])->middleware('permission:manage diseases');

        Route::get('diseases/create', [ActiveDiseaseController::class, 'create'])->middleware('permission:manage diseases');
        Route::post('diseases/create', [ActiveDiseaseController::class, 'store'])->middleware('permission:manage diseases');
        Route::put('diseases/{disease}/active', [ActiveDiseaseController::class, 'update'])->middleware('permission:manage diseases');
        Route::delete('diseases/{disease}/delete', [ActiveDiseaseController::class, 'destroy'])->middleware('permission:manage diseases');
        Route::get('diseases/{disease}/extend', [ActiveDiseaseController::class, 'extend'])->middleware('permission:manage diseases');
        //Termine
        Route::resource('termin', TerminController::class);
        Route::get('termine/create/{post}', [TerminController::class, 'createFromPost']);
        //Route::get('termin/{termin}/edit', [TerminController::class, 'edit']);

        //Rückmeldungen

        Route::get('rueckmeldung/create/{post}/{type}', [RueckmeldungenController::class, 'create']);
        Route::put('rueckmeldung/{rueckmeldung}/update/date', [RueckmeldungenController::class, 'updateDate']);

        Route::get('userrueckmeldung/{rueckmeldung}/edit/{userrueckmeldung}', [RueckmeldungenController::class, 'editUserAbfrage']);
        Route::get('userrueckmeldung/{rueckmeldung}/new', [RueckmeldungenController::class, 'createUserAbfrage']);
        Route::post('userrueckmeldung/{rueckmeldung}/save', [RueckmeldungenController::class, 'storeNewUserAbfrage']);
        Route::put('userrueckmeldung/{rueckmeldung}/update/{userrueckmeldung}', [RueckmeldungenController::class, 'updateUserAbfrage']);
        Route::delete('userrueckmeldung/{rueckmeldung}/delete/{userrueckmeldung}', [RueckmeldungenController::class, 'deleteUserAbfrage']);


        //Text userRueckmeldungen
        Route::post('/rueckmeldung/{posts_id}', [UserRueckmeldungenController::class, 'sendRueckmeldung']);
        Route::get('/userrueckmeldung/edit/{userRueckmeldungen}', [UserRueckmeldungenController::class, 'edit']);
        Route::put('/userrueckmeldung/{userRueckmeldungen}', [UserRueckmeldungenController::class, 'update']);

        //AbfrageRueckmeldung
        Route::post('/userrueckmeldung/{rueckmeldung}', [UserRueckmeldungenController::class, 'store']);
        Route::get('/rueckmeldung/{rueckmeldung}/editAbfrage', [RueckmeldungenController::class, 'editAbfrage']);
        Route::put('/rueckmeldung/{rueckmeldung}/updateAbfrage', [RueckmeldungenController::class, 'updateAbfrage']);
        Route::delete('rueckmeldungen/{post}/', [RueckmeldungenController::class, 'destroyAbfrage']);



        Route::post('/rueckmeldung/{posts_id}/create', [RueckmeldungenController::class, 'store']);
        Route::post('/rueckmeldung/{posts_id}/create/abfrage', [RueckmeldungenController::class, 'storeAbfrage']);
        Route::put('/rueckmeldung/{posts_id}/create', [RueckmeldungenController::class, 'update']);
        Route::get('rueckmeldungen/{posts_id}/createImageUpload', [RueckmeldungenController::class, 'createImageRueckmeldung']);
        Route::get('rueckmeldungen/{posts_id}/createDiskussion', [RueckmeldungenController::class, 'createDiskussionRueckmeldung']);


        //show posts
        Route::get('/home', [NachrichtenController::class, 'index']);
        Route::get('/archiv', [NachrichtenController::class, 'postsArchiv']);
        Route::get('/archiv/{month}', [NachrichtenController::class, 'postsArchiv']);
        Route::get('/external', [NachrichtenController::class, 'postsExternal']);
        Route::get('/', [NachrichtenController::class, 'index']);
        Route::get('post/{post}', [NachrichtenController::class, 'findPost']);
        Route::post('post/readReceipt', [ReadReceiptsController::class, 'store'])->name('nachrichten.read_receipt');
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
        //Route::get('kiosk/{bereich?}', [KioskController::class, 'kioskView']);

        //Listen
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

        //TerminListe
        Route::post('listen/termine/{liste}/store', [ListenTerminController::class, 'store']);
        Route::put('listen/termine/{listen_termine}', [ListenTerminController::class, 'update']);
        Route::get('listen/termine/{listen_termine}/copy', [ListenTerminController::class, 'copy']);
        Route::delete('listen/termine/{listen_termine}', [ListenTerminController::class, 'destroy']);
        Route::delete('listen/termine/absagen/{listen_termine}', [ListenTerminController::class, 'absagen']);
        //EintragListe
        Route::post('listen/{liste}/eintragungen', [ListenEintragungenController::class, 'store']);
        Route::put('listen/eintragungen/{listen_eintragung}', [ListenEintragungenController::class, 'update']);
        Route::delete('listen/eintragungen/{listen_eintragung}', [ListenEintragungenController::class, 'destroy']);
        Route::delete('eintragungen/absagen/{listen_eintragung}', [ListenEintragungenController::class, 'destroy']);

        //Reinigungsplan
        Route::get('reinigung', [ReinigungController::class, 'index']);

        Route::middleware('permission:edit reinigung')->group(function () {
            Route::get('reinigung/{bereich}/export', [ReinigungController::class, 'export']);
            Route::get('reinigung/task/{task}/trash', [ReinigungsTaskController::class, 'destroy']);
            Route::post('reinigung/task/', [ReinigungsTaskController::class, 'store']);
            Route::post('reinigung/{Bereich}', [ReinigungController::class, 'store']);
            Route::get('reinigung/create/{Bereich}/{Datum}', [ReinigungController::class, 'create']);
            Route::get('reinigung/{Bereich}/{reinigung}/trash', [ReinigungController::class, 'destroy']);
            Route::get('reinigung/{Bereich}/auto', [ReinigungController::class, 'autoCreateStart']);
            Route::post('reinigung/{Bereich}/auto', [ReinigungController::class, 'autoCreate']);
        });

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
        Route::get('posts/{media}/changeCollection/{collection_name}', [ImageController::class, 'changeCollection'])->middleware(['permission:edit posts']);
        Route::get('posts/delete/{post}', [NachrichtenController::class, 'destroy']);

        Route::delete('posts/{posts}', [NachrichtenController::class, 'destroy']);
        Route::delete('rueckmeldung/{rueckmeldung}', [RueckmeldungenController::class, 'destroy']);

        Route::post('rueckmeldung/{posts}/saveFile', [FileController::class, 'saveFileRueckmeldung']);

        //Comment posts
        Route::post('nachricht/{posts}/comment/create', [NachrichtenController::class, 'storeComment']);
        Route::get('rueckmeldungen/{rueckmeldungen}/commentable', [RueckmeldungenController::class, 'updateCommentable']);

        //user-Verwaltung
        Route::get('/einstellungen', [BenutzerController::class, 'show']);
        Route::put('/einstellungen', [BenutzerController::class, 'update']);
        Route::post('/einstellungen/token', [BenutzerController::class, 'createToken']);
        Route::delete('/einstellungen/token/{token}', [BenutzerController::class, 'deleteToken']);

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
            Route::get('users/importVerein', [ImportController::class, 'importVereinForm'])->middleware(['permission:import user']);
            Route::post('users/importVerein', [ImportController::class, 'importVerein'])->middleware(['permission:import user']);

            Route::delete('users/{id}', [UserController::class, 'destroy']);
            Route::get('users/mass/delete', [UserController::class, 'showMassDelete']);
            Route::delete('users/mass/delete', [UserController::class, 'massDelete']);

            Route::resource('users', UserController::class);
            Route::get('users/{user}/remove/sorg2/{sorg2}', [UserController::class, 'removeVerknuepfung']);
            //Route::get('users/{user}/delete', [UserController::class, 'destroy']);
            //Route::get('sendErinnerung', [RueckmeldungenController::class, 'sendErinnerung']);
            //Route::get('/daily', [NachrichtenController::class, 'emailDaily']);
        });

        //Gruppenverwaltung
        Route::get('/groups', [GroupsController::class, 'index']);
        Route::post('/groups', [GroupsController::class, 'store'])->middleware(['permission:view groups']);
        Route::post('groups/own', [GroupsController::class, 'storeOwnGroup'])->middleware(['permission:create own group']);
        Route::post('groups/{group}/removeUser', [GroupsController::class, 'removeUserFromOwnGroup'])->middleware(['permission:create own group']);
        Route::get('groups/{group}/add', [GroupsController::class, 'addUserToOwnGroup'])->middleware(['permission:create own group']);
        Route::post('groups/{group}/addUser', [GroupsController::class, 'storeUserToOwnGroup'])->middleware(['permission:create own group']);
        Route::delete('/groups/{group}/delete', [GroupsController::class, 'delete'])->middleware(['permission:delete groups']);

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
            Route::get('settings/modul/bottomnav/{modul}', [SettingsController::class, 'change_nav']);
            Route::get('settings/modul/{modul}', [SettingsController::class, 'change_status']);
            Route::get('settings/losungen/import', [LosungController::class, 'importView']);
            Route::post('settings/losungen/import', [LosungController::class, 'import']);
        });

        Route::group(['middlewareGroups' => ['can:loginAsUser']], function () {
            Route::get('showUser/{id}', [UserController::class, 'loginAsUser']);
        });

        //Seitenverwaltung und -anzeige
        Route::group(['middlewareGroups' => ['can:view sites']], function () {
            Route::resource('sites', SiteController::class);
            Route::get('sites/{site}/active', [SiteController::class, 'activate'])->name('sites.activate');
            Route::post('blocks', [SiteBlockController::class, 'store'])->name('blocks.store');
            Route::put('blocks/{block}', [SiteBlockController::class, 'update'])->name('blocks.text.update');
            Route::delete('blocks/{block}/delete', [SiteBlockController::class, 'destroy'])->name('blocks.delete');
            Route::delete('blocks/{block}/{media}/delete', [SiteBlockController::class, 'removeMedia'])->name('blocks.media.delete');
            Route::post('blocks/{block}/image', [SiteBlockController::class, 'storeImage'])->name('blocks.image.store');
            Route::post('blocks/{block}/files', [SiteBlockController::class, 'storeFile'])->name('blocks.files.store');
            Route::get('blocks/{block}/move/up', [SiteBlockController::class, 'blockPostionUp'])->name('blocks.move.up');
            Route::get('blocks/{block}/move/down', [SiteBlockController::class, 'blockPostionDown'])->name('blocks.move.down');
        });

        Route::get('logoutAsUser', function () {
            if (session()->has('ownID')) {
                Auth::loginUsingId(Crypt::decryptString(session()->get('ownID')));
                session()->remove('ownID');
            }

            return redirect(url('/'));
        });
        //Elternratsbereich
        Route::middleware('permission:view elternrat')->group(function () {
            Route::resource('elternrat', ElternratController::class);
            Route::delete('elternrat/file/{file}', [ElternratController::class, 'deleteFile']);
            Route::delete('elternrat/discussion/{discussion}/delete', [ElternratController::class, 'destroy']);
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
    Route::delete('feedback/{mail}', [FeedbackController::class, 'deleteMail'])->middleware('can:see mails');
    Route::get('feedback/show/{mail}', [FeedbackController::class, 'showMail']);

    Route::group(['middlewareGroups' => ['can:see logs']], function () {
        Route::get('logs', [LogController::class, 'index']);
    });
});

