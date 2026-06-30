<#
.SYNOPSIS
    Crée le compte de service svc-premiereconnexion et délègue les droits
    minimum nécessaires au flux de première connexion (Reset Password +
    écriture de l'attribut custom premiereconnexion), sans donner de droits
    d'administration du domaine.

.NOTES
    À exécuter par un administrateur disposant des droits de création
    d'objets et de modification des ACL sur les OU concernées (Domain Admin,
    ou délégation équivalente), depuis un contrôleur de domaine ou un poste
    avec RSAT (module ActiveDirectory + dsacls.exe) installé.

    Ce script ne peut pas être exécuté depuis le serveur applicatif Laravel :
    il n'a ni connectivité AD, ni les outils RSAT, ni les droits nécessaires.
#>

#Requires -Modules ActiveDirectory

$ErrorActionPreference = 'Stop'

# --- Paramètres à adapter si besoin -----------------------------------------
$Domain          = 'company.lan'
$NetbiosDomain   = 'COMPANY'                 # à confirmer si différent
$DisplayName     = 'svc-test'     # CN / UPN : pas de limite de longueur
# sAMAccountName est limité à 20 caractères (contrainte NetBIOS héritée) ;
# 'svc-premiereconnexion' en fait 21, d'où l'erreur ERROR_INVALID_ACCOUNT_NAME (1315).
$SamAccountName  = 'svc-premconnexion'
$ServiceOuDn     = 'OU=Service Accounts,OU=ADMIN,DC=company,DC=lan'
$TargetUsersOuDn = 'OU=Utilisateurs,DC=company,DC=lan'
$CustomAttribute = 'premiereconnexion'
# -----------------------------------------------------------------------------

# 1. Création du compte (idempotent)
$existing = Get-ADUser -Filter "sAMAccountName -eq '$SamAccountName'" -ErrorAction SilentlyContinue

if ($existing) {
    Write-Host "Le compte $SamAccountName existe déjà ($($existing.DistinguishedName)), création ignorée." -ForegroundColor Yellow
} else {
    $securePassword = Read-Host -AsSecureString "Mot de passe initial pour $SamAccountName (à stocker ensuite dans le coffre/secrets, jamais en clair)"

    New-ADUser `
        -Name $DisplayName `
        -SamAccountName $SamAccountName `
        -UserPrincipalName "$DisplayName@$Domain" `
        -Path $ServiceOuDn `
        -AccountPassword $securePassword `
        -Enabled $true `
        -PasswordNeverExpires $true `
        -CannotChangePassword $true `
        -Description 'Compte de service applicatif - flux de première connexion'

    Write-Host "Compte $SamAccountName créé dans $ServiceOuDn." -ForegroundColor Green
}

# 2. Délégation des droits sur l'OU contenant les comptes utilisateurs réels
#    (PAS sur l'OU Service Accounts : la délégation doit cibler les comptes
#    qui passeront par le flux.
#
#    Droits accordés, strictement nécessaires au flux :
#      - CA "Reset Password" : permet d'écrire unicodePwd sans connaître
#        l'ancien mot de passe (étape 4 du flux)
#      - RP/WP sur l'attribut custom premiereconnexion : lecture du mot de
#        passe de vérification (étape 3) + remise à null après usage (étape 4)
#      - RP sur pwdLastSet, sAMAccountName, cn : lecture utilisée pour le
#        contrôle d'éligibilité (étape 2) ; explicite ici au cas où l'OU a
#        des ACL durcies qui retirent les droits de lecture par défaut
#        d'Authenticated Users.
$trustee = "$NetbiosDomain\$SamAccountName"

$dsaclsArgs = @(
    @('/G', "${trustee}:CA;Reset Password;user"),
    @('/G', "${trustee}:RP;${CustomAttribute};user"),
    @('/G', "${trustee}:WP;${CustomAttribute};user"),
    @('/G', "${trustee}:RP;pwdLastSet;user"),
    @('/G', "${trustee}:RP;sAMAccountName;user"),
    @('/G', "${trustee}:RP;cn;user")
)

foreach ($args in $dsaclsArgs) {
    # /I:S = l'ACE s'applique aux objets User descendants de l'OU, pas à l'OU elle-même
    & dsacls.exe $TargetUsersOuDn '/I:S' $args[0] $args[1]
    if ($LASTEXITCODE -ne 0) {
        throw "Échec dsacls sur $($args[1])"
    }
}

Write-Host "Délégation appliquée sur $TargetUsersOuDn pour $trustee." -ForegroundColor Green

# 3. Vérification
Write-Host "`nVérification des ACE déléguées :" -ForegroundColor Cyan
& dsacls.exe $TargetUsersOuDn | Select-String -SimpleMatch $SamAccountName

Write-Host "`nCompte AD prêt. Valeurs à reporter dans le .env de l'application :" -ForegroundColor Cyan
Write-Host "  LDAP_USERNAME=$DisplayName@$Domain"
Write-Host "  LDAP_BASE_DN=DC=company,DC=lan"
Write-Host "  LDAP_HOST=<adresse de votre contrôleur de domaine>"
Write-Host "  LDAP_PASSWORD=<le mot de passe saisi ci-dessus, stocké dans le coffre secrets>"
