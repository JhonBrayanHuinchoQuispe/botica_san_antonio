// Íconos sugeridos para variedad visual
const iconosCategoria = {
  blue: 'mdi:pill',
  red: 'mdi:virus-outline',
  yellow: 'mdi:emoticon-happy-outline',
  green: 'mdi:leaf',
  purple: 'mdi:flask-outline',
  default: 'mdi:apps-box',
};
const colores = ['blue', 'red', 'yellow', 'green', 'purple'];

let presentacionesData = [];
let ordenCol = 'id';
let ordenDir = 'asc';
let registrosPorPagina = 10;
let paginaActual = 1;
let busqueda = '';
let estadoFiltro = 'todos';

function renderPresentacionesTabla() {
  let lista = presentacionesData.slice();
  // Filtro de búsqueda
  if (busqueda.trim() !== '') {
    const b = busqueda.trim().toLowerCase();
    lista = lista.filter(item =>
      item.nombre.toLowerCase().includes(b) ||
      (item.descripcion && item.descripcion.toLowerCase().includes(b))
    );
  }
  // Filtro por estado
  if (estadoFiltro !== 'todos') {
    const activoVal = estadoFiltro === 'activo';
    lista = lista.filter(item => !!item.activo === activoVal);
  }
  // Ordenamiento
  if (ordenCol) {
    lista.sort((a, b) => {
      let vA = a[ordenCol], vB = b[ordenCol];
      if (ordenCol === 'nombre') {
        vA = vA.toLowerCase(); vB = vB.toLowerCase();
      }
      if (vA < vB) return ordenDir === 'asc' ? -1 : 1;
      if (vA > vB) return ordenDir === 'asc' ? 1 : -1;
      return 0;
    });
  }
  // Paginación
  const total = lista.length;
  const totalPaginas = Math.ceil(total / registrosPorPagina) || 1;
  if (paginaActual > totalPaginas) paginaActual = 1;
  const inicio = (paginaActual - 1) * registrosPorPagina;
  const fin = inicio + registrosPorPagina;
  const paginados = lista.slice(inicio, fin);

  const tbody = document.getElementById('presentaciones-tbody');
  tbody.innerHTML = '';
  if (paginados.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:2rem;">No se encontraron presentaciones.</td></tr>';
    return;
  }
  const getEstadoBadgeHtml = (activo) => {
    return activo
      ? '<span class="estado-badge estado-badge-activo"><span class="dot"></span>Activo</span>'
      : '<span class="estado-badge estado-badge-inactivo"><span class="dot"></span>Inactivo</span>';
  };

  const truncateDesc = (txt) => {
    if (!txt) return '';
    const max = 120;
    return txt.length > max ? txt.slice(0, max).trim() + '...' : txt;
  };

  paginados.forEach(item => {
    const tr = document.createElement('tr');
    tr.dataset.id = item.id;
    
    tr.innerHTML = `
      <td data-label="ID">${item.id}</td>
      <td data-label="Nombre">${item.nombre}</td>
      <td data-label="Descripción"><span class="desc-with-icon">${item.descripcion ? '<iconify-icon icon="mdi:comment-text-outline" class="desc-icon"></iconify-icon>' : ''}${item.descripcion ? truncateDesc(item.descripcion) : ''}</span></td>
      <td data-label="Estado" class="estado-cell">${getEstadoBadgeHtml(!!item.activo)}</td>
      <td data-label="Acciones">
        <button class="tabla-btn edit" data-id="${item.id}" title="Editar"><iconify-icon icon="lucide:edit"></iconify-icon></button>
        <label class="toggle-switch" title="Activar/Desactivar">
          <input type="checkbox" class="estado-toggle" data-id="${item.id}" ${item.activo ? 'checked' : ''}>
          <span class="toggle-slider"></span>
        </label>
      </td>
    `;
    tbody.appendChild(tr);
  });
  attachToggleHandlers();
  actualizarSortIcons();

  // Renderizar paginación estilo historial
  renderPresentacionesPaginacion({ total, inicio, fin, totalPaginas });
}

function actualizarSortIcons() {
  document.querySelectorAll('th.sortable').forEach(th => {
      const icon = th.querySelector('.sort-icon');
      if(!icon) return;
      const col = th.dataset.col;
      th.classList.remove('sorted-asc', 'sorted-desc', 'sorted-none');
      if (ordenCol === col) {
          icon.innerHTML = ordenDir === 'asc' ? '<iconify-icon icon="mdi:arrow-up"></iconify-icon>' : '<iconify-icon icon="mdi:arrow-down"></iconify-icon>';
          th.classList.add(ordenDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
      } else {
          icon.innerHTML = '<iconify-icon icon="mdi:arrow-up-down"></iconify-icon>';
          th.classList.add('sorted-none');
      }
  });
}

async function cargarPresentaciones() {
    try {
        // Mostrar skeleton mientras se cargan los datos desde la API
        const skeleton = document.getElementById('presentacionesSkeleton');
        const tbody = document.getElementById('presentaciones-tbody');
        if (skeleton && tbody) { skeleton.style.display = 'block'; tbody.style.display = 'none'; }
        const res = await fetch(`/inventario/presentacion/api`);
        const data = await res.json();
        if (data.success) {
            presentacionesData = data.data;
            renderPresentacionesTabla();
        }
    } catch (error) {
        Swal.fire('Error', 'No se pudo actualizar la lista de presentaciones.', 'error');
    }
    finally {
        const skeleton = document.getElementById('presentacionesSkeleton');
        const tbody = document.getElementById('presentaciones-tbody');
        if (skeleton && tbody) { skeleton.style.display = 'none'; tbody.style.display = ''; }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('presentaciones-tbody')) {
        presentacionesData = window.presentacionesIniciales || []; 
        setupEventListeners();
        actualizarSortIcons();
        initAgregarPresentacion();
        initEditarPresentacion();
        initEliminarPresentacion();
        // Render inicial para que la paginación e información se muestren desde el inicio
        renderPresentacionesTabla();
    }
});

