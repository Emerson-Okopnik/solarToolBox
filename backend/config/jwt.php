<?php
return [
    'secret' => env('JWT_SECRET'),
    'ttl'    => (int) env('JWT_TTL_MINUTES', 60), // em minutos
    'alg'    => 'HS256',
    'issuer' => env('APP_URL', 'http://localhost'),
];
