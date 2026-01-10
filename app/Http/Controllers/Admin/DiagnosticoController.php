<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class DiagnosticoController extends Controller
{
    public function index()
    {
        $checks = [];

        // APP_URL y esquema
        $checks['app_url'] = config('app.url');
        $checks['https_forzado'] = (URL::forceScheme('https') || (parse_url(config('app.url'), PHP_URL_SCHEME) === 'https'));

        // APP_KEY
        $checks['app_key_definido'] = (bool) config('app.key');

        // Sesiones y cookies
        $checks['session_driver'] = config('session.driver');
        $checks['session_secure_cookie'] = config('session.secure');

        // Permisos de directorios
        $checks['writable_storage'] = is_writable(storage_path('framework')) && is_writable(storage_path('logs'));
        $checks['writable_cache'] = is_writable(base_path('bootstrap/cache'));

        // DB conexión simple
        try {
            DB::select('SELECT 1');
            $checks['db_conexion'] = true;
        } catch (\Throwable $e) {
            $checks['db_conexion'] = false;
            $checks['db_error'] = $e->getMessage();
            Log::warning('Diagnóstico DB', ['error' => $e->getMessage()]);
        }

        // Columnas críticas de users
        try {
            $columns = Schema::getColumnListing('users');
            $checks['users_columns'] = [
                'last_login_at' => in_array('last_login_at', $columns),
                'last_login_ip' => in_array('last_login_ip', $columns),
                'failed_login_attempts' => in_array('failed_login_attempts', $columns),
                'locked_until' => in_array('locked_until', $columns),
            ];
        } catch (\Throwable $e) {
            $checks['users_columns_error'] = $e->getMessage();
        }

        return view('admin.diagnostico', compact('checks'));
    }
}