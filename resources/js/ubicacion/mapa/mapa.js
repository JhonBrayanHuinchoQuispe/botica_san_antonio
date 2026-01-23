// ===============================================
// MAPA DE ALMACÉN - ARCHIVO PRINCIPAL
// ===============================================

class MapaAlmacen {
    constructor() {
        this.tabActual = this.obtenerTabDesdeURL() || 'tab-mapa';
        this.filtros = {
            productos: {
                busqueda: '',
                estante: '',
                estado: ''
            },
            sinUbicar: {
                busqueda: '',
                categoria: '',
                prioridad: ''
            }
        };
        this.paginacion = {
            productos: { pagina: 1, totalPorPagina: 10 },
            sinUbicar: { pagina: 1, totalPorPagina: 10 }
        };
        this.init();
    }

    init() {
        console.log('🗺️ Inicializando Mapa de Almacén...');
        this.estanteAEliminar = null;
        this.estanteEnEdicion = null;
        this.configurarPestañas();
        this.activarTabInicial();
        this.configurarEventosURL();
        this.configurarBuscadores();
        this.configurarFiltros();
        this.configurarAcciones();
        this.configurarModales();
        this.configurarPaginacion();
        this.configurarCheckboxes();
        this.configurarAlertas();
        this.setupCalculoAutomaticoCapacidad();
        
        // Cálculo inicial cuando se carga la página
        setTimeout(() => {
            this.calcularCapacidadTotal();
        }, 100);
        
        // Cargar datos iniciales si estamos en la pestaña de mapa
        if (this.tabActual === 'tab-mapa') {
            console.log('🚀 Cargando estantes automáticamente...');
            this.actualizarResumenEstantes();
        }
        
        console.log('✅ Mapa de Almacén listo');
    }

