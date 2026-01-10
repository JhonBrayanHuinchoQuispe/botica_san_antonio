@extends('errors.error-layout')
@section('illustration')
<svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
  <rect x="22" y="32" width="76" height="56" rx="12" fill="#111c2e" stroke="#2b3a5a" stroke-width="2"/>
  <path d="M36 60h48" stroke="#ef4444" stroke-width="4"/>
  <circle cx="60" cy="60" r="20" fill="#ef4444" opacity="0.16"/>
</svg>
@endsection
@section('code', '500')
@section('title', 'Error interno del servidor')
@section('message', 'Ha ocurrido un error inesperado. Regresa al inicio y vuelve a intentarlo en unos segundos.')
@section('actions')
    <a href="/" class="btn">Ir al inicio</a>
@endsection