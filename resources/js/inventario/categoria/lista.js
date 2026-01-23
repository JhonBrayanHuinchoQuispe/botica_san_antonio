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

let categoriasData = [];
let ordenCol = 'id';
let ordenDir = 'asc';
let registrosPorPagina = 10;
let paginaActual = 1;
let busqueda = '';
let estadoFiltro = 'todos';

function renderCategoriasTabla() {
  let lista = categoriasData.slice();
  // Filtro de búsqueda
  if (busqueda.trim() !== '') {
    const b = busqueda.trim().toLowerCase();
    lista = lista.filter(cat =>
      cat.nombre.toLowerCase().includes(b) ||
      (cat.descripcion && cat.descripcion.toLowerCase().includes(b))
    );
  }
  // Filtro por estado
  if (estadoFiltro !== 'todos') {
    const activoVal = estadoFiltro === 'activo';
    lista = lista.filter(cat => !!cat.activo === activoVal);
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
  const totalPaginas = Math.ceil(total / registrosPorPagina);
  if (paginaActual > totalPaginas) paginaActual = 1;
  const inicio = (paginaActual - 1) * registrosPorPagina;
  const fin = inicio + registrosPorPagina;
  const paginados = lista.slice(inicio, fin);

  const tbody = document.getElementById('categorias-tbody');
  tbody.innerHTML = '';
  if (paginados.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:2rem;">No se encontraron categorías con los filtros actuales.</td></tr>';
    return;
  }
  const getEstadoBadgeHtml = (activo) => {
    return activo
      ? '<span class="estado-badge estado-badge-activo"><span class="dot"></span>Activo</span>'
      : '<span class="estado-badge estado-badge-inactivo"><span class="dot"></span>Inactivo</span>';
  };

  paginados.forEach(cat => {
    const tr = document.createElement('tr');
    tr.dataset.id = cat.id;
    tr.innerHTML = `
      <td data-label="ID">${cat.id}</td>
      <td data-label="Nombre">${cat.nombre}</td>
      <td data-label="Descripción"><span class="desc-with-icon">${cat.descripcion ? '<iconify-icon icon="mdi:comment-text-outline" class="desc-icon"></iconify-icon>' : ''}${cat.descripcion ? cat.descripcion : ''}</span></td>
      <td data-label="Estado" class="estado-cell">${getEstadoBadgeHtml(!!cat.activo)}</td>
      <td data-label="Acciones">
        <button class="tabla-btn edit" data-id="${cat.id}" title="Editar"><iconify-icon icon="lucide:edit"></iconify-icon></button>
        <label class="toggle-switch" title="Activar/Desactivar">
          <input type="checkbox" class="estado-toggle" data-id="${cat.id}" ${cat.activo ? 'checked' : ''}>
          <span class="toggle-slider"></span>
        </label>
      </td>
    `;
    tbody.appendChild(tr);
  });
  // Reasignar manejadores para los toggles recién renderizados
  attachToggleHandlers();
  actualizarSortIcons();

  // Renderizar paginación estilo historial
  renderCategoriasPaginacion({ total, inicio, fin, totalPaginas });
}

function actualizarSortIcons() {
  const cols = ['id', 'nombre'];
  cols.forEach(col => {
    const th = document.querySelector(`th[data-col="${col}"]`);
    if (!th) return;
    const icon = th.querySelector('.sort-icon');
    th.classList.remove('sorted-asc', 'sorted-desc', 'sorted-none');
    if (ordenCol === col) {
      if (ordenDir === 'asc') {
        th.classList.add('sorted-asc');
        icon.innerHTML = '<iconify-icon icon="mdi:arrow-up"></iconify-icon>';
      } else {
        th.classList.add('sorted-desc');
        icon.innerHTML = '<iconify-icon icon="mdi:arrow-down"></iconify-icon>';
      }
    } else {
      th.classList.add('sorted-none');
      icon.innerHTML = '<iconify-icon icon="mdi:arrow-up-down"></iconify-icon>';
    }
  });
}

async function cargarCategorias() {
    try {
        // Mostrar skeleton mientras se cargan los datos desde la API
        const skeleton = document.getElementById('categoriasSkeleton');
        const tbody = document.getElementById('categorias-tbody');
        if (skeleton && tbody) { skeleton.style.display = 'block'; tbody.style.display = 'none'; }
        const res = await fetch(`/inventario/categoria/api`);
        if (!res.ok) throw new Error('Error al recargar las categorías');
        const data = await res.json();
        if (data.success) {
            categoriasData = data.data;
            renderCategoriasTabla();
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'No se pudo actualizar la lista de categorías.', 'error');
    }
    finally {
        const skeleton = document.getElementById('categoriasSkeleton');
        const tbody = document.getElementById('categorias-tbody');
        if (skeleton && tbody) { skeleton.style.display = 'none'; tbody.style.display = ''; }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todas las funcionalidades de la página de categorías
    initCategoriasPage();
});

function initCategoriasPage() {
    if (document.getElementById('categorias-tbody')) {
        // Usar los datos inyectados por Blade para la carga inicial
        // La tabla ya está renderizada por el servidor
        categoriasData = window.categoriasIniciales || []; 
        setupEventListeners();
        actualizarSortIcons(); // Asegurarse de que los íconos de ordenamiento se muestren correctamente
        // Render inicial para que la paginación e información se muestren desde el inicio
        renderCategoriasTabla();
    }
    // Inicializar lógica para los modales SOLO si los elementos existen
    if (document.getElementById('modalAgregarCategoria')) initAgregarCategoria();
    if (document.getElementById('modalEditarCategoria')) initEditarCategoria();
    if (document.getElementById('categorias-tbody')) initEliminarCategoria();
}

function setupEventListeners() {
    // Buscador
    const buscarInput = document.getElementById('buscarCategoria');
    if (buscarInput) {
        buscarInput.addEventListener('input', (e) => {
            busqueda = e.target.value;
            paginaActual = 1;
            renderCategoriasTabla();
        });
    }

    // Registros por página
    const registrosSelect = document.getElementById('registrosPorPagina');
    if (registrosSelect) {
        registrosSelect.addEventListener('change', (e) => {
            registrosPorPagina = parseInt(e.target.value);
            paginaActual = 1;
            renderCategoriasTabla();
        });
    }

    // Filtro Estado
    const estadoSelect = document.getElementById('filtroEstado');
    if (estadoSelect) {
        estadoSelect.addEventListener('change', (e) => {
            estadoFiltro = e.target.value;
            paginaActual = 1;
            renderCategoriasTabla();
        });
    }

    // Cabeceras de tabla para ordenar
    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.getAttribute('data-col');
            if (ordenCol === col) {
                ordenDir = ordenDir === 'asc' ? 'desc' : 'asc';
            } else {
                ordenCol = col;
                ordenDir = 'asc';
            }
            renderCategoriasTabla();
        });
    });
}

