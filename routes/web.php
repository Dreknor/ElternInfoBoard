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

            Route::post('/rueckmeldung/{posts_id}', 'UserRueckmeldungenController@sendRueckmeldung');
            Route::post('/rueckmeldung/{posts_id}/create', 'RueckmeldungenController@store');
            Route::put('/rueckmeldung/{posts_id}/create', 'RueckmeldungenController@update');

            //show posts
            Route::get('/home/{archiv?}', 'NachrichtenController@index');
            Route::get('/', 'NachrichtenController@index');

            //Edit and create posts
            Route::get('/posts/create', 'NachrichtenController@create');
            Route::get('/posts/edit/{posts}', 'NachrichtenController@edit');
            Route::get('/posts/touch/{posts}', 'NachrichtenController@touch');
            Route::put('/posts/{posts}', 'NachrichtenController@update');
            Route::post('/posts/', 'NachrichtenController@store');


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

            Route::group(['middleware' => ['permission:edit user|import user']], function () {
                Route::resource('users', 'UserController');


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

