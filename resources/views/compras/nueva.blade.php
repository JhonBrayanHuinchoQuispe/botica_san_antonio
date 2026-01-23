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
    <link rel="stylesheet" href="{{ asset('assets/css/compras/compras-modern.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
</head>

@section('content')

<div class="grid grid-cols-12">
    <div class="col-span-12">
        
        <div class="compras-form-container-simple">
            <form id="formEntradaMercaderia" class="compras-form-simple">
                @csrf
        
                <!-- Resumen de Stock Superior -->
                <div class="compras-stock-preview-container">
                    <div class="compras-stock-preview">
                        <div class="preview-card-simple current">
                            <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                            <span class="label">Stock actual:</span>
                            <span class="value" id="preview-stock-actual">—</span>
                        </div>
                        
                        <div class="preview-divider">
                            <iconify-icon icon="solar:double-alt-arrow-right-bold-duotone"></iconify-icon>
                        </div>

                        <div class="preview-card-simple next">
                            <iconify-icon icon="solar:box-minimalistic-bold-duotone"></iconify-icon>
                            <span class="label">Stock post-entrada:</span>
                            <span class="value" id="preview-stock-nuevo">—</span>
                        </div>
                    </div>

                    <div class="compras-header-actions">
                        <a href="{{ route('compras.historial') }}" class="compras-btn-simple compras-btn-info-simple">
                            <iconify-icon icon="solar:history-bold-duotone"></iconify-icon>
                            Ver Historial
                        </a>
                    </div>
                </div>
        
                <div class="compras-sections-grid">
                    <!-- Sección 1: Información del Producto y Lote -->
                    <div class="compras-section-card">
                        <div class="section-header">
                            <iconify-icon icon="solar:info-circle-bold-duotone"></iconify-icon>
                            <h3>Información de Producto y Lote</h3>
                        </div>
                        
                        <div class="section-body">
                            <div class="compras-field-group full-width">
                                <label class="compras-label-simple">
                                    <iconify-icon icon="solar:magnifer-bold-duotone"></iconify-icon>
                                    Buscar Producto *
                                </label>
                                <div class="compras-busqueda-container">
                                    <input type="text" id="buscar-producto" class="compras-input-simple" placeholder="Nombre, código de barras o lote..." autocomplete="off">
                                    <button type="button" id="btn-limpiar-producto" class="compras-btn-limpiar" style="display: none;" title="Limpiar selección">
                                        <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                                    </button>
                                    <input type="hidden" id="producto-id" name="producto_id" required>
                                    <div id="resultados-busqueda" class="compras-resultados-busqueda" style="display: none;"></div>
                                </div>
                                <span class="compras-hint-simple">Escriba para buscar y seleccione el producto</span>
                            </div>

                            <div class="fields-row">
                                <div class="compras-field-group">
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="compras-label-simple mb-0">
                                            <iconify-icon icon="solar:box-minimalistic-bold-duotone"></iconify-icon>
                                            Lote *
                                        </label>
                                        <label class="lote-toggle-container" title="Seleccione para usar un lote existente">
                                            <input type="checkbox" id="check-lote-existente" class="lote-toggle-checkbox">
                                            <span class="lote-toggle-btn">
                                                <span class="lote-toggle-icon-check">✓</span>
                                                Existente
                                            </span>
                                        </label>
                                    </div>
                                    <div id="container-lote-nuevo">
                                        <input type="text" id="input-lote-texto" name="lote" class="compras-input-simple" placeholder="Ej: LOTE123" required>
                                    </div>
                                    <input type="hidden" id="existing-lote-id" name="existing_lote_id" disabled>
                                    <div id="container-lote-select" style="display: none;" class="select-wrapper">
                                        <select id="select-lote" class="compras-input-simple compras-select-simple" disabled>
                                            <option value="">Seleccionar lote...</option>
                                        </select>
                                        <div class="select-arrow">
                                            <iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon>
                                        </div>
                                    </div>
                                </div>

                                <div class="compras-field-group">
                                    <label class="compras-label-simple">
                                        <iconify-icon icon="solar:calendar-date-bold-duotone"></iconify-icon>
                                        Vencimiento *
                                    </label>
                                    <input type="date" id="input-fecha-vencimiento" name="fecha_vencimiento" class="compras-input-simple" min="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Proveedor y Cantidad -->
                    <div class="compras-section-card">
                        <div class="section-header">
                            <iconify-icon icon="solar:delivery-bold-duotone"></iconify-icon>
                            <h3>Proveedor y Cantidad</h3>
                        </div>
                        
                        <div class="section-body">
                            <div class="fields-row">
                                <div class="compras-field-group" style="flex: 2;">
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
                                </div>

                                <div class="compras-field-group" style="flex: 1;">
                                    <label class="compras-label-simple">
                                        <iconify-icon icon="solar:hashtag-bold-duotone"></iconify-icon>
                                        Cant. Recibida *
                                    </label>
                                    <input type="number" name="cantidad" id="input-cantidad" min="1" class="compras-input-simple" placeholder="0" required>
                                </div>
                            </div>

                            <div class="fields-row mt-4">
                                <div class="compras-field-group">
                                    <label class="compras-label-simple">
                                        <iconify-icon icon="solar:card-receive-bold-duotone"></iconify-icon>
                                        Precio Compra (Unid)
                                    </label>
                                    <input type="number" step="0.01" name="precio_compra" id="input-precio-compra" class="compras-input-simple" placeholder="0.00">
                                </div>

                                <div class="compras-field-group">
                                    <label class="compras-label-simple">
                                        <iconify-icon icon="solar:card-send-bold-duotone"></iconify-icon>
                                        Precio Venta (Unid)
                                    </label>
                                    <input type="number" step="0.01" name="precio_venta" id="input-precio-venta" class="compras-input-simple" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nueva Distribución Inferior: Presentaciones y Acciones -->
                <div class="compras-bottom-container" id="container-inferior" style="display: none;">
                    <div class="compras-layout-split">
                        <!-- Lado Izquierdo: Presentaciones -->
                        <div class="compras-section-card" id="section-presentaciones">
                            <div class="section-header">
                                <iconify-icon icon="solar:tag-price-bold-duotone"></iconify-icon>
                                <h3>Precios por Presentación</h3>
                                <span class="section-badge">Obligatorio</span>
                            </div>
                            
                            <div class="section-body">
                                <div class="table-responsive">
                                    <table class="compras-table">
                                        <thead>
                                            <tr>
                                                <th>Presentación</th>
                                                <th width="150">Unidades</th>
                                                <th width="180">Precio Venta (S/.)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lista-presentaciones-precios">
                                            <!-- Se carga dinámicamente con JS -->
                                        </tbody>
                                    </table>
                                </div>
                                <p class="compras-hint-simple mt-3">
                                    <iconify-icon icon="solar:info-circle-bold" style="vertical-align: middle;"></iconify-icon>
                                    Ajuste los precios de venta para este lote.
                                </p>
                            </div>
                        </div>

                        <!-- Lado Derecho: Acciones -->
                        <div class="compras-actions-card">
                            <div class="actions-header">
                                <iconify-icon icon="solar:check-read-bold-duotone"></iconify-icon>
                                <h3>Finalizar Registro</h3>
                            </div>
                            <div class="actions-body">
                                <div class="actions-buttons-stack">
                                    <button type="submit" id="btn-registrar-entrada" class="compras-btn-simple compras-btn-save-simple w-full" disabled>
                                        <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                        Registrar Entrada
                                    </button>
                                    
                                    <button type="button" id="btn-limpiar-todo" class="compras-btn-simple compras-btn-cancel-simple w-full">
                                        <iconify-icon icon="solar:eraser-bold-duotone"></iconify-icon>
                                        Limpiar Todo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

