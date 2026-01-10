@extends('layout.layout')
@php
    $title = 'Historial de Ventas';
    $subTitle = 'Registro completo de ventas realizadas';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/ventas/historial.js') . '?v=' . time() . '"></script>
    ';
@endphp

<head>
    <title>Historial de Ventas</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ventas/ventas.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

@push('head')
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<style>
/* Estilos mejorados para historial de ventas */
.historial-venta-number {
    font-weight: 600;
    color: #374151;
    font-size: 0.95rem;
}

.historial-sunat-code {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 2px;
}

.historial-price-total {
    font-weight: 700;
    color: #059669;
    font-size: 1.1rem;
}

.historial-user-simple {
    font-weight: 500;
    color: #374151;
}

.historial-actions-improved {
    display: flex;
    gap: 8px;
    align-items: center;
}

.action-btn-primary, .action-btn-secondary {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    border-radius: 8px;
    border: none;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    opacity: 1 !important;
    visibility: visible !important;
}

.action-btn-primary {
    background: #eef5ff;
    color: #1e56cf;
    border: 1px solid #cfe0ff;
    box-shadow: none;
}

.action-btn-primary:hover {
    background: #e7f0ff;
    border-color: #bcd4ff;
}

.action-btn-secondary {
    background: #eaf7ee;
    color: #0a7c4a;
    border: 1px solid #bfe7cf;
    box-shadow: none;
}

.action-btn-secondary:hover {
    background: #e3f4e9;
    border-color: #a9dcc0;
}

.action-btn-primary .material-icons,
.action-btn-secondary .material-icons {
    font-size: 16px;
}

/* Asegurar que los botones siempre sean visibles */
.historial-actions-improved button {
    opacity: 1 !important;
    visibility: visible !important;
}

/* Indicador de estado devuelto */
.estado-devuelto {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    color: #dc2626;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    border: 1px solid #fca5a5;
    margin-left: 8px;
}

.estado-devuelto .material-icons {
    font-size: 14px;
}

