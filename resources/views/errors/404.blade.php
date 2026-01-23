@extends('errors.error-layout')

@section('illustration')
<svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="pillGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#3b82f6"/>
            <stop offset="100%" stop-color="#06b6d4"/>
        </linearGradient>
        <linearGradient id="bottleGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#f0f9ff"/>
            <stop offset="100%" stop-color="#e0f2fe"/>
        </linearGradient>
    </defs>
    
    <rect x="35" y="45" width="70" height="80" rx="16" fill="url(#bottleGrad)" stroke="#3b82f6" stroke-width="2"/>
    <rect x="45" y="35" width="50" height="18" rx="9" fill="url(#bottleGrad)" stroke="#3b82f6" stroke-width="2"/>
    
    <rect x="60" y="65" width="20" height="40" rx="4" fill="url(#pillGrad)"/>
    <rect x="50" y="80" width="40" height="12" rx="4" fill="url(#pillGrad)"/>
    
    <circle cx="110" cy="35" r="20" fill="#fff" stroke="#3b82f6" stroke-width="2"/>
    <text x="110" y="42" text-anchor="middle" font-size="22" font-weight="700" fill="#3b82f6">?</text>
</svg>
@endsection

@section('code', '404')
@section('title', 'Página no encontrada')
@section('message', 'La página que buscas no existe o fue movida. Verifica la URL o regresa al inicio para continuar navegando.')

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
        Volver atrás
    </a>
@endsection

