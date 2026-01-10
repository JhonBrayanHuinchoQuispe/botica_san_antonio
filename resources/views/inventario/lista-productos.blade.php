@extends('layout.layout')
@php
    $title = 'Lista de Productos';
    $subTitle = 'Lista de Productos';
    $script = '<script src="' . asset('assets/js/inventario/categoria/lista.js') . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/categoria/agregar.js') . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/categoria/editar.js') . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/categoria/eliminar.js') . '"></script>';
@endphp

<head>
    <title>Lista de Productos</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logotipo.png') }}" sizes="16x16">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/productos_botica.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/crud.css') }}?v={{ time() }}">
    <!-- Reusar estilos de modales originales para mantener el look -->
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/modal_agregar.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/modal_editar.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/modal_ver.css') }}?v={{ time() }}">
    <!-- Reusar estilos de ubicaciones y tablas para badges idénticos -->
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/productos/tablas.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/ubicaciones-multiples.css') }}?v={{ time() }}">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Librerías para exportar (Excel y PDF) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <!-- Reusar lógica existente de eliminar y validaciones -->
    <script src="{{ asset('assets/js/inventario/eliminar.js') }}" defer></script>
<!-- removed legacy realtime validation script to prevent duplicate messages -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Estilos para Badges de Lotes */
        .lote-badge {
            display: flex;
            flex-direction: column;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            width: fit-content;
            min-width: 120px;
        }

        .lote-badge.empty {
            background-color: #f1f5f9;
            color: #64748b;
            align-items: center;
            flex-direction: row;
            gap: 6px;
        }

        .lote-badge.single {
            border: 1px solid #e2e8f0;
            background-color: #fff;
        }
        .lote-badge.single.expired {
            background-color: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
        }
        .lote-badge.single.warning {
            background-color: #fef3c7;
            border-color: #fde68a;
            color: #92400e;
        }
        .lote-badge.single.normal {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }

        .lote-badge.multiple {
            border: 1px solid #e2e8f0;
            background-color: #fff;
        }
        .lote-badge.multiple.expired {
            border-left: 4px solid #ef4444;
        }
        .lote-badge.multiple.warning {
            border-left: 4px solid #f59e0b;
        }
        .lote-badge.multiple.normal {
            border-left: 4px solid #22c55e;
        }

        .lote-main {
            display: flex;
            flex-direction: column;
        }
        .lote-code {
            font-weight: 600;
        }
        .lote-date {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        .lote-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
        }
        .lote-count {
            font-weight: 700;
        }
        .lote-fefo-label {
            font-size: 0.65rem;
            background: #0f172a;
            color: #fff;
            padding: 1px 4px;
            border-radius: 4px;
        }
        .lote-next {
            font-size: 0.75rem;
            color: #475569;
        }
    </style>
</head>

@section('content')
<div class="grid grid-cols-12">
    <div class="col-span-12">
        <div class="card border-0 overflow-hidden">
            
            <div class="card-body-botica">
                <div class="botica-header mb-6 flex flex-wrap gap-3 items-center justify-between">
                    <div class="flex gap-3 flex-1 items-center">
                        <!-- Búsqueda principal (única) -->
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
                        <!-- Descargas -->
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

                <div class="table-scroll">
                    <table class="tabla-productos-botica">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
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
      const message = field.getAttribute('data-error-message') || 'Este campo es obligatorio';
      // Insertar contenedor si no existe
      let error = field.nextElementSibling && field.nextElementSibling.classList && field.nextElementSibling.classList.contains('field-error')
        ? field.nextElementSibling
        : null;
      if (!error) {
        error = createErrorElement(message);
        field.parentNode.insertBefore(error, field.nextSibling);
      }

      const hideError = () => { error.classList.remove('visible'); field.classList.remove('is-invalid'); };
      const showErrorIfEmpty = () => {
        const empty = (field.tagName.toLowerCase() === 'select') ? !field.value : !field.value.trim();
        error.classList.toggle('visible', empty);
        field.classList.toggle('is-invalid', empty);
      };

      field.addEventListener('input', hideError);
      field.addEventListener('change', hideError);
      field.addEventListener('blur', showErrorIfEmpty);
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

  // Remover textos negros duplicados (ayudas antiguas)
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
<!-- Modal Ver Detalles (mismo diseño que Editar) -->
<div id="modalDetallesBotica" class="modal-overlay fixed inset-0 hidden items-center justify-center z-50" style="display:none;">
    <div class="modal-container bg-white mx-auto rounded-lg shadow-lg z-50 overflow-y-auto" style="width:78vw; max-width:980px; max-height:96vh; height:auto; display:flex; flex-direction:column; overflow-y:hidden;">
        <div class="modal-header">
            <h3 class="text-xl font-semibold flex items-center gap-3">
                <iconify-icon icon="iconamoon:eye-light"></iconify-icon>
                Detalles del Producto
            </h3>
            <div class="flex items-center gap-3">
                <button type="button" id="btnVerHistorial" class="hidden flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium">
                    <iconify-icon icon="solar:history-bold-duotone" class="text-lg"></iconify-icon>
                    Ver Historial de Cambios
                </button>
                <button class="modal-close text-2xl font-bold" id="cerrarModalBotica">&times;</button>
            </div>
        </div>
        <div class="modal-content px-6 pt-6 pb-6 overflow-auto" style="flex:1 1 auto;">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Columna izquierda: imagen y datos clave -->
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                                <input type="text" id="det-categoria" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Presentación</label>
                                <input type="text" id="det-presentacion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: resto de información -->
                <div class="lg:col-span-8">
                    <!-- Lote, Código, Proveedor -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lote</label>
                            <input type="text" id="det-lote" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                        </div>
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

                    <!-- Stock y Precios -->
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

                    <!-- Fechas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fabricación</label>
                            <input type="text" id="det-fecha_fabricacion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                            <input type="text" id="det-fecha_vencimiento" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" readonly>
                        </div>
                    </div>

                    <!-- Lotes Activos (FEFO) -->
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

