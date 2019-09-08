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
        Route::get('/home', 'Nachrichtencontroller@index');
        Route::get('/', 'NachrichtenController@index');
        Route::get('/posts/create', 'NachrichtenController@create');
        Route::post('/posts/', 'NachrichtenController@store');
});


