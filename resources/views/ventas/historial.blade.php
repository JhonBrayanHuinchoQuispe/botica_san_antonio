@extends('layout.layout')
@php
    $title = 'Historial de Ventas';
    $subTitle = 'Registro completo de ventas realizadas';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/ventas/historial.js') . '?v=' . time() . '"></script>
    ';
@endphp

@push('head')
    <title>Historial de Ventas</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ventas/ventas.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<style>

.historial-table thead, 
.historial-table thead tr, 
.historial-table thead th {
    background-color: #f8fafc !important;
    background: #f8fafc !important;
    color: #475569 !important;
}

.historial-table thead th {
    border-bottom: 2px solid #e2e8f0 !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    font-size: 0.72rem !important;
    letter-spacing: 0.05em !important;
    padding: 1rem 0.75rem !important;
    text-align: center !important;
}

.historial-table thead th:first-child {
    text-align: left !important;
    padding-left: 1.5rem !important;
}

.reportes-metrics-grid-4 {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.reportes-metric-card {
    background: white;
    padding: 1rem;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.reportes-metric-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
}

.reportes-metric-icon-small {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}

.reportes-metric-content {
    flex: 1;
}

.reportes-metric-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 0.15rem;
}

.reportes-metric-value-medium {
    font-size: 1.15rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.reportes-metric-comparison {
    font-size: 0.65rem;
    margin-top: 0.15rem;
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: 600;
}

.reportes-metric-card.gold { border-left: 3px solid #eab308; }
.reportes-metric-card.gold .reportes-metric-icon-small { background: rgba(234, 179, 8, 0.1); color: #a16207; }
.reportes-metric-card.gold .reportes-metric-comparison { color: #a16207; }

.reportes-metric-card.teal { border-left: 3px solid #14b8a6; }
.reportes-metric-card.teal .reportes-metric-icon-small { background: rgba(20, 184, 166, 0.1); color: #0f766e; }
.reportes-metric-card.teal .reportes-metric-comparison { color: #0f766e; }

.reportes-metric-card.purple { border-left: 3px solid #8b5cf6; }
.reportes-metric-card.purple .reportes-metric-icon-small { background: rgba(139, 92, 246, 0.1); color: #6d28d9; }
.reportes-metric-card.purple .reportes-metric-comparison { color: #6d28d9; }

.reportes-metric-card.red { border-left: 3px solid #ef4444; }
.reportes-metric-card.red .reportes-metric-icon-small { background: rgba(239, 68, 68, 0.1); color: #b91c1c; }
.reportes-metric-card.red .reportes-metric-comparison { color: #b91c1c; }

.presentacion-item-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    margin: 2px;
    border: 1px solid transparent;
}

.pres-unidad { background: #eff6ff; color: #2563eb; border-color: #dbeafe; }
.pres-blister { background: #f5f3ff; color: #7c3aed; border-color: #ede9fe; }
.pres-caja { background: #fff7ed; color: #ea580c; border-color: #ffedd5; }
.pres-generico { background: #f8fafc; color: #475569; border-color: #e2e8f0; }

.price-total-green {
    color: #10b981 !important;
    font-weight: 700 !important;
    font-size: 1.05rem !important;
}

.vuelto-label {
    font-size: 0.7rem;
    color: #94a3b8;
    font-weight: 500;
    display: block;
    margin-top: 1px;
}

.historial-row {
    transition: all 0.2s ease;
}

.historial-row:not(.venta-anulada-row):not(.venta-parcial-row):hover {
    background-color: #f8fafc !important;
}

.historial-row td {
    padding: 1rem !important;
    border-bottom: 1px solid #f1f5f9 !important;
    vertical-align: middle !important;
    text-align: center;
}

.historial-row td:first-child {
    text-align: left;
    padding-left: 1.5rem !important;
}

.detail-content {
    padding: 0 !important;
    background: #f8fafc;
}

.status-badge-premium {
    padding: 0.35rem 0.7rem;
    border-radius: 99px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.status-paid {
    background: rgba(16, 185, 129, 0.08);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.15);
}

.status-returned {
    background: rgba(239, 68, 68, 0.08);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.15);
}

.status-partial {
    background: rgba(245, 158, 11, 0.08);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.15);
}

.venta-anulada-row {
    background-color: rgba(239, 68, 68, 0.03) !important;
}
.venta-anulada-row:hover {
    background-color: rgba(239, 68, 68, 0.05) !important;
}

.venta-parcial-row {
    background-color: rgba(245, 158, 11, 0.03) !important;
}
.venta-parcial-row:hover {
    background-color: rgba(245, 158, 11, 0.05) !important;
}

.total-tachado {
    text-decoration: line-through;
    color: #94a3b8;
    font-size: 0.85rem;
    margin-right: 4px;
}

.historial-btn-limpiar {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.5rem 1.25rem;
    background: #f1f5f9;
    color: #475569;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    border: 1px solid #e2e8f0;
    cursor: pointer;
}
.historial-btn-limpiar:hover {
    background: #e2e8f0;
    color: #1e293b;
    transform: translateY(-1px);
}
.historial-btn-limpiar iconify-icon {
    font-size: 1.1rem;
}

@keyframes shimmer {
    0% { background-position: -468px 0; }
    100% { background-position: 468px 0; }
}

.skeleton-loading {
    background: #f6f7f8;
    background-image: linear-gradient(to right, #f6f7f8 0%, #edeef1 20%, #f6f7f8 40%, #f6f7f8 100%);
    background-repeat: no-repeat;
    background-size: 800px 104px;
    display: inline-block;
    position: relative;
    animation: shimmer 1s linear infinite forwards;
    border-radius: 4px;
}

.skeleton-text { height: 12px; width: 100%; margin: 4px 0; }
.skeleton-badge { height: 24px; width: 80px; border-radius: 12px; }
.skeleton-circle { height: 32px; width: 32px; border-radius: 50%; }

.historial-actions-improved button {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.historial-actions-improved .action-btn-view {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%) !important;
    color: #2563eb !important;
    border: 1px solid rgba(59, 130, 246, 0.15) !important;
}

.historial-actions-improved .action-btn-view:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.15) 100%) !important;
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.historial-actions-improved .action-btn-print {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(16, 185, 129, 0.08) 100%) !important;
    color: #059669 !important;
    border: 1px solid rgba(16, 185, 129, 0.15) !important;
}

.historial-actions-improved .action-btn-print:hover {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.15) 100%) !important;
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.historial-table-container-improved {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.historial-hscroll-track { height: 8px; background: #f1f5f9; border-radius: 9999px; margin: 8px 1.5rem; position: relative; width: calc(100% - 3rem); display: none; }
.historial-hscroll-thumb { height: 8px; background: #cbd5e1; border-radius: 9999px; width: 60px; position: absolute; left: 0; cursor: pointer; transition: background 0.2s; }
.historial-hscroll-thumb:hover { background: #94a3b8; }
.historial-hscroll { scrollbar-width: none; }
.historial-hscroll::-webkit-scrollbar { display: none; }

.historial-venta-number {
    font-weight: 500;
    color: #64748b;
    font-size: 0.85rem;
}

.historial-price-total {
    font-weight: 700;
    color: #059669;
    font-size: 1.1rem;
}

.historial-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 14px;
    min-height: 30px;
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    border: 1px solid transparent;
    background: #eef2f7;
    color: #374151;
}
.historial-badge-success { background: rgba(16, 185, 129, 0.05); color: #059669; border-color: rgba(16, 185, 129, 0.1); }
.historial-badge-info { background: rgba(37, 99, 235, 0.05); color: #2563eb; border-color: rgba(37, 99, 235, 0.1); }
.historial-badge-warning { background: rgba(245, 158, 11, 0.05); color: #d97706; border-color: rgba(245, 158, 11, 0.1); }
.historial-badge-gray { background: #f1f5f9; color: #6b7280; border-color: #e2e8f0; }

.historial-quantity-container { display: flex; flex-direction: column; align-items: center; gap: 2px; }

.cliente-doc { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:9999px; background:rgba(37, 99, 235, 0.05); color:#2563eb; border:1px solid rgba(37, 99, 235, 0.1); font-size:0.75rem; font-weight:600; }

.total-badge { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:0; min-height:unset; border-radius:0; background:transparent; color:#059669; border:none; font-weight:700; }
.historial-badge-yape { background:rgba(124, 58, 237, 0.05); color:#7c3aed; border-color:rgba(124, 58, 237, 0.1); }
</style>
@endpush

@section('content')

<div class="grid grid-cols-12 gap-4">
    
    <div class="col-span-12 mb-1">
        <div class="flex flex-col gap-0.5">
            <h1 class="text-xl font-bold text-slate-800">{{ $title }}</h1>
            <p class="text-xs text-slate-500">{{ $subTitle }}</p>
        </div>
    </div>

    
    <div class="col-span-12">
        <div class="reportes-metrics-grid-4">
            
            <div class="reportes-metric-card gold">
                <div class="reportes-metric-icon-small">
                    <iconify-icon icon="solar:cart-large-2-bold-duotone"></iconify-icon>
                </div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Ventas Hoy</p>
                    <h3 class="reportes-metric-value-medium">{{ $estadisticas['ventas_hoy'] }}</h3>
                    <p class="reportes-metric-comparison">
                        <iconify-icon icon="{{ $estadisticas['cambio_respecto_ayer'] >= 0 ? 'solar:trend-up-bold' : 'solar:trend-down-bold' }}"></iconify-icon>
                        {{ abs($estadisticas['cambio_respecto_ayer']) }} vs ayer
                    </p>
                </div>
            </div>

            
            <div class="reportes-metric-card teal">
                <div class="reportes-metric-icon-small">
                    <iconify-icon icon="solar:wad-of-money-bold-duotone"></iconify-icon>
                </div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Ingresos Hoy</p>
                    <h3 class="reportes-metric-value-medium">S/ {{ number_format($estadisticas['ingresos_hoy'], 2) }}</h3>
                    <p class="reportes-metric-comparison">Dinero en Caja</p>
                </div>
            </div>

            
            <div class="reportes-metric-card purple">
                <div class="reportes-metric-icon-small">
                    <iconify-icon icon="solar:ticket-sale-bold-duotone"></iconify-icon>
                </div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Ticket Promedio</p>
                    <h3 class="reportes-metric-value-medium">
                        S/ {{ $estadisticas['ventas_mes'] > 0 ? number_format($estadisticas['ingresos_mes'] / $estadisticas['ventas_mes'], 2) : '0.00' }}
                    </h3>
                    <p class="reportes-metric-comparison">Media mensual</p>
                </div>
            </div>

            
            <div class="reportes-metric-card red">
                <div class="reportes-metric-icon-small">
                    <iconify-icon icon="solar:calendar-date-bold-duotone"></iconify-icon>
                </div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Ventas del Mes</p>
                    <h3 class="reportes-metric-value-medium">{{ $estadisticas['ventas_mes'] }}</h3>
                    <p class="reportes-metric-comparison">Total del periodo</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-span-12">

        
        

        
        <div class="historial-table-container-improved">
            <div class="historial-table-header-improved">
                <div class="historial-filters-layout-improved">
                    
                    <div class="historial-filters-left-improved">
                        <form method="GET" action="{{ route('ventas.historial') }}" id="filtrosForm">
                            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                                <input type="search" 
                                       name="search"
                                       value="{{ request('search') }}"
                                       class="historial-input-clean" 
                                       placeholder="Buscar ventas..." 
                                       id="searchHistorial"
                                       style="min-width: 220px; flex: 1;">
                                
                                <div class="historial-select-with-label" style="min-width: auto;">
                                    <span class="historial-inline-label">Desde</span>
                                    <input type="date" 
                                           name="fecha_desde" 
                                           value="{{ request('fecha_desde') }}" 
                                           class="historial-input-clean" 
                                           id="fechaDesde"
                                           max="{{ date('Y-m-d') }}"
                                           style="width: 130px; padding: 0.5rem 0.4rem;">
                                </div>
                                
                                <div class="historial-select-with-label" style="min-width: auto;">
                                    <span class="historial-inline-label">Hasta</span>
                                    <input type="date" 
                                           name="fecha_hasta" 
                                           value="{{ request('fecha_hasta') }}" 
                                           class="historial-input-clean" 
                                           id="fechaHasta"
                                           max="{{ date('Y-m-d') }}"
                                           style="width: 130px; padding: 0.5rem 0.4rem;">
                                </div>
                                
                                <div class="historial-select-with-label" style="min-width: auto;">
                                    <span class="historial-inline-label">Usuario</span>
                                    <div class="historial-select-wrapper-clean">
                                        <select class="historial-select-clean" name="usuario_id" id="filtroUsuario" style="width: 140px;">
                                            <option value="">Todos</option>
                                            @foreach($usuarios as $usuario)
                                                <option value="{{ $usuario->id }}" {{ request('usuario_id') == $usuario->id ? 'selected' : '' }}>
                                                    {{ $usuario->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="historial-select-arrow-clean">
                                            <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    
                    <div class="historial-filters-right">
                        <button type="button" onclick="limpiarFiltrosVentas()" class="historial-btn-limpiar" title="Limpiar todos los filtros">
                            <iconify-icon icon="solar:eraser-bold-duotone"></iconify-icon>
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="historial-table-wrapper-improved">
                <div class="historial-hscroll" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table class="historial-table" id="tablaHistorial" style="min-width:1200px;">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>N° Venta</th>
                            <th>Productos</th>
                            <th>Cliente</th>
                            <th>Método de Pago</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaHistorialBody">
                        @forelse($ventas as $venta)
                            <tr class="historial-row 
                                @if($venta->estado === 'devuelta') venta-anulada-row 
                                @elseif($venta->estado === 'parcialmente_devuelta') venta-parcial-row 
                                @endif">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <iconify-icon icon="solar:calendar-bold-duotone" style="color: #94a3b8; font-size: 1.2rem;"></iconify-icon>
                                        <div>
                                            <div class="historial-date" style="font-weight: 600; color: #64748b; font-size: 0.85rem;">
                                                {{ optional($venta->fecha_venta ?? $venta->created_at)->format('d/m/Y') ?? '-' }}
                                            </div>
                                            <div class="historial-time" style="font-size: 0.7rem; color: #94a3b8;">
                                                {{ optional($venta->fecha_venta ?? $venta->created_at)->format('g:i A') ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="historial-sale-info">
                                        <div class="historial-sale-number" style="font-weight: 500; color: #64748b; font-size: 0.85rem;">
                                            #{{ $venta->numero_venta }}
                                        </div>
                                        <div style="margin-top: 4px;">
                                            @if($venta->estado === 'devuelta')
                                                <span class="status-badge-premium status-returned">
                                                    Anulada
                                                </span>
                                            @elseif($venta->estado === 'parcialmente_devuelta')
                                                <span class="status-badge-premium status-partial">
                                                    Parcial
                                                </span>
                                            @else
                                                <span class="status-badge-premium status-paid">
                                                    Pagado
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            <td>
                                <div class="historial-quantity-container">
                                    @php
                                        $presentaciones = [];
                                        foreach ($venta->detalles as $detalle) {
                                            $nombrePres = $detalle->presentacion_nombre ?: 'Unidad';
                                            $presentaciones[$nombrePres] = ($presentaciones[$nombrePres] ?? 0) + $detalle->cantidad;
                                        }
                                    @endphp
                                    
                                    @foreach($presentaciones as $nombre => $cantidad)
                                        @php
                                            $cleanName = strtolower($nombre);
                                            $class = 'pres-generico';
                                            if (str_contains($cleanName, 'unidad')) $class = 'pres-unidad';
                                            elseif (str_contains($cleanName, 'blister')) $class = 'pres-blister';
                                            elseif (str_contains($cleanName, 'caja')) $class = 'pres-caja';
                                        @endphp
                                        <span class="presentacion-item-badge {{ $class }}">
                                            {{ $cantidad }} {{ $nombre }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                @php($cliente = $venta->cliente)
                                @if($cliente)
                                    <div class="historial-provider-name" style="font-weight: 600; color: #475569; font-size: 0.85rem;">{{ $cliente->nombre_completo }}</div>
                                    @if($cliente->dni)
                                        <div class="cliente-doc" style="margin-top: 2px; font-size: 0.7rem;">DNI: {{ $cliente->dni }}</div>
                                    @endif
                                @elseif(!empty($venta->cliente_razon_social))
                                    <div class="historial-provider-name" style="font-weight: 600; color: #475569; font-size: 0.85rem;">{{ $venta->cliente_razon_social }}</div>
                                @else
                                    <span class="historial-badge" style="background: rgba(148, 163, 184, 0.08); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.15); font-size: 0.75rem; padding: 4px 10px;">
                                        Cliente General
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="historial-payment-method">
                                    @if($venta->metodo_pago == 'efectivo')
                                        <span class="historial-badge" style="background: rgba(16, 185, 129, 0.05); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.12); font-size: 0.75rem;">
                                            <iconify-icon icon="solar:wad-of-money-bold-duotone" style="font-size: 1rem;"></iconify-icon>
                                            Efectivo
                                        </span>
                                    @elseif($venta->metodo_pago == 'tarjeta')
                                        <span class="historial-badge" style="background: rgba(37, 99, 235, 0.05); color: #2563eb; border: 1px solid rgba(37, 99, 235, 0.12); font-size: 0.75rem;">
                                            <iconify-icon icon="solar:card-bold-duotone" style="font-size: 1rem;"></iconify-icon>
                                            Tarjeta
                                        </span>
                                    @elseif($venta->metodo_pago == 'yape')
                                        <span class="historial-badge" style="background: rgba(124, 58, 237, 0.05); color: #7c3aed; border: 1px solid rgba(124, 58, 237, 0.12); font-size: 0.75rem;">
                                            <iconify-icon icon="solar:phone-bold-duotone" style="font-size: 1rem;"></iconify-icon>
                                            Yape
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="historial-price-total">
                                    @if($venta->estado === 'devuelta')
                                        <span class="total-tachado">S/ {{ number_format($venta->total, 2) }}</span>
                                        <span style="color: #ef4444; font-weight: 700; font-size: 1.05rem;">S/ 0.00</span>
                                    @elseif($venta->tiene_devoluciones)
                                        <span class="total-tachado">S/ {{ number_format($venta->total, 2) }}</span>
                                        <span class="price-total-green">S/ {{ number_format($venta->total_actual, 2) }}</span>
                                    @else
                                        <span class="price-total-green">S/ {{ number_format($venta->total, 2) }}</span>
                                    @endif

                                    @if($venta->metodo_pago == 'efectivo' && $venta->vuelto > 0)
                                        <span class="vuelto-label">Vuelto: S/ {{ number_format($venta->vuelto, 2) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="historial-actions-improved">
                                    <button class="action-btn-view" onclick="mostrarDetalleVenta({{ $venta->id }})" title="Ver Detalles">
                                        <iconify-icon icon="heroicons:eye"></iconify-icon>
                                    </button>
                                    <button class="action-btn-print" onclick="mostrarModalImpresionHistorial({{ $venta->id }})" title="Imprimir">
                                        <iconify-icon icon="heroicons:printer"></iconify-icon>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div style="padding: 4rem 2rem; text-align: center;">
                                    <div style="width: 80px; height: 80px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                                        <iconify-icon icon="solar:magnifer-zoom-out-bold-duotone" style="font-size: 3rem;"></iconify-icon>
                                    </div>
                                    <h3 style="font-weight: 800; color: #1e293b; font-size: 1.5rem; margin-bottom: 0.5rem;">No se encontraron resultados</h3>
                                    <p style="color: #64748b; font-size: 1rem;">No hay ventas registradas que coincidan con los filtros aplicados.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <div class="historial-hscroll-track">
                    <div class="historial-hscroll-thumb"></div>
                </div>
                
                
                @if($ventas->hasPages())
                <div class="historial-pagination-improved">
                    
                    <div class="historial-pagination-info">
                        <p class="text-sm text-gray-700">
                            Mostrando 
                            <span class="font-medium">{{ $ventas->firstItem() }}</span>
                            a 
                            <span class="font-medium">{{ $ventas->lastItem() }}</span>
                            de 
                            <span class="font-medium">{{ $ventas->total() }}</span>
                            ventas
                        </p>
                    </div>
                    
                    
                    <div class="historial-pagination-controls">
                        {{-- Botón Primera página --}}
                        @if ($ventas->onFirstPage())
                            <span class="historial-pagination-btn historial-pagination-btn-disabled">
                                Primera
                            </span>
                        @else
                            <a href="{{ $ventas->url(1) }}" class="historial-pagination-btn">
                                Primera
                            </a>
                        @endif
                        
                        {{-- Botón Anterior --}}
                        @if ($ventas->onFirstPage())
                            <span class="historial-pagination-btn historial-pagination-btn-disabled">
                                ‹ Anterior
                            </span>
                        @else
                            <a href="{{ $ventas->previousPageUrl() }}" class="historial-pagination-btn">
                                ‹ Anterior
                            </a>
                        @endif
                        
                        {{-- Números de página --}}
                        @foreach ($ventas->getUrlRange(max(1, $ventas->currentPage() - 2), min($ventas->lastPage(), $ventas->currentPage() + 2)) as $page => $url)
                            @if ($page == $ventas->currentPage())
                                <span class="historial-pagination-btn historial-pagination-btn-current">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="historial-pagination-btn">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                        
                        {{-- Botón Siguiente --}}
                        @if ($ventas->hasMorePages())
                            <a href="{{ $ventas->nextPageUrl() }}" class="historial-pagination-btn">
                                Siguiente ›
                            </a>
                        @else
                            <span class="historial-pagination-btn historial-pagination-btn-disabled">
                                Siguiente ›
                            </span>
                        @endif
                        
                        {{-- Botón Última página --}}
                        @if ($ventas->hasMorePages())
                            <a href="{{ $ventas->url($ventas->lastPage()) }}" class="historial-pagination-btn">
                                Última
                            </a>
                        @else
                            <span class="historial-pagination-btn historial-pagination-btn-disabled">
                                Última
                            </span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function limpiarFiltrosVentas() {
        const form = document.getElementById('filtrosForm');
        form.querySelectorAll('input').forEach(i => i.value = '');
        form.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
        form.submit();
    }

    if (typeof window.mostrarModalImpresionHistorial !== 'function') {
    window.mostrarModalImpresionHistorial = function(ventaId) {
        Swal.fire({
            title: '',
            html: '\
                <div style="text-align:center; padding-top:6px;">\
                    <div style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:56px; border-radius:50%; background:#f3f4f6; color:#374151; margin-bottom:10px;">\
                        <iconify-icon icon="mdi:printer" style="font-size:28px"></iconify-icon>\
                    </div>\
                    <div style="font-size:20px; font-weight:700; color:#111827; margin-bottom:10px;">Imprimir comprobante de venta</div>\
                    <div id="impresionVentaContenido" style="text-align:left; padding-top:8px;">Seleccione una opción:</div>\
                </div>\
            ',
            showConfirmButton: false,
            showCloseButton: true,
            allowOutsideClick: false,
            width: '520px'
        });
        var cont = document.getElementById('impresionVentaContenido');
        if (cont) {
            cont.innerHTML = '\
            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">\
                <button id="swalBoleta" style="padding:10px 14px; border-radius:10px; background:#dc2626; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">\
                    <iconify-icon icon="mdi:file-document-outline" style="font-size:18px"></iconify-icon>\
                    <span>Boleta</span>\
                </button>\
                <button id="swalTicket" style="padding:10px 14px; border-radius:10px; background:#2563eb; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">\
                    <iconify-icon icon="mdi:receipt-outline" style="font-size:18px"></iconify-icon>\
                    <span>Ticket</span>\
                </button>\
                <button id="swalWhatsApp" style="padding:10px 14px; border-radius:10px; background:#25d366; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">\
                    <iconify-icon icon="mdi:whatsapp" style="font-size:18px"></iconify-icon>\
                    <span>WhatsApp</span>\
                </button>\
            </div>';
            var b = document.getElementById('swalBoleta');
            var t = document.getElementById('swalTicket');
            var w = document.getElementById('swalWhatsApp');
            if (b) b.addEventListener('click', function(){
                try {
                    var iframe = document.createElement('iframe');
                    iframe.style.position = 'fixed'; iframe.style.right = '0'; iframe.style.bottom = '0'; iframe.style.width = '0'; iframe.style.height = '0'; iframe.style.border = '0';
                    iframe.setAttribute('aria-hidden','true'); iframe.src = '/punto-venta/boleta/'+ventaId; document.body.appendChild(iframe);
                    iframe.onload = function(){ try { var iw = iframe.contentWindow; iw.focus(); iw.print(); } catch(e){ window.open(iframe.src,'_blank'); } };
                } catch(e) { window.open('/punto-venta/boleta/'+ventaId,'_blank'); }
            });
            if (t) t.addEventListener('click', function(){
                try {
                    var iframe = document.createElement('iframe');
                    iframe.style.position = 'fixed'; iframe.style.right = '0'; iframe.style.bottom = '0'; iframe.style.width = '0'; iframe.style.height = '0'; iframe.style.border = '0';
                    iframe.setAttribute('aria-hidden','true'); iframe.src = '/punto-venta/ticket/'+ventaId+'?formato=pdf&w=80'; document.body.appendChild(iframe);
                    iframe.onload = function(){ try { var iw = iframe.contentWindow; iw.focus(); iw.print(); } catch(e){ window.open(iframe.src,'_blank'); } };
                } catch(e) { window.open('/punto-venta/ticket/'+ventaId+'?formato=pdf&w=80','_blank'); }
            });
            if (w) w.addEventListener('click', function(){
                Swal.fire({
                    title: '<i class="fab fa-whatsapp" style="color: #25d366;"></i> Enviar por WhatsApp',
                    html: '\
                        <div style="text-align: left; padding: 10px;">\
                            <input type="hidden" id="whatsapp-formato" value="ticket" />\
                            <div style="margin-bottom: 15px;">\
                                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Número de teléfono del cliente:</label>\
                                <input type="tel" id="whatsapp-phone" class="swal2-input" placeholder="Ej: 987654321" style="margin: 0; width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;" maxlength="9" pattern="[0-9]{9}">\
                                <small style="color: #666; font-size: 12px;">Ingrese solo los 9 dígitos (sin +51)</small>\
                            </div>\
                        </div>',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fab fa-whatsapp"></i> Enviar WhatsApp',
                    cancelButtonText: '<i class="fas fa-arrow-left"></i> Volver',
                    confirmButtonColor: '#25d366',
                    cancelButtonColor: '#6c757d',
                    width: '450px',
                    allowOutsideClick: false,
                    buttonsStyling: false,
                    preConfirm: function(){ var phone = document.getElementById('whatsapp-phone').value.trim(); if(!/^[0-9]{9}$/.test(phone)){ Swal.showValidationMessage('El número debe tener exactamente 9 dígitos'); return false; } return phone; }
                }).then(function(result){
                    if (result.isConfirmed) {
                        fetch('/api/whatsapp/enviar-boleta', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                            body: JSON.stringify({ venta_id: ventaId, telefono: result.value, tipo_comprobante: (document.getElementById('whatsapp-formato')?.value) || 'ticket', guardar_en_cliente: false })
                        }).then(function(r){ return r.json(); }).then(function(data){
                            if (data.success) {
                                Swal.fire({ icon:'success', title:'¡Listo para enviar!', timer:2500, showConfirmButton:false }).then(function(){ window.open(data.url_whatsapp || data.whatsapp_url, '_blank'); });
                            } else { Swal.fire({ icon:'error', title:'Error al enviar', text: data.message || 'No se pudo preparar el mensaje.' }); }
                        }).catch(function(){ Swal.fire({ icon:'error', title:'Error al enviar', text:'No se pudo preparar el mensaje.' }); });
                    }
                });
            });
        }
    };
}

document.addEventListener('DOMContentLoaded', function() {
    const fechaDesde = document.getElementById('fechaDesde');
    const fechaHasta = document.getElementById('fechaHasta');
    const filtrosForm = document.getElementById('filtrosForm');
    
    if (fechaDesde && fechaHasta) {

        fechaDesde.addEventListener('change', function() {
            if (fechaHasta.value && fechaDesde.value > fechaHasta.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha inválida',
                    text: 'La fecha "Desde" no puede ser mayor que la fecha "Hasta"',
                    confirmButtonColor: '#dc2626'
                });
                fechaDesde.value = '';
            }

            if (fechaDesde.value) {
                fechaHasta.min = fechaDesde.value;
            }
        });
        
        fechaHasta.addEventListener('change', function() {
            if (fechaDesde.value && fechaHasta.value < fechaDesde.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha inválida',
                    text: 'La fecha "Hasta" no puede ser menor que la fecha "Desde"',
                    confirmButtonColor: '#dc2626'
                });
                fechaHasta.value = '';
            }
        });

        fechaDesde.addEventListener('change', function() {
            if (this.value) {
                filtrosForm.submit();
            }
        });
        
        fechaHasta.addEventListener('change', function() {
            if (this.value) {
                filtrosForm.submit();
            }
        });
    }
});
</script>
@endpush
