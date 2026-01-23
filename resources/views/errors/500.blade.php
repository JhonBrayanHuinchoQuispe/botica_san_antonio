@extends('errors.error-layout')

@section('illustration')
<svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="errorGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#ef4444"/>
            <stop offset="100%" stop-color="#dc2626"/>
        </linearGradient>
    </defs>
    
    <rect x="30" y="40" width="80" height="60" rx="8" fill="#f3f4f6" stroke="#667eea" stroke-width="2"/>
    
    <rect x="40" y="50" width="60" height="35" rx="4" fill="#1f2937"/>
    
    <circle cx="70" cy="67" r="12" fill="none" stroke="#ef4444" stroke-width="2.5"/>
    <line x1="70" y1="60" x2="70" y2="70" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round"/>
    <circle cx="70" cy="74" r="1.5" fill="#ef4444"/>
    
    <circle cx="45" cy="95" r="3" fill="#ef4444"/>
    <circle cx="55" cy="95" r="3" fill="#fbbf24"/>
    <circle cx="65" cy="95" r="3" fill="#10b981"/>
    
    <path d="M110 45 L115 50 L110 55" stroke="#ef4444" stroke-width="2" fill="none" stroke-linecap="round"/>
    <path d="M25 65 L20 70 L25 75" stroke="#ef4444" stroke-width="2" fill="none" stroke-linecap="round"/>
    <circle cx="115" cy="70" r="2" fill="#ef4444"/>
    <circle cx="20" cy="50" r="2" fill="#ef4444"/>
</svg>
@endsection

@section('code', '500')
@section('title', 'Error interno del servidor')
@section('message', 'Ocurrió un error inesperado en el servidor. Nuestro equipo ha sido notificado y está trabajando para solucionarlo. Por favor, intenta nuevamente en unos momentos.')

@section('meta-info')
    <p><strong>¿Qué puedes hacer?</strong></p>
    <p>• Espera unos minutos y vuelve a intentar<br>
    • Verifica tu conexión a internet<br>
    • Si el problema persiste, contacta a soporte técnico</p>
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
        Reintentar
    </a>
    <a href="/dashboard/analisis" class="btn btn-secondary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        Ir al inicio
    </a>
@endsection
