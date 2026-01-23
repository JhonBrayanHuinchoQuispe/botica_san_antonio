@extends('layout.layout')
@php
    $title = 'Salud del Inventario';
    $subTitle = 'Análisis de valorización, vencimientos y rotación';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="' . asset('assets/js/reportes/inventario-salud.js') . '?v=' . time() . '"></script>
    ';
@endphp

<head>
    <title>Salud del Inventario</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ventas/ventas.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ventas/reportes-modern.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ventas/reportes-modern-badges.css') }}?v={{ time() }}">
    <style>
        .reportes-metric-card.gold {
            background: #fff7ed;
            border: 1px solid #ffedd5;
            color: #9a3412;
        }
        .reportes-metric-card.gold .reportes-metric-icon-small {
            background: #ffedd5;
            color: #f59e0b;
        }
        .reportes-metric-card.gold .reportes-metric-value-medium {
            color: #c2410c;
        }
        .reportes-metric-card.gold .reportes-metric-label, 
        .reportes-metric-card.gold .reportes-metric-comparison {
            color: #9a3412;
            opacity: 0.8;
        }

        .reportes-metric-card.red {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #991b1b;
        }
        .reportes-metric-card.red .reportes-metric-icon-small {
            background: #fee2e2;
            color: #ef4444;
        }
        .reportes-metric-card.red .reportes-metric-value-medium {
            color: #b91c1c;
        }
        .reportes-metric-card.red .reportes-metric-label, 
        .reportes-metric-card.red .reportes-metric-comparison {
            color: #991b1b;
            opacity: 0.8;
        }

        
        .reportes-metric-card.purple { background: #f5f3ff; border: 1px solid #ede9fe; color: #5b21b6; }
        .reportes-metric-card.purple .reportes-metric-icon-small { background: #ede9fe; color: #8b5cf6; }
        .reportes-metric-card.purple .reportes-metric-value-medium { color: #6d28d9; }
        
        .reportes-metric-card.teal { background: #f0fdfa; border: 1px solid #ccfbf1; color: #115e59; }
        .reportes-metric-card.teal .reportes-metric-icon-small { background: #ccfbf1; color: #14b8a6; }
        .reportes-metric-card.teal .reportes-metric-value-medium { color: #0f766e; }

        .reportes-alert-item {
            display: flex;
            align-items: stretch;
            gap: 15px;
            padding: 0;
            border-radius: 12px;
            margin-bottom: 15px;
            overflow: hidden;
            border: 1px solid transparent;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .reportes-alert-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            font-size: 1.8rem;
        }
        .reportes-alert-content {
            padding: 12px 15px;
            flex: 1;
        }
        .reportes-alert-title {
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        .reportes-alert-list {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .reportes-alert-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            background: rgba(255,255,255,0.5);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .reportes-alert-item.danger { background: #fff1f2; border-color: #fecdd3; }
        .reportes-alert-item.danger .reportes-alert-icon { background: #fecdd3; color: #e11d48; }
        .reportes-alert-item.danger .reportes-alert-title { color: #9f1239; }

        .reportes-alert-item.warning { background: #fffbeb; border-color: #fef3c7; }
        .reportes-alert-item.warning .reportes-alert-icon { background: #fef3c7; color: #d97706; }
        .reportes-alert-item.warning .reportes-alert-title { color: #92400e; }

        .reportes-alert-item.info { background: #f0f9ff; border-color: #e0f2fe; }
        .reportes-alert-item.info .reportes-alert-icon { background: #e0f2fe; color: #0284c7; }
        .reportes-alert-item.info .reportes-alert-title { color: #075985; }
        
        
        .reportes-mini-table-container {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e9d5ff; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .reportes-mini-table thead tr {
            background: #f5f3ff; 
        }
        .reportes-mini-table th {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 2px solid #ddd6fe;
            color: #5b21b6; 
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        
        .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            gap: 5px;
        }
        .page-item .page-link {
            border-radius: 8px;
            padding: 8px 14px;
            color: #6d28d9;
            background-color: #f5f3ff;
            border: 1px solid #ddd6fe;
            font-weight: 600;
        }
        .page-item.active .page-link {
            background-color: #7c3aed;
            border-color: #7c3aed;
            color: white;
        }
        .pagination-info {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 10px;
        }
        .reportes-mini-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
        }
        .reportes-mini-table tr:hover {
            background-color: #f8fafc;
        }
        .reportes-mini-table tr:last-child td {
            border-bottom: none;
        }
        .reportes-badge-muerto {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .reportes-val-invertido {
            font-weight: 700;
            color: #111827;
        }

        
        .badge-vencido {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            font-weight: 700;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
        }
        .badge-por-vencer {
            background-color: #fffbeb;
            color: #92400e;
            border: 1px solid #fef3c7;
            font-weight: 700;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
        }
        .badge-stock-cero {
            background-color: #000000;
            color: #ffffff;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .alertas-container-layout {
            display: grid;
            grid-template-columns: 1fr 250px;
            gap: 20px;
            align-items: center;
        }
        .alertas-decoration {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            opacity: 0.15;
            padding: 20px;
        }
        .alertas-decoration iconify-icon {
            font-size: 8rem;
            margin-bottom: 10px;
        }
    </style>
</head>

@section('content')

<div class="reportes-professional-container">

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <iconify-icon icon="solar:danger-bold" style="vertical-align: middle; margin-right: 5px;"></iconify-icon>
            <strong>¡Atención!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    
    <div class="reportes-quick-filters-section">
        <div class="reportes-quick-filters-header">
            <div class="reportes-filters-title">
                <iconify-icon icon="solar:chart-square-bold-duotone" style="font-size: 1.5rem; color: #3b82f6;"></iconify-icon>
                <span>Resumen de Salud</span>
            </div>
            <a href="{{ route('admin.reportes.inventario.exportar') }}" class="reportes-export-btn-modern">
                <iconify-icon icon="solar:download-minimalistic-bold"></iconify-icon>
                Exportar Excel
            </a>
        </div>
        
        <div class="reportes-period-display">
            <iconify-icon icon="solar:info-circle-bold-duotone"></iconify-icon>
            <span>Datos actualizados en tiempo real basados en stock actual y lotes registrados.</span>
        </div>
    </div>

    
    <div class="reportes-metrics-grid-4">
        <div class="reportes-metric-card gold">
            <div class="reportes-metric-icon-small">
                <iconify-icon icon="solar:money-bag-bold"></iconify-icon>
            </div>
            <div class="reportes-metric-content">
                <div class="reportes-metric-label">Valorización Total</div>
                <div class="reportes-metric-value-medium">S/ {{ number_format($valorizacion, 2) }}</div>
                <div class="reportes-metric-comparison">
                    <span>Inversión en stock</span>
                </div>
            </div>
        </div>
        
        <div class="reportes-metric-card red">
            <div class="reportes-metric-icon-small">
                <iconify-icon icon="solar:danger-bold"></iconify-icon>
            </div>
            <div class="reportes-metric-content">
                <div class="reportes-metric-label">Lotes Vencidos</div>
                <div class="reportes-metric-value-medium">{{ $vencimientoRiesgo['vencidos'] }}</div>
                <div class="reportes-metric-comparison">
                    <span>Pérdida potencial</span>
                </div>
            </div>
        </div>
        
        <div class="reportes-metric-card purple">
            <div class="reportes-metric-icon-small">
                <iconify-icon icon="solar:ghost-bold"></iconify-icon>
            </div>
            <div class="reportes-metric-content">
                <div class="reportes-metric-label">Productos Muertos</div>
                <div class="reportes-metric-value-medium">{{ $productosMuertosCount }}</div>
                <div class="reportes-metric-comparison">
                    <span>Sin ventas (90 días)</span>
                </div>
            </div>
        </div>
        
        <div class="reportes-metric-card teal">
            <div class="reportes-metric-icon-small">
                <iconify-icon icon="solar:box-minimalistic-bold"></iconify-icon>
            </div>
            <div class="reportes-metric-content">
                <div class="reportes-metric-label">Quiebres de Stock</div>
                <div class="reportes-metric-value-medium">{{ $quiebresStockCount }}</div>
                <div class="reportes-metric-comparison">
                    <span>Productos agotados</span>
                </div>
            </div>
        </div>
    </div>

    
    <div class="reportes-charts-row">
        
        <div class="reportes-chart-card">
            <div class="reportes-chart-header">
                <div class="reportes-chart-title-group">
                    <iconify-icon icon="solar:calendar-mark-bold-duotone" style="font-size: 1.75rem; color: #f59e0b;"></iconify-icon>
                    <div>
                        <h3>Mapa de Riesgo de Vencimiento</h3>
                        <p>Distribución de lotes por proximidad de vencimiento</p>
                    </div>
                </div>
            </div>
            <div class="reportes-chart-body">
                <canvas id="vencimientoRiesgoChart" style="max-height: 300px;"></canvas>
            </div>
        </div>

        
        <div class="reportes-chart-card">
            <div class="reportes-chart-header">
                <div class="reportes-chart-title-group">
                    <iconify-icon icon="solar:ranking-bold-duotone" style="font-size: 1.75rem; color: #3b82f6;"></iconify-icon>
                    <div>
                        <h3>Top 5 Valorización</h3>
                        <p>Productos con mayor capital invertido</p>
                    </div>
                </div>
            </div>
            <div class="reportes-chart-body">
                <canvas id="topValorizacionChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    
    <div class="reportes-charts-row" style="margin-top: 20px; align-items: flex-start;">
        
        
        <div class="reportes-chart-card" style="flex: 1.5;">
            <div class="reportes-chart-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <div class="reportes-chart-title-group">
                    <iconify-icon icon="solar:ghost-bold-duotone" style="font-size: 1.75rem; color: #8b5cf6;"></iconify-icon>
                    <div>
                        <h3>Productos "Muertos"</h3>
                        <p>Stock con baja rotación (Sin ventas 90 días)</p>
                    </div>
                </div>
            </div>
            <div class="reportes-chart-body" style="padding: 1.5rem; display: flex; flex-direction: column; width: 100%;">
                <div class="reportes-mini-table-container" style="width: 100%;">
                    <table class="reportes-mini-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="padding: 15px;">Producto / Categoría</th>
                                <th style="text-align: center; padding: 15px;">P. Compra</th>
                                <th style="text-align: center; padding: 15px;">Stock</th>
                                <th style="padding: 15px;">Valor Invertido</th>
                                <th style="padding: 15px; text-align: center;">Sin vender</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productosMuertosLista as $prod)
                                <tr>
                                    <td style="padding: 12px 15px;">
                                        <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem;">{{ $prod->nombre }}</div>
                                        <div style="font-size: 0.8rem; color: #64748b;">{{ $prod->categoria_model->nombre ?? $prod->categoria ?? 'Sin Categoría' }}</div>
                                    </td>
                                    <td style="text-align: center; padding: 12px 15px;">
                                        <span style="font-weight: 600; color: #475569;">S/ {{ number_format($prod->precio_compra, 2) }}</span>
                                    </td>
                                    <td style="text-align: center; padding: 12px 15px;">
                                        <span class="reportes-val-invertido" style="font-size: 1rem;">{{ $prod->stock_actual }}</span>
                                    </td>
                                    <td class="reportes-val-invertido" style="padding: 12px 15px; font-size: 0.95rem; color: #0f172a;">
                                        S/ {{ number_format($prod->stock_actual * $prod->precio_compra, 2) }}
                                    </td>
                                    <td style="text-align: center; padding: 12px 15px;">
                                        <span class="reportes-badge-muerto" style="background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2;">
                                            {{ $prod->dias_sin_venta ? $prod->dias_sin_venta . ' días' : '+90 días' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <iconify-icon icon="solar:smile-circle-bold-duotone" style="font-size: 3rem; margin-bottom: 10px;"></iconify-icon>
                                        <p>¡Excelente! Todos tus productos tienen rotación.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                
                <div style="margin-top: 25px; display: flex; flex-direction: column; align-items: center; width: 100%; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                    <div class="pagination-info" style="font-weight: 500; color: #475569;">
                        Mostrando {{ $productosMuertosLista->firstItem() }} a {{ $productosMuertosLista->lastItem() }} de {{ $productosMuertosLista->total() }} resultados
                    </div>
                    <nav>
                        <ul class="pagination">
                            {{-- Botón Anterior --}}
                            @if ($productosMuertosLista->onFirstPage())
                                <li class="page-item disabled"><span class="page-link">Anterior</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $productosMuertosLista->previousPageUrl() }}&muertos_page={{ $productosMuertosLista->currentPage()-1 }}" rel="prev">Anterior</a></li>
                            @endif

                            {{-- Números de Página --}}
                            @foreach ($productosMuertosLista->getUrlRange(max(1, $productosMuertosLista->currentPage() - 2), min($productosMuertosLista->lastPage(), $productosMuertosLista->currentPage() + 2)) as $page => $url)
                                <li class="page-item {{ $page == $productosMuertosLista->currentPage() ? 'active' : '' }}">
                                    <a class="page-link" href="{{ $url }}&muertos_page={{ $page }}">{{ $page }}</a>
                                </li>
                            @endforeach

                            {{-- Botón Siguiente --}}
                            @if ($productosMuertosLista->hasMorePages())
                                <li class="page-item"><a class="page-link" href="{{ $productosMuertosLista->nextPageUrl() }}&muertos_page={{ $productosMuertosLista->currentPage()+1 }}" rel="next">Siguiente</a></li>
                            @else
                                <li class="page-item disabled"><span class="page-link">Siguiente</span></li>
                            @endif
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        
        <div class="reportes-chart-card" style="flex: 1;">
            <div class="reportes-chart-header" style="background: #fdf2f2; border-bottom: 1px solid #fee2e2;">
                <div class="reportes-chart-title-group">
                    <iconify-icon icon="solar:bell-bing-bold-duotone" style="font-size: 1.5rem; color: #ef4444;"></iconify-icon>
                    <div>
                        <h3 style="color: #991b1b;">Alertas Críticas</h3>
                        <p style="color: #b91c1c; opacity: 0.7;">Acciones inmediatas recomendadas</p>
                    </div>
                </div>
            </div>
            <div class="reportes-chart-body" style="padding: 1.5rem;">
                
                @if($vencimientoRiesgo['vencidos'] > 0 || $vencimientoRiesgo['proximos_3'] > 0 || $quiebresStockCount > 0)
                    <div class="reportes-mini-table-container" style="border-color: #fee2e2;">
                        <table class="reportes-mini-table">
                            <thead>
                                <tr style="background: #fef2f2;">
                                    <th style="color: #991b1b; border-bottom-color: #fecaca; padding: 12px;">Alerta / Detalle</th>
                                    <th style="color: #991b1b; border-bottom-color: #fecaca; padding: 12px; text-align: center;">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($vencimientoRiesgo['vencidos'] > 0)
                                    @foreach($lotesVencidosDetalle as $lote)
                                        <tr>
                                            <td style="padding: 10px 15px;">
                                                <div style="font-weight: 700; color: #9f1239; font-size: 0.85rem;">RETIRO: {{ $lote->producto->nombre }}</div>
                                                <div style="font-size: 0.75rem; color: #be123c;">Lote: {{ $lote->lote }}</div>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge-vencido">VENCIDO</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                @if($vencimientoRiesgo['proximos_3'] > 0)
                                    @foreach($lotesProximosDetalle as $lote)
                                        <tr>
                                            <td style="padding: 10px 15px;">
                                                <div style="font-weight: 700; color: #92400e; font-size: 0.85rem;">PROMOCIÓN: {{ $lote->producto->nombre }}</div>
                                                <div style="font-size: 0.75rem; color: #b45309;">Vence: {{ $lote->fecha_vencimiento->format('d/m/Y') }}</div>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge-por-vencer">POR VENCER</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif

                                @if($quiebresStockCount > 0)
                                    @foreach($quiebresStockLista as $prod)
                                        <tr>
                                            <td style="padding: 10px 15px;">
                                                <div style="font-weight: 700; color: #1e293b; font-size: 0.85rem;">REPOSICIÓN: {{ $prod->nombre }}</div>
                                                <div style="font-size: 0.75rem; color: #64748b;">Agotado en almacén</div>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge-stock-cero">STOCK 0</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align: center; padding: 30px; color: #059669;">
                        <iconify-icon icon="solar:check-circle-bold" style="font-size: 3rem; margin-bottom: 10px;"></iconify-icon>
                        <p>No hay alertas críticas pendientes.</p>
                    </div>
                @endif

            </div>
        </div>
    </div>

</div>

<script>

    window.vencimientoData = @json($vencimientoRiesgo);
    window.topValorizacionData = @json($topValorizacion->map(function($item) {
        return [
            'nombre' => $item->producto->nombre ?? 'N/A',
            'valor' => (float)$item->total_valor
        ];
    }));
</script>

@endsection
