<?php

/*
|--------------------------------------------------------------------------
| App-API v1 (Eltern-App)
|--------------------------------------------------------------------------
| Präfix /api/v1, Routennamen api.v1.*. Einheitliches Format { data, meta? } bzw. { message }.
| Schreibende Aufrufe sind über den Header `Idempotency-Key` wiederholbar, GET-Antworten tragen ein ETag.
*/

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\CommunityController;
use App\Http\Controllers\API\V1\DashboardController;
use App\Http\Controllers\API\V1\FeedbackController;
use App\Http\Controllers\API\V1\InstanceController;
use App\Http\Controllers\API\V1\ListenController;
use App\Http\Controllers\API\V1\MeController;
use App\Http\Controllers\API\V1\MessengerController;
use App\Http\Controllers\API\V1\NotificationController;
use App\Http\Controllers\API\V1\ParentController;
use App\Http\Controllers\API\V1\PostController;
use App\Http\Controllers\API\V1\TerminController;
use Illuminate\Support\Facades\Route;

// ── Ohne Anmeldung ───────────────────────────────────────────────────────────
Route::get('instance', [InstanceController::class, 'show'])->name('instance');

Route::middleware('throttle:login')->group(function () {
    Route::post('token', [AuthController::class, 'login'])->name('token');
    Route::post('auth/magic-link', [AuthController::class, 'magicLink'])->name('auth.magic');
    Route::post('auth/exchange', [AuthController::class, 'exchange'])->name('auth.exchange');
    Route::get('auth/sso/start', [AuthController::class, 'ssoStart'])->name('auth.sso.start');
    Route::get('auth/sso/callback', [AuthController::class, 'ssoCallback'])->name('auth.sso.callback');
});

