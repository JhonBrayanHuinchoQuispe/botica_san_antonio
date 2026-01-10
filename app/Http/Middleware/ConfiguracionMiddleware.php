<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConfiguracionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para acceder a esta sección.');
        }

        $user = Auth::user();

        // Verificar si el usuario tiene permisos de administrador o configuración
        if (!$user->hasRole('admin') && !$user->can('configurar_sistema')) {
            abort(403, 'No tiene permisos para acceder a la configuración del sistema.');
        }

        return $next($request);
    }
}