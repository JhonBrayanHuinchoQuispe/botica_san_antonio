<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo rastrear si el usuario está autenticado
        if (Auth::check()) {
            $currentTime = time();
            $lastActivity = session()->get('last_activity_time');
            
            // Si existe una última actividad, verificar si pasaron más de 30 minutos (1800 segundos)
            if ($lastActivity && ($currentTime - $lastActivity) > 1800) {
                // Marcar sesión como expirada
                session()->put('session_expired', true);
                
                // Cerrar sesión del usuario
                Auth::logout();
                
                // Invalidar la sesión
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirigir a la página de sesión expirada
                return redirect()->route('session.timeout');
            }
            
            // Actualizar el timestamp de última actividad
            session()->put('last_activity_time', $currentTime);
        }
        
        return $next($request);
    }
}
