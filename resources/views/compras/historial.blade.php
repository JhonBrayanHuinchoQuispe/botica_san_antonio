@extends('layout.layout')
@php
    $title = 'Historial de Entradas';
    $subTitle = 'Registro completo de entradas de mercadería';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/compras/historial.js') . '?v=' . time() . '"></script>
    ';
@endphp

<head>
    <title>Historial de Entradas de Mercadería</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/compras/compras.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

@section('content')

<div class="grid grid-cols-12">
    <div class="col-span-12">

        <!-- Estadísticas Mejoradas con Degradados Bonitos -->
        <div class="historial-stats-grid">
            <div class="historial-stat-card historial-stat-red-improved">
                <div class="historial-stat-content">
                    <div class="historial-stat-label">Entradas Hoy</div>
                    <div class="historial-stat-value">{{ $estadisticas['entradas_hoy'] ?? 2 }}</div>
                    <div class="historial-stat-change historial-stat-change-positive">
                        <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                        +{{ $estadisticas['cambio_respecto_ayer'] ?? 2 }} Respecto a ayer
                    </div>
                </div>
                <div class="historial-stat-icon">
                    <iconify-icon icon="solar:calendar-add-bold-duotone"></iconify-icon>
                </div>
            </div>
            
            <div class="historial-stat-card historial-stat-orange-improved">
                <div class="historial-stat-content">
                    <div class="historial-stat-label">Este Mes</div>
                    <div class="historial-stat-value">{{ $estadisticas['entradas_mes'] ?? 2 }}</div>
                    <div class="historial-stat-change historial-stat-change-positive">
                        <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                        +{{ $estadisticas['cambio_mes'] ?? 15 }} Entradas del mes
                    </div>
                </div>
                <div class="historial-stat-icon">
                    <iconify-icon icon="solar:calendar-mark-bold-duotone"></iconify-icon>
                </div>
            </div>
            
            <div class="historial-stat-card historial-stat-blue-improved">
                <div class="historial-stat-content">
                    <div class="historial-stat-label">Productos</div>
                    <div class="historial-stat-value">{{ $estadisticas['productos_ingresados'] ?? 400 }}</div>
                    <div class="historial-stat-change historial-stat-change-positive">
                        <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                        +{{ $estadisticas['unidades_ingresadas'] ?? 50 }} Unidades ingresadas
                    </div>
                </div>
                <div class="historial-stat-icon">
                    <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                </div>
            </div>
            
            <div class="historial-stat-card historial-stat-green-improved">
                <div class="historial-stat-content">
                    <div class="historial-stat-label">Valor Total</div>
                    <div class="historial-stat-value">S/ {{ number_format($estadisticas['valor_total'] ?? 1100, 2) }}</div>
                    <div class="historial-stat-change historial-stat-change-positive">
                        <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                        +S/{{ number_format($estadisticas['valor_invertido'] ?? 1200, 0) }} Valor invertido
                    </div>
                </div>
                <div class="historial-stat-icon">
                    <iconify-icon icon="solar:wallet-money-bold-duotone"></iconify-icon>
                </div>
            </div>
        </div>

        <!-- Filtros Mejorados Sin Etiquetas -->
        <div class="historial-table-container-improved">
            <div class="historial-table-header-improved">
                <div class="historial-filters-layout-improved">
                    <!-- Filtros a la izquierda -->
                    <div class="historial-filters-left-improved">
                        <input type="text" 
                               class="historial-input-clean" 
                               placeholder="Buscar en el historial..." 
                               id="searchHistorial">
                        
                        <div class="historial-select-with-label">
                            <span class="historial-inline-label">Mostrar</span>
                            <div class="historial-select-wrapper-clean">
                                <select class="historial-select-clean" id="perPageSelect">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <div class="historial-select-arrow-clean">
                                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                                </div>
                            </div>
                        </div>
                        
                        <div class="historial-select-with-label">
                            <span class="historial-inline-label">Proveedor</span>
                            <div class="historial-select-wrapper-clean">
                                <select class="historial-select-clean" id="filtroProveedor">
                                    <option value="">Todos</option>
                                    @foreach($proveedores as $proveedor)
                                        <option value="{{ $proveedor->id }}">
                                            {{ $proveedor->razon_social }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="historial-select-arrow-clean">
                                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botón a la derecha -->
                    <div class="historial-filters-right">
                        <a href="{{ route('compras.nueva') }}" class="historial-btn-nueva-entrada">
                            <iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon>
                            Nueva Entrada
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="historial-table-wrapper-improved">
                <table class="historial-table" id="tablaHistorial">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Producto</th>
                            <th>Proveedor</th>
                            <th>Cantidad</th>
                            <th>Lote y Vencimiento</th>
                            <th>Precios</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Mensaje de no resultados (oculto por defecto) -->
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="7" class="historial-no-results-cell">
                                <div class="historial-no-results-content">
                                    <div class="historial-no-results-icon">
                                        <iconify-icon icon="solar:magnifer-zoom-out-bold-duotone"></iconify-icon>
                                    </div>
                                    <h3>No se encontraron resultados</h3>
                                    <p id="noResultsText">No hay entradas que coincidan con los criterios de búsqueda</p>
                                    <div class="historial-no-results-actions">
                                        <button onclick="limpiarTodosFiltros()" class="historial-btn-secondary-small">
                                            <iconify-icon icon="solar:refresh-bold-duotone"></iconify-icon>
                                            Limpiar filtros
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        @forelse($entradas as $entrada)
                        <tr class="historial-data-row">
                            <td>
                                <div class="historial-date">
                                    {{ $entrada->fecha_entrada->format('d/m/Y') }}
                                </div>
                                <div class="historial-time">
                                    {{ $entrada->fecha_entrada->format('g:i A') }}
                                </div>
                            </td>
                            <td>
                                <div class="historial-product">
                                    <div class="historial-product-info">
                                        <div class="historial-product-name">{{ $entrada->producto->nombre ?? 'Producto no disponible' }}</div>
                                        @if($entrada->producto && $entrada->producto->codigo_barras)
                                            <div class="historial-product-code">{{ $entrada->producto->codigo_barras }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($entrada->proveedor)
                                    <div class="historial-provider-name">{{ $entrada->proveedor->razon_social }}</div>
                                    @if($entrada->proveedor->nombre_comercial)
                                        <div class="historial-provider-commercial">{{ $entrada->proveedor->nombre_comercial }}</div>
                                    @endif
                                @else
                                    <span class="historial-badge historial-badge-gray">Sin proveedor</span>
                                @endif
                            </td>
                            <td>
                                <div class="historial-quantity-container">
                                    <span class="historial-badge historial-badge-success">{{ $entrada->cantidad }}</span>
                                    <div class="historial-stock-change">
                                        {{ $entrada->stock_anterior }} → {{ $entrada->stock_nuevo }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="historial-lot">{{ $entrada->lote }}</div>
                                @if($entrada->fecha_vencimiento)
                                    <div class="historial-expiry">
                                        Vence: {{ $entrada->fecha_vencimiento->format('d/m/Y') }}
                                    </div>
                                @else
                                    <div class="historial-no-expiry">Sin vencimiento</div>
                                @endif
                            </td>
                            <td>
                                @if($entrada->precio_compra_nuevo)
                                    <div class="historial-price">S/ {{ number_format($entrada->precio_compra_nuevo, 2) }}</div>
                                    @if($entrada->hubo_cambio_precio_compra)
                                        <div class="historial-price-change">
                                            Cambió de S/ {{ number_format($entrada->precio_compra_anterior, 2) }}
                                        </div>
                                    @endif
                                @else
                                    <span class="historial-badge historial-badge-gray">Sin cambio</span>
                                @endif
                            </td>
                            <td>
                                <div class="historial-user">
                                    <div class="historial-user-name">{{ $entrada->usuario->name ?? 'Usuario no disponible' }}</div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="historial-empty-improved">
                                    <div class="historial-empty-icon">
                                        <iconify-icon icon="solar:inbox-bold-duotone"></iconify-icon>
                                    </div>
                                    <h3>No hay entradas registradas</h3>
                                    <p>Aún no se han registrado entradas de mercadería</p>
                                    <div class="historial-empty-actions">
                                        <a href="{{ route('compras.nueva') }}" class="historial-btn-primary-small">
                                            <iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon>
                                            Registrar Primera Entrada
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <!-- Paginación Mejorada -->
                @if($entradas->hasPages())
                <div class="historial-pagination-improved">
                    <!-- Información de paginación -->
                    <div class="historial-pagination-info">
                        <p class="text-sm text-gray-700">
                            Mostrando 
                            <span class="font-medium">{{ $entradas->firstItem() }}</span>
                            a 
                            <span class="font-medium">{{ $entradas->lastItem() }}</span>
                            de 
                            <span class="font-medium">{{ $entradas->total() }}</span>
                            entradas
                        </p>
                    </div>
                    
                    <!-- Controles de paginación -->
                    <div class="historial-pagination-controls">
                        {{-- Botón Primera página --}}
                        @if ($entradas->onFirstPage())
                            <span class="historial-pagination-btn historial-pagination-btn-disabled">
                                Primera
                            </span>
                        @else
                            <a href="{{ $entradas->url(1) }}" class="historial-pagination-btn">
                                Primera
                            </a>
                        @endif
                        
                        {{-- Botón Anterior --}}
                        @if ($entradas->onFirstPage())
                            <span class="historial-pagination-btn historial-pagination-btn-disabled">
                                ‹ Anterior
                            </span>
                        @else
                            <a href="{{ $entradas->previousPageUrl() }}" class="historial-pagination-btn">
                                ‹ Anterior
                            </a>
                        @endif
                        
                        {{-- Números de página --}}
                        @foreach ($entradas->getUrlRange(max(1, $entradas->currentPage() - 2), min($entradas->lastPage(), $entradas->currentPage() + 2)) as $page => $url)
                            @if ($page == $entradas->currentPage())
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
                        @if ($entradas->hasMorePages())
                            <a href="{{ $entradas->nextPageUrl() }}" class="historial-pagination-btn">
                                Siguiente ›
                            </a>
                        @else
                            <span class="historial-pagination-btn historial-pagination-btn-disabled">
                                Siguiente ›
                            </span>
                        @endif
                        
                        {{-- Botón Última página --}}
                        @if ($entradas->hasMorePages())
                            <a href="{{ $entradas->url($entradas->lastPage()) }}" class="historial-pagination-btn">
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

<style>
/* Header Compacto */
.historial-header {
    background: linear-gradient(135deg, #e53e3e 0%, #f56565 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.historial-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
}

/* Estadísticas con Degradados Bonitos y Opacidad */
.historial-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.historial-stat-card {
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.historial-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.1);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.historial-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.historial-stat-card:hover::before {
    opacity: 1;
}

.historial-stat-content {
    flex: 1;
    z-index: 1;
}

/* Mejorar la legibilidad de todos los textos */
.historial-stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    opacity: 0.95;
}

.historial-stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    line-height: 1.2;
}

.historial-stat-change {
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    opacity: 0.9;
}

.historial-stat-change-positive {
    color: #059669;
}

.historial-stat-icon {
    font-size: 2.5rem;
    opacity: 0.7;
    z-index: 1;
}

/* Degradados vibrantes como en la foto 2 */
.historial-stat-red-improved {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 50%, #e74c3c 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
}

.historial-stat-orange-improved {
    background: linear-gradient(135deg, #ffa726 0%, #ff9800 50%, #f57c00 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
}

.historial-stat-blue-improved {
    background: linear-gradient(135deg, #42a5f5 0%, #2196f3 50%, #1976d2 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
}

.historial-stat-green-improved {
    background: linear-gradient(135deg, #66bb6a 0%, #4caf50 50%, #388e3c 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

/* Texto blanco para todas las tarjetas */
.historial-stat-red-improved .historial-stat-label,
.historial-stat-red-improved .historial-stat-value,
.historial-stat-red-improved .historial-stat-change,
.historial-stat-orange-improved .historial-stat-label,
.historial-stat-orange-improved .historial-stat-value,
.historial-stat-orange-improved .historial-stat-change,
.historial-stat-blue-improved .historial-stat-label,
.historial-stat-blue-improved .historial-stat-value,
.historial-stat-blue-improved .historial-stat-change,
.historial-stat-green-improved .historial-stat-label,
.historial-stat-green-improved .historial-stat-value,
.historial-stat-green-improved .historial-stat-change {
    color: white !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* Iconos blancos con opacidad */
.historial-stat-red-improved .historial-stat-icon,
.historial-stat-orange-improved .historial-stat-icon,
.historial-stat-blue-improved .historial-stat-icon,
.historial-stat-green-improved .historial-stat-icon {
    color: rgba(255, 255, 255, 0.9);
}

/* Input de búsqueda con el mismo estilo que los selects */
.historial-input-clean {
    padding: 0.75rem 1rem !important;
    border: 2px solid #e5e7eb !important;
    border-radius: 12px !important;
    background: #f9fafb !important;
    font-size: 0.875rem !important;
    transition: all 0.2s ease !important;
    min-width: 250px !important;
    color: #374151 !important;
    box-sizing: border-box !important;
}

.historial-input-clean:focus {
    outline: none !important;
    border-color: #e53e3e !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1) !important;
}

.historial-input-clean::placeholder {
    color: #9ca3af !important;
}

.historial-input-clean:hover {
    border-color: #9ca3af !important;
    background: white !important;
}

/* Contenedor de tabla mejorado */
.historial-table-container-improved {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.historial-table-header-improved {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

/* Filtros sin etiquetas */
.historial-filters-layout-improved {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.historial-filters-left-improved {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex: 1;
}

.historial-select-with-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
}

.historial-inline-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.historial-select-wrapper-clean {
    position: relative;
}

.historial-select-clean {
    padding: 0.75rem 2.5rem 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: #f9fafb;
    font-size: 0.875rem;
    appearance: none;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 80px;
}

.historial-select-clean:focus {
    outline: none;
    border-color: #e53e3e;
    background: white;
    box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
}

.historial-select-arrow-clean {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #6b7280;
    font-size: 1rem;
}

/* Tabla con bordes redondeados */
.historial-table-wrapper-improved {
    overflow: hidden;
    border-radius: 0 0 16px 16px;
}

.historial-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
}

.historial-table thead th {
    background: linear-gradient(135deg, #e53e3e 0%, #dc2626 100%);
    color: white;
    padding: 1rem 1.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    text-align: left;
    border: none;
}

.historial-table thead th:first-child {
    border-top-left-radius: 0;
}

.historial-table thead th:last-child {
    border-top-right-radius: 0;
}

.historial-table tbody td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.historial-table tbody tr:hover {
    background: #f8fafc;
}

.historial-table tbody tr:last-child td {
    border-bottom: none;
}

/* Estado Vacío Mejorado */
.historial-empty-improved {
    text-align: center;
    padding: 3rem 2rem;
    background: #f9fafb;
    border-radius: 12px;
    margin: 1rem;
}

.historial-empty-improved .historial-empty-icon {
    font-size: 4rem;
    color: #e53e3e;
    margin-bottom: 1rem;
}

.historial-empty-improved h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 0.5rem 0;
}

.historial-empty-improved p {
    color: #6b7280;
    margin: 0 0 1.5rem 0;
    font-size: 1rem;
}

.historial-empty-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.historial-btn-secondary-small {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.historial-btn-secondary-small:hover {
    background: #e5e7eb;
    border-color: #9ca3af;
}

.historial-btn-primary-small {
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #e53e3e 0%, #f56565 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    text-decoration: none;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.historial-btn-primary-small:hover {
    background: linear-gradient(135deg, #c53030 0%, #e53e3e 100%);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Paginación Mejorada */
.historial-pagination-improved {
    padding: 1.5rem 2rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: between;
    align-items: center;
    gap: 1rem;
    background: white;
}

.historial-pagination-info {
    flex: 1;
}

.historial-pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.historial-pagination-btn {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.5rem;
}

.historial-pagination-btn:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    color: #374151;
}

.historial-pagination-btn-current {
    background: #e53e3e !important;
    border-color: #e53e3e !important;
    color: white !important;
}

.historial-pagination-btn-current:hover {
    background: #c53030 !important;
    border-color: #c53030 !important;
    color: white !important;
}

.historial-pagination-btn-disabled {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #9ca3af;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    cursor: not-allowed;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.5rem;
}

/* Elementos de la tabla */
.historial-date {
    font-weight: 600;
    color: #374151;
}

.historial-time {
    font-size: 0.75rem;
    color: #6b7280;
}

.historial-product {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.historial-product-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, #e53e3e 0%, #f56565 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
}

.historial-product-name {
    font-weight: 600;
    color: #374151;
}

.historial-product-code {
    font-size: 0.75rem;
    color: #6b7280;
}

.historial-provider-name {
    font-weight: 600;
    color: #374151;
}

.historial-provider-commercial {
    font-size: 0.75rem;
    color: #6b7280;
}

.historial-quantity-container {
    text-align: center;
}

.historial-stock-change {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.historial-lot {
    font-weight: 600;
    color: #374151;
}

.historial-expiry {
    font-size: 0.75rem;
    color: #6b7280;
}

.historial-no-expiry {
    font-size: 0.75rem;
    color: #9ca3af;
}

.historial-price {
    font-weight: 600;
    color: #059669;
}

.historial-price-change {
    font-size: 0.75rem;
    color: #f59e0b;
}

.historial-user {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.historial-user-icon {
    width: 2rem;
    height: 2rem;
    background: linear-gradient(135deg, #10b981, #34d399);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
}

.historial-user-name {
    font-weight: 600;
    color: #374151;
}

/* Badges */
.historial-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.historial-badge-success {
    background: #d1fae5;
    color: #059669;
}

.historial-badge-gray {
    background: #f3f4f6;
    color: #6b7280;
}

/* Botón de acción */
.historial-action-btn {
    width: 2rem;
    height: 2rem;
    background: #f0f9ff;
    color: #0284c7;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.historial-action-btn:hover {
    background: #e0f2fe;
    transform: scale(1.1);
}

/* Responsive */
@media (max-width: 768px) {
    .historial-filters-layout-improved {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .historial-filters-left-improved {
        flex-direction: column;
        gap: 1rem;
    }
    
    .historial-select-with-label {
        justify-content: space-between;
    }
    
    .historial-input-clean {
        min-width: auto;
    }
    
    .historial-pagination-improved {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .historial-pagination-controls {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .historial-empty-actions,
    .historial-no-results-actions {
        flex-direction: column;
        align-items: center;
    }
}

/* Mensaje de No Resultados dentro de la tabla */
.historial-no-results-cell {
    padding: 3rem 2rem !important;
    text-align: center;
    background: #f9fafb;
    border: none !important;
}

.historial-no-results-content {
    max-width: 400px;
    margin: 0 auto;
}

.historial-no-results-icon {
    font-size: 3rem;
    color: #e53e3e;
    margin-bottom: 1rem;
}

.historial-no-results-content h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 0.5rem 0;
}

.historial-no-results-content p {
    color: #6b7280;
    margin: 0 0 1.5rem 0;
    font-size: 0.95rem;
}

.historial-no-results-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Botón Nueva Entrada mejorado */
.historial-filters-right {
    flex-shrink: 0;
}

.historial-btn-nueva-entrada {
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #e53e3e 0%, #dc2626 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    text-decoration: none;
    box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
}

.historial-btn-nueva-entrada:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px -1px rgba(0, 0, 0, 0.15);
}
</style>

<script>
function limpiarFiltros() {
    document.getElementById('filtroProveedor').value = '';
    document.getElementById('searchHistorial').value = '';
    document.getElementById('perPageSelect').value = '10';
}

function limpiarTodosFiltros() {
    document.getElementById('searchHistorial').value = '';
    document.getElementById('filtroProveedor').value = '';
    document.getElementById('perPageSelect').value = '10';
    
    // Mostrar todas las filas de datos y ocultar mensaje de no resultados
    const dataRows = document.querySelectorAll('#tablaHistorial tbody tr.historial-data-row');
    const noResultsRow = document.getElementById('noResultsRow');
    
    dataRows.forEach(row => {
        row.style.display = '';
    });
    
    noResultsRow.style.display = 'none';
}

function aplicarFiltros() {
    const searchTerm = document.getElementById('searchHistorial').value.toLowerCase();
    const selectedProveedor = document.getElementById('filtroProveedor').value;
    const dataRows = document.querySelectorAll('#tablaHistorial tbody tr.historial-data-row');
    const noResultsRow = document.getElementById('noResultsRow');
    const noResultsText = document.getElementById('noResultsText');
    
    let visibleRows = 0;
    let filtroAplicado = false;
    
    dataRows.forEach(row => {
        let mostrarFila = true;
        
        // Filtro de búsqueda
        if (searchTerm) {
            filtroAplicado = true;
            const text = row.textContent.toLowerCase();
            if (!text.includes(searchTerm)) {
                mostrarFila = false;
            }
        }
        
        // Filtro de proveedor
        if (selectedProveedor && mostrarFila) {
            filtroAplicado = true;
            const proveedorCell = row.querySelector('td:nth-child(3)');
            if (proveedorCell) {
                const proveedorText = proveedorCell.textContent.toLowerCase();
                const selectedProveedorText = document.querySelector(`#filtroProveedor option[value="${selectedProveedor}"]`);
                if (selectedProveedorText) {
                    const selectedProveedorName = selectedProveedorText.textContent.toLowerCase();
                    if (!proveedorText.includes(selectedProveedorName)) {
                        mostrarFila = false;
                    }
                }
            }
        }
        
        if (mostrarFila) {
            row.style.display = '';
            visibleRows++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Mostrar mensaje de no resultados si es necesario
    if (visibleRows === 0 && filtroAplicado) {
        let mensaje = 'No hay entradas que coincidan con los criterios de búsqueda';
        
        if (searchTerm && selectedProveedor) {
            const selectedProveedorText = document.querySelector(`#filtroProveedor option[value="${selectedProveedor}"]`);
            const proveedorName = selectedProveedorText ? selectedProveedorText.textContent : 'el proveedor seleccionado';
            mensaje = `No se encontraron resultados para "${searchTerm}" en ${proveedorName}`;
        } else if (searchTerm) {
            mensaje = `No se encontraron resultados para "${searchTerm}"`;
        } else if (selectedProveedor) {
            const selectedProveedorText = document.querySelector(`#filtroProveedor option[value="${selectedProveedor}"]`);
            const proveedorName = selectedProveedorText ? selectedProveedorText.textContent : 'el proveedor seleccionado';
            mensaje = `No hay entradas para ${proveedorName}`;
        }
        
        noResultsText.textContent = mensaje;
        noResultsRow.style.display = 'table-row';
    } else {
        noResultsRow.style.display = 'none';
    }
}

function limpiarBusqueda() {
    limpiarTodosFiltros();
}

// Event Listeners para filtros en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchHistorial');
    const proveedorSelect = document.getElementById('filtroProveedor');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            aplicarFiltros();
        });
        
        searchInput.addEventListener('keyup', function() {
            aplicarFiltros();
        });
    }
    
    if (proveedorSelect) {
        proveedorSelect.addEventListener('change', function() {
            aplicarFiltros();
        });
    }
});

function verDetalleEntrada(id) {
    console.log('Ver detalle de entrada:', id);
    // Aquí puedes agregar la lógica para mostrar los detalles
}
</script>