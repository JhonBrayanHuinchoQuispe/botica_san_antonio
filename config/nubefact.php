<?php

return [
    // Modo de operación (true = pruebas, false = producción)
    'modo_prueba' => env('NUBEFACT_MODO_PRUEBA', true),

    // Endpoint de la API de Nubefact
    'api_url' => env('NUBEFACT_API_URL', ''),

    // Token de autenticación de Nubefact
    'api_token' => env('NUBEFACT_API_TOKEN', ''),

    // Serie por defecto para boletas en pruebas
    'serie_boleta' => env('NUBEFACT_SERIE_BOLETA', 'B001'),

    // Porcentaje de IGV (usualmente 18%)
    'igv' => env('NUBEFACT_IGV', 18.0),
];