<?php

return [
    // Comma-separated IPs or CIDR ranges of the reverse proxy (Caddy). Empty: trust none.
    'proxies' => env('TRUSTED_PROXIES'),
];