function attachToggleHandlers() {
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  document.querySelectorAll('.estado-toggle').forEach(chk => {
    // Evitar duplicar listeners
    if (chk.dataset.bound === '1') return;
    chk.dataset.bound = '1';
    chk.addEventListener('change', async () => {
      const id = chk.getAttribute('data-id');
      try {
        const resp = await fetch(`/inventario/categorias/${id}/cambiar-estado`, {
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
          categoriasData = categoriasData.map(cat => cat.id === idNum ? { ...cat, activo: data.activo, estado: data.activo ? 'activo' : 'inactivo' } : cat);
          // Toast de éxito
          Swal.fire({
            icon: 'success',
            title: data.activo ? 'Categoría activada' : 'Categoría desactivada',
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

function renderCategoriasPaginacion({ total, inicio, fin, totalPaginas }) {
  const infoEl = document.getElementById('categorias-pagination-info');
  const controlsEl = document.getElementById('categorias-pagination-controls');
  if (!infoEl || !controlsEl) return;

  const start = total === 0 ? 0 : inicio + 1;
  const end = Math.min(fin, total);
  infoEl.textContent = `Mostrando ${start} a ${end} de ${total} categorías`;

  const disablePrev = paginaActual === 1 || totalPaginas === 0;
  const disableNext = paginaActual === totalPaginas || totalPaginas === 0;

  // Construir controles
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

  // Listeners
  controlsEl.querySelectorAll('button.historial-pagination-btn').forEach(b => {
    const action = b.getAttribute('data-action');
    b.addEventListener('click', () => {
      if (!action) return;
      if (action === 'first') paginaActual = 1;
      else if (action === 'prev') paginaActual = Math.max(1, paginaActual - 1);
      else if (action.startsWith('page:')) paginaActual = parseInt(action.split(':')[1]);
      else if (action === 'next') paginaActual = Math.min(totalPaginas, paginaActual + 1);
      else if (action === 'last') paginaActual = totalPaginas;
      renderCategoriasTabla();
    });
  });
}
