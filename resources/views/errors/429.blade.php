@extends('errors.error-layout')

@section('illustration')
<svg viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="speedGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#f59e0b"/>
            <stop offset="100%" stop-color="#d97706"/>
        </linearGradient>
    </defs>
    
    <circle cx="70" cy="80" r="45" fill="#f9fafb" stroke="url(#speedGrad)" stroke-width="4"/>
    <circle cx="70" cy="80" r="38" fill="#fff"/>
    
    
    <line x1="70" y1="42" x2="70" y2="48" stroke="url(#speedGrad)" stroke-width="3"/>
    <line x1="100" y1="55" x2="95" y2="59" stroke="url(#speedGrad)" stroke-width="3"/>
    <line x1="110" y1="80" x2="104" y2="80" stroke="url(#speedGrad)" stroke-width="3"/>
    <line x1="100" y1="105" x2="95" y2="101" stroke="url(#speedGrad)" stroke-width="3"/>
    <line x1="40" y1="55" x2="45" y2="59" stroke="url(#speedGrad)" stroke-width="3"/>
    <line x1="30" y1="80" x2="36" y2="80" stroke="url(#speedGrad)" stroke-width="3"/>
    <line x1="40" y1="105" x2="45" y2="101" stroke="url(#speedGrad)" stroke-width="3"/>
    
    
    <line x1="70" y1="80" x2="95" y2="60" stroke="#ef4444" stroke-width="4" stroke-linecap="round"/>
    <circle cx="70" cy="80" r="6" fill="#ef4444"/>
    
    
    <path d="M 85 55 A 45 45 0 0 1 110 80" fill="none" stroke="#ef4444" stroke-width="6" opacity="0.3"/>
    
    
    <circle cx="100" cy="30" r="16" fill="#fff" stroke="#f59e0b" stroke-width="2"/>
    <path d="M100 22 L100 32" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round"/>
    <circle cx="100" cy="36" r="1.5" fill="#f59e0b"/>
</svg>
@endsection

@section('code', '429')
@section('title', 'Demasiadas solicitudes')
@section('message', 'Has realizado demasiadas peticiones en poco tiempo. Por favor, espera un momento antes de continuar.')

@section('meta-info')
    <p>⏱️ <strong>Tiempo de espera:</strong> <span id="countdown">60</span> segundos</p>
    <p>🛡️ <strong>Razón:</strong> Protección contra uso excesivo del sistema</p>
    <p>Esta medida ayuda a mantener el sistema rápido y disponible para todos.</p>
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="btn" id="retry-btn" style="opacity: 0.5; pointer-events: none;">
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

@section('footer')
    <p>💡 <strong>Consejo:</strong> Evita hacer clic repetidamente en los botones.</p>
@endsection

@section('head')
<script>

let seconds = 60;
const countdownEl = document.getElementById('countdown');
const retryBtn = document.getElementById('retry-btn');

const interval = setInterval(() => {
    seconds--;
    if (countdownEl) {
        countdownEl.textContent = seconds;
    }
    
    if (seconds <= 0) {
        clearInterval(interval);
        if (retryBtn) {
            retryBtn.style.opacity = '1';
            retryBtn.style.pointerEvents = 'auto';
        }
        if (countdownEl) {
            countdownEl.textContent = '0';
            countdownEl.parentElement.innerHTML = '✅ <strong>Ya puedes continuar</strong>';
        }
    }
}, 1000);
</script>
@endsection
