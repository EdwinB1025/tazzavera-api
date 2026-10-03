<?php

declare(strict_types=1);

/** Texts of the e-mail notifications (published template and password reset). */
return [
    'greeting' => 'Hello!',
    'greeting_error' => 'Whoops!',
    'salutation' => 'Regards,',
    'signature' => 'The Tazavera team',
    'subcopy' => 'If the ":actionText" button does not work, copy and paste this URL into your web browser:',

    'reset_password' => [
        'subject' => 'Reset your Tazavera password',
        'intro' => 'You are receiving this email because we received a request to reset the password of your account.',
        'action' => 'Reset password',
        'expire' => 'This link will expire in :count minutes.',
        'outro' => 'If you did not request a password reset, you can ignore this email.',
    ],
];
