<?php

namespace App\Http\Controllers\FirstLogin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FirstLogin\IdentifyRequest;
use App\Http\Requests\FirstLogin\SetPasswordRequest;
use App\Http\Requests\FirstLogin\VerifyIdentityRequest;
use App\Services\FirstLoginService;
use App\Support\LdapErrorMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use LdapRecord\LdapRecordException;

class FirstLoginController extends Controller
{
    public function __construct(private FirstLoginService $firstLogin) {}

    public function showIdentify(): Response
    {
        return Inertia::render('FirstLogin/Identify');
    }

    public function identify(IdentifyRequest $request): RedirectResponse
    {
        $samaccountname = $request->validated('samaccountname');

        try {
            $eligible = $this->firstLogin->findEligibleUser($samaccountname);
        } catch (LdapRecordException $exception) {
            return $this->ldapUnavailable($exception, 'samaccountname');
        }

        if (! $eligible) {
            Log::channel('ldap-security')->warning('Identifiant non éligible à la première connexion', [
                'samaccountname' => $samaccountname,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'samaccountname' => 'Ce compte a déjà un mot de passe défini, contactez votre administrateur',
            ]);
        }

        $request->session()->put('first_login.samaccountname', $samaccountname);
        $request->session()->put('first_login.identified', true);

        return to_route('first-login.verify');
    }

    public function showVerify(): Response
    {
        return Inertia::render('FirstLogin/VerifyIdentity');
    }

    public function verify(VerifyIdentityRequest $request): RedirectResponse
    {
        $samaccountname = $request->session()->get('first_login.samaccountname');

        try {
            $user = $this->firstLogin->findEligibleUser($samaccountname);
        } catch (LdapRecordException $exception) {
            return $this->ldapUnavailable($exception, 'code');
        }

        $mismatch = back()->withErrors([
            'code' => 'Les informations saisies ne correspondent pas.',
        ]);

        if (! $user) {
            return $mismatch;
        }

        if (! $this->firstLogin->identityMatches($user, $request->validated('code'))) {
            Log::channel('ldap-security')->warning('Échec de vérification d\'identité (première connexion)', [
                'samaccountname' => $samaccountname,
                'ip' => $request->ip(),
            ]);

            return $mismatch;
        }

        $request->session()->put('first_login.identity_verified', true);

        return to_route('first-login.password');
    }

    public function showPassword(): Response
    {
        return Inertia::render('FirstLogin/SetPassword');
    }

    public function setPassword(SetPasswordRequest $request): RedirectResponse|Response
    {
        $samaccountname = $request->session()->get('first_login.samaccountname');

        try {
            $user = $this->firstLogin->findEligibleUser($samaccountname);
        } catch (LdapRecordException $exception) {
            return $this->ldapUnavailable($exception, 'password');
        }

        if (! $user) {
            return to_route('first-login.identify')->withErrors([
                'samaccountname' => 'Ce compte a déjà un mot de passe défini, contactez votre administrateur',
            ]);
        }

        try {
            $this->firstLogin->setPassword($user, $request->validated('password'));
        } catch (LdapRecordException $exception) {
            return back()->withErrors([
                'password' => LdapErrorMapper::toUserMessage($exception),
            ]);
        }

        $request->session()->forget('first_login');

        return Inertia::render('FirstLogin/Done');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget('first_login');

        return to_route('first-login.identify');
    }

    private function ldapUnavailable(LdapRecordException $exception, string $field): RedirectResponse
    {
        Log::error('Connexion LDAP indisponible lors du flux de première connexion', [
            'message' => $exception->getMessage(),
        ]);

        return back()->withErrors([
            $field => LdapErrorMapper::toUserMessage($exception),
        ]);
    }
}
