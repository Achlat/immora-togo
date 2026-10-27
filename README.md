# Immora — Gestion immobilière

Application web de gestion locative pour les agences d'Afrique francophone :
biens et lots, propriétaires et mandats, baux (caution, avance, prorata), échéances,
encaissements (espèces, Mobile Money, virement, chèque), quittances PDF avec QR code,
caisse, dépenses, relevés de gérance et reversements, portails locataire et propriétaire.
Montants en FCFA, base de données MySQL. Référence fonctionnelle : `docs/Immora-Cahier-des-charges.pdf`.

## Démarrage en local (XAMPP)

Prérequis : PHP 8.2+ (extensions `pdo_mysql`, `gd`, `intl`, `mbstring`), Composer, Node 20+, MySQL 8 / MariaDB 10.4+.

```bash
composer install
npm install && npm run build
cp .env.example .env        # puis renseigner DB_DATABASE=immora, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate --seed  # crée les offres + l'agence de démonstration « Horizon Immobilier »
php artisan serve --port=8090
```

Ouvrir http://127.0.0.1:8090

### Comptes de démonstration

| Rôle | Identifiant | Mot de passe |
|------|-------------|--------------|
| Administratrice d'agence | `awa@horizon-immo.ci` | `Horizon-Demo-2026` |
| Gestionnaire | `koffi@horizon-immo.ci` | `Horizon-Demo-2026` |
| Comptable | `mariam@horizon-immo.ci` | `Horizon-Demo-2026` |
| Locataire (code SMS) | `07 07 07 07 07` | — |
| Propriétaire (code SMS) | `+33 6 12 34 56 78` | — |
| Super-admin éditeur | `admin@immora.app` | `Immora-Admin-2026` |

En mode démonstration (`IMMORA_SMS_DRIVER=log`), aucun SMS n'est envoyé : le code de connexion s'affiche
à l'écran et tous les messages sont écrits dans `storage/logs/laravel.log`.

## Tâches automatiques

Ajouter au planificateur du serveur (cron) : `* * * * * php /chemin/artisan schedule:run`

| Commande | Quand | Rôle |
|----------|-------|------|
| `immora:invoices` | le 25 à 6 h | Génère les échéances du mois suivant |
| `immora:penalties` | chaque jour à 6 h 30 | Applique les pénalités de retard |
| `immora:payments-check` | toutes les 15 min | Vérifie les paiements en ligne en attente |
| `immora:super-admin email@domaine` | à la main | Crée le compte de l'éditeur |

## Mise en production

1. `.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `SESSION_SECURE_COOKIE=true`.
2. `php artisan migrate --force && php artisan db:seed --class=PlanSeeder --force` (sans données de démonstration).
3. `php artisan immora:super-admin vous@immora.app`.
4. `php artisan config:cache route:cache view:cache` et `npm run build`.
5. Paiement en ligne : `IMMORA_PAYMENT_DRIVER=cinetpay` et les clés `CINETPAY_*` du compte marchand.
6. SMS : remplacer `LogSmsSender` par le connecteur de l'opérateur choisi (interface `App\Services\Messaging\SmsSender`).
7. Anti-robot (facultatif) : `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` (Cloudflare).

## Tests

```bash
php artisan test
```

La base de test `immora_test` est distincte. Les tests vérifient les règles chiffrées du cahier des charges
(prorata, facture d'entrée, imputation, pénalité, relevé à 923 400 FCFA), la sécurité de connexion,
le cloisonnement entre agences et l'affichage de chaque écran.
