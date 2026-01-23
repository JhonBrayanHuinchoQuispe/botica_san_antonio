@extends('layout.layout')
@php
    $title = 'Configuración del Sistema';
    $subTitle = 'Gestión de parámetros del sistema';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/admin/configuracion.js') . '?v=' . time() . '"></script>
    ';
@endphp

<head>
    <title>Configuración del Sistema</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/configuracion.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

@push('head')
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
@endpush

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-primary-600/10 to-bg-white">
        <div class="card-body p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-neutral-900 mb-1">IGV</p>
                    <h6 class="mb-0 text-2xl font-bold">
                        @if($configuracion->igv_habilitado)
                            {{ $configuracion->igv_porcentaje }}%
                        @else
                            Deshabilitado
                        @endif
                    </h6>
                </div>
                <div class="w-[50px] h-[50px] bg-primary-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="solar:calculator-bold-duotone" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
            </div>
            <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                @if($configuracion->igv_habilitado)
                    <span class="inline-flex items-center gap-1 text-success-600">
                        <iconify-icon icon="heroicons:check-circle" class="text-xs"></iconify-icon> 
                        Activo
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-danger-600">
                        <iconify-icon icon="heroicons:x-circle" class="text-xs"></iconify-icon> 
                        Inactivo
                    </span>
                @endif
                Impuesto General a las Ventas
            </p>
        </div>
    </div>

    
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-success-600/10 to-bg-white">
        <div class="card-body p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-neutral-900 mb-1">Descuentos</p>
                    <h6 class="mb-0 text-2xl font-bold">
                        @if($configuracion->descuentos_habilitados)
                            Hasta {{ $configuracion->descuento_maximo_porcentaje }}%
                        @else
                            Deshabilitados
                        @endif
                    </h6>
                </div>
                <div class="w-[50px] h-[50px] bg-success-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="solar:tag-bold-duotone" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
            </div>
            <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                @if($configuracion->descuentos_habilitados)
                    <span class="inline-flex items-center gap-1 text-success-600">
                        <iconify-icon icon="heroicons:check-circle" class="text-xs"></iconify-icon> 
                        Activo
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-danger-600">
                        <iconify-icon icon="heroicons:x-circle" class="text-xs"></iconify-icon> 
                        Inactivo
                    </span>
                @endif
                Sistema de Descuentos
            </p>
        </div>
    </div>

    
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-warning-600/10 to-bg-white">
        <div class="card-body p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-neutral-900 mb-1">Promociones</p>
                    <h6 class="mb-0 text-2xl font-bold">
                        {{ $configuracion->promociones_habilitadas ? 'Activas' : 'Inactivas' }}
                    </h6>
                </div>
                <div class="w-[50px] h-[50px] bg-warning-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="solar:gift-bold-duotone" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
            </div>
            <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                @if($configuracion->promociones_habilitadas)
                    <span class="inline-flex items-center gap-1 text-success-600">
                        <iconify-icon icon="heroicons:check-circle" class="text-xs"></iconify-icon> 
                        Activo
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-danger-600">
                        <iconify-icon icon="heroicons:x-circle" class="text-xs"></iconify-icon> 
                        Inactivo
                    </span>
                @endif
                Sistema de Promociones
            </p>
        </div>
    </div>

    
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-info-600/10 to-bg-white">
        <div class="card-body p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-neutral-900 mb-1">Moneda</p>
                    <h6 class="mb-0 text-2xl font-bold">{{ $configuracion->simbolo_moneda }} {{ $configuracion->moneda }}</h6>
                </div>
                <div class="w-[50px] h-[50px] bg-info-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="solar:dollar-bold-duotone" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
            </div>
            <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-info-600">
                    <iconify-icon icon="heroicons:currency-dollar" class="text-xs"></iconify-icon> 
                    {{ $configuracion->decimales }} decimales
                </span>
                Configuración de moneda
            </p>
        </div>
    </div>
</div>

