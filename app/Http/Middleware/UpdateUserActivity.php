<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo actualizar si el usuario está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            
            // Actualizar last_login_at cada 2 minutos para evitar demasiadas queries
            $lastUpdate = $user->last_login_at;
            
            if (!$lastUpdate || $lastUpdate->diffInMinutes(now()) >= 2) {
                $user->update([
                    'last_login_at' => now(),
                    'last_login_ip' => $request->ip()
                ]);
            }
        }

        return $next($request);
    }
}
