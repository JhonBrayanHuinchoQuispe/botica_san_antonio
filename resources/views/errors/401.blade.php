@extends('errors.error-layout')
@section('illustration')
<svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
  <rect x="25" y="30" width="70" height="60" rx="10" fill="#111c2e" stroke="#2b3a5a" stroke-width="2"/>
  <circle cx="60" cy="60" r="18" fill="#ef4444" opacity="0.2"/>
  <path d="M52 60h16" stroke="#ef4444" stroke-width="3"/>
  <path d="M60 52v16" stroke="#ef4444" stroke-width="3"/>
</svg>
@endsection
@section('code', '401')
@section('title', 'No autenticado')
@section('message', 'Necesitas iniciar sesión para continuar. Usa el botón para regresar al inicio.')
@section('actions')
    <a href="/" class="btn">Ir al inicio</a>
@endsection
