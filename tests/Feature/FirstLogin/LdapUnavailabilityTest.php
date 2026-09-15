<?php

namespace Tests\Feature\FirstLogin;

use Illuminate\Support\Facades\Log;
use LdapRecord\LdapRecordException;
use LdapRecord\Testing\DirectoryFake;
use LdapRecord\Testing\LdapFake;
use Tests\TestCase;

/**
 * Ces tests simulent un annuaire LDAP totalement injoignable (recherche en
 * échec) sans passer par le DirectoryEmulator, qui court-circuite les appels
 * LDAP bruts et ne permet donc pas de simuler une panne de connexion.
 */
class LdapUnavailabilityTest extends TestCase
{
    protected function tearDown(): void
    {
        DirectoryFake::tearDown();

        parent::tearDown();
    }

    private function fakeUnreachableDirectory(): void
    {
        DirectoryFake::setup('default')->getLdapConnection()->expect(
            LdapFake::operation('search')->andThrow(
                new LdapRecordException("Can't contact LDAP server")
            )
        );
    }

    public function test_identify_step_reports_a_friendly_error_when_ldap_is_unreachable(): void
    {
        $this->fakeUnreachableDirectory();

        Log::shouldReceive('error')->once();

        $response = $this->post(route('first-login.identify.submit'), [
            'samaccountname' => 'jdupont',
        ]);

        $response->assertSessionHasErrors('samaccountname');
        $this->assertFalse(session()->has('first_login.identified'));
    }

    public function test_verify_step_reports_a_friendly_error_when_ldap_is_unreachable(): void
    {
        $this->fakeUnreachableDirectory();

        $this->withSession([
            'first_login.samaccountname' => 'jdupont',
            'first_login.identified' => true,
        ]);

        Log::shouldReceive('error')->once();

        $response = $this->post(route('first-login.verify.submit'), [
            'code' => 'ABC123',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse(session()->has('first_login.identity_verified'));
    }

    public function test_password_step_reports_a_friendly_error_when_ldap_is_unreachable(): void
    {
        $this->fakeUnreachableDirectory();

        $this->withSession([
            'first_login.samaccountname' => 'jdupont',
            'first_login.identified' => true,
            'first_login.identity_verified' => true,
        ]);

        Log::shouldReceive('error')->once();

        $response = $this->post(route('first-login.password.submit'), [
            'password' => 'C0mpl3x!Pass2024',
            'password_confirmation' => 'C0mpl3x!Pass2024',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
