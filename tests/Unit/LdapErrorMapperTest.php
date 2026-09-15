<?php

namespace Tests\Unit;

use App\Support\LdapErrorMapper;
use LdapRecord\LdapRecordException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LdapErrorMapperTest extends TestCase
{
    public static function knownErrorCodes(): array
    {
        return [
            'unknown username' => ['525', 'Identifiant inconnu.'],
            'invalid credentials' => ['52e', 'Identifiant ou mot de passe invalide.'],
            'logon time restriction' => ['530', 'Connexion non autorisée à cette heure.'],
            'workstation restriction' => ['531', 'Connexion non autorisée depuis ce poste.'],
            'password expired' => ['532', 'Le mot de passe a expiré.'],
            'account disabled' => ['533', 'Ce compte est désactivé, contactez votre administrateur.'],
            'account expired' => ['701', 'Ce compte a expiré, contactez votre administrateur.'],
            'must reset password' => ['773', 'Le compte doit redéfinir son mot de passe.'],
            'account locked' => ['775', 'Ce compte est verrouillé, contactez votre administrateur.'],
        ];
    }

    #[DataProvider('knownErrorCodes')]
    public function test_known_active_directory_codes_are_mapped_to_french_messages(string $code, string $expected): void
    {
        $exception = new LdapRecordException(
            "80090308: LdapErr: DSID-0C090442, comment: AcceptSecurityContext error, data $code, v3839"
        );

        $this->assertSame($expected, LdapErrorMapper::toUserMessage($exception));
    }

    public function test_code_matching_is_case_insensitive(): void
    {
        $exception = new LdapRecordException('AcceptSecurityContext error, data 52E, v3839');

        $this->assertSame('Identifiant ou mot de passe invalide.', LdapErrorMapper::toUserMessage($exception));
    }

    public function test_unknown_code_falls_back_to_a_generic_message(): void
    {
        $exception = new LdapRecordException('AcceptSecurityContext error, data 999, v3839');

        $this->assertSame(
            "Une erreur est survenue lors de la communication avec l'annuaire. Contactez votre administrateur.",
            LdapErrorMapper::toUserMessage($exception)
        );
    }

    public function test_message_without_a_data_code_falls_back_to_a_generic_message(): void
    {
        $exception = new LdapRecordException("Can't contact LDAP server");

        $this->assertSame(
            "Une erreur est survenue lors de la communication avec l'annuaire. Contactez votre administrateur.",
            LdapErrorMapper::toUserMessage($exception)
        );
    }
}
