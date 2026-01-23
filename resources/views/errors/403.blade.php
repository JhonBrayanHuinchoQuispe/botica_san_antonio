@extends('errors.error-layout')

@section('illustration')
<svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="shieldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#3b82f6"/>
            <stop offset="100%" stop-color="#06b6d4"/>
        </linearGradient>
    </defs>
    
    <path d="M70 15 L115 35 L115 70 Q115 100 70 125 Q25 100 25 70 L25 35 Z" fill="url(#shieldGrad)"/>
    
    <path d="M70 30 L100 45 L100 70 Q100 90 70 108 Q40 90 40 70 L40 45 Z" fill="#fff" opacity="0.9"/>
    
    <circle cx="70" cy="70" r="22" fill="none" stroke="#ef4444" stroke-width="4"/>
    <line x1="55" y1="55" x2="85" y2="85" stroke="#ef4444" stroke-width="4" stroke-linecap="round"/>
    <line x1="85" y1="55" x2="55" y2="85" stroke="#ef4444" stroke-width="4" stroke-linecap="round"/>
</svg>
@endsection

@section('code', '403')
@section('title', 'Acceso denegado')
@section('message', 'No tienes permisos para acceder a esta página. Si crees que esto es un error, contacta al administrador del sistema.')

@section('actions')
    <a href="/dashboard/analisis" class="btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        Ir al inicio
    </a>
    <a href="javascript:history.back()" class="btn btn-secondary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 19-7-7 7-7"/>
            <path d="M19 12H5"/>
        </svg>
        Volver
    </a>
@endsection

