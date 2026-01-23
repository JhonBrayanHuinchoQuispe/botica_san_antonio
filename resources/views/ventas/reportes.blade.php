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
    <link rel="stylesheet" href="{{ asset('assets/css/ventas/reportes-modern-badges.css') }}?v={{ time() }}">
</head>

@section('content')

<div class="reportes-professional-container">
    
    <style>
        .reportes-table-modern thead th {
            background-color: #f3f0ff !important;
            color: #6b21a8 !important;
            border-bottom: 2px solid #e9d5ff !important;
        }

        
        #modalExportarReporte .modal-content {
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        #modalExportarReporte .bg-gradient-primary {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important;
        }

        .btn-check:checked + .btn-outline-primary {
            background-color: #eef2ff;
            border-color: #6366f1;
            color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .btn-check:checked + .btn-outline-success {
            background-color: #f0fdf4;
            border-color: #22c55e;
            color: #16a34a;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
        }

        .btn-outline-primary, .btn-outline-success {
            transition: all 0.2s ease;
            border-width: 2px;
        }

        .btn-outline-primary:hover {
            background-color: #f8fafc;
            color: #6366f1;
            border-color: #6366f1;
        }

        .btn-outline-success:hover {
            background-color: #f8fafc;
            color: #22c55e;
            border-color: #22c55e;
        }

        .modal-backdrop.show {
            opacity: 0.7;
            backdrop-filter: blur(4px);
        }
    </style>
    
    
    <div class="reportes-quick-filters-section">
        <div class="reportes-quick-filters-header">
            <div class="reportes-filters-title">
                <iconify-icon icon="solar:filter-bold-duotone" style="font-size: 1.5rem; color: #3b82f6;"></iconify-icon>
                <span>Filtros Rápidos</span>
            </div>
            <button onclick="exportarReporte()" class="reportes-export-btn-modern">
                <iconify-icon icon="solar:download-minimalistic-bold"></iconify-icon>
                Exportar Reporte
            </button>
        </div>
        
        
        <div class="reportes-pills-container">
            <button class="reportes-pill {{ $periodo == 'hoy' ? 'active' : '' }}" data-periodo="hoy">
                <iconify-icon icon="solar:calendar-bold"></iconify-icon>
                Hoy
            </button>
            <button class="reportes-pill {{ $periodo == 'ayer' ? 'active' : '' }}" data-periodo="ayer">
                <iconify-icon icon="solar:history-bold"></iconify-icon>
                Ayer
            </button>
            <button class="reportes-pill {{ $periodo == 'ultimos7' ? 'active' : '' }}" data-periodo="ultimos7">
                <iconify-icon icon="solar:calendar-mark-bold"></iconify-icon>
                Esta Semana
            </button>
            <button class="reportes-pill {{ $periodo == 'mes' ? 'active' : '' }}" data-periodo="mes">
                <iconify-icon icon="solar:calendar-bold-duotone"></iconify-icon>
                Este Mes
            </button>
            <button class="reportes-pill {{ $periodo == 'anual' ? 'active' : '' }}" data-periodo="anual">
                <iconify-icon icon="solar:calendar-date-bold"></iconify-icon>
                Este Año
            </button>
            <button class="reportes-pill {{ $periodo == 'personalizado' ? 'active' : '' }}" data-periodo="personalizado" id="btnPorFecha">
                <iconify-icon icon="solar:calendar-search-bold"></iconify-icon>
                Por Fecha
            </button>
        </div>
        
        
        <div class="reportes-custom-dates" id="fechasPersonalizadasGroup" style="display: none;">
            <div class="reportes-date-inputs">
                <div class="reportes-date-input-group">
                    <label>Desde</label>
                    <input type="date" id="fechaInicio" class="reportes-input-date" max="{{ date('Y-m-d') }}">
                </div>
                <iconify-icon icon="solar:arrow-right-bold" style="font-size: 1.5rem; color: #9ca3af;"></iconify-icon>
                <div class="reportes-date-input-group">
                    <label>Hasta</label>
                    <input type="date" id="fechaFin" class="reportes-input-date" max="{{ date('Y-m-d') }}">
                </div>
                <button id="btnAplicarFechas" class="reportes-apply-dates-btn">
                    <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                    Aplicar
                </button>
                <button id="btnCancelarFechas" class="reportes-cancel-dates-btn">
                    <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                    Cancelar
                </button>
            </div>
        </div>
        
        
        <div class="reportes-period-display">
            <iconify-icon icon="solar:calendar-mark-bold-duotone"></iconify-icon>
            <span id="textoPeriodoActual">Hoy - {{ now()->format('d') }} de {{ now()->locale('es')->translatedFormat('F') }} del {{ now()->format('Y') }}</span>
        </div>
    </div>

    
    <div class="reportes-metrics-grid-4">
        <div class="reportes-metric-card blue">
            <div class="reportes-metric-icon-small">
                <iconify-icon icon="solar:bag-smile-bold"></iconify-icon>
            </div>
            <div class="reportes-metric-content">
                <div class="reportes-metric-label">Total Ventas</div>
                <div class="reportes-metric-value-medium" id="statTotalVentas">{{ $datos['total_ventas'] }}</div>
                <div class="reportes-metric-comparison" id="compTotalVentas">
                    <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                    <span>+0.0% vs período anterior</span>
                </div>
            </div>
        </div>
        
        <div class="reportes-metric-card green">
            <div class="reportes-metric-icon-small">
                <iconify-icon icon="solar:dollar-minimalistic-bold"></iconify-icon>
            </div>
            <div class="reportes-metric-content">
                <div class="reportes-metric-label">Ingresos Totales</div>
                <div class="reportes-metric-value-medium" id="statIngresosTotal">S/ {{ number_format($datos['total_ingresos'], 2) }}</div>
                <div class="reportes-metric-comparison" id="compIngresos">
                    <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                    <span>+0.0% vs período anterior</span>
                </div>
            </div>
        </div>
        
        <div class="reportes-metric-card purple">
            <div class="reportes-metric-icon-small">
                <iconify-icon icon="solar:box-bold"></iconify-icon>
            </div>
            <div class="reportes-metric-content">
                <div class="reportes-metric-label">Productos Vendidos</div>
                <div class="reportes-metric-value-medium" id="statProductosVendidos">{{ $datos['productos_mas_vendidos']->sum('total_vendido') }}</div>
                <div class="reportes-metric-comparison">
                    <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                    <span>Unidades totales</span>
                </div>
            </div>
        </div>
        
        <div class="reportes-metric-card teal">
            <div class="reportes-metric-icon-small">
                <iconify-icon icon="solar:layers-bold"></iconify-icon>
            </div>
            <div class="reportes-metric-content">
                <div class="reportes-metric-label">Productos Únicos</div>
                <div class="reportes-metric-value-medium" id="statProductosUnicos">{{ $datos['productos_mas_vendidos']->count() }}</div>
                <div class="reportes-metric-comparison">
                    <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                    <span>Diferentes productos</span>
                </div>
            </div>
        </div>
    </div>

    
    <div class="reportes-alerts-section">
        <div class="reportes-alert-card warning">
            <iconify-icon icon="solar:danger-triangle-bold"></iconify-icon>
            <div class="reportes-alert-content">
                <div class="reportes-alert-title">Stock Crítico</div>
                <div class="reportes-alert-value" id="alertStockCritico">Cargando...</div>
            </div>
        </div>
        
        <div class="reportes-alert-card info">
            <iconify-icon icon="solar:clock-circle-bold"></iconify-icon>
            <div class="reportes-alert-content">
                <div class="reportes-alert-title">Próximos a Vencer</div>
                <div class="reportes-alert-value" id="alertPorVencer">Cargando...</div>
            </div>
        </div>
        
        <div class="reportes-alert-card danger">
            <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
            <div class="reportes-alert-content">
                <div class="reportes-alert-title">Sin Ventas (7 días)</div>
                <div class="reportes-alert-value" id="alertSinVentas">Cargando...</div>
            </div>
        </div>
        
        <div class="reportes-alert-card success">
            <iconify-icon icon="solar:fire-bold"></iconify-icon>
            <div class="reportes-alert-content">
                <div class="reportes-alert-title">Más Vendido Hoy</div>
                <div class="reportes-alert-value" id="alertMasVendido">Cargando...</div>
            </div>
        </div>
    </div>

    
    <div class="reportes-charts-row">
        
        <div class="reportes-chart-card large">
            <div class="reportes-chart-header">
                <div class="reportes-chart-title-group">
                    <iconify-icon icon="solar:chart-2-bold-duotone" style="font-size: 1.75rem; color: #3b82f6;"></iconify-icon>
                    <div>
                        <h3>Tendencia de Ventas</h3>
                        <p>Evolución de ingresos en el período seleccionado</p>
                        <p id="textoPeriodoComparacion" style="font-size: 0.9rem; font-weight: 600; margin-top: 0.5rem; color: #3b82f6;"></p>
                    </div>
                </div>
                <div class="reportes-chart-controls">
                    <select id="periodoChartSelect" class="reportes-select-mini">
                        <option value="hoy">Hoy</option>
                        <option value="ultimos7">Últimos 7 días</option>
                        <option value="mes" selected>Este mes</option>
                        <option value="anual">Este año</option>
                    </select>
                    <select id="compararSelect" class="reportes-select-mini">
                        <option value="mes_anterior">vs Mes anterior</option>
                        <option value="ayer">vs Ayer</option>
                        <option value="semana_anterior">vs Semana anterior</option>
                        <option value="anio_anterior">vs Año anterior</option>
                    </select>
                </div>
            </div>
            <div class="reportes-chart-body">
                <div id="chart-loading-ventas" class="reportes-chart-loading">
                    <div class="reportes-loading-spinner"></div>
                    <p>Cargando datos...</p>
                </div>
                <div id="ventas-chart-reportes" style="min-height: 380px;"></div>
            </div>
            <div class="reportes-chart-footer">
                <div class="reportes-chart-stat">
                    <iconify-icon icon="solar:arrow-up-bold" style="color: #10b981;"></iconify-icon>
                    <span>Pico: <strong id="statPico">S/ 0.00</strong></span>
                </div>
                <div class="reportes-chart-stat">
                    <iconify-icon icon="solar:arrow-down-bold" style="color: #ef4444;"></iconify-icon>
                    <span>Mínimo: <strong id="statMinimo">S/ 0.00</strong></span>
                </div>
                <div class="reportes-chart-stat">
                    <iconify-icon icon="solar:chart-bold" style="color: #3b82f6;"></iconify-icon>
                    <span>Promedio: <strong id="statPromedio">S/ 0.00</strong></span>
                </div>
            </div>
        </div>
        
        
        <div class="reportes-chart-card small">
            <div class="reportes-chart-header">
                <div class="reportes-chart-title-group">
                    <iconify-icon icon="solar:wallet-bold-duotone" style="font-size: 1.5rem; color: #10b981;"></iconify-icon>
                    <div>
                        <h3>Métodos de Pago</h3>
                        <p>Distribución de pagos</p>
                    </div>
                </div>
            </div>
            <div class="reportes-chart-body">
                <div id="chart-loading-metodos" class="reportes-chart-loading">
                    <div class="reportes-loading-spinner"></div>
                </div>
                <canvas id="metodosChart" style="max-height: 280px;"></canvas>
            </div>
            <div class="reportes-payment-details">
                <div class="reportes-payment-item">
                    <div class="reportes-payment-dot green"></div>
                    <span>Efectivo</span>
                    <strong id="montoEfectivo">S/ 0.00</strong>
                </div>
                <div class="reportes-payment-item">
                    <div class="reportes-payment-dot blue"></div>
                    <span>Tarjeta</span>
                    <strong id="montoTarjeta">S/ 0.00</strong>
                </div>
                <div class="reportes-payment-item">
                    <div class="reportes-payment-dot orange"></div>
                    <span>Yape</span>
                    <strong id="montoYape">S/ 0.00</strong>
                </div>
            </div>
        </div>
    </div>

    
    <div class="reportes-tables-row">
        
        <div class="reportes-table-card">
            <div class="reportes-table-header">
                <div class="reportes-table-title-group">
                    <iconify-icon icon="solar:star-bold-duotone" style="font-size: 1.5rem; color: #f59e0b;"></iconify-icon>
                    <div>
                        <h3>Top 10 Productos Más Vendidos</h3>
                        <p>Productos con mayor rotación en el período</p>
                    </div>
                </div>
            </div>
            <div class="reportes-table-body">
                <table class="reportes-table-modern">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Producto</th>
                            <th style="width: 120px; text-align: center;">Unidades</th>
                            <th style="width: 140px; text-align: right;">Ingresos</th>
                            <th style="width: 100px; text-align: center;">Tendencia</th>
                        </tr>
                    </thead>
                    <tbody id="topProductosBody">
                        @php
                            $totalVendido = $datos['productos_mas_vendidos']->sum('total_vendido');
                        @endphp
                        @foreach($datos['productos_mas_vendidos']->take(10) as $index => $producto)
                        @php
                            $precioPromedio = $producto->precio_promedio ?? 60;
                            $ingresos = ($producto->total_vendido ?? 0) * $precioPromedio;
                            $porcentaje = $totalVendido > 0 ? (($producto->total_vendido ?? 0) / $totalVendido) * 100 : 0;
                        @endphp
                        <tr>
                            <td>
                                <div class="reportes-rank-badge {{ $index < 3 ? 'gold' : 'normal' }}">
                                    {{ $index + 1 }}
                                </div>
                            </td>
                            <td>
                                <div class="reportes-product-info">
                                    <div class="reportes-product-name">{{ $producto->producto->nombre ?? $producto->nombre ?? 'Producto' }}</div>
                                    <div class="reportes-product-detail">{{ $producto->producto->concentracion ?? $producto->concentracion ?? '' }}</div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <div class="reportes-quantity-badge">
                                    {{ $producto->total_vendido ?? 0 }}
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <strong style="color: #10b981; font-size: 0.95rem;">
                                    S/ {{ number_format($ingresos, 2) }}
                                </strong>
                            </td>
                            <td style="text-align: center;">
                                @if($porcentaje >= 20)
                                    <div class="reportes-trend-badge hot">
                                        <iconify-icon icon="solar:fire-bold"></iconify-icon>
                                        Hot
                                    </div>
                                @elseif($porcentaje >= 10)
                                    <div class="reportes-trend-badge up">
                                        <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                                        Subiendo
                                    </div>
                                @else
                                    <div class="reportes-trend-badge normal">
                                        <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                                        Normal
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        
        <div class="reportes-table-card">
            <div class="reportes-table-header">
                <div class="reportes-table-title-group">
                    <iconify-icon icon="solar:tag-bold-duotone" style="font-size: 1.5rem; color: #8b5cf6;"></iconify-icon>
                    <div>
                        <h3>Marcas Más Compradas</h3>
                        <p>Principales marcas en el período</p>
                    </div>
                </div>
            </div>
            <div class="reportes-table-body">
                <table class="reportes-table-modern">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Marca</th>
                            <th style="width: 120px; text-align: center;">Unidades</th>
                        </tr>
                    </thead>
                    <tbody id="topMarcasBody">
                        @foreach($datos['marcas_mas_compradas']->take(10) as $index => $marca)
                        <tr>
                            <td>
                                <div class="reportes-rank-badge {{ $index < 3 ? 'reportes-rank-top' : 'reportes-rank-normal' }}">
                                    {{ $index + 1 }}
                                </div>
                            </td>
                            <td>
                                <div class="reportes-producto">
                                    <div class="reportes-producto-nombre">{{ $marca->marca ?? 'Sin marca' }}</div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="reportes-cantidad">{{ $marca->unidades ?? 0 }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>

    window.datosIngresos = @json($datos['ingresos_por_dia'] ?? []);
    window.datosMetodos = @json($datos['ventas_por_metodo'] ?? []);
    window.datosComparativo = @json($datos['comparativo'] ?? null);
</script>

@endsection

