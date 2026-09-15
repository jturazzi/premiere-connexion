<?php

namespace Tests\Feature;

use App\Ldap\User as LdapUser;
use App\Services\FirstLoginService;
use LdapRecord\Laravel\Testing\DirectoryEmulator;
use Tests\TestCase;

class FirstLoginServiceTest extends TestCase
{
    private FirstLoginService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DirectoryEmulator::setup('default');

        $this->service = new FirstLoginService;
    }

    protected function tearDown(): void
    {
        DirectoryEmulator::tearDown();

        parent::tearDown();
    }

    public function test_finds_eligible_user_by_samaccountname(): void
    {
        LdapUser::create([
            'cn' => 'jdupont',
            'samaccountname' => 'jdupont',
            'premiereconnexion' => 'ABC123',
        ]);

        $user = $this->service->findEligibleUser('jdupont');

        $this->assertNotNull($user);
        $this->assertSame('jdupont', $user->getFirstAttribute('samaccountname'));
    }

    public function test_user_without_premiereconnexion_is_not_eligible(): void
    {
        LdapUser::create([
            'cn' => 'jdupont',
            'samaccountname' => 'jdupont',
        ]);

        $this->assertNull($this->service->findEligibleUser('jdupont'));
    }

    public function test_unknown_user_is_not_eligible(): void
    {
        $this->assertNull($this->service->findEligibleUser('inconnu'));
    }

    public function test_identity_matches_compares_the_premiereconnexion_code(): void
    {
        $user = LdapUser::create([
            'cn' => 'jdupont',
            'samaccountname' => 'jdupont',
            'premiereconnexion' => 'ABC123',
        ]);

        $this->assertTrue($this->service->identityMatches($user, 'ABC123'));
        $this->assertTrue($this->service->identityMatches($user, '  ABC123  '));
        $this->assertFalse($this->service->identityMatches($user, 'WRONG'));
    }

    public function test_identity_never_matches_when_code_is_blank(): void
    {
        $user = LdapUser::create([
            'cn' => 'jdupont',
            'samaccountname' => 'jdupont',
            'premiereconnexion' => '   ',
        ]);

        $this->assertFalse($this->service->identityMatches($user, ''));
        $this->assertFalse($this->service->identityMatches($user, '   '));
    }

    public function test_set_password_updates_password_and_clears_premiereconnexion(): void
    {
        $user = LdapUser::create([
            'cn' => 'jdupont',
            'samaccountname' => 'jdupont',
            'premiereconnexion' => 'ABC123',
        ]);

        $this->service->setPassword($user, 'C0mpl3x!Pass2024');

        $user->refresh();
        $this->assertNull($user->getPremiereConnexion());
        $this->assertFalse($user->premiereConnexionIsSet());
    }
}
