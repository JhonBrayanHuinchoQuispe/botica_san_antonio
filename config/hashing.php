<?php

return [
    // Usar bcrypt con un coste moderado para acelerar verificación en entornos locales/Windows.
    'driver' => env('HASH_DRIVER', 'bcrypt'),

    'bcrypt' => [
        // Coste por defecto 10 (equilibrio velocidad/seguridad). Puedes subirlo a 12 en producción.
        'rounds' => env('BCRYPT_ROUNDS', 10),
    ],

    'argon' => [
        // Parámetros razonables si decides usar Argon2id (requiere HASH_DRIVER=argon y soporte en PHP).
        'memory' => env('ARGON_MEMORY', 65536), // 64 MB
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 2),
    ],
];