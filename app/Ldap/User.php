<?php

namespace App\Ldap;

use LdapRecord\Models\ActiveDirectory\User as ActiveDirectoryUser;

class User extends ActiveDirectoryUser
{
    public function getPremiereConnexion(): ?string
    {
        return $this->getFirstAttribute('premiereconnexion');
    }

    public function premiereConnexionIsSet(): bool
    {
        return trim((string) $this->getPremiereConnexion()) !== '';
    }
}
