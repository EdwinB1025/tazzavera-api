<?php

declare(strict_types=1);

/** Texts of the web authentication views (OAuth login, 2FA, password reset, /user/security). */
return [
    'brand' => 'TAZAVERA',
    'brand_name' => 'Tazavera',

    'fields' => [
        'email' => 'Correo electrónico',
        'email_placeholder' => 'correo@ejemplo.com',
        'password' => 'Contraseña',
        'password_confirmation' => 'Confirmar contraseña',
        'show_password' => 'Mostrar contraseña',
        'hide_password' => 'Ocultar contraseña',
    ],

    'login' => [
        'page_title' => 'Iniciar sesión',
        'title' => 'Accede a tu cuenta',
        'description' => 'Ingresa tu correo electrónico y tu contraseña para acceder a tu cuenta.',
        'forgot_password' => '¿Olvidaste tu contraseña?',
        'remember' => 'Recuérdame',
        'submit' => 'Iniciar sesión',
    ],

    'passkey' => [
        'sign_in' => 'Iniciar sesión con passkey',
        'signing_in' => 'Autenticando…',
        'or_email' => 'O continúa con tu correo electrónico',
        'confirm' => 'Confirmar con passkey',
        'confirming' => 'Confirmando…',
        'or_password' => 'O confirma con tu contraseña',
        'unsupported' => 'Este navegador no admite passkeys.',
        'name' => 'Nombre de la passkey',
        'name_placeholder' => 'Ej.: portátil del trabajo',
        'name_required' => 'Escribe un nombre para la passkey.',
        'add' => 'Añadir passkey',
    ],

    'forgot' => [
        'title' => 'Recuperar contraseña',
        'description' => 'Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.',
        'submit' => 'Enviar enlace',
        'back' => 'O vuelve a',
        'back_link' => 'iniciar sesión',
    ],

    'reset' => [
        'title' => 'Restablecer contraseña',
        'description' => 'Ingresa tu nueva contraseña.',
        'submit' => 'Restablecer contraseña',
    ],

    'confirm' => [
        'title' => 'Confirmar contraseña',
        'description' => 'Esta es un área segura. Confirma tu contraseña para continuar.',
        'submit' => 'Confirmar',
    ],

    'two_factor' => [
        'page_title' => 'Autenticación de dos factores',
        'code_title' => 'Código de autenticación',
        'code_description' => 'Ingresa el código que muestra tu aplicación de autenticación.',
        'recovery_title' => 'Código de recuperación',
        'recovery_description' => 'Confirma el acceso a tu cuenta con uno de tus códigos de recuperación de emergencia.',
        'code' => 'Código de autenticación',
        'recovery_code' => 'Código de recuperación',
        'submit' => 'Continuar',
        'or' => 'O puedes',
        'use_recovery' => 'iniciar sesión con un código de recuperación',
        'use_code' => 'iniciar sesión con un código de autenticación',
    ],

    'security' => [
        'title' => 'Seguridad',
        'description' => 'Gestiona la autenticación de dos factores y tus passkeys.',

        'status' => [
            'two-factor-authentication-enabled' => 'Escanea el código QR y confirma con un código de tu aplicación.',
            'two-factor-authentication-confirmed' => 'Autenticación de dos factores activada.',
            'two-factor-authentication-disabled' => 'Autenticación de dos factores desactivada.',
            'passkey-registered' => 'Passkey añadida.',
            'passkey-deleted' => 'Passkey eliminada.',
        ],

        'two_factor' => [
            'title' => 'Autenticación de dos factores',
            'disabled' => 'No activada. Al activarla, el inicio de sesión pedirá un código de tu aplicación de autenticación.',
            'enable' => 'Activar',
            'scan' => 'Escanea este código QR con tu aplicación de autenticación e ingresa el código que genera.',
            'setup_key' => 'Clave de configuración:',
            'code' => 'Código de autenticación',
            'confirm' => 'Confirmar',
            'cancel' => 'Cancelar',
            'enabled' => 'Activada. El inicio de sesión pide un código de tu aplicación de autenticación.',
            'show_codes' => 'Ver códigos de recuperación',
            'hide_codes' => 'Ocultar códigos de recuperación',
            'codes_help' => 'Guarda estos códigos en un lugar seguro. Cada uno sirve una sola vez si pierdes el acceso a tu aplicación.',
            'disable' => 'Desactivar',
        ],

        'passkeys' => [
            'title' => 'Passkeys',
            'empty' => 'Todavía no tienes passkeys.',
            'added_on' => 'Añadida el :date',
            'delete' => 'Eliminar',
            'delete_named' => 'Eliminar la passkey :name',
        ],
    ],
];
