<?php

declare(strict_types=1);

/** Texts of the e-mail notifications (published template and password reset). */
return [
    'greeting' => '¡Hola!',
    'greeting_error' => '¡Vaya!',
    'salutation' => 'Saludos,',
    'signature' => 'El equipo de Tazavera',
    'subcopy' => 'Si el botón ":actionText" no funciona, copia y pega esta URL en tu navegador:',

    'reset_password' => [
        'subject' => 'Restablece tu contraseña de Tazavera',
        'intro' => 'Recibes este correo porque hemos recibido una solicitud para restablecer la contraseña de tu cuenta.',
        'action' => 'Restablecer contraseña',
        'expire' => 'Este enlace caducará en :count minutos.',
        'outro' => 'Si no has solicitado restablecer la contraseña, puedes ignorar este correo.',
    ],
    'verify_email' => [
        'subject' => 'Verifica tu dirección de correo electrónico de Tazavera',
        'intro' => 'Por favor, confirma tu dirección de correo electrónico haciendo clic en el botón de abajo.',
        'action' => 'Verificar correo electrónico',
        'outro' => 'Si no creaste una cuenta, puedes ignorar este correo electrónico.',
    ],
];
