@extends('errors.error-layout')

@section('illustration')
<svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
      <stop offset="0" stop-color="#60a5fa"/>
      <stop offset="1" stop-color="#22d3ee"/>
    </linearGradient>
  </defs>
  <!-- Frasco de botica con cruz -->
  <rect x="30" y="30" width="60" height="70" rx="12" fill="#111c2e" stroke="#2b3a5a" stroke-width="2"/>
  <rect x="40" y="22" width="40" height="16" rx="8" fill="#1f2b46" stroke="#2b3a5a" stroke-width="2"/>
  <circle cx="60" cy="70" r="22" fill="url(#g)" opacity="0.15"/>
  <rect x="52" y="58" width="16" height="24" fill="url(#g)" rx="3"/>
  <rect x="48" y="66" width="24" height="8" fill="url(#g)" rx="3"/>
</svg>
@endsection

@section('code', '404')
@section('title', 'Página no encontrada')
@section('message', 'La ruta que intentaste abrir no existe. Puede que el enlace esté desactualizado, que no tengas permisos, o que la sesión haya expirado.')

@section('actions')
    <a href="/dashboard/analisis" class="btn">Ir al inicio</a>
@endsection