    // ==================== PESTAÑAS ====================
    configurarPestañas() {
        const tabs = document.querySelectorAll('.tab-link-modern');
        const contenidos = document.querySelectorAll('.tab-content-modern');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.dataset.tab;
                this.cambiarTab(tabId, true);
            });
        });
    }

    onCambiarTab(tabId) {
        console.log(`📋 Cambiando a pestaña: ${tabId}`);
        
        switch(tabId) {
            case 'tab-mapa':
                console.log('🗺️ Cargando vista de mapa...');
                this.actualizarResumenEstantes();
                break;
            case 'tab-listado-ubicados':
                this.cargarProductosUbicados();
                break;
            case 'tab-listado-sin-ubicar':
                this.cargarProductosSinUbicar();
                break;
        }
    }

    // ==================== GESTIÓN DE URL Y ESTADO ====================
    obtenerTabDesdeURL() {
        const hash = window.location.hash.substring(1);
        const tabsValidas = ['tab-mapa', 'tab-listado-ubicados', 'tab-listado-sin-ubicar'];
        
        // Mapeo de hashes amigables a IDs de tabs
        const hashToTab = {
            'mapa': 'tab-mapa',
            'productos-ubicados': 'tab-listado-ubicados',
            'productos-sin-ubicar': 'tab-listado-sin-ubicar'
        };
        
        if (hash && hashToTab[hash]) {
            return hashToTab[hash];
        }
        
        if (hash && tabsValidas.includes(hash)) {
            return hash;
        }
        
        return null;
    }

    activarTabInicial() {
        // Activar la pestaña que corresponde según la URL
        this.cambiarTab(this.tabActual, false);
    }

    configurarEventosURL() {
        // Escuchar cambios en el hash de la URL (botón atrás/adelante)
        window.addEventListener('hashchange', () => {
            const nuevoTab = this.obtenerTabDesdeURL();
            if (nuevoTab && nuevoTab !== this.tabActual) {
                this.cambiarTab(nuevoTab, false);
            }
        });
    }

    cambiarTab(tabId, actualizarURL = true) {
        const tabs = document.querySelectorAll('.tab-link-modern');
        const contenidos = document.querySelectorAll('.tab-content-modern');
        
        // Verificar que el tab existe
        if (!document.getElementById(tabId)) {
            console.warn(`Tab ${tabId} no encontrado`);
            return;
        }

        // Actualizar pestañas activas
        tabs.forEach(t => t.classList.remove('active'));
        contenidos.forEach(c => c.classList.add('hidden'));
        
        // Activar la pestaña seleccionada
        const tabButton = document.querySelector(`[data-tab="${tabId}"]`);
        if (tabButton) {
            tabButton.classList.add('active');
        }
        
        document.getElementById(tabId).classList.remove('hidden');
        
        // Actualizar estado interno
        this.tabActual = tabId;
        
        // Actualizar URL si es necesario
        if (actualizarURL) {
            this.actualizarURL(tabId);
        }
        
        // ✨ ACTUALIZAR SIDEBAR - Sincronizar con el menú lateral
        this.actualizarSidebarActivo(tabId);
        
        // Ejecutar lógica específica del tab
        this.onCambiarTab(tabId);
    }

    actualizarURL(tabId) {
        // Mapeo de IDs de tabs a hashes amigables
        const tabToHash = {
            'tab-mapa': 'mapa',
            'tab-listado-ubicados': 'productos-ubicados',
            'tab-listado-sin-ubicar': 'productos-sin-ubicar'
        };
        
        const hash = tabToHash[tabId] || tabId;
        
        // Actualizar URL sin recargar la página
        if (window.history.pushState) {
            const newUrl = window.location.protocol + "//" + 
                          window.location.host + 
                          window.location.pathname + 
                          '#' + hash;
            window.history.pushState({path: newUrl}, '', newUrl);
        } else {
            // Fallback para navegadores antiguos
            window.location.hash = hash;
        }
    }

    actualizarSidebarActivo(tabId) {
        // Solo actualizar si estamos en la página de ubicaciones/mapa
        if (!window.location.pathname.includes('/ubicaciones/mapa')) {
            return;
        }

        // Mapeo de IDs de tabs a hashes para el sidebar
        const tabToHash = {
            'tab-mapa': '#mapa',
            'tab-listado-ubicados': '#productos-ubicados',
            'tab-listado-sin-ubicar': '#productos-sin-ubicar'
        };
        
        const currentHash = tabToHash[tabId];
        if (!currentHash) return;
        
        // Remover active-page de todos los submenús de Almacén
        const almacenSubmenuLinks = document.querySelectorAll('a[href*="#mapa"], a[href*="#productos-ubicados"], a[href*="#productos-sin-ubicar"]');
        almacenSubmenuLinks.forEach(function(link) {
            link.classList.remove('active-page');
            link.parentElement.classList.remove('active-page');
        });
        
        // Activar el submenú correcto
        let targetLink = null;
        if (currentHash === '#productos-ubicados') {
            targetLink = document.querySelector('a[href*="#productos-ubicados"]');
        } else if (currentHash === '#productos-sin-ubicar') {
            targetLink = document.querySelector('a[href*="#productos-sin-ubicar"]');
        } else {
            // Default para "Mapa del Almacén"
            targetLink = document.querySelector('a[href*="#mapa"]');
        }
        
        if (targetLink) {
            targetLink.classList.add('active-page');
            targetLink.parentElement.classList.add('active-page');
            
            // Asegurar que el dropdown de Almacén esté abierto
            const almacenDropdown = targetLink.closest('.dropdown');
            if (almacenDropdown) {
                almacenDropdown.classList.add('dropdown-open', 'open', 'show');
                const submenu = almacenDropdown.querySelector('.sidebar-submenu');
                if (submenu) {
                    submenu.style.display = 'block';
                }
            }
        }
        
        console.log(`🔄 Sidebar actualizado para pestaña: ${tabId} → ${currentHash}`);
    }

    // ==================== BUSCADORES ====================
    configurarBuscadores() {
        // Buscador de productos ubicados
        const buscarUbicados = document.getElementById('buscarProductosUbicados');
        if (buscarUbicados) {
            buscarUbicados.addEventListener('input', (e) => {
                this.filtros.productos.busqueda = e.target.value.toLowerCase();
                this.filtrarProductosUbicados();
            });
        }

        // Buscador de productos sin ubicar
        const buscarSinUbicar = document.getElementById('buscarProductosSinUbicar');
        if (buscarSinUbicar) {
            buscarSinUbicar.addEventListener('input', (e) => {
                this.filtros.sinUbicar.busqueda = e.target.value.toLowerCase();
                this.filtrarProductosSinUbicar();
            });
        }
    }

    // ==================== FILTROS ====================
    configurarFiltros() {
        // Filtros para productos ubicados
        const filtroEstante = document.getElementById('filtroEstante');
        const filtroEstado = document.getElementById('filtroEstado');
        
        if (filtroEstante) {
            filtroEstante.addEventListener('change', (e) => {
                this.filtros.productos.estante = e.target.value;
                this.filtrarProductosUbicados();
            });
        }

        if (filtroEstado) {
            filtroEstado.addEventListener('change', (e) => {
                this.filtros.productos.estado = e.target.value;
                this.filtrarProductosUbicados();
            });
        }

        // Filtros para productos sin ubicar
        const filtroCategoria = document.getElementById('filtroCategoria');
        const filtroPrioridad = document.getElementById('filtroPrioridad');
        
        if (filtroCategoria) {
            filtroCategoria.addEventListener('change', (e) => {
                this.filtros.sinUbicar.categoria = e.target.value;
                this.filtrarProductosSinUbicar();
            });
        }

        if (filtroPrioridad) {
            filtroPrioridad.addEventListener('change', (e) => {
                this.filtros.sinUbicar.prioridad = e.target.value;
                this.filtrarProductosSinUbicar();
            });
        }
    }

    // ==================== ACCIONES ====================
    configurarAcciones() {
        // Botones de exportar
        const btnExportar = document.querySelector('.btn-exportar-modern');
        if (btnExportar) {
            btnExportar.addEventListener('click', () => this.exportarDatos());
        }

        // Botón de asignar masivo
        const btnAsignarMasivo = document.querySelector('.btn-asignar-masivo-modern');
        if (btnAsignarMasivo) {
            btnAsignarMasivo.addEventListener('click', () => this.abrirAsignacionMasiva());
        }

        // Botones de acción en tablas
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-accion-tabla');
            if (btn) {
                const accion = btn.classList.contains('ver') ? 'ver' : 
                              btn.classList.contains('editar') ? 'editar' :
                              btn.classList.contains('mover') ? 'mover' :
                              btn.classList.contains('asignar') ? 'asignar' : null;
                
                if (accion) {
                    const producto = btn.dataset.producto;
                    this.ejecutarAccion(accion, producto);
                }
            }
        });

        // Botón actualizar foto
        const btnActualizarFoto = document.querySelector('.btn-actualizar-foto');
        if (btnActualizarFoto) {
            btnActualizarFoto.addEventListener('click', () => this.actualizarFotoAlmacen());
        }

        // Botón nuevo estante
        const btnNuevoEstante = document.getElementById('btnNuevoEstante');
        if (btnNuevoEstante) {
            btnNuevoEstante.addEventListener('click', () => {
                // Llamar a la función global definida en modal_agregar.js
                if (typeof window.abrirModalAgregarEstante === 'function') {
                    window.abrirModalAgregarEstante();
                } else {
                    console.error('❌ Función abrirModalAgregarEstante no está disponible');
                }
            });
        }

        // Event delegation para botones de eliminar y editar estante
        document.addEventListener('click', (e) => {
            // Navegación robusta al detalle del estante (por si algún handler bloquea el enlace)
            const link = e.target.closest('.estante-link-area');
            if (link) {
                // Evitar interferencia con otros elementos interactivos dentro de la tarjeta
                const isActionButton = e.target.closest('.btn-accion-estante');
                if (!isActionButton) {
                    e.preventDefault();
                    if (typeof e.stopImmediatePropagation === 'function') {
                        e.stopImmediatePropagation();
                    }
                    const href = link.getAttribute('href');
                    console.log('➡️ Navegando al detalle de estante via link:', href);
                    window.location.href = href; // usar ruta relativa
                    return;
                }
            }

            // Verificar si el clic fue EXACTAMENTE en un botón de eliminar o su icono
            if (e.target.matches('.btn-eliminar-estante, .btn-eliminar-estante *, .btn-eliminar-estante iconify-icon')) {
                const btnEliminar = e.target.closest('.btn-eliminar-estante');
                if (btnEliminar) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🗑️ Botón de eliminar clicado:', btnEliminar);
                    console.log('📋 Datos del botón:', {
                        estanteId: btnEliminar.dataset.estanteId,
                        estanteNombre: btnEliminar.dataset.estanteNombre,
                        productosActuales: btnEliminar.dataset.productosActuales
                    });
                    this.abrirModalEliminarEstante(btnEliminar);
                    return;
                }
            }

            // Verificar si el clic fue EXACTAMENTE en un botón de editar o su icono
            if (e.target.matches('.btn-editar-estante, .btn-editar-estante *, .btn-editar-estante iconify-icon')) {
                const btnEditar = e.target.closest('.btn-editar-estante');
                if (btnEditar) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('✏️ Botón de editar clicado:', btnEditar);
                    console.log('📋 Datos del botón:', {
                        estanteId: btnEditar.dataset.estanteId
                    });
                    this.abrirModalEditarEstante(btnEditar.dataset.estanteId);
                    return;
                }
            }
        });

        // Modal de eliminación - botones
        const btnCancelarEliminacion = document.getElementById('btnCancelarEliminacion');
        const btnConfirmarEliminacion = document.getElementById('btnConfirmarEliminacion');
        
        if (btnCancelarEliminacion) {
            btnCancelarEliminacion.addEventListener('click', () => this.cerrarModalEliminarEstante(true));
        }
        
        if (btnConfirmarEliminacion) {
            btnConfirmarEliminacion.addEventListener('click', () => this.confirmarEliminarEstante());
        }

        // Cerrar modal al hacer clic fuera
        const modalEliminarEstante = document.getElementById('modalEliminarEstante');
        if (modalEliminarEstante) {
            modalEliminarEstante.addEventListener('click', (e) => {
                if (e.target === modalEliminarEstante) {
                    this.cerrarModalEliminarEstante(true);
                }
            });
        }

        // Event listeners para modal de edición
        const btnCerrarEditarEstante = document.getElementById('btnCerrarEditarEstante');
        const btnCancelarEditarEstante = document.getElementById('btnCancelarEditarEstante');
        const btnGuardarEditarEstante = document.getElementById('btnGuardarEditarEstante');
        const formEditarEstante = document.getElementById('formEditarEstante');

        if (btnCerrarEditarEstante) {
            btnCerrarEditarEstante.addEventListener('click', () => this.cerrarModalEditarEstante());
        }

        if (btnCancelarEditarEstante) {
            btnCancelarEditarEstante.addEventListener('click', () => this.cerrarModalEditarEstante());
        }

        if (formEditarEstante) {
            formEditarEstante.addEventListener('submit', (e) => {
                e.preventDefault();
                this.guardarCambiosEstante();
            });
        }

        // Cerrar modal de edición al hacer clic fuera
        const modalEditarEstante = document.getElementById('modalEditarEstante');
        if (modalEditarEstante) {
            modalEditarEstante.addEventListener('click', (e) => {
                if (e.target === modalEditarEstante) {
                    this.cerrarModalEditarEstante();
                }
            });
        }
    }

    // ==================== FUNCIONES DE DATOS ====================
    actualizarResumenEstantes() {
        console.log('📊 Actualizando resumen de estantes...');
        
        // Mostrar loading - el loading ya está en el HTML
        const mapaContainer = document.querySelector('.warehouse-container-modern');
        console.log('🔄 Contenedor encontrado para loading:', !!mapaContainer);
        
        // Construir URL correcta dinámicamente
        const apiUrl = window.location.origin + '/api/ubicaciones/estantes';
        
        console.log('🌐 URL API para estantes:', apiUrl);
        
        // Cargar estantes desde la API
        fetch(apiUrl)
            .then(response => {
                console.log('Respuesta recibida:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Datos recibidos de API:', data);
                console.log('✅ Success:', data.success);
                console.log('✅ Cantidad de estantes:', data.data ? data.data.length : 'undefined');
                if (data.success) {
                    console.log('✅ Llamando renderizarEstantes con:', data.data);
                    this.limpiarLoading(); // Limpiar loading antes de renderizar
                    this.renderizarEstantes(data.data);
                } else {
                    console.error('❌ Error en la respuesta:', data.message);
                    this.limpiarLoading(); // Limpiar loading también en caso de error
                    this.mostrarNotificacion('error', data.message || 'Error al cargar los estantes');
                }
            })
            .catch(error => {
                console.error('Error detallado:', error);
                console.error('Stack:', error.stack);
                
                this.limpiarLoading(); // Limpiar loading en caso de error
                
                // Si no hay estantes, mostrar mensaje apropiado
                if (error.message.includes('404')) {
                    // Mostrar área vacía con mensaje
                    const warehouseMap = document.querySelector('.warehouse-map');
                    if (warehouseMap) {
                        warehouseMap.innerHTML = `
                            <div class="no-estantes-message">
                                <iconify-icon icon="solar:box-minimalistic-broken" style="font-size: 48px; color: #999;"></iconify-icon>
                                <h4 style="color: #666; margin-top: 10px;">No hay estantes creados</h4>
                                <p style="color: #999;">Haz clic en "Nuevo Estante" para crear el primero</p>
                            </div>
                        `;
                    }
                } else {
                    this.mostrarNotificacion('error', 'Error de conexión al cargar estantes');
                }
            })
            .finally(() => {
                console.log('🏁 Llamada a API completada (éxito o error)');
                // El loading placeholder se quita automáticamente cuando se limpia warehouseMap.innerHTML
            });
    }

    renderizarEstantes(estantes) {
        console.log('🎨 Iniciando renderizado de estantes:', estantes);
        const warehouseMap = document.querySelector('.warehouse-map');
        console.log('🎯 Elemento warehouse-map encontrado:', !!warehouseMap);
        if (!warehouseMap) {
            console.error('❌ No se encontró el elemento .warehouse-map');
            return;
        }
        
        // Limpiar el contenedor (esto quita el loading placeholder)
        warehouseMap.innerHTML = '';
        console.log('🧹 Contenedor limpiado (loading placeholder removido)');
        
        console.log('📊 Cantidad de estantes recibidos:', estantes.length);
        if (estantes.length === 0) {
            // Mostrar mensaje bonito cuando no hay estantes
            warehouseMap.innerHTML = `
                <div class="no-estantes-container">
                    <div class="no-estantes-icon">
                        <iconify-icon icon="solar:box-minimalistic-broken"></iconify-icon>
                    </div>
                    <div class="no-estantes-content">
                        <h3 class="no-estantes-title">¡Bienvenido al Sistema de Almacén!</h3>
                        <p class="no-estantes-description">
                            Aún no tienes estantes configurados en tu almacén. 
                            <br>Crea tu primer estante para comenzar a organizar tus productos.
                        </p>
                        <div class="no-estantes-features">
                            <div class="feature-item">
                                <iconify-icon icon="solar:checklist-bold-duotone"></iconify-icon>
                                <span>Organiza productos por ubicación</span>
                            </div>
                            <div class="feature-item">
                                <iconify-icon icon="solar:graph-bold-duotone"></iconify-icon>
                                <span>Controla el stock por estante</span>
                            </div>
                            <div class="feature-item">
                                <iconify-icon icon="solar:map-point-bold-duotone"></iconify-icon>
                                <span>Encuentra productos rápidamente</span>
                            </div>
                        </div>
                        <button class="btn-crear-primer-estante" onclick="window.abrirModalAgregarEstante && window.abrirModalAgregarEstante()">
                            <iconify-icon icon="solar:add-square-bold-duotone"></iconify-icon>
                            <span>Crear Mi Primer Estante</span>
                        </button>
                    </div>
                </div>
            `;
            console.log('📝 Mensaje de bienvenida mostrado');
        } else {
            // Renderizar estantes normalmente
            console.log('🎯 Renderizando estantes...');
            estantes.forEach((estante, index) => {
                console.log(`🏗️ Creando estante ${index + 1}/${estantes.length}:`, estante.nombre);
                const estanteCard = this.crearEstanteCard(estante);
                if (estanteCard) {
                    warehouseMap.appendChild(estanteCard);
                    console.log(`✅ Estante ${estante.nombre} agregado al DOM`);
                } else {
                    console.error(`❌ No se pudo crear card para estante ${estante.nombre}`);
                }
            });
            console.log(`✅ ${estantes.length} estantes renderizados correctamente`);
            // Asegurar navegación fiable en los enlaces dentro de las tarjetas
            this.habilitarNavegacionEstantes();
        }
    }

    habilitarNavegacionEstantes() {
        try {
            const links = document.querySelectorAll('.estante-card-compact .estante-link-area');
            console.log('🔗 Enlaces de estantes encontrados:', links.length);
            links.forEach(link => {
                // Evitar duplicar handlers
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (typeof e.stopImmediatePropagation === 'function') {
                        e.stopImmediatePropagation();
                    }
                    const href = link.getAttribute('href');
                    const match = href && href.match(/\/ubicaciones\/estante\/(\d+)/);
                    const estanteId = match ? parseInt(match[1], 10) : NaN;
                    console.log('🔗 Click en link de estante', { estanteId, href });
                    if (typeof this.irADetalleEstante === 'function') {
                        this.irADetalleEstante(estanteId);
                    } else {
                        window.location.href = href;
                    }
                }, { once: false });
            });
        } catch (err) {
            console.warn('No se pudo habilitar navegación de estantes:', err);
        }
    }

    irADetalleEstante(id) {
        try {
            if (!Number.isInteger(id) || id <= 0) {
                console.warn('ID de estante inválido para navegación:', id);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No se pudo abrir el estante',
                        text: 'ID de estante inválido. Recarga el mapa e inténtalo nuevamente.'
                    });
                }
                return;
            }
            fetch(`/api/ubicaciones/estante/${id}`)
                .then(res => res.json().catch(() => ({ success: false })))
                .then(data => {
                    if (data && data.success !== false) {
                        console.log('✅ Estante válido, navegando a detalle:', id);
                        window.location.href = `/ubicaciones/estante/${id}`;
                    } else {
                        console.warn('⚠️ API no confirmó estante, intento de navegación directa:', id);
                        window.location.href = `/ubicaciones/estante/${id}`;
                    }
                })
                .catch(() => {
                    console.log('ℹ️ Fallback navegación directa');
                    window.location.href = `/ubicaciones/estante/${id}`;
                });
        } catch (err) {
            console.error('Error al navegar al detalle del estante:', err);
            window.location.href = `/ubicaciones/estante/${id}`;
        }
    }

    // Función de utilidad para forzar la limpieza del loading
    limpiarLoading() {
        console.log('🔄 Ejecutando limpiarLoading()...');
        const warehouseMap = document.querySelector('.warehouse-map');
        console.log('🎯 Elemento warehouse-map encontrado:', !!warehouseMap);
        if (warehouseMap) {
            const loadingPlaceholder = warehouseMap.querySelector('.loading-placeholder');
            console.log('🔍 Loading placeholder encontrado:', !!loadingPlaceholder);
            if (loadingPlaceholder) {
                console.log('🧹 Removiendo loading placeholder...');
                loadingPlaceholder.remove();
                console.log('✅ Loading placeholder removido exitosamente');
            } else {
                console.log('ℹ️ No hay loading placeholder para remover');
            }
        } else {
            console.error('❌ No se encontró el elemento .warehouse-map');
        }
    }

    // ==================== FUNCIONES DE CÁLCULO AUTOMÁTICO ====================
    
    setupCalculoAutomaticoCapacidad() {
        // Para modal de nuevo estante
        const nivelesInput = document.getElementById('numero_niveles');
        const columnasInput = document.getElementById('numero_posiciones');
        
        if (nivelesInput && columnasInput) {
            nivelesInput.addEventListener('input', () => this.calcularCapacidadTotal());
            columnasInput.addEventListener('input', () => this.calcularCapacidadTotal());
        }

        // Para modal de editar estante
        const editarNivelesInput = document.getElementById('editar_numero_niveles');
        const editarColumnasInput = document.getElementById('editar_numero_posiciones');
        
        if (editarNivelesInput && editarColumnasInput) {
            editarNivelesInput.addEventListener('input', () => this.calcularCapacidadTotalEditar());
            editarColumnasInput.addEventListener('input', () => this.calcularCapacidadTotalEditar());
        }
    }

    calcularCapacidadTotal() {
        const niveles = parseInt(document.getElementById('numero_niveles')?.value) || 0;
        const columnas = parseInt(document.getElementById('numero_posiciones')?.value) || 0;
        const total = niveles * columnas;
        
        this.actualizarCapacidadCalculada(niveles, columnas, total);
        
        // Actualizar campo oculto
        const capacidadInput = document.getElementById('capacidad_total');
        if (capacidadInput) {
            capacidadInput.value = total;
        }
    }

    calcularCapacidadTotalEditar() {
        const niveles = parseInt(document.getElementById('editar_numero_niveles')?.value) || 0;
        const columnas = parseInt(document.getElementById('editar_numero_posiciones')?.value) || 0;
        const total = niveles * columnas;
        
        this.actualizarCapacidadCalculadaEditar(niveles, columnas, total);
        
        // Actualizar campo oculto
        const capacidadInput = document.getElementById('editar_capacidad_total');
        if (capacidadInput) {
            capacidadInput.value = total;
        }
    }

    actualizarCapacidadCalculada(niveles, columnas, total = null) {
        if (total === null) {
            total = niveles * columnas;
        }
        
        const textElement = document.getElementById('capacidad_calculada_text');
        if (textElement) {
            textElement.innerHTML = `Total: <strong>${total} slots</strong> (${niveles} niveles × ${columnas} columnas)`;
        }
    }

    actualizarCapacidadCalculadaEditar(niveles, columnas, total = null) {
        if (total === null) {
            total = niveles * columnas;
        }
        
        const textElement = document.getElementById('editar_capacidad_calculada_text');
        if (textElement) {
            textElement.innerHTML = `Total: <strong>${total} slots</strong> (${niveles} niveles × ${columnas} columnas)`;
        }
    }

    crearEstanteCard(estante) {
        try {
            console.log('🏗️ Creando tarjeta para estante:', estante);
            
            // Validar datos básicos
            if (!estante || !estante.id || !estante.nombre) {
                console.error('❌ Datos del estante incompletos:', estante);
                return this.crearTarjetaError('Estante inválido');
            }
            
            // Detectar tipo de estante de forma segura
            const tipo = this.detectarTipoEstante(estante.nombre) || 'venta';
            
            // Valores seguros con defaults
            const id = estante.id;
            const nombre = estante.nombre || 'Sin nombre';
            const capacidadTotal = parseInt(estante.capacidad_total) || 0;
            const productosActuales = parseInt(estante.productos_actuales) || 0;
            const slotsOcupados = parseInt(estante.slots_ocupados) || 0;
            const ocupacionPorcentaje = parseFloat(estante.ocupacion_porcentaje) || 0;
            const estado = estante.estado || 'ok';
            const ubicacionFisica = estante.ubicacion_fisica || '';
            
            // Calcular disponibles de forma segura
            const disponibles = Math.max(0, capacidadTotal - slotsOcupados);
            
            // Crear el contenedor del estante
        const card = document.createElement('div');
        card.className = 'estante-card-compact';
            card.dataset.estado = estado;
        card.dataset.tipo = tipo;
            card.dataset.estanteId = id;

        // Navegación al detalle al hacer clic en cualquier parte de la tarjeta
        card.style.cursor = 'pointer';
        card.addEventListener('click', (ev) => {
            // No navegar si el clic fue en un botón de acción (editar/eliminar)
            if (ev.target.closest('.btn-accion-estante')) return;
            const estanteId = parseInt(id, 10);
            console.log('🖱️ Click en tarjeta de estante', { estanteId, nombre });
            if (typeof this.irADetalleEstante === 'function') {
                this.irADetalleEstante(estanteId);
            } else {
                const url = `/ubicaciones/estante/${estanteId}`;
                window.location.href = url;
            }
        });
        
        // Determinar icono según tipo
        const icono = tipo === 'almacen' ? 
            'solar:box-minimalistic-bold-duotone' : 
            'solar:shop-bold-duotone';
        
        // Descripción según tipo
            let descripcion = tipo === 'almacen' ? 
            'Zona de almacenamiento interno' : 
            'Estante de productos para venta';
                
            // Si hay ubicación física, usarla como descripción
            if (ubicacionFisica && ubicacionFisica.trim() !== '') {
                descripcion = ubicacionFisica.trim();
            }
            
            console.log(`📊 Estante ${nombre}: ${slotsOcupados}/${capacidadTotal} slots, ${productosActuales} productos`);
        
        card.innerHTML = `
            <!-- Botones de acción en la esquina superior derecha -->
            <div class="estante-acciones">
                <button class="btn-accion-estante btn-eliminar-estante" 
                        title="Eliminar estante" 
                            data-estante-id="${id}"
                            data-estante-nombre="${nombre}"
                            data-productos-actuales="${productosActuales}">
                    <iconify-icon icon="solar:trash-bin-minimalistic-bold"></iconify-icon>
                </button>
                <button class="btn-accion-estante btn-editar-estante" 
                        title="Editar estante" 
                            data-estante-id="${id}">
                    <iconify-icon icon="solar:pen-bold"></iconify-icon>
                </button>
            </div>

            <!-- Área clicable para ir al detalle -->
                <a href="/ubicaciones/estante/${id}" class="estante-link-area">
                <!-- Header con icono y nombre -->
                <div class="estante-header-compact">
                    <div class="estante-top-row">
                        <div class="estante-icon-compact">
                            <iconify-icon icon="${icono}"></iconify-icon>
                        </div>
                        <div class="estante-info-compact">
                                <h4 class="estante-name-compact">${nombre}</h4>
                        </div>
                        <div class="estante-capacity-compact">
                                <span>Cap. ${capacidadTotal}</span>
                        </div>
                    </div>
                    <div class="estante-description-compact">
                        ${descripcion}
                    </div>
                </div>

                <!-- Estadísticas visuales -->
                <div class="estante-stats-compact">
                    <div class="stat-item-compact">
                            <div class="stat-number-compact">${slotsOcupados}</div>
                        <div class="stat-label-compact">OCUPADOS</div>
                    </div>
                    <div class="stat-item-compact">
                            <div class="stat-number-compact">${disponibles}</div>
                        <div class="stat-label-compact">DISPONIBLES</div>
                    </div>
                    <div class="stat-item-compact">
                            <div class="stat-number-compact">${ocupacionPorcentaje.toFixed(1)}%</div>
                        <div class="stat-label-compact">OCUPACIÓN</div>
                    </div>
                </div>

                <!-- Footer con acción -->
                <div class="estante-footer-compact">
                    <iconify-icon icon="solar:cursor-bold"></iconify-icon>
                    <span>Click para gestionar</span>
                </div>
            </a>
        `;
        
            console.log(`✅ Tarjeta creada para estante ${nombre}`);
            return card;
            
        } catch (error) {
            console.error('❌ Error al crear tarjeta del estante:', error);
            console.error('📊 Datos del estante:', estante);
            return this.crearTarjetaError('Error al crear estante');
        }
    }

    crearTarjetaError(mensaje) {
        const card = document.createElement('div');
        card.className = 'estante-card-compact estante-error';
        card.innerHTML = `
            <div class="estante-error-content">
                <iconify-icon icon="solar:danger-triangle-bold" style="color: #ef4444; font-size: 24px;"></iconify-icon>
                <p style="color: #6b7280; margin: 8px 0 0 0; font-size: 12px;">${mensaje}</p>
            </div>
        `;
        return card;
    }

    detectarTipoEstante(nombre) {
        const nombreLower = nombre.toLowerCase();
        
        // Si contiene "almacen" o "almacén" o "interno"
        if (nombreLower.includes('almacen') || 
            nombreLower.includes('almacén') || 
            nombreLower.includes('interno')) {
            return 'almacen';
        }
        
        // Por defecto es tipo estante de venta
        return 'venta';
    }

    cargarProductosUbicados() {
        console.log('📦 Cargando productos ubicados...');
        this.mostrarSkeletonLoading('tablaProductosUbicadosBody');
        
        setTimeout(() => {
            this.ocultarSkeletonLoading('tablaProductosUbicadosBody');
            console.log('✅ Productos ubicados cargados');
        }, 800);
    }

    cargarProductosSinUbicar() {
        console.log('📋 Cargando productos sin ubicar...');
        this.mostrarSkeletonLoading('tablaProductosSinUbicarBody');
        
        setTimeout(() => {
            this.ocultarSkeletonLoading('tablaProductosSinUbicarBody');
            console.log('✅ Productos sin ubicar cargados');
        }, 800);
    }

    filtrarProductosUbicados() {
        console.log('🔍 Filtrando productos ubicados...', this.filtros.productos);
    }

    filtrarProductosSinUbicar() {
        console.log('🔍 Filtrando productos sin ubicar...', this.filtros.sinUbicar);
    }

    exportarDatos() {
        this.mostrarNotificacion('info', 'Exportando datos...');
        
        setTimeout(() => {
            this.mostrarNotificacion('success', 'Datos exportados correctamente');
        }, 1000);
    }

    actualizarFotoAlmacen() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/jpeg,image/png,image/jpg,image/gif';
        input.multiple = false;
        
        input.onchange = (event) => {
            const file = event.target.files[0];
            if (file) {
                // Validar tamaño del archivo (máximo 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    this.mostrarNotificacion('error', 'La imagen es demasiado grande. El tamaño máximo es 2MB.');
                    return;
                }

                // Validar tipo de archivo
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    this.mostrarNotificacion('error', 'Tipo de archivo no válido. Solo se permiten imágenes JPG, PNG y GIF.');
                    return;
                }

                // Mostrar loading
                this.mostrarNotificacion('info', 'Subiendo imagen...');
                
                // Crear FormData para enviar el archivo
                const formData = new FormData();
                formData.append('imagen', file);
                
                // Obtener token CSRF
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) {
                    this.mostrarNotificacion('error', 'Error de seguridad: Token CSRF no encontrado');
                    return;
                }

                // Construir URL para la API
                const apiUrl = window.location.origin + '/api/ubicaciones/actualizar-imagen';
                
                // Realizar la petición
                fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    console.log('Respuesta del servidor:', response.status, response.statusText);
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data);
                    
                    if (data.success) {
                        // Actualizar la imagen en el DOM
                    const warehouseBg = document.querySelector('.warehouse-bg-modern');
                        const imagenFondoAlmacen = document.getElementById('imagenFondoAlmacen');
                        
                    if (warehouseBg) {
                            warehouseBg.src = data.data.imagen_url + '?t=' + Date.now(); // Cache busting
                        }
                        if (imagenFondoAlmacen) {
                            imagenFondoAlmacen.src = data.data.imagen_url + '?t=' + Date.now(); // Cache busting
                        }
                        
                        this.mostrarNotificacion('success', data.message || 'Imagen del almacén actualizada exitosamente');
                        console.log('✅ Imagen del almacén actualizada correctamente');
                        
                    } else {
                        this.mostrarNotificacion('error', data.message || 'Error al actualizar la imagen');
                        console.error('❌ Error en la respuesta:', data.message);
                    }
                })
                .catch(error => {
                    console.error('❌ Error detallado:', error);
                    this.mostrarNotificacion('error', 'Error de conexión al actualizar la imagen');
                });
            }
        };
        
        input.click();
    }

    // ==================== UTILIDADES ====================
    mostrarSkeletonLoading(tableBodyId) {
        const tbody = document.getElementById(tableBodyId);
        if (tbody) {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                row.style.opacity = '0.5';
                row.classList.add('loading-skeleton');
            });
        }
    }

    ocultarSkeletonLoading(tableBodyId) {
        const tbody = document.getElementById(tableBodyId);
        if (tbody) {
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                row.style.opacity = '1';
                row.classList.remove('loading-skeleton');
            });
        }
    }

    mostrarNotificacion(tipo, mensaje) {
        if (typeof Swal !== 'undefined') {
            const iconos = {
                'success': 'success',
                'error': 'error',
                'warning': 'warning',
                'info': 'info'
            };

            Swal.fire({
                icon: iconos[tipo] || 'info',
                title: mensaje,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            console.log(`${tipo.toUpperCase()}: ${mensaje}`);
        }
    }

    // ==================== CONFIGURACIONES EXTERNAS ====================
    // Estas funciones son implementadas en archivos separados
    configurarModales() {
        // Implementado en modal_agregar.js
    }

    configurarPaginacion() {
        // Implementado en archivos específicos
    }

    configurarCheckboxes() {
        // Implementado en archivos específicos
    }

    configurarAlertas() {
        // Implementado en archivos específicos
    }

    // ==================== FUNCIONES DE ELIMINACIÓN ====================
    abrirModalEliminarEstante(btn) {
        try {
            const estanteId = btn?.dataset?.estanteId;
            const estanteNombre = btn?.dataset?.estanteNombre;
            const productosActuales = parseInt(btn?.dataset?.productosActuales) || 0;
            
            // Validar que tenemos los datos básicos
            if (!estanteId || !estanteNombre) {
                console.error('❌ Datos del estante incompletos:', { estanteId, estanteNombre });
                this.mostrarNotificacion('error', 'Error: No se pudo obtener la información del estante');
                return;
            }
            
            // Obtener datos adicionales del estante desde la tarjeta de forma segura
            const card = btn.closest('.estante-card-compact');
            let capacidadTotal = '0';
            
            if (card) {
                const capacityElement = card.querySelector('.estante-capacity-compact span');
                if (capacityElement && capacityElement.textContent) {
                    capacidadTotal = capacityElement.textContent.replace('Cap. ', '').trim();
                }
            }
            
            // Guardar datos para la eliminación
            this.estanteAEliminar = {
                id: estanteId,
                nombre: estanteNombre,
                productosActuales: productosActuales,
                capacidadTotal: capacidadTotal
            };
            
            console.log('📊 Datos del estante a eliminar:', this.estanteAEliminar);
            
            // Llenar el modal con la información de forma segura
            const estanteNombreElement = document.getElementById('estanteNombreEliminar');
            const capacidadTotalElement = document.getElementById('capacidadTotalEliminar');
            const productosActualesElement = document.getElementById('productosActualesEliminar');
            
            if (estanteNombreElement) estanteNombreElement.textContent = estanteNombre;
            if (capacidadTotalElement) capacidadTotalElement.textContent = capacidadTotal;
            if (productosActualesElement) productosActualesElement.textContent = productosActuales;
            
            // Mostrar/ocultar warning de productos
            const warningProductos = document.getElementById('warningProductos');
            const cantidadProductosWarning = document.getElementById('cantidadProductosWarning');
            
            if (productosActuales > 0 && warningProductos && cantidadProductosWarning) {
                cantidadProductosWarning.textContent = productosActuales;
                warningProductos.style.display = 'flex';
            } else if (warningProductos) {
                warningProductos.style.display = 'none';
            }
            
            // Mostrar el modal
            const modal = document.getElementById('modalEliminarEstante');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Focus en el botón de cancelar por seguridad
                setTimeout(() => {
                    const btnCancelar = document.getElementById('btnCancelarEliminacion');
                    if (btnCancelar) {
                        btnCancelar.focus();
                    }
                }, 100);
            } else {
                console.error('❌ Modal de eliminación no encontrado');
                this.mostrarNotificacion('error', 'Error: Modal de eliminación no disponible');
                return;
            }
            
            console.log('🗑️ Modal de eliminación abierto para estante:', estanteNombre);
            
        } catch (error) {
            console.error('❌ Error al abrir modal de eliminación:', error);
            this.mostrarNotificacion('error', 'Error al abrir el modal de eliminación');
        }
    }

    cerrarModalEliminarEstante(limpiarDatos = false) {
        try {
            const modal = document.getElementById('modalEliminarEstante');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
            
            // Solo limpiar datos si se especifica explícitamente
            if (limpiarDatos) {
                this.estanteAEliminar = null;
                console.log('🗑️ Datos del estante limpiados');
            }
            
            // Rehabilitar el botón de confirmación por si acaso
            const btn = document.getElementById('btnConfirmarEliminacion');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `
                    <iconify-icon icon="solar:trash-bin-minimalistic-bold"></iconify-icon>
                    <span>Sí, Eliminar Estante</span>
                `;
            }
            
            console.log('❌ Modal de eliminación cerrado');
            
        } catch (error) {
            console.error('❌ Error al cerrar modal de eliminación:', error);
        }
    }

    async confirmarEliminarEstante() {
        if (!this.estanteAEliminar || !this.estanteAEliminar.id) {
            console.error('❌ No hay datos del estante para eliminar:', this.estanteAEliminar);
            this.mostrarNotificacion('error', 'Error: No se ha seleccionado ningún estante para eliminar');
            return;
        }

        const btn = document.getElementById('btnConfirmarEliminacion');
        if (!btn) {
            console.error('❌ Botón de confirmación no encontrado');
            this.mostrarNotificacion('error', 'Error: Botón de confirmación no disponible');
            return;
        }
        
        const originalBtnContent = btn.innerHTML;
        
        try {
            console.log('🗑️ Iniciando eliminación del estante:', this.estanteAEliminar);
            
            // Deshabilitar el botón y mostrar loading
            btn.disabled = true;
            btn.innerHTML = `
                <iconify-icon icon="solar:loading-bold" style="animation: spin 1s linear infinite;"></iconify-icon>
                <span>Eliminando...</span>
            `;
            
            this.mostrarNotificacion('info', 'Eliminando estante...');
            
            // Obtener token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                throw new Error('Token CSRF no encontrado');
            }

            // Construir URL para la API (usando el mismo patrón que las otras APIs)
            const apiUrl = `${window.location.origin}/api/ubicaciones/eliminar-estante/${this.estanteAEliminar.id}`;
            
            console.log('🌐 URL de eliminación:', apiUrl);
            console.log('📋 Datos del estante:', this.estanteAEliminar);
            
            // Realizar la petición con mejor manejo de errores
            const response = await fetch(apiUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            console.log('📥 Respuesta HTTP:', response.status, response.statusText);
            console.log('📋 Headers de respuesta:', Object.fromEntries(response.headers.entries()));
            
            // Obtener el texto de respuesta primero para debug
            const responseText = await response.text();
            console.log('📝 Respuesta raw:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ Error parseando JSON:', parseError);
                console.log('📝 Respuesta que causó error:', responseText);
                throw new Error(`Respuesta no válida del servidor: ${responseText.substring(0, 200)}...`);
            }
            
            console.log('📊 Datos parseados:', data);
            
            if (!response.ok) {
                const errorMsg = data?.message || `Error HTTP ${response.status}: ${response.statusText}`;
                console.error('❌ Error HTTP:', errorMsg);
                throw new Error(errorMsg);
            }
            
            if (data.success) {
                console.log('✅ Eliminación exitosa!');
                
                // PASO 1: Cerrar modal de confirmación INMEDIATAMENTE
                const modal = document.getElementById('modalEliminarEstante');
                if (modal) {
                    modal.style.display = 'none';
                    console.log('✅ Modal de confirmación cerrado inmediatamente');
                }
                
                // PASO 2: Ocultar cualquier overlay o backdrop
                document.body.style.overflow = '';
                
                // PASO 3: Cerrar cualquier toast/notificación de loading anterior
                if (typeof Swal !== 'undefined') {
                    Swal.close();
                }
                
                // PASO 4: Restaurar botón inmediatamente
                btn.disabled = false;
                btn.innerHTML = originalBtnContent;
                
                // PASO 5: Limpiar datos del estante
                this.estanteAEliminar = null;
                
                // PASO 6: Buscar y remover la tarjeta del estante de la UI
                this.removerTarjetaEstanteDelDOM();
                
                // PASO 7: Mostrar notificación de éxito
                this.mostrarNotificacion('success', data.message || 'Estante eliminado correctamente');
                
                // PASO 8: Actualizar el resumen de estantes para reflejar los cambios
                    setTimeout(() => {
                    this.actualizarResumenEstantes();
                }, 500);
                
                console.log('🎉 Eliminación completada exitosamente');
                
            } else {
                const errorMsg = data.message || 'Error desconocido al eliminar el estante';
                console.error('❌ Error en la respuesta:', errorMsg);
                this.mostrarNotificacion('error', errorMsg);
            }
            
        } catch (error) {
            console.error('❌ Error durante la eliminación:', error);
            console.error('📍 Stack trace:', error.stack);
            
            // Restaurar botón
            btn.disabled = false;
            btn.innerHTML = originalBtnContent;
            
            // Mostrar error al usuario
            let errorMessage = 'Error al eliminar el estante';
            
            if (error.message.includes('CSRF')) {
                errorMessage = 'Error de seguridad. Recarga la página e intenta nuevamente.';
            } else if (error.message.includes('fetch')) {
                errorMessage = 'Error de conexión. Verifica tu conexión a internet.';
            } else if (error.message.includes('HTTP 500')) {
                errorMessage = 'Error interno del servidor. Revisa los logs del sistema.';
            } else if (error.message.includes('HTTP 404')) {
                errorMessage = 'El estante especificado no existe.';
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            this.mostrarNotificacion('error', errorMessage);
        }
    }

    // Nueva función para remover la tarjeta del DOM de forma segura
    removerTarjetaEstanteDelDOM() {
        if (!this.estanteAEliminar || !this.estanteAEliminar.id) {
            console.warn('⚠️ No hay datos del estante para remover de la UI');
                    return;
                }
                
        console.log('🔍 Buscando tarjeta del estante para remover:', this.estanteAEliminar.id);
                
        // Buscar tarjeta por data-estante-id
                let card = document.querySelector(`[data-estante-id="${this.estanteAEliminar.id}"]`);
                
                if (!card) {
            // Buscar por dataset
                    card = Array.from(document.querySelectorAll('.estante-card-compact')).find(el => 
                        el.dataset.estanteId === String(this.estanteAEliminar.id)
                    );
                }
                
                if (!card) {
            // Buscar por nombre como última opción
                    card = Array.from(document.querySelectorAll('.estante-card-compact')).find(el => {
                        const nombreElement = el.querySelector('.estante-name-compact');
                        return nombreElement && nombreElement.textContent.trim() === this.estanteAEliminar.nombre;
                    });
                }
                
                if (card) {
            console.log('✅ Tarjeta encontrada, removiendo del DOM...');
            
            // Animar salida
            card.style.transition = 'all 0.3s ease';
            card.style.transform = 'scale(0.8)';
            card.style.opacity = '0';
            
            // Remover del DOM después de la animación
                        setTimeout(() => {
                            try {
                    if (card && card.parentNode) {
                        card.parentNode.removeChild(card);
                        console.log('🗑️ Tarjeta removida del DOM exitosamente');
                    }
                } catch (error) {
                    console.error('❌ Error removiendo tarjeta del DOM:', error);
                            }
                        }, 300);
                        
                } else {
            console.warn('⚠️ No se encontró la tarjeta del estante en el DOM');
        }
    }

    // ==================== FUNCIONES DE EDICIÓN ====================
    async abrirModalEditarEstante(estanteId) {
        try {
            if (!estanteId) {
                console.error('❌ ID de estante no proporcionado');
                this.mostrarNotificacion('error', 'Error: ID de estante no válido');
                return;
            }

            console.log('✏️ Abriendo modal de edición para estante ID:', estanteId);

            // Limpiar datos previos y establecer ID temporal
            this.estanteEnEdicion = { id: estanteId, cargando: true };

            // Mostrar el modal con loading
            const modal = document.getElementById('modalEditarEstante');
            if (!modal) {
                console.error('❌ Modal de edición no encontrado');
                this.mostrarNotificacion('error', 'Error: Modal de edición no disponible');
                return;
            }

            // Mostrar modal
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            // Deshabilitar botón de guardar mientras carga
            const btnGuardar = document.getElementById('btnGuardarEditarEstante');
            if (btnGuardar) {
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = `
                    <iconify-icon icon="solar:loading-bold" style="animation: spin 1s linear infinite;"></iconify-icon>
                    <span>Cargando...</span>
                `;
            }

            // Cargar datos del estante
            await this.cargarDatosEstante(estanteId);

        } catch (error) {
            console.error('❌ Error al abrir modal de edición:', error);
            this.mostrarNotificacion('error', 'Error al abrir el modal de edición');
            this.cerrarModalEditarEstante();
        }
    }

    cerrarModalEditarEstante() {
        try {
            console.log('🔒 Cerrando modal de edición...');

            const modal = document.getElementById('modalEditarEstante');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }

            // Limpiar formulario
            const form = document.getElementById('formEditarEstante');
            if (form) {
                form.reset();
                this.limpiarErroresFormulario();
            }

            // Restaurar botón de guardar a su estado original
            const btnGuardar = document.getElementById('btnGuardarEditarEstante');
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = `
                    <iconify-icon icon="solar:diskette-bold"></iconify-icon>
                    <span>Guardar Cambios</span>
                `;
            }

            // Limpiar datos del estante en edición con un pequeño delay
            // para evitar que se limpie durante operaciones en curso
            setTimeout(() => {
                if (modal && modal.classList.contains('hidden')) {
                    this.estanteEnEdicion = null;
                    console.log('🧹 Datos del estante limpiados');
                }
            }, 100);

            console.log('✅ Modal de edición cerrado');

        } catch (error) {
            console.error('❌ Error al cerrar modal de edición:', error);
        }
    }

    async cargarDatosEstante(estanteId) {
        try {
            console.log('📥 Cargando datos del estante ID:', estanteId);

            // Validar ID
            if (!estanteId || isNaN(estanteId)) {
                throw new Error('ID de estante inválido');
            }

            // Obtener token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                throw new Error('Token CSRF no encontrado. Recarga la página e intenta nuevamente.');
            }

            // Construir URL para obtener datos del estante
            const apiUrl = window.location.origin + `/api/ubicaciones/estante/${estanteId}`;

            console.log('🌐 Obteniendo datos via API:', apiUrl);

            // Mostrar loading en el modal
            const modalContent = document.querySelector('#modalEditarEstante .modal-content');
            if (modalContent) {
                modalContent.style.opacity = '0.5';
                modalContent.style.pointerEvents = 'none';
            }

            // Crear timeout para la petición
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Tiempo de espera agotado')), 10000); // 10 segundos
            });

            const fetchPromise = fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            const response = await Promise.race([fetchPromise, timeoutPromise]);

            console.log('📥 Respuesta del servidor:', response.status, response.statusText);

            // Restaurar modal
            if (modalContent) {
                modalContent.style.opacity = '1';
                modalContent.style.pointerEvents = 'auto';
            }

            if (!response.ok) {
                let errorMessage = `Error HTTP: ${response.status}`;
                
                if (response.status === 404) {
                    errorMessage = 'El estante no existe o fue eliminado';
                } else if (response.status === 500) {
                    errorMessage = 'Error interno del servidor. Intenta nuevamente.';
                } else if (response.status === 403) {
                    errorMessage = 'No tienes permisos para realizar esta acción';
                }
                
                throw new Error(errorMessage);
            }

            const data = await response.json();
            console.log('📊 Datos del estante recibidos:', data);

            if (data.success && data.data) {
                // Asegurarse de que no quede la propiedad 'cargando'
                this.estanteEnEdicion = { ...data.data };
                delete this.estanteEnEdicion.cargando;
                
                console.log('✅ Datos del estante cargados:', this.estanteEnEdicion);
                this.llenarFormularioEdicion(data.data);
            } else {
                throw new Error(data.message || 'Error al obtener datos del estante');
            }

        } catch (error) {
            console.error('❌ Error al cargar datos del estante:', error);
            
            // Restaurar modal si está en loading
            const modalContent = document.querySelector('#modalEditarEstante .modal-content');
            if (modalContent) {
                modalContent.style.opacity = '1';
                modalContent.style.pointerEvents = 'auto';
            }
            
            this.mostrarNotificacion('error', 'Error al cargar los datos del estante: ' + error.message);
            this.cerrarModalEditarEstante();
        }
    }

    llenarFormularioEdicion(estante) {
        try {
            console.log('📝 Llenando formulario con datos:', estante);

            // Calcular columnas por nivel (capacidad_total / numero_niveles)
            const numeroNiveles = parseInt(estante.numero_niveles) || 4;
            const capacidadTotal = parseInt(estante.capacidad_total) || 20;
            const columnasPorNivel = Math.ceil(capacidadTotal / numeroNiveles);

            // Llenar campos del formulario
            const campos = {
                'editar_nombre_estante': estante.nombre,
                'editar_ubicacion_local': estante.ubicacion_fisica || '',
                'editar_tipo_estante': estante.tipo,
                'editar_numero_niveles': numeroNiveles,
                'editar_numero_posiciones': columnasPorNivel,
                'editar_capacidad_total': capacidadTotal
            };

            Object.entries(campos).forEach(([fieldId, value]) => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.value = value;
                }
            });

            // Actualizar el texto de capacidad calculada
            this.actualizarCapacidadCalculadaEditar(numeroNiveles, columnasPorNivel);

            // Determinar si hay productos ubicados para bloquear ciertos campos
            const tieneProductos = estante.productos_actuales > 0;
            if (tieneProductos) {
                console.log('⚠️ Estante tiene productos, algunos campos serán readonly');
                const camposABloquear = ['editar_numero_niveles', 'editar_numero_posiciones'];
                camposABloquear.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.setAttribute('readonly', 'readonly');
                        field.title = 'No se puede modificar porque el estante tiene productos ubicados';
                    }
                });
            }

            // Restaurar botón de guardar
            const btnGuardar = document.getElementById('btnGuardarEditarEstante');
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = `
                    <iconify-icon icon="solar:diskette-bold"></iconify-icon>
                    <span>Guardar Cambios</span>
                `;
            }

            console.log('✅ Formulario llenado correctamente');

        } catch (error) {
            console.error('❌ Error al llenar formulario:', error);
            this.mostrarNotificacion('error', 'Error al llenar el formulario de edición');
            
            // Restaurar botón en caso de error
            const btnGuardar = document.getElementById('btnGuardarEditarEstante');
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = `
                    <iconify-icon icon="solar:diskette-bold"></iconify-icon>
                    <span>Guardar Cambios</span>
                `;
            }
        }
    }

    async guardarCambiosEstante() {
        console.log('💾 Iniciando guardado de cambios...');
        console.log('🔍 Estado actual estanteEnEdicion:', this.estanteEnEdicion);

        // Verificación más robusta
        if (!this.estanteEnEdicion) {
            console.error('❌ estanteEnEdicion es null');
            this.mostrarNotificacion('error', 'Error: Los datos del estante no están disponibles. Cierra y abre nuevamente el modal.');
            return;
        }

        if (!this.estanteEnEdicion.id) {
            console.error('❌ estanteEnEdicion no tiene ID:', this.estanteEnEdicion);
            this.mostrarNotificacion('error', 'Error: ID del estante no válido. Cierra y abre nuevamente el modal.');
            return;
        }

        // Verificar si aún está cargando los datos
        if (this.estanteEnEdicion.cargando) {
            console.warn('⏳ Los datos del estante aún se están cargando');
            this.mostrarNotificacion('warning', 'Por favor espera a que terminen de cargar los datos del estante.');
            return;
        }

        // Verificar que tengamos los datos básicos necesarios
        if (!this.estanteEnEdicion.nombre) {
            console.error('❌ Datos del estante incompletos:', this.estanteEnEdicion);
            this.mostrarNotificacion('error', 'Error: Los datos del estante están incompletos. Cierra y abre nuevamente el modal.');
            return;
        }

        const btn = document.getElementById('btnGuardarEditarEstante');
        if (!btn) {
            console.error('❌ Botón de guardar no encontrado');
            this.mostrarNotificacion('error', 'Error: Botón de guardar no encontrado');
            return;
        }

        const originalBtnContent = btn.innerHTML;

        try {
            console.log('💾 Iniciando guardado de cambios para estante:', this.estanteEnEdicion.id);

            // Validar formulario
            if (!this.validarFormularioEdicion()) {
                return;
            }

            // Deshabilitar botón y mostrar loading
            btn.disabled = true;
            btn.innerHTML = `
                <iconify-icon icon="solar:loading-bold" style="animation: spin 1s linear infinite;"></iconify-icon>
                <span>Guardando...</span>
            `;

            this.mostrarNotificacion('info', 'Guardando cambios...');

            // Recolectar datos del formulario
            const formData = this.recolectarDatosFormularioEdicion();
            console.log('📋 Datos a enviar:', formData);

            // Obtener token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                throw new Error('Token CSRF no encontrado');
            }

            // Construir URL para la API
            const apiUrl = window.location.origin + `/api/ubicaciones/actualizar-estante/${this.estanteEnEdicion.id}`;

            console.log('🌐 Actualizando estante via API:', apiUrl);

            // Crear timeout para la petición de guardado
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Tiempo de espera agotado al guardar')), 15000); // 15 segundos
            });

            const fetchPromise = fetch(apiUrl, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            // Realizar la petición
            const response = await Promise.race([fetchPromise, timeoutPromise]);

            console.log('📥 Respuesta del servidor:', response.status, response.statusText);

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
            }

            const data = await response.json();
            console.log('📊 Datos recibidos:', data);

            if (data.success) {
                // Cerrar modal
                this.cerrarModalEditarEstante();

                // Mostrar notificación de éxito
                this.mostrarNotificacion('success', data.message || 'Estante actualizado correctamente');

                // Actualizar la tarjeta en el DOM
                this.actualizarTarjetaEstante(this.estanteEnEdicion.id, data.data);

                console.log('✅ Estante actualizado exitosamente');

            } else {
                // Manejar errores de validación
                if (data.errors) {
                    this.mostrarErroresValidacion(data.errors);
                }
                throw new Error(data.message || 'Error desconocido al actualizar el estante');
            }

        } catch (error) {
            console.error('❌ Error al guardar cambios:', error);
            this.mostrarNotificacion('error', 'Error al guardar los cambios: ' + error.message);

        } finally {
            // Rehabilitar botón
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalBtnContent;
            }
        }
    }

    validarFormularioEdicion() {
        let esValido = true;
        this.limpiarErroresFormulario();

        // Validar nombre
        const nombre = document.getElementById('editar_nombre_estante').value.trim();
        if (!nombre) {
            this.mostrarErrorCampo('editar_nombre_estante', 'El nombre del estante es obligatorio');
            esValido = false;
        } else if (nombre.length < 2) {
            this.mostrarErrorCampo('editar_nombre_estante', 'El nombre debe tener al menos 2 caracteres');
            esValido = false;
        }

        // Validar tipo
        const tipo = document.getElementById('editar_tipo_estante').value;
        if (!tipo) {
            this.mostrarErrorCampo('editar_tipo_estante', 'Debe seleccionar un tipo de estante');
            esValido = false;
        }

        return esValido;
    }

    recolectarDatosFormularioEdicion() {
        const niveles = parseInt(document.getElementById('editar_numero_niveles').value) || 4;
        const columnas = parseInt(document.getElementById('editar_numero_posiciones').value) || 5;
        
        return {
            nombre: document.getElementById('editar_nombre_estante').value.trim(),
            ubicacion_fisica: document.getElementById('editar_ubicacion_local').value.trim(),
            tipo: document.getElementById('editar_tipo_estante').value,
            numero_niveles: niveles,
            numero_posiciones: columnas,
            capacidad_total: niveles * columnas,
            activo: true // Siempre activo por defecto
        };
    }

    actualizarTarjetaEstante(estanteId, datosActualizados) {
        try {
            console.log('🔄 Actualizando tarjeta del estante en el DOM:', estanteId);

            const card = document.querySelector(`[data-estante-id="${estanteId}"]`);
            if (!card) {
                console.warn('⚠️ No se encontró la tarjeta del estante, refrescando vista...');
                this.actualizarResumenEstantes();
                return;
            }

            // Actualizar nombre
            const nombreElement = card.querySelector('.estante-name-compact');
            if (nombreElement && datosActualizados.nombre) {
                nombreElement.textContent = datosActualizados.nombre;
            }

            // Actualizar tipo/descripción
            const descripcionElement = card.querySelector('.estante-description-compact');
            if (descripcionElement && datosActualizados.tipo) {
                const nuevaDescripcion = datosActualizados.tipo === 'almacen' ? 
                    'Zona de almacenamiento interno' : 
                    'Estante de productos para venta';
                descripcionElement.textContent = nuevaDescripcion;
            }

            // Actualizar atributo data-tipo para estilos
            if (datosActualizados.tipo) {
                card.dataset.tipo = datosActualizados.tipo;
            }

            // Actualizar icono si cambió el tipo
            const iconElement = card.querySelector('.estante-icon-compact iconify-icon');
            if (iconElement && datosActualizados.tipo) {
                const nuevoIcono = datosActualizados.tipo === 'almacen' ? 
                    'solar:box-minimalistic-bold-duotone' : 
                    'solar:shop-bold-duotone';
                iconElement.setAttribute('icon', nuevoIcono);
            }

            console.log('✅ Tarjeta del estante actualizada en el DOM');

        } catch (error) {
            console.error('❌ Error al actualizar tarjeta:', error);
            // Si falla, recargar los estantes
            this.actualizarResumenEstantes();
        }
    }

    mostrarErrorCampo(fieldId, mensaje) {
        const field = document.getElementById(fieldId);
        const inputGroup = field?.closest('.input-group');
        const errorElement = document.getElementById(`error_${fieldId.replace('editar_', '')}`);

        if (inputGroup) {
            inputGroup.classList.add('error');
        }

        if (errorElement) {
            errorElement.textContent = mensaje;
            errorElement.classList.add('show');
        }
    }

    limpiarErroresFormulario() {
        const inputGroups = document.querySelectorAll('#formEditarEstante .input-group');
        inputGroups.forEach(group => {
            group.classList.remove('error', 'success');
        });

        const errorElements = document.querySelectorAll('#formEditarEstante .field-error');
        errorElements.forEach(error => {
            error.classList.remove('show');
            error.textContent = '';
        });
    }

    mostrarErroresValidacion(errors) {
        Object.entries(errors).forEach(([field, messages]) => {
            const fieldId = `editar_${field}`;
            const mensaje = Array.isArray(messages) ? messages[0] : messages;
            this.mostrarErrorCampo(fieldId, mensaje);
        });
    }

    // Métodos que serán implementados en archivos específicos
    ejecutarAccion(accion, producto) {
        // Implementado en archivos específicos
    }
    
    abrirAsignacionMasiva() {
        // Implementado en archivos específicos
    }
}

// Inicialización automática
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.warehouse-container-modern') || 
        document.querySelector('.productos-ubicados-container') ||
        document.querySelector('.productos-sin-ubicar-container')) {
        
        window.mapaAlmacen = new MapaAlmacen();
    }
});

// Exportar la clase
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MapaAlmacen;
}