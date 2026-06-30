<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Limite la recherche d'identifiant (étape 1) pour empêcher l'énumération
        // de comptes par brute force (aucun identifiant n'est encore connu en
        // session à ce stade, on ne peut limiter que par IP).
        RateLimiter::for('first-login-identify', function ($request) {
            return [
                Limit::perMinute(10)->by('first-login-identify:'.$request->ip()),
                Limit::perHour(50)->by('first-login-identify:'.$request->ip()),
            ];
        });

        // Limite la vérification du mot de passe de première connexion (étape 3)
        // pour empêcher de le deviner par brute force.
        RateLimiter::for('first-login-verify', function ($request) {
            return [
                Limit::perMinute(5)->by('first-login-verify:'.$request->ip()),
                Limit::perHour(20)->by('first-login-verify:'.$request->session()->get('first_login.samaccountname')),
            ];
        });

        // Limite la définition du mot de passe final (étape 4) par mesure de
        // défense en profondeur, même si l'accès est déjà conditionné aux
        // étapes précédentes.
        RateLimiter::for('first-login-password', function ($request) {
            return [
                Limit::perMinute(10)->by('first-login-password:'.$request->ip()),
                Limit::perHour(30)->by('first-login-password:'.$request->session()->get('first_login.samaccountname')),
            ];
        });
    }
}
