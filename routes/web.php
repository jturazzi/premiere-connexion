<?php

use App\Http\Controllers\FirstLogin\FirstLoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/premiere-connexion');

Route::prefix('premiere-connexion')->name('first-login.')->group(function () {
    Route::get('/', [FirstLoginController::class, 'showIdentify'])->name('identify');
    Route::post('/identifier', [FirstLoginController::class, 'identify'])
        ->middleware('throttle:first-login-identify')
        ->name('identify.submit');

    Route::middleware('first-login.step:identified')->group(function () {
        Route::get('/verification', [FirstLoginController::class, 'showVerify'])->name('verify');
        Route::post('/verifier', [FirstLoginController::class, 'verify'])
            ->middleware('throttle:first-login-verify')
            ->name('verify.submit');
    });

    Route::middleware('first-login.step:identity_verified')->group(function () {
        Route::get('/mot-de-passe', [FirstLoginController::class, 'showPassword'])->name('password');
        Route::post('/mot-de-passe', [FirstLoginController::class, 'setPassword'])
            ->middleware('throttle:first-login-password')
            ->name('password.submit');
    });

    Route::post('/recommencer', [FirstLoginController::class, 'reset'])->name('reset');
});
