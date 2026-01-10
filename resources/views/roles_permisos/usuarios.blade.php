@extends('layout.layout')
@php
    $title='Gestión de Productos';
    $subTitle = 'Lista de Productos';
    $script='
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
        <script src="' . asset('assets/js/inventario/eliminar.js') . '"></script>
        <script src="' . asset('assets/js/inventario/listaproductos.js') . '"></script>
    ';
    use Carbon\Carbon;
@endphp
<head>
    <title>Lista de productos</title>
    <!-- Vite removido para evitar errores de conexión -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/tablas.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/crud.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/modal_agregar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/modal_ver.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/modal_editar.css') }}">
    <!-- Estilos modernos para badges de ubicación -->
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/productos/tablas.css') }}?v={{ time() }}">
    <!-- FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Scripts moved to section for better organization -->
</head>

@section('content')

    <!-- Modal de Detalles -->
    <div id="modalDetalles" class="modal-overlay fixed inset-0 hidden items-center justify-center z-50">
        <div class="modal-container max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="modal-header-detail">
                <h3 class="modal-title-detail">
                    <iconify-icon icon="heroicons:eye-solid" class="text-xl"></iconify-icon>
                    Detalles del Producto
                </h3>
                <button class="modal-close-detail" onclick="cerrarModalDetalles()">
                    <iconify-icon icon="heroicons:x-mark-solid" class="text-lg"></iconify-icon>
                </button>
            </div>

            <!-- Content -->
            <div class="modal-content-details">
                <div class="details-grid-container">

                    <!-- Columna Izquierda: Imagen y Datos Principales -->
                    <div class="details-col">
                        <!-- Imagen y Nombre -->
                        <div class="detail-section product-main-info">
                            <div class="product-image-container">
                                <img id="modal-imagen" src="" alt="Producto">
                                <div class="product-icon-overlay">
                                    <i class="fas fa-pills"></i>
                                    <span class="product-icon-fallback">💊</span>
                                </div>
                            </div>
                            <h3 id="modal-nombre" class="text-2xl font-bold text-gray-800"></h3>
                            <p id="modal-concentracion" class="text-md text-gray-500"></p>
                        </div>
                        
                        <!-- Información General -->
                        <div class="detail-section">
                            <h4 class="detail-section-title">
                                <iconify-icon icon="heroicons:identification-solid"></iconify-icon>
                                Información General
                            </h4>
                            <div class="space-y-2">
                                <div class="detail-item-grid">
                                    <span class="detail-label">Categoría</span>
                                    <span class="detail-value" id="modal-categoria"></span>
                                </div>
                                <div class="detail-item-grid">
                                    <span class="detail-label">Marca</span>
                                    <span class="detail-value" id="modal-marca"></span>
                                </div>
                                <div class="detail-item-grid">
                                    <span class="detail-label">Presentación</span>
                                    <span class="detail-value" id="modal-presentacion"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="detail-section">
                            <h4 class="detail-section-title">
                                <iconify-icon icon="heroicons:check-badge-solid"></iconify-icon>
                                Estado Actual
                            </h4>
                            <div id="modal-estado-container">
                                <span id="modal-estado" class="detail-value-lg"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Resto de la Información -->
                    <div class="details-col">
                        <!-- Códigos y Lote -->
                         <div class="detail-section">
                            <h4 class="detail-section-title">
                                <iconify-icon icon="heroicons:qr-code-solid"></iconify-icon>
                                Códigos y Lote
                            </h4>
                            <div class="codes-cards-grid">
                                <div class="detail-card">
                                    <span class="detail-label">ID</span>
                                    <span class="detail-value-lg" id="modal-id"></span>
                                </div>
                                <div class="detail-card">
                                    <span class="detail-label">Código de Barras</span>
                                    <span class="detail-value-lg" id="modal-codigo-barras"></span>
                                </div>
                                <div class="detail-card">
                                    <span class="detail-label">Lote</span>
                                    <span class="detail-value-lg" id="modal-lote"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Stock y Precios -->
                        <div class="detail-section">
                            <h4 class="detail-section-title">
                                <iconify-icon icon="heroicons:banknotes-solid"></iconify-icon>
                                Stock y Precios
                            </h4>
                            <div class="stock-dates-cards-grid">
                                <div class="detail-card">
                                    <span class="detail-label">Stock Actual</span>
                                    <span class="detail-value-lg" id="modal-stock"></span>
                                </div>
                                <div class="detail-card">
                                    <span class="detail-label">Stock Mínimo</span>
                                    <span class="detail-value-lg" id="modal-stock-min"></span>
                                </div>
                                <div class="detail-card">
                                    <span class="detail-label">Precio Compra</span>
                                    <span class="detail-value-lg" id="modal-precio-compra"></span>
                                </div>
                                <div class="detail-card">
                                    <span class="detail-label">Precio Venta</span>
                                    <span class="detail-value-lg" id="modal-precio-venta"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Fechas y Ubicación -->
                        <div class="detail-section">
                             <h4 class="detail-section-title">
                                <iconify-icon icon="heroicons:calendar-days-solid"></iconify-icon>
                                Fechas y Ubicación
                            </h4>
                             <div class="stock-dates-cards-grid">
                                <div class="detail-card">
                                    <span class="detail-label">Fabricación</span>
                                    <span class="detail-value-lg" id="modal-fecha-fab"></span>
                                </div>
                                <div class="detail-card">
                                    <span class="detail-label">Vencimiento</span>
                                    <span class="detail-value-lg" id="modal-fecha-ven"></span>
                                </div>
                                <div class="detail-card ubicacion-card">
                                    <span class="detail-label">Ubicación</span>
                                    <span class="detail-value-lg" id="modal-ubicacion"></span>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Modal Editar Producto -->
    <div id="modalEditar" class="modal-overlay fixed inset-0 hidden items-center justify-center z-50">
        <div class="modal-container bg-white w-11/12 md:max-w-3xl mx-auto rounded-lg shadow-lg z-50 overflow-y-auto">
            <div class="modal-header">
                <h3 class="text-xl font-semibold flex items-center gap-3">
                    <iconify-icon icon="lucide:edit"></iconify-icon>
                    Editar Producto
                </h3>
                <button class="modal-close-edit text-2xl font-bold">&times;</button>
            </div>
        
            <div class="modal-content p-6">
                <form id="formEditarProducto" class="space-y-6" enctype="multipart/form-data" novalidate>
                    <input type="hidden" id="edit-producto-id" name="producto_id">
        
                    <!-- Sección: Información Principal -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                            <iconify-icon icon="heroicons:identification-solid"></iconify-icon>
                            Información Principal
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto</label>
                                <div class="input-group">
                                    <iconify-icon icon="heroicons:cube"></iconify-icon>
                                    <input type="text" name="nombre" id="edit-nombre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                                <div class="input-group select-group">
                                    <iconify-icon icon="heroicons:tag-solid"></iconify-icon>
                                    <select name="categoria" id="edit-categoria" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                        <option value="">Seleccionar</option>
                                        @if(isset($categorias))
                                            @foreach($categorias as $categoria)
                                                <option value="{{ $categoria->nombre }}">{{ $categoria->nombre }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                                <div class="input-group">
                                    <iconify-icon icon="heroicons:building-storefront-solid"></iconify-icon>
                                    <input type="text" name="marca" id="edit-marca" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                                <div class="input-group select-group">
                                    <iconify-icon icon="heroicons:truck-solid"></iconify-icon>
                                    <select name="proveedor_id" id="edit-proveedor" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">Seleccionar proveedor (opcional)</option>
                                        @if(isset($proveedores))
                                            @foreach($proveedores as $proveedor)
                                                <option value="{{ $proveedor->id }}">{{ $proveedor->razon_social }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Sección: Detalles y Códigos -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                            <iconify-icon icon="heroicons:document-text-solid"></iconify-icon>
                            Detalles y Códigos
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Presentación</label>
                                <div class="input-group select-group">
                                     <iconify-icon icon="heroicons:beaker-solid"></iconify-icon>
                                    <select name="presentacion" id="edit-presentacion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                        <option value="">Seleccionar</option>
                                        @if(isset($presentaciones))
                                            @foreach($presentaciones as $presentacion)
                                                <option value="{{ $presentacion->nombre }}">{{ $presentacion->nombre }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Concentración</label>
                                <div class="input-group">
                                    <iconify-icon icon="heroicons:scale-solid"></iconify-icon>
                                    <input type="text" name="concentracion" id="edit-concentracion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            </div>
                             <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Lote</label>
                                <div class="input-group">
                                    <iconify-icon icon="heroicons:inbox-stack"></iconify-icon>
                                    <input type="text" name="lote" id="edit-lote" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras</label>
                                <div class="input-group">
                                    <iconify-icon icon="heroicons:qr-code"></iconify-icon>
                                    <input type="text" name="codigo_barras" id="edit-codigo_barras" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección: Stock y Precios -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                            <iconify-icon icon="heroicons:circle-stack-solid"></iconify-icon>
                            Stock y Precios
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual</label>
                                <input type="number" name="stock_actual" id="edit-stock_actual" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                                <input type="number" name="stock_minimo" id="edit-stock_minimo" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                             <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Precio Compra</label>
                                <input type="number" name="precio_compra" id="edit-precio_compra" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Precio Venta</label>
                                <input type="number" name="precio_venta" id="edit-precio_venta" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                        </div>
                    </div>
        
                    <!-- Sección: Fechas -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                             <iconify-icon icon="heroicons:calendar-days-solid"></iconify-icon>
                            Fechas
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fabricación</label>
                                <input type="date" name="fecha_fabricacion" id="edit-fecha_fabricacion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                                <input type="date" name="fecha_vencimiento" id="edit-fecha_vencimiento" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección: Ubicación -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                            <iconify-icon icon="heroicons:map-pin-solid"></iconify-icon>
                            Ubicación (Opcional)
                        </h4>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
                                 <div class="input-group">
                                    <iconify-icon icon="heroicons:building-library"></iconify-icon>
                                    <input type="text" name="ubicacion" id="edit-ubicacion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Pasillo A, Estante 2">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                     <!-- Sección: Imagen -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                             <iconify-icon icon="heroicons:photo-solid"></iconify-icon>
                            Imagen del Producto
                        </h4>
                         <div class="image-upload-wrapper">
                             <input type="file" name="imagen" accept="image/*" id="edit-imagen-input">
                             <div class="upload-icon">
                                 <iconify-icon icon="heroicons:arrow-up-tray-solid"></iconify-icon>
                             </div>
                             <p class="upload-text">Haz clic para subir una nueva imagen</p>
                             <p class="upload-hint">PNG, JPG o GIF (Max. 2MB)</p>
                         </div>
                        <div id="edit-preview-container" class="mt-4 text-center" style="display: block;">
                            <img id="edit-preview-image" class="h-32 w-32 object-cover rounded-lg inline-block" src="" alt="Vista previa de la imagen">
                            <p class="text-sm text-gray-500 mt-2">Imagen actual</p>
                        </div>
                    </div>
        
                    <!-- Botones de acción -->
                    <div class="form-actions flex justify-end gap-4 pt-4">
                        <button type="button"
                                class="btn-cancel-edit px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors border border-gray-300">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="btn-save-edit px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
                            <iconify-icon icon="heroicons:check-circle-solid"></iconify-icon>
                            <span>Guardar Cambios</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Modal Agregar Producto -->
    <div id="modalAgregar" class="modal-overlay fixed inset-0 hidden items-center justify-center z-50">
        <div class="modal-container bg-white w-11/12 md:max-w-3xl mx-auto rounded-lg shadow-lg z-50 overflow-y-auto">
            <div class="modal-header">
                <h3 class="text-xl font-semibold flex items-center gap-3">
                    <iconify-icon icon="heroicons:plus-circle-solid"></iconify-icon>
                    Agregar Nuevo Producto
                </h3>
                <button class="modal-close text-2xl font-bold">&times;</button>
            </div>
        
            <div class="modal-content p-6">
                <form id="formAgregarProducto" class="space-y-6" enctype="multipart/form-data" novalidate>
        
                    <!-- Sección: Información Principal -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                            <iconify-icon icon="heroicons:identification-solid"></iconify-icon>
                            Información Principal
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto</label>
                                <div class="input-group">
                                    <iconify-icon icon="heroicons:cube"></iconify-icon>
                                    <input type="text" name="nombre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Paracetamol 500mg" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                                <div class="input-group select-group">
                                    <iconify-icon icon="heroicons:tag-solid"></iconify-icon>
                                    <select name="categoria" id="add-categoria" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                        <option value="">Seleccionar</option>
                                        @if(isset($categorias))
                                            @foreach($categorias as $categoria)
                                                <option value="{{ $categoria->nombre }}">{{ $categoria->nombre }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                                <div class="input-group">
                                    <iconify-icon icon="heroicons:building-storefront-solid"></iconify-icon>
                                    <input type="text" name="marca" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: Genfar" required>
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Sección: Detalles y Códigos -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                            <iconify-icon icon="heroicons:document-text-solid"></iconify-icon>
                            Detalles y Códigos
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Presentación</label>
                                <div class="input-group select-group">
                                     <iconify-icon icon="heroicons:beaker-solid"></iconify-icon>
                                    <select name="presentacion" id="add-presentacion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                        <option value="">Seleccionar</option>
                                        @if(isset($presentaciones))
                                            @foreach($presentaciones as $presentacion)
                                                <option value="{{ $presentacion->nombre }}">{{ $presentacion->nombre }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Concentración</label>
                                <div class="input-group">
                                    <iconify-icon icon="heroicons:scale-solid"></iconify-icon>
                                    <input type="text" name="concentracion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej: 500mg" required>
                                </div>
                            </div>
                             <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Lote</label>
                                <div class="input-group">
                                    <iconify-icon icon="heroicons:inbox-stack"></iconify-icon>
                                    <input type="text" name="lote" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras</label>
                                <div class="input-group">
                                    <iconify-icon icon="heroicons:qr-code"></iconify-icon>
                                    <input type="text" name="codigo_barras" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección: Stock y Precios -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                            <iconify-icon icon="heroicons:circle-stack-solid"></iconify-icon>
                            Stock y Precios
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock Actual</label>
                                <input type="number" name="stock_actual" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                                <input type="number" name="stock_minimo" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                             <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Precio Compra</label>
                                <input type="number" name="precio_compra" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Precio Venta</label>
                                <input type="number" name="precio_venta" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                        </div>
                    </div>
        
                    <!-- Sección: Fechas -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                             <iconify-icon icon="heroicons:calendar-days-solid"></iconify-icon>
                            Fechas
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fabricación</label>
                                <input type="date" name="fecha_fabricacion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
                                <input type="date" name="fecha_vencimiento" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                        </div>
                    </div>
                    
                     <!-- Sección: Imagen -->
                    <div class="form-section">
                        <h4 class="form-section-title">
                             <iconify-icon icon="heroicons:photo-solid"></iconify-icon>
                            Imagen del Producto
                        </h4>
                         <div class="image-upload-wrapper">
                             <input type="file" name="imagen" accept="image/*" id="imagen-input">
                             <div class="upload-icon">
                                 <iconify-icon icon="heroicons:arrow-up-tray-solid"></iconify-icon>
                             </div>
                             <p class="upload-text">Haz clic para subir una imagen</p>
                             <p class="upload-hint">PNG, JPG o GIF (Max. 2MB)</p>
                         </div>
                        <div id="preview-container" class="mt-4 hidden text-center">
                            <img id="preview-image" class="h-32 w-32 object-cover rounded-lg inline-block" src="" alt="Vista previa de la imagen">
                        </div>
                    </div>
        
                    <!-- Botones de acción -->
                    <div class="form-actions flex justify-end gap-4 pt-4">
                        <button type="button"
                                class="btn-cancel px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors border border-gray-300">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="btn-save px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
                            <iconify-icon icon="heroicons:check-circle-solid"></iconify-icon>
                            <span>Guardar Producto</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    


    <div class="grid grid-cols-12">
        <div class="col-span-12">
            <div class="card border-0 overflow-hidden">
                <div class="card-header">
                    <h6 class="card-title mb-0 text-lg">Lista de Productos</h6>
                </div>
                <div class="card-body">
                    <!-- Barra de herramientas -->
                    <div class="filtros-bar">
                        <input type="search" 
                            id="searchInput" 
                            placeholder="Buscar..." 
                            class="input-buscar" />
                        <label for="filterEstado">Estado:</label>
                        <select id="filterEstado" class="select-estado">
                            <option value="todos">Todos</option>
                            <option value="normal">Normal</option>
                            <option value="bajo_stock">Bajo stock</option>
                            <option value="por_vencer">Por Vencer</option>
                            <option value="vencido">Vencido</option>
                        </select>
                        <div style="flex:1"></div>
                        <button id="btnExcel" class="btn-excel">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Excel
                        </button>
                        <button id="btnPDF" class="btn-pdf">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            PDF
                        </button>
                        <button id="btnAgregarProducto" class="btn-agregar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar Producto
                        </button>
                    </div>

                      
                    @if ($productos->isEmpty())
                        <div class="text-center py-8">
                            <div class="flex flex-col items-center justify-center p-6">
                                <h3 class="text-xl font-medium text-gray-800 mt-4">No se encontraron productos</h3>
                                <p class="text-base text-gray-500 text-center mt-2">
                                    Aún no hay productos registrados. ¡Haz clic en "Agregar Producto" para comenzar!
                                </p>
                            </div>
                        </div>
                    @else
                        <table id="selection-table" class="table-productos min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500">
                                    <tr>
                                    <th scope="col" class="text-neutral-800">
                                        <div class="flex items-center gap-2 justify-center">
                                            ID
                                            <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4"/>
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="text-neutral-800">
                                        <div class="flex items-center gap-2">
                                            Producto
                                            <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4"/>
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="text-neutral-800">
                                        <div class="flex items-center gap-2">
                                            Fecha Vencimiento
                                            <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4"/>
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="text-neutral-800">
                                        <div class="flex items-center gap-2">
                                            Precio
                                            <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4"/>
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="text-neutral-800">
                                        <div class="flex items-center gap-2">
                                            Ubicación Almacén
                                            <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4"/>
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="text-neutral-800">
                                        <div class="flex items-center gap-2">
                                            Estado
                                            <svg class="w-4 h-4 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m8 15 4 4 4-4m0-6-4-4-4 4"/>
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="text-neutral-800">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($productos as $producto)
                                @php
                                    $hoy = Carbon::now()->startOfDay();
                                    $fechaVenc = Carbon::parse($producto->fecha_vencimiento)->startOfDay();
                                    $diasParaVencer = $hoy->diffInDays($fechaVenc, false);
                                    $estado = '';
                                    $estadoClass = '';
                                    if ($fechaVenc->lt($hoy)) {
                                        $estado = 'Vencido';
                                        $estadoClass = 'estado-vencido';
                                    } elseif ($diasParaVencer <= 30 && $diasParaVencer >= 0) {
                                        $estado = 'Por vencer';
                                        $estadoClass = 'estado-por-vencer';
                                    } elseif ($producto->stock_actual <= $producto->stock_minimo) {
                                        $estado = 'Bajo stock';
                                        $estadoClass = 'estado-bajo-stock';
                                    } else {
                                        $estado = 'Normal';
                                        $estadoClass = 'estado-normal';
                                    }
                                @endphp
                                <tr data-id="{{ $producto->id }}">
                                    <td class="text-xs text-center">{{ $producto->id }}</td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $producto->imagen_url }}" 
                                                alt="{{ $producto->nombre }}"
                                                class="w-10 h-10 rounded-lg object-cover shadow-sm border border-gray-200 bg-white"
                                                style="padding: 2px; margin-right: 8px;"
                                                onerror="this.onerror=null; this.src='{{ asset('assets/images/default-product.svg') }}';">
                                            <div>
                                                <h6 class="text-base font-semibold text-gray-800 leading-tight">{{ $producto->nombre }}</h6>
                                                <span class="text-secondary">{{ $producto->concentracion }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center text-secondary">{{ $producto->fecha_vencimiento_solo_fecha ?? 'N/A' }}</td>
                                    <td class="text-center font-semibold">S/ {{ number_format($producto->precio_venta, 2) }}</td>
                                    <td class="text-center">
                                        @if($producto->ubicacion_almacen)
                                            <div class="ubicacion-badge ubicado">
                                                <div class="ubicacion-icon">
                                                    <iconify-icon icon="solar:map-point-bold-duotone"></iconify-icon>
                                                </div>
                                                <div class="ubicacion-info">
                                                    <span class="ubicacion-texto">{{ $producto->ubicacion_almacen }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="ubicacion-badge sin-ubicar">
                                                <div class="ubicacion-icon">
                                                    <iconify-icon icon="solar:question-circle-bold-duotone"></iconify-icon>
                                                </div>
                                                <div class="ubicacion-info">
                                                    <span class="ubicacion-texto">Sin ubicar</span>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="estado-badge {{ $estadoClass }}">
                                            {{ $estado }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="javascript:void(0)" 
                                        class="btn-view w-8 h-8 bg-primary-50 text-primary-600 rounded-full inline-flex items-center justify-center">
                                            <iconify-icon icon="iconamoon:eye-light"></iconify-icon>
                                        </a>
                                        <a href="javascript:void(0)" 
                                        class="btn-edit w-8 h-8 bg-success-100 text-success-600 rounded-full inline-flex items-center justify-center">
                                            <iconify-icon icon="lucide:edit"></iconify-icon>
                                        </a>
                                        <a href="javascript:void(0)" 
                                            onclick="eliminarProducto({{ $producto->id }})"
                                            class="w-8 h-8 bg-danger-100 text-danger-600 rounded-full inline-flex items-center justify-center">
                                            <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

<!-- JavaScript inline para garantizar que las funciones estén disponibles -->
<script>
// Función global para verificar si SweetAlert2 está disponible
function verificarSwal() {
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 no está cargado');
        return false;
    }
    return true;
}

// Función global eliminarProducto (debe estar disponible inmediatamente)
function eliminarProducto(id) {
    if (!verificarSwal()) {
        alert('Error: Sistema no inicializado correctamente');
        return;
    }
    
    // Buscar información del producto
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (!row) {
        Swal.fire('Error', 'No se pudo encontrar el producto', 'error');
        return;
    }
    
    const nombreProducto = row.querySelector('td:nth-child(2) h6')?.textContent?.trim() || 'Producto';
    const precio = row.querySelector('td:nth-child(4)')?.textContent?.trim() || 'N/A';
    const estado = row.querySelector('td:nth-child(6) span')?.textContent?.trim() || 'Normal';

    Swal.fire({
        title: '¿Eliminar producto?',
        html: `
            <div class="text-center">
                <p class="mb-2 text-gray-600">Estás a punto de eliminar el producto:</p>
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <p class="font-semibold text-lg text-gray-800">${nombreProducto}</p>
                    <div class="flex items-center justify-center gap-4 mt-2 text-sm text-gray-600">
                        <span>Precio: ${precio}</span>
                        <span class="px-2 py-1 rounded bg-gray-200">${estado}</span>
                    </div>
                </div>
                <p class="text-sm text-gray-600">Esta acción eliminará permanentemente el producto del inventario y no podrá ser recuperado.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar producto',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando producto...',
                text: 'Por favor, espere...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const token = document.querySelector('meta[name="csrf-token"]').content;
            
            fetch(`/inventario/producto/eliminar/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado correctamente!',
                        text: `${nombreProducto} ha sido eliminado del inventario.`,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        Turbo.visit(window.location.href, { action: 'replace' });
                    });
                } else {
                    throw new Error(data.message || 'Error al eliminar el producto');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error al eliminar',
                    text: error.message || 'Hubo un problema al intentar eliminar el producto.',
                    icon: 'error',
                    confirmButtonText: 'Entendido'
                });
            });
        }
    });
}

