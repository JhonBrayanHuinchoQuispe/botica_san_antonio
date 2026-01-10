<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Oculta warnings/notices para evitar romper respuestas JSON y la UI.
// Esto es especialmente útil en entornos de desarrollo con librerías que emiten avisos.
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
