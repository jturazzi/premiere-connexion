<?php

namespace Tests\Feature\FirstLogin;

use Illuminate\Support\Facades\Log;

class VerifyIdentityStepTest extends FirstLoginTestCase
{
    public function test_verify_step_is_inaccessible_without_being_identified_first(): void
    {
        $response = $this->get(route('first-login.verify'));

        $response->assertRedirect(route('first-login.identify'));
    }

    public function test_verify_page_is_displayed_once_identified(): void
    {
        $this->markAsIdentified('jdupont');

        $response = $this->get(route('first-login.verify'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('FirstLogin/VerifyIdentity'));
    }

    public function test_correct_code_advances_to_password_step(): void
    {
        $this->createEligibleUser('jdupont', 'ABC123');
        $this->markAsIdentified('jdupont');

        $response = $this->post(route('first-login.verify.submit'), [
            'code' => 'ABC123',
        ]);

        $response->assertRedirect(route('first-login.password'));
        $this->assertTrue(session('first_login.identity_verified'));
    }

    public function test_incorrect_code_is_rejected(): void
    {
        $this->createEligibleUser('jdupont', 'ABC123');
        $this->markAsIdentified('jdupont');

        Log::shouldReceive('channel')->once()->with('ldap-security')->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $response = $this->post(route('first-login.verify.submit'), [
            'code' => 'WRONG-CODE',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse(session()->has('first_login.identity_verified'));
    }

    public function test_user_no_longer_eligible_is_treated_as_a_mismatch(): void
    {
        // L'utilisateur a défini son mot de passe entre l'étape 1 et l'étape 2.
        $this->createIneligibleUser('jdupont');
        $this->markAsIdentified('jdupont');

        $response = $this->post(route('first-login.verify.submit'), [
            'code' => 'ABC123',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse(session()->has('first_login.identity_verified'));
    }

    public function test_code_is_required(): void
    {
        $this->markAsIdentified('jdupont');

        $response = $this->post(route('first-login.verify.submit'), []);

        $response->assertSessionHasErrors('code');
    }

    public function test_verify_endpoint_is_rate_limited_per_ip(): void
    {
        $this->createEligibleUser('jdupont', 'ABC123');
        $this->markAsIdentified('jdupont');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('first-login.verify.submit'), [
                'code' => 'WRONG-CODE',
            ])->assertSessionHasErrors('code');
        }

        $this->post(route('first-login.verify.submit'), [
            'code' => 'WRONG-CODE',
        ])->assertStatus(429);
    }
}
