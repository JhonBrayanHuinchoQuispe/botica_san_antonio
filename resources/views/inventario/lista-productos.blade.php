@extends('layout.layout')
@php
    $title = 'Lista de Productos';
    $subTitle = 'Lista de Productos';
    $script = '<script src="' . asset('assets/js/inventario/categoria/lista.js') . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/categoria/agregar.js') . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/categoria/editar.js') . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/categoria/eliminar.js') . '"></script>';
@endphp

@push('head')
    <title>Lista de Productos</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logotipo.png') }}" sizes="16x16">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/productos_botica.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/crud.css') }}?v={{ time() }}">
    
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/modal_agregar.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/modal_editar.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/modal_ver.css') }}?v={{ time() }}">
    
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/productos/tablas.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/ubicaciones-multiples.css') }}?v={{ time() }}">
    
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/presentaciones-tabla.css') }}?v={{ time() }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    
    <script src="{{ asset('assets/js/inventario/eliminar.js') }}?v={{ time() }}" defer></script>
    
    <script src="{{ asset('assets/js/inventario/presentaciones-tabla-mejorado.js') }}?v={{ time() }}" defer></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        
        .product-info-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .product-name-highlight {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            line-height: 1.2;
        }
        .product-concentration-sub {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 500;
        }

        
        .stock-chip { 
            display:inline-flex !important; 
            align-items:center !important; 
            gap:.45rem !important; 
            padding:.35rem .7rem !important; 
            border-radius:9999px !important; 
            font-weight:700 !important; 
            font-size:.95rem !important; 
            border:1px solid transparent !important; 
            box-shadow:0 1px 2px rgba(0,0,0,0.05) !important; 
        }
        .stock-chip.high { background:#e7f5ec !important; color:#166534 !important; border-color:#c9e9d6 !important; }
        .stock-chip.medium { background:#fff7ed !important; color:#92400e !important; border-color:#fed7aa !important; }
        .stock-chip.low { background:#fef2f2 !important; color:#b91c1c !important; border-color:#fecaca !important; }
        .stock-chip.empty { background:#fef2f2 !important; color:#b91c1c !important; border-color:#fecaca !important; }

        
        .price-display-wrapper {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .price-main {
            font-size: 1.1rem;
            font-weight: 800;
            color: #059669;
        }
        .price-secondary {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 600;
            background: #f8fafc;
            padding: 1px 6px;
            border-radius: 4px;
            width: fit-content;
        }

        
        .lote-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.85rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            min-width: 140px;
        }
        .lote-pill:hover {
            transform: translateY(-1px);
        }
        .lote-pill iconify-icon {
            font-size: 1.25rem;
        }
        
        
        .lote-pill.single.normal {
            border-color: #bfdbfe !important;
            background-color: #f0f7ff !important; 
        }
        .lote-pill.single.normal iconify-icon, .lote-pill.single.normal .lote-code-mini {
            color: #3b82f6 !important;
        }

        
        .lote-pill.multiple.normal {
            border-color: #ddd6fe !important;
            background-color: #f5f3ff !important; 
        }
        .lote-pill.multiple.normal iconify-icon, .lote-pill.multiple.normal .lote-count-mini {
            color: #8b5cf6 !important;
        }

        
        .lote-pill.expired {
            background-color: #fef2f2 !important;
            border-color: #fecaca !important;
        }
        .lote-pill.expired iconify-icon, .lote-pill.expired .lote-code-mini, .lote-pill.expired .lote-count-mini {
            color: #ef4444 !important;
        }
        .lote-pill.warning {
            background-color: #fffbeb !important;
            border-color: #fde68a !important;
        }
        .lote-pill.warning iconify-icon, .lote-pill.warning .lote-code-mini, .lote-pill.warning .lote-count-mini {
            color: #f59e0b !important;
        }

        .lote-info-mini { display: flex; flex-direction: column; line-height: 1.1; }
        .lote-code-mini, .lote-count-mini { font-weight: 700; }
        .lote-date-mini, .lote-next-mini { font-size: 0.75rem; opacity: 0.8; }
        .lote-arrow { margin-left: auto; font-size: 0.9rem; opacity: 0.5; }

        
        .presentacion-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        
        .presentacion-pill.single.unit {
            background: #f0f7ff !important;
            color: #3b82f6 !important;
            border-color: #bfdbfe !important;
        }
        
        .presentacion-pill.single.none {
            background: #f8fafc !important;
            color: #64748b !important;
            border-color: #e2e8f0 !important;
        }
        .presentacion-pill.multiple {
            background: #f5f3ff !important; 
            color: #7c3aed !important;
            border-color: #ddd6fe !important;
            cursor: pointer;
        }
        .presentacion-pill.multiple:hover {
            filter: brightness(0.98);
            transform: translateY(-1px);
        }

        
        .product-img-wrapper {
            position: relative;
            width: 48px;
            height: 48px;
            z-index: 5;
        }
        .product-img-zoom {
            cursor: default;
        }

        
        .active-filters-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 0;
            padding: 0;
            transition: all 0.3s ease;
        }
        .active-filters-container.has-filters {
            margin-bottom: 12px;
            padding: 2px 0 8px 0;
        }
        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid transparent;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        
        .filter-tag.status-normal { background: #f0fdf4 !important; color: #16a34a !important; border-color: #bbf7d0 !important; }
        .filter-tag.status-bajo-stock { background: #fffbeb !important; color: #d97706 !important; border-color: #fde68a !important; }
        .filter-tag.status-por-vencer { background: #fff7ed !important; color: #ea580c !important; border-color: #ffedd5 !important; }
        .filter-tag.status-vencido { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }
        .filter-tag.status-agotado { background: #f8fafc !important; color: #475569 !important; border-color: #e2e8f0 !important; }
        .filter-tag.search-tag { background: #eff6ff !important; color: #2563eb !important; border-color: #dbeafe !important; }

        .filter-tag .remove-filter {
            cursor: pointer;
            opacity: 0.6;
            font-size: 1.1rem;
            transition: opacity 0.2s;
        }
        .filter-tag .remove-filter:hover {
            opacity: 1;
        }

        
        .acciones-cell button {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
            cursor: pointer;
            margin: 0 3px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        .acciones-cell button iconify-icon { font-size: 1.25rem; }

        
        .btn-view { 
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%) !important;
            color: #2563eb !important;
            border: 1px solid rgba(59, 130, 246, 0.15) !important;
        }
        .btn-view:hover { 
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.15) 100%) !important;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        
        .btn-edit { 
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(16, 185, 129, 0.08) 100%) !important;
            color: #059669 !important;
            border: 1px solid rgba(16, 185, 129, 0.15) !important;
        }
        .btn-edit:hover { 
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.15) 100%) !important;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        
        .btn-delete { 
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.12) 0%, rgba(239, 68, 68, 0.08) 100%) !important;
            color: #dc2626 !important;
            border: 1px solid rgba(239, 68, 68, 0.15) !important;
        }
        .btn-delete:hover { 
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.15) 100%) !important;
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }
        .acciones-cell button iconify-icon { font-size: 1.25rem; }

        
        .table-scroll { overflow: visible !important; }
        .tabla-productos-botica { border-collapse: separate !important; }
    </style>
    <style>
        
        .lote-badge { display: none; }
    </style>
@endpush

@section('content')
<div class="grid grid-cols-12">
    <div class="col-span-12">
        <div class="card border-0 overflow-hidden">
            
            <div class="card-body-botica">
                <div class="botica-header mb-4 flex flex-wrap gap-3 items-center justify-between">
                    <div class="flex gap-3 flex-1 items-center">
                        
                        <div class="ml-2" style="flex:1;max-width:340px;">
                            <div class="botica-search" style="width:100%;">
                                <iconify-icon icon="ion:search-outline" class="search-icon"></iconify-icon>
                                <input type="text" id="buscarProductoBotica" placeholder="Buscar productos..." class="search-input" style="width:100%;">
                                <button type="button" id="clearBuscarProductoBotica" class="search-clear" title="Limpiar" style="display:none;">×</button>
                            </div>
                        </div>
                        <div class="ml-2">
                            <label class="registros-label" for="mostrarBotica">Mostrar</label>
                            <select id="mostrarBotica" class="registros-select">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="ml-2">
                            <label class="registros-label" for="estadoBotica">Estado</label>
                            <select id="estadoBotica" class="registros-select">
                                <option value="todos" selected>Todos</option>
                                <option value="Normal">Normal</option>
                                <option value="Bajo stock">Bajo stock</option>
                                <option value="Por vencer">Por Vencer</option>
                                <option value="Vencido">Vencido</option>
                                <option value="Agotado">Agotado</option>
                            </select>
                        </div>
                        
                        <div class="ml-2 export-dropdown-container">
                            <button type="button" id="btnExportarBotica" class="btn-exportar-dropdown">
                                <iconify-icon icon="solar:export-bold-duotone"></iconify-icon>
                                <span>Descargar</span>
                            </button>
                            <div class="exportar-dropdown-menu hidden" id="exportarDropdownMenuBotica">
                                <button type="button" class="exportar-option excel" id="btnExportarExcelBotica">
                                    <iconify-icon icon="vscode-icons:file-type-excel"></iconify-icon>
                                    <span>Excel</span>
                                </button>
                                <button type="button" class="exportar-option pdf" id="btnExportarPDFBotica">
                                    <iconify-icon icon="vscode-icons:file-type-pdf2"></iconify-icon>
                                    <span>PDF</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" id="btnAgregarProducto" class="btn-ir-agregar flex items-center gap-2 ml-auto">
                        <iconify-icon icon="ic:round-add-circle" class="text-xl"></iconify-icon>
                        <span> Agregar Producto</span>
                    </button>
                </div>

                <div id="activeFilters" class="active-filters-container"></div>

                <div class="table-scroll">
                    <table class="tabla-productos-botica">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Presentaciones</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Lotes (FEFO)</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="productos-botica-tbody"></tbody>
                    </table>
                    <div id="productosBoticaSkeleton" class="skeleton-table" style="display:none;"></div>
                </div>
                <div id="productos-botica-pagination" class="historial-pagination-improved">
                    <div class="historial-pagination-info">
                        <p class="text-sm text-gray-700" id="productos-botica-pagination-info">Mostrando 0 a 0 de 0 productos</p>
                    </div>
                    <div class="historial-pagination-controls" id="productos-botica-pagination-controls"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  function createErrorElement(message) {
    const el = document.createElement('div');
    el.className = 'field-error';
    el.innerHTML = `<iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>${message}</span>`;
    return el;
  }

  function setupFormValidation(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    const fields = form.querySelectorAll('input[required], select[required], textarea[required]');

    fields.forEach(field => {
      const fieldName = field.getAttribute('name');
      let error = field.parentNode.querySelector('.field-error');
      
      if (!error) {
        error = createErrorElement('Este campo es obligatorio');
        field.parentNode.appendChild(error);
      }

      const validateField = () => {
        let isValid = true;
        let errorMsg = '';
        const value = field.value.trim();

        if (field.hasAttribute('required')) {
          const isEmpty = field.tagName.toLowerCase() === 'select' ? !field.value : !value;
          if (isEmpty) {
            isValid = false;
            errorMsg = field.tagName.toLowerCase() === 'select' ? 'Selecciona una opción' : 'Este campo es obligatorio';
          }
        }

        if (value && isValid) {

          if (fieldName === 'codigo_barras') {
            if (!/^\d+$/.test(value)) {
              isValid = false;
              errorMsg = 'Solo se permiten números';
            } else if (value.length !== 13) {
              isValid = false;
              errorMsg = 'Debe tener exactamente 13 dígitos';
            }
          }

          if (fieldName === 'stock_actual' || fieldName === 'stock_minimo') {
            if (!/^\d+$/.test(value)) {
              isValid = false;
              errorMsg = 'Solo se permiten números enteros';
            } else if (parseInt(value) < 0) {
              isValid = false;
              errorMsg = 'Debe ser mayor o igual a 0';
            }
          }

          if (fieldName === 'precio_compra' || fieldName === 'precio_venta') {
            if (!/^\d+(\.\d{1,2})?$/.test(value)) {
              isValid = false;
              errorMsg = 'Formato: 0.00 (máximo 2 decimales)';
            } else if (parseFloat(value) < 0) {
              isValid = false;
              errorMsg = 'Debe ser mayor o igual a 0';
            }
          }

          if (fieldName === 'precio_venta' && form.querySelector('[name="precio_compra"]')) {
            const precioCompra = parseFloat(form.querySelector('[name="precio_compra"]').value || 0);
            const precioVenta = parseFloat(value || 0);
            if (precioVenta > 0 && precioCompra > 0 && precioVenta <= precioCompra) {
              isValid = false;
              errorMsg = 'Debe ser mayor al precio de compra';
            }
          }
        }

        if (!isValid) {
          error.querySelector('span').textContent = errorMsg;
          error.classList.add('visible');
          field.classList.add('is-invalid');
        } else {
          error.classList.remove('visible');
          field.classList.remove('is-invalid');
        }
        
        return isValid;
      };

      field.addEventListener('input', validateField);
      field.addEventListener('change', validateField);
      field.addEventListener('blur', validateField);
    });

    form.addEventListener('submit', (e) => {
      let firstInvalid = null;
      const fields = form.querySelectorAll('input[required], select[required], textarea[required]');
      fields.forEach(field => {
        const error = field.nextElementSibling && field.nextElementSibling.classList && field.nextElementSibling.classList.contains('field-error')
          ? field.nextElementSibling
          : null;
        const empty = (field.tagName.toLowerCase() === 'select') ? !field.value : !field.value.trim();
        if (empty) {
          if (error) error.classList.add('visible');
          field.classList.add('is-invalid');
          if (!firstInvalid) firstInvalid = field;
        }
      });
      if (firstInvalid) {
        e.preventDefault();
        firstInvalid.focus();
      }
    });
  }

  setupFormValidation('formAgregarProducto');
  setupFormValidation('formEditarProducto');

  function removeLegacyHelperTexts() {
    const selectors = ['#modalAgregar .text-gray-500', '#modalEditar .text-gray-500'];
    selectors.forEach(sel => {
      document.querySelectorAll(sel).forEach(el => {
        const txt = (el.textContent || '').trim().toLowerCase();
        if (txt === 'el nombre del producto es obligatorio' || txt === 'este campo es obligatorio') {
          el.remove();
        }
      });
    });
  }

  removeLegacyHelperTexts();
});
</script>

