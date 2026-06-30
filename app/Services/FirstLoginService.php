<?php

namespace App\Services;

use App\Ldap\User;

class FirstLoginService
{
    public function findEligibleUser(string $samaccountname): ?User
    {
        /** @var User|null $user */
        $user = User::query()->where('samaccountname', '=', $samaccountname)->first();

        if (! $user || ! $user->premiereConnexionIsSet()) {
            return null;
        }

        return $user;
    }

    public function identityMatches(User $user, string $code): bool
    {
        $expected = trim((string) $user->getPremiereConnexion());
        $submitted = trim($code);

        return $expected !== '' && hash_equals($expected, $submitted);
    }

    public function setPassword(User $user, string $password): void
    {
        $user->password = $password;
        $user->premiereconnexion = null;
        $user->save();
    }
}