<!-- Modal Agregar/Editar Lote -->
<div id="modalLote" class="modal-overlay fixed inset-0 hidden items-center justify-center z-[60]" style="display:none;">
    <div class="modal-container bg-white mx-auto rounded-lg shadow-lg z-[60] overflow-hidden w-full max-w-md">
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
@endsection

<style>
/* Toggle switch minimal styles */
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
/* Buscador unificado */
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
    // Mostrar/ocultar botón limpiar del buscador
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

    // Verificar si el producto tiene historial y mostrar/ocultar botón (función global)
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
    
    // Botón "Ver Historial de Cambios" en el header del modal
    document.addEventListener('click', function(e) {
        if (e.target.closest('#btnVerHistorial')) {
            const productoNombre = document.getElementById('det-nombre')?.value || '';
            if (productoNombre) {
                // Redirigir a auditoría con búsqueda automática
                window.location.href = `/admin/logs?search=${encodeURIComponent(productoNombre)}`;
            }
        }
    })
});
</script>

<!-- Overlay de carga para edición -->
<style>
.loading-overlay { position: fixed; inset: 0; background: rgba(255,255,255,0.6); backdrop-filter: blur(2px); display: none; align-items: center; justify-content: center; z-index: 9999; }
.loading-overlay .loading-spinner {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  position: relative;
  /* Semicírculo rojo que gira */
  background: conic-gradient(#f87171 0 180deg, transparent 180deg 360deg);
  /* Grosor del aro */
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
  background: #f87171; /* círculo interno fijo */
  box-shadow: 0 0 0 4px #fde2e2; /* halo suave alrededor para estilo similar a la referencia */
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
// Variables globales necesarias para el JavaScript
window.APP_PRODUCTS_AJAX = '{{ route('inventario.productos.ajax') }}';
window.APP_DEFAULT_IMAGE = '{{ asset('assets/images/default-product.svg') }}';
</script>
<script src="{{ asset('assets/js/inventario/agregar.js') }}?v={{ time() }}" defer></script>
<script src="{{ asset('assets/js/inventario/productos_botica.js') }}?v={{ time() }}"></script>
<style>
/* Paginación estilo historial (rojo) */
.historial-pagination-improved { padding: 1.5rem 2rem; border-top: 1px solid #e5e7eb; display:flex; justify-content:between; align-items:center; gap:1rem; background:white; }
.historial-pagination-info { flex:1; }
.historial-pagination-controls { display:flex; align-items:center; gap:.5rem; }
.historial-pagination-btn { padding:.5rem .75rem; font-size:.875rem; font-weight:500; color:#374151; background:white; border:1px solid #d1d5db; border-radius:6px; text-decoration:none; transition:all .2s ease; display:inline-flex; align-items:center; justify-content:center; min-width:2.5rem; }
.historial-pagination-btn:hover { background:#f9fafb; border-color:#9ca3af; color:#374151; }
.historial-pagination-btn-current { background:#e53e3e !important; border-color:#e53e3e !important; color:white !important; }
.historial-pagination-btn-disabled { padding:.5rem .75rem; font-size:.875rem; font-weight:500; color:#9ca3af; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; cursor:not-allowed; display:inline-flex; align-items:center; justify-content:center; min-width:2.5rem; }
@media (max-width:768px){ .historial-pagination-improved{ flex-direction:column; align-items:stretch; gap:1rem; } .historial-pagination-controls{ justify-content:center; flex-wrap:wrap; } }

/* Skeleton loading (Categorías) */
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
/* Header del modal: asegurar texto blanco */
.modal-header { color: #ffffff !important; }
.modal-header h3 { color: #ffffff !important; }
.modal-header iconify-icon { color: #ffffff !important; }
.modal-header .modal-close, .modal-close-edit { color: #ffffff !important; }
/* Fondo del header: púrpura para Agregar, verde para Editar */
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
/* Detalles: usa mismo header verde */
#modalDetallesBotica .modal-header {
  background: linear-gradient(90deg, #93c5fd, #3b82f6);
  color: #ffffff;
  padding: 16px;
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}

/* Flechitas visibles en selects dentro de modales */
#modalAgregar select, #modalEditar select {
  -webkit-appearance: none; /* Safari */
  -moz-appearance: none;    /* Firefox */
  appearance: none;         /* Estándar */
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='22' height='22' viewBox='0 0 24 24' fill='none' stroke='%23111827' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 22px 22px;
  padding-right: 3rem; /* espacio para la flecha */
}

/* Ocultar flecha de IE/Edge heredada */
select::-ms-expand { display: none; }

/* Wrapper para flecha visible en selects */
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
/* ====== Mejora visual de inputs en los modales (Agregar/Editar) ====== */
:root {
  --input-radius: 10px;
  --input-bg: #f9fafb;
  --input-border: #d1d5db;
  --input-text: #111827;
  --input-placeholder: #9ca3af;
  --focus-color: #7c3aed; /* violeta del header */
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

/* Detalles - estilo más suave y moderno */
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

/* Evitar scroll de fondo cuando hay modal abierto */
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

/* Mensajes de validación (solo cuando hay error) */
.field-error { display: none; color: #dc2626; font-size: 0.875rem; margin-top: .25rem; align-items: center; gap: .375rem; }
.field-error.visible { display: flex; }
.field-error iconify-icon { color: #dc2626; font-size: 1rem; }
</style>
<!-- Modal Agregar Producto (look original) -->
<div id="modalAgregar" class="modal-overlay fixed inset-0 hidden items-center justify-center z-50">
    <div class="modal-container bg-white mx-auto rounded-lg shadow-lg z-50 overflow-y-auto" style="width:78vw; max-width:980px; max-height:96vh; height:auto; display:flex; flex-direction:column; overflow-y:hidden;">
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
                <!-- Fila 1: Nombre del producto (ocupa toda la fila) -->
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

                <!-- Fila 2: Marca, Categoría, Presentación, Concentración -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca <span class="text-red-500">*</span></label>
                        <input type="text" name="marca" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Genfar" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                        <div class="select-wrapper mt-1">
                            <select name="categoria" id="add-categoria" class="block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Seleccionar</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Presentación <span class="text-red-500">*</span></label>
                        <div class="select-wrapper mt-1">
                            <select name="presentacion" id="add-presentacion" class="block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Seleccionar</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Concentración <span class="text-red-500">*</span></label>
                        <input type="text" name="concentracion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: 500mg" required>
                    </div>
                </div>

                <!-- Fila 3: Lote, Código de Barras, Proveedor -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lote <span class="text-red-500">*</span></label>
                        <input type="text" name="lote" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras <span class="text-red-500">*</span></label>
                        <input type="text" name="codigo_barras" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" maxlength="13" required>
                        <p class="text-xs text-gray-500 mt-1">Ingrese 13 dígitos (EAN13).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <div class="select-wrapper mt-1">
                            <select name="proveedor_id" id="add-proveedor" class="block w-full rounded-md border-gray-300 shadow-sm" required>
                                <option value="">Seleccionar</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Fila 4: Stock Actual, Stock Mínimo, Precio Compra, Precio Venta -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual <span class="text-red-500">*</span></label>
                        <input type="number" name="stock_actual" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo <span class="text-red-500">*</span></label>
                        <input type="number" name="stock_minimo" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Precio Compra <span class="text-red-500">*</span></label>
                        <input type="number" name="precio_compra" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Precio Venta <span class="text-red-500">*</span></label>
                        <input type="number" name="precio_venta" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>

                <!-- Fila 5: Fecha Fabricación, Fecha Vencimiento -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fabricación <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha_fabricacion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha_vencimiento" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>

                <!-- Fila 8: Imagen (ocupa toda la fila) -->
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <div class="image-upload-wrapper">
                            <input type="file" name="imagen" accept="image/*" id="imagen-input">
                            <p class="upload-text">Haz clic para subir una imagen</p>
                            <p class="upload-hint">PNG, JPG o GIF (Max. 2MB)</p>
                        </div>
                        <div id="preview-container" class="mt-4 hidden text-center">
                            <img id="preview-image" class="h-32 w-32 object-cover rounded-lg inline-block" src="" alt="Vista previa">
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

<!-- Modal Editar Producto (look original) -->
<div id="modalEditar" class="modal-overlay fixed inset-0 hidden items-center justify-center z-50">
    <div class="modal-container bg-white mx-auto rounded-lg shadow-lg z-50 overflow-y-auto" style="width:76vw; max-width:960px; max-height:96vh; height:auto; display:flex; flex-direction:column; overflow-y:hidden;">
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

                <!-- Fila 1: Nombre del producto (ocupa toda la fila) -->
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" id="edit-nombre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Paracetamol 500mg" required>
                        <div id="error-nombre-edit" class="field-error">
                            <iconify-icon icon="heroicons:exclamation-circle-solid"></iconify-icon>
                            <span>El nombre del producto es obligatorio</span>
                        </div>
                    </div>
                </div>

                <!-- Fila 2: Marca, Categoría, Presentación, Concentración -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Marca <span class="text-red-500">*</span></label>
                        <input type="text" name="marca" id="edit-marca" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Genfar" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
                        <div class="select-wrapper mt-1">
                            <select name="categoria" id="edit-categoria" class="block w-full rounded-md border-gray-300 shadow-sm" required></select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Presentación <span class="text-red-500">*</span></label>
                        <div class="select-wrapper mt-1">
                            <select name="presentacion" id="edit-presentacion" class="block w-full rounded-md border-gray-300 shadow-sm" required></select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Concentración <span class="text-red-500">*</span></label>
                        <input type="text" name="concentracion" id="edit-concentracion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: 500mg" required>
                    </div>
                </div>

                <!-- Fila 3: Lote, Código de Barras, Proveedor -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lote <span class="text-red-500">*</span></label>
                        <input type="text" name="lote" id="edit-lote" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras <span class="text-red-500">*</span></label>
                        <input type="text" name="codigo_barras" id="edit-codigo_barras" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" maxlength="13" required>
                        <p class="text-xs text-gray-500 mt-1">Ingrese 13 dígitos (EAN13).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor <span class="text-red-500">*</span></label>
                        <div class="select-wrapper mt-1">
                            <select name="proveedor_id" id="edit-proveedor" class="block w-full rounded-md border-gray-300 shadow-sm" required disabled>
                                <option value="">Cargando proveedores...</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Fila 4: Stock Actual, Stock Mínimo, Precio Compra, Precio Venta -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual <span class="text-red-500">*</span></label>
                        <input type="number" name="stock_actual" id="edit-stock_actual" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo <span class="text-red-500">*</span></label>
                        <input type="number" name="stock_minimo" id="edit-stock_minimo" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Precio Compra <span class="text-red-500">*</span></label>
                        <input type="number" name="precio_compra" id="edit-precio_compra" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Precio Venta <span class="text-red-500">*</span></label>
                        <input type="number" name="precio_venta" id="edit-precio_venta" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>

                <!-- Fila 5: Fecha Fabricación, Fecha Vencimiento -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fabricación <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha_fabricacion" id="edit-fecha_fabricacion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha_vencimiento" id="edit-fecha_vencimiento" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </div>

                <!-- Fila 8: Imagen (ocupa toda la fila) -->
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
// Función para manejar la selección de productos desde la búsqueda instantánea
function seleccionarProductoInventario(producto) {
    // Buscar el producto en la tabla y resaltarlo
    const filas = document.querySelectorAll('.tabla-productos-botica tbody tr');
    
    filas.forEach(fila => {
        fila.classList.remove('highlight-producto');
        const nombreProducto = fila.querySelector('td:first-child')?.textContent?.trim();
        
        if (nombreProducto && nombreProducto.toLowerCase().includes(producto.nombre.toLowerCase())) {
            fila.classList.add('highlight-producto');
            // Scroll hacia el producto
            fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Remover el highlight después de 3 segundos
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