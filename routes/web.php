<?php

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

Auth::routes();
Route::get('image/{media_id}', 'ImageController@getImage');

Route::middleware('auth')->group(function () {
        Route::middleware(['password_expired'])->group(function () {
            Route::get('settings/scan', 'FileController@showScan')->middleware('can:scan files');
            Route::delete('settings/removeFiles', 'FileController@removeOldFiles')->middleware('can:scan files');
            Route::get('settings/file/{file}/destroy', 'FileController@destroy')->middleware('can:scan files');
            Route::get('settings/post/{post}/destroy', 'NachrichtenController@deleteTrashed')->middleware('can:scan files');

            //Datenschutz
            Route::get('datenschutz', 'DatenschutzController@show');

            //Push
            Route::post('/push', 'PushController@store');
            Route::get('/push2', 'PushController@store');

            //make a push notification.
            Route::get('/push', 'PushController@push')->name('push');

            //Route::get('noRueckmeldung', 'RueckmeldungenController@sendErinnerung');

            //Schickzeiten
            Route::get('schickzeiten', 'SchickzeitenController@index');
            Route::get('verwaltung/schickzeiten', 'SchickzeitenController@indexVerwaltung')->middleware('can:edit schickzeiten');
            Route::get('schickzeiten/download', 'SchickzeitenController@download')->middleware('can:download schickzeiten');
            Route::post('schickzeiten/child/create', 'SchickzeitenController@createChild');
            Route::post('verwaltung/schickzeiten/child/create', 'SchickzeitenController@createChildVerwaltung')->middleware('can:edit schickzeiten');
            Route::get('schickzeiten/edit/{day}/{child}', 'SchickzeitenController@edit');
            Route::get('verwaltung/schickzeiten/edit/{day}/{child}/{parent}', 'SchickzeitenController@editVerwaltung')->middleware('can:edit schickzeiten');
            Route::post('schickzeiten', 'SchickzeitenController@store');
            Route::post('verwaltung/schickzeiten/{parent}', 'SchickzeitenController@storeVerwaltung')->middleware('can:edit schickzeiten');
            Route::delete('schickzeiten/{day}/{child}', 'SchickzeitenController@destroy');
            Route::delete('verwaltung/schickzeiten/{day}/{child}/{parent}', 'SchickzeitenController@destroyVerwaltung')->middleware('can:edit schickzeiten');

            //Krankmeldung
            Route::get('krankmeldung', 'KrankmeldungenController@index');
            Route::post('krankmeldung', 'KrankmeldungenController@store');
            Route::get('krankmeldung/test', 'KrankmeldungenController@dailyReport');

            //Termine
            Route::resource('termin', 'TerminController');

            Route::post('/rueckmeldung/{posts_id}', 'UserRueckmeldungenController@sendRueckmeldung');
            Route::get('/userrueckmeldung/edit/{userRueckmeldungen}', 'UserRueckmeldungenController@edit');
            Route::put('/userrueckmeldung/{userRueckmeldungen}', 'UserRueckmeldungenController@update');

            //Rückmeldungen
            Route::post('/rueckmeldung/{posts_id}/create', 'RueckmeldungenController@store');
            Route::put('/rueckmeldung/{posts_id}/create', 'RueckmeldungenController@update');
            Route::get('rueckmeldungen/{posts}/createImageUpload', 'RueckmeldungenController@createImageRueckmeldung');

            //show posts
            Route::get('/home', 'NachrichtenController@index');
            Route::get('/archiv', 'NachrichtenController@postsArchiv');
            Route::get('/', 'NachrichtenController@index');
            //Route::get('pdf/{archiv?}', 'NachrichtenController@pdf');

            //KioskAnsicht
            //Route::get('kiosk/{bereich?}', 'NachrichtenController@kioskView');
            //Route::get('kiosk/{bereich?}', 'KioskController@kioskView');

            //Terminlisten
            Route::get('listen', 'ListenController@index');
            Route::post('listen', 'ListenController@store');
            Route::get('listen/create', 'ListenController@create');
            Route::get('listen/{terminListe}', 'ListenController@show');
            Route::get('listen/{terminListe}/edit', 'ListenController@edit');
            Route::put('listen/{terminListe}', 'ListenController@update');
            Route::get('listen/{liste}/activate', 'ListenController@activate');
            Route::get('listen/{liste}/deactivate', 'ListenController@deactivate');
            Route::get('listen/{liste}/export', 'ListenController@pdf');
            Route::get('listen/{terminListe}/auswahl', 'ListenController@auswahl');
            Route::post('eintragungen/{liste}/store', 'ListenTerminController@store');
            Route::put('eintragungen/{listen_termine}', 'ListenTerminController@update');
            Route::delete('eintragungen/{listen_termine}', 'ListenTerminController@destroy');
            Route::delete('eintragungen/absagen/{listen_termine}', 'ListenTerminController@absagen');

            //Reinigungsplan
            Route::get('reinigung', 'ReinigungController@index');
            Route::post('reinigung/{Bereich}', 'ReinigungController@store');
            Route::get('reinigung/create/{Bereich}/{Datum}', 'ReinigungController@create');

            //Edit and create posts
            Route::get('/posts/create', 'NachrichtenController@create');
            Route::get('/posts/edit/{posts}', 'NachrichtenController@edit');
            Route::get('/posts/edit/{posts}/{kiosk?}', 'NachrichtenController@edit');
            Route::get('/posts/touch/{posts}', 'NachrichtenController@touch');
            Route::get('/posts/release/{posts}', 'NachrichtenController@release');
            Route::get('/posts/stick/{post}', 'NachrichtenController@stickPost')->middleware(['permission:make sticky']);
            Route::get('/posts/archiv/{posts}', 'NachrichtenController@archiv');
            Route::put('/posts/{posts}/{kiosk?}', 'NachrichtenController@update');
            Route::post('/posts/', 'NachrichtenController@store');

            Route::delete('posts/{posts}', 'NachrichtenController@destroy');
            Route::delete('rueckmeldung/{rueckmeldung}', 'RueckmeldungenController@destroy');

            Route::post('rueckmeldung/{posts}/saveFile', 'FileController@saveFileRueckmeldung');

            //Comment posts
            Route::post('nachricht/{posts}/comment/create', 'NachrichtenController@storeComment');
            Route::get('rueckmeldungen/{rueckmeldungen}/commentable', 'RueckmeldungenController@updateCommentable');

            //user-Verwaltung
            Route::get('/einstellungen', 'BenutzerController@show');
            Route::put('/einstellungen', 'BenutzerController@update');

            //Downloads
            Route::get('/files', 'FileController@index');
            Route::post('/files', 'FileController@store')->middleware(['permission:upload files']);
            Route::get('/files/create', 'FileController@create')->middleware(['permission:upload files']);
            Route::delete('file/{file}', 'FileController@delete');

            //changelog
            Route::resource('changelog', 'ChangelogController');

            //Suche
            Route::post('search', 'SearchController@search');

            //Routen für Benutzerverwaltung

            Route::middleware('permission:edit user|import user')->group(function () {
                Route::get('email/{daily}/{id?}', 'NachrichtenController@email');
                /*             Route::get('email/daily', 'NachrichtenController@emailDaily');
                */

                Route::get('users/import', 'ImportController@importForm')->middleware(['permission:import user']);
                Route::post('users/import', 'ImportController@import')->middleware(['permission:import user']);

                Route::delete('users/{id}', 'UserController@destroy');

                Route::resource('users', 'UserController');
                //Route::get('users/{user}/delete', 'UserController@destroy');
                //Route::get('sendErinnerung', 'RueckmeldungenController@sendErinnerung');
                //Route::get('/daily', 'NachrichtenController@emailDaily');
            });

            //Gruppenverwaltung
            Route::get('/groups', 'GroupsController@index')->middleware(['permission:view groups']);
            Route::post('/groups', 'GroupsController@store')->middleware(['permission:view groups']);

            //Routen zur Rechteverwaltung
            Route::middleware('permission:edit permission')->group(function () {
                Route::get('roles', 'RolesController@edit');
                Route::put('roles', 'RolesController@update');
                Route::post('roles', 'RolesController@store');
                Route::post('roles/permission', 'RolesController@storePermission');
            });

            //Routen zur Rechteverwaltung
            Route::middleware('permission:edit settings')->group(function () {
                Route::get('settings', 'SettingsController@module');
                Route::get('settings/modul/{modul}', 'SettingsController@change_status');
            });

            Route::group(['middlewareGroups' => ['can:loginAsUser']], function () {
                Route::get('showUser/{id}', 'UserController@loginAsUser');
            });

            Route::get('logoutAsUser', function () {
                if (session()->has('ownID')) {
                    \Illuminate\Support\Facades\Auth::loginUsingId(session()->pull('ownID'));
                }

                return redirect(url('/'));
            });

            //Elternratsbereich
            Route::middleware('permission:view elternrat')->group(function () {
                Route::resource('elternrat', 'ElternratController');
                Route::delete('elternrat/file/{file}', 'ElternratController@deleteFile');
                Route::delete('elternrat/comment/{comment}', 'ElternratController@deleteComment');
                Route::get('elternrat/add/file', 'ElternratController@addFile');
                Route::post('elternrat/file', 'ElternratController@storeFile');
                Route::post('beitrag/{discussion}/comment/create', 'ElternratController@storeComment');
                Route::get('elternrat/discussion/create', 'ElternratController@create');
                Route::post('elternrat/discussion', 'ElternratController@store');
                Route::get('elternrat/discussion/edit/{discussion}', 'ElternratController@edit');
                Route::put('elternrat/discussion/{discussion}', 'ElternratController@update');
            });
        });

        //Feedback
        Route::get('feedback', 'FeedbackController@show');
        Route::post('feedback', 'FeedbackController@send');

        Route::get('password/expired', 'Auth\ExpiredPasswordController@expired')
            ->name('password.expired');
        Route::post('password/post_expired', 'Auth\ExpiredPasswordController@postExpired')
            ->name('password.post_expired');
    });
