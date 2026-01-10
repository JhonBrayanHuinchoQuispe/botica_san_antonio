<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico del Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-3">Diagnóstico del Sistema</h2>
    <p class="text-muted">Comprueba configuración básica para que el login funcione en producción.</p>

    <div class="card mb-3">
        <div class="card-header">Aplicación</div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">APP_URL: <strong>{{ $checks['app_url'] }}</strong></li>
                <li class="list-group-item">HTTPS forzado: <strong class="{{ $checks['https_forzado'] ? 'text-success' : 'text-danger' }}">{{ $checks['https_forzado'] ? 'Sí' : 'No' }}</strong></li>
                <li class="list-group-item">APP_KEY definido: <strong class="{{ $checks['app_key_definido'] ? 'text-success' : 'text-danger' }}">{{ $checks['app_key_definido'] ? 'Sí' : 'No' }}</strong></li>
                <li class="list-group-item">Session driver: <strong>{{ $checks['session_driver'] }}</strong></li>
                <li class="list-group-item">Cookie de sesión segura: <strong class="{{ $checks['session_secure_cookie'] ? 'text-success' : 'text-warning' }}">{{ $checks['session_secure_cookie'] ? 'Sí' : 'No' }}</strong></li>
            </ul>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Sistema de archivos</div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">storage/framework y logs escribibles: <strong class="{{ $checks['writable_storage'] ? 'text-success' : 'text-danger' }}">{{ $checks['writable_storage'] ? 'Sí' : 'No' }}</strong></li>
                <li class="list-group-item">bootstrap/cache escribible: <strong class="{{ $checks['writable_cache'] ? 'text-success' : 'text-danger' }}">{{ $checks['writable_cache'] ? 'Sí' : 'No' }}</strong></li>
            </ul>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Base de Datos</div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">Conexión DB: <strong class="{{ $checks['db_conexion'] ? 'text-success' : 'text-danger' }}">{{ $checks['db_conexion'] ? 'OK' : 'ERROR' }}</strong>
                    @if(!$checks['db_conexion'])
                        <div class="small text-danger mt-2">{{ $checks['db_error'] }}</div>
                    @endif
                </li>
                @isset($checks['users_columns'])
                    <li class="list-group-item">users.last_login_at: <strong class="{{ $checks['users_columns']['last_login_at'] ? 'text-success' : 'text-warning' }}">{{ $checks['users_columns']['last_login_at'] ? 'Sí' : 'No' }}</strong></li>
                    <li class="list-group-item">users.last_login_ip: <strong class="{{ $checks['users_columns']['last_login_ip'] ? 'text-success' : 'text-warning' }}">{{ $checks['users_columns']['last_login_ip'] ? 'Sí' : 'No' }}</strong></li>
                    <li class="list-group-item">users.failed_login_attempts: <strong class="{{ $checks['users_columns']['failed_login_attempts'] ? 'text-success' : 'text-warning' }}">{{ $checks['users_columns']['failed_login_attempts'] ? 'Sí' : 'No' }}</strong></li>
                    <li class="list-group-item">users.locked_until: <strong class="{{ $checks['users_columns']['locked_until'] ? 'text-success' : 'text-warning' }}">{{ $checks['users_columns']['locked_until'] ? 'Sí' : 'No' }}</strong></li>
                @endisset
            </ul>
        </div>
    </div>

    <a href="{{ route('login') }}" class="btn btn-secondary">Volver al Login</a>
</div>
</body>
</html>