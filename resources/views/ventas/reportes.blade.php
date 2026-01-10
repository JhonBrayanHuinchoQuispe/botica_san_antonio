@extends('layout.layout')
@php
    $title = 'Reportes de Ventas';
    $subTitle = 'Análisis y métricas de ventas';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
        <script src="' . asset('assets/js/ventas/reportes.js') . '?v=' . time() . '"></script>
    ';
@endphp

<head>
    <title>Reportes de Ventas</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="usuario-nombre" content="{{ auth()->user()->name ?? '' }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ventas/ventas.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ventas/reportes-modern.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

@section('content')

<div class="grid grid-cols-12">
    <div class="col-span-12">
        
        <!-- Filtro de Período -->
        <div class="historial-table-container-improved" style="margin-bottom: 2rem;">
            <div class="historial-table-header-improved">
                <div class="historial-filters-layout-improved">
                    <div class="historial-filters-left-improved">
                        <form method="GET" action="{{ route('ventas.reportes') }}" id="filtroReporteForm">
                            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-weight: 600; color: #374151;">📊 Período:</span>
                                    <select name="periodo" id="periodoSelect" class="historial-input-clean" style="min-width: 150px;">
                                        <option value="hoy" {{ $periodo == 'hoy' ? 'selected' : '' }}>Hoy</option>
                                        <option value="ultimos7" {{ $periodo == 'ultimos7' ? 'selected' : '' }}>Últimos 7 días</option>
                                        <option value="mes" {{ $periodo == 'mes' ? 'selected' : '' }}>Este mes</option>
                                        <option value="anual" {{ $periodo == 'anual' ? 'selected' : '' }}>Este año</option>
                                        <option value="personalizado" {{ $periodo == 'personalizado' ? 'selected' : '' }}>Personalizado</option>
                                    </select>
                                </div>

                                <div id="fechasPersonalizadas" style="display: none; align-items: center; gap: 0.5rem;">
                                    <input type="date" name="fecha_inicio" id="fechaInicio" class="historial-input-clean">
                                    <span style="color: #6b7280;">-</span>
                                    <input type="date" name="fecha_fin" id="fechaFin" class="historial-input-clean">
                                </div>

                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-weight: 600; color: #374151;">👤 Vendedor:</span>
                                    <select name="usuario_id" id="usuarioSelect" class="historial-input-clean" style="min-width: 150px;">
                                        <option value="">Todos</option>
                                        @foreach($usuarios as $usuario)
                                            <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <button type="button" id="btnAplicarFiltros" class="historial-btn-filtrar" style="padding: 0.5rem 1rem; background-color: #3b82f6; color: white; border-radius: 0.375rem; border: none; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                                    <iconify-icon icon="solar:filter-bold"></iconify-icon> Aplicar
                                </button>
                                
                                <div id="rangoFechasTexto" style="color: #6b7280; font-size: 0.875rem; margin-left: 0.5rem;">
                                    <iconify-icon icon="solar:calendar-bold-duotone"></iconify-icon>
                                    <span id="textoFechas">{{ $datos['fecha_inicio']->format('d/m/Y') }} - {{ $datos['fecha_fin']->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="historial-filters-right">
                        <button onclick="exportarReporte()" class="historial-btn-nueva-entrada">
                            <iconify-icon icon="solar:download-bold-duotone"></iconify-icon>
                            Exportar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Métricas Principales -->
        <div class="reportes-stats-grid mb-4">
            <div class="reportes-stat-card reportes-stat-blue-gradient">
                <div class="reportes-stat-content">
                    <div class="reportes-stat-label">Total Ventas</div>
                    <div class="reportes-stat-value">{{ $datos['total_ventas'] }}</div>
                    <div class="reportes-stat-change">
                        <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon>
                        Ventas realizadas
                    </div>
                </div>
                <div class="reportes-stat-icon">
                    <iconify-icon icon="solar:bag-smile-bold"></iconify-icon>
                </div>
            </div>
            
            <div class="reportes-stat-card reportes-stat-green-gradient">
                <div class="reportes-stat-content">
                    <div class="reportes-stat-label">Ingresos Totales</div>
                    <div class="reportes-stat-value">S/ {{ number_format($datos['total_ingresos'], 2) }}</div>
                    <div class="reportes-stat-change">
                        <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon>
                        Ingresos generados
                    </div>
                </div>
                <div class="reportes-stat-icon">
                    <iconify-icon icon="solar:dollar-minimalistic-bold"></iconify-icon>
                </div>
            </div>
            
            <div class="reportes-stat-card reportes-stat-orange-gradient">
                <div class="reportes-stat-content">
                    <div class="reportes-stat-label">Ticket Promedio</div>
                    <div class="reportes-stat-value">S/ {{ number_format($datos['ticket_promedio'], 2) }}</div>
                    <div class="reportes-stat-change">
                        <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon>
                        Por venta
                    </div>
                </div>
                <div class="reportes-stat-icon">
                    <iconify-icon icon="solar:calculator-bold"></iconify-icon>
                </div>
            </div>
            
            <div class="reportes-stat-card reportes-stat-purple-gradient">
                <div class="reportes-stat-content">
                    <div class="reportes-stat-label">Productos Vendidos</div>
                    <div class="reportes-stat-value">{{ $datos['productos_mas_vendidos']->sum('total_vendido') }}</div>
                    <div class="reportes-stat-change">
                        <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon>
                        Unidades totales
                    </div>
                </div>
                <div class="reportes-stat-icon">
                    <iconify-icon icon="solar:box-bold"></iconify-icon>
                </div>
            </div>
        </div>

        <!-- Gráfico principal: Estadística de ventas -->
        <div class="reportes-chart-container" style="margin-bottom: 1.5rem;">
            <div class="reportes-chart-header" style="align-items: center; justify-content: space-between; display:flex;">
                <div class="reportes-chart-title">
                    <iconify-icon icon="solar:chart-2-bold" class="reportes-chart-icon"></iconify-icon>
                    <span>Estadística de ventas</span>
                </div>
                <div style="display:flex; align-items:center; gap:.75rem;">
                    <span style="font-weight:600; color:#374151;">Periodo:</span>
                    <select id="periodoChartSelect" class="historial-input-clean" style="min-width: 160px;">
                        <option value="hoy" selected>Hoy</option>
                        <option value="ultimos7">Últimos 7 días</option>
                        <option value="mes">Mes</option>
                        <option value="anual">Año</option>
                    </select>
                    <span style="font-weight:600; color:#374151; margin-left: .75rem;">Comparar con:</span>
                    <select id="compararSelect" class="historial-input-clean" style="min-width: 180px;">
                        <option value="ayer">Ayer</option>
                        <option value="semana_anterior">Semana anterior</option>
                        <option value="mes_anterior">Mes anterior</option>
                        <option value="anio_anterior">Año anterior</option>
                    </select>
                    <span id="vistaMesLabel" style="font-weight:600; color:#374151; margin-left: .75rem; display:none;">Vista:</span>
                    <select id="vistaMesSelect" class="historial-input-clean" style="min-width: 160px; display:none;">
                        <option value="semanal" selected>Semanal</option>
                        <option value="diario">Diario</option>
                    </select>
                </div>
            </div>
            <div class="reportes-chart-subtitle" id="reportes-titulo-periodo" style="margin: .25rem 2rem 0; text-align:center;">{{ $datos['fecha_inicio']->format('d/m/Y') }} - {{ $datos['fecha_fin']->format('d/m/Y') }}</div>
            <div class="chart-mini-stats" style="display:flex; align-items:center; gap:1rem; padding: .5rem 2rem; margin-top:.25rem;">
                <div class="mini-stat-value estadisticas-total">S/. {{ number_format(collect($datos['ingresos_por_dia'])->sum('ingresos'), 2) }}</div>
                <span class="mini-stat-badge estadisticas-ventas">{{ $datos['total_ventas'] }} ventas</span>
                <span class="mini-stat-average estadisticas-promedio">+ S/. {{ number_format(collect($datos['ingresos_por_dia'])->avg('ingresos'), 2) }} Por día</span>
                <span class="mini-stat-delta" id="miniStatDelta" style="display:none;"></span>
            </div>
            <div class="reportes-chart-body">
                <div id="ventas-chart-reportes" style="height: 360px; width: 100%;"></div>
                <div id="chart-loading-ventas" class="reportes-chart-loading" style="display: none;">
                    <div class="reportes-loading-spinner"></div>
                    <span>Cargando gráfico...</span>
                </div>
            </div>
        </div>

        <!-- Gráficos secundarios: Métodos de Pago y Top 10 lado a lado -->
        <div class="reportes-charts-grid mb-4">
            <!-- Métodos de Pago -->
            <div class="reportes-chart-container reportes-chart-secondary">
                <div class="reportes-chart-header">
                    <div class="reportes-chart-title">
                        <iconify-icon icon="mdi:credit-card-multiple" class="reportes-chart-icon"></iconify-icon>
                        <span>Métodos de Pago</span>
                    </div>
                    <div class="reportes-chart-subtitle">Distribución de pagos en el período</div>
                </div>
                <div class="reportes-chart-body" style="min-height: 350px;">
                    <div id="chart-loading-metodos" class="reportes-chart-loading" style="display: none;">
                        <div class="reportes-loading-spinner"></div>
                        <span>Cargando gráfico...</span>
                    </div>
                    <canvas id="metodosChart" width="300" height="300"></canvas>
                </div>
            </div>
            
            <!-- Top 10 Productos Más Vendidos -->
            <div class="reportes-table-container">
                <div class="reportes-table-header">
                    <div class="reportes-table-title">
                        <iconify-icon icon="mdi:trophy" class="reportes-table-icon"></iconify-icon>
                        <span>Top 10 Productos Más Vendidos</span>
                    </div>
                    <div class="reportes-table-subtitle">Productos con mayor rotación en el período</div>
                </div>
                <div class="reportes-table-body">
                    @if($datos['productos_mas_vendidos']->count() > 0)
                        <div class="reportes-table-wrapper">
                            <table class="reportes-table" id="topProductosTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th class="text-center">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody id="topProductosBody">
                                    @foreach($datos['productos_mas_vendidos'] as $index => $item)
                                        <tr>
                                            <td>
                                                <div class="reportes-rank-badge reportes-rank-{{ $index < 3 ? 'top' : 'normal' }}">
                                                    {{ $index + 1 }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="reportes-product-info">
                                                    <div class="reportes-product-details">
                                                        <div class="reportes-product-name">{{ $item->producto->nombre }}</div>
                                                        <div class="reportes-product-code">{{ $item->producto->concentracion ?? 'N/A' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="reportes-quantity-badge">{{ (int) $item->total_vendido }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="reportes-empty-state">
                            <iconify-icon icon="solar:box-bold" class="reportes-empty-icon"></iconify-icon>
                            <h6 class="reportes-empty-title">No hay productos vendidos</h6>
                            <p class="reportes-empty-text">No se encontraron productos vendidos en este período</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-span-1">
            <div class="reportes-table-card">
                <div class="reportes-table-header">
                    <div class="reportes-table-title">
                        <iconify-icon icon="mdi:star-box" class="reportes-table-icon"></iconify-icon>
                        <span>Marcas Más Compradas</span>
                    </div>
                    <div class="reportes-table-subtitle">Principales marcas en el período seleccionado</div>
                </div>
                <div class="reportes-table-body">
                    <div class="reportes-table-wrapper">
                        <table class="reportes-table" id="topMarcasTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Marca</th>
                                    <th class="text-center">Unidades</th>
                                </tr>
                            </thead>
                            <tbody id="topMarcasBody">
                                @if(isset($datos['marcas_mas_compradas']) && count($datos['marcas_mas_compradas']) > 0)
                                    @foreach($datos['marcas_mas_compradas'] as $i => $m)
                                        <tr>
                                            <td>
                                                <div class="reportes-rank-badge reportes-rank-{{ $i < 3 ? 'top' : 'normal' }}">{{ $i + 1 }}</div>
                                            </td>
                                            <td>
                                                <div class="reportes-producto">
                                                    <div class="reportes-producto-nombre">{{ $m->marca ?? 'Sin marca' }}</div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="reportes-cantidad">{{ (int)($m->unidades ?? 0) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                        @if(!isset($datos['marcas_mas_compradas']) || count($datos['marcas_mas_compradas']) === 0)
                        <div class="reportes-empty">
                            <p class="reportes-empty-text">Sin datos de marcas para este período</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

<style>
/* Estilos adicionales para reportes */
.historial-ranking {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

/* Responsive para reportes */
@media (max-width: 1024px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 768px) {
    .historial-filters-left-improved div[style*="display: flex"] {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.5rem !important;
    }
    
    .historial-table th:nth-child(4),
    .historial-table td:nth-child(4) {
        display: none;
    }
}
</style>

<script>
// Datos iniciales para gráficos
window.datosIngresos = @json($datos['ingresos_por_dia']);
window.datosMetodos = @json($datos['ventas_por_metodo']);

document.addEventListener('DOMContentLoaded', function() {
    const btn = document.querySelector('.historial-btn-nueva-entrada');
    if (btn) {
        btn.style.opacity = '1';
        btn.style.visibility = 'visible';
        btn.style.display = 'inline-flex';
        btn.style.alignItems = 'center';
        btn.style.gap = '6px';
    }
    // Inicializa gráficos del módulo
    try { inicializarGraficos(); } catch (e) { console.warn('Inicialización diferida de reportes'); }
});
</script>
