<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFirstLoginStepCompleted
{
    /**
     * @param  'identified'|'identity_verified'  $sessionKey  clé attendue en session pour accéder à cette étape
     */
    public function handle(Request $request, Closure $next, string $sessionKey): Response
    {
        if (! $request->session()->get("first_login.{$sessionKey}")) {
            return redirect()->route('first-login.identify');
        }

        return $next($request);
    }
}
