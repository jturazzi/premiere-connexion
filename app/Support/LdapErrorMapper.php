<?php

namespace App\Support;

use LdapRecord\LdapRecordException;

class LdapErrorMapper
{
    /**
     * Messages mappés depuis les codes d'erreur étendus AD (extrait du
     * "data XX" présent dans le message d'erreur LDAP, ex: 52e, 532, 533...).
     *
     * @see https://ldapwiki.com/wiki/Common%20Active%20Directory%20Bind%20Errors
     */
    private const MESSAGES = [
        '525' => 'Identifiant inconnu.',
        '52e' => 'Identifiant ou mot de passe invalide.',
        '530' => 'Connexion non autorisée à cette heure.',
        '531' => 'Connexion non autorisée depuis ce poste.',
        '532' => 'Le mot de passe a expiré.',
        '533' => 'Ce compte est désactivé, contactez votre administrateur.',
        '701' => 'Ce compte a expiré, contactez votre administrateur.',
        '773' => 'Le compte doit redéfinir son mot de passe.',
        '775' => 'Ce compte est verrouillé, contactez votre administrateur.',
    ];

    public static function toUserMessage(LdapRecordException $exception): string
    {
        $code = static::extractDataCode($exception->getMessage());

        return $code !== null && isset(static::MESSAGES[$code])
            ? static::MESSAGES[$code]
            : 'Une erreur est survenue lors de la communication avec l\'annuaire. Contactez votre administrateur.';
    }

    private static function extractDataCode(string $message): ?string
    {
        if (preg_match('/data\s+([0-9a-f]+)/i', $message, $matches)) {
            return strtolower($matches[1]);
        }

        return null;
    }
}
