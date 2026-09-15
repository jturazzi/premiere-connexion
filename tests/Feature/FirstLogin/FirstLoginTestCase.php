<?php

namespace Tests\Feature\FirstLogin;

use App\Ldap\User as LdapUser;
use LdapRecord\Laravel\Testing\DirectoryEmulator;
use Tests\TestCase;

abstract class FirstLoginTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DirectoryEmulator::setup('default');
    }

    protected function tearDown(): void
    {
        DirectoryEmulator::tearDown();

        parent::tearDown();
    }

    /**
     * Crée un utilisateur AD éligible à la première connexion (attribut
     * "premiereconnexion" renseigné avec le code de vérification).
     */
    protected function createEligibleUser(string $samaccountname, string $code = 'CODE-1234'): LdapUser
    {
        return LdapUser::create([
            'cn' => $samaccountname,
            'samaccountname' => $samaccountname,
            'premiereconnexion' => $code,
        ]);
    }

    /**
     * Crée un utilisateur AD déjà actif (pas de code de première connexion),
     * donc non éligible au flux.
     */
    protected function createIneligibleUser(string $samaccountname): LdapUser
    {
        return LdapUser::create([
            'cn' => $samaccountname,
            'samaccountname' => $samaccountname,
        ]);
    }

    /**
     * Place la session dans l'état "identifié" pour l'utilisateur donné,
     * permettant d'accéder directement à l'étape de vérification.
     */
    protected function markAsIdentified(string $samaccountname): void
    {
        $this->withSession([
            'first_login.samaccountname' => $samaccountname,
            'first_login.identified' => true,
        ]);
    }

    /**
     * Place la session dans l'état "identité vérifiée" pour l'utilisateur
     * donné, permettant d'accéder directement à l'étape de mot de passe.
     */
    protected function markAsIdentityVerified(string $samaccountname): void
    {
        $this->withSession([
            'first_login.samaccountname' => $samaccountname,
            'first_login.identified' => true,
            'first_login.identity_verified' => true,
        ]);
    }
}