// Función auxiliar para productos
function obtenerProductoDeLaFila(row) {
  return {
    id: row.getAttribute('data-id'),
    nombre: row.querySelector('td:nth-child(2) h6')?.textContent.trim() || '',
    categoria: row.querySelector('td:nth-child(3)')?.textContent.trim() || '',
    marca: '',
    presentacion: '',
    concentracion: row.querySelector('td:nth-child(2) span')?.textContent.trim() || '',
    lote: '',
    codigo_barras: '',
    stock_actual: '',
    stock_minimo: '',
    precio_compra: '',
    precio_venta: '',
    fecha_fabricacion: '',
    fecha_vencimiento: row.querySelector('td:nth-child(4)')?.textContent.trim() || '',
    ubicacion: '',
    imagen: ''
  };
}

// Función auxiliar para renderizado
function renderCategorias(categorias) {
    let html = '';
    categorias.forEach(cat => {
        html += `
            <tr>
                <td>${cat.id}</td>
                <td>${cat.nombre}</td>
                <td>${cat.descripcion || ''}</td>
                <td>${cat.productos_count || 0}</td>
                <td><!-- Acciones --></td>
            </tr>
        `;
    });
    const tbody = document.getElementById('categorias-tbody');
    if (tbody) {
        tbody.innerHTML = html;
}
}

// Función global para cerrar modal de detalles
function cerrarModalDetalles() {
    const modal = document.getElementById('modalDetalles');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando funciones de productos');
    
    // Verificar que SweetAlert2 esté cargado
    setTimeout(() => {
        if (typeof Swal !== 'undefined') {
            console.log('✓ SweetAlert2 cargado correctamente');
        } else {
            console.error('✗ SweetAlert2 no está disponible');
        }
    }, 100);
    
    // Event listeners para modal de detalles
    const modalDetalles = document.getElementById('modalDetalles');
    if (modalDetalles) {
        // Click fuera del modal para cerrar
        modalDetalles.addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalDetalles();
            }
        });
        
        // Escape key para cerrar
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modalDetalles.classList.contains('hidden')) {
                cerrarModalDetalles();
            }
        });
    }
});
</script>