<style>
    .input-precio-table {
        width: 100%;
        border: 1px solid #f1f5f9 !important;
        border-radius: 8px !important;
        padding: 0.5rem 0.75rem !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        color: #334155 !important;
        background: #f8fafc !important;
        transition: all 0.2s;
    }
    .input-precio-table:focus {
        border-color: #10b981 !important;
        background: #fff !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
    }
    .compras-inline-input-group {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-radius: 8px;
        padding: 0 0.75rem;
        width: fit-content;
    }
    .compras-inline-input-group input {
        border: none !important;
        background: transparent !important;
        padding: 0.5rem 0 !important;
        width: 50px !important;
        text-align: center;
        font-weight: 700;
        color: #1e293b;
        box-shadow: none !important;
    }
    .compras-bottom-container {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #e5e7eb;
    }
    .compras-layout-split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        align-items: start;
    }
    .compras-actions-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        position: sticky;
        top: 2rem;
    }
    .actions-header {
        background: #f8fafc;
        padding: 1.25rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .actions-header h3 {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    .actions-header iconify-icon {
    font-size: 1.25rem;
    color: #10b981;
}
    .actions-body {
        padding: 1.5rem;
    }
    .actions-buttons-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .w-full { width: 100% !important; justify-content: center !important; }

    @media (max-width: 1024px) {
        .compras-layout-split {
            grid-template-columns: 1fr;
        }
        .compras-actions-card {
            position: static;
        }
    }
    .compras-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.5rem;
    }
    .compras-table th {
        background: transparent;
        padding: 0.85rem 1rem;
        text-align: center;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #94a3b8;
        font-weight: 800;
        border-bottom: 1px solid #f1f5f9;
    }
    .compras-table th:first-child { text-align: left; }
    .compras-table th:last-child { text-align: right; }

    .compras-table td {
        padding: 1.25rem 1rem;
        border-bottom: 1px solid #f8fafc;
        vertical-align: middle;
        text-align: center;
    }
    .compras-table td:first-child { text-align: left; }
    .compras-table td:last-child { text-align: right; }

    .compras-table tr:hover td {
        background: #fdfdfd;
    }
    .compras-table tr:last-child td {
        border-bottom: none;
    }

    /* Inputs Minimalistas (Estilo Foto 2) */
    .input-minimalist {
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        text-align: center;
        width: 100%;
        box-shadow: none !important;
        transition: color 0.2s;
    }
    .input-minimalist:focus {
        color: #e53e3e !important;
        outline: none !important;
    }
    .input-minimalist.price-text {
        color: #10b981 !important; /* Verde como la foto 2 */
        text-align: right;
        font-size: 16px !important;
    }
    .input-minimalist[readonly] {
        cursor: default;
    }

    /* Badge de Unidades */
    .unit-badge {
        background: #f1f5f9;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    .unit-badge input {
        width: 35px !important;
        text-align: center;
        font-weight: 800 !important;
        color: #1e293b !important;
    }
    .section-badge {
        background: #fee2e2;
        color: #ef4444;
        padding: 0.25rem 0.75rem;
        border-radius: 99px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
    }
</style>

<style>

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

.lote-toggle-checkbox:checked ~ .lote-toggle-btn {
    background-color: #10b981; 
    color: white;
    border-color: #059669;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

.lote-toggle-checkbox:checked ~ .lote-toggle-btn .lote-toggle-icon-check {
    display: inline-block;
}

.lote-toggle-checkbox:disabled ~ .lote-toggle-btn {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: #e5e7eb;
    color: #9ca3af;
}

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

    .compras-stock-preview-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        background: #f8fafc;
        padding: 0.75rem 1.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }

    .compras-stock-preview { 
        display: flex; 
        align-items: center; 
        gap: 2.5rem; 
        background: transparent;
        border: none;
        padding: 0;
    }

    .preview-card-simple { 
        display: flex; 
        align-items: center; 
        gap: 0.75rem; 
    }
    .preview-card-simple iconify-icon {
        font-size: 1.5rem;
    }
    .preview-card-simple.current iconify-icon { color: #3b82f6; }
.preview-card-simple.next iconify-icon { color: #10b981; }
.compras-header-actions .compras-btn-info-simple {
    background: #10b981 !important;
    color: white !important;
    border: none !important;
    font-weight: 700 !important;
    padding: 0.5rem 1rem !important;
    border-radius: 8px !important;
    transition: all 0.2s ease !important;
}
.compras-header-actions .compras-btn-info-simple:hover {
    background: #059669 !important;
    transform: translateY(-1px) !important;
}
    .preview-card-simple .label { 
        font-size: 0.95rem; 
        font-weight: 600; 
        color: #64748b; 
    }
    .preview-card-simple .value { 
        font-size: 1.25rem; 
        font-weight: 800; 
        color: #1e293b; 
    }
    .preview-divider {
        color: #cbd5e1;
        display: flex;
        align-items: center;
        font-size: 1.25rem;
    }

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
    color: #10b981 !important;
    font-size: 1rem !important;
}

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
    border-color: #10b981 !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
}

.compras-input-simple:hover,
.compras-textarea-simple:hover,
.compras-select-simple:hover {
    border-color: #9ca3af !important;
    background: white !important;
}

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
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%) !important;
    color: white !important;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
    border: none !important;
}

