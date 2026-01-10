@extends('layout.layout')
@php
    $title = 'Nueva entrada de mercadería';
    $subTitle = 'Nueva entrada';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/compras/nueva-compra.js') . '?v=' . time() . '"></script>
    ';
@endphp

<head>
    <title>Nueva Entrada de Mercadería</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/compras/compras.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

@section('content')

<div class="grid grid-cols-12">
    <div class="col-span-12">
        <!-- Header Elegante -->
        <div class="compras-header-simple">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 style="color: white !important;">
                        <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                        {{ $title }}
                    </h1>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('compras.historial') }}" class="compras-btn-simple compras-btn-secondary-simple">
                        <iconify-icon icon="solar:history-bold-duotone"></iconify-icon>
                        Ver Historial
                    </a>
                </div>
            </div>
        </div>

        <!-- Formulario Principal Compacto -->
        <div class="compras-form-container-simple">
            <form id="formEntradaMercaderia" class="compras-form-simple">
                @csrf
        
        <div class="compras-stock-preview">
            <div class="preview-card">
                <div class="preview-label">Stock actual</div>
                <div class="preview-value" id="preview-stock-actual">—</div>
            </div>
            <div class="preview-card arrow">➜</div>
            <div class="preview-card">
                <div class="preview-label">Stock luego de entrada</div>
                <div class="preview-value" id="preview-stock-nuevo">—</div>
            </div>
        </div>
        
        <div class="compras-grid-simple">
            <!-- Fila 1: Producto, Proveedor, Cantidad -->
            <div class="compras-field-group">
                <label class="compras-label-simple">
                    <iconify-icon icon="solar:magnifer-bold-duotone"></iconify-icon>
                    Buscar Producto *
                </label>
                <div class="compras-busqueda-container">
                    <input type="text" id="buscar-producto" class="compras-input-simple" placeholder="Escriba para buscar producto..." autocomplete="off">
                    <button type="button" id="btn-limpiar-producto" class="compras-btn-limpiar" style="display: none;" title="Limpiar selección">
                        <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                    </button>
                    <input type="hidden" id="producto-id" name="producto_id" required>
                    <div id="resultados-busqueda" class="compras-resultados-busqueda" style="display: none;"></div>
                </div>
                <span class="compras-hint-simple">Escriba el nombre o código del producto que está recibiendo</span>
            </div>
            
            <div class="compras-field-group">
                <label class="compras-label-simple">
                    <iconify-icon icon="solar:truck-bold-duotone"></iconify-icon>
                    Proveedor *
                </label>
                <div class="select-wrapper">
                    <select id="proveedor-select" name="proveedor_id" class="compras-input-simple compras-select-simple" required>
                        <option value="">Seleccionar proveedor...</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}">{{ $prov->razon_social ?? $prov->nombre_comercial ?? 'Proveedor' }}</option>
                        @endforeach
                    </select>
                    <div class="select-arrow">
                        <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                    </div>
                </div>
                <span class="compras-hint-simple">Selecciona el proveedor de esta mercadería</span>
            </div>
            
            <div class="compras-field-group">
                <label class="compras-label-simple">
                    <iconify-icon icon="solar:hashtag-bold-duotone"></iconify-icon>
                    Cantidad Recibida *
                </label>
                <input type="number" name="cantidad" min="1" class="compras-input-simple" placeholder="0" required>
                <span class="compras-hint-simple">Ingresa la cantidad de unidades recibidas</span>
            </div>
            
            <!-- Fila 2: Precios -->
            <div class="compras-field-group">
                <label class="compras-label-simple">
                    <iconify-icon icon="solar:card-receive-bold-duotone"></iconify-icon>
                    Precio de Compra
                </label>
                <input type="number" step="0.01" name="precio_compra" class="compras-input-simple" placeholder="">
                <span class="compras-hint-simple">Dejar vacío para mantener el precio actual</span>
            </div>
            
            <div class="compras-field-group">
                <label class="compras-label-simple">
                    <iconify-icon icon="solar:card-send-bold-duotone"></iconify-icon>
                    Precio de Venta
                </label>
                <input type="number" step="0.01" name="precio_venta" class="compras-input-simple" placeholder="">
                <span class="compras-hint-simple">Dejar vacío para mantener el precio actual</span>
            </div>
            
            <!-- Campos de lote y vencimiento -->
            <div class="compras-field-group">
                <div class="flex justify-between items-center mb-1">
                    <label class="compras-label-simple mb-0">
                        <iconify-icon icon="solar:box-minimalistic-bold-duotone"></iconify-icon>
                        Lote *
                    </label>
                    
                    <!-- Checkbox estilo botón toggle -->
                    <label class="lote-toggle-container" title="Seleccione para usar un lote existente">
                        <input type="checkbox" id="check-lote-existente" class="lote-toggle-checkbox">
                        <span class="lote-toggle-btn">
                            <span class="lote-toggle-icon-check">✓</span>
                            Seleccionar existente
                        </span>
                    </label>
                </div>
                
                <!-- Container para nuevo lote -->
                <div id="container-lote-nuevo">
                    <input type="text" id="input-lote-texto" name="lote" class="compras-input-simple" placeholder="Código de lote" required>
                </div>
                
                <!-- Input hidden para ID de lote existente -->
                <input type="hidden" id="existing-lote-id" name="existing_lote_id" disabled>
                
                <!-- Select para lote existente (inicialmente oculto) -->
                <div id="container-lote-select" style="display: none;" class="select-wrapper">
                    <select id="select-lote" class="compras-input-simple compras-select-simple" disabled>
                        <option value="">Seleccionar lote...</option>
                    </select>
                    <div class="select-arrow">
                        <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                    </div>
                </div>

                <span class="compras-hint-simple">Identificador del lote de fabricación</span>
            </div>

            <div class="compras-field-group">
                <label class="compras-label-simple">
                    <iconify-icon icon="solar:calendar-date-bold-duotone"></iconify-icon>
                    Fecha Vencimiento *
                </label>
                <input type="date" id="input-fecha-vencimiento" name="fecha_vencimiento" class="compras-input-simple" required>
                <span class="compras-hint-simple">Fecha de expiración del lote</span>
            </div>

        </div>
        
        <!-- Botones de Acción -->
        <div class="compras-actions-simple">
            <a href="{{ route('compras.historial') }}" class="compras-btn-simple compras-btn-cancel-simple">
                <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                Cancelar
            </a>
            <button type="submit" id="btn-registrar-entrada" class="compras-btn-simple compras-btn-save-simple" disabled>
                <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                Registrar Entrada de Mercadería
            </button>
        </div>
    </form>
        </div>
    </div>