<form id="formConfiguracion" class="space-y-6">
    @csrf
    
    
    <div class="card shadow-none border border-gray-200 rounded-lg">
        <div class="card-header bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-t-lg p-4">
            <div class="flex items-center gap-2">
                <iconify-icon icon="solar:calculator-bold-duotone" class="text-2xl"></iconify-icon>
                <h3 class="text-lg font-semibold">Configuración del IGV (Impuesto General a las Ventas)</h3>
            </div>
        </div>
        <div class="card-body p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="form-label">Estado del IGV</label>
                    <select name="igv_habilitado" id="igv_habilitado" class="form-control">
                        <option value="1" {{ $configuracion->igv_habilitado ? 'selected' : '' }}>Habilitado</option>
                        <option value="0" {{ !$configuracion->igv_habilitado ? 'selected' : '' }}>Deshabilitado</option>
                    </select>
                    <small class="text-muted">Activar o desactivar el cálculo del IGV</small>
                </div>
                
                <div>
                    <label class="form-label">Porcentaje de IGV (%)</label>
                    <input type="number" name="igv_porcentaje" id="igv_porcentaje" 
                           class="form-control" 
                           value="{{ $configuracion->igv_porcentaje }}"
                           min="0" max="100" step="0.01">
                    <small class="text-muted">Porcentaje a aplicar (ej: 18.00)</small>
                </div>
                
                <div>
                    <label class="form-label">Nombre del Impuesto</label>
                    <input type="text" name="igv_nombre" id="igv_nombre" 
                           class="form-control" 
                           value="{{ $configuracion->igv_nombre }}"
                           maxlength="50">
                    <small class="text-muted">Nombre que aparecerá en los comprobantes</small>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card shadow-none border border-gray-200 rounded-lg">
        <div class="card-header bg-gradient-to-r from-success-600 to-success-700 text-white rounded-t-lg p-4">
            <div class="flex items-center gap-2">
                <iconify-icon icon="solar:tag-bold-duotone" class="text-2xl"></iconify-icon>
                <h3 class="text-lg font-semibold">Configuración de Descuentos</h3>
            </div>
        </div>
        <div class="card-body p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Estado de Descuentos</label>
                    <select name="descuentos_habilitados" id="descuentos_habilitados" class="form-control">
                        <option value="1" {{ $configuracion->descuentos_habilitados ? 'selected' : '' }}>Habilitado</option>
                        <option value="0" {{ !$configuracion->descuentos_habilitados ? 'selected' : '' }}>Deshabilitado</option>
                    </select>
                    <small class="text-muted">Activar o desactivar el sistema de descuentos</small>
                </div>
                
                <div>
                    <label class="form-label">Descuento Máximo (%)</label>
                    <input type="number" name="descuento_maximo_porcentaje" id="descuento_maximo_porcentaje" 
                           class="form-control" 
                           value="{{ $configuracion->descuento_maximo_porcentaje }}"
                           min="0" max="100" step="0.01">
                    <small class="text-muted">Porcentaje máximo permitido de descuento</small>
                </div>
                
                <div>
                    <label class="form-label">Requiere Autorización</label>
                    <select name="requiere_autorizacion_descuento" id="requiere_autorizacion_descuento" class="form-control">
                        <option value="1" {{ $configuracion->requiere_autorizacion_descuento ? 'selected' : '' }}>Sí</option>
                        <option value="0" {{ !$configuracion->requiere_autorizacion_descuento ? 'selected' : '' }}>No</option>
                    </select>
                    <small class="text-muted">Si los descuentos requieren autorización</small>
                </div>
                
                <div>
                    <label class="form-label">Descuento Sin Autorización (%)</label>
                    <input type="number" name="descuento_sin_autorizacion_max" id="descuento_sin_autorizacion_max" 
                           class="form-control" 
                           value="{{ $configuracion->descuento_sin_autorizacion_max }}"
                           min="0" max="100" step="0.01"
                           {{ !$configuracion->requiere_autorizacion_descuento ? 'disabled' : '' }}>
                    <small class="text-muted">Máximo descuento sin requerir autorización</small>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card shadow-none border border-gray-200 rounded-lg">
        <div class="card-header bg-gradient-to-r from-warning-600 to-warning-700 text-white rounded-t-lg p-4">
            <div class="flex items-center gap-2">
                <iconify-icon icon="solar:gift-bold-duotone" class="text-2xl"></iconify-icon>
                <h3 class="text-lg font-semibold">Configuración de Promociones</h3>
            </div>
        </div>
        <div class="card-body p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">Estado de Promociones</label>
                    <select name="promociones_habilitadas" id="promociones_habilitadas" class="form-control">
                        <option value="1" {{ $configuracion->promociones_habilitadas ? 'selected' : '' }}>Habilitado</option>
                        <option value="0" {{ !$configuracion->promociones_habilitadas ? 'selected' : '' }}>Deshabilitado</option>
                    </select>
                    <small class="text-muted">Activar o desactivar el sistema de promociones</small>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card shadow-none border border-gray-200 rounded-lg">
        <div class="card-header bg-gradient-to-r from-info-600 to-info-700 text-white rounded-t-lg p-4">
            <div class="flex items-center gap-2">
                <iconify-icon icon="solar:document-text-bold-duotone" class="text-2xl"></iconify-icon>
                <h3 class="text-lg font-semibold">Configuración de Comprobantes</h3>
            </div>
        </div>
        <div class="card-body p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="form-label">Serie de Boleta</label>
                    <input type="text" name="serie_boleta" id="serie_boleta" 
                           class="form-control" 
                           value="{{ $configuracion->serie_boleta }}"
                           maxlength="10">
                    <small class="text-muted">Serie para boletas electrónicas</small>
                </div>
                
                <div>
                    <label class="form-label">Serie de Factura</label>
                    <input type="text" name="serie_factura" id="serie_factura" 
                           class="form-control" 
                           value="{{ $configuracion->serie_factura }}"
                           maxlength="10">
                    <small class="text-muted">Serie para facturas electrónicas</small>
                </div>
                
                <div>
                    <label class="form-label">Serie de Ticket</label>
                    <input type="text" name="serie_ticket" id="serie_ticket" 
                           class="form-control" 
                           value="{{ $configuracion->serie_ticket }}"
                           maxlength="10">
                    <small class="text-muted">Serie para tickets simples</small>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card shadow-none border border-gray-200 rounded-lg">
        <div class="card-header bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-t-lg p-4">
            <div class="flex items-center gap-2">
                <iconify-icon icon="solar:settings-bold-duotone" class="text-2xl"></iconify-icon>
                <h3 class="text-lg font-semibold">Configuración General</h3>
            </div>
        </div>
        <div class="card-body p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="form-label">Moneda</label>
                    <select name="moneda" id="moneda" class="form-control">
                        <option value="PEN" {{ $configuracion->moneda == 'PEN' ? 'selected' : '' }}>PEN - Sol Peruano</option>
                        <option value="USD" {{ $configuracion->moneda == 'USD' ? 'selected' : '' }}>USD - Dólar Americano</option>
                    </select>
                    <small class="text-muted">Moneda del sistema</small>
                </div>
                
                <div>
                    <label class="form-label">Símbolo de Moneda</label>
                    <input type="text" name="simbolo_moneda" id="simbolo_moneda" 
                           class="form-control" 
                           value="{{ $configuracion->simbolo_moneda }}"
                           maxlength="10">
                    <small class="text-muted">Símbolo a mostrar</small>
                </div>
                
                <div>
                    <label class="form-label">Decimales</label>
                    <input type="number" name="decimales" id="decimales" 
                           class="form-control" 
                           value="{{ $configuracion->decimales }}"
                           min="0" max="4">
                    <small class="text-muted">Cantidad de decimales</small>
                </div>
                
                <div>
                    <label class="form-label">Imprimir Automáticamente</label>
                    <select name="imprimir_automatico" id="imprimir_automatico" class="form-control">
                        <option value="1" {{ $configuracion->imprimir_automatico ? 'selected' : '' }}>Sí</option>
                        <option value="0" {{ !$configuracion->imprimir_automatico ? 'selected' : '' }}>No</option>
                    </select>
                    <small class="text-muted">Imprimir comprobantes automáticamente</small>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card shadow-none border border-gray-200 rounded-lg">
        <div class="card-body p-4">
            <div class="flex justify-end gap-3">
                <button type="button" class="btn btn-secondary" onclick="Turbo.visit(window.location.href, { action: 'replace' })">
                    <iconify-icon icon="solar:refresh-bold-duotone" class="mr-2"></iconify-icon>
                    Restablecer
                </button>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold-duotone" class="mr-2"></iconify-icon>
                    Guardar Configuración
                </button>
            </div>
        </div>
    </div>
</form>

@endsection
