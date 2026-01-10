@extends('layout.layout')

@php
    $title = 'Gestión de Almacén';
    $subTitle = 'Mapa Visual del Almacén';
@endphp

@section('content')
<div class="card border-0 overflow-hidden">
    <div class="card-body">
        
        

        <!-- Contenido de las Pestañas -->
        <div id="tab-mapa" class="tab-content-modern">
            <!-- Vista del Almacén Mejorada -->
            <div class="warehouse-section-modern">
                <div class="section-header-modern">
                    <div class="section-title-wrapper">
                        <iconify-icon icon="solar:warehouse-bold-duotone"></iconify-icon>
                        <h3>Distribución del Almacén</h3>
                    </div>
                    <div class="section-actions-modern">
                        <button type="button" id="btnConsejosUbicacion" class="btn-hint-modern" title="Consejos para crear ubicaciones">
                            <iconify-icon icon="solar:info-circle-bold-duotone"></iconify-icon>
                            <span>Consejos para crear</span>
                        </button>
                        <button id="btnNuevoEstante" class="btn-nuevo-estante-flotante">
                            <iconify-icon icon="solar:add-square-bold-duotone"></iconify-icon>
                            <span>Nuevo Estante</span>
                        </button>
                    </div>
                </div>
                
                <div class="warehouse-container-modern">
                    <img src="{{ ($configuracion && $configuracion->imagen_fondo_url) ? $configuracion->imagen_fondo_url : asset('imagen/login/login-fondo.jpg') }}" alt="Foto del almacén" class="warehouse-bg-modern" id="imagenFondoAlmacen">
                    <div class="warehouse-overlay-modern"></div>
                    
                    
                    
                    <div class="estantes-grid-premium warehouse-map">
                        <!-- Los estantes se cargarán dinámicamente aquí -->
                        <div class="loading-placeholder text-center p-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                                    </div>
                            <p class="mt-2 text-white">Cargando estantes...</p>
                                    </div>
                                    </div>
                                </div>
                                </div>
        </div>

        <div id="tab-listado-ubicados" class="tab-content-modern hidden">
            <div class="productos-ubicados-container">
                <div class="productos-header-modern">
                    <div class="productos-title-section">
                        <h3>Productos Ubicados</h3>
                        <p>Lista de productos con ubicación asignada en el almacén</p>
                    </div>
                    <div class="productos-controles">
                        <div class="controles-izquierda">
                            <div class="registros-selector-top">
                                <select id="registrosPorPaginaUbicados" class="select-registros-top">
                                    <option value="5">5</option>
                                    <option value="10" selected>10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                            <div class="buscar-container-modern">
                                <div class="buscar-input-wrapper">
                                    <iconify-icon icon="solar:magnifer-bold-duotone" class="buscar-icon"></iconify-icon>
                                    <input type="text" id="buscarProductosUbicados" placeholder="Buscar productos..." class="buscar-input-modern">
                                </div>
                            </div>
                        </div>
                        <div class="controles-derecha">
                            <div class="filtros-container">
                                <select id="filtroEstanteUbicados" class="filtro-select-modern">
                                    <option value="">Todos los estantes</option>
                                    @foreach($estantes as $estante)
                                        <option value="{{ $estante['id'] }}">{{ $estante['nombre'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="exportar-dropdown-container">
                                <button class="btn-exportar-dropdown" id="btnExportarDropdown">
                                    <iconify-icon icon="solar:export-bold-duotone"></iconify-icon>
                                    <span>Exportar</span>
                                    <iconify-icon icon="solar:alt-arrow-down-bold" class="dropdown-arrow"></iconify-icon>
                                </button>
                                <div class="exportar-dropdown-menu hidden" id="exportarDropdownMenu">
                                    <button class="exportar-option excel" id="btnExportarExcel">
                                        <iconify-icon icon="vscode-icons:file-type-excel" onload="this.style.display='block'" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline'"></iconify-icon>
                                        <span class="icon-fallback excel-fallback" style="display:none">📊</span>
                                        <span>Exportar Excel</span>
                                    </button>
                                    <button class="exportar-option pdf" id="btnExportarPDF">
                                        <iconify-icon icon="vscode-icons:file-type-pdf2" onload="this.style.display='block'" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline'"></iconify-icon>
                                        <span class="icon-fallback pdf-fallback" style="display:none">📄</span>
                                        <span>Exportar PDF</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tabla-responsive-modern">
                    <table class="tabla-productos-ubicados">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Ubicación</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaProductosUbicadosBody">
                            @forelse($productosUbicados as $producto)
                            <tr data-estante="{{ $producto['estante_id'] }}" data-producto-id="{{ $producto['id'] }}">
                                <td>
                                    <div class="id-cell">
                                        <strong>{{ $loop->iteration }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="producto-info-simple">
                                        <strong>{{ $producto['nombre'] }}</strong>
                                        @if($producto['concentracion'])
                                            <small style="color: #6b7280;">{{ $producto['concentracion'] }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="ubicacion-badge tipo-{{ $producto['estante_tipo'] }}">
                                        <iconify-icon icon="{{ $producto['icono_ubicacion'] }}"></iconify-icon>
                                        <span>{{ $producto['ubicacion_completa'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="stock-simple">
                                        <span class="stock-numero">{{ $producto['cantidad'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="estado-badge {{ $producto['estado'] }}">
                                        @if($producto['estado'] === 'normal')
                                            Normal
                                        @elseif($producto['estado'] === 'stock-bajo')
                                            Stock Bajo
                                        @elseif($producto['estado'] === 'stock-critico')
                                            Stock Crítico
                                        @else
                                            {{ ucfirst($producto['estado']) }}
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones-simples">
                                        <button class="btn-accion-ubicacion ver" 
                                                title="Ver en estante" 
                                                onclick="Turbo.visit('{{ route('ubicaciones.estante.detalle', ['id' => $producto['estante_id']]) }}')">
                                            <iconify-icon icon="solar:eye-bold"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #6b7280;">
                                    <iconify-icon icon="solar:box-minimalistic-broken" style="font-size: 48px; margin-bottom: 16px;"></iconify-icon>
                                    <div>
                                        <h4 style="margin: 0 0 8px 0;">No hay productos ubicados</h4>
                                        <p style="margin: 0;">Agrega productos a los estantes para verlos aquí</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="sin-productos-ubicados hidden" id="sinProductosUbicados">
                    <iconify-icon icon="solar:box-minimalistic-broken"></iconify-icon>
                    <h4>No se encontraron productos</h4>
                    <p>No hay productos que coincidan con tu búsqueda</p>
                </div>

                <div class="pagination-container-modern">
                    <div class="pagination-info">
                        Mostrando <span id="rangoUbicados">1-{{ $productosUbicados->count() }}</span> de <span id="totalUbicados">{{ $productosUbicados->count() }}</span> productos
                    </div>
                    <div class="pagination-controls">
                        <button class="btn-pagination" id="btnPrevUbicados" disabled>
                            <iconify-icon icon="solar:alt-arrow-left-bold"></iconify-icon>
                        </button>
                        <button class="btn-pagination active" data-page="1">1</button>
                        <button class="btn-pagination" id="btnNextUbicados" disabled>
                            <iconify-icon icon="solar:alt-arrow-right-bold"></iconify-icon>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-listado-sin-ubicar" class="tab-content-modern hidden">
            <div class="productos-sin-ubicar-container">
                <div class="productos-header-modern">
                    <div class="productos-title-section">
                        <h3>Productos Sin Ubicar</h3>
                        <p>Productos que necesitan asignación de ubicación urgente</p>
                    </div>
                    <div class="productos-controles">
                        <div class="controles-izquierda">
                            <div class="registros-selector-top">
                                <select id="registrosPorPaginaSinUbicar" class="select-registros-top">
                                    <option value="5">5</option>
                                    <option value="10" selected>10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                            <div class="buscar-container-modern">
                                <div class="buscar-input-wrapper">
                                    <iconify-icon icon="solar:magnifer-bold-duotone" class="buscar-icon"></iconify-icon>
                                    <input type="text" id="buscarProductosSinUbicar" placeholder="Buscar productos..." class="buscar-input-modern">
                                </div>
                            </div>
                        </div>
                        <div class="controles-derecha">
                            <div class="filtros-container">
                                <select id="filtroCategoria" class="filtro-select-modern">
                                    <option value="">Todas las categorías</option>
                                    @php
                                        $categoriasUnicas = $productosSinUbicar->pluck('categoria')->unique()->filter();
                                    @endphp
                                    @foreach($categoriasUnicas as $categoria)
                                        <option value="{{ $categoria }}">{{ $categoria }}</option>
                                    @endforeach
                                </select>
                                <select id="filtroPrioridad" class="filtro-select-modern">
                                    <option value="">Todas las prioridades</option>
                                    <option value="alta">Alta</option>
                                    <option value="media">Media</option>
                                    <option value="baja">Baja</option>
                                </select>
                            </div>
                            <button class="btn-asignar-masivo-modern">
                                <iconify-icon icon="solar:widget-add-bold-duotone"></iconify-icon>
                                <span>Asignar Masivo</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alerta mejorada de productos sin ubicar -->
                <div class="alerta-sin-ubicar-mejorada">
                    <div class="alerta-contenido-principal">
                        <div class="alerta-icon-principal">
                            <iconify-icon icon="solar:danger-triangle-bold-duotone"></iconify-icon>
                        </div>
                        <div class="alerta-texto-principal">
                            <h4 class="alerta-titulo">¡Atención Requerida!</h4>
                            <p class="alerta-descripcion">
                                Tienes <span class="numero-productos">{{ $productosSinUbicar->count() }}</span> 
                                producto{{ $productosSinUbicar->count() !== 1 ? 's' : '' }} esperando ubicación en el almacén
                            </p>
                            
                            <div class="alerta-estadisticas-compactas">
                                @php
                                    $priorityStats = $productosSinUbicar->groupBy('prioridad');
                                    $altaPrioridad = $priorityStats->get('alta', collect())->count();
                                    $mediaPrioridad = $priorityStats->get('media', collect())->count();
                                    $bajaPrioridad = $priorityStats->get('baja', collect())->count();
                                @endphp
                                
                                @if($altaPrioridad > 0)
                                    <span class="stat-pill alta">
                                        <iconify-icon icon="solar:danger-triangle-bold"></iconify-icon>
                                        {{ $altaPrioridad }} alta prioridad
                                    </span>
                                @endif
                                
                                @if($mediaPrioridad > 0)
                                    <span class="stat-pill media">
                                        <iconify-icon icon="solar:clock-circle-bold"></iconify-icon>
                                        {{ $mediaPrioridad }} media prioridad
                                    </span>
                                @endif
                                
                                @if($bajaPrioridad > 0)
                                    <span class="stat-pill baja">
                                        <iconify-icon icon="solar:checklist-minimalistic-bold"></iconify-icon>
                                        {{ $bajaPrioridad }} baja prioridad
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="alerta-acciones-derecha">
                        <button class="btn-alerta-accion primario" id="btnVerResumen">
                            <iconify-icon icon="solar:eye-bold"></iconify-icon>
                            <span>Ver Resumen</span>
                        </button>
                        <button class="btn-alerta-accion secundario" id="btnOrdenarPrioridad">
                            <iconify-icon icon="solar:sort-vertical-bold"></iconify-icon>
                            <span>Ordenar por Prioridad</span>
                        </button>
                    </div>
                    
                    <button class="alerta-close-btn">
                        <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                    </button>
                </div>

                <div class="tabla-responsive-modern">
                    <table class="tabla-productos-sin-ubicar tabla-moderna">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <label class="checkbox-modern">
                                        <input type="checkbox" id="selectAllSinUbicar">
                                        <span class="checkmark-modern"></span>
                                    </label>
                                </th>
                                <th style="width: 60px;">ID</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Stock</th>
                                <th>Tiempo Esperando</th>
                                <th>Prioridad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaProductosSinUbicarBody">
                            @forelse($productosSinUbicar as $index => $producto)
                            <tr data-prioridad="{{ $producto['prioridad'] }}" data-producto-id="{{ $producto['id'] }}">
                                <td>
                                    <label class="checkbox-modern">
                                        <input type="checkbox" class="producto-checkbox" data-producto-id="{{ $producto['id'] }}">
                                        <span class="checkmark-modern"></span>
                                    </label>
                                </td>
                                <td class="id-cell">{{ $index + 1 }}</td>
                                <td>
                                    <div class="producto-info-simple">
                                        <strong>{{ $producto['nombre'] }}</strong>
                                        @if(!empty($producto['concentracion']))
                                            <span style="color: #6b7280; font-size: 12px; margin-left: 5px;">{{ $producto['concentracion'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="categoria-badge">
                                        <span>{{ $producto['categoria'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="stock-simple">
                                        <span class="stock-numero">{{ $producto['stock'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="tiempo-esperando">
                                        <span class="tiempo-numero">{{ $producto['tiempo_esperando'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="prioridad-badge {{ $producto['prioridad'] }}">
                                        @if($producto['prioridad'] === 'alta')
                                            <iconify-icon icon="solar:danger-triangle-bold"></iconify-icon>
                                            Alta
                                        @elseif($producto['prioridad'] === 'media')
                                            <iconify-icon icon="solar:clock-circle-bold"></iconify-icon>
                                            Media
                                        @else
                                            <iconify-icon icon="solar:checklist-minimalistic-bold"></iconify-icon>
                                            Baja
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones-simples">
                                        <button class="btn-accion-principal asignar" 
                                                title="Asignar ubicación" 
                                                data-producto-id="{{ $producto['id'] }}"
                                                data-producto-nombre="{{ $producto['nombre'] }}"
                                                data-producto-stock="{{ $producto['stock'] }}">
                                            <iconify-icon icon="solar:map-point-add-bold"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;">
                                    <iconify-icon icon="solar:checklist-minimalistic-broken" style="font-size: 48px; margin-bottom: 16px; color: #22c55e;"></iconify-icon>
                                    <div>
                                        <h4 style="margin: 0 0 8px 0; color: #22c55e;">¡Excelente!</h4>
                                        <p style="margin: 0;">Todos los productos están ubicados correctamente</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination-container-modern">
                    <div class="pagination-info">
                        Mostrando <span id="rangoSinUbicar">1-{{ $productosSinUbicar->count() }}</span> de <span id="totalSinUbicar">{{ $productosSinUbicar->count() }}</span> productos
                    </div>
                    <div class="pagination-controls">
                        <button class="btn-pagination" id="btnPrevSinUbicar" disabled>
                            <iconify-icon icon="solar:alt-arrow-left-bold"></iconify-icon>
                        </button>
                        <button class="btn-pagination active" data-page="1">1</button>
                        <button class="btn-pagination" id="btnNextSinUbicar">
                            <iconify-icon icon="solar:alt-arrow-right-bold"></iconify-icon>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal Nuevo Estante -->
<div id="modalNuevoEstante" class="modal-overlay hidden">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <iconify-icon icon="solar:add-square-bold-duotone"></iconify-icon>
                <span>Nuevo Estante</span>
            </h3>
            <button class="modal-close-btn">&times;</button>
        </div>
        <form id="formNuevoEstante" class="modal-content" novalidate>
            <div class="form-group">
                <label for="nombre_estante">Nombre del Estante</label>
                <div class="input-group">
                    <iconify-icon icon="solar:tag-horizontal-bold-duotone"></iconify-icon>
                    <input type="text" id="nombre_estante" name="nombre" placeholder="Ej: Estante E" required>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="numero_niveles">Número de Niveles</label>
                    <div class="input-group">
                        <iconify-icon icon="solar:layers-bold-duotone"></iconify-icon>
                        <input type="number" id="numero_niveles" name="numero_niveles" placeholder="4" min="1" max="10" required>
                    </div>
                    <small class="form-help">Mínimo: 1, Máximo: 10</small>
                </div>
                <div class="form-group">
                    <label for="numero_posiciones">Columnas por Nivel</label>
                     <div class="input-group">
                        <iconify-icon icon="solar:window-frame-bold-duotone"></iconify-icon>
                        <input type="number" id="numero_posiciones" name="numero_posiciones" placeholder="5" min="1" max="20" required>
                    </div>
                    <small class="form-help">Mínimo: 1, Máximo: 20</small>
                </div>
            </div>
            
            <!-- Capacidad total calculada automáticamente -->
            <div class="form-group">
                <label>Capacidad Total (Se calcula automáticamente)</label>
                <div class="capacidad-calculada-display">
                    <div class="capacidad-info">
                        <iconify-icon icon="solar:calculator-bold-duotone"></iconify-icon>
                        <span id="capacidad_calculada_text">Total: <strong>20 slots</strong> (4 niveles × 5 columnas)</span>
                    </div>
                </div>
                <input type="hidden" id="capacidad_total" name="capacidad_total" value="20">
            </div>
            <div class="form-group">
                <label for="ubicacion_local">Ubicación en el Local (Opcional)</label>
                <div class="input-group">
                    <iconify-icon icon="solar:map-point-wave-bold-duotone"></iconify-icon>
                    <input type="text" id="ubicacion_local" name="ubicacion" placeholder="Ej: Pasillo central, lado izquierdo">
                </div>
            </div>
        </form>
        <div class="modal-footer">
            <button id="btnCancelarNuevoEstante" type="button" class="btn btn-secondary">Cancelar</button>
            <button id="btnGuardarNuevoEstante" type="submit" form="formNuevoEstante" class="btn btn-primary">
                <iconify-icon icon="solar:diskette-bold"></iconify-icon>
                <span>Guardar</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Asignar Ubicación -->
<div id="modalAsignarUbicacion" class="modal-overlay hidden">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <iconify-icon icon="solar:map-point-add-bold-duotone"></iconify-icon>
                <span id="tituloAsignarUbicacion">Asignar Ubicación</span>
            </h3>
            <button class="modal-close-btn" onclick="cerrarModalAsignacion()">&times;</button>
        </div>
        <form id="formUbicarProducto" class="modal-content">
            @csrf
            <input type="hidden" id="productoIdAsignar" name="producto_id" value="">
            <input type="hidden" id="ubicacionIdAsignar" name="ubicacion_id" value="">
            <input type="hidden" name="cantidad" value="1">
            
            <div class="producto-info-asignar">
                <h4>Producto a Ubicar</h4>
                <div class="producto-card-modal">
                    <div class="producto-nombre-modal" id="nombreProductoAsignar">Selecciona un producto</div>
                    <div class="producto-stock-modal" id="stockProductoAsignar">Stock: 0 unidades</div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Seleccionar Estante *</label>
                <select class="form-select" id="estanteAsignar" required>
                    <option value="">Seleccionar estante...</option>
                    <!-- Se cargarán dinámicamente desde la API -->
                </select>
            </div>
            
            <div class="form-group">
                <label>Slot Específico *</label>
                <select class="form-select" id="slotAsignar" disabled required>
                    <option value="">Primero selecciona un estante</option>
                </select>
            </div>
        </form>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModalAsignacion()">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmarAsignacion()">
                <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                <span>Asignar Ubicación</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Asignación Masiva -->
<div id="modalAsignacionMasiva" class="modal-overlay hidden">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <iconify-icon icon="solar:widget-add-bold-duotone"></iconify-icon>
                <span>Asignación Masiva de Ubicaciones</span>
            </h3>
            <button class="modal-close-btn" onclick="cerrarModalAsignacionMasiva()">&times;</button>
        </div>
        <div class="modal-content">
            <div class="productos-seleccionados-info">
                <h4>Productos Seleccionados</h4>
                <div class="resumen-seleccionados">
                    <span id="cantidadSeleccionados">0</span> productos seleccionados para asignar
                </div>
            </div>
            
            <div class="form-group">
                <label>Estrategia de Asignación</label>
                <select class="form-select" id="estrategiaAsignacion">
                    <option value="">Seleccionar estrategia...</option>
                    <option value="automatica">Asignación Automática (Espacios disponibles)</option>
                    <option value="por_categoria">Por Categoría de Producto</option>
                    <option value="por_prioridad">Por Prioridad de Rotación</option>
                    <option value="estante_especifico">Estante Específico</option>
                </select>
            </div>
            
            <div id="estanteEspecificoGroup" class="form-group" style="display: none;">
                <label>Estante Objetivo</label>
                <select class="form-select" id="estanteObjetivo">
                    <option value="">Seleccionar estante...</option>
                    <option value="A">Estante A</option>
                    <option value="B">Estante B</option>
                    <option value="C">Estante C</option>
                    <option value="D">Estante D</option>
                </select>
            </div>
            
            <div class="preview-asignacion">
                <h5>Vista Previa de Asignaciones</h5>
                <div id="previewAsignaciones" class="preview-contenido">
                    <p class="preview-empty">Selecciona una estrategia para ver la vista previa</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModalAsignacionMasiva()">Cancelar</button>
            <button class="btn btn-primary" onclick="ejecutarAsignacionMasiva()" disabled id="btnEjecutarAsignacionMasiva">
                <iconify-icon icon="solar:play-circle-bold"></iconify-icon>
                <span>Ejecutar Asignación</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Cambiar Ubicación -->
<div id="modalCambiarUbicacion" class="modal-overlay hidden">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <iconify-icon icon="solar:transfer-horizontal-bold-duotone"></iconify-icon>
                <span>Cambiar Ubicación</span>
            </h3>
            <button class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-content">
            <div class="producto-info-modal">
                <h4 id="productoNombreModal">-</h4>
                <p class="ubicacion-actual">
                    <strong>Ubicación actual:</strong> 
                    <span id="ubicacionActualModal">-</span>
                </p>
            </div>
            
            <div class="form-group">
                <label>Nueva Ubicación</label>
                <select class="form-select" id="nuevaUbicacionSelect">
                    <option value="">Seleccionar ubicación...</option>
                    <optgroup label="Estantes de Venta">
                        <option value="A-1-1">Estante A - 1-1</option>
                        <option value="A-1-2">Estante A - 1-2</option>
                        <option value="A-2-1">Estante A - 2-1</option>
                        <option value="A-3-1">Estante A - 3-1</option>
                        <option value="A-4-1">Estante A - 4-1</option>
                        <option value="B-1-1">Estante B - 1-1</option>
                        <option value="B-2-1">Estante B - 2-1</option>
                        <option value="C-1-1">Estante C - 1-1</option>
                    </optgroup>
                    <optgroup label="Almacén Interno">
                        <option value="ALMACEN-A">Almacén - Zona A</option>
                        <option value="ALMACEN-B">Almacén - Zona B</option>
                    </optgroup>
                </select>
            </div>
            
            <div class="form-group">
                <label>Motivo del cambio (Opcional)</label>
                <textarea class="form-textarea" id="motivoCambio" 
                          placeholder="Ej: Reorganización del estante..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="btnCancelarCambio">Cancelar</button>
            <button class="btn btn-primary" id="btnConfirmarCambio">
                <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                <span>Confirmar Cambio</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Eliminar Estante -->
<div id="modalEliminarEstante" class="modal-eliminar-estante">
    <div class="modal-eliminar-contenido">
        <div class="modal-eliminar-header">
            <div class="modal-icono-peligro">
                <iconify-icon icon="solar:danger-triangle-bold"></iconify-icon>
            </div>
            <h3 class="modal-eliminar-titulo">¿Eliminar Estante?</h3>
            <p class="modal-eliminar-subtitulo">Esta acción no se puede deshacer</p>
        </div>
        
        <div class="modal-eliminar-body">
            <div class="estante-info-eliminacion">
                <div class="estante-nombre-eliminacion">
                    <iconify-icon icon="solar:box-minimalistic-bold-duotone"></iconify-icon>
                    <span id="estanteNombreEliminar">Estante A</span>
                </div>
                
                <div class="estante-detalles-eliminacion">
                    <div class="detalle-item-eliminacion">
                        <span class="detalle-numero-eliminacion" id="capacidadTotalEliminar">15</span>
                        <div class="detalle-label-eliminacion">Capacidad Total</div>
                    </div>
                    <div class="detalle-item-eliminacion">
                        <span class="detalle-numero-eliminacion" id="productosActualesEliminar">0</span>
                        <div class="detalle-label-eliminacion">Productos Actuales</div>
                    </div>
                </div>

                <div id="warningProductos" class="productos-warning" style="display: none;">
                    <iconify-icon icon="solar:shield-warning-bold" class="productos-warning-icon"></iconify-icon>
                    <div class="productos-warning-text">
                        <strong>¡Atención!</strong> Este estante contiene <span id="cantidadProductosWarning">0</span> productos ubicados. 
                        Al eliminarlo, estos productos <strong>perderán su ubicación asignada</strong> y deberán ser reubicados manualmente.
                    </div>
                </div>
            </div>

            <div class="confirmacion-eliminacion">
                <p><strong>¿Estás seguro de que deseas eliminar este estante?</strong></p>
                <p>Se eliminarán permanentemente:</p>
                <ul style="margin: 0.5rem 0; padding-left: 1.5rem; color: #6b7280;">
                    <li>El estante y todas sus ubicaciones</li>
                    <li>Las asignaciones de productos (los productos no se eliminarán)</li>
                    <li>El historial de movimientos relacionado</li>
                </ul>
            </div>
        </div>
        
        <div class="modal-eliminar-footer">
            <button class="btn-modal-eliminar btn-cancelar-eliminacion" id="btnCancelarEliminacion">
                <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                <span>Cancelar</span>
            </button>
            <button class="btn-modal-eliminar btn-confirmar-eliminacion" id="btnConfirmarEliminacion">
                <iconify-icon icon="solar:trash-bin-minimalistic-bold"></iconify-icon>
                <span>Sí, Eliminar Estante</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Editar Estante -->
<div id="modalEditarEstante" class="modal-overlay hidden">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                <span>Editar Estante</span>
            </h3>
            <button class="modal-close-btn" id="btnCerrarEditarEstante">&times;</button>
        </div>
        <form id="formEditarEstante" class="modal-content" novalidate>
            <!-- 1) Nombre del Estante -->
            <div class="form-group">
                <label for="editar_nombre_estante">Nombre del Estante</label>
                <div class="input-group">
                    <iconify-icon icon="solar:tag-horizontal-bold-duotone"></iconify-icon>
                    <input type="text" id="editar_nombre_estante" name="nombre" placeholder="Ej: Estante E" required>
                </div>
                <div class="field-error" id="error_editar_nombre"></div>
            </div>
            
            <!-- 2) Niveles y Columnas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="form-group">
                    <label for="editar_numero_niveles">Número de Niveles</label>
                    <div class="input-group">
                        <iconify-icon icon="solar:layers-bold-duotone"></iconify-icon>
                        <input type="number" id="editar_numero_niveles" name="numero_niveles" placeholder="4" min="1" required readonly>
                    </div>
                    <small class="form-help">Se modifica desde el detalle del estante.</small>
                </div>
                <div class="form-group">
                    <label for="editar_numero_posiciones">Columnas por Nivel</label>
                    <div class="input-group">
                        <iconify-icon icon="solar:window-frame-bold-duotone"></iconify-icon>
                        <input type="number" id="editar_numero_posiciones" name="numero_posiciones" placeholder="5" min="1" required readonly>
                    </div>
                    <small class="form-help">Se modifica desde el detalle del estante.</small>
                </div>
            </div>
            
            <!-- 3) Capacidad total calculada (solo lectura, estilo resaltado) -->
            <div class="form-group">
                <label>Capacidad Total (Calculada automáticamente)</label>
                <div class="capacidad-calculada-display">
                    <div class="capacidad-info">
                        <iconify-icon icon="solar:calculator-bold-duotone"></iconify-icon>
                        <span id="editar_capacidad_calculada_text">Total: <strong>20 slots</strong> (4 niveles × 5 columnas)</span>
                    </div>
                </div>
                <input type="hidden" id="editar_capacidad_total" name="capacidad_total" value="20">
            </div>
            
            <!-- 4) Ubicación en el local -->
            <div class="form-group">
                <label for="editar_ubicacion_local">Ubicación en el Local</label>
                <div class="input-group">
                    <iconify-icon icon="solar:map-point-wave-bold-duotone"></iconify-icon>
                    <input type="text" id="editar_ubicacion_local" name="ubicacion_fisica" placeholder="Ej: Pasillo central, lado izquierdo">
                </div>
            </div>
        </form>
        <div class="modal-footer">
            <button id="btnCancelarEditarEstante" type="button" class="btn btn-secondary">Cancelar</button>
            <button id="btnGuardarEditarEstante" type="submit" form="formEditarEstante" class="btn btn-primary">
                <iconify-icon icon="solar:diskette-bold"></iconify-icon>
                <span>Guardar Cambios</span>
            </button>
        </div>
    </div>
</div>

<!-- Estilos específicos del mapa -->
<style>
    @import url("{{ asset('assets/css/ubicacion/shared/base.css') }}");
    @import url("{{ asset('assets/css/ubicacion/shared/modals.css') }}");
    @import url("{{ asset('assets/css/ubicacion/shared/forms.css') }}");
    @import url("{{ asset('assets/css/ubicacion/shared/buttons.css') }}");
    @import url("{{ asset('assets/css/ubicacion/shared/badges.css') }}");
    @import url("{{ asset('assets/css/ubicacion/mapa/mapa.css') }}");
    @import url("{{ asset('assets/css/ubicacion/productos/tablas.css') }}");

    /* CSS adicional para tipos de ubicación */
    .ubicacion-badge.tipo-venta {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border: 1px solid #3b82f6;
        color: #1e40af;
    }

    .ubicacion-badge.tipo-almacen {
        background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
        border: 1px solid #8b5cf6;
        color: #6b21a8;
    }

    .ubicacion-badge.tipo-estante {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border: 1px solid #3b82f6;
        color: #1e40af;
    }

    .estado-badge.normal {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .estado-badge.stock-bajo {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #f59e0b;
    }

    .estado-badge.stock-critico {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }

    .id-cell {
        text-align: center;
        font-weight: bold;
        color: #374151;
        font-size: 14px;
        min-width: 50px;
    }

    .tabla-productos-ubicados th:first-child,
    .tabla-productos-ubicados td:first-child {
        width: 60px;
        text-align: center;
    }

    /* Estilos para productos sin ubicar */
    .prioridad-badge.alta {
        background: linear-gradient(135deg, #fee2e2, #fca5a5);
        border: 1px solid #ef4444;
        color: #991b1b;
        font-weight: 600;
    }

    .prioridad-badge.media {
        background: linear-gradient(135deg, #fef3c7, #fcd34d);
        border: 1px solid #f59e0b;
        color: #92400e;
        font-weight: 600;
    }

    .prioridad-badge.baja {
        background: linear-gradient(135deg, #dcfce7, #86efac);
        border: 1px solid #22c55e;
        color: #166534;
        font-weight: 600;
    }

    .alerta-estadisticas {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .stat-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
    }

    .stat-item.alta {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }

    .stat-item.media {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #f59e0b;
    }

    .stat-item.baja {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #22c55e;
    }

    .alerta-sin-ubicar-mejorada {
        background: linear-gradient(135deg, #fef7ed, #fed7aa);
        border: 2px solid #f59e0b;
        border-radius: 16px;
        padding: 0;
        margin-bottom: 24px;
        box-shadow: 0 10px 25px rgba(245, 158, 11, 0.15);
        position: relative;
        overflow: hidden;
        max-width: 100%;
    }

    .alerta-sin-ubicar-mejorada::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #f59e0b, #ea580c, #dc2626);
    }

    .alerta-contenido-principal {
        display: flex;
        align-items: center;
        padding: 20px;
        gap: 16px;
        flex-wrap: wrap;
        justify-content: space-between;
    }

    .alerta-icon-principal {
        flex-shrink: 0;
        width: 56px;
        height: 56px;
        background: linear-gradient(135deg, #fed7aa, #fdba74);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid #f59e0b;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .alerta-icon-principal iconify-icon {
        font-size: 28px;
        color: #ea580c;
    }

    .alerta-texto-principal {
        flex: 1;
        min-width: 280px;
        padding-right: 180px;
    }

    .alerta-titulo {
        margin: 0 0 8px 0;
        color: #9a3412;
        font-size: 20px;
        font-weight: 700;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .alerta-descripcion {
        margin: 0 0 12px 0;
        color: #a16207;
        font-size: 16px;
        line-height: 1.5;
    }

    .numero-productos {
        font-weight: 700;
        color: #dc2626;
        font-size: 18px;
        background: #fef2f2;
        padding: 2px 8px;
        border-radius: 6px;
        border: 1px solid #fecaca;
    }

    .alerta-estadisticas-compactas {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }

    .stat-pill.alta {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #991b1b;
        border-color: #ef4444;
    }

    .stat-pill.media {
        background: linear-gradient(135deg, #fef3c7, #fed7aa);
        color: #92400e;
        border-color: #f59e0b;
    }

    .stat-pill.baja {
        background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        color: #166534;
        border-color: #22c55e;
    }

    .alerta-acciones-derecha {
        position: absolute;
        top: 20px;
        right: 80px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 10;
    }

    .btn-alerta-accion {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        min-width: 140px;
        justify-content: center;
    }

    .btn-alerta-accion.primario {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-alerta-accion.primario:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }

    .btn-alerta-accion.secundario {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        color: #475569;
        border: 2px solid #e2e8f0;
    }

    .btn-alerta-accion.secundario:hover {
        background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
        border-color: #94a3b8;
        transform: translateY(-1px);
    }

    .alerta-close-btn {
        position: absolute;
        top: 12px;
        right: 12px;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #6b7280;
    }

    .alerta-close-btn:hover {
        background: white;
        color: #374151;
        transform: scale(1.1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* Header de tabla en rojo medio claro */
    .tabla-productos-sin-ubicar thead tr {
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        border: none !important;
    }

    .tabla-productos-sin-ubicar thead th {
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        color: white !important;
        font-weight: 600 !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid #dc2626 !important;
        border-bottom: 2px solid #dc2626 !important;
        padding: 12px 16px !important;
    }

    .tabla-productos-sin-ubicar thead th:first-child {
        border-left: 1px solid #dc2626 !important;
    }

    .tabla-productos-sin-ubicar thead th:last-child {
        border-right: 1px solid #dc2626 !important;
    }

    /* Responsive para la alerta */
    @media (max-width: 768px) {
        .alerta-contenido-principal {
            flex-direction: column;
            text-align: center;
            padding: 16px;
            justify-content: center;
        }
        
        .alerta-texto-principal {
            min-width: auto;
            padding-right: 0;
        }
        
        .alerta-acciones-derecha {
            position: static;
            display: flex;
            flex-direction: row;
            width: 100%;
            margin-top: 16px;
            top: auto;
            right: auto;
            justify-content: center;
        }
        
        .btn-alerta-accion {
            flex: 1;
            min-width: auto;
        }
        
        .alerta-estadisticas-compactas {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .alerta-acciones-derecha {
            flex-direction: column;
        }
    }

    /* Estilos para modal de SweetAlert2 mejorado */
    .swal2-confirm-custom {
        background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 12px 24px !important;
        font-weight: 600 !important;
        font-size: 14px !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
        transition: all 0.3s ease !important;
    }

    .swal2-confirm-custom:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4) !important;
    }

    .swal2-confirm-custom:focus {
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5) !important;
    }

    /* Estilos para asignación masiva */
    .btn-asignar-masivo-modern:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        background: #9ca3af !important;
    }

    .btn-asignar-masivo-modern:not(:disabled):hover {
        background: #dc2626 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    /* Acciones del encabezado (consejos + nuevo estante) */
    .section-actions-modern {
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .btn-hint-modern {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: 0.75rem 1.25rem; /* mismo tamaño que nuevo estante */
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); /* naranja muy suave */
        color: #374151; /* gris oscuro agradable */
        border: 1px solid #fed7aa; /* borde pastel */
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: var(--transition);
        box-shadow: var(--shadow-soft);
    }
    .btn-hint-modern:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        background: linear-gradient(135deg, #ffe9d5 0%, #fed7aa 100%);
        border-color: #fdba74;
    }
    .btn-hint-modern:focus { outline: none; box-shadow: 0 0 0 3px rgba(253, 186, 116, 0.35); }
    .btn-hint-modern iconify-icon { font-size: 1.1rem; color: #f59e0b; }

    /* Estilo específico del modal Nuevo Estante: header y título en blanco */
    #modalNuevoEstante .modal-header {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        border-bottom: none;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        padding: 1.5rem 1.75rem;
    }
    #modalNuevoEstante .modal-title { color: #ffffff; }
    #modalNuevoEstante .modal-title iconify-icon { color: rgba(255,255,255,0.95); }
    #modalNuevoEstante .modal-close-btn {
        color: #ffffff;
        background: rgba(255,255,255,0.2);
        border: 2px solid rgba(255,255,255,0.25);
        border-radius: 12px;
    }

    /* Espaciado más compacto en el modal Editar Estante */
    #modalEditarEstante .modal-content { gap: 1rem; }

    /* Header Editar Estante: verde medio y texto blanco */
    #modalEditarEstante .modal-header {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: #ffffff;
        border-bottom: none;
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        padding: 1.5rem 1.75rem;
    }
    #modalEditarEstante .modal-title { color: #ffffff; }
    #modalEditarEstante .modal-title iconify-icon { color: rgba(255,255,255,0.95); }
    #modalEditarEstante .modal-close-btn {
        color: #ffffff;
        background: rgba(255,255,255,0.2);
        border: 2px solid rgba(255,255,255,0.25);
        border-radius: 12px;
    }

    /* Botón Guardar Cambios con el mismo verde medio */
    #modalEditarEstante .btn.btn-primary {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        color: #ffffff !important;
        border: none !important;
        box-shadow: var(--shadow-soft) !important;
    }
    #modalEditarEstante .btn.btn-primary:hover {
        transform: translateY(-1px) !important;
        box-shadow: var(--shadow-medium) !important;
    }
    #modalEditarEstante .btn.btn-primary iconify-icon { color: #ffffff !important; }
</style>

<!-- Nota de ayuda al pie eliminada: se muestra mediante SweetAlert al pulsar 'Consejos para crear' -->

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript del Mapa - Estructura Modular -->
<script src="{{ asset('assets/js/ubicacion/mapa/mapa.js') }}" defer></script>
<script src="{{ asset('assets/js/ubicacion/mapa/modal_agregar.js') }}" defer></script>
<script src="{{ asset('assets/js/ubicacion/productos/acciones.js') }}" defer></script>
<script src="{{ asset('assets/js/ubicacion/productos/checkboxes.js') }}" defer></script>

<!-- Scripts para exportaciones -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

<script src="{{ asset('assets/js/ubicacion/productos/productos-ubicados.js') }}" defer></script>
<script src="{{ asset('assets/js/ubicacion/productos/productos-sin-ubicar.js') }}" defer></script>

<!-- Consejos para crear ubicaciones (SweetAlert) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnConsejos = document.getElementById('btnConsejosUbicacion');
    if (!btnConsejos || typeof Swal === 'undefined') return;

    btnConsejos.addEventListener('click', function() {
        Swal.fire({
            icon: 'info',
            title: 'Guía para crear un estante',
            html: `
                <div style="text-align:left; line-height:1.6; font-size:1rem;">
                    <ul style="padding-left:1rem; margin:0;">
                        <li style="margin-bottom:.5rem"><strong>Nombre:</strong> empieza con “Estante”. Ej.: <em>Estante A</em>.</li>
                        <li style="margin-bottom:.5rem"><strong>Convención:</strong> usa letras o números y mantén el mismo esquema (A, B, C… o 1, 2, 3…).</li>
                        <li style="margin-bottom:0"><strong>Zonas internas:</strong> nómbralas con “Almacén” o “Almacén Interno”. Ej.: <em>Almacén Interno – Devoluciones</em>.</li>
                    </ul>
                </div>
            `,
            confirmButtonText: 'Entendido',
            customClass: { confirmButton: 'swal2-confirm-custom' }
        });
    });
});
</script>

@endsection
