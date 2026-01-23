@extends('errors.error-layout')

@section('illustration')
<svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="clockGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#3b82f6"/>
            <stop offset="100%" stop-color="#06b6d4"/>
        </linearGradient>
    </defs>
    
    <ellipse cx="70" cy="35" rx="25" ry="8" fill="url(#clockGrad)"/>
    <ellipse cx="70" cy="105" rx="25" ry="8" fill="url(#clockGrad)"/>
    <path d="M45 35 L55 70 L45 105 L95 105 L85 70 L95 35 Z" fill="url(#clockGrad)" opacity="0.9"/>
    <path d="M45 35 L55 70 L45 105 L95 105 L85 70 L95 35 Z" fill="none" stroke="#fff" stroke-width="2"/>
    
    <circle cx="70" cy="65" r="3" fill="#fff" opacity="0.8"/>
    <circle cx="70" cy="75" r="3" fill="#fff" opacity="0.6"/>
    <circle cx="70" cy="85" r="3" fill="#fff" opacity="0.4"/>
    
    <ellipse cx="70" cy="100" rx="15" ry="5" fill="#fff" opacity="0.7"/>
</svg>
@endsection

@section('code', '419')
@section('title', 'Sesión expirada')
@section('message', 'Tu sesión ha expirado por seguridad. Por favor, vuelve a iniciar sesión para continuar trabajando.')

@section('meta-info')
    <p><strong>Razón:</strong> Token de seguridad vencido</p>
    <p>Las sesiones expiran automáticamente después de un período de inactividad para proteger tu cuenta.</p>
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
    <p>Botica San Antonio • Sistema de Gestión</p>
@endsection
