@extends('layout.layout')
@php
    $title = 'Gestión de Presentaciones';
    $subTitle = 'Lista de Presentaciones';
    $version = '?v=' . time();
    $script = '<script src="' . asset('assets/js/inventario/presentacion/lista.js') . $version . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/presentacion/agregar.js') . $version . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/presentacion/editar.js') . $version . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/presentacion/eliminar.js') . $version . '"></script>';
@endphp

<head>
    <title>Lista de Presentaciones</title>
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/presentacion/lista.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/presentacion/agregar.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/presentacion/editar.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/presentacion/eliminar.css') }}?v={{ time() }}">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

@section('content')
<div class="grid grid-cols-12">
    <div class="col-span-12">
        <div class="card border-0 overflow-hidden">
            
            <div class="card-body">
                <div class="presentaciones-header mb-8 flex flex-wrap gap-4 items-center justify-between">
                    <div class="flex gap-3 flex-1 items-center">
                        <div class="search-group" style="flex:1;max-width:300px;">
                            <iconify-icon icon="ion:search-outline" class="search-icon"></iconify-icon>
                            <input type="search" id="buscarPresentacion" class="search-input" placeholder="Buscar presentación...">
                            <button type="button" id="clearBuscarPresentacion" class="search-clear" title="Limpiar">
                                <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                            </button>
                        </div>
                        <div class="registros-por-pagina-group ml-2">
                            <label class="registros-label" for="registrosPorPagina">Mostrar</label>
                            <select id="registrosPorPagina" class="registros-por-pagina-select">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                        <div class="filtro-estado-group ml-2">
                            <label class="registros-label" for="filtroEstado">Estado</label>
                            <select id="filtroEstado" class="registros-por-pagina-select">
                                <option value="todos" selected>Todos</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <button id="btnNuevaPresentacion" class="btn-nueva-presentacion flex items-center gap-2 ml-auto">
                        <iconify-icon icon="ic:round-add-circle" class="text-xl"></iconify-icon>
                        <span> Nueva Presentación</span>
                    </button>
                </div>
                <div class="table-hscroll" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table class="tabla-presentaciones" style="min-width:1100px;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="presentaciones-tbody">
                            @if(isset($presentaciones) && $presentaciones->isNotEmpty())
                                @foreach($presentaciones as $presentacion)
                                    <tr data-id="{{ $presentacion->id }}">
                                        <td data-label="ID">{{ $presentacion->id }}</td>
                                        <td data-label="Nombre">{{ $presentacion->nombre }}</td>
                                        <td data-label="Descripción"><span class="desc-with-icon">{{ $presentacion->descripcion ? \Illuminate\Support\Str::limit($presentacion->descripcion, 36, '...') : '' }}</span></td>
                                        
                                        <td data-label="Estado" class="estado-cell">
                                            @if($presentacion->estado === 'activo')
                                                <iconify-icon icon="solar:check-circle-bold" class="estado-icon" style="color:#22c55e;font-size:26px;" title="Activo"></iconify-icon>
                                            @else
                                                <iconify-icon icon="solar:close-circle-bold" class="estado-icon" style="color:#ef4444;font-size:26px;" title="Inactivo"></iconify-icon>
                                            @endif
                                        </td>
                                        <td data-label="Acciones">
                                            <button class="tabla-btn edit" data-id="{{ $presentacion->id }}" title="Editar"><iconify-icon icon="lucide:edit"></iconify-icon></button>
                                            <label class="toggle-switch" title="Activar/Desactivar">
                                                <input type="checkbox" class="estado-toggle" data-id="{{ $presentacion->id }}" {{ $presentacion->estado === 'activo' ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:2rem;">No hay presentaciones registradas.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="table-hscroll-track"><div class="table-hscroll-thumb"></div></div>
                    <!-- Skeleton loading for Presentaciones -->
                    <div id="presentacionesSkeleton" class="skeleton-table" style="display:none;">
                        @for ($i = 0; $i < 10; $i++)
                            <div class="skeleton-row">
                                <span class="skeleton-bar short"></span>
                                <span class="skeleton-bar medium"></span>
                                <span class="skeleton-bar long"></span>
                                <span class="skeleton-bar medium"></span>
                                <span class="skeleton-bar short"></span>
                                <span class="skeleton-bar short"></span>
                                <span class="skeleton-dot"></span>
                                <span class="skeleton-bar actions"></span>
                            </div>
                        @endfor
                    </div>
                </div>
                <!-- Paginación estilo Historial de Ventas -->
                <div id="presentaciones-pagination" class="historial-pagination-improved">
                    <div class="historial-pagination-info">
                        <p class="text-sm text-gray-700" id="presentaciones-pagination-info">Mostrando 0 a 0 de 0 presentaciones</p>
                    </div>
                    <div class="historial-pagination-controls" id="presentaciones-pagination-controls"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Presentación -->
<div id="modalAgregarPresentacion" class="modal-presentacion-overlay" style="display:none;">
    <div class="modal-presentacion-container">
        <div class="modal-presentacion-header">
            <iconify-icon icon="ic:round-add-circle"></iconify-icon>
            <span>Agregar Presentación</span>
        </div>
        <form id="formAgregarPresentacion" class="modal-presentacion-form">
            <label class="font-semibold">Nombre</label>
            <div class="input-icon-group">
                <iconify-icon icon="ion:ribbon-outline" class="input-icon"></iconify-icon>
                <input type="text" id="agregarPresentacionNombre" name="nombre" required maxlength="100" class="input-modal-presentacion input-with-icon" placeholder="Ej: Tabletas">
            </div>
            <label class="font-semibold">Descripción</label>
            <textarea id="agregarPresentacionDescripcion" name="descripcion" maxlength="255" class="input-modal-presentacion" placeholder="Ej: Caja con 20 tabletas"></textarea>
            
            <div class="modal-presentacion-actions">
                <button type="button" class="btn-modal-presentacion btn-cancelar" id="btnCancelarAgregarPresentacion">
                    <iconify-icon icon="ic:round-cancel"></iconify-icon> Cancelar
                </button>
                <button type="submit" class="btn-modal-presentacion btn-guardar" id="btnGuardarAgregarPresentacion">
                    <iconify-icon icon="ic:round-check-circle"></iconify-icon> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Presentación -->
<div id="modalEditarPresentacion" class="modal-presentacion-overlay" style="display:none;">
    <div class="modal-presentacion-container">
        <div class="modal-presentacion-header-edit">
            <iconify-icon icon="lucide:edit"></iconify-icon>
            <span>Editar Presentación</span>
        </div>
        <form id="formEditarPresentacion" class="modal-presentacion-form">
            <input type="hidden" id="editarPresentacionId" name="id">
            <label class="font-semibold">Nombre</label>
            <div class="input-icon-group">
                <iconify-icon icon="ion:ribbon-outline" class="input-icon"></iconify-icon>
                <input type="text" id="editarPresentacionNombre" name="nombre" required maxlength="100" class="input-modal-presentacion input-with-icon">
            </div>
            <label class="font-semibold">Descripción</label>
            <textarea id="editarPresentacionDescripcion" name="descripcion" maxlength="255" class="input-modal-presentacion"></textarea>
            
            <div class="modal-presentacion-actions">
                <button type="button" class="btn-modal-presentacion btn-cancelar-edit" id="btnCancelarEditarPresentacion">
                    <iconify-icon icon="ic:round-cancel"></iconify-icon> Cancelar
                </button>
                <button type="submit" class="btn-modal-presentacion btn-guardar-edit" id="btnGuardarEditarPresentacion">
                    <iconify-icon icon="ic:round-check-circle"></iconify-icon> Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

<style>
/* Toggle switch minimal styles */
.tabla-presentaciones td { vertical-align: middle; }
.estado-cell { text-align: center; }
.tabla-presentaciones td[data-label="Acciones"] { display: flex; align-items: center; gap: 12px; }
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; margin-left: 8px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e5e7eb; transition: .2s; border-radius: 999px; }
.toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .2s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.toggle-switch input:checked + .toggle-slider { background-color: #10b981; }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }
/* Inputs con ícono (igual que Categorías) */
.input-icon-group { position: relative; }
.input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 18px; }
.input-with-icon { padding-left: 2.5rem; }
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
/* Campos de presentación (grid y chips) */
.presentacion-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 8px; }
.form-field label { font-size:.85rem; color:#374151; display:block; margin-bottom:6px; }
.form-field .hint { color:#6b7280; font-size:.75rem; }
.chips { display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
.chip { padding: 4px 8px; font-size:.75rem; border-radius:9999px; background:#f1f5f9; color:#374151; border:1px solid #e5e7eb; display:inline-flex; align-items:center; gap:6px; }
.chip iconify-icon { font-size:16px; color:#64748b; }
/* Skeleton loading (Presentaciones) */
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
/* Estado badges y descripción con icono */
.estado-badge { display:inline-flex; align-items:center; gap:.4rem; padding:.25rem .6rem; border-radius:9999px; font-weight:600; font-size:.85rem; }
.estado-badge .dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
.estado-badge-activo { background:#d1fae5; color:#059669; }
.estado-badge-activo .dot { background:#22c55e; }
.estado-badge-inactivo { background:#fee2e2; color:#dc2626; }
.estado-badge-inactivo .dot { background:#ef4444; }
.desc-with-icon { display:inline-flex; align-items:center; gap:.4rem; color:#374151; }
.desc-icon { color:#64748b; font-size:18px; }
/* Scroll horizontal personalizado bajo la tabla */
.table-hscroll-track{height:10px;background:#e5e7eb;border-radius:9999px;margin-top:10px;position:relative;display:none;width:100%}
.table-hscroll-thumb{height:10px;background:#9ca3af;border-radius:9999px;width:60px;position:absolute;left:0}
.table-hscroll{scrollbar-width:none}
.table-hscroll::-webkit-scrollbar{display:none}
</style>

<script>
    // Inyectar datos del servidor a JavaScript para una carga inicial rápida
    window.presentacionesIniciales = @json($presentaciones ?? []);
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        // Mostrar/ocultar botón limpiar del buscador
        const buscarInput = document.getElementById('buscarPresentacion');
        const clearBtn = document.getElementById('clearBuscarPresentacion');
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
                    const resp = await fetch(`{{ route('inventario.presentaciones.cambiar-estado', ['id' => 'ID_PLACEHOLDER']) }}`.replace('ID_PLACEHOLDER', id), {
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

        const wrapper = document.querySelector('.table-hscroll');
        const track = document.querySelector('.table-hscroll-track');
        const thumb = document.querySelector('.table-hscroll-thumb');
        if (wrapper && track && thumb) {
            const updateThumb = () => {
                const sw = wrapper.scrollWidth, cw = wrapper.clientWidth, max = sw - cw;
                track.style.display = (sw > cw) ? 'block' : 'none';
                const ratio = cw / sw;
                thumb.style.width = Math.max(60, track.clientWidth * ratio) + 'px';
                const pos = (max > 0) ? (wrapper.scrollLeft / max) * (track.clientWidth - thumb.offsetWidth) : 0;
                thumb.style.left = pos + 'px';
            };
            wrapper.addEventListener('scroll', updateThumb);
            new ResizeObserver(updateThumb).observe(wrapper);
            window.addEventListener('resize', updateThumb);
            updateThumb();
            let dragging=false, sx=0, sl=0;
            thumb.addEventListener('mousedown', e=>{dragging=true;sx=e.clientX;sl=parseFloat(thumb.style.left||'0');e.preventDefault();});
            window.addEventListener('mouseup', ()=>{dragging=false});
            window.addEventListener('mousemove', e=>{if(!dragging)return;const delta=e.clientX-sx;const tw=track.clientWidth-thumb.offsetWidth;let nl=Math.max(0,Math.min(tw,sl+delta));thumb.style.left=nl+'px';const max=wrapper.scrollWidth-wrapper.clientWidth;wrapper.scrollLeft=(tw>0)?(nl/tw)*max:0;});
        }
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
</style>
