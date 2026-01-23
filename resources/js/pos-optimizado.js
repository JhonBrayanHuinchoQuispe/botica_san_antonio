/**
 * POS Optimizado - Frontend Ultra Rápido
 * Objetivo: Carga de productos en menos de 50ms
 * Animaciones suaves sin lag
 * Scroll infinito para catálogo grande
 */

class PosOptimizado {
    constructor() {
        this.apiBase = '/api/pos-optimizado';
        this.cache = new Map();
        this.debounceTimer = null;
        this.scrollInfinito = {
            ultimoId: 0,
            cargando: false,
            tieneMore: true,
            filtros: {}
        };
        this.productos = [];
        this.productosSeleccionados = [];
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.precargarDatos();
        this.setupAnimaciones();
        this.setupScrollInfinito();
    }

    setupEventListeners() {
        // Búsqueda en tiempo real con debounce
        const searchInput = document.getElementById('busqueda-producto');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.buscarProductosDebounced(e.target.value);
            });

            // Búsqueda instantánea con Enter
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.buscarProductosInstantaneo(e.target.value);
                }
            });
        }

        // Lector de código de barras
        const codigoBarrasInput = document.getElementById('codigo-barras');
        if (codigoBarrasInput) {
            codigoBarrasInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.buscarPorCodigoBarras(e.target.value);
                    e.target.value = '';
                }
            });
        }

        // Filtros de categoría y marca
        const filtroCategoria = document.getElementById('filtro-categoria');
        const filtroMarca = document.getElementById('filtro-marca');
        
        if (filtroCategoria) {
            filtroCategoria.addEventListener('change', (e) => {
                this.aplicarFiltros();
            });
        }

        if (filtroMarca) {
            filtroMarca.addEventListener('change', (e) => {
                this.aplicarFiltros();
            });
        }

        // Botón limpiar caché
        const btnLimpiarCache = document.getElementById('btn-limpiar-cache');
        if (btnLimpiarCache) {
            btnLimpiarCache.addEventListener('click', () => {
                this.limpiarCache();
            });
        }
    }

    setupAnimaciones() {
        // Configurar animaciones CSS para transiciones suaves
        const style = document.createElement('style');
        style.textContent = `
            .producto-card {
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                transform: translateZ(0);
                will-change: transform, opacity;
            }
            
            .producto-card:hover {
                transform: translateY(-2px) scale(1.02);
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            }
            
            .producto-loading {
                opacity: 0.6;
                pointer-events: none;
            }
            
            .fade-in {
                animation: fadeIn 0.3s ease-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .slide-in {
                animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            @keyframes slideIn {
                from { transform: translateX(-20px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            .loading-skeleton {
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: loading 1.5s infinite;
            }
            
            @keyframes loading {
                0% { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }
        `;
        document.head.appendChild(style);
    }

    setupScrollInfinito() {
        const contenedorProductos = document.getElementById('contenedor-productos');
        if (!contenedorProductos) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.scrollInfinito.cargando && this.scrollInfinito.tieneMore) {
                    this.cargarMasProductos();
                }
            });
        }, {
            rootMargin: '100px'
        });

        // Crear elemento sentinel para el scroll infinito
        const sentinel = document.createElement('div');
        sentinel.id = 'scroll-sentinel';
        sentinel.style.height = '1px';
        contenedorProductos.appendChild(sentinel);
        observer.observe(sentinel);
    }

    async precargarDatos() {
        try {
            // Precargar productos populares
            await this.cargarProductosPopulares();
            
            // Precargar categorías y marcas
            await Promise.all([
                this.cargarCategorias(),
                this.cargarMarcas()
            ]);

            // Precargar caché en el servidor
            await this.precargarCacheServidor();

        } catch (error) {
            console.error('Error precargando datos:', error);
        }
    }

    buscarProductosDebounced(termino) {
        clearTimeout(this.debounceTimer);
        
        if (termino.length < 2) {
            this.mostrarProductosPopulares();
            return;
        }

        this.debounceTimer = setTimeout(() => {
            this.buscarProductos(termino);
        }, 150); // 150ms de debounce para mejor UX
    }

    async buscarProductosInstantaneo(termino) {
        clearTimeout(this.debounceTimer);
        
        if (termino.length < 2) {
            this.mostrarProductosPopulares();
            return;
        }

        await this.buscarProductos(termino);
    }

    async buscarProductos(termino, limite = 20) {
        const startTime = performance.now();
        
        try {
            this.mostrarCargando();
            
            // Verificar caché primero
            const cacheKey = `busqueda_${termino}_${limite}`;
            if (this.cache.has(cacheKey)) {
                const cachedData = this.cache.get(cacheKey);
                this.mostrarProductos(cachedData.data);
                this.mostrarTiempoRespuesta(cachedData.tiempo_ms, true);
                return;
            }

            const response = await fetch(`${this.apiBase}/buscar?q=${encodeURIComponent(termino)}&limite=${limite}`);
            const data = await response.json();

            const endTime = performance.now();
            const clientTime = endTime - startTime;

            if (data.success) {
                // Guardar en caché
                this.cache.set(cacheKey, data);
                
                this.mostrarProductos(data.data);
                this.mostrarTiempoRespuesta(data.meta.tiempo_ms, false, clientTime);
            } else {
                this.mostrarError(data.message);
            }

        } catch (error) {
            console.error('Error buscando productos:', error);
            this.mostrarError('Error de conexión');
        } finally {
            this.ocultarCargando();
        }
    }

    async buscarPorCodigoBarras(codigoBarras) {
        if (!codigoBarras) return;

        const startTime = performance.now();
        
        try {
            this.mostrarCargando();

            const response = await fetch(`${this.apiBase}/codigo-barras?codigo_barras=${encodeURIComponent(codigoBarras)}`);
            const data = await response.json();

            const endTime = performance.now();
            const clientTime = endTime - startTime;

            if (data.success) {
                this.agregarProductoAVenta(data.data);
                this.mostrarTiempoRespuesta(data.meta.tiempo_ms, false, clientTime);
                this.mostrarNotificacion(`Producto agregado: ${data.data.nombre}`, 'success');
            } else {
                this.mostrarError(data.message);
            }

        } catch (error) {
            console.error('Error buscando por código de barras:', error);
            this.mostrarError('Error de conexión');
        } finally {
            this.ocultarCargando();
        }
    }

    async cargarProductosPopulares(limite = 50) {
        try {
            const cacheKey = `populares_${limite}`;
            if (this.cache.has(cacheKey)) {
                const cachedData = this.cache.get(cacheKey);
                this.mostrarProductos(cachedData.data);
                return;
            }

            const response = await fetch(`${this.apiBase}/populares?limite=${limite}`);
            const data = await response.json();

            if (data.success) {
                this.cache.set(cacheKey, data);
                this.mostrarProductos(data.data);
            }

        } catch (error) {
            console.error('Error cargando productos populares:', error);
        }
    }

    async cargarMasProductos() {
        if (this.scrollInfinito.cargando || !this.scrollInfinito.tieneMore) return;

        this.scrollInfinito.cargando = true;
        this.mostrarCargandoMas();

        try {
            const params = new URLSearchParams({
                ultimo_id: this.scrollInfinito.ultimoId,
                limite: 20,
                ...this.scrollInfinito.filtros
            });

            const response = await fetch(`${this.apiBase}/scroll-infinito?${params}`);
            const data = await response.json();

            if (data.success) {
                this.scrollInfinito.ultimoId = data.meta.ultimo_id;
                this.scrollInfinito.tieneMore = data.meta.tiene_mas;
                
                this.agregarProductosAlContenedor(data.data);
            }

        } catch (error) {
            console.error('Error cargando más productos:', error);
        } finally {
            this.scrollInfinito.cargando = false;
            this.ocultarCargandoMas();
        }
    }

    async aplicarFiltros() {
        const categoria = document.getElementById('filtro-categoria')?.value || '';
        const marca = document.getElementById('filtro-marca')?.value || '';
        const busqueda = document.getElementById('busqueda-producto')?.value || '';

        this.scrollInfinito.filtros = {
            categoria: categoria,
            marca: marca,
            busqueda: busqueda
        };

        this.scrollInfinito.ultimoId = 0;
        this.scrollInfinito.tieneMore = true;
        
        this.limpiarContenedorProductos();
        await this.cargarMasProductos();
    }

    async cargarCategorias() {
        try {
            const response = await fetch(`${this.apiBase}/categorias`);
            const data = await response.json();

            if (data.success) {
                this.llenarSelectCategorias(data.data);
            }

        } catch (error) {
            console.error('Error cargando categorías:', error);
        }
    }

    async cargarMarcas() {
        try {
            const response = await fetch(`${this.apiBase}/marcas`);
            const data = await response.json();

            if (data.success) {
                this.llenarSelectMarcas(data.data);
            }

        } catch (error) {
            console.error('Error cargando marcas:', error);
        }
    }

    async limpiarCache() {
        try {
            this.cache.clear();
            
            const response = await fetch(`${this.apiBase}/limpiar-cache`, {
                method: 'POST'
            });
            const data = await response.json();

            if (data.success) {
                this.mostrarNotificacion('Caché limpiado correctamente', 'success');
            }

        } catch (error) {
            console.error('Error limpiando caché:', error);
            this.mostrarError('Error limpiando caché');
        }
    }

    async precargarCacheServidor() {
        try {
            await fetch(`${this.apiBase}/precargar-cache`, {
                method: 'POST'
            });
        } catch (error) {
            console.error('Error precargando caché:', error);
        }
    }

    mostrarProductos(productos) {
        const contenedor = document.getElementById('contenedor-productos');
        if (!contenedor) return;

        contenedor.innerHTML = '';
        this.agregarProductosAlContenedor(productos);
    }

    agregarProductosAlContenedor(productos) {
        const contenedor = document.getElementById('contenedor-productos');
        if (!contenedor) return;

        productos.forEach((producto, index) => {
            const card = this.crearProductoCard(producto);
            card.style.animationDelay = `${index * 50}ms`;
            card.classList.add('fade-in');
            contenedor.appendChild(card);
        });
    }

    crearProductoCard(producto) {
        const card = document.createElement('div');
        card.className = 'producto-card col-md-3 col-sm-4 col-6 mb-3';
        
        const estadoClass = this.obtenerEstadoClass(producto);
        const precioFormateado = new Intl.NumberFormat('es-PE', {
            style: 'currency',
            currency: 'PEN'
        }).format(producto.precio_venta);

        card.innerHTML = `
            <div class="card h-100 ${estadoClass}">
                <div class="card-img-container position-relative">
                    <img src="${producto.imagen}" class="card-img-top" alt="${producto.nombre}" 
                         loading="lazy" onerror="this.src='/assets/images/default-product.svg'">
                    ${producto.estado_vencimiento !== 'normal' ? 
                        `<span class="badge badge-warning position-absolute" style="top: 5px; right: 5px;">
                            ${producto.estado_vencimiento === 'vencido' ? 'Vencido' : 'Por vencer'}
                        </span>` : ''
                    }
                </div>
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title text-truncate" title="${producto.nombre}">${producto.nombre}</h6>
                    <p class="card-text small text-muted mb-1">${producto.concentracion || ''}</p>
                    <p class="card-text small text-muted mb-1">Stock: ${producto.stock_actual}</p>
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h6 mb-0 text-primary">${precioFormateado}</span>
                            <button class="btn btn-sm btn-primary" onclick="posOptimizado.agregarProductoAVenta(${JSON.stringify(producto).replace(/"/g, '&quot;')})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        return card;
    }

    obtenerEstadoClass(producto) {
        if (producto.estado_vencimiento === 'vencido') return 'border-danger';
        if (producto.estado_vencimiento === 'por_vencer') return 'border-warning';
        if (producto.stock_actual <= 5) return 'border-warning';
        return '';
    }

    agregarProductoAVenta(producto) {
        // Verificar si el producto ya está en la venta
        const existente = this.productosSeleccionados.find(p => p.id === producto.id);
        
        if (existente) {
            existente.cantidad += 1;
        } else {
            this.productosSeleccionados.push({
                ...producto,
                cantidad: 1
            });
        }

        this.actualizarResumenVenta();
        this.mostrarNotificacion(`${producto.nombre} agregado a la venta`, 'success');
    }

    actualizarResumenVenta() {
        const contenedor = document.getElementById('resumen-venta');
        if (!contenedor) return;

        let total = 0;
        let html = '';

        this.productosSeleccionados.forEach(producto => {
            const subtotal = producto.precio_venta * producto.cantidad;
            total += subtotal;

            html += `
                <div class="item-venta d-flex justify-content-between align-items-center mb-2">
                    <div class="flex-grow-1">
                        <small class="d-block font-weight-bold">${producto.nombre}</small>
                        <small class="text-muted">${producto.cantidad} x S/ ${producto.precio_venta}</small>
                    </div>
                    <div class="text-right">
                        <span class="font-weight-bold">S/ ${subtotal.toFixed(2)}</span>
                        <button class="btn btn-sm btn-outline-danger ml-2" 
                                onclick="posOptimizado.removerProductoDeVenta(${producto.id})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        contenedor.innerHTML = html;
        
        const totalElement = document.getElementById('total-venta');
        if (totalElement) {
            totalElement.textContent = `S/ ${total.toFixed(2)}`;
        }
    }

    removerProductoDeVenta(productoId) {
        this.productosSeleccionados = this.productosSeleccionados.filter(p => p.id !== productoId);
        this.actualizarResumenVenta();
    }

    mostrarProductosPopulares() {
        this.cargarProductosPopulares();
    }

    mostrarCargando() {
        const contenedor = document.getElementById('contenedor-productos');
        if (contenedor) {
            contenedor.classList.add('producto-loading');
        }
    }

    ocultarCargando() {
        const contenedor = document.getElementById('contenedor-productos');
        if (contenedor) {
            contenedor.classList.remove('producto-loading');
        }
    }

    mostrarCargandoMas() {
        const sentinel = document.getElementById('scroll-sentinel');
        if (sentinel) {
            sentinel.innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Cargando más productos...</div>';
        }
    }

    ocultarCargandoMas() {
        const sentinel = document.getElementById('scroll-sentinel');
        if (sentinel) {
            sentinel.innerHTML = '';
        }
    }

    limpiarContenedorProductos() {
        const contenedor = document.getElementById('contenedor-productos');
        if (contenedor) {
            contenedor.innerHTML = '';
        }
    }

    mostrarTiempoRespuesta(tiempoServidor, fromCache = false, tiempoCliente = 0) {
        const elemento = document.getElementById('tiempo-respuesta');
        if (elemento) {
            const total = fromCache ? tiempoCliente : tiempoServidor + tiempoCliente;
            const source = fromCache ? '(caché)' : '(servidor)';
            elemento.textContent = `${total.toFixed(1)}ms ${source}`;
            
            // Cambiar color según el tiempo
            elemento.className = total < 50 ? 'text-success' : total < 100 ? 'text-warning' : 'text-danger';
        }
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        // Implementar sistema de notificaciones toast
        console.log(`${tipo.toUpperCase()}: ${mensaje}`);
    }

    mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'error');
    }

    llenarSelectCategorias(categorias) {
        const select = document.getElementById('filtro-categoria');
        if (!select) return;

        select.innerHTML = '<option value="">Todas las categorías</option>';
        categorias.forEach(categoria => {
            const option = document.createElement('option');
            option.value = categoria;
            option.textContent = categoria;
            select.appendChild(option);
        });
    }

    llenarSelectMarcas(marcas) {
        const select = document.getElementById('filtro-marca');
        if (!select) return;

        select.innerHTML = '<option value="">Todas las marcas</option>';
        marcas.forEach(marca => {
            const option = document.createElement('option');
            option.value = marca;
            option.textContent = marca;
            select.appendChild(option);
        });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.posOptimizado = new PosOptimizado();
});