// ── Angemeldet ───────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'api.password', 'idempotency', 'etag'])->group(function () {

    // Sitzung & Profil
    Route::get('bootstrap', [MeController::class, 'bootstrap'])->name('bootstrap');
    Route::post('token/refresh', [AuthController::class, 'refresh'])->name('token.refresh');
    Route::post('token/logout', [AuthController::class, 'logout'])->name('token.logout');
    Route::post('auth/web-link', [AuthController::class, 'webLink'])->name('auth.web-link');
    Route::get('me', [MeController::class, 'show'])->name('me');
    Route::patch('me', [MeController::class, 'update'])->name('me.update');
    Route::put('me/password', [MeController::class, 'password'])->name('me.password');
    Route::get('me/tokens', [MeController::class, 'tokens'])->name('me.tokens');
    Route::delete('me/tokens/{id}', [MeController::class, 'destroyToken'])->whereNumber('id')->name('me.tokens.destroy');
    Route::get('me/datenschutz', [MeController::class, 'datenschutz'])->name('me.datenschutz');
    Route::post('devices', [MeController::class, 'storeDevice'])->name('devices.store');
    Route::delete('devices/{token}', [MeController::class, 'destroyDevice'])->where('token', '.+')->name('devices.destroy');

    // Start & Aufgaben
    Route::get('parent/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('parent/todo', [DashboardController::class, 'todo'])->name('todo');

    // Benachrichtigungen
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->whereNumber('id')->name('notifications.read');

    // Nachrichten
    Route::get('posts', [PostController::class, 'index'])->name('posts');
    Route::get('posts/{post}', [PostController::class, 'show'])->whereNumber('post')->name('posts.show');
    Route::get('posts/{post}/pdf', [PostController::class, 'pdf'])->name('posts.pdf');
    Route::post('posts/{post}/read', [PostController::class, 'read'])->name('posts.read');
    Route::post('posts/{post}/reactions', [PostController::class, 'react'])->name('posts.react');
    Route::delete('posts/{post}/reactions', [PostController::class, 'unreact'])->name('posts.unreact');
    Route::post('posts/{post}/report', [PostController::class, 'report'])->name('posts.report');
    Route::post('posts/{post}/feedback', [FeedbackController::class, 'storeText'])->name('feedback.store');
    Route::put('feedback/{response}', [FeedbackController::class, 'updateText'])->name('feedback.update');
    Route::post('posts/{post}/abfrage', [FeedbackController::class, 'storeAbfrage'])->name('abfrage.store');
    Route::put('posts/{post}/abfrage/{response}', [FeedbackController::class, 'updateAbfrage'])->whereNumber('response')->name('abfrage.update');
    Route::post('posts/{post}/images', [FeedbackController::class, 'storeImages'])->name('images.store');
    Route::delete('posts/{post}/images/{media}', [FeedbackController::class, 'destroyImage'])->name('images.destroy');
    Route::get('posts/{post}/poll', [FeedbackController::class, 'poll'])->name('poll');
    Route::post('posts/{post}/poll/vote', [FeedbackController::class, 'vote'])->name('poll.vote');
    Route::get('posts/{post}/comments', [FeedbackController::class, 'comments'])->name('comments');
    Route::post('posts/{post}/comments', [FeedbackController::class, 'storeComment'])->name('comments.store');
    Route::delete('comments/{comment}', [FeedbackController::class, 'destroyComment'])->name('comments.destroy');

    // Termine & Listen
    Route::get('termine', [TerminController::class, 'index'])->name('termine');
    Route::get('listen', [ListenController::class, 'index'])->name('listen');
    Route::get('listen/{liste}', [ListenController::class, 'show'])->whereNumber('liste')->name('listen.show');
    Route::post('listen/termine/{termin}/reservation', [ListenController::class, 'reserveTermin'])->name('listen.termin.reserve');
    Route::delete('listen/termine/{termin}/reservation', [ListenController::class, 'cancelTermin'])->name('listen.termin.cancel');
    Route::post('listen/{liste}/eintraege', [ListenController::class, 'addEintrag'])->name('listen.eintrag.add');
    Route::post('listen/eintraege/{eintrag}/reservation', [ListenController::class, 'reserveEintrag'])->name('listen.eintrag.reserve');
    Route::delete('listen/eintraege/{eintrag}/reservation', [ListenController::class, 'cancelEintrag'])->name('listen.eintrag.cancel');

    // Familie (ergänzt die bestehenden /api/parent/*-Endpunkte)
    Route::post('parent/krankmeldungen', [ParentController::class, 'storeKrankmeldung'])->name('krankmeldungen.store');
    Route::patch('parent/children/{child}', [ParentController::class, 'updateChild'])->name('children.update');
    Route::get('parent/children/{child}/stundenplan', [ParentController::class, 'stundenplan'])->name('children.stundenplan');
    Route::get('parent/ags', [ParentController::class, 'ags'])->name('ags');
    Route::post('parent/ags/{ag}/enrollments', [ParentController::class, 'enroll'])->name('ags.enroll');
    Route::get('parent/reinigung', [ParentController::class, 'reinigung'])->name('reinigung');

    // Infoseiten & Elternrat
    Route::get('sites', [CommunityController::class, 'sites'])->name('sites');
    Route::get('sites/{site}', [CommunityController::class, 'site'])->whereNumber('site')->name('sites.show');
    Route::get('elternrat', [CommunityController::class, 'elternrat'])->name('elternrat');
    Route::get('elternrat/discussions/{discussion}', [CommunityController::class, 'discussion'])->name('elternrat.discussion');
    Route::post('elternrat/discussions/{discussion}/comments', [CommunityController::class, 'commentDiscussion'])->name('elternrat.comment');
    Route::post('elternrat/events/{event}/attendance', [CommunityController::class, 'attendance'])->name('elternrat.attendance');

    // Messenger
    Route::middleware('permission:use messenger')->prefix('messenger')->name('messenger.')->group(function () {
        Route::get('conversations', [MessengerController::class, 'conversations'])->name('conversations');
        Route::get('unread-count', [MessengerController::class, 'unreadCount'])->name('unread');
        Route::get('users', [MessengerController::class, 'searchUsers'])->name('users');
        Route::post('direct/{target}', [MessengerController::class, 'startDirect'])->name('direct');
        Route::get('conversations/{conversation}/messages', [MessengerController::class, 'messages'])->name('messages');
        Route::post('conversations/{conversation}/messages', [MessengerController::class, 'send'])->name('send');
        Route::post('conversations/{conversation}/mute', [MessengerController::class, 'mute'])->name('mute');
        Route::put('messages/{message}', [MessengerController::class, 'update'])->name('update');
        Route::delete('messages/{message}', [MessengerController::class, 'destroy'])->name('destroy');
        Route::post('messages/{message}/report', [MessengerController::class, 'report'])->name('report');
        Route::get('messages/{message}/attachment', [MessengerController::class, 'attachment'])->name('attachment');
    });
});
