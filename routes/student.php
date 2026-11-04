<?php

/*
|--------------------------------------------------------------------------
| Schülerkonten (docs/konzept-schuelerkonten.md)
|--------------------------------------------------------------------------
| Routen mit dem Namenspräfix `student.` sind die einzigen, die ein Schülerkonto erreicht
| (RestrictStudentAccounts). Verwaltungs- und Eltern-Routen heißen deshalb bewusst anders
| (`schuelerkonten.*`, `kinder.schuelerkonto.*`). Alle Routen gibt es nur bei aktivem Modul.
*/

use App\Http\Controllers\Students\ParentStudentAccountController;
use App\Http\Controllers\Students\StudentAccountAdminController;
use App\Http\Controllers\Students\StudentImportController;
use App\Http\Controllers\Students\StudentLoginController;
use App\Http\Controllers\Students\StudentPortalController;
use Illuminate\Support\Facades\Route;

Route::middleware('student.enabled')->group(function () {

    // ── Anmeldung ohne SSO (Gäste) ──────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('schueler/anmelden', [StudentLoginController::class, 'show'])->name('student.login');
        Route::post('schueler/anmelden', [StudentLoginController::class, 'login'])->middleware('throttle:login-student')->name('student.login.store');
    });
    Route::get('schueler/code', [StudentLoginController::class, 'showCode'])->name('student.code');
    Route::post('schueler/code', [StudentLoginController::class, 'redeemCode'])->middleware('throttle:login-student')->name('student.code.store');

    // ── Schüleransicht ──────────────────────────────────────────────────────
    Route::middleware(['auth', 'student'])->prefix('schueler')->name('student.')->group(function () {
        Route::get('/', [StudentPortalController::class, 'home'])->name('home');
        Route::get('nachrichten', [StudentPortalController::class, 'posts'])->name('posts');
        Route::get('nachrichten/{post}', [StudentPortalController::class, 'showPost'])->whereNumber('post')->name('posts.show');
        Route::get('nachrichten/{post}/datei/{media}', [StudentPortalController::class, 'media'])->whereNumber(['post', 'media'])->name('media');
        Route::get('termine', [StudentPortalController::class, 'termine'])->name('termine');
        Route::get('vertretungsplan', [StudentPortalController::class, 'vertretungsplan'])->name('vertretungsplan');
        Route::get('stundenplan', [StudentPortalController::class, 'stundenplan'])->name('stundenplan');
        Route::get('ags', [StudentPortalController::class, 'ags'])->name('ags');
        Route::get('profil', [StudentPortalController::class, 'profile'])->name('profile');
        Route::put('profil/passwort', [StudentPortalController::class, 'updatePassword'])->middleware('throttle:10,1')->name('profile.password');
    });

    // ── Verwaltung (Recht „edit user“, Import „import user“) ────────────────
    Route::middleware(['auth', 'password_expired'])->prefix('verwaltung/schuelerkonten')->name('schuelerkonten.')->group(function () {
        Route::middleware('permission:edit user')->group(function () {
            Route::get('/', [StudentAccountAdminController::class, 'index'])->name('index');
            Route::post('/', [StudentAccountAdminController::class, 'store'])->name('store');
            Route::post('klasse', [StudentAccountAdminController::class, 'storeForClass'])->name('class');
            Route::post('codes', [StudentAccountAdminController::class, 'codes'])->name('codes');
            Route::put('{student}', [StudentAccountAdminController::class, 'update'])->whereNumber('student')->name('update');
            Route::post('{student}/sperren', [StudentAccountAdminController::class, 'deactivate'])->whereNumber('student')->name('deactivate');
            Route::post('{student}/entsperren', [StudentAccountAdminController::class, 'activate'])->whereNumber('student')->name('activate');
            Route::delete('{student}', [StudentAccountAdminController::class, 'destroy'])->whereNumber('student')->name('destroy');
        });
        Route::middleware('permission:import user')->group(function () {
            Route::get('import', [StudentImportController::class, 'show'])->name('import');
            Route::post('import/vorschau', [StudentImportController::class, 'preview'])->name('import.preview');
            Route::post('import/ausfuehren', [StudentImportController::class, 'execute'])->name('import.execute');
        });
        Route::middleware('permission:edit settings')->group(function () {
            Route::get('einstellungen', [StudentAccountAdminController::class, 'settings'])->name('settings');
            Route::put('einstellungen', [StudentAccountAdminController::class, 'updateSettings'])->name('settings.update');
        });
    });

    // ── Eltern: Status, Zustimmung, Codes (§9, S-10) ────────────────────────
    Route::middleware(['auth', 'password_expired'])->prefix('kinder/{child}/schuelerkonto')->whereNumber('child')->name('kinder.schuelerkonto.')->group(function () {
        Route::post('zustimmung', [ParentStudentAccountController::class, 'consent'])->name('consent');
        Route::post('code', [ParentStudentAccountController::class, 'code'])->middleware('throttle:10,1')->name('code');
    });
});
