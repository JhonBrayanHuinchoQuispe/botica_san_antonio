<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Botica San Antonio
    |--------------------------------------------------------------------------
    */
    
    'name' => 'Botica San Antonio',
    'description' => 'Sistema de Administración',
    'version' => '1.0.0',
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Emails
    |--------------------------------------------------------------------------
    */
    'mail' => [
        'from_name' => 'Botica San Antonio',
        'from_email' => 'noreply@boticasanantonio.com',
        'support_email' => 'soporte@boticasanantonio.com',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Recuperación de Contraseña
    |--------------------------------------------------------------------------
    */
    'password_reset' => [
        'expire_minutes' => 60, // 1 hora
        'throttle_minutes' => 1, // 1 minuto entre intentos
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad
    |--------------------------------------------------------------------------
    */
    'security' => [
        'max_login_attempts' => 5,
        'lockout_duration' => 15, // minutos
        'password_min_length' => 8,
    ],
]; 