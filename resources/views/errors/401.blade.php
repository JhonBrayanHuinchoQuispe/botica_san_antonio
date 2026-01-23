@extends('errors.error-layout')

@section('illustration')
<svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="lockGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#3b82f6"/>
            <stop offset="100%" stop-color="#06b6d4"/>
        </linearGradient>
    </defs>
    
    <rect x="40" y="60" width="60" height="55" rx="12" fill="url(#lockGrad)"/>
    <path d="M55 60V45a15 15 0 0 1 30 0v15" fill="none" stroke="#3b82f6" stroke-width="8" stroke-linecap="round"/>
    
    <circle cx="70" cy="85" r="10" fill="#fff"/>
    <rect x="67" y="90" width="6" height="15" rx="3" fill="#fff"/>
    
    <path d="M70 10 L100 25 L100 55 Q100 75 70 90 Q40 75 40 55 L40 25 Z" fill="none" stroke="#e0e7ff" stroke-width="2" opacity="0.5"/>
</svg>
@endsection

@section('code', '401')
@section('title', 'Acceso no autorizado')
@section('message', 'Necesitas iniciar sesión para acceder a esta página. Por favor, ingresa tus credenciales para continuar.')

@section('actions')
    <a href="/" class="btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
            <polyline points="10 17 15 12 10 7"/>
            <line x1="15" y1="12" x2="3" y2="12"/>
        </svg>
        Iniciar sesión
    </a>
    <a href="javascript:history.back()" class="btn btn-secondary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 19-7-7 7-7"/>
            <path d="M19 12H5"/>
        </svg>
        Volver
    </a>
@endsection

