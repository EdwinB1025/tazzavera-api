<?php

declare(strict_types=1);

/**
 * Validation messages used by the web authentication views (login, password
 * reset, confirmation, 2FA and passkeys). Any other rule falls back to the
 * fallback locale.
 */
return [
    'confirmed'        => 'La confirmació del camp :attribute no coincideix.',
    'current_password' => 'La contrasenya no és correcta.',
    'email'            => 'El camp :attribute ha de ser una adreça de correu electrònic vàlida.',
    'max'              => [
        'string' => 'El camp :attribute no pot tenir més de :max caràcters.',
    ],
    'min'              => [
        'string' => 'El camp :attribute ha de tenir com a mínim :min caràcters.',
    ],
    'password'         => [
        'letters'       => 'El camp :attribute ha de contenir com a mínim una lletra.',
        'mixed'         => 'El camp :attribute ha de contenir com a mínim una lletra majúscula i una de minúscula.',
        'numbers'       => 'El camp :attribute ha de contenir com a mínim un número.',
        'symbols'       => 'El camp :attribute ha de contenir com a mínim un símbol.',
        'uncompromised' => 'El valor del camp :attribute ha aparegut en una filtració de dades. Tria\'n un altre.',
    ],
    'required'         => 'El camp :attribute és obligatori.',
    'string'           => 'El camp :attribute ha de ser una cadena de text.',

    'attributes' => [
        'email'                 => 'correu electrònic',
        'password'              => 'contrasenya',
        'password_confirmation' => 'confirmació de la contrasenya',
        'code'                  => 'codi d\'autenticació',
        'recovery_code'         => 'codi de recuperació',
        'token'                 => 'enllaç de restabliment',
        'name'                  => 'nom',
    ],
];
