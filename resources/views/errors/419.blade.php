@extends('errors.error-layout')
@section('illustration')
<svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
  <rect x="28" y="30" width="64" height="58" rx="10" fill="#111c2e" stroke="#2b3a5a" stroke-width="2"/>
  <path d="M40 74h40" stroke="#60a5fa" stroke-width="3"/>
  <circle cx="60" cy="56" r="16" fill="#60a5fa" opacity="0.18"/>
  <path d="M58 50h4v8h-4z" fill="#60a5fa"/>
</svg>
@endsection
@section('code', '419')
@section('title', 'Sesión expirada')
@section('message', 'Tu sesión ha expirado o el token CSRF ya no es válido. Regresa al inicio para iniciar nuevamente.')
@section('actions')
    <a href="/" class="btn">Ir al inicio</a>
@endsection
