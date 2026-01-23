<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }
        
        // Verificar si la sesión expiró por timeout de inactividad
        if (session()->has('session_expired')) {
            return route('session.timeout');
        }
        
        // Verificar si fue por inactividad prolongada (>30 minutos)
        $lastActivity = session()->get('last_activity_time');
        if ($lastActivity && (time() - $lastActivity) > 1800) { // 1800 segundos = 30 minutos
            session()->put('session_expired', true);
            return route('session.timeout');
        }
        
        return route('login');
    }
}