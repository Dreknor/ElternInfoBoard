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

Route::group([
    'middleware' => ['auth'],
],
    function () {

        Route::middleware(['password_expired'])->group(function () {

            //Termine
            Route::resource('termin', 'TerminController');

            Route::post('/rueckmeldung/{posts_id}', 'UserRueckmeldungenController@sendRueckmeldung');
            Route::get('/userrueckmeldung/edit/{userRueckmeldungen}', 'UserRueckmeldungenController@edit');
            Route::put('/userrueckmeldung/{userRueckmeldungen}', 'UserRueckmeldungenController@update');

            Route::post('/rueckmeldung/{posts_id}/create', 'RueckmeldungenController@store');
            Route::put('/rueckmeldung/{posts_id}/create', 'RueckmeldungenController@update');

            //show posts
            Route::get('/home/{archiv?}', 'NachrichtenController@index');
            Route::get('/', 'NachrichtenController@index');
            Route::get('pdf/{archiv?}', 'NachrichtenController@pdf');

            //Terminlisten
            Route::get('listen', 'ListenController@index');
            Route::post('listen', 'ListenController@store');
            Route::get('listen/create', 'ListenController@create');
            Route::get('listen/{terminListe}', 'ListenController@show');
            Route::get('listen/{terminListe}/auswahl', 'ListenController@auswahl');
            Route::post('eintragungen/{liste}/store', 'ListenTerminController@store');
            Route::put('eintragungen/{listen_termine}', 'ListenTerminController@update');
            Route::delete('eintragungen/{listen_termine}', 'ListenTerminController@destroy');

            //Reinigungsplan
            Route::get('reinigung', 'ReinigungController@index');
            Route::post('reinigung/{Bereich}', 'ReinigungController@store');
            Route::get('reinigung/create/{Bereich}/{Datum}', 'ReinigungController@create');

            //Edit and create posts
            Route::get('/posts/create', 'NachrichtenController@create');
            Route::get('/posts/edit/{posts}', 'NachrichtenController@edit');
            Route::get('/posts/touch/{posts}', 'NachrichtenController@touch');
            Route::get('/posts/release/{posts}', 'NachrichtenController@release');
            Route::put('/posts/{posts}', 'NachrichtenController@update');
            Route::post('/posts/', 'NachrichtenController@store');

            Route::delete('posts/{posts}', 'NachrichtenController@destroy');
            Route::delete("rueckmeldung/{rueckmeldung}", "RueckmeldungenController@destroy");
            //user-Verwaltung
            Route::get('/einstellungen', 'BenutzerController@show');
            Route::put('/einstellungen', 'BenutzerController@update');

            //Downloads
            Route::get('/files', 'FileController@index');
            Route::post('/files', 'FileController@store')->middleware(['permission:upload files']);
            Route::get('/files/create', 'FileController@create')->middleware(['permission:upload files']);
            Route::delete('file/{file}', 'FileController@delete');

/*
            Route::get('email/weekly', 'NachrichtenController@email');
            Route::get('email/daily', 'NachrichtenController@emailDaily');
*/
            //Routen für Benutzerverwaltung



            Route::get('users/import', 'ImportController@importForm')->middleware(['permission:import user']);
            Route::post('users/import', 'ImportController@import')->middleware(['permission:import user']);

            Route::delete("users/{id}", "UserController@destroy");


            //Suche
            Route::post('search','SearchController@search');

            Route::group(['middleware' => ['permission:edit user|import user']], function () {
                Route::resource('users', 'UserController');
                //Route::get('/daily', 'NachrichtenController@emailDaily');
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

