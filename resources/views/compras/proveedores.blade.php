@extends('layout.layout')
@php
    $title = 'Gestión de Proveedores';
    $subTitle = 'Administración completa de proveedores';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/compras/proveedores.js') . '?v=' . time() . '"></script>
    ';
@endphp

<head>
    <title>Gestión de Proveedores</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/compras/compras.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/roles.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

@section('content')

<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12">
        <!-- Estadísticas Mejoradas con Degradados Bonitos -->
        <div class="proveedores-stats-grid">
            <div class="proveedores-stat-card proveedores-stat-red-improved">
                <div class="proveedores-stat-content">
                    <div class="proveedores-stat-label">Total Proveedores</div>
                    <div class="proveedores-stat-value">{{ $proveedores->count() }}</div>
                    <div class="proveedores-stat-change proveedores-stat-change-positive">
                        <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                        +{{ $proveedores->where('created_at', '>=', now()->subMonth())->count() }} Este mes
                    </div>
                </div>
                <div class="proveedores-stat-icon">
                    <iconify-icon icon="solar:truck-bold-duotone"></iconify-icon>
                </div>
            </div>
            
            <div class="proveedores-stat-card proveedores-stat-orange-improved">
                <div class="proveedores-stat-content">
                    <div class="proveedores-stat-label">Proveedores Activos</div>
                    <div class="proveedores-stat-value">{{ $proveedores->where('estado', 'activo')->count() }}</div>
                    <div class="proveedores-stat-change proveedores-stat-change-positive">
                        <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                        +{{ round(($proveedores->where('estado', 'activo')->count() / max($proveedores->count(), 1)) * 100) }}% Activos
                    </div>
                </div>
                <div class="proveedores-stat-icon">
                    <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                </div>
            </div>
            
            <div class="proveedores-stat-card proveedores-stat-blue-improved">
                <div class="proveedores-stat-content">
                    <div class="proveedores-stat-label">Con RUC</div>
                    <div class="proveedores-stat-value">{{ $proveedores->whereNotNull('ruc')->count() }}</div>
                    <div class="proveedores-stat-change proveedores-stat-change-positive">
                        <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                        +{{ round(($proveedores->whereNotNull('ruc')->count() / max($proveedores->count(), 1)) * 100) }}% Con RUC
                    </div>
                </div>
                <div class="proveedores-stat-icon">
                    <iconify-icon icon="solar:document-text-bold-duotone"></iconify-icon>
                </div>
            </div>
            
            <div class="proveedores-stat-card proveedores-stat-green-improved">
                <div class="proveedores-stat-content">
                    <div class="proveedores-stat-label">Con Contacto</div>
                    <div class="proveedores-stat-value">{{ $proveedores->where(function($q) { $q->whereNotNull('telefono')->orWhereNotNull('email'); })->count() }}</div>
                    <div class="proveedores-stat-change proveedores-stat-change-positive">
                        <iconify-icon icon="solar:arrow-up-bold"></iconify-icon>
                        +{{ round(($proveedores->where(function($q) { $q->whereNotNull('telefono')->orWhereNotNull('email'); })->count() / max($proveedores->count(), 1)) * 100) }}% Contactables
                    </div>
                </div>
                <div class="proveedores-stat-icon">
                    <iconify-icon icon="solar:phone-bold-duotone"></iconify-icon>
                </div>
            </div>
        </div>
        
        <!-- Filtros Mejorados Sin Etiquetas -->
        <div class="proveedores-table-container-improved">
            <div class="proveedores-table-header-improved">
                <div class="proveedores-filters-layout-improved">
                    <!-- Filtros a la izquierda -->
                    <div class="proveedores-filters-left-improved">
                        <input type="text" 
                               class="proveedores-input-clean" 
                               placeholder="Buscar proveedores..." 
                               id="buscarProveedor">
                        
                        <div class="proveedores-select-with-label">
                            <span class="proveedores-inline-label">Mostrar</span>
                            <div class="proveedores-select-wrapper-clean">
                                <select class="proveedores-select-clean" id="perPageSelect">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <div class="proveedores-select-arrow-clean">
                                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                                </div>
                            </div>
                        </div>
                        
                        <div class="proveedores-select-with-label">
                            <span class="proveedores-inline-label">Estado</span>
                            <div class="proveedores-select-wrapper-clean">
                                <select class="proveedores-select-clean" id="filtroEstado">
                                    <option value="">Todos</option>
                                    <option value="activo">Activos</option>
                                    <option value="inactivo">Inactivos</option>
                                </select>
                                <div class="proveedores-select-arrow-clean">
                                    <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botón a la derecha -->
                    <div class="proveedores-filters-right">
                        <button type="button" class="proveedores-btn-nueva-entrada-visible" onclick="window.abrirModalAgregar && window.abrirModalAgregar()">
                            <iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon>
                            Nuevo Proveedor
                        </button>
                    </div>
                </div>
            </div>
        
            <div class="proveedores-table-wrapper-improved">
                <!-- Mensaje de no resultados (oculto por defecto) -->
                <table class="proveedores-table" id="tablaProveedores">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>RUC</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Mensaje de no resultados (oculto por defecto) -->
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="7" class="proveedores-no-results-cell">
                                <div class="proveedores-no-results-content">
                                    <div class="proveedores-no-results-icon">
                                        <iconify-icon icon="solar:magnifer-zoom-out-bold-duotone"></iconify-icon>
                                    </div>
                                    <h3>No se encontraron resultados</h3>
                                    <p id="noResultsText">No hay proveedores que coincidan con los criterios de búsqueda</p>
                                    <div class="proveedores-no-results-actions">
                                        <button onclick="limpiarTodosFiltros()" class="proveedores-btn-secondary-small">
                                            <iconify-icon icon="solar:refresh-bold-duotone"></iconify-icon>
                                            Limpiar filtros
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        @forelse($proveedores as $index => $proveedor)
                        <tr class="proveedores-data-row {{ $proveedor->estado !== 'activo' ? 'opacity-75' : '' }}" data-proveedor-id="{{ $proveedor->id }}">
                            <td>
                                <div class="proveedores-id">
                                    {{ $index + 1 }}
                                </div>
                            </td>
                            <td>
                                <div class="proveedores-company">
                                    <div class="proveedores-company-icon">
                                        <iconify-icon icon="solar:buildings-bold-duotone"></iconify-icon>
                                    </div>
                                    <div class="proveedores-company-info">
                                        <div class="proveedores-company-name">{{ $proveedor->razon_social }}</div>
                                        @if($proveedor->nombre_comercial)
                                            <div class="proveedores-company-commercial">{{ $proveedor->nombre_comercial }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($proveedor->ruc)
                                    <span class="proveedores-badge proveedores-badge-info">{{ $proveedor->ruc }}</span>
                                @else
                                    <span class="proveedores-badge proveedores-badge-gray">Sin RUC</span>
                                @endif
                            </td>
                            <td>
                                <div class="proveedores-contact">
                                    @if($proveedor->telefono)
                                        <div class="proveedores-contact-item">
                                            <iconify-icon icon="solar:phone-bold-duotone"></iconify-icon>
                                            {{ $proveedor->telefono }}
                                        </div>
                                    @endif
                                    @if($proveedor->email)
                                        <div class="proveedores-contact-item">
                                            <iconify-icon icon="solar:letter-bold-duotone"></iconify-icon>
                                            {{ $proveedor->email }}
                                        </div>
                                    @endif
                                    @if(!$proveedor->telefono && !$proveedor->email)
                                        <span class="proveedores-no-contact">Sin contacto</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($proveedor->estado === 'activo')
                                    <span class="proveedores-badge proveedores-badge-success estado-badge">
                                        <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                        Activo
                                    </span>
                                @else
                                    <span class="proveedores-badge proveedores-badge-secondary estado-badge">
                                        <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td>
                    <div class="proveedores-date">
                        {{ optional($proveedor->created_at)->format('d/m/Y') ?? '-' }}
                    </div>
                            </td>
                            <td>
                                <div class="proveedores-action-buttons">
                                    <button class="proveedores-action-btn proveedores-action-btn-view" 
                                            onclick="verProveedor({{ $proveedor->id }})" 
                                            title="Ver detalles">
                                        <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                                    </button>
                                    <button class="proveedores-action-btn proveedores-action-btn-edit" 
                                            onclick="editarProveedor({{ $proveedor->id }})" 
                                            title="Editar">
                                        <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                                    </button>
                                    <!-- Toggle estado activo/inactivo -->
                                    <label class="toggle-switch proveedor-toggle" title="Activar/Desactivar">
                                        <input type="checkbox" class="proveedor-status-toggle" data-proveedor-id="{{ $proveedor->id }}" {{ $proveedor->estado === 'activo' ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>

                                    <!-- Eliminar -->
                                    <button class="proveedores-action-btn proveedores-action-btn-delete" 
                                            onclick="eliminarProveedor({{ $proveedor->id }})" 
                                            title="Eliminar">
                                        <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="proveedores-empty-improved">
                                    <div class="proveedores-empty-icon">
                                        <iconify-icon icon="solar:truck-bold-duotone"></iconify-icon>
                                    </div>
                                    <h3>No hay proveedores registrados</h3>
                                    <p>Comienza agregando tu primer proveedor</p>
                                    <div class="proveedores-empty-actions">
                                        <button type="button" class="proveedores-btn-primary-small" onclick="window.abrirModalAgregar && window.abrirModalAgregar()">
                                            <iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon>
                                            Agregar Primer Proveedor
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <!-- Skeleton loading for Proveedores -->
                <div id="proveedoresSkeleton" class="skeleton-table" style="display:none;">
                    <div class="skeleton-row">
                        <span class="skeleton-bar short"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar short"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar actions"></span>
                    </div>
                    <div class="skeleton-row">
                        <span class="skeleton-bar short"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar short"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar actions"></span>
                    </div>
                    <div class="skeleton-row">
                        <span class="skeleton-bar short"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar short"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar actions"></span>
                    </div>
                    <div class="skeleton-row">
                        <span class="skeleton-bar short"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar short"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar actions"></span>
                    </div>
                    <div class="skeleton-row">
                        <span class="skeleton-bar short"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar short"></span>
                        <span class="skeleton-bar medium"></span>
                        <span class="skeleton-bar actions"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

<style>
/* TODOS LOS ESTILOS CSS EXISTENTES SE MANTIENEN IGUAL */
/* Solo se ha eliminado el JavaScript duplicado */

/* Header Elegante */
.proveedores-header {
    background: linear-gradient(135deg, #e53e3e 0%, #dc2626 100%);
    color: white;
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.proveedores-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.proveedores-header h1 iconify-icon {
    font-size: 2.25rem;
}

/* Estadísticas con Degradados Bonitos */
.proveedores-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.proveedores-stat-card {
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

.proveedores-stat-card::before {
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

.proveedores-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.proveedores-stat-card:hover::before {
    opacity: 1;
}

.proveedores-stat-content {
    flex: 1;
    z-index: 1;
}

.proveedores-stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    opacity: 0.95;
}

.proveedores-stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    line-height: 1.2;
}

.proveedores-stat-change {
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    opacity: 0.9;
}

.proveedores-stat-icon {
    font-size: 2.5rem;
    opacity: 0.7;
    z-index: 1;
}

/* Degradados específicos con colores vibrantes */
.proveedores-stat-red-improved {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 50%, #e74c3c 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
}

.proveedores-stat-orange-improved {
    background: linear-gradient(135deg, #ffa726 0%, #ff9800 50%, #f57c00 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
}

.proveedores-stat-blue-improved {
    background: linear-gradient(135deg, #42a5f5 0%, #2196f3 50%, #1976d2 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
}

.proveedores-stat-green-improved {
    background: linear-gradient(135deg, #66bb6a 0%, #4caf50 50%, #388e3c 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

/* Texto blanco para todas las tarjetas */
.proveedores-stat-red-improved .proveedores-stat-label,
.proveedores-stat-red-improved .proveedores-stat-value,
.proveedores-stat-red-improved .proveedores-stat-change,
.proveedores-stat-orange-improved .proveedores-stat-label,
.proveedores-stat-orange-improved .proveedores-stat-value,
.proveedores-stat-orange-improved .proveedores-stat-change,
.proveedores-stat-blue-improved .proveedores-stat-label,
.proveedores-stat-blue-improved .proveedores-stat-value,
.proveedores-stat-blue-improved .proveedores-stat-change,
.proveedores-stat-green-improved .proveedores-stat-label,
.proveedores-stat-green-improved .proveedores-stat-value,
.proveedores-stat-green-improved .proveedores-stat-change {
    color: white !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* Iconos blancos con opacidad */
.proveedores-stat-red-improved .proveedores-stat-icon,
.proveedores-stat-orange-improved .proveedores-stat-icon,
.proveedores-stat-blue-improved .proveedores-stat-icon,
.proveedores-stat-green-improved .proveedores-stat-icon {
    color: rgba(255, 255, 255, 0.9);
}

/* Contenedor de tabla mejorado */
.proveedores-table-container-improved {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.proveedores-table-header-improved {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

/* Filtros sin etiquetas */
.proveedores-filters-layout-improved {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.proveedores-filters-left-improved {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex: 1;
}

.proveedores-input-clean {
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

.proveedores-input-clean:focus {
    outline: none !important;
    border-color: #e53e3e !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1) !important;
}

.proveedores-input-clean::placeholder {
    color: #9ca3af !important;
}

.proveedores-input-clean:hover {
    border-color: #9ca3af !important;
    background: white !important;
}

.proveedores-select-with-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
}

.proveedores-inline-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.proveedores-select-wrapper-clean {
    position: relative;
}

.proveedores-select-clean {
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

.proveedores-select-clean:focus {
    outline: none;
    border-color: #e53e3e;
    background: white;
    box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
}

.proveedores-select-arrow-clean {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #6b7280;
    font-size: 1rem;
}

/* Botón Nueva Entrada SUPER FORZADO para que siempre se vea */
.proveedores-filters-right {
    flex-shrink: 0;
}

.proveedores-btn-nueva-entrada-visible {
    padding: 0.75rem 1.25rem !important;
    background: #e53e3e !important;
    background-image: linear-gradient(135deg, #e53e3e 0%, #dc2626 100%) !important;
    color: white !important;
    border: none !important;
    border-radius: 12px !important;
    font-size: 0.875rem !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    transition: all 0.3s ease !important;
    text-decoration: none !important;
    box-shadow: 0 4px 8px -1px rgba(229, 62, 62, 0.4) !important;
    opacity: 1 !important;
    visibility: visible !important;
    position: relative !important;
    z-index: 10 !important;
}

.proveedores-btn-nueva-entrada-visible:hover {
    background: #dc2626 !important;
    background-image: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
    color: white !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 12px -1px rgba(229, 62, 62, 0.5) !important;
}

.proveedores-btn-nueva-entrada-visible:focus {
    background: #e53e3e !important;
    background-image: linear-gradient(135deg, #e53e3e 0%, #dc2626 100%) !important;
    color: white !important;
    outline: none !important;
}

.proveedores-btn-nueva-entrada-visible:active {
    background: #e53e3e !important;
    background-image: linear-gradient(135deg, #e53e3e 0%, #dc2626 100%) !important;
    color: white !important;
}

.proveedores-btn-nueva-entrada-visible:visited {
    background: #e53e3e !important;
    background-image: linear-gradient(135deg, #e53e3e 0%, #dc2626 100%) !important;
    color: white !important;
}

/* Forzar iconos del botón también */
.proveedores-btn-nueva-entrada-visible iconify-icon {
    color: white !important;
    opacity: 1 !important;
}

/* Botones de acción */
.proveedores-action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

/* Toggle switch para estado de proveedor (coherente con tablas) */
.proveedor-toggle.toggle-switch { position: relative; display: inline-block; width: 50px; height: 28px; vertical-align: middle; }
.proveedor-toggle.toggle-switch input { opacity: 0; width: 0; height: 0; }
.proveedor-toggle .toggle-slider { position: absolute; cursor: pointer; inset: 0; background-color: #e5e7eb; transition: 0.2s ease; border-radius: 9999px; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05); }
.proveedor-toggle .toggle-slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 3px; top: 3px; background: white; border-radius: 9999px; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: 0.2s ease; }
.proveedor-toggle input:checked + .toggle-slider { background-color: #10b981; }
.proveedor-toggle input:checked + .toggle-slider:before { transform: translateX(22px); }

.proveedores-action-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 36px !important;
    height: 36px !important;
    border-radius: 8px !important;
    border: none !important;
    cursor: pointer !important;
    font-size: 16px !important;
    transition: all 0.2s ease !important;
    color: white !important;
}

.proveedores-action-btn-view {
    background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
}

.proveedores-action-btn-view:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
    transform: translateY(-1px) !important;
}

.proveedores-action-btn-edit {
    background: linear-gradient(135deg, #f97316, #ea580c) !important;
}

.proveedores-action-btn-edit:hover {
    background: linear-gradient(135deg, #ea580c, #dc2626) !important;
    transform: translateY(-1px) !important;
}

.proveedores-action-btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
}

.proveedores-action-btn-delete:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
    transform: translateY(-1px) !important;
}

.proveedores-action-btn-success {
    background: linear-gradient(135deg, #10b981, #059669) !important;
}

.proveedores-action-btn-success:hover {
    background: linear-gradient(135deg, #059669, #047857) !important;
    transform: translateY(-1px) !important;
}

/* Tabla con bordes redondeados */
.proveedores-table-wrapper-improved {
    overflow: hidden;
    border-radius: 0 0 16px 16px;
}

.proveedores-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
}

.proveedores-table thead th {
    background: linear-gradient(135deg, #e53e3e 0%, #dc2626 100%);
    color: white;
    padding: 1rem 1.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    text-align: left;
    border: none;
}

.proveedores-table thead th:first-child {
    border-top-left-radius: 0;
}

.proveedores-table thead th:last-child {
    border-top-right-radius: 0;
}

.proveedores-table tbody td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}

.proveedores-table tbody tr:hover {
    background: #f8fafc;
}

.proveedores-table tbody tr:last-child td {
    border-bottom: none;
}

/* Elementos específicos de proveedores */
.proveedores-id {
    font-weight: 600;
    color: #4f46e5;
    font-size: 0.875rem;
}

.proveedores-company {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.proveedores-company-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.proveedores-company-name {
    font-weight: 600;
    color: #374151;
}

.proveedores-company-commercial {
    font-size: 0.75rem;
    color: #4f46e5;
}

.proveedores-contact {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.proveedores-contact-item {
    font-size: 0.875rem;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.proveedores-contact-item iconify-icon {
    color: #10b981;
}

.proveedores-no-contact {
    color: #9ca3af;
    font-size: 0.875rem;
}

.proveedores-date {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Badges */
.proveedores-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.proveedores-badge-info {
    background: #dbeafe;
    color: #1e40af;
}

.proveedores-badge-gray {
    background: #f3f4f6;
    color: #6b7280;
}

.proveedores-badge-success {
    background: #dcfce7;
    color: #166534;
}

.proveedores-badge-secondary {
    background: #f1f5f9;
    color: #64748b;
}

/* Mensaje de no resultados */
.proveedores-no-results-cell {
    padding: 3rem 2rem !important;
    text-align: center;
    background: #f9fafb;
    border: none !important;
}

.proveedores-no-results-content {
    max-width: 400px;
    margin: 0 auto;
}

.proveedores-no-results-icon {
    font-size: 3rem;
    color: #e53e3e;
    margin-bottom: 1rem;
}

.proveedores-no-results-content h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 0.5rem 0;
}

.proveedores-no-results-content p {
    color: #6b7280;
    margin: 0 0 1.5rem 0;
    line-height: 1.5;
}

.proveedores-no-results-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.proveedores-btn-secondary-small {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    color: #374151;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.proveedores-btn-secondary-small:hover {
    background: #e5e7eb;
}

.proveedores-btn-primary-small {
    background: linear-gradient(135deg, #e53e3e 0%, #dc2626 100%);
    color: white;
}

.proveedores-btn-primary-small:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-1px);
}

/* Estado vacío */
.proveedores-empty-improved {
    text-align: center;
    padding: 2rem;
}

.proveedores-empty-icon {
    font-size: 3rem;
    color: #e53e3e;
    margin-bottom: 1rem;
}

.proveedores-empty-improved h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 0.5rem 0;
}

.proveedores-empty-improved p {
    color: #6b7280;
    margin: 0 0 1.5rem 0;
}

.proveedores-empty-actions {
    display: flex;
    justify-content: center;
}

/* Skeleton loading (Proveedores) */
.skeleton-table { padding: 8px 0; }
.skeleton-row { display:grid; grid-template-columns: 64px 1fr 1fr 1fr 120px 140px 170px; gap: 16px; align-items:center; padding: 14px 10px; border-bottom: 1px solid #f1f5f9; }
.skeleton-bar { display:block; height: 16px; border-radius: 8px; background: linear-gradient(90deg, #e5e7eb 25%, #f1f5f9 37%, #e5e7eb 63%); background-size: 400% 100%; animation: skeleton-shimmer 1.2s ease-in-out infinite; }
.skeleton-bar.short { width: 48px; }
.skeleton-bar.medium { width: 220px; }
.skeleton-bar.long { width: 100%; }
.skeleton-bar.actions { width: 120px; height: 14px; }
.skeleton-dot { width: 16px; height: 16px; border-radius: 50%; background: linear-gradient(90deg, #e5e7eb 25%, #f1f5f9 37%, #e5e7eb 63%); background-size: 400% 100%; animation: skeleton-shimmer 1.2s ease-in-out infinite; justify-self:start; }
@keyframes skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
@media (prefers-reduced-motion: reduce) { .skeleton-bar, .skeleton-dot { animation: none; } }

/* Responsive */
@media (max-width: 768px) {
    .proveedores-filters-layout-improved {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .proveedores-filters-left-improved {
        flex-direction: column;
        gap: 1rem;
    }
    
    .proveedores-select-with-label {
        justify-content: space-between;
    }
    
    .proveedores-input-clean {
        min-width: auto;
    }
    
    .proveedores-empty-actions,
    .proveedores-no-results-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .proveedores-action-buttons {
        gap: 0.25rem;
    }
    
    .proveedores-action-btn {
        width: 32px !important;
        height: 32px !important;
        font-size: 14px !important;
    }
}
</style>

@section('scripts')
<script>
// JavaScript se maneja desde el archivo externo proveedores.js
console.log('✅ Vista de proveedores cargada - funciones disponibles desde proveedores.js');
</script>
@endsection