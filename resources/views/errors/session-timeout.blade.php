@extends('errors.error-layout')

@section('illustration')
<svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="timeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#3b82f6"/>
            <stop offset="100%" stop-color="#06b6d4"/>
        </linearGradient>
    </defs>
    
    <circle cx="70" cy="70" r="45" fill="#f9fafb" stroke="url(#timeGrad)" stroke-width="4"/>
    <circle cx="70" cy="70" r="38" fill="#fff"/>
    
    
    <circle cx="70" cy="32" r="3" fill="url(#timeGrad)"/>
    <circle cx="70" cy="108" r="3" fill="url(#timeGrad)"/>
    <circle cx="108" cy="70" r="3" fill="url(#timeGrad)"/>
    <circle cx="32" cy="70" r="3" fill="url(#timeGrad)"/>
    
    
    <line x1="70" y1="70" x2="70" y2="45" stroke="url(#timeGrad)" stroke-width="4" stroke-linecap="round"/>
    <line x1="70" y1="70" x2="88" y2="70" stroke="url(#timeGrad)" stroke-width="3" stroke-linecap="round"/>
    
    
    <circle cx="70" cy="70" r="6" fill="url(#timeGrad)"/>
    
    
    <circle cx="100" cy="40" r="18" fill="#fff" stroke="url(#timeGrad)" stroke-width="2"/>
    <rect x="92" y="44" width="16" height="12" rx="3" fill="url(#timeGrad)"/>
    <path d="M96 44 V38 A4 4 0 0 1 104 38 V44" fill="none" stroke="url(#timeGrad)" stroke-width="2"/>
    <circle cx="100" cy="50" r="2" fill="#fff"/>
    
    
    <path d="M20 55 Q 15 50 20 45" stroke="#cbd5e1" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.5"/>
    <path d="M120 85 Q 125 90 120 95" stroke="#cbd5e1" stroke-width="2" fill="none" stroke-linecap="round" opacity="0.5"/>
</svg>
@endsection

@section('code', '⏰')
@section('title', 'Tu sesión ha expirado por inactividad')
@section('message', 'Tu sesión fue cerrada automáticamente por razones de seguridad después de estar inactivo por más de 30 minutos.')

@section('meta-info')
    <p>⏱️ <strong>Tiempo de inactividad:</strong> Más de 30 minutos</p>
    <p>🔒 <strong>Seguridad:</strong> Tu cuenta ha sido protegida</p>
    <p>Para continuar trabajando, por favor inicia sesión nuevamente.</p>
@endsection

@section('actions')
    <a href="/" class="btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
        Iniciar sesión nuevamente
    </a>
@endsection

@section('footer')
    <p>💡 <strong>Consejo:</strong> Mantén tu sesión activa trabajando en el sistema regularmente.</p>
@endsection

@section('head')
<script>

setTimeout(function() {
    window.location.href = '/';
}, 5000);
</script>
@endsection
