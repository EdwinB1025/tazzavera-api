<?php

declare(strict_types=1);

/** Texts of the web authentication views (OAuth login, 2FA, password reset, /user/security). */
return [
    'brand' => 'TAZAVERA',

    'fields' => [
        'email' => 'Email',
        'email_placeholder' => 'email@example.com',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'show_password' => 'Show password',
        'hide_password' => 'Hide password',
    ],

    'login' => [
        'page_title' => 'Log in',
        'title' => 'Log in to your account',
        'description' => 'Enter your email and password to access your account.',
        'forgot_password' => 'Forgot your password?',
        'remember' => 'Remember me',
        'submit' => 'Log in',
    ],

    'passkey' => [
        'sign_in' => 'Log in with a passkey',
        'signing_in' => 'Authenticating…',
        'or_email' => 'Or continue with your email',
        'confirm' => 'Confirm with a passkey',
        'confirming' => 'Confirming…',
        'or_password' => 'Or confirm with your password',
        'unsupported' => 'This browser does not support passkeys.',
        'name' => 'Passkey name',
        'name_placeholder' => 'E.g. work laptop',
        'name_required' => 'Enter a name for the passkey.',
        'add' => 'Add passkey',
    ],

    'forgot' => [
        'title' => 'Recover your password',
        'description' => 'Enter your email and we will send you a link to reset your password.',
        'submit' => 'Send link',
        'back' => 'Or go back to',
        'back_link' => 'log in',
    ],

    'reset' => [
        'title' => 'Reset password',
        'description' => 'Enter your new password.',
        'submit' => 'Reset password',
    ],

    'confirm' => [
        'title' => 'Confirm password',
        'description' => 'This is a secure area. Confirm your password to continue.',
        'submit' => 'Confirm',
    ],

    'two_factor' => [
        'page_title' => 'Two-factor authentication',
        'code_title' => 'Authentication code',
        'code_description' => 'Enter the code shown by your authenticator app.',
        'recovery_title' => 'Recovery code',
        'recovery_description' => 'Confirm access to your account with one of your emergency recovery codes.',
        'code' => 'Authentication code',
        'recovery_code' => 'Recovery code',
        'submit' => 'Continue',
        'or' => 'Or you can',
        'use_recovery' => 'log in with a recovery code',
        'use_code' => 'log in with an authentication code',
    ],

    'security' => [
        'title' => 'Security',
        'description' => 'Manage two-factor authentication and your passkeys.',

        'status' => [
            'two-factor-authentication-enabled' => 'Scan the QR code and confirm with a code from your app.',
            'two-factor-authentication-confirmed' => 'Two-factor authentication enabled.',
            'two-factor-authentication-disabled' => 'Two-factor authentication disabled.',
            'passkey-registered' => 'Passkey added.',
            'passkey-deleted' => 'Passkey deleted.',
        ],

        'two_factor' => [
            'title' => 'Two-factor authentication',
            'disabled' => 'Not enabled. Once enabled, logging in will ask for a code from your authenticator app.',
            'enable' => 'Enable',
            'scan' => 'Scan this QR code with your authenticator app and enter the code it generates.',
            'setup_key' => 'Setup key:',
            'code' => 'Authentication code',
            'confirm' => 'Confirm',
            'cancel' => 'Cancel',
            'enabled' => 'Enabled. Logging in asks for a code from your authenticator app.',
            'show_codes' => 'Show recovery codes',
            'hide_codes' => 'Hide recovery codes',
            'codes_help' => 'Keep these codes somewhere safe. Each one works only once if you lose access to your app.',
            'disable' => 'Disable',
        ],

        'passkeys' => [
            'title' => 'Passkeys',
            'empty' => 'You have no passkeys yet.',
            'added_on' => 'Added on :date',
            'delete' => 'Delete',
            'delete_named' => 'Delete the passkey :name',
        ],
    ],
];