.compras-btn-save-simple:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
    color: white !important;
}

.compras-btn-info-simple {
    background: white !important;
    color: #64748b !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
}

.compras-btn-info-simple:hover {
    background: #f8fafc !important;
    color: #1e293b !important;
    border-color: #cbd5e1 !important;
}

.compras-header-actions {
    display: flex;
    align-items: center;
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

.compras-form-container-simple input, 
.compras-form-container-simple select, 
.compras-form-container-simple textarea {
    border-radius: 8px !important;
    background: #f9fafb !important;
    border: 2px solid #d1d5db !important;
}

.compras-busqueda-container {
    position: relative !important;
}

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

.compras-resultado-item {
    padding: 12px 16px !important;
    border-bottom: 1px solid #f1f5f9 !important;
    cursor: pointer !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    transition: all 0.2s ease !important;
    position: relative !important;
    overflow: hidden !important;
}

.compras-resultado-item:hover {
    background: #f8fafc !important;
    padding-left: 20px !important;
}

.compras-resultado-nombre {
    font-weight: 700 !important;
    color: #1e293b !important;
    font-size: 0.95rem !important;
    margin-bottom: 4px !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.compras-resultado-detalles {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    flex-wrap: wrap !important;
}

.compras-stock {
    background: #e0f2fe !important;
    color: #0369a1 !important;
    padding: 2px 8px !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    font-size: 0.75rem !important;
}

.compras-lote {
    background: #f1f5f9 !important;
    color: #64748b !important;
    padding: 2px 8px !important;
    border-radius: 6px !important;
    font-size: 0.75rem !important;
    border: 1px solid #e2e8f0 !important;
}

.compras-resultado-precio {
    font-weight: 800 !important;
    color: #10b981 !important;
    font-size: 1.1rem !important;
    text-shadow: 0 1px 2px rgba(16, 185, 129, 0.1) !important;
}

.compras-historial {
    font-size: 0.7rem !important;
    color: #94a3b8 !important;
    margin-top: 4px !important;
    font-style: italic !important;
}

/* Estilos para la tabla de presentaciones */
.unit-control-btn {
    width: 32px !important;
    height: 32px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 50% !important;
    background: transparent !important;
    color: #3b82f6 !important;
    border: none !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
}

.unit-control-btn:hover {
    background: rgba(59, 130, 246, 0.1) !important;
    color: #2563eb !important;
    transform: scale(1.1) !important;
}

.unit-control-btn iconify-icon {
    font-size: 24px !important;
}

.input-unit-blue {
    width: 60px !important;
    text-align: center !important;
    background: #f8fafc !important;
    border: 2px solid #dbeafe !important;
    border-radius: 8px !important;
    font-weight: 700 !important;
    color: #1e40af !important;
    padding: 4px !important;
}

.input-unit-blue:focus {
    border-color: #3b82f6 !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

.icon-presentation-blue {
    color: #60a5fa !important;
}

.ganancia-tag {
    font-size: 10px !important;
    color: #10b981 !important;
    font-weight: 700 !important;
    margin-top: 2px !important;
    display: block !important;
}

.badge-obligatorio {
    transition: opacity 0.3s ease !important;
}

.estado-badge {
    padding: 2px 10px !important;
    border-radius: 20px !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
}

.estado-badge.stock-agotado { background: #fee2e2 !important; color: #dc2626 !important; border: 1px solid #fecaca !important; }
.estado-badge.stock-bajo { background: #fef3c7 !important; color: #d97706 !important; border: 1px solid #fde68a !important; }
.estado-badge.proximo-vencimiento { background: #ffedd5 !important; color: #ea580c !important; border: 1px solid #fed7aa !important; }

.concentracion-text {
    color: #64748b !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
}

.compras-resultado-item.sugerido {
    background: linear-gradient(to right, #fffdfa, #fffbeb) !important;
    border-left: 4px solid #f59e0b !important;
}

.compras-resultado-item.sugerido:hover {
    background: linear-gradient(to right, #fffbeb, #fef3c7) !important;
}

.compras-form-container-simple input:focus, 
.compras-form-container-simple select:focus, 
.compras-form-container-simple textarea:focus {
    background: white !important;
    border-color: #10b981 !important;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
}

.compras-form-container-simple input:hover, 
.compras-form-container-simple select:hover, 
.compras-form-container-simple textarea:hover {
    background: white !important;
    border-color: #9ca3af !important;
}

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
}
</style>

{{-- Eliminado: inclusión duplicada del script. La carga se realiza arriba vía $script con versión dinámica. --}}

<script>

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
