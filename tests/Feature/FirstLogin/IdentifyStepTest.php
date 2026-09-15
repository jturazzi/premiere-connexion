<?php

namespace Tests\Feature\FirstLogin;

use Illuminate\Support\Facades\Log;

class IdentifyStepTest extends FirstLoginTestCase
{
    public function test_identify_page_is_displayed(): void
    {
        $response = $this->get(route('first-login.identify'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('FirstLogin/Identify'));
    }

    public function test_eligible_user_is_identified_and_redirected_to_verify_step(): void
    {
        $this->createEligibleUser('jdupont', 'ABC123');

        $response = $this->post(route('first-login.identify.submit'), [
            'samaccountname' => 'jdupont',
        ]);

        $response->assertRedirect(route('first-login.verify'));
        $this->assertSame('jdupont', session('first_login.samaccountname'));
        $this->assertTrue(session('first_login.identified'));
    }

    public function test_user_without_premiereconnexion_attribute_is_rejected(): void
    {
        $this->createIneligibleUser('jdupont');

        Log::shouldReceive('channel')->once()->with('ldap-security')->andReturnSelf();
        Log::shouldReceive('warning')->once()->with(
            'Identifiant non éligible à la première connexion',
            \Mockery::on(fn ($context) => $context['samaccountname'] === 'jdupont')
        );

        $response = $this->post(route('first-login.identify.submit'), [
            'samaccountname' => 'jdupont',
        ]);

        $response->assertSessionHasErrors('samaccountname');
        $this->assertFalse(session()->has('first_login.identified'));
    }

    public function test_unknown_samaccountname_is_rejected(): void
    {
        $response = $this->post(route('first-login.identify.submit'), [
            'samaccountname' => 'inconnu',
        ]);

        $response->assertSessionHasErrors('samaccountname');
        $this->assertFalse(session()->has('first_login.identified'));
    }

    public function test_samaccountname_is_required(): void
    {
        $response = $this->post(route('first-login.identify.submit'), []);

        $response->assertSessionHasErrors('samaccountname');
    }

    public function test_identify_endpoint_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('first-login.identify.submit'), [
                'samaccountname' => 'inconnu',
            ])->assertSessionHasErrors('samaccountname');
        }

        $this->post(route('first-login.identify.submit'), [
            'samaccountname' => 'inconnu',
        ])->assertStatus(429);
    }
}