<div id="modalDetallesBotica" class="modal-overlay fixed inset-0 hidden items-center justify-center z-[1050]">
    <div class="modal-container bg-white mx-auto rounded-lg shadow-lg z-[1050] overflow-y-auto" style="width:78vw; max-width:980px; max-height:96vh; height:auto; display:flex; flex-direction:column; overflow-y:hidden;">
        <div class="modal-header">
            <h3 class="text-xl font-semibold flex items-center gap-3">
                <iconify-icon icon="iconamoon:eye-light"></iconify-icon>
                Detalles del Producto
            </h3>
            <div class="flex items-center gap-3">
                <button class="modal-close text-2xl font-bold" id="cerrarModalBotica">&times;</button>
            </div>
        </div>
        <div class="modal-content px-6 pt-6 pb-6 overflow-auto" style="flex:1 1 auto;">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <div class="lg:col-span-4">
                    <div class="image-upload-wrapper">
                        <p class="upload-text">Imagen del producto</p>
                        <div id="det-preview-container" class="mt-4 text-center">
                            <img id="det-preview-image" class="h-40 w-40 object-cover rounded-lg inline-block" src="/assets/images/default-product.svg" alt="Vista previa">
                        </div>
                    </div>
                    <div class="mt-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto</label>
                            <input type="text" id="det-nombre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                                <input type="text" id="det-marca" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Concentración</label>
                                <input type="text" id="det-concentracion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                            <input type="text" id="det-categoria" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                        </div>
                    </div>
                </div>

                
                <div class="lg:col-span-8">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras</label>
                            <input type="text" id="det-codigo_barras" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                            <p class="text-xs text-gray-500 mt-1">Ingrese 13 dígitos (EAN13).</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                            <input type="text" id="det-proveedor" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                        </div>
                    </div>

                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual</label>
                            <input type="text" id="det-stock_actual" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                            <input type="text" id="det-stock_minimo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precio Compra</label>
                            <input type="text" id="det-precio_compra" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                        </div>
                            <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Precio Venta</label>
                            <input type="text" id="det-precio_venta" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                        </div>
                    </div>

                    
                    <div class="mt-6 border-t pt-4">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                                <iconify-icon icon="solar:box-minimalistic-bold-duotone" class="text-indigo-600"></iconify-icon>
                                Presentaciones del Producto
                            </h4>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-500 uppercase">Lote:</span>
                                <select id="det-lote-selector" class="text-xs font-bold py-1 px-3 border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-indigo-50 text-indigo-700">
                                    <!-- Populated dynamically -->
                                </select>
                            </div>
                        </div>
                        <div id="det-presentaciones-list" class="space-y-2">
                            <div class="text-sm text-gray-500 italic text-center py-2">Cargando presentaciones...</div>
                        </div>
                    </div>

                    
                    <div class="mt-6 border-t pt-4">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                                <iconify-icon icon="solar:box-minimalistic-bold-duotone" class="text-blue-600"></iconify-icon>
                                Lotes Activos (FEFO)
                            </h4>
                        </div>
                        <div id="det-lotes-list" class="space-y-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar border rounded-lg p-2 bg-gray-50">
                            <div class="text-sm text-gray-500 italic text-center py-2">Cargando lotes...</div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<div id="modalSelectorLotes" class="modal-overlay fixed inset-0 hidden items-center justify-center z-[1060]">
    <div class="modal-container bg-white mx-auto rounded-2xl shadow-2xl z-[1060] overflow-hidden w-full" style="max-width: 900px; width: 95%;">
        <div class="modal-header px-6 py-5 border-b flex justify-between items-center" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <div class="flex items-center gap-3">
                <iconify-icon icon="lucide:package" class="text-white text-2xl"></iconify-icon>
                <div>
                    <h3 class="text-xl font-bold text-white" id="modalSelectorLotesTitle">Seleccionar Lote para Detalles</h3>
                </div>
            </div>
            <button class="text-white hover:text-gray-200 transition-colors text-3xl leading-none" id="cerrarModalSelectorLotes">&times;</button>
        </div>
        
        <div class="p-6">
            <div class="mb-4 p-4 bg-gray-50 rounded-xl border border-gray-100 flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">PRODUCTO SELECCIONADO</p>
                    <h4 class="text-xl font-extrabold text-gray-900" id="selectorProductoNombre">-</h4>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">GESTOR DE INVENTARIO</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">FEFO (First Expired, First Out)</span>
                </div>
            </div>
            
            <div class="overflow-x-auto border rounded-xl" style="max-height: 60vh; overflow-y: auto;">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 border-b sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-4 text-left font-bold text-gray-700 uppercase" style="font-size: 0.75rem;">LOTE</th>
                            <th class="px-4 py-4 text-left font-bold text-gray-700 uppercase" style="font-size: 0.75rem;">VENCIMIENTO</th>
                            <th class="px-4 py-4 text-center font-bold text-gray-700 uppercase" style="font-size: 0.75rem;">CANTIDAD</th>
                            <th class="px-4 py-4 text-center font-bold text-gray-700 uppercase" style="font-size: 0.75rem;">P. COMPRA</th>
                            <th class="px-4 py-4 text-center font-bold text-gray-700 uppercase" style="font-size: 0.75rem;">P. VENTA</th>
                        </tr>
                    </thead>
                    <tbody id="selectorLotesBody" class="divide-y divide-gray-100">
                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="modalLote" class="modal-overlay fixed inset-0 hidden items-center justify-center z-[1060]">
    <div class="modal-container bg-white mx-auto rounded-lg shadow-lg z-[1060] overflow-hidden w-full max-w-md">
        <div class="modal-header bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800" id="modalLoteTitle">Nuevo Lote</h3>
            <button class="text-gray-400 hover:text-gray-600 transition-colors text-2xl leading-none" id="cerrarModalLote">&times;</button>
        </div>
        <form id="formLote" class="p-6">
            <input type="hidden" id="loteId">
            <input type="hidden" id="loteProductoId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código de Lote <span class="text-red-500">*</span></label>
                    <input type="text" id="loteCodigo" name="lote" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Vencimiento <span class="text-red-500">*</span></label>
                    <input type="date" id="loteVencimiento" name="fecha_vencimiento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad Inicial <span class="text-red-500">*</span></label>
                    <input type="number" id="loteCantidad" name="cantidad" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" min="1" required>
                </div>

            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors" id="cancelarLote">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                    <iconify-icon icon="solar:disk-bold-duotone"></iconify-icon>
                    <span>Guardar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>

