<?php

declare(strict_types=1);

/** Texts of the e-mail notifications (published template and password reset). */
return [
    'greeting' => 'Hola!',
    'greeting_error' => 'Vaja!',
    'salutation' => 'Salutacions,',
    'signature' => "L'equip de Tazavera",
    'subcopy' => 'Si el botó ":actionText" no funciona, copia i enganxa aquest URL al teu navegador:',

    'reset_password' => [
        'subject' => 'Restableix la teva contrasenya de Tazavera',
        'intro' => 'Reps aquest correu perquè hem rebut una sol·licitud per restablir la contrasenya del teu compte.',
        'action' => 'Restableix la contrasenya',
        'expire' => "Aquest enllaç caducarà d'aquí a :count minuts.",
        'outro' => 'Si no has demanat restablir la contrasenya, pots ignorar aquest correu.',
    ],
    'verify_email' => [
        'subject' => 'Verifica la teva adreça de correu electrònic de Tazavera',
        'intro' => 'Si us plau, confirma la teva adreça de correu electrònic fent clic al botó de sota.',
        'action' => 'Verificar correu electrònic',
        'outro' => 'Si no has creat cap compte, pots ignorar aquest correu electrònic.',
    ],
];
