<?php

return [

    'default_country' => env('IMMORA_DEFAULT_COUNTRY', 'TG'),

    'inactivity_minutes' => (int) env('IMMORA_INACTIVITY_MINUTES', 30),

    'trial_days' => 14,

    /*
    | Pays pris en charge : indicatif, devise, longueur du numéro national.
    */
    'countries' => [
        'CI' => ['name' => "Côte d'Ivoire", 'dial' => '225', 'currency' => 'XOF', 'timezone' => 'Africa/Abidjan'],
        'SN' => ['name' => 'Sénégal', 'dial' => '221', 'currency' => 'XOF', 'timezone' => 'Africa/Dakar'],
        'BJ' => ['name' => 'Bénin', 'dial' => '229', 'currency' => 'XOF', 'timezone' => 'Africa/Porto-Novo'],
        'TG' => ['name' => 'Togo', 'dial' => '228', 'currency' => 'XOF', 'timezone' => 'Africa/Lome'],
        'BF' => ['name' => 'Burkina Faso', 'dial' => '226', 'currency' => 'XOF', 'timezone' => 'Africa/Ouagadougou'],
        'ML' => ['name' => 'Mali', 'dial' => '223', 'currency' => 'XOF', 'timezone' => 'Africa/Bamako'],
        'NE' => ['name' => 'Niger', 'dial' => '227', 'currency' => 'XOF', 'timezone' => 'Africa/Niamey'],
        'CM' => ['name' => 'Cameroun', 'dial' => '237', 'currency' => 'XAF', 'timezone' => 'Africa/Douala'],
        'GA' => ['name' => 'Gabon', 'dial' => '241', 'currency' => 'XAF', 'timezone' => 'Africa/Libreville'],
        'CG' => ['name' => 'Congo', 'dial' => '242', 'currency' => 'XAF', 'timezone' => 'Africa/Brazzaville'],
    ],

    /*
    | Rôles livrés par défaut (CDC chap. 6). Les rôles « owner » et « tenant »
    | n'accèdent qu'aux portails.
    */
    'roles' => [
        'admin' => 'Administrateur',
        'manager' => 'Gestionnaire locatif',
        'accountant' => 'Comptable / caissier',
        'agent' => 'Agent commercial',
        'owner' => 'Propriétaire',
        'tenant' => 'Locataire',
    ],

    'staff_roles' => ['admin', 'manager', 'accountant', 'agent'],

    'two_factor_required_roles' => ['admin', 'accountant'],

    /*
    | Matrice des droits (CDC chap. 6). Clé = permission, valeur = rôles autorisés.
    */
    'permissions' => [
        'dashboard.view' => ['admin', 'manager', 'accountant', 'agent'],
        'properties.view' => ['admin', 'manager', 'accountant', 'agent'],
        'properties.manage' => ['admin', 'manager'],
        'owners.manage' => ['admin', 'manager'],
        'tenants.manage' => ['admin', 'manager'],
        'leases.manage' => ['admin', 'manager'],
        'payments.collect' => ['admin', 'manager', 'accountant'],
        'payments.reverse' => ['admin'],
        'cash.close' => ['admin', 'accountant'],
        'expenses.manage' => ['admin', 'manager', 'accountant'],
        'statements.manage' => ['admin', 'accountant'],
        'payouts.validate' => ['admin', 'accountant'],
        'reports.view' => ['admin', 'manager', 'accountant'],
        'team.manage' => ['admin'],
        'settings.manage' => ['admin'],
        'audit.view' => ['admin'],
    ],

    'security' => [
        'lock_after' => [5 => 15, 10 => 60],
        'hard_lock_after' => 20,
        'captcha_after' => 3,
        'otp_ttl_minutes' => 5,
        'otp_max_attempts' => 5,
        'otp_per_hour' => 3,
        'reset_ttl_minutes' => 30,
        'invitation_ttl_hours' => 72,
        'trusted_device_days' => 30,
        'payout_dual_control_amount' => 1000000,
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'sms' => [
        'driver' => env('IMMORA_SMS_DRIVER', 'log'),
    ],

    'payments' => [
        'driver' => env('IMMORA_PAYMENT_DRIVER', 'fake'),
        'cinetpay' => [
            'api_key' => env('CINETPAY_API_KEY'),
            'site_id' => env('CINETPAY_SITE_ID'),
            'secret_key' => env('CINETPAY_SECRET_KEY'),
            'base_url' => 'https://api-checkout.cinetpay.com/v2',
        ],
    ],

    'property_types' => [
        'immeuble' => 'Immeuble', 'villa' => 'Villa', 'duplex' => 'Duplex', 'cour_commune' => 'Cour commune',
        'residence' => 'Résidence meublée', 'commercial' => 'Centre commercial', 'entrepot' => 'Entrepôt',
        'terrain' => 'Terrain',
    ],

    'unit_types' => [
        'appartement' => 'Appartement', 'studio' => 'Studio', 'chambre_salon' => 'Chambre-salon',
        'chambre' => 'Chambre simple', 'villa_basse' => 'Villa basse', 'magasin' => 'Magasin',
        'boutique' => 'Boutique', 'bureau' => 'Bureau', 'parking' => 'Parking', 'entrepot' => 'Entrepôt',
        'terrain' => 'Terrain',
    ],

    'expense_categories' => [
        'plomberie' => 'Plomberie', 'electricite' => 'Électricité', 'climatisation' => 'Climatisation',
        'peinture' => 'Peinture', 'serrurerie' => 'Serrurerie', 'toiture' => 'Toiture',
        'nettoyage' => 'Nettoyage', 'gardiennage' => 'Gardiennage', 'taxes' => 'Impôts et taxes',
        'autre' => 'Autre',
    ],

    'payment_methods' => [
        'cash' => 'Espèces', 'mobile_money' => 'Mobile Money', 'transfer' => 'Virement',
        'cheque' => 'Chèque', 'card' => 'Carte bancaire',
    ],

    'mobile_money_providers' => [
        'orange' => 'Orange Money', 'mtn' => 'MTN MoMo', 'moov' => 'Moov Money', 'wave' => 'Wave', 'yas' => 'YAS Togo',
    ],
];