.tabla-categorias td { vertical-align: middle; }
.estado-cell { text-align: center; }
.tabla-categorias td[data-label="Acciones"] { display: flex; align-items: center; gap: 12px; }
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; margin-left: 8px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e5e7eb; transition: .2s; border-radius: 999px; }
.toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .2s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.toggle-switch input:checked + .toggle-slider { background-color: #10b981; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }
</style>

<style>

.search-group { display:flex; align-items:center; gap:.5rem; border:1px solid #E9EDF5; border-radius:12px; padding:0 .85rem; height:44px; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,0.035); }
.search-group:focus-within { border-color:#8b5cf6; box-shadow:0 0 0 2px rgba(139,92,246,.35); }
.search-icon { color:#64748b; font-size:18px; }
.search-input { flex:1; border:none; outline:none; background:transparent; font-size:14px; padding:0; height:100%; }
.search-input:focus { outline:none !important; box-shadow:none !important; }
.search-clear { display:none; border:none; background:transparent; color:#9ca3af; cursor:pointer; }
.registros-por-pagina-select { border:1px solid #E9EDF5; border-radius:12px; background:#fff; padding:.25rem .75rem; min-height:44px; box-shadow:0 1px 2px rgba(0,0,0,0.035); }
.registros-por-pagina-select:focus { outline:none; border-color:#8b5cf6; box-shadow:0 0 0 2px rgba(139,92,246,.35); }
.filtro-estado-group .registros-por-pagina-select { border:1px solid #E9EDF5; }
.estado-badge { display:inline-flex; align-items:center; gap:.4rem; padding:.25rem .6rem; border-radius:9999px; font-weight:600; font-size:.85rem; }
.estado-badge .dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
.estado-badge-activo { background:#d1fae5; color:#059669; }
.estado-badge-activo .dot { background:#22c55e; }
.estado-badge-inactivo { background:#fee2e2; color:#dc2626; }
.estado-badge-inactivo .dot { background:#ef4444; }
.desc-with-icon { display:inline-flex; align-items:center; gap:.4rem; color:#374151; }
.desc-icon { color:#64748b; font-size:18px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const buscarInput = document.getElementById('buscarCategoria');
    const clearBtn = document.getElementById('clearBuscarCategoria');
    if (buscarInput && clearBtn) {
        const toggleClear = () => { clearBtn.style.display = buscarInput.value ? 'inline-flex' : 'none'; };
        buscarInput.addEventListener('input', toggleClear);
        clearBtn.addEventListener('click', () => { buscarInput.value=''; toggleClear(); buscarInput.dispatchEvent(new Event('input')); });
        toggleClear();
    }
    document.querySelectorAll('.estado-toggle').forEach(chk => {
        chk.addEventListener('change', async () => {
            const id = chk.getAttribute('data-id');
            try {
                const resp = await fetch(`{{ route('inventario.categorias.cambiar-estado', ['id' => 'ID_PLACEHOLDER']) }}`.replace('ID_PLACEHOLDER', id), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await resp.json();
                if (data.success) {
                    const row = chk.closest('tr');
                    const estadoCell = row.querySelector('td.estado-cell');
                    estadoCell.innerHTML = data.activo
                        ? '<iconify-icon icon="solar:check-circle-bold" class="estado-icon" style="color:#22c55e;font-size:26px;" title="Activo"></iconify-icon>'
                        : '<iconify-icon icon="solar:close-circle-bold" class="estado-icon" style="color:#ef4444;font-size:26px;" title="Inactivo"></iconify-icon>';
                }
            } catch (e) {
                console.error(e);
                alert('No se pudo cambiar el estado');
            }
        });
    });

    window.verificarHistorialProducto = async function(productoId) {
        const btnHistorial = document.getElementById('btnVerHistorial');
        if (!productoId || !btnHistorial) return;
        
        try {
            const response = await fetch(`/api/productos/${productoId}/historial`);
            const data = await response.json();
            
            if (data.success && data.historial && data.historial.length > 0) {
                btnHistorial.classList.remove('hidden');
            } else {
                btnHistorial.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error al verificar historial:', error);
            btnHistorial.classList.add('hidden');
        }
    }

    document.addEventListener('click', function(e) {
        if (e.target.closest('#btnVerHistorial')) {
            const productoNombre = document.getElementById('det-nombre')?.value || '';
            if (productoNombre) {

                window.location.href = `/admin/logs?search=${encodeURIComponent(productoNombre)}`;
            }
        }
    })
});
</script>

<style>
.loading-overlay { position: fixed; inset: 0; background: rgba(255,255,255,0.6); backdrop-filter: blur(2px); display: none; align-items: center; justify-content: center; z-index: 9999; }
.loading-overlay .loading-spinner {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  position: relative;
  
  background: conic-gradient(#f87171 0 180deg, transparent 180deg 360deg);
  
  -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 0);
  mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 0);
  animation: spin 0.7s linear infinite;
}
.loading-overlay .loading-spinner::after {
  content: "";
  position: absolute;
  inset: 50% auto auto 50%;
  transform: translate(-50%, -50%);
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #f87171; 
  box-shadow: 0 0 0 4px #fde2e2; 
}
.loading-overlay .loading-text { margin-top: .6rem; color: #f87171; font-weight: 400; font-size: 20px; text-shadow: none; }
.loading-overlay .inner { display: flex; flex-direction: column; align-items: center; justify-content: center; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
@include('components.loading-overlay', [
    'id' => 'loadingOverlay',
    'size' => 36,
    'inner' => 14,
    'color' => '#f87171',
    'textColor' => '#f87171',
    'textSize' => 20,
    'label' => 'Cargando datos...'
])

<script>

window.APP_PRODUCTS_AJAX = '{{ route('inventario.productos.ajax') }}';
window.APP_DEFAULT_IMAGE = '{{ asset('assets/images/default-product.svg') }}';

</script>
<script src="{{ asset('assets/js/inventario/agregar.js') }}?v={{ time() }}" defer></script>
<script src="{{ asset('assets/js/inventario/productos_botica.js') }}?v={{ time() }}" defer></script>
<style>

.historial-pagination-improved { padding: 1.5rem 2rem; border-top: 1px solid #e5e7eb; display:flex; justify-content:between; align-items:center; gap:1rem; background:white; }
.historial-pagination-info { flex:1; }
.historial-pagination-controls { display:flex; align-items:center; gap:.5rem; }
.historial-pagination-btn { padding:.5rem .75rem; font-size:.875rem; font-weight:500; color:#374151; background:white; border:1px solid #d1d5db; border-radius:6px; text-decoration:none; transition:all .2s ease; display:inline-flex; align-items:center; justify-content:center; min-width:2.5rem; }
.historial-pagination-btn:hover { background:#f9fafb; border-color:#9ca3af; color:#374151; }
.historial-pagination-btn-current { background:#e53e3e !important; border-color:#e53e3e !important; color:white !important; }
.historial-pagination-btn-disabled { padding:.5rem .75rem; font-size:.875rem; font-weight:500; color:#9ca3af; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; cursor:not-allowed; display:inline-flex; align-items:center; justify-content:center; min-width:2.5rem; }
@media (max-width:768px){ .historial-pagination-improved{ flex-direction:column; align-items:stretch; gap:1rem; } .historial-pagination-controls{ justify-content:center; flex-wrap:wrap; } }

.skeleton-table { padding: 8px 0; }
.skeleton-row { display:grid; grid-template-columns: 64px 1fr 2fr 140px 170px; gap: 16px; align-items:center; padding: 14px 10px; border-bottom: 1px solid #f1f5f9; }
.skeleton-bar { display:block; height: 16px; border-radius: 8px; background: linear-gradient(90deg, #e5e7eb 25%, #f1f5f9 37%, #e5e7eb 63%); background-size: 400% 100%; animation: skeleton-shimmer 1.2s ease-in-out infinite; }
.skeleton-bar.short { width: 48px; }
.skeleton-bar.medium { width: 220px; }
.skeleton-bar.long { width: 100%; }
.skeleton-bar.actions { width: 120px; height: 14px; }
.skeleton-dot { width: 16px; height: 16px; border-radius: 50%; background: linear-gradient(90deg, #e5e7eb 25%, #f1f5f9 37%, #e5e7eb 63%); background-size: 400% 100%; animation: skeleton-shimmer 1.2s ease-in-out infinite; justify-self:start; }
@keyframes skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
@media (prefers-reduced-motion: reduce) { .skeleton-bar, .skeleton-dot { animation: none; } }

.modal-header { color: #ffffff !important; }
.modal-header h3 { color: #ffffff !important; }
.modal-header iconify-icon { color: #ffffff !important; }
.modal-header .modal-close, .modal-close-edit { color: #ffffff !important; }

#modalAgregar .modal-header {
  background: linear-gradient(90deg, #7c3aed, #6d28d9);
  padding: 16px;
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}
#modalEditar .modal-header {
  background: linear-gradient(90deg, #10b981, #059669);
  padding: 16px;
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}

#modalDetallesBotica .modal-header {
  background: linear-gradient(90deg, #93c5fd, #3b82f6);
  color: #ffffff;
  padding: 16px;
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}

#modalAgregar select, #modalEditar select {
  -webkit-appearance: none; 
  -moz-appearance: none;    
  appearance: none;         
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='22' height='22' viewBox='0 0 24 24' fill='none' stroke='%23111827' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 22px 22px;
  padding-right: 3rem; 
}

select::-ms-expand { display: none; }

.select-wrapper { position: relative; }
.select-wrapper select { padding-right: 2.75rem; }
.select-wrapper::after {
  content: '';
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 18px;
  pointer-events: none;
  background-repeat: no-repeat;
  background-size: 18px 18px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23111827' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
}

:root {
  --input-radius: 10px;
  --input-bg: #f9fafb;
  --input-border: #d1d5db;
  --input-text: #111827;
  --input-placeholder: #9ca3af;
  --focus-color: #7c3aed; 
  --focus-shadow: rgba(124, 58, 237, 0.25);
}

#modalAgregar label, #modalEditar label { font-weight: 600; color: #374151; letter-spacing: 0.01em; }

#modalAgregar input[type="text"],
#modalAgregar input[type="number"],
#modalAgregar input[type="date"],
#modalAgregar select,
#modalAgregar textarea,
#modalEditar input[type="text"],
#modalEditar input[type="number"],
#modalEditar input[type="date"],
#modalEditar select,
#modalEditar textarea,
#modalDetallesBotica input[type="text"],
#modalDetallesBotica input[type="number"],
#modalDetallesBotica input[type="date"],
#modalDetallesBotica select,
#modalDetallesBotica textarea {
  background: var(--input-bg);
  border: 1px solid var(--input-border);
  border-radius: var(--input-radius);
  color: var(--input-text);
  transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
}

#modalAgregar input[type="text"],
#modalAgregar input[type="number"],
#modalAgregar input[type="date"],
#modalAgregar select,
#modalEditar input[type="text"],
#modalEditar input[type="number"],
#modalEditar input[type="date"],
#modalEditar select,
#modalDetallesBotica input[type="text"],
#modalDetallesBotica input[type="number"],
#modalDetallesBotica input[type="date"],
#modalDetallesBotica select { height: 44px; }

#modalAgregar textarea, #modalEditar textarea, #modalDetallesBotica textarea { min-height: 110px; }

#modalAgregar input::placeholder,
#modalAgregar textarea::placeholder,
#modalAgregar select::placeholder,
#modalEditar input::placeholder,
#modalEditar textarea::placeholder,
#modalEditar select::placeholder,
#modalDetallesBotica input::placeholder,
#modalDetallesBotica textarea::placeholder,
#modalDetallesBotica select::placeholder { color: var(--input-placeholder); }

#modalAgregar input:hover, #modalAgregar select:hover, #modalAgregar textarea:hover,
#modalEditar input:hover, #modalEditar select:hover, #modalEditar textarea:hover,
#modalDetallesBotica input:hover, #modalDetallesBotica select:hover, #modalDetallesBotica textarea:hover {
  background: #ffffff;
  border-color: #cfd8e3;
}

#modalAgregar input:focus, #modalAgregar select:focus, #modalAgregar textarea:focus,
#modalEditar input:focus, #modalEditar select:focus, #modalEditar textarea:focus,
#modalDetallesBotica input:focus, #modalDetallesBotica select:focus, #modalDetallesBotica textarea:focus {
  outline: none;
  border-color: var(--focus-color);
  box-shadow: 0 0 0 2px var(--focus-shadow);
  background: #ffffff;
}

#modalDetallesBotica input[readonly] {
  background: #f8fafc;
  border-color: #e5e7eb;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  border-radius: 9999px;
  padding: 0.6rem 1rem;
}
#modalDetallesBotica .image-upload-wrapper {
  border: 2px dashed #e5e7eb;
  border-radius: 12px;
  padding: 1.25rem;
  min-height: 180px;
  background: linear-gradient(135deg, #f8fafc, #eef2ff);
  display: flex; align-items: center; justify-content: center; flex-direction: column;
}
#modalDetallesBotica .upload-text { color: #475569; font-weight: 500; }

body.modal-open { overflow: hidden; }

#modalAgregar .relative > span:first-child,
#modalEditar .relative > span:first-child {
  color: #374151;
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 0;
  font-weight: 600;
}

.btn-save, .btn-save-edit {
  border-radius: 10px;
  box-shadow: 0 6px 12px rgba(79, 70, 229, 0.18);
  transition: transform .12s ease, box-shadow .12s ease;
}
.btn-save:hover, .btn-save-edit:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(79, 70, 229, 0.25); }
.btn-cancel, .btn-cancel-edit { border-radius: 10px; transition: transform .12s ease, box-shadow .12s ease; }
.btn-cancel:hover, .btn-cancel-edit:hover { transform: translateY(-1px); box-shadow: 0 6px 12px rgba(0,0,0,0.06); }

.is-invalid { border-color: #ef4444 !important; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25) !important; }
.is-disabled, [disabled] { background: #f3f4f6 !important; color: #9ca3af !important; cursor: not-allowed; }

.field-error { display: none; color: #dc2626; font-size: 0.875rem; margin-top: .25rem; align-items: center; gap: .375rem; }
.field-error.visible { display: flex; }
.field-error iconify-icon { color: #dc2626; font-size: 1rem; }
</style>

<div id="modalAgregar" class="modal-overlay fixed inset-0 hidden items-center justify-center z-[1050]">
    <div class="modal-container bg-white mx-auto rounded-lg shadow-lg z-[1050] overflow-y-auto" style="width:78vw; max-width:980px; max-height:96vh; height:auto; display:flex; flex-direction:column; overflow-y:hidden;">
        <div class="modal-header">
            <h3 class="text-xl font-semibold flex items-center gap-3">
                <iconify-icon icon="heroicons:plus-circle-solid"></iconify-icon>
                Agregar Nuevo Producto
            </h3>
            <button class="modal-close text-2xl font-bold" id="closeAgregar">&times;</button>
        </div>

        <div class="modal-content px-6 pt-6 pb-6 overflow-auto" style="flex:1 1 auto;">
            <form id="formAgregarProducto" class="space-y-4" enctype="multipart/form-data" novalidate>
                @csrf
                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto <span class="text-red-500">*</span></label>
                        <input type="text" id="nombreProducto" name="nombre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Paracetamol 500mg" required>
                        <div id="error-nombre-add" class="field-error">
                            <iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon>
                            <span>El nombre del producto es obligatorio</span>
                        </div>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca <span class="text-red-500">*</span></label>
                        <input type="text" name="marca" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Genfar" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Este campo es obligatorio</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                        <div class="select-wrapper mt-1">
                            <select name="categoria" id="add-categoria" class="block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Seleccionar</option>
                            </select>
                        </div>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Selecciona una categoría</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Concentración <span class="text-red-500">*</span></label>
                        <input type="text" name="concentracion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: 500mg" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Este campo es obligatorio</span></div>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lote <span class="text-red-500">*</span></label>
                        <input type="text" name="lote" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Este campo es obligatorio</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras <span class="text-red-500">*</span></label>
                        <input type="text" name="codigo_barras" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" maxlength="13" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Debe tener 13 dígitos</span></div>
                        <p class="text-xs text-gray-500 mt-1">Ingrese 13 dígitos (EAN13).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <div class="select-wrapper mt-1">
                            <select name="proveedor_id" id="add-proveedor" class="block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Seleccionar</option>
                            </select>
                        </div>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Selecciona un proveedor</span></div>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual <span class="text-red-500">*</span></label>
                        <input type="number" name="stock_actual" id="stock_actual" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Debe ser un número ≥ 0</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo <span class="text-red-500">*</span></label>
                        <input type="number" name="stock_minimo" id="stock_minimo" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Debe ser un número ≥ 0</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Precio Compra Unitario <span class="text-red-500">*</span>
                            <span class="text-xs text-blue-600">(por unidad)</span>
                        </label>
                        <input type="number" name="precio_compra" id="precio_compra_base" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="0.00" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Debe ser un número ≥ 0</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Precio Venta Unitario <span class="text-red-500">*</span>
                            <span class="text-xs text-blue-600">(por unidad)</span>
                        </label>
                        <input type="number" name="precio_venta" id="precio_venta_base" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="0.00" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Debe ser mayor al precio de compra</span></div>
                    </div>
                </div>

                
                <div class="presentaciones-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin-bottom: 0.25rem;">
                                <i class="fas fa-boxes"></i>
                                Presentaciones del Producto
                            </h3>
                            <p style="margin: 0;">La primera presentación "Unidad" usa los precios de arriba. Agrega más presentaciones si vendes en Blíster, Caja, etc.</p>
                        </div>
                        <button type="button" id="btn-agregar-presentacion" class="btn-agregar-presentacion">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>

                    <div class="presentaciones-table-wrapper">
                        <table class="presentaciones-table">
                            <thead>
                                <tr>
                                    <th>Nombre Presentación</th>
                                    <th style="text-align: center;">Unidades</th>
                                    <th style="text-align: center;">Precio Venta</th>
                                    <th style="text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="presentaciones-table-body">
                                
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha Vencimiento <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200" required>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle text-blue-500"></i>
                            Fecha de vencimiento del lote
                        </p>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>La fecha de vencimiento es obligatoria</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Imagen del Producto</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition-colors cursor-pointer bg-gray-50" onclick="document.getElementById('imagen-input').click()">
                            <input type="file" name="imagen" accept="image/*" id="imagen-input" class="hidden">
                            <div id="upload-placeholder">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-600 font-medium">Haz clic para subir</p>
                                <p class="text-xs text-gray-500 mt-1">PNG, JPG o GIF (Max. 2MB)</p>
                            </div>
                            <div id="preview-container" class="hidden">
                                <img id="preview-image" class="h-32 w-32 object-cover rounded-lg mx-auto" src="" alt="Vista previa">
                                <p class="text-xs text-gray-600 mt-2">Click para cambiar</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions mt-6 border-t border-gray-200 flex justify-end gap-4 pt-4">
                    <button type="button" class="btn-cancel px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors border border-gray-300" id="btnCancelarAgregar">Cancelar</button>
                    <button type="submit" class="btn-save px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
 </div>

<div id="modal-presentacion" class="modal-presentacion-overlay hidden">
    <div class="modal-presentacion-container">
        <div class="modal-presentacion-header">
            <h3 id="modal-presentacion-title">Agregar Presentación</h3>
            <button type="button" id="btn-cerrar-modal-presentacion">&times;</button>
        </div>
        <form id="form-presentacion">
            <div class="modal-presentacion-body">
                <div class="form-group-modal">
                    <label for="nombre_presentacion_modal">Nombre Presentación <span style="color: red;">*</span></label>
                    <input type="text" id="nombre_presentacion_modal" placeholder="Ej: Blíster, Caja, Tira" required>
                    <p class="text-xs text-gray-500 mt-1">Nombre de esta forma de venta (no incluyas "Unidad", esa ya existe)</p>
                </div>
                <div class="form-group-modal">
                    <label for="unidades_presentacion_modal">¿Cuántas unidades contiene? <span style="color: red;">*</span></label>
                    <input type="number" id="unidades_presentacion_modal" min="2" placeholder="Ej: 10, 20, 50" required>
                    <p class="text-xs text-gray-500 mt-1">Ejemplo: Si un blíster tiene 10 tabletas, pon 10</p>
                </div>
                
                
                <div id="precio-calculado-info" style="display: none;" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-3">
                    <div class="flex-1">
                        <p class="font-semibold text-sm text-blue-900 mb-2">Cálculo Automático:</p>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-700">Costo de esta presentación:</span>
                                <span id="costo-calculado" class="font-semibold text-gray-900">S/ 0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-700">Precio sugerido:</span>
                                <span id="precio-sugerido" class="font-semibold text-blue-600">S/ 0.00</span>
                            </div>
                            <div class="flex justify-between pt-1 border-t border-blue-200">
                                <span class="text-gray-700">Tu ganancia sería:</span>
                                <span id="ganancia-calculada" class="font-semibold text-green-600">S/ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group-modal">
                    <label for="precio_venta_modal">Precio de Venta <span style="color: red;">*</span></label>
                    <input type="number" id="precio_venta_modal" step="0.01" min="0" placeholder="0.00" required>
                    <p class="text-xs text-gray-500 mt-1">El sistema calcula automáticamente, pero puedes ajustarlo si das descuento</p>
                </div>
            </div>
            <div class="modal-presentacion-footer">
                <button type="button" id="btn-cancelar-modal-presentacion" class="btn-modal-cancelar">Cancelar</button>
                <button type="submit" class="btn-modal-guardar">
                    <i class="fas fa-save mr-1"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>

document.getElementById('imagen-input')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
            document.getElementById('upload-placeholder').classList.add('hidden');
            document.getElementById('preview-container').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
});
</script>

<div id="modalEditar" class="modal-overlay fixed inset-0 hidden items-center justify-center z-[1050]">
    <div class="modal-container bg-white mx-auto rounded-lg shadow-lg z-[1050] overflow-y-auto" style="width:76vw; max-width:960px; max-height:96vh; height:auto; display:flex; flex-direction:column; overflow-y:hidden;">
        <div class="modal-header">
            <h3 class="text-xl font-semibold flex items-center gap-3">
                <iconify-icon icon="lucide:edit"></iconify-icon>
                Editar Producto
            </h3>
            <button class="modal-close-edit text-2xl font-bold" id="closeEditar">&times;</button>
        </div>
        <div class="modal-content px-6 pt-6 pb-6 overflow-auto" style="flex:1 1 auto;">
            <form id="formEditarProducto" class="space-y-4" enctype="multipart/form-data" novalidate>
                <input type="hidden" id="edit-producto-id" name="producto_id">

                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" id="edit-nombre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Paracetamol 500mg" required autocomplete="off">
                        <div id="error-nombre-edit" class="field-error">
                            <iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon>
                            <span>El nombre del producto es obligatorio</span>
                        </div>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca <span class="text-red-500">*</span></label>
                        <input type="text" name="marca" id="edit-marca" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Genfar" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Este campo es obligatorio</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                        <div class="select-wrapper mt-1">
                            <select name="categoria" id="edit-categoria" class="block w-full rounded-md border-gray-300 shadow-sm" required></select>
                        </div>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Selecciona una categoría</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Concentración <span class="text-red-500">*</span></label>
                        <input type="text" name="concentracion" id="edit-concentracion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: 500mg" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Este campo es obligatorio</span></div>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lote <span class="text-red-500">*</span></label>
                        <input type="text" name="lote" id="edit-lote" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Este campo es obligatorio</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras <span class="text-red-500">*</span></label>
                        <input type="text" name="codigo_barras" id="edit-codigo_barras" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" maxlength="13" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Debe tener 13 dígitos</span></div>
                        <p class="text-xs text-gray-500 mt-1">Ingrese 13 dígitos (EAN13).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <div class="select-wrapper mt-1">
                            <select name="proveedor_id" id="edit-proveedor" class="block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Seleccionar</option>
                            </select>
                        </div>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Selecciona un proveedor</span></div>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual <span class="text-red-500">*</span></label>
                        <input type="number" name="stock_actual" id="edit-stock_actual" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Debe ser un número ≥ 0</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo <span class="text-red-500">*</span></label>
                        <input type="number" name="stock_minimo" id="edit-stock_minimo" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Debe ser un número ≥ 0</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Precio Compra Unitario <span class="text-red-500">*</span>
                            <span class="text-xs text-blue-600">(por unidad)</span>
                        </label>
                        <input type="text" name="precio_compra" id="precio_compra_base_edit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="0.00" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Debe ser un número ≥ 0</span></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Precio Venta Unitario <span class="text-red-500">*</span>
                            <span class="text-xs text-blue-600">(por unidad)</span>
                        </label>
                        <input type="text" name="precio_venta" id="precio_venta_base_edit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="0.00" required>
                        <div class="field-error"><iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon><span>Debe ser mayor al precio de compra</span></div>
                    </div>
                </div>

                
                <div id="presentaciones-section-editar" class="mt-6 bg-gradient-to-r from-indigo-50 to-purple-50 border-2 border-indigo-200 rounded-xl p-6 shadow-sm">
                    <input type="hidden" id="producto_id_hidden_edit" value="">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <i class="fas fa-boxes text-indigo-600 text-xl"></i>
                                Presentaciones del Producto
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">La primera presentación "Unidad" usa los precios de arriba. Agrega más presentaciones si vendes en Blíster, Caja, etc.</p>
                        </div>
                        <button type="button" id="btn-abrir-modal-presentacion-edit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                            <i class="fas fa-plus mr-2"></i> Agregar
                        </button>
                    </div>

                    <div class="presentaciones-table-wrapper">
                        <table class="presentaciones-table">
                            <thead>
                                <tr>
                                    <th>Nombre Presentación</th>
                                    <th style="text-align: center;">Unidades</th>
                                    <th style="text-align: center;">Precio Venta</th>
                                    <th style="text-align: center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="presentaciones-table-body-edit">
                                
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <div class="image-upload-wrapper">
                            <input type="file" name="imagen" accept="image/*" id="edit-imagen-input">
                            <p class="upload-text">Haz clic para subir una imagen</p>
                            <p class="upload-hint">PNG, JPG o GIF (Max. 2MB)</p>
                        </div>
                        <div id="edit-preview-container" class="mt-4 text-center" style="display: none;">
                            <img id="edit-preview-image" class="h-32 w-32 object-cover rounded-lg inline-block" src="" alt="Vista previa">
                            <p class="text-sm text-gray-500 mt-2">Imagen actual</p>
                        </div>
                    </div>
                </div>
                <div class="form-actions mt-6 border-t border-gray-200 flex justify-end gap-4 pt-4">
                    <button type="button" class="btn-cancel-edit px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors border border-gray-300" id="btnCancelarEditar">Cancelar</button>
                    <button type="submit" class="btn-save-edit px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

function seleccionarProductoInventario(producto) {

    const filas = document.querySelectorAll('.tabla-productos-botica tbody tr');
    
    filas.forEach(fila => {
        fila.classList.remove('highlight-producto');
        const nombreProducto = fila.querySelector('td:first-child')?.textContent?.trim();
        
        if (nombreProducto && nombreProducto.toLowerCase().includes(producto.nombre.toLowerCase())) {
            fila.classList.add('highlight-producto');

            fila.scrollIntoView({ behavior: 'smooth', block: 'center' });

            setTimeout(() => {
                fila.classList.remove('highlight-producto');
            }, 3000);
        }
    });
}
</script>

<style>
.highlight-producto {
    background-color: #fef3c7 !important;
    border: 2px solid #f59e0b !important;
    transition: all 0.3s ease;
}
</style>
@endsection
