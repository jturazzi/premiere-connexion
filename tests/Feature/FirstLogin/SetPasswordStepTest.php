<?php

namespace Tests\Feature\FirstLogin;

use LdapRecord\Container;

class SetPasswordStepTest extends FirstLoginTestCase
{
    private const VALID_PASSWORD = 'C0mpl3x!Pass2024';

    public function test_password_step_is_inaccessible_without_verified_identity(): void
    {
        $response = $this->get(route('first-login.password'));

        $response->assertRedirect(route('first-login.identify'));
    }

    public function test_password_step_is_inaccessible_when_only_identified(): void
    {
        $this->markAsIdentified('jdupont');

        $response = $this->get(route('first-login.password'));

        $response->assertRedirect(route('first-login.identify'));
    }

    public function test_password_page_is_displayed_once_identity_is_verified(): void
    {
        $this->markAsIdentityVerified('jdupont');

        $response = $this->get(route('first-login.password'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('FirstLogin/SetPassword'));
    }

    public function test_valid_password_completes_the_first_login_flow(): void
    {
        $user = $this->createEligibleUser('jdupont', 'ABC123');
        $this->markAsIdentityVerified('jdupont');

        $response = $this->post(route('first-login.password.submit'), [
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('FirstLogin/Done'));

        $this->assertFalse(session()->has('first_login'));

        $user->refresh();
        $this->assertNull($user->getPremiereConnexion());
        $this->assertNotEmpty($user->getFirstAttribute('unicodepwd'));
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->createEligibleUser('jdupont', 'ABC123');
        $this->markAsIdentityVerified('jdupont');

        $response = $this->post(route('first-login.password.submit'), [
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => 'Different-1234!',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(session('first_login.identity_verified'));
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->createEligibleUser('jdupont', 'ABC123');
        $this->markAsIdentityVerified('jdupont');

        $response = $this->post(route('first-login.password.submit'), [
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_password_is_required(): void
    {
        $this->markAsIdentityVerified('jdupont');

        $response = $this->post(route('first-login.password.submit'), []);

        $response->assertSessionHasErrors('password');
    }

    public function test_user_no_longer_eligible_redirects_back_to_identify_step(): void
    {
        // Le compte a déjà été traité entre-temps (ex : deux onglets ouverts).
        $this->createIneligibleUser('jdupont');
        $this->markAsIdentityVerified('jdupont');

        $response = $this->post(route('first-login.password.submit'), [
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
        ]);

        $response->assertRedirect(route('first-login.identify'));
        $response->assertSessionHasErrors('samaccountname');
    }

    public function test_ldap_error_while_saving_is_mapped_to_a_friendly_message(): void
    {
        $this->createEligibleUser('jdupont', 'ABC123');
        $this->markAsIdentityVerified('jdupont');

        // Simule une connexion non sécurisée : l'écriture du mot de passe
        // doit alors être refusée par LdapRecord (protection intégrée).
        Container::getConnection('default')->getLdapConnection()->setTLS(false);

        $response = $this->post(route('first-login.password.submit'), [
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
        ]);

        $response->assertSessionHasErrors([
            'password' => "Une erreur est survenue lors de la communication avec l'annuaire. Contactez votre administrateur.",
        ]);
        $this->assertTrue(session('first_login.identity_verified'));
    }

    public function test_password_endpoint_is_rate_limited_per_ip(): void
    {
        $this->markAsIdentityVerified('jdupont');

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('first-login.password.submit'), [])
                ->assertSessionHasErrors('password');
        }

        $this->post(route('first-login.password.submit'), [])
            ->assertStatus(429);
    }
}