</div>

@endsection

<style>
/* Estilos para el toggle de lote */
.lote-toggle-container {
    position: relative;
    display: inline-block;
    cursor: pointer;
    user-select: none;
}

.lote-toggle-checkbox {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.lote-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #4b5563;
    transition: all 0.3s ease;
}

.lote-toggle-icon-check {
    display: none;
    font-weight: bold;
    font-size: 0.8rem;
}

/* Estado Checkado (Pintado) */
.lote-toggle-checkbox:checked ~ .lote-toggle-btn {
    background-color: #e53e3e; /* Rojo corporativo */
    color: white;
    border-color: #c53030;
    box-shadow: 0 2px 4px rgba(229, 62, 62, 0.3);
}

.lote-toggle-checkbox:checked ~ .lote-toggle-btn .lote-toggle-icon-check {
    display: inline-block;
}

/* Estado Deshabilitado */
.lote-toggle-checkbox:disabled ~ .lote-toggle-btn {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #e5e7eb;
    color: #9ca3af;
}

/* Estilos específicos para la nueva entrada compacta */
.compras-header-simple {
    background: linear-gradient(135deg, #e53e3e 0%, #f56565 100%) !important;
    color: white !important;
    padding: 1.5rem 2rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.compras-header-simple h1 {
    font-size: 1.75rem !important;
    font-weight: 700 !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
    color: white !important;
}

.compras-header-simple p {
    margin: 0.5rem 0 0 0 !important;
    opacity: 0.9 !important;
    font-size: 1rem !important;
    color: white !important;
}

.compras-form-container-simple {
    background: white !important;
    border-radius: 12px !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
    padding: 2rem !important;
    margin-bottom: 0 !important;
}

.compras-stock-preview { display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem; padding:0.75rem 1rem; background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; }
.preview-card { display:flex; flex-direction:column; justify-content:center; align-items:flex-start; gap:.25rem; }
.preview-card.arrow { font-size:1.25rem; color:#64748b; }
.preview-label { font-size:.85rem; color:#6b7280; }
.preview-value { font-size:1.25rem; font-weight:700; color:#111827; }

.compras-form-simple {
    max-width: none !important;
}

.compras-grid-simple {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
    gap: 1.5rem !important;
    margin-bottom: 2rem !important;
}

.compras-field-group {
    display: flex !important;
    flex-direction: column !important;
    gap: 0.5rem !important;
}

.compras-field-full {
    grid-column: 1 / -1 !important;
}

.compras-label-simple {
    font-weight: 600 !important;
    color: #374151 !important;
    font-size: 0.875rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    margin-bottom: 0.25rem !important;
}

.compras-label-simple iconify-icon {
    color: #e53e3e !important;
    font-size: 1rem !important;
}

/* FORZAR ESTILOS DE INPUTS */
.compras-input-simple,
.compras-textarea-simple,
.compras-select-simple {
    padding: 0.75rem 1rem !important;
    border: 2px solid #d1d5db !important;
    border-radius: 8px !important;
    font-size: 0.875rem !important;
    transition: all 0.2s ease !important;
    background: #f9fafb !important;
    color: #374151 !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.compras-input-simple:focus,
.compras-textarea-simple:focus,
.compras-select-simple:focus {
    outline: none !important;
    border-color: #e53e3e !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1) !important;
}

.compras-input-simple:hover,
.compras-textarea-simple:hover,
.compras-select-simple:hover {
    border-color: #9ca3af !important;
    background: white !important;
}

/* SELECT WRAPPER PARA FLECHA FIJA */
.select-wrapper {
    position: relative !important;
}

.select-wrapper select {
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    padding-right: 2.5rem !important;
}

.select-arrow {
    position: absolute !important;
    top: 50% !important;
    right: 0.75rem !important;
    transform: translateY(-50%) !important;
    pointer-events: none !important;
    color: #6b7280 !important;
    font-size: 1rem !important;
    z-index: 1 !important;
}

.compras-hint-simple {
    font-size: 0.75rem !important;
    color: #6b7280 !important;
    margin-top: 0.25rem !important;
}

.compras-actions-simple {
    display: flex !important;
    gap: 1rem !important;
    justify-content: flex-end !important;
    padding-top: 1.5rem !important;
    border-top: 1px solid #e5e7eb !important;
}

/* FORZAR ESTILOS DE BOTONES */
.compras-btn-simple {
    padding: 0.75rem 1.5rem !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    border: none !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    text-decoration: none !important;
}

.compras-btn-secondary-simple {
    background: #f3f4f6 !important;
    color: #374151 !important;
    border: 1px solid #d1d5db !important;
}

.compras-btn-secondary-simple:hover {
    background: #e5e7eb !important;
    color: #374151 !important;
}

.compras-btn-cancel-simple {
    background: #f3f4f6 !important;
    color: #374151 !important;
    border: 1px solid #d1d5db !important;
}

.compras-btn-cancel-simple:hover {
    background: #e5e7eb !important;
    color: #374151 !important;
}

.compras-btn-save-simple {
    background: linear-gradient(135deg, #e53e3e 0%, #f56565 100%) !important;
    color: white !important;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
    border: none !important;
}

.compras-btn-save-simple:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
    background: linear-gradient(135deg, #c53030 0%, #e53e3e 100%) !important;
    color: white !important;
}

.compras-btn-save-simple:disabled {
    background: #d1d5db !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    transform: none !important;
    box-shadow: none !important;
}

.compras-btn-save-simple:disabled:hover {
    background: #d1d5db !important;
    color: #9ca3af !important;
    transform: none !important;
    box-shadow: none !important;
}

/* FORZAR SOLO PARA INPUTS DE COMPRAS */
.compras-form-container-simple input, 
.compras-form-container-simple select, 
.compras-form-container-simple textarea {
    border-radius: 8px !important;
    background: #f9fafb !important;
    border: 2px solid #d1d5db !important;
}

.compras-form-container-simple input:focus, 
.compras-form-container-simple select:focus, 
.compras-form-container-simple textarea:focus {
    background: white !important;
    border-color: #e53e3e !important;
}

.compras-form-container-simple input:hover, 
.compras-form-container-simple select:hover, 
.compras-form-container-simple textarea:hover {
    background: white !important;
    border-color: #9ca3af !important;
}

/* Responsive */
@media (max-width: 768px) {
    .compras-grid-simple {
        grid-template-columns: 1fr !important;
    }
    
    .compras-actions-simple {
        flex-direction: column !important;
    }
    
    .compras-btn-simple {
        width: 100% !important;
        justify-content: center !important;
    }
    
    .compras-header-simple {
        padding: 1rem 1.5rem !important;
    }
    
    .compras-header-simple h1 {
        font-size: 1.5rem !important;
    }
    
    .compras-form-container-simple {
        padding: 1.5rem !important;
    }
}

/* Estilos para búsqueda dinámica de productos */
.compras-busqueda-container {
    position: relative !important;
}

/* Botón limpiar dentro del campo de búsqueda */
.compras-btn-limpiar {
    position: absolute !important;
    right: 8px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    background: none !important;
    border: none !important;
    color: #9ca3af !important;
    cursor: pointer !important;
    padding: 4px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.2s ease !important;
    z-index: 10 !important;
}

.compras-btn-limpiar:hover {
    color: #dc2626 !important;
    background: #fef2f2 !important;
}

.compras-btn-limpiar iconify-icon {
    font-size: 1.2rem !important;
}

.compras-resultados-busqueda {
    position: absolute !important;
    top: 100% !important;
    left: 0 !important;
    right: 0 !important;
    background: white !important;
    border: 2px solid #e5e7eb !important;
    border-top: none !important;
    border-radius: 0 0 8px 8px !important;
    max-height: 300px !important;
    overflow-y: auto !important;
    z-index: 1000 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

/* Aviso visual de vencimiento cercano */
.input-warning { border-color: #f59e0b !important; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15) !important; }

.compras-resultado-item {
    padding: 0.75rem !important;
    border-bottom: 1px solid #f3f4f6 !important;
    cursor: pointer !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    transition: background-color 0.2s ease !important;
}

.compras-resultado-item:hover {
    background: #f9fafb !important;
}

.compras-resultado-item:last-child {
    border-bottom: none !important;
}

.compras-resultado-info {
    flex: 1 !important;
}

.compras-resultado-nombre {
    font-weight: 600 !important;
    color: #374151 !important;
    font-size: 0.875rem !important;
    margin-bottom: 0.25rem !important;
}

.compras-resultado-detalles {
    font-size: 0.75rem !important;
    color: #6b7280 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.compras-stock {
    background: #dbeafe !important;
    color: #1e40af !important;
    padding: 0.125rem 0.375rem !important;
    border-radius: 4px !important;
    font-weight: 500 !important;
}

.compras-resultado-precio {
    font-weight: 600 !important;
    color: #059669 !important;
    font-size: 0.875rem !important;
}

.compras-no-resultados {
    padding: 1rem !important;
    text-align: center !important;
    color: #6b7280 !important;
    font-style: italic !important;
}

.compras-error {
    padding: 1rem !important;
    text-align: center !important;
    color: #dc2626 !important;
    background: #fef2f2 !important;
    border-radius: 4px !important;
    margin: 0.5rem !important;
}

/* Estilos para autocompletado inteligente */
.compras-resultado-item.sugerido {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
    border-left: 4px solid #f59e0b !important;
}

.compras-resultado-item.sugerido:hover {
    background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%) !important;
}

.compras-resultado-item.stock-agotado {
    background: #fef2f2 !important;
    border-left: 4px solid #dc2626 !important;
}

.compras-resultado-item.stock-bajo {
    background: #fefce8 !important;
    border-left: 4px solid #eab308 !important;
}

.compras-resultado-item.proximo-vencimiento {
    background: #fff7ed !important;
    border-left: 4px solid #ea580c !important;
}

.compras-stock.stock-agotado {
    background: #fecaca !important;
    color: #991b1b !important;
}

.compras-stock.stock-bajo {
    background: #fef3c7 !important;
    color: #92400e !important;
}

.compras-lote {
    background: #e0e7ff !important;
    color: #3730a3 !important;
    padding: 0.125rem 0.375rem !important;
    border-radius: 4px !important;
    font-weight: 500 !important;
    font-size: 0.7rem !important;
}

.compras-estado-texto {
    font-size: 0.7rem !important;
    font-weight: 600 !important;
    margin-top: 0.25rem !important;
    padding: 0.125rem 0.375rem !important;
    border-radius: 4px !important;
    display: inline-block !important;
}

.compras-resultado-item.stock-agotado .compras-estado-texto {
    background: #dc2626 !important;
    color: white !important;
}

.compras-resultado-item.stock-bajo .compras-estado-texto {
    background: #eab308 !important;
    color: white !important;
}

.compras-resultado-item.proximo-vencimiento .compras-estado-texto {
    background: #ea580c !important;
    color: white !important;
}

.compras-historial {
    font-size: 0.7rem !important;
    color: #6b7280 !important;
    margin-top: 0.25rem !important;
    font-style: italic !important;
}

.estado-icono {
    margin-left: 0.5rem !important;
    font-size: 1rem !important;
}

/* Animaciones para elementos sugeridos */
.compras-resultado-item.sugerido {
    animation: pulseGlow 2s infinite !important;
}

@keyframes pulseGlow {
    0%, 100% {
        box-shadow: 0 0 5px rgba(245, 158, 11, 0.3) !important;
    }
    50% {
        box-shadow: 0 0 15px rgba(245, 158, 11, 0.6) !important;
    }
}

/* Mejoras en la visualización de resultados */
.compras-resultado-detalles {
    flex-wrap: wrap !important;
    gap: 0.25rem !important;
}

.compras-resultado-detalles > span {
    white-space: nowrap !important;
}

/* Responsive para autocompletado */
@media (max-width: 768px) {
    .compras-resultado-item {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.5rem !important;
    }
    
    .compras-resultado-precio {
        align-self: flex-end !important;
    }
    
    .compras-resultado-detalles {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 0.25rem !important;
    }
}
</style>

{{-- Eliminado: inclusión duplicada del script. La carga se realiza arriba vía $script con versión dinámica. --}}

<script>
// Este script solo maneja funcionalidades adicionales específicas de la vista
document.addEventListener('DOMContentLoaded', function() {
    console.log('Vista de nueva entrada de mercadería cargada correctamente');
});
</script>

{{-- Overlay de carga reutilizable (como en Presentación/Categoría) --}}
@include('components.loading-overlay', [
    'id' => 'loadingOverlay',
    'size' => 36,
    'inner' => 14,
    'label' => 'Procesando entrada...'
])