function setupEventListeners() {
    const buscarInput = document.getElementById('buscarPresentacion');
    if (buscarInput) {
        buscarInput.addEventListener('input', e => {
            busqueda = e.target.value;
            paginaActual = 1;
            renderPresentacionesTabla();
        });
    }

    const registrosSelect = document.getElementById('registrosPorPagina');
    if (registrosSelect) {
        registrosSelect.addEventListener('change', e => {
            registrosPorPagina = parseInt(e.target.value);
            paginaActual = 1;
            renderPresentacionesTabla();
        });
    }

    // Filtro Estado
    const estadoSelect = document.getElementById('filtroEstado');
    if (estadoSelect) {
        estadoSelect.addEventListener('change', e => {
            estadoFiltro = e.target.value;
            paginaActual = 1;
            renderPresentacionesTabla();
        });
    }

    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.getAttribute('data-col');
            if (ordenCol === col) {
                ordenDir = ordenDir === 'asc' ? 'desc' : 'asc';
            } else {
                ordenCol = col;
                ordenDir = 'asc';
            }
            renderPresentacionesTabla();
        });
    });
}

function renderPresentacionesPaginacion({ total, inicio, fin, totalPaginas }) {
  const infoEl = document.getElementById('presentaciones-pagination-info');
  const controlsEl = document.getElementById('presentaciones-pagination-controls');
  if (!infoEl || !controlsEl) return;

  const start = total === 0 ? 0 : inicio + 1;
  const end = Math.min(fin, total);
  infoEl.textContent = `Mostrando ${start} a ${end} de ${total} presentaciones`;

  const disablePrev = paginaActual === 1 || totalPaginas === 0;
  const disableNext = paginaActual === totalPaginas || totalPaginas === 0;

  const rangeStart = Math.max(1, paginaActual - 2);
  const rangeEnd = Math.min(totalPaginas, paginaActual + 2);

  const btn = (label, disabled, action, current=false) => {
    if (disabled) {
      return `<span class="historial-pagination-btn historial-pagination-btn-disabled">${label}</span>`;
    }
    if (current) {
      return `<span class="historial-pagination-btn historial-pagination-btn-current">${label}</span>`;
    }
    return `<button class="historial-pagination-btn" data-action="${action}">${label}</button>`;
  };

  let html = '';
  html += btn('Primera', disablePrev, 'first');
  html += btn('‹ Anterior', disablePrev, 'prev');
  for (let p = rangeStart; p <= rangeEnd; p++) {
    html += btn(String(p), false, `page:${p}`, p === paginaActual);
  }
  html += btn('Siguiente ›', disableNext, 'next');
  html += btn('Última', disableNext, 'last');
  controlsEl.innerHTML = html;

  controlsEl.querySelectorAll('button.historial-pagination-btn').forEach(b => {
    const action = b.getAttribute('data-action');
    b.addEventListener('click', () => {
      if (action === 'first') paginaActual = 1;
      else if (action === 'prev') paginaActual = Math.max(1, paginaActual - 1);
      else if (action && action.startsWith('page:')) paginaActual = parseInt(action.split(':')[1]);
      else if (action === 'next') paginaActual = Math.min(totalPaginas, paginaActual + 1);
      else if (action === 'last') paginaActual = totalPaginas;
      renderPresentacionesTabla();
    });
  });
}

function attachToggleHandlers() {
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  document.querySelectorAll('.estado-toggle').forEach(chk => {
    if (chk.dataset.bound === '1') return;
    chk.dataset.bound = '1';
    chk.addEventListener('change', async () => {
      const id = chk.getAttribute('data-id');
      try {
        const resp = await fetch(`/inventario/presentaciones/${id}/cambiar-estado`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const data = await resp.json();
        if (data.success) {
          const row = chk.closest('tr');
          const estadoCell = row.querySelector('td.estado-cell');
          estadoCell.innerHTML = data.activo
            ? '<span class="estado-badge estado-badge-activo"><span class="dot"></span>Activo</span>'
            : '<span class="estado-badge estado-badge-inactivo"><span class="dot"></span>Inactivo</span>';
          // Actualizar dataset para filtros futuros
          const idNum = parseInt(id);
          presentacionesData = presentacionesData.map(p => p.id === idNum ? { ...p, activo: data.activo, estado: data.activo ? 'activo' : 'inactivo' } : p);
          // Toast de éxito
          Swal.fire({
            icon: 'success',
            title: data.activo ? 'Presentación activada' : 'Presentación desactivada',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500
          });
        }
      } catch (e) {
        console.error(e);
        alert('No se pudo cambiar el estado');
      }
    });
  });
}
