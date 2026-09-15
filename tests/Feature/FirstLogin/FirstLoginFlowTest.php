<?php

namespace Tests\Feature\FirstLogin;

class FirstLoginFlowTest extends FirstLoginTestCase
{
    public function test_full_first_login_journey_succeeds_end_to_end(): void
    {
        $user = $this->createEligibleUser('jdupont', 'ABC123');

        $this->post(route('first-login.identify.submit'), [
            'samaccountname' => 'jdupont',
        ])->assertRedirect(route('first-login.verify'));

        $this->post(route('first-login.verify.submit'), [
            'code' => 'ABC123',
        ])->assertRedirect(route('first-login.password'));

        $response = $this->post(route('first-login.password.submit'), [
            'password' => 'C0mpl3x!Pass2024',
            'password_confirmation' => 'C0mpl3x!Pass2024',
        ]);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('FirstLogin/Done'));

        $this->assertFalse(session()->has('first_login'));

        $user->refresh();
        $this->assertNull($user->getPremiereConnexion());
    }

    public function test_reset_clears_progress_and_returns_to_identify_step(): void
    {
        $this->markAsIdentityVerified('jdupont');

        $this->post(route('first-login.reset'))
            ->assertRedirect(route('first-login.identify'));

        $this->assertFalse(session()->has('first_login'));

        // Les étapes suivantes redeviennent inaccessibles.
        $this->get(route('first-login.verify'))->assertRedirect(route('first-login.identify'));
        $this->get(route('first-login.password'))->assertRedirect(route('first-login.identify'));
    }
}
