@extends('layout.layout')
@php
    $title = 'Gestión de Categorías';
    $subTitle = 'Lista de Categorías';
    $script = '<script src="' . asset('assets/js/inventario/categoria/lista.js') . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/categoria/agregar.js') . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/categoria/editar.js') . '"></script>';
    $script .= '<script src="' . asset('assets/js/inventario/categoria/eliminar.js') . '"></script>';
@endphp

<head>
    <title>Lista de Categorías</title>
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/categoria/lista.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/categoria/agregar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/categoria/editar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/categoria/eliminar.css') }}">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

@section('content')
<div class="grid grid-cols-12">
    <div class="col-span-12">
        <div class="card border-0 overflow-hidden">
            
            <div class="card-body">
                <div class="categorias-header mb-8 flex flex-wrap gap-4 items-center justify-between">
                    <div class="flex gap-3 flex-1 items-center">
                        <div class="search-group" style="flex:1;max-width:300px;">
                            <iconify-icon icon="ion:search-outline" class="search-icon"></iconify-icon>
                            <input type="search" id="buscarCategoria" class="search-input" placeholder="Buscar categoría...">
                            <button type="button" id="clearBuscarCategoria" class="search-clear" title="Limpiar">
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
                    <button id="btnNuevaCategoria" class="btn-nueva-categoria flex items-center gap-2 ml-auto">
                        <iconify-icon icon="ic:round-add-circle" class="text-xl"></iconify-icon>
                        <span> Nueva Categoría</span>
                    </button>
                </div>
                <div class="table-hscroll" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table class="tabla-categorias" style="min-width:1100px;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="categorias-tbody">
                            @if(isset($categorias) && $categorias->isNotEmpty())
                                @foreach($categorias as $categoria)
                                    <tr data-id="{{ $categoria->id }}">
                                        <td data-label="ID">{{ $categoria->id }}</td>
                                        <td data-label="Nombre">{{ $categoria->nombre }}</td>
                                        <td data-label="Descripción">{{ $categoria->descripcion ?: '' }}</td>
                                        <td data-label="Estado" class="estado-cell">
                                            @if($categoria->estado === 'activo')
                                                <iconify-icon icon="solar:check-circle-bold" class="estado-icon" style="color:#22c55e;font-size:26px;" title="Activo"></iconify-icon>
                                            @else
                                                <iconify-icon icon="solar:close-circle-bold" class="estado-icon" style="color:#ef4444;font-size:26px;" title="Inactivo"></iconify-icon>
                                            @endif
                                        </td>
                                        <td data-label="Acciones">
                                            <button class="tabla-btn edit" data-id="{{ $categoria->id }}" title="Editar"><iconify-icon icon="lucide:edit"></iconify-icon></button>
                                            <label class="toggle-switch" title="Activar/Desactivar">
                                                <input type="checkbox" class="estado-toggle" data-id="{{ $categoria->id }}" {{ $categoria->estado === 'activo' ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:2rem;">No hay categorías registradas.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="table-hscroll-track"><div class="table-hscroll-thumb"></div></div>
                    <!-- Skeleton loading for Categorías -->
                    <div id="categoriasSkeleton" class="skeleton-table" style="display:none;">
                        @for ($i = 0; $i < 10; $i++)
                            <div class="skeleton-row">
                                <span class="skeleton-bar short"></span>
                                <span class="skeleton-bar medium"></span>
                                <span class="skeleton-bar long"></span>
                                <span class="skeleton-dot"></span>
                                <span class="skeleton-bar actions"></span>
                            </div>
                        @endfor
                    </div>
                </div>
                <!-- Paginación estilo Historial de Ventas -->
                <div id="categorias-pagination" class="historial-pagination-improved">
                    <div class="historial-pagination-info">
                        <p class="text-sm text-gray-700" id="categorias-pagination-info">Mostrando 0 a 0 de 0 categorías</p>
                    </div>
                    <div class="historial-pagination-controls" id="categorias-pagination-controls"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Categoría -->
<div id="modalAgregarCategoria" class="modal-categoria-overlay" style="display:none;">
    <div class="modal-categoria-container">
        <div class="modal-categoria-header">
            <iconify-icon icon="ic:round-add-circle"></iconify-icon>
            <span>Agregar Categoría</span>
        </div>
        <form id="formAgregarCategoria" class="modal-categoria-form">
            <label class="font-semibold">Nombre</label>
            <div class="input-icon-group">
                <iconify-icon icon="mdi:label-outline" class="input-icon"></iconify-icon>
                <input type="text" id="agregarCategoriaNombre" name="nombre" required maxlength="100" class="input-modal-categoria input-with-icon" placeholder="Ej: Analgésicos">
            </div>
            <label class="font-semibold">Descripción</label>
            <div style="margin-bottom: 1.25rem;">
                <textarea id="agregarCategoriaDescripcion" name="descripcion" maxlength="255" class="input-modal-categoria" placeholder="Ej: Para aliviar el dolor" style="padding: 0.8rem 1rem;"></textarea>
            </div>
            <div class="modal-categoria-actions">
                <button type="button" class="btn-modal-categoria btn-cancelar" id="btnCancelarAgregarCategoria">
                    <iconify-icon icon="ic:round-cancel"></iconify-icon> Cancelar
                </button>
                <button type="submit" class="btn-modal-categoria btn-guardar" id="btnGuardarAgregarCategoria">
                    <iconify-icon icon="ic:round-check-circle"></iconify-icon> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Categoría -->
<div id="modalEditarCategoria" class="modal-categoria-overlay" style="display:none;">
    <div class="modal-categoria-container">
        <div class="modal-categoria-header-edit">
            <iconify-icon icon="lucide:edit"></iconify-icon>
            <span>Editar Categoría</span>
        </div>
        <form id="formEditarCategoria" class="modal-categoria-form">
            <input type="hidden" id="editarCategoriaId" name="id">
            <label class="font-semibold">Nombre</label>
            <div class="input-icon-group">
                <iconify-icon icon="mdi:label-outline" class="input-icon"></iconify-icon>
                <input type="text" id="editarCategoriaNombre" name="nombre" required maxlength="100" class="input-modal-categoria input-with-icon">
            </div>
            <label class="font-semibold">Descripción</label>
            <div style="margin-bottom: 1.25rem;">
                <textarea id="editarCategoriaDescripcion" name="descripcion" maxlength="255" class="input-modal-categoria" style="padding: 0.8rem 1rem;"></textarea>
            </div>
            <div class="modal-categoria-actions">
                <button type="button" class="btn-modal-categoria btn-cancelar-edit" id="btnCancelarEditarCategoria">
                    <iconify-icon icon="ic:round-cancel"></iconify-icon> Cancelar
                </button>
                <button type="submit" class="btn-modal-categoria btn-guardar-edit" id="btnGuardarEditarCategoria">
                    <iconify-icon icon="ic:round-check-circle"></iconify-icon> Guardar
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
.registros-por-pagina-select { 
    border:1px solid #E9EDF5; 
    border-radius:12px; 
    background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24'%3E%3Cpath fill='%23666' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") no-repeat right 0.75rem center;
    background-size: 20px;
    padding:.25rem 2.5rem .25rem .75rem; 
    min-height:44px; 
    box-shadow:0 1px 2px rgba(0,0,0,0.035);
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
}
.registros-por-pagina-select:focus { outline:none; border-color:#8b5cf6; box-shadow:0 0 0 2px rgba(139,92,246,.35); background-color:#fff; }
.filtro-estado-group .registros-por-pagina-select { border:1px solid #E9EDF5; }
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
    // Inyectar datos del servidor a JavaScript para una carga inicial rápida
    window.categoriasIniciales = @json($categorias ?? []);
</script>
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
</style>
