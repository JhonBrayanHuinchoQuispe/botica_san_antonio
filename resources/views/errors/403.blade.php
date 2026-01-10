@extends('errors.error-layout')
@section('illustration')
<svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
  <rect x="24" y="34" width="72" height="52" rx="10" fill="#111c2e" stroke="#2b3a5a" stroke-width="2"/>
  <path d="M44 60h32" stroke="#f59e0b" stroke-width="4"/>
  <circle cx="60" cy="60" r="18" fill="#f59e0b" opacity="0.15"/>
</svg>
@endsection
@section('code', '403')
@section('title', 'Acceso denegado')
@section('message', 'No cuentas con permisos para ver esta página. Regresa al inicio para continuar.')
@section('actions')
    <a href="/" class="btn">Ir al inicio</a>
@endsection
