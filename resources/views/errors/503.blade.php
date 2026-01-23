@extends('errors.error-layout')

@section('illustration')
<svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="toolGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#3b82f6"/>
            <stop offset="100%" stop-color="#06b6d4"/>
        </linearGradient>
    </defs>
    
    
    <rect x="35" y="50" width="12" height="60" rx="6" fill="url(#toolGrad)" transform="rotate(-45 41 80)"/>
    <circle cx="41" cy="56" r="8" fill="none" stroke="url(#toolGrad)" stroke-width="4"/>
    
    <rect x="75" y="45" width="10" height="50" rx="5" fill="url(#toolGrad)" transform="rotate(45 80 70)"/>
    <rect x="70" y="35" width="20" height="18" rx="3" fill="url(#toolGrad)" transform="rotate(45 80 44)"/>
    
    <circle cx="90" cy="90" r="15" fill="none" stroke="url(#toolGrad)" stroke-width="4"/>
    <circle cx="90" cy="90" r="8" fill="url(#toolGrad)"/>
    <rect x="88" y="70" width="4" height="10" fill="url(#toolGrad)"/>
    <rect x="88" y="100" width="4" height="10" fill="url(#toolGrad)"/>
    <rect x="100" y="88" width="10" height="4" fill="url(#toolGrad)"/>
    <rect x="70" y="88" width="10" height="4" fill="url(#toolGrad)"/>
    
    <path d="M50 30 L52 35 L50 40" stroke="#fbbf24" stroke-width="2" fill="none" stroke-linecap="round"/>
    <path d="M100 55 L105 57 L100 59" stroke="#fbbf24" stroke-width="2" fill="none" stroke-linecap="round"/>
    <circle cx="60" cy="105" r="2" fill="#fbbf24"/>
</svg>
@endsection

@section('code', '503')
@section('title', 'Servicio temporalmente no disponible')
@section('message', 'Estamos realizando mejoras en el sistema para brindarte una mejor experiencia. Volveremos muy pronto.')

@section('meta-info')
    <p><strong>⏱️ Tiempo estimado:</strong> Aproximadamente 15-30 minutos</p>
    <p><strong>🔧 Motivo:</strong> Mantenimiento programado del sistema</p>
    <p>Agradecemos tu paciencia mientras mejoramos nuestro servicio.</p>
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
        Verificar disponibilidad
    </a>
@endsection