/* Indicador de estado parcialmente devuelto */
.estado-parcialmente-devuelto {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: linear-gradient(135deg, #fef3e2 0%, #fed7aa 100%);
    color: #ea580c;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 12px;
    border: 1px solid #fdba74;
    margin-left: 8px;
}

.estado-parcialmente-devuelto .material-icons {
    font-size: 14px;
}

/* Estilo para filas de ventas devueltas */
.venta-devuelta {
    background-color: #fef2f2 !important;
    border-left: 4px solid #dc2626 !important;
}

/* Estilo para filas de ventas parcialmente devueltas */
.venta-parcialmente-devuelta {
    background-color: #fef3e2 !important;
    border-left: 4px solid #ea580c !important;
}

/* Información de devolución en el historial */
.devolucion-info {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 2px;
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.devolucion-info .devolucion-detalle {
    display: flex;
    align-items: center;
    gap: 4px;
}

.devolucion-info .material-icons {
    font-size: 12px;
}

.monto-devuelto {
    color: #dc2626;
    font-weight: 600;
}

.productos-devueltos {
    color: #ea580c;
    font-weight: 500;
}

/* Estilos para el modal de detalle de venta */
.swal-popup-detail {
    border-radius: 16px !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
}

.swal-popup-detail .material-icons {
    font-size: 18px;
    vertical-align: middle;
}

/* Mejorar botones en devoluciones */
.historial-actions button,
.action-btn-primary,
.action-btn-secondary {
    opacity: 1 !important;
    visibility: visible !important;
    display: inline-flex !important;
}

/* Estilo especial para botones de devoluciones */
.devoluciones-actions button {
    opacity: 1 !important;
    visibility: visible !important;
    background: transparent !important;
    border: 1px solid !important;
    border-radius: 8px !important;
    padding: 8px 16px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
}

.btn-buscar-venta {
    border-color: #3b82f6 !important;
    color: #3b82f6 !important;
}

.btn-buscar-venta:hover {
    background: #3b82f6 !important;
    color: white !important;
}

.btn-procesar-devolucion {
    border-color: #059669 !important;
    color: #059669 !important;
}

.btn-procesar-devolucion:hover {
    background: #059669 !important;
    color: white !important;
}

/* Estados de devolución */
.estado-devuelto, .estado-parcialmente-devuelto {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.estado-devuelto {
    background: #fee2e2;
    color: #dc2626;
}

.estado-parcialmente-devuelto {
    background: #fef3c7;
    color: #d97706;
}

/* Venta completamente devuelta - Efecto tachado */
.venta-devuelta-completa {
    opacity: 0.7;
    position: relative;
}

.venta-devuelta-completa::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 2px;
    background: #dc2626;
    transform: translateY(-50%);
    opacity: 0.8;
    z-index: 1;
}

.venta-devuelta-completa td {
    text-decoration: line-through;
    color: #9ca3af !important;
}

.venta-devuelta-completa .historial-price-total {
    text-decoration: line-through;
    color: #9ca3af !important;
}

/* Venta parcialmente devuelta */
.venta-parcialmente-devuelta {
    background: #fffbeb;
    border-left: 4px solid #f59e0b;
}

/* Asegurar que el botón Ver no se tache nunca */
.venta-devuelta-completa .historial-actions-improved button,
.venta-parcialmente-devuelta .historial-actions-improved button {
    text-decoration: none !important;
    opacity: 1 !important;
}

.venta-devuelta-completa td:not(:last-child) {
    text-decoration: line-through;
    opacity: 0.6;
}

.venta-parcialmente-devuelta td {
    opacity: 0.9;
}

/* Excepciones para elementos que no deben tacharse */
.venta-devuelta-completa .historial-badge,
.venta-devuelta-completa .historial-sale-info span,
.venta-devuelta-completa .historial-price-total span {
    text-decoration: none !important;
}

/* Badges estilo pastel (similar a lista de productos) */
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
.historial-badge-success { background: #eaf7ee; color: #0a7c4a; border-color: #bfe7cf; }
.historial-badge-info { background: #eaf1ff; color: #1e56cf; border-color: #c6d7ff; }
.historial-badge-warning { background: #fff4e6; color: #d97706; border-color: #ffd7b0; }
.historial-badge-gray { background: #f1f5f9; color: #6b7280; border-color: #e2e8f0; }

.historial-quantity-container { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.historial-stock-change { font-size: 0.78rem; color: #6b7280; }

.cliente-doc { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:9999px; background:#eaf1ff; color:#1e56cf; border:1px solid #c6d7ff; font-size:0.78rem; font-weight:600; }

/* Scroll horizontal bajo la tabla */
.historial-hscroll-track { height: 10px; background: #e5e7eb; border-radius: 9999px; margin-top: 10px; position: relative; width: 100%; display: none; }
.historial-hscroll-thumb { height: 10px; background: #9ca3af; border-radius: 9999px; width: 60px; position: absolute; left: 0; }
.historial-hscroll { scrollbar-width: none; }
.historial-hscroll::-webkit-scrollbar { display: none; }

/* Tooltip estilo burbuja para badges */
[data-tooltip] { position: relative; }
[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: #fff;
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 0.78rem;
    white-space: nowrap;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    opacity: 0;
    pointer-events: none;
    transition: opacity .15s ease;
}
[data-tooltip]::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: #1f2937;
    opacity: 0;
    transition: opacity .15s ease;
}
[data-tooltip]:hover::after,
[data-tooltip]:hover::before { opacity: 1; }

/* Total badge pastel */
.total-badge { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:0; min-height:unset; border-radius:0; background:transparent; color:#0a7c4a; border:none; font-weight:700; }
.estado-parcialmente-devuelto { background:#fef3c7; color:#d97706; padding:6px 12px; border-radius:9999px; font-size:0.75rem; display:inline-flex; align-items:center; justify-content:center; min-height:28px; }
.historial-badge-yape { background:#f1ecff; color:#6f4bd8; border-color:#d6c9ff; }
</style>
@endpush

@section('content')

<div class="grid grid-cols-12">
    <div class="col-span-12">

        

        <!-- Filtros Mejorados Sin Etiquetas -->
        <div class="historial-table-container-improved">
            <div class="historial-table-header-improved">
                <div class="historial-filters-layout-improved">
                    <!-- Filtros a la izquierda -->
                    <div class="historial-filters-left-improved">
                        <form method="GET" action="{{ route('ventas.historial') }}" id="filtrosForm">
                            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                                <input type="search" 
                                       name="search"
                                       value="{{ request('search') }}"
                                       class="historial-input-clean" 
                                       placeholder="Buscar ventas..." 
                                       id="searchHistorial">
                                
                                <div class="historial-select-with-label">
                                    <span class="historial-inline-label">Método</span>
                                    <div class="historial-select-wrapper-clean">
                                        <select class="historial-select-clean" name="metodo_pago" id="filtroMetodo">
                                            <option value="">Todos</option>
                                            <option value="efectivo" {{ request('metodo_pago') == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                                            <option value="tarjeta" {{ request('metodo_pago') == 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                                            <option value="yape" {{ request('metodo_pago') == 'yape' ? 'selected' : '' }}>Yape</option>
                                        </select>
                                        <div class="historial-select-arrow-clean">
                                            <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                                        </div>
                                    </div>
                                </div>
                                
                                
                                
                                <div class="historial-select-with-label">
                                    <span class="historial-inline-label">Usuario</span>
                                    <div class="historial-select-wrapper-clean">
                                        <select class="historial-select-clean" name="usuario_id" id="filtroUsuario">
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
                    
                    <!-- Botón a la derecha -->
                    <div class="historial-filters-right">
                        <a href="{{ route('punto-venta.index') }}" class="historial-btn-nueva-entrada">
                            <iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon>
                            Nueva Venta
                        </a>
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
                            <th>Comprobante</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventas as $venta)
                            <tr class="historial-row @if($venta->estado === 'devuelta') venta-devuelta-completa @elseif($venta->estado === 'parcialmente_devuelta') venta-parcialmente-devuelta @endif">
                                <td>
                                    <div class="historial-date">
                {{ optional($venta->fecha_venta ?? $venta->created_at)->format('d/m/Y') ?? '-' }}
                                    </div>
                                    <div class="historial-time">
                {{ optional($venta->fecha_venta ?? $venta->created_at)->format('g:i A') ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="historial-sale-info">
                                        <div class="historial-sale-number">
                                            {{ $venta->numero_venta }}
                                            @if($venta->estado === 'devuelta')
                                                <span style="background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-left: 4px;">
                                                    ✓ DEVUELTA
                                                </span>
                                            @elseif($venta->estado === 'parcialmente_devuelta')
                                                <span class="estado-parcialmente-devuelto" data-tooltip="Parcialmente devuelta">◐ PARCIAL</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            <td>
                                @php($unidades = $venta->detalles->sum('cantidad'))
                                @php($prodCount = $venta->detalles->count())
                                <div class="historial-quantity-container">
                                    @php($tooltipText = $prodCount === 1 ? '1 producto' : ($prodCount . ' productos diferentes'))
                                    <span class="historial-badge historial-badge-success" @if($venta->estado !== 'devuelta') data-tooltip="{{ $tooltipText }}" @endif>
                                        {{ $unidades }} {{ $unidades == 1 ? 'unidad' : 'unidades' }}
                                    </span>
                                    @if($venta->tiene_devoluciones && $venta->cantidad_productos_devueltos > 0)
                                        <div class="historial-stock-change" style="color: #dc2626; font-weight: 500;">
                                            <i class="material-icons" style="font-size: 12px;">remove_circle</i>
                                            {{ $venta->cantidad_productos_devueltos }} devueltas
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @php($cliente = $venta->cliente)
                                @if($cliente)
                                    <div class="historial-provider-name">{{ $cliente->nombre_completo }}</div>
                                    @if($cliente->dni)
                                        <div class="cliente-doc">DNI: {{ $cliente->dni }}</div>
                                    @endif
                                @elseif(!empty($venta->cliente_razon_social))
                                    <div class="historial-provider-name">{{ $venta->cliente_razon_social }}</div>
                                @else
                                    <span class="historial-badge historial-badge-gray">Sin datos</span>
                                @endif
                            </td>
                            <td>
                                <div class="historial-payment-method">
                                    @if($venta->metodo_pago == 'efectivo')
                                        <span class="historial-badge historial-badge-success">
                                            <iconify-icon icon="solar:wallet-money-bold-duotone"></iconify-icon>
                                            Efectivo
                                        </span>
                                        @if($venta->vuelto > 0)
                                            <div class="historial-payment-details">
                                                Vuelto: S/ {{ number_format($venta->vuelto, 2) }}
                                            </div>
                                        @endif
                                    @elseif($venta->metodo_pago == 'tarjeta')
                                        <span class="historial-badge historial-badge-info">
                                            <iconify-icon icon="solar:card-bold-duotone"></iconify-icon>
                                            Tarjeta
                                        </span>
                                    @elseif($venta->metodo_pago == 'yape')
                                        <span class="historial-badge historial-badge-yape">
                                            <iconify-icon icon="solar:phone-bold-duotone"></iconify-icon>
                                            Yape
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($venta->tipo_comprobante == 'boleta')
                                    <span class="historial-badge historial-badge-info">
                                        <iconify-icon icon="solar:document-text-bold-duotone"></iconify-icon>
                                        Boleta
                                    </span>
                                @elseif($venta->tipo_comprobante == 'ticket')
                                    <span class="historial-badge historial-badge-info">
                                        <iconify-icon icon="mdi:receipt-outline"></iconify-icon>
                                        Ticket
                                    </span>
                                @else
                                    <span class="historial-badge historial-badge-gray">No</span>
                                @endif
                            </td>
                            <td>
                                <div class="historial-price-total">
                                    @if($venta->estado === 'devuelta')
                                        <div style="position: relative;">
                                            <span style="text-decoration: line-through; color: #9ca3af; font-size: 0.875rem;">S/ {{ number_format($venta->total, 2) }}</span>
                                            <br>
                                            <span style="color: #dc2626; font-weight: 600; font-size: 1.1rem;">S/ 0.00</span>
                                            <span style="background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-left: 4px;">
                                                DEVUELTO
                                            </span>
                                        </div>
                                    @elseif($venta->tiene_devoluciones)
                                        <div style="position: relative;">
                                            <span style="text-decoration: line-through; color: #9ca3af; font-size: 0.875rem;">S/ {{ number_format($venta->total, 2) }}</span>
                                            <br>
                                            <span style="color: #059669; font-weight: 600;">S/ {{ number_format($venta->total_actual, 2) }}</span>
                                            <span style="background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-left: 4px;">
                                                -S/ {{ number_format($venta->monto_total_devuelto, 2) }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="total-badge">S/ {{ number_format($venta->total, 2) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="historial-actions-improved">
                                    <button class="action-btn-primary" onclick="mostrarDetalleVenta({{ $venta->id }})" title="Ver">
                                        <i class="material-icons">visibility</i>
                                    </button>
                                    <button class="action-btn-secondary" onclick="mostrarModalImpresionHistorial({{ $venta->id }})" title="Imprimir">
                                        <i class="material-icons">print</i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                <div class="historial-empty-improved">
                                    <div class="historial-empty-icon">
                                        <iconify-icon icon="solar:cart-bold-duotone"></iconify-icon>
                                    </div>
                                    <h3>No hay ventas registradas</h3>
                                    <p>Aún no se han registrado ventas en el sistema</p>
                                    <div class="historial-empty-actions">
                                        <a href="{{ route('punto-venta.index') }}" class="historial-btn-primary-small">
                                            <iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon>
                                            Realizar Primera Venta
                                        </a>
                                    </div>
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
                
                <!-- Paginación Mejorada -->
                @if($ventas->hasPages())
                <div class="historial-pagination-improved">
                    <!-- Información de paginación -->
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
                    
                    <!-- Controles de paginación -->
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
</script>
@endpush
