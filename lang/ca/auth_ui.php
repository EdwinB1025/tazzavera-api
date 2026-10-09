<?php

declare(strict_types=1);

/** Texts of the web authentication views (OAuth login, 2FA, password reset, /user/security). */
return [
    'brand' => 'TAZAVERA',
    'brand_name' => 'Tazavera',

    'fields' => [
        'email' => 'Correu electrònic',
        'email_placeholder' => 'correu@exemple.com',
        'password' => 'Contrasenya',
        'password_confirmation' => 'Confirma la contrasenya',
        'show_password' => 'Mostra la contrasenya',
        'hide_password' => 'Amaga la contrasenya',
    ],

    'login' => [
        'page_title' => 'Inicia la sessió',
        'title' => 'Accedeix al teu compte',
        'description' => 'Introdueix el teu correu electrònic i la contrasenya per accedir al teu compte.',
        'forgot_password' => 'Has oblidat la contrasenya?',
        'remember' => 'Recorda\'m',
        'submit' => 'Inicia la sessió',
    ],

    'passkey' => [
        'sign_in' => 'Inicia la sessió amb una clau d\'accés',
        'signing_in' => 'S\'està autenticant…',
        'or_email' => 'O continua amb el teu correu electrònic',
        'confirm' => 'Confirma amb una clau d\'accés',
        'confirming' => 'S\'està confirmant…',
        'or_password' => 'O confirma amb la teva contrasenya',
        'unsupported' => 'Aquest navegador no admet claus d\'accés.',
        'name' => 'Nom de la clau d\'accés',
        'name_placeholder' => 'P. ex.: portàtil de la feina',
        'name_required' => 'Escriu un nom per a la clau d\'accés.',
        'add' => 'Afegeix una clau d\'accés',
    ],

    'forgot' => [
        'title' => 'Recupera la contrasenya',
        'description' => 'Introdueix el teu correu electrònic i t\'enviarem un enllaç per restablir la contrasenya.',
        'submit' => 'Envia l\'enllaç',
        'back' => 'O torna a',
        'back_link' => 'iniciar la sessió',
    ],

    'reset' => [
        'title' => 'Restableix la contrasenya',
        'description' => 'Introdueix la teva contrasenya nova.',
        'submit' => 'Restableix la contrasenya',
    ],

    'confirm' => [
        'title' => 'Confirma la contrasenya',
        'description' => 'Aquesta és una àrea segura. Confirma la contrasenya per continuar.',
        'submit' => 'Confirma',
    ],

    'two_factor' => [
        'page_title' => 'Autenticació en dos passos',
        'code_title' => 'Codi d\'autenticació',
        'code_description' => 'Introdueix el codi que mostra la teva aplicació d\'autenticació.',
        'recovery_title' => 'Codi de recuperació',
        'recovery_description' => 'Confirma l\'accés al teu compte amb un dels teus codis de recuperació d\'emergència.',
        'code' => 'Codi d\'autenticació',
        'recovery_code' => 'Codi de recuperació',
        'submit' => 'Continua',
        'or' => 'O pots',
        'use_recovery' => 'iniciar la sessió amb un codi de recuperació',
        'use_code' => 'iniciar la sessió amb un codi d\'autenticació',
    ],

    'verify_email' => [
        'title' => 'Verifica el teu correu',
        'description' => "Verifica la teva adreça de correu fent clic a l'enllaç que t'acabem d'enviar.",
        'sent' => "S'ha enviat un nou enllaç de verificació a la teva adreça de correu.",
        'resend' => 'Torna a enviar el correu de verificació',
        'back' => 'O torna a',
        'back_link' => 'Tazavera',
    ],

    'security' => [
        'title' => 'Seguretat',
        'description' => 'Gestiona l\'autenticació en dos passos i les teves claus d\'accés.',

        'status' => [
            'two-factor-authentication-enabled' => 'Escaneja el codi QR i confirma amb un codi de la teva aplicació.',
            'two-factor-authentication-confirmed' => 'Autenticació en dos passos activada.',
            'two-factor-authentication-disabled' => 'Autenticació en dos passos desactivada.',
            'passkey-registered' => 'Clau d\'accés afegida.',
            'passkey-deleted' => 'Clau d\'accés eliminada.',
        ],

        'two_factor' => [
            'title' => 'Autenticació en dos passos',
            'disabled' => 'No activada. Quan l\'activis, l\'inici de sessió demanarà un codi de la teva aplicació d\'autenticació.',
            'enable' => 'Activa',
            'scan' => 'Escaneja aquest codi QR amb la teva aplicació d\'autenticació i introdueix el codi que genera.',
            'setup_key' => 'Clau de configuració:',
            'code' => 'Codi d\'autenticació',
            'confirm' => 'Confirma',
            'cancel' => 'Cancel·la',
            'enabled' => 'Activada. L\'inici de sessió demana un codi de la teva aplicació d\'autenticació.',
            'show_codes' => 'Mostra els codis de recuperació',
            'hide_codes' => 'Amaga els codis de recuperació',
            'codes_help' => 'Desa aquests codis en un lloc segur. Cadascun serveix una sola vegada si perds l\'accés a la teva aplicació.',
            'disable' => 'Desactiva',
        ],

        'passkeys' => [
            'title' => 'Claus d\'accés',
            'empty' => 'Encara no tens cap clau d\'accés.',
            'added_on' => 'Afegida el :date',
            'delete' => 'Elimina',
            'delete_named' => 'Elimina la clau d\'accés :name',
        ],
    ],
];
