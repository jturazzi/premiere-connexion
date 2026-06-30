# Première connexion

Application Laravel + Inertia.js + Vue 3 permettant à un utilisateur Active Directory de définir son mot de passe **la toute première fois** (compte créé par un administrateur, mot de passe jamais positionné par l'utilisateur).

Ce n'est **pas** un flux de réinitialisation de mot de passe oublié : un compte qui a déjà défini son mot de passe ne peut pas repasser par ce flux.

## Sommaire

- [Flux fonctionnel](#flux-fonctionnel)
- [Stack technique](#stack-technique)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Déploiement Docker](#déploiement-docker)
- [Configuration LDAP](#configuration-ldap)
- [Lancer l'application](#lancer-lapplication)
- [Tests](#tests)
- [Structure du projet](#structure-du-projet)
- [Sécurité](#sécurité)

## Flux fonctionnel

| Étape | Route | Description |
|---|---|---|
| 1. Identification | `GET/POST /premiere-connexion` | L'utilisateur saisit son identifiant AD (`samaccountname`). |
| 2. Contrôle d'éligibilité | — | Le compte n'est éligible que si l'attribut AD custom `premiereconnexion` est renseigné. Sinon : *"Ce compte a déjà un mot de passe défini, contactez votre administrateur"*. |
| 3. Vérification d'identité | `GET/POST /premiere-connexion/verification` | L'utilisateur saisit le mot de passe de vérification à usage unique communiqué par l'administrateur. Il est comparé (en chaîne stricte, `hash_equals`) à l'attribut `premiereconnexion`. Étape protégée par rate-limiting (5 tentatives/minute par IP, 20/heure par identifiant). |
| 4. Définition du mot de passe | `GET/POST /premiere-connexion/mot-de-passe` | L'utilisateur choisit son mot de passe définitif (12 caractères min., majuscule, minuscule, chiffre, symbole — avec retour visuel en temps réel). Il est écrit dans l'attribut LDAP `unicodePwd` via le compte de service. |
| 5. Terminé | — | Confirmation. L'attribut `premiereconnexion` est remis à `null` : le compte ne peut plus repasser par ce flux. |

La progression entre les étapes est protégée côté serveur par le middleware `EnsureFirstLoginStepCompleted` (`app/Http/Middleware/EnsureFirstLoginStepCompleted.php`), qui s'appuie sur des flags en session — impossible d'accéder directement à `/mot-de-passe` sans avoir réussi les étapes précédentes.

## Stack technique

- **Backend** : Laravel 13 (PHP ^8.3)
- **Frontend** : Inertia.js + Vue 3 (Composition API, `<script setup>`)
- **CSS** : Tailwind CSS v4
- **Annuaire** : Active Directory via [`directorytree/ldaprecord-laravel`](https://ldaprecord.com), connexion **LDAPS obligatoire** (port 636)
- **Build** : Vite

## Prérequis

- PHP 8.3+
- Composer
- Node.js 20+ / npm
- Un annuaire Active Directory joignable en LDAPS, avec :
  - un compte de service dédié (voir [Configuration LDAP](#configuration-ldap))
  - l'attribut custom `premiereconnexion` créé dans le schéma AD

## Installation

Pour du développement local (sans Docker — voir [Déploiement Docker](#déploiement-docker) pour la production) :

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate
```

`SESSION_DRIVER`, `CACHE_STORE` et `QUEUE_CONNECTION` sont configurés sur `database` par défaut : `php artisan migrate` crée les tables nécessaires (sessions, cache, jobs).

## Déploiement Docker

Une image prête à l'emploi est publiée sur `ghcr.io/jturazzi/premiere-connexion`, avec un [`docker-compose.yml`](docker-compose.yml) fourni à la racine du projet.

```bash
docker compose up -d
docker compose exec premiere-connexion php artisan migrate
```

Avant de lancer, complétez les variables d'environnement dans `docker-compose.yml` :

- `APP_KEY` — à générer une fois (`php artisan key:generate --show`) puis à coller telle quelle
- `APP_URL` — l'URL publique réelle de l'application
- le bloc `MAIL_*` (voir [Sécurité](#sécurité) pour le rôle de `MAIL_DESTINATION`)
- le bloc `LDAP_*` (voir [Configuration LDAP](#configuration-ldap) ci-dessous)

Le conteneur :
- sert l'application sur le port `8080` via FrankenPHP
- expose un healthcheck sur `/up`
- persiste `storage/` (logs, cache de vues...) dans le volume nommé `storage-data`

## Configuration LDAP

La connexion LDAP est définie dans [`config/ldap.php`](config/ldap.php) et pilotée par les variables d'environnement suivantes (voir `.env.example`) :

```env
LDAP_CONNECTION=default
LDAP_HOST=votredc.company.lan
LDAP_USERNAME="svc-premiereconnexion@company.lan"
LDAP_PASSWORD=
LDAP_PORT=636
LDAP_BASE_DN="DC=company,DC=lan"
LDAP_TIMEOUT=5
LDAP_USE_LDAPS=true
LDAP_STARTTLS=false
LDAP_SASL=false
LDAP_LOGGING=true
```

⚠️ `LDAP_USE_LDAPS` doit rester à `true` et `allow_insecure_password_changes` à `false` (déjà figé dans `config/ldap.php`) : Active Directory refuse toute écriture de mot de passe en LDAP non chiffré.

### Compte de service AD

Le compte utilisé par `LDAP_USERNAME` ne doit **pas** être administrateur du domaine. Il a uniquement besoin, sur l'OU contenant les comptes utilisateurs concernés :

- du droit étendu **Reset Password** (écrire `unicodePwd` sans connaître l'ancien mot de passe)
- de la lecture/écriture de l'attribut custom `premiereconnexion`
- de la lecture de `sAMAccountName` et `cn`

Un script PowerShell prêt à l'emploi (création du compte + délégation) est fourni dans [`docs/ad/create-svc-premiereconnexion.ps1`](docs/ad/create-svc-premiereconnexion.ps1), à exécuter par un administrateur AD depuis un contrôleur de domaine ou un poste RSAT.

## Lancer l'application

```bash
composer run dev
```

Cette commande (définie dans `composer.json`) lance en parallèle le serveur Laravel, la queue, les logs (`pail`) et Vite en mode watch. Pour les lancer séparément :

```bash
php artisan serve
npm run dev
```

L'application est accessible sur `/premiere-connexion` (la racine `/` y redirige).

## Tests

```bash
php artisan test
```

Les tests LDAP utilisent [`DirectoryEmulator`](https://ldaprecord.com/docs/laravel/v3/testing/) (annuaire en mémoire, aucune connexion réelle nécessaire) :

- `tests/Feature/FirstLoginFlowTest.php` — flux complet bout en bout, contrôle d'éligibilité, rejet d'un mauvais code de vérification
- `tests/Unit/FirstLoginServiceTest.php` — comparaison du code de vérification
- `tests/Unit/SetPasswordRequestTest.php` — règles de complexité du mot de passe
- `tests/Unit/LdapErrorMapperTest.php` — traduction des codes d'erreur AD en messages utilisateur

## Structure du projet

```
app/
  Http/
    Controllers/FirstLogin/FirstLoginController.php   # les 4 étapes du flux
    Requests/FirstLogin/                               # validation par étape
    Middleware/EnsureFirstLoginStepCompleted.php        # garde-fou anti-saut d'étape
  Ldap/User.php                                         # modèle LdapRecord (attribut premiereconnexion)
  Services/FirstLoginService.php                        # éligibilité, vérification, écriture du mot de passe
  Support/LdapErrorMapper.php                            # codes d'erreur AD -> messages utilisateur
resources/js/
  Components/                                           # FirstLoginLayout, Stepper, TextInput, PrimaryButton...
  Pages/FirstLogin/                                      # Identify, VerifyIdentity, SetPassword, Done
docs/ad/                                                 # runbooks d'administration AD (hors périmètre applicatif)
```

## Sécurité

- Connexion AD exclusivement en LDAPS (port 636), jamais en clair
- Comparaison du code de vérification en temps constant (`hash_equals`)
- Rate-limiting dédié sur l'étape de vérification d'identité (`AppServiceProvider`)
- Tentatives échouées de vérification d'identité journalisées sur le canal `ldap-security`
- Le compte de service AD n'a que des droits délégués minimaux (pas d'administration du domaine)
- L'attribut `premiereconnexion` est effacé après usage : un compte ne peut traverser ce flux qu'une seule fois
