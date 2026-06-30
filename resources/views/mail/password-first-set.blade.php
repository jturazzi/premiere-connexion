Un utilisateur vient de définir son mot de passe pour la première fois via l'application {{ config('app.name') }}.

Identifiant : {{ $samaccountname }}
Date : {{ $occurredAt->format('d/m/Y H:i:s') }}
Adresse IP : {{ $ip }}

Ce compte ne pourra plus repasser par le flux de première connexion.
