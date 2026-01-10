






/**
 * PUNTO DE VENTA PROFESIONAL - JavaScript
 * Sistema POS moderno y profesional
 */

class POSProfesional {
    constructor() {
        this.carrito = [];
        this.productos = [];
        this.categorias = [];
        this.ubicaciones = [];
        this.proveedores = [];
        this.filtrosActivos = {};
        this.metodosPagoSeleccionado = 'efectivo';
        // Selección de comprobante (boleta/ticket)
        this.tipoComprobanteSeleccionado = 'ticket';
        
        // Configuración del sistema
        this.configuracion = {
            igv_habilitado: false,
            igv_porcentaje: 18,
            descuentos_habilitados: true,
            descuento_maximo_porcentaje: 50
        };
        
        // Descuento actual
        this.descuento = {
            tipo: 'porcentaje', // 'porcentaje' o 'monto'
            valor: 0,
            monto: 0
        };
        this.virtualGrid = { pageSize: 30, renderedCount: 0, observer: null, productos: [] };
        // Cliente seleccionado por DNI
        this.clienteSeleccionado = null;
        
        this.init();
    }

    init() {
        // Configurar interfaz inmediatamente
        this.setupEventListeners();
        this.inicializarContadores();
        
        // Cargar configuración y datos de forma asíncrona
        this.cargarConfiguracion().catch(error => {
            console.error('Error cargando configuración:', error);
        });
        
        this.cargarDatosIniciales();
    }

    async cargarConfiguracion() {
        try {
            const response = await fetch('/admin/configuracion/obtener');
            const data = await response.json();
            
            if (data.success && data.configuracion) {
                this.configuracion = data.configuracion;
                
                // Actualizar UI con la configuración
                this.actualizarUIConfiguracion();
            }
        } catch (error) {
            console.error('Error al cargar configuración:', error);
        }
    }
    
    actualizarUIConfiguracion() {
        // Actualizar porcentaje de IGV en la UI
        const igvPorcentaje = document.getElementById('igvPorcentaje');
        if (igvPorcentaje) {
            if (this.configuracion.igv_habilitado) {
                igvPorcentaje.textContent = `(${this.configuracion.igv_porcentaje}%)`;
            } else {
                igvPorcentaje.textContent = '(0%)';
            }
        }
        
        // Mostrar/ocultar sección de descuento
        const seccionDescuento = document.getElementById('seccionDescuento');
        if (seccionDescuento) {
            seccionDescuento.style.display = this.configuracion.descuentos_habilitados ? 'block' : 'none';
        }
        
        // Actualizar totales
        this.calcularTotales();
    }

    setupEventListeners() {
        // Búsqueda con debounce optimizado
        const buscarInput = document.getElementById('buscarProductos');
        if (buscarInput) {
            // Debounce para escritura normal
            buscarInput.addEventListener('input', this.debounce(() => this.buscarProductos(), 300));
            
            // Búsqueda inmediata con Enter
            buscarInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.buscarProductos();
                }
                // Limpiar con Escape
                if (e.key === 'Escape') {
                    e.preventDefault();
                    buscarInput.value = '';
                    this.limpiarBusqueda();
                }
            });
            
            // Placeholder dinámico
            buscarInput.placeholder = 'Buscar por nombre, código de barras, marca...';
        }

        // Filtros
        this.setupFiltros();

        // Métodos de pago
        this.setupMetodosPago();

        // Efectivo recibido
        const efectivoInput = document.getElementById('efectivoRecibido');
        if (efectivoInput) {
            efectivoInput.addEventListener('input', this.calcularVuelto.bind(this));
        }

        // Teclas rápidas
        this.setupTeclasRapidas();

        // Cliente por DNI: toggle y acciones
        const toggleDni = document.getElementById('toggleDniCliente');
        const seccionDni = document.getElementById('clienteDniSection');
        const dniInput = document.getElementById('dniCliente');
        const btnConsultarDni = document.getElementById('consultarDniBtn');
        const btnLimpiarCliente = document.getElementById('limpiarClienteBtn');

        if (toggleDni && seccionDni) {
            toggleDni.addEventListener('change', () => {
                const activo = toggleDni.checked;
                seccionDni.style.display = activo ? 'block' : 'none';
                if (!activo) {
                    this.limpiarCliente();
                } else {
                    dniInput && dniInput.focus();
                }
                this.validarProcesamientoVenta();
            });
        }

        if (dniInput) {
            dniInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.consultarDniCliente();
                }
            });

            // Restringir a números y máximo 8 dígitos
            dniInput.addEventListener('input', (e) => {
                const el = e.target;
                el.value = (el.value || '').replace(/\D/g, '').slice(0, 8);
                const ok = /^[0-9]{8}$/.test(el.value || '');
                if (btnConsultarDni) btnConsultarDni.disabled = !ok;
            });
            const okInicial = /^[0-9]{8}$/.test((dniInput.value || ''));
            if (btnConsultarDni) btnConsultarDni.disabled = !okInicial;
        }

        if (btnConsultarDni) {
            btnConsultarDni.addEventListener('click', (e) => {
                e.preventDefault();
                this.consultarDniCliente();
            });
        }

        if (btnLimpiarCliente) {
            btnLimpiarCliente.addEventListener('click', (e) => {
                e.preventDefault();
                this.limpiarCliente();
            });
        }

        // No restaurar cliente ni DNI desde localStorage para evitar ventas con datos anteriores
    }

    setupFiltros() {
        // Los filtros ahora son botones, se manejan con onclick en el HTML
        // La función cambiarFiltro se define globalmente al final del archivo
    }

    setupMetodosPago() {
        console.log('🏦 Configurando métodos de pago...');
        
        // Obtener botones de métodos de pago de forma segura
        const botonesMetodo = document.querySelectorAll('.pos-metodo-rapido');
        
        if (botonesMetodo.length === 0) {
            console.warn('⚠️ No se encontraron botones de métodos de pago');
            return;
        }

        botonesMetodo.forEach(boton => {
            boton.addEventListener('click', (e) => {
                e.preventDefault();
                
                const metodo = boton.dataset.metodo;
                if (!metodo) {
                    console.warn('⚠️ Botón sin método de pago definido');
                    return;
                }
                
                console.log(`💳 Método seleccionado: ${metodo}`);
                
                // Remover active de todos
                botonesMetodo.forEach(btn => btn.classList.remove('active'));
                
                // Activar el seleccionado
                boton.classList.add('active');
                
                // Actualizar método
                this.metodosPagoSeleccionado = metodo;
                
                // Mostrar/ocultar campos según el método
                this.actualizarInterfazPago(metodo);
                
                // Validar procesamiento
                this.validarProcesamientoVenta();
            });
        });

        // Configurar campo de efectivo recibido
        const efectivoInput = document.getElementById('efectivoRecibido');
        if (efectivoInput) {
            efectivoInput.addEventListener('input', () => {
                this.calcularVuelto();
                this.validarProcesamientoVenta();
            });
            
            efectivoInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (this.validarVenta()) {
                        this.procesarVenta();
                    }
                }
            });
        } else {
            console.warn('⚠️ Campo de efectivo recibido no encontrado');
        }
        
        console.log('✅ Métodos de pago configurados');

        // Ocultar botones Boleta/Ticket del panel principal (se mostrarán tras procesar)
        const btnBoleta = document.getElementById('btnBoleta');
        const btnTicket = document.getElementById('btnTicket');
        const contComprobantes = document.querySelector('.pos-botones-comprobantes');
        if (btnBoleta) btnBoleta.style.display = 'none';
        if (btnTicket) btnTicket.style.display = 'none';
        if (contComprobantes) contComprobantes.style.display = 'none';

        // Crear/estilizar botón "Procesar Venta" si no existe
        let btnProcesarVenta = document.getElementById('btnProcesarVenta');
        if (!btnProcesarVenta) {
            btnProcesarVenta = document.createElement('button');
            btnProcesarVenta.id = 'btnProcesarVenta';
            btnProcesarVenta.type = 'button';
            btnProcesarVenta.innerHTML = '<iconify-icon icon="material-symbols:point-of-sale-rounded" style="font-size:20px;"></iconify-icon> Procesar Venta';
            btnProcesarVenta.style.cssText = 'margin-top:6px;padding:14px 16px;border-radius:8px;background:#dc2626;color:#fff;font-weight:600;border:none;display:inline-flex;align-items:center;gap:8px;justify-content:center;';
            const contenedor = (btnBoleta && btnBoleta.parentElement) || (btnTicket && btnTicket.parentElement);
            if (contenedor && contenedor.parentElement) {
                contenedor.parentElement.appendChild(btnProcesarVenta);
            } else {
                const carrito = document.querySelector('#carritoProductos') || document.body;
                carrito.appendChild(btnProcesarVenta);
            }
        }

        btnProcesarVenta.addEventListener('click', (e) => {
            e.preventDefault();
            this.procesarVenta();
        });

        // Estado inicial del botón según validación
        this.validarProcesamientoVenta();
    }

    // Nueva función para manejar la interfaz según el método de pago
    actualizarInterfazPago(metodo) {
        const campoEfectivo = document.getElementById('pagoEfectivoRapido');
        
        if (!campoEfectivo) {
            console.warn('⚠️ Campo de pago efectivo no encontrado');
            return;
        }
        
        if (metodo === 'efectivo') {
            // Mostrar campos de efectivo
            campoEfectivo.style.display = 'block';
            
            // Focus al campo después de un delay
            setTimeout(() => {
                const efectivoInput = document.getElementById('efectivoRecibido');
                if (efectivoInput) {
                    efectivoInput.focus();
                }
            }, 100);
        } else {
            // Ocultar campos de efectivo para tarjeta y yape
            campoEfectivo.style.display = 'none';
            
            // Limpiar valores de efectivo
            const efectivoInput = document.getElementById('efectivoRecibido');
            if (efectivoInput) {
                efectivoInput.value = '';
            }
            
            // No hay vuelto para tarjeta y yape
            this.actualizarVuelto(0);
        }
        
        console.log(`💳 Interfaz actualizada para método: ${metodo}`);
    }

    setupTeclasRapidas() {
        document.addEventListener('keydown', (e) => {
            // F1 - Enfocar búsqueda
            if (e.key === 'F1') {
                e.preventDefault();
                document.getElementById('buscarProductos')?.focus();
            }
            
            // F2 - Limpiar carrito
            if (e.key === 'F2') {
                e.preventDefault();
                this.limpiarCarrito();
            }
            
            // F3 - Procesar venta
            if (e.key === 'F3') {
                e.preventDefault();
                this.procesarVenta();
            }

            // Escape - Limpiar búsqueda
            if (e.key === 'Escape') {
                this.limpiarBusqueda();
            }
        });
    }

    validarProcesamientoVenta() {
        const btnProcesar = document.getElementById('btnProcesarVenta');
        let puedeProcesar = false;

        // Debe existir al menos un producto
        if (this.carrito.length > 0) {
            // Bloquear si alguna línea supera el stock disponible
            const hayExcesoStock = this.carrito.some(ci => (parseInt(ci.cantidad) || 0) > (parseInt(ci.stock_disponible) || 0));
            if (this.metodosPagoSeleccionado === 'efectivo') {
                const efectivoInput = document.getElementById('efectivoRecibido');
                const efectivoRecibido = efectivoInput ? parseFloat(efectivoInput.value) || 0 : 0;
                const total = this.calcularTotal();
                puedeProcesar = efectivoRecibido >= total && total > 0 && !hayExcesoStock;
                if (efectivoInput) {
                    const vueltoEl = document.getElementById('vueltoCalculado');
                    if (efectivoRecibido < total) {
                        efectivoInput.classList.add('error');
                        if (vueltoEl) vueltoEl.classList.add('alert');
                    } else {
                        efectivoInput.classList.remove('error');
                        if (vueltoEl) vueltoEl.classList.remove('alert');
                    }
                }
            } else {
                // Tarjeta/Yape: con tener productos es suficiente
                const total = this.calcularTotal();
                puedeProcesar = total > 0 && !hayExcesoStock;
            }
            // DNI activo debe ser válido (8 dígitos)
            const toggleDni = document.getElementById('toggleDniCliente');
            const dniInput = document.getElementById('dniCliente');
            if (toggleDni && toggleDni.checked) {
                const dniVal = (dniInput?.value || '').trim();
                const dniValido = /^[0-9]{8}$/.test(dniVal);
                if (!dniValido) {
                    puedeProcesar = false;
                    dniInput && dniInput.classList.add('error');
                } else {
                    dniInput && dniInput.classList.remove('error');
                }
            } else {
                dniInput && dniInput.classList.remove('error');
            }

            // Descuento activo debe ser válido y > 0
            const toggleDescuento = document.getElementById('conDescuento');
            const descuentoInput = document.getElementById('descuentoInlineInput');
            if (toggleDescuento && toggleDescuento.checked) {
                const valorStr = (descuentoInput?.value ?? '').toString();
                const valorNum = parseFloat(valorStr);
                const subtotal = this.calcularSubtotal();
                let validoDescuento = true;
                if (valorStr.trim() === '' || isNaN(valorNum) || valorNum <= 0) validoDescuento = false;
                if (this.descuento?.tipo === 'porcentaje' && valorNum > 100) validoDescuento = false;
                if (this.descuento?.tipo === 'monto' && valorNum > subtotal) validoDescuento = false;
                if (!validoDescuento) {
                    puedeProcesar = false;
                    descuentoInput && descuentoInput.classList.add('error');
                } else {
                    descuentoInput && descuentoInput.classList.remove('error');
                }
            } else {
                descuentoInput && descuentoInput.classList.remove('error');
            }
            // Ayuda visual en el botón cuando hay exceso de stock
            if (btnProcesar) {
                btnProcesar.title = hayExcesoStock ? 'Corrige cantidades que superan el stock disponible en el carrito.' : '';
            }
        }

        if (btnProcesar) {
            btnProcesar.disabled = !puedeProcesar;
            btnProcesar.style.opacity = puedeProcesar ? '1' : '0.6';
            btnProcesar.style.cursor = puedeProcesar ? 'pointer' : 'not-allowed';
        }

        console.log(`🔄 Validación procesamiento: ${puedeProcesar ? 'PUEDE' : 'NO PUEDE'} procesar (${this.metodosPagoSeleccionado})`);
    }

    async cargarDatosIniciales() {
        try {
            // Ocultar filtros al inicio
            this.ocultarFiltros();
            
            // Cargar productos más vendidos de forma asíncrona sin bloquear la UI
            this.cargarProductosMasVendidos().catch(error => {
                console.error('Error cargando productos:', error);
            });
            
            // Cargar estadísticas del día de forma asíncrona
            this.cargarEstadisticasHoy().catch(error => {
                console.error('Error cargando estadísticas:', error);
            });
            
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.mostrarError('Error al cargar datos iniciales');
        }
    }

    async cargarProductosMasVendidos() {
        try {
            // Mostrar ranking de los más vendidos
            this.actualizarTituloProductos('Top 10 Productos Más Vendidos');
            this.actualizarContadorProductos('Cargando...');

            const headers = { 'Accept': 'application/json' };
            let productos = [];

            // API oficial de más vendidos
            const resp = await fetch('/api/punto-venta/productos-mas-vendidos', { headers });
            const result = await resp.json();

            // Normalizar diferentes estructuras posibles
            let lista = [];
            if (Array.isArray(result?.data)) {
                lista = result.data;
            } else if (Array.isArray(result?.productos)) {
                lista = result.productos;
            } else if (Array.isArray(result)) {
                lista = result;
            }

            // Mapear a estructura común para renderizar tarjetas
            productos = lista.map(item => {
                const p = item.producto || item; // algunos endpoints devuelven {producto, cantidad}
                return {
                    id: p.id,
                    nombre: p.nombre,
                    concentracion: p.concentracion || null,
                    presentacion: p.presentacion || p.categoria || 'Presentación estándar',
                    precio_venta: parseFloat(p.precio_venta || p.precio || 0),
                    stock_actual: parseInt(p.stock_actual ?? p.stock ?? 0, 10),
                    imagen: p.imagen_url || p.imagen || null,
                    ubicacion_almacen: p.ubicacion_almacen || p.ubicacion || '',
                    fecha_vencimiento: p.fecha_vencimiento || null,
                    dias_para_vencer: p.fecha_vencimiento ? (function(){
                        const fv = new Date(p.fecha_vencimiento);
                        const hoy = new Date();
                        return Math.floor((fv - hoy) / (1000*60*60*24));
                    })() : null,
                    estado_vencimiento: 'normal',
                    estado: (p.stock_actual ?? p.stock ?? 0) > 0 ? 'disponible' : 'agotado',
                    // dato extra opcional para ranking
                    ventas: item.cantidad || item.total || item.ventas || null,
                };
            });

            // Deduplicar por producto id (sumando ventas si viene repetido)
            const agrupados = new Map();
            productos.forEach(prod => {
                if (!prod || !prod.id) return;
                if (!agrupados.has(prod.id)) {
                    agrupados.set(prod.id, { ...prod });
                } else {
                    const acc = agrupados.get(prod.id);
                    const ventasAcc = parseFloat(acc.ventas || 0) || 0;
                    const ventasProd = parseFloat(prod.ventas || 0) || 0;
                    acc.ventas = ventasAcc + ventasProd || null;
                    acc.imagen = acc.imagen || prod.imagen || null;
                    acc.ubicacion_almacen = acc.ubicacion_almacen || prod.ubicacion_almacen || '';
                    acc.stock_actual = Math.max(parseInt(acc.stock_actual || 0, 10), parseInt(prod.stock_actual || 0, 10));
                    agrupados.set(prod.id, acc);
                }
            });
            productos = Array.from(agrupados.values());
            productos.sort((a, b) => (parseFloat(b.ventas || 0) || 0) - (parseFloat(a.ventas || 0) || 0));
            productos = productos.slice(0, 10);

            // Completar imágenes faltantes consultando detalles del producto
            try {
                productos = await this.enriquecerImagenes(productos);
            } catch (e) {
                console.warn('ℹ️ No se pudieron enriquecer algunas imágenes del ranking:', e.message || e);
            }

            // Si la API no retorna datos válidos, fallback al popular disponible
            if (!productos || productos.length === 0) {
                const respFallback = await fetch('/api/pos-optimizado/populares?limite=10', { headers });
                const resultFallback = await respFallback.json();
                const vistos = new Set();
                productos = (resultFallback.data || []).filter(p => {
                    if (!p || !p.id) return false;
                    if (vistos.has(p.id)) return false;
                    vistos.add(p.id);
                    return (p.stock_actual || 0) > 0;
                }).map(p => ({
                    id: p.id,
                    nombre: p.nombre,
                    concentracion: p.concentracion || null,
                    presentacion: p.presentacion || 'Presentación estándar',
                    precio_venta: parseFloat(p.precio_venta || 0),
                    stock_actual: parseInt(p.stock_actual || 0, 10),
                    imagen: p.imagen_url || p.imagen || null,
                    ubicacion_almacen: p.ubicacion_almacen || '',
                    fecha_vencimiento: p.fecha_vencimiento || null,
                    dias_para_vencer: p.fecha_vencimiento ? (function(){
                        const fv = new Date(p.fecha_vencimiento);
                        const hoy = new Date();
                        return Math.floor((fv - hoy) / (1000*60*60*24));
                    })() : null,
                    estado_vencimiento: 'normal',
                    estado: (p.stock_actual || 0) > 0 ? 'disponible' : 'agotado',
                }));
            }

            this.productos = productos;
            this.renderizarProductos(this.productos);
            this.actualizarContadorProductos(this.productos.length);
            this.actualizarContadoresFiltros();
        } catch (error) {
            console.error('Error cargando productos más vendidos:', error);
            this.actualizarTituloProductos('Todos los Productos');
            this.actualizarContadorProductos('Error');
            // Fallback a todos los productos
            try {
                await this.cargarTodosLosProductos();
            } catch (err) {
                console.error('Error en fallback:', err);
                this.actualizarContadorProductos('Sin productos');
            }
        }
    }

    // Normaliza ruta de imagen asegurando prefijo
    normalizarImagen(ruta) {
        if (!ruta) return null;
        return (!ruta.startsWith('http') && !ruta.startsWith('/')) ? ('/' + ruta) : ruta;
    }

    preferirWebp(ruta) {
        if (!ruta) return '';
                const lower = ruta.toLowerCase();
        // No convertir imágenes por defecto ni SVG ni URLs remotas
        if (lower.includes('/assets/images/default-product') || /\.svg$/i.test(ruta) || /^https?:\/\//i.test(ruta)) {
            return ruta;
        }
        if (/\.(png|jpg|jpeg)$/i.test(ruta)) {
            return ruta.replace(/\.(png|jpg|jpeg)$/i, '.webp');
        }
        return ruta;
    }

    // Enriquecer productos con imágenes desde API de detalles
    async enriquecerImagenes(productos) {
        const tasks = productos.map(async (p) => {
                if (p.imagen_url) {
                p.imagen = this.normalizarImagen(p.imagen_url);
                return p;
            }
            if (p.imagen) {
                p.imagen = this.normalizarImagen(p.imagen);
                return p;
            }
            try {
                const resp = await fetch(`/api/productos/${p.id}/detalles`);
                const data = await resp.json();
                const imagen = data?.imagen_url || data?.imagen || data?.producto?.imagen_url || data?.producto?.imagen || null;
                p.imagen = this.normalizarImagen(imagen) || p.imagen || null;
            } catch (_err) {
                // silencioso, el componente usará fallback
            }
            return p;
        });
        await Promise.allSettled(tasks);
        return productos;
    }

    actualizarContadoresFiltros(productos = null) {
        // Usar productos pasados como parámetro, o this.productos como fallback
        const productosParaContar = productos || this.productos || [];

        // Contador todos
        const contadorTodos = productosParaContar.length;
        
        // Contador por vencer (30 días o menos)
        const contadorPorVencer = productosParaContar.filter(producto => {
            const diasParaVencer = producto.dias_para_vencer;
            return diasParaVencer && diasParaVencer <= 30 && diasParaVencer >= 0;
        }).length;
        
        // Contador alternativas (productos con palabras clave médicas)
        const contadorAlternativas = productosParaContar.filter(producto => {
            if (producto.stock_actual <= 0) return false;
            const palabrasClave = ['dolor', 'fiebre', 'tos', 'gripe', 'inflamacion', 'gastritis', 'diarrea', 'antibiotico', 'vitamina'];
            const nombreLower = producto.nombre.toLowerCase();
            return palabrasClave.some(palabra => nombreLower.includes(palabra));
        }).length;

        // Actualizar contadores en DOM
        const elementoTodos = document.getElementById('contadorTodos');
        const elementoPorVencer = document.getElementById('contadorPorVencer');
        const elementoAlternativas = document.getElementById('contadorAlternativas');

        if (elementoTodos) elementoTodos.textContent = contadorTodos;
        if (elementoPorVencer) elementoPorVencer.textContent = contadorPorVencer;
        if (elementoAlternativas) elementoAlternativas.textContent = contadorAlternativas;
    }

    async cargarTodosLosProductos() {
        try {
            // Obtener token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
            
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
            }

            const response = await fetch('/api/punto-venta/buscar-productos?termino=&limit=50', {
                method: 'GET',
                headers: headers
            });
            
            // Verificar si la respuesta es una redirección al login
            if (response.url.includes('/login') || response.status === 401) {
                console.log('Usuario no autenticado, redirigiendo al login...');
                window.location.href = '/login';
                return;
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.productos = data.productos;
                this.renderizarProductos(this.productos);
                this.actualizarContadorProductos(this.productos.length);
                this.actualizarTituloProductos('Productos Disponibles');
            }
        } catch (error) {
            console.error('Error cargando productos:', error);
        }
    }



    async cargarEstadisticasHoy() {
        try {
            // Obtener token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
            
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
            }

            const response = await fetch('/api/punto-venta/estadisticas-hoy', {
                method: 'GET',
                headers: headers
            });
            const data = await response.json();
            
            // Asegurar estructura segura
            const stats = (data && data.estadisticas) ? data.estadisticas : { ventas: 0, total: 0 };
            this.actualizarEstadisticas(stats);
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
            // Fallar silenciosamente para no interrumpir la experiencia del usuario
        }
    }



    renderizarProductos(productos) {
        const grid = document.getElementById('productosGrid');
        if (!grid) return;

        grid.innerHTML = '';

        if (productos.length === 0) {
            grid.innerHTML = `
                <div class="pos-no-productos">
                    <iconify-icon icon="solar:box-linear" class="pos-no-productos-icon"></iconify-icon>
                    <p>No se encontraron productos</p>
                    <small>Intenta ajustar los filtros de búsqueda</small>
                </div>
            `;
            this.actualizarContadoresFiltros([]);
            return;
        }

        this.virtualGrid.productos = productos;
        this.virtualGrid.renderedCount = 0;
        this.renderizarProductosChunk();
        this.instalarObserverVirtual(grid);
        this.actualizarContadoresFiltros(productos);
        grid.classList.add('fade-in');
        setTimeout(() => grid.classList.remove('fade-in'), 300);
    }

    renderizarProductosChunk() {
        const grid = document.getElementById('productosGrid');
        if (!grid) return;
        const start = this.virtualGrid.renderedCount;
        const end = Math.min(start + this.virtualGrid.pageSize, this.virtualGrid.productos.length);
        for (let i = start; i < end; i++) {
            const card = this.crearTarjetaProducto(this.virtualGrid.productos[i]);
            grid.appendChild(card);
        }
        this.virtualGrid.renderedCount = end;
        const sentinel = document.getElementById('productosGridSentinel');
        if (sentinel) grid.appendChild(sentinel);
        this.actualizarContadorProductos(this.virtualGrid.renderedCount);
    }

    instalarObserverVirtual(grid) {
        if (this.virtualGrid.observer) {
            try { this.virtualGrid.observer.disconnect(); } catch (_) {}
        }
        let sentinel = document.getElementById('productosGridSentinel');
        if (!sentinel) {
            sentinel = document.createElement('div');
            sentinel.id = 'productosGridSentinel';
            sentinel.style.height = '1px';
            sentinel.style.width = '100%';
            grid.appendChild(sentinel);
        }
        const obs = new IntersectionObserver((entries) => {
            const inter = entries.some(e => e.isIntersecting);
            if (inter) {
                if (this.virtualGrid.renderedCount < this.virtualGrid.productos.length) {
                    this.renderizarProductosChunk();
                }
            }
        });
        obs.observe(sentinel);
        this.virtualGrid.observer = obs;
    }

    crearTarjetaProducto(producto) {
        const card = document.createElement('div');
        card.className = 'producto-card';
        card.dataset.productoId = producto.id;

        // Determinar estado del stock y aplicar clases CSS farmacéuticas
        const estadoStock = this.determinarEstadoStock(producto);
        const claseStock = this.determinarClaseStock(producto.stock_actual);
        
        if (claseStock) {
            card.classList.add(claseStock);
        }

        // Combinar nombre y concentración evitando duplicados
        let nombreCompleto = producto.nombre || '';
        const conc = producto.concentracion || '';
        if (conc && !nombreCompleto.toLowerCase().includes(conc.toLowerCase())) {
            nombreCompleto = `${nombreCompleto} ${conc}`.trim();
        }

        // Usar presentación real del producto
        const presentacion = producto.presentacion || 'Presentación estándar';
        const concText = producto.concentracion ? ` \u2022 ${producto.concentracion}` : '';

        // URL de imagen (normalizada)
        const imagenUrl = this.normalizarImagen(producto.imagen_url) || this.normalizarImagen(producto.imagen) || '/assets/images/default-product.svg';

        // Determinar información de ubicación
        const infoUbicacion = this.determinarInfoUbicacion(producto);

        // Aplicar clase por vencimiento si corresponde
        if (estadoStock.clase === 'vencido') {
            card.classList.add('vencido');
        } else if (estadoStock.clase === 'por-vencer') {
            card.classList.add('por-vencer');
        }

        card.innerHTML = `
            <div class="producto-imagen">
                <picture>
                    <source srcset="${this.preferirWebp(imagenUrl)}" type="image/webp">
                    <img src="${imagenUrl}" alt="${producto.nombre}" loading="lazy" class="producto-imagen-img lazy" onload="this.classList.remove('lazy')" onerror="this.src='/assets/images/default-product.svg'">
                </picture>
                <div class="producto-badge ${estadoStock.clase}">${estadoStock.texto}</div>
            </div>
            
            <div class="producto-info">
                <div class="producto-header-info">
                    <div class="producto-nombre-completo">${nombreCompleto}</div>
                </div>
                
                <div class="producto-laboratorio">${presentacion.toUpperCase()}${concText}</div>
                
                <div class="producto-detalles-farmacia">
                    <div class="producto-stock ${claseStock || 'disponible'}">
                        <iconify-icon icon="solar:medical-kit-bold-duotone"></iconify-icon>
                        <span>${producto.stock_actual}</span>
                    </div>
                    ${infoUbicacion.badge}
                </div>
                
                <div class="producto-precio-container">
                    <div class="producto-precio">S/. ${parseFloat(producto.precio_venta).toFixed(2)}</div>
                </div>
                
                ${infoUbicacion.expandible}
            </div>
        `;

        // Agregar event listener solo si hay stock y NO está vencido
        const esVencido = estadoStock.clase === 'vencido';
        if (producto.stock_actual > 0 && !esVencido) {
            card.addEventListener('click', () => {
                this.agregarAlCarrito(producto.id);
            });
        } else if (esVencido) {
            card.style.cursor = 'not-allowed';
            card.title = 'Producto vencido - no vendible';
        }

        return card;
    }

    determinarClaseStock(stock) {
        if (stock <= 0) return 'sin-stock';
        if (stock <= 10) return 'stock-bajo';
        return 'disponible';
    }

    determinarInfoUbicacion(producto) {
        // Verificar si tiene ubicaciones detalladas
        const tieneUbicaciones = producto.ubicaciones_detalle && producto.ubicaciones_detalle.length > 0;
        const ubicacionAlmacen = producto.ubicacion_almacen;
        const stockTotal = producto.stock_actual || 0;

        // Si no hay stock, mostrar explícitamente "Sin ubicar" y no detallar ubicaciones
        if (stockTotal <= 0) {
            return {
                badge: `<div class="producto-ubicacion-badge sin-ubicacion">
                    <iconify-icon icon="solar:map-point-remove-bold"></iconify-icon>
                    <span>Sin ubicar</span>
                </div>`,
                expandible: ''
            };
        }
        
        if (!tieneUbicaciones && (!ubicacionAlmacen || ubicacionAlmacen === 'Sin ubicar' || ubicacionAlmacen.trim() === '')) {
            // Sin ubicación
            return {
                badge: `<div class="producto-ubicacion-badge sin-ubicacion">
                    <iconify-icon icon="solar:map-point-remove-bold"></iconify-icon>
                    <span>Sin ubicar</span>
                </div>`,
                expandible: ''
            };
        }
        
        if (tieneUbicaciones && producto.ubicaciones_detalle.length > 1) {
            // Múltiples ubicaciones
            const totalUbicado = producto.ubicaciones_detalle.reduce((sum, ub) => sum + (ub.cantidad || 0), 0);
            const ubicacionesItems = producto.ubicaciones_detalle
                .map(ubicacion => `<div class="ubicacion-item-expandible">
                    <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                    <span>${ubicacion.ubicacion_completa}: ${ubicacion.cantidad} und.</span>
                </div>`)
                .join('');
            
            // Obtener el estante principal (el que tiene más stock)
            const ubicacionPrincipal = producto.ubicaciones_detalle.reduce((prev, current) => 
                (current.cantidad > prev.cantidad) ? current : prev
            );
            const estantePrincipal = ubicacionPrincipal.ubicacion_completa.split(' - ')[0];
                
            return {
                badge: `<div class="producto-ubicacion-badge multiples-ubicaciones">
                    <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                    <span>${estantePrincipal} +${producto.ubicaciones_detalle.length - 1}</span>
                </div>`,
                expandible: `<div class="producto-ubicaciones-expandible">
                    <div class="ubicaciones-header">
                        <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                        <span>Ubicaciones:</span>
                    </div>
                    ${ubicacionesItems}
                </div>`
            };
        }
        
        if (tieneUbicaciones && producto.ubicaciones_detalle.length === 1) {
            // Una ubicación - verificar si es parcial
            const ubicacion = producto.ubicaciones_detalle[0];
            const cantidadUbicada = ubicacion.cantidad || 0;
            const esParcial = cantidadUbicada < stockTotal;
            
            if (esParcial) {
                // Ubicación parcial
                const estante = ubicacion.ubicacion_completa.split(' - ')[0];
                return {
                    badge: `<div class="producto-ubicacion-badge ubicacion-parcial">
                        <iconify-icon icon="solar:map-point-wave-bold"></iconify-icon>
                        <span>${estante} (Parcial)</span>
                    </div>`,
                    expandible: `<div class="producto-ubicaciones-expandible">
                        <div class="ubicacion-item-expandible parcial">
                            <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                            <span>${cantidadUbicada} unidades en ${ubicacion.ubicacion_completa}</span>
                        </div>
                        <div class="ubicacion-item-expandible sin-ubicar">
                            <iconify-icon icon="solar:map-point-remove-bold"></iconify-icon>
                            <span>${stockTotal - cantidadUbicada} sin ubicar</span>
                        </div>
                    </div>`
                };
            } else {
                // Totalmente ubicado
                const estante = ubicacion.ubicacion_completa.split(' - ')[0];
                return {
                    badge: `<div class="producto-ubicacion-badge con-ubicacion">
                        <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                        <span>${estante}</span>
                    </div>`,
                    expandible: `<div class="producto-ubicaciones-expandible">
                        <div class="ubicacion-item-expandible completa">
                            <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                            <span>${ubicacion.ubicacion_completa} - ${cantidadUbicada} unidades</span>
                        </div>
                    </div>`
                };
            }
        }
        
        // Fallback para ubicación simple del almacén
        if (ubicacionAlmacen) {
            return {
                badge: `<div class="producto-ubicacion-badge con-ubicacion">
                    <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                    <span>${ubicacionAlmacen}</span>
                </div>`,
                expandible: `<div class="producto-ubicaciones-expandible">
                    <div class="ubicacion-item-expandible completa">
                        <iconify-icon icon="solar:map-point-bold"></iconify-icon>
                        <span>${ubicacionAlmacen}</span>
                    </div>
                </div>`
            };
        }
        
        return {
            badge: '',
            expandible: ''
        };
    }

    determinarEstadoStock(producto) {
        const stock = parseInt(producto.stock_actual) || 0;
        const minimo = parseInt(producto.stock_minimo) || 10;
        const estadoVenc = (producto.estado_vencimiento || '').toString().toLowerCase();

        // Prioridad: vencido/por vencer por encima del stock
        if (estadoVenc === 'vencido') {
            return { clase: 'vencido', texto: 'Vencido' };
        }
        if (estadoVenc === 'por_vencer' || estadoVenc === 'por-vencer') {
            return { clase: 'por-vencer', texto: 'Por vencer' };
        }

        // Fallback por fecha/días si no vino estado_vencimiento
        const dias = typeof producto.dias_para_vencer !== 'undefined' ? parseInt(producto.dias_para_vencer) : null;
        const fechaVenc = producto.fecha_vencimiento ? new Date(producto.fecha_vencimiento) : null;
        if (fechaVenc && !isNaN(fechaVenc.getTime())) {
            const hoy = new Date();
            const diffDias = Math.floor((fechaVenc - hoy) / (1000 * 60 * 60 * 24));
            if (diffDias < 0) return { clase: 'vencido', texto: 'Vencido' };
            if (diffDias <= 30) return { clase: 'por-vencer', texto: 'Por vencer' };
        } else if (dias !== null) {
            if (dias < 0) return { clase: 'vencido', texto: 'Vencido' };
            if (dias <= 30 && dias > 0) return { clase: 'por-vencer', texto: 'Por vencer' };
        }

        if (stock <= 0) {
            return { clase: 'sin-stock', texto: 'Agotado' };
        } else if (stock <= 3) {
            return { clase: 'stock-critico', texto: 'Crítico' };
        } else if (stock <= minimo) {
            return { clase: 'stock-bajo', texto: 'Bajo' };
        } else {
            return { clase: 'stock-normal', texto: 'Disponible' };
        }
    }

    crearTooltipUbicacion(producto) {
        if (!producto.ubicaciones_detalle || producto.ubicaciones_detalle.length === 0) {
            return '';
        }

        const ubicacionesItems = producto.ubicaciones_detalle
            .map(ubicacion => `<span class="ubicacion-item-hover">${ubicacion.codigo}: ${ubicacion.cantidad} und.</span>`)
            .join('');

        return `<div class="producto-ubicaciones-hover">${ubicacionesItems}</div>`;
    }

    async buscarProductos() {
        const termino = document.getElementById('buscarProductos')?.value.trim();
        
        if (!termino) {
            // Ocultar filtros cuando no hay búsqueda
            this.ocultarFiltros();
            this.cargarProductosMasVendidos(); // Sin await para mayor velocidad
            this.actualizarTituloProductos('Top 10 Productos Disponibles');
            // Ocultar alternativas cuando no hay búsqueda
            this.ocultarAlternativas();
            return;
        }

        if (termino.length < 2) return;

        // Loading mínimo visual
        this.mostrarLoading(true);

        try {
            // Búsqueda con timeout para evitar cuelgues
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 segundos max

            const response = await fetch(`/api/punto-venta/buscar-productos?q=${encodeURIComponent(termino)}`, {
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);

            if (!response.ok) throw new Error('Error en la búsqueda');
            
            const data = await response.json();

            if (data.success) {
                this.productos = data.productos || [];
                this.renderizarProductos(this.productos);
                this.actualizarContadorProductos(this.productos.length);
                this.actualizarTituloProductos(`Resultados para "${termino}"`);

                // Mostrar filtros solo si hay productos encontrados
                if (this.productos.length > 0) {
                    this.mostrarFiltros();
                } else {
                    this.ocultarFiltros();
                }

                // Ocultar alternativas en búsqueda - solo se mostrarán con el filtro
                this.ocultarAlternativas();
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error en búsqueda:', error);
                this.mostrarError('Error al buscar productos');
            }
        } finally {
            this.mostrarLoading(false);
        }
    }

    async buscarAlternativas(termino) {
        try {
            console.log('🔍 Buscando alternativas para:', termino);
            
            const response = await fetch(`/api/punto-venta/buscar-alternativas?q=${encodeURIComponent(termino)}`, {
                method: 'GET',
                credentials: 'same-origin', // 🔑 Incluir cookies de sesión
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            console.log('📡 Respuesta del servidor:', response.status, response.statusText);
            
            if (!response.ok) {
                if (response.status === 401) {
                    console.error('❌ Error de autenticación - redirigiendo al login');
                    window.location.href = '/login';
                    return;
                } else if (response.status === 419) {
                    console.error('❌ Token CSRF expirado - recargando página');
                    window.location.reload();
                    return;
                }
                console.error('❌ Error en respuesta:', response.status, response.statusText);
                this.mostrarMensajeError('Error al buscar alternativas. Código: ' + response.status);
                return;
            }
            
            const data = await response.json();
            console.log('📊 Datos recibidos:', data);

            if (data.success && data.alternativas && data.alternativas.length > 0) {
                this.mostrarAlternativas(data.alternativas, data);
                console.log('✅ Alternativas encontradas:', data.alternativas.length);
            } else {
                console.log('ℹ️ No se encontraron alternativas para:', termino);
                this.mostrarMensajeNoAlternativas(termino);
            }
        } catch (error) {
            console.error('💥 Error buscando alternativas:', error);
            this.mostrarMensajeError('Error de conexión al buscar alternativas');
        }
    }

    mostrarAlternativas(alternativas, data = {}) {
        const contenedor = document.getElementById('productosAlternativos');
        const grid = document.getElementById('alternativasGrid');
        
        if (!contenedor || !grid) return;

        grid.innerHTML = '';
        
        // Mostrar información del criterio de búsqueda si está disponible
        if (data.criterio_busqueda) {
            const criterioDiv = document.createElement('div');
            criterioDiv.className = 'criterio-busqueda-info';
            criterioDiv.innerHTML = `
                <div class="criterio-header">
                    <iconify-icon icon="medical-icon:i-pharmacy"></iconify-icon>
                    <span>Análisis Farmacológico</span>
                </div>
                <p class="criterio-texto">${data.criterio_busqueda}</p>
            `;
            grid.appendChild(criterioDiv);
        }
        
        alternativas.forEach(producto => {
            const card = this.crearTarjetaAlternativa(producto);
            grid.appendChild(card);
        });

        contenedor.style.display = 'block';
    }

    ocultarAlternativas() {
        const contenedor = document.getElementById('productosAlternativos');
        if (contenedor) {
            contenedor.style.display = 'none';
        }
    }

    crearTarjetaAlternativa(producto) {
        const div = document.createElement('div');
        div.className = 'producto-card producto-alternativo';
        
        // Determinar color de similitud
        const similitudNum = parseInt(producto.similitud);
        let colorSimilitud = '#10b981'; // Verde por defecto
        if (similitudNum >= 80) colorSimilitud = '#059669'; // Verde oscuro
        else if (similitudNum >= 60) colorSimilitud = '#10b981'; // Verde
        else if (similitudNum >= 40) colorSimilitud = '#f59e0b'; // Amarillo
        else colorSimilitud = '#ef4444'; // Rojo

        div.innerHTML = `
            <div class="producto-imagen-container">
                <img src="${this.normalizarImagen(producto.imagen_url) || this.normalizarImagen(producto.imagen) || '/assets/images/default-product.svg'}" alt="${producto.nombre}" class="producto-imagen">
                <div class="similitud-badge" style="background-color: ${colorSimilitud}">
                    ${producto.similitud}
                </div>
            </div>
            
            <div class="producto-info">
                <h4 class="producto-nombre">${producto.nombre}</h4>
                <div class="producto-detalles">
                    <span class="producto-concentracion">${producto.concentracion || ''}</span>
                    <span class="producto-presentacion">${producto.presentacion}</span>
                </div>
                <div class="producto-categoria">
                    <iconify-icon icon="medical-icon:i-pharmacy"></iconify-icon>
                    ${producto.categoria}
                </div>
                <div class="razon-similitud">
                    <iconify-icon icon="solar:info-circle-linear"></iconify-icon>
                    <span>${producto.razon_similitud}</span>
                </div>
            </div>
            
            <div class="producto-precio-stock">
                <div class="producto-precio">S/. ${producto.precio_venta.toFixed(2)}</div>
                <div class="producto-stock">Stock: ${producto.stock_actual}</div>
            </div>
            
            <button class="pos-btn-agregar" onclick="pos.agregarAlCarrito(${producto.id})">
                <iconify-icon icon="solar:cart-plus-linear"></iconify-icon>
                Agregar
            </button>
        `;
        
        return div;
    }

    aplicarFiltros(filtroTipo = '') {
        this.filtroActual = filtroTipo;

        // Aplicar filtros a los productos
        const productosFiltrados = this.productos.filter(producto => {
            if (!filtroTipo) return true; // "Todos" - mostrar todos los productos

            switch (filtroTipo) {
                case 'por-vencer':
                    const diasParaVencer = producto.dias_para_vencer;
                    return diasParaVencer && diasParaVencer <= 30 && diasParaVencer >= 0;
                    
                case 'alternativas':
                    // Mostrar productos que podrían ser alternativas basado en:
                    // 1. Misma categoría
                    // 2. Palabras clave similares en el nombre (dolor, fiebre, tos, etc.)
                    // 3. Stock disponible
                    if (producto.stock_actual <= 0) return false;
                    
                    const palabrasClave = ['dolor', 'fiebre', 'tos', 'gripe', 'inflamacion', 'gastritis', 'diarrea', 'antibiotico', 'vitamina'];
                    const nombreLower = producto.nombre.toLowerCase();
                    
                    return palabrasClave.some(palabra => nombreLower.includes(palabra));
                    
                default:
                    return true;
            }
        });

        this.renderizarProductos(productosFiltrados);
        this.actualizarContadorProductos(productosFiltrados.length);
        
        // Actualizar título según filtro
        let titulo = 'Productos Más Vendidos';
        switch (filtroTipo) {
            case 'por-vencer':
                titulo = 'Productos por Vencer (30 días)';
                break;
            case 'alternativas':
                titulo = 'Productos Alternativos';
                break;
        }
        this.actualizarTituloProductos(titulo);
    }

    // 🚨 Funciones para mostrar mensajes de error y alternativas
    mostrarMensajeError(mensaje) {
        console.error('❌ Error:', mensaje);
        
        // Mostrar en el contenedor de alternativas
        const contenedor = document.getElementById('productosAlternativos');
        const grid = document.getElementById('alternativasGrid');
        
        if (contenedor && grid) {
            grid.innerHTML = `
                <div class="mensaje-error-alternativas">
                    <iconify-icon icon="solar:danger-circle-linear" class="error-icon"></iconify-icon>
                    <h4>Error al buscar alternativas</h4>
                    <p>${mensaje}</p>
                    <button class="btn-reintentar" onclick="pos.ocultarAlternativas()">
                        <iconify-icon icon="solar:refresh-linear"></iconify-icon>
                        Cerrar
                    </button>
                </div>
            `;
            contenedor.style.display = 'block';
        }
        
        // También mostrar toast si está disponible
        if (this.mostrarToast) {
            this.mostrarToast(mensaje, 'error');
        }
    }

    mostrarMensajeNoAlternativas(termino) {
        console.log('ℹ️ No se encontraron alternativas para:', termino);
        
        const contenedor = document.getElementById('productosAlternativos');
        const grid = document.getElementById('alternativasGrid');
        
        if (contenedor && grid) {
            grid.innerHTML = `
                <div class="mensaje-no-alternativas">
                    <iconify-icon icon="solar:magnifer-linear" class="search-icon"></iconify-icon>
                    <h4>Sin alternativas disponibles</h4>
                    <p>No se encontraron productos alternativos para "<strong>${termino}</strong>"</p>
                    <div class="sugerencias">
                        <p><strong>Sugerencias:</strong></p>
                        <ul>
                            <li>Verifica que el producto esté escrito correctamente</li>
                            <li>Intenta buscar por principio activo (ej: "ibuprofeno")</li>
                            <li>Busca por categoría terapéutica</li>
                        </ul>
                    </div>
                    <button class="btn-cerrar-alternativas" onclick="pos.ocultarAlternativas()">
                        <iconify-icon icon="solar:close-circle-linear"></iconify-icon>
                        Cerrar
                    </button>
                </div>
            `;
            contenedor.style.display = 'block';
        }
    }

    async agregarAlCarrito(productoId) {
        const producto = this.productos.find(p => p.id == productoId);
        if (!producto || producto.stock_actual <= 0) {
            this.mostrarNotificacionRapida('error', '¡Sin stock!', 'Producto no disponible');
            return;
        }

        let loteSeleccionadoId = null;
        let loteSeleccionadoCodigo = null;
        let loteSeleccionadoVencimiento = null;
        let precioVentaLote = null;

        // Lógica de selección de lotes
        if (producto.lotes_disponibles && producto.lotes_disponibles.length > 1) {
            // Preparar opciones para el modal
            const opcionesLotes = producto.lotes_disponibles.map(lote => {
                const vencimiento = lote.fecha_vencimiento || 'Sin fecha';
                const dias = lote.dias_para_vencer !== null ? `(${Math.round(lote.dias_para_vencer)} días)` : '';
                const claseVencimiento = lote.dias_para_vencer !== null && lote.dias_para_vencer < 30 ? 'text-red-600 font-bold' : '';
                
                return `<div class="lote-opcion p-2 border-b hover:bg-gray-50 cursor-pointer flex justify-between items-center">
                    <label class="flex items-center w-full cursor-pointer">
                        <input type="radio" name="lote_seleccion" value="${lote.id}" class="mr-2" 
                               data-codigo="${lote.lote}" 
                               data-vencimiento="${vencimiento}"
                               data-precio="${lote.precio_venta || producto.precio_venta}">
                        <div class="flex-1">
                            <div class="font-medium">Lote: ${lote.lote}</div>
                            <div class="text-xs text-gray-500">Vence: <span class="${claseVencimiento}">${vencimiento} ${dias}</span></div>
                        </div>
                        <div class="font-bold text-blue-600">${lote.cantidad} und.</div>
                    </label>
                </div>`;
            }).join('');

            // Opción automática (FEFO)
            const opcionAuto = `<div class="lote-opcion p-2 border-b bg-blue-50 hover:bg-blue-100 cursor-pointer flex justify-between items-center">
                <label class="flex items-center w-full cursor-pointer">
                    <input type="radio" name="lote_seleccion" value="auto" checked class="mr-2">
                    <div class="flex-1">
                        <div class="font-bold text-blue-800">Selección Automática (FEFO)</div>
                        <div class="text-xs text-blue-600">Prioriza lotes por vencer</div>
                    </div>
                </label>
            </div>`;

            const { value: loteId } = await Swal.fire({
                title: 'Seleccionar Lote',
                html: `<div class="text-left max-h-60 overflow-y-auto border rounded">
                        ${opcionAuto}
                        ${opcionesLotes}
                       </div>`,
                showCancelButton: false,
                confirmButtonText: 'Agregar',
                cancelButtonText: 'Cancelar',
                focusConfirm: false,
                customClass: {
                    confirmButton: 'swal2-confirm-visible' // Clase personalizada para forzar visibilidad
                },
                didOpen: () => {
                     // Asegurar que el botón de confirmar sea visible siempre
                     const btn = Swal.getConfirmButton();
                     if(btn) {
                         btn.style.cssText = `
                             display: inline-block !important;
                             opacity: 1 !important;
                             visibility: visible !important;
                             background-color: #3085d6 !important;
                             color: white !important;
                             box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                         `;
                     }
                },
                preConfirm: () => {
                    const selected = document.querySelector('input[name="lote_seleccion"]:checked');
                    if (!selected) return null;
                    if (selected.value === 'auto') return 'auto';
                    return {
                        id: selected.value,
                        codigo: selected.dataset.codigo,
                        vencimiento: selected.dataset.vencimiento,
                        precio: selected.dataset.precio
                    };
                }
            });

            if (!loteId) return; // Cancelado

            if (loteId !== 'auto') {
                loteSeleccionadoId = loteId.id;
                loteSeleccionadoCodigo = loteId.codigo;
                loteSeleccionadoVencimiento = loteId.vencimiento;
                precioVentaLote = parseFloat(loteId.precio);
            }
        } else if (producto.lotes_disponibles && producto.lotes_disponibles.length === 1) {
            // Solo un lote, usar ese (o dejar null para automático, da igual, pero mejor ser explícito si queremos mostrar info)
            const lote = producto.lotes_disponibles[0];
            loteSeleccionadoId = lote.id;
            loteSeleccionadoCodigo = lote.lote;
            loteSeleccionadoVencimiento = lote.fecha_vencimiento;
            precioVentaLote = lote.precio_venta ? parseFloat(lote.precio_venta) : null;
        }

        // Verificar si ya está en el carrito (considerando lote específico si se seleccionó)
        // NOTA: Si permitimos items separados por lote, deberíamos buscar por id + lote.
        // Por ahora, para simplificar, si selecciona "auto" se agrupa. Si selecciona lote específico, 
        // idealmente debería ser un item separado o forzar la selección.
        // Vamos a asumir que si selecciona un lote específico, se agrega como item nuevo O se suma si ya existe ese mismo lote.
        
        const itemKey = loteSeleccionadoId ? `${productoId}_${loteSeleccionadoId}` : `${productoId}_auto`;
        const itemExistenteIndex = this.carrito.findIndex(item => {
            const currentItemKey = item.lote_id ? `${item.id}_${item.lote_id}` : `${item.id}_auto`;
            return currentItemKey === itemKey;
        });
        
        if (itemExistenteIndex >= 0) {
            const itemExistente = this.carrito[itemExistenteIndex];
            // Validar stock del lote específico si aplica
            let stockLimite = producto.stock_actual;
            if (loteSeleccionadoId) {
                const lote = producto.lotes_disponibles.find(l => l.id == loteSeleccionadoId);
                if (lote) stockLimite = lote.cantidad;
            }

            if (itemExistente.cantidad >= stockLimite) {
                this.mostrarNotificacionRapida('warning', '¡Stock límite!', `Máximo ${stockLimite} unidades en este lote`);
                return;
            }
            itemExistente.cantidad++;
        } else {
            // Determinar stock disponible para este item
            let stockDisponible = producto.stock_actual;
            if (loteSeleccionadoId) {
                const lote = producto.lotes_disponibles.find(l => l.id == loteSeleccionadoId);
                if (lote) stockDisponible = lote.cantidad;
            }

            this.carrito.push({
                id: producto.id,
                lote_id: loteSeleccionadoId, // Nuevo campo
                lote_codigo: loteSeleccionadoCodigo, // Nuevo campo
                lote_vencimiento: loteSeleccionadoVencimiento, // Nuevo campo
                nombre: producto.nombre,
                concentracion: producto.concentracion,
                precio: precioVentaLote || parseFloat(producto.precio_venta),
                cantidad: 1,
                stock_disponible: stockDisponible
            });
        }

        // Actualización en batch para mayor velocidad
        this.renderizarCarrito();
        this.calcularTotales();
        this.actualizarContadorCarrito(); // Actualizar contador del carrito
        
        // Notificación discreta de éxito
        this.mostrarNotificacionRapida('success', '¡Agregado!', `${producto.nombre} en carrito`);
    }

    renderizarCarrito() {
        const contenedor = document.getElementById('carritoProductos');
        const btnLimpiar = document.querySelector('.pos-btn-limpiar-header');
        const seccionDescuento = document.getElementById('seccionDescuento');
        
        if (!contenedor) return;

        if (this.carrito.length === 0) {
            contenedor.innerHTML = `
                <div class="pos-carrito-vacio">
                    <iconify-icon icon="solar:cart-large-4-linear" class="pos-carrito-vacio-icon"></iconify-icon>
                    <p>Carrito vacío</p>
                    <small>Busca y agrega productos para comenzar</small>
                </div>
            `;
            if (btnLimpiar) btnLimpiar.style.display = 'none';
            if (seccionDescuento && this.configuracion.descuentos_habilitados) {
                seccionDescuento.style.display = 'none';
            }
            return;
        }

        contenedor.innerHTML = '';
        
        this.carrito.forEach((item, index) => {
            const itemElement = this.crearItemCarrito(item, index);
            contenedor.appendChild(itemElement);
        });

        if (btnLimpiar) btnLimpiar.style.display = 'inline-block';
        if (seccionDescuento && this.configuracion.descuentos_habilitados) {
            seccionDescuento.style.display = 'block';
        }
        this.actualizarContadorCarrito();
    }

    crearItemCarrito(item, index) {
        const div = document.createElement('div');
        div.className = 'carrito-item';
        
        // Crear nombre completo con concentración
        const nombreCompleto = item.concentracion ? 
            `${item.nombre} ${item.concentracion}` : 
            item.nombre;
        
        // Info de lote
        let infoLote = '';
        /*
        if (item.lote_codigo) {
            infoLote = `<div class="text-xs text-gray-500 mt-1 flex items-center">
                <iconify-icon icon="solar:box-minimalistic-linear" class="mr-1"></iconify-icon>
                Lote: ${item.lote_codigo} 
                ${item.lote_vencimiento ? `<span class="ml-2 text-red-500">Vence: ${item.lote_vencimiento}</span>` : ''}
            </div>`;
        }
        */

        div.innerHTML = `
            <div class="carrito-item-row">
                <div class="carrito-item-info">
                    <div class="carrito-item-nombre-completo">${nombreCompleto}</div>
                    
                    <div class="carrito-item-precio">S/. ${(item.precio * item.cantidad).toFixed(2)}</div>
                </div>
                
                <div class="carrito-item-controls">
                    <div class="cantidad-controls">
                        <button class="cantidad-btn" onclick="pos.cambiarCantidad(${index}, ${item.cantidad - 1})">-</button>
                        <input type="number" 
                               class="cantidad-input" 
                               value="${item.cantidad}" 
                               min="1" 
                               max="${item.stock_disponible}"
                               oninput="pos.clampCantidadInput(${index}, this)"
                               onchange="pos.cambiarCantidad(${index}, this.value)">
                        <button class="cantidad-btn" onclick="pos.cambiarCantidad(${index}, ${item.cantidad + 1})">+</button>
                    </div>
                    
                    <button class="carrito-item-remove" onclick="pos.removerDelCarrito(${index})">
                        <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                    </button>
                </div>
            </div>
        `;
        return div;
    }

    cambiarCantidad(index, nuevaCantidad) {
        nuevaCantidad = parseInt(nuevaCantidad);
        
        if (nuevaCantidad <= 0) {
            this.removerDelCarrito(index);
            return;
        }

        const item = this.carrito[index];
        if (nuevaCantidad > item.stock_disponible) {
            this.mostrarErrorStock(item.nombre, item.stock_disponible, nuevaCantidad);
            return;
        }

        item.cantidad = nuevaCantidad;
        this.renderizarCarrito();
        this.calcularTotales();
        this.actualizarContadorCarrito(); // Actualizar contador del carrito
        this.validarProcesamientoVenta();
    }

    // Ajuste en tiempo real mientras el usuario escribe en el input
    clampCantidadInput(index, inputEl) {
        let nuevaCantidad = parseInt(inputEl.value);
        if (isNaN(nuevaCantidad)) nuevaCantidad = 1;

        const item = this.carrito[index];
        if (nuevaCantidad <= 0) {
            nuevaCantidad = 1;
        }

        if (nuevaCantidad > item.stock_disponible) {
            // Ajustar silenciosamente al máximo y dar feedback rápido
            inputEl.value = item.stock_disponible;
            item.cantidad = item.stock_disponible;
            this.mostrarNotificacionRapida('warning', 'Stock límite', `Máximo ${item.stock_disponible} unidades`);
        } else {
            item.cantidad = nuevaCantidad;
        }

        this.renderizarCarrito();
        this.calcularTotales();
        this.actualizarContadorCarrito();
        this.validarProcesamientoVenta();
    }

    removerDelCarrito(index) {
        this.carrito.splice(index, 1);
        this.renderizarCarrito();
        this.calcularTotales();
        this.actualizarContadorCarrito(); // Actualizar contador del carrito
        this.validarProcesamientoVenta();
        this.mostrarToast('Producto removido del carrito', 'info');
    }

    limpiarCarrito() {
        if (this.carrito.length === 0) return;

        this.carrito = [];
        this.renderizarCarrito();
        this.calcularTotales();
        this.actualizarContadorCarrito(); // Actualizar contador del carrito
        this.validarProcesamientoVenta();
        this.mostrarToast('Carrito limpiado', 'info');
    }

    calcularTotales() {
        const subtotal = this.calcularSubtotal();
        const descuento = this.calcularDescuento(subtotal);
        const subtotalConDescuento = subtotal - descuento;
        const igv = this.calcularIGV(subtotalConDescuento);
        const total = subtotalConDescuento + igv;

        // Actualizar DOM de forma batch
        const elementos = {
            'subtotalVenta': `S/. ${subtotal.toFixed(2)}`,
            'descuentoVenta': `-S/. ${descuento.toFixed(2)}`,
            'igvVenta': `S/. ${igv.toFixed(2)}`,
            'totalVenta': `S/. ${total.toFixed(2)}`,
            'btnTotal': `S/. ${total.toFixed(2)}`
        };

        // Actualizar todos los elementos de una vez
        Object.entries(elementos).forEach(([id, texto]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = texto;
        });
        
        // Mostrar/ocultar fila de descuento
        const descuentoRow = document.getElementById('descuentoRow');
        if (descuentoRow) {
            descuentoRow.style.display = descuento > 0 ? 'flex' : 'none';
        }
        
        // Actualizar porcentaje de descuento mostrado
        if (this.descuento.tipo === 'porcentaje' && this.descuento.valor > 0) {
            const descuentoPorcentaje = document.getElementById('descuentoPorcentaje');
            if (descuentoPorcentaje) {
                descuentoPorcentaje.textContent = `(${this.descuento.valor}%)`;
            }
        }

        // Validar procesamiento
        this.validarProcesamientoVenta();
        this.calcularVuelto();
    }

    calcularVuelto() {
        // Solo calcular vuelto para pagos en efectivo
        if (this.metodosPagoSeleccionado !== 'efectivo') {
            this.actualizarVuelto(0);
            return;
        }

        const efectivoInput = document.getElementById('efectivoRecibido');
        const efectivoRecibido = efectivoInput ? parseFloat(efectivoInput.value) || 0 : 0;
        const total = this.calcularTotal();
        
        const vuelto = Math.max(0, efectivoRecibido - total);
        this.actualizarVuelto(vuelto);
        
        // Validar procesamiento
        this.validarProcesamientoVenta();
    }

    calcularTotal() {
        const subtotal = this.calcularSubtotal();
        const descuento = this.calcularDescuento(subtotal);
        const subtotalConDescuento = subtotal - descuento;
        const igv = this.calcularIGV(subtotalConDescuento);
        return subtotalConDescuento + igv;
    }
    
    calcularSubtotal() {
        if (!this.carrito?.length) return 0;
        return this.carrito.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    }
    
    calcularIGV(monto) {
        if (!this.configuracion.igv_habilitado) return 0;
        const porcentaje = this.configuracion.igv_porcentaje / 100;
        return Math.round(monto * porcentaje * 100) / 100;
    }
    
    calcularDescuento(subtotal) {
        if (this.descuento.tipo === 'porcentaje' && this.descuento.valor > 0) {
            return Math.round(subtotal * (this.descuento.valor / 100) * 100) / 100;
        } else if (this.descuento.tipo === 'monto' && this.descuento.valor > 0) {
            return Math.min(this.descuento.valor, subtotal);
        }
        return 0;
    }

    togglePagoEfectivo(mostrar) {
        const seccion = document.getElementById('pagoEfectivo');
        if (seccion) {
            seccion.style.display = mostrar ? 'block' : 'none';
        }
    }

    async procesarVentaConTipo(tipoComprobante) {
        console.log('🎯 Procesando venta con tipo:', tipoComprobante);
        
        // Validar venta
        const esValida = this.validarVenta();
        if (!esValida) {
            return; // El error ya se muestra en validarVenta()
        }

        try {
            this.mostrarLoading(true);

            // Preparar datos de la venta
            const datosVenta = {
                productos: this.carrito,
                total: this.calcularTotal(),
                subtotal: this.calcularSubtotal(),
                igv: this.calcularIGV(this.calcularSubtotal()),
                descuento: this.descuento.valor || 0,
                metodo_pago: this.metodosPagoSeleccionado,
                efectivo_recibido: this.metodosPagoSeleccionado === 'efectivo' ? 
                    parseFloat(document.getElementById('efectivoRecibido').value) || 0 : null,
                tipo_comprobante: tipoComprobante,
                cliente_id: this.obtenerClienteIdParaVenta()
            };

            console.log('📋 Datos de venta:', datosVenta);

            // Enviar venta al servidor (ruta web)
            const response = await fetch('/punto-venta/procesar-venta', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(datosVenta)
            });

            // Manejo robusto de respuesta
            let resultado;
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                resultado = await response.json();
                if (!response.ok || !resultado?.success) {
                    throw new Error(resultado?.message || 'Error al procesar la venta');
                }
            } else {
                const texto = await response.text();
                console.error('Respuesta no JSON del servidor:', texto.substring(0, 200));
                throw new Error('Error interno del servidor');
            }

            console.log('✅ Venta procesada:', resultado);

            // Limpiar formulario
            this.limpiarFormulario();

            // Procesar según tipo de comprobante e imprimir directamente
            this.imprimirDirecto(resultado.venta, tipoComprobante);

            // Recargar productos en segundo plano
            setTimeout(() => {
                this.cargarProductosMasVendidos();
                this.cargarEstadisticasHoy();
            }, 1000);

        } catch (error) {
            console.error('❌ Error al procesar venta:', error);
            this.mostrarError(error.message || 'Error inesperado al procesar la venta');
        } finally {
            this.mostrarLoading(false);
        }
    }

    async procesarVenta() {
        console.log('🛒 Iniciando procesamiento de venta...');
        
        if (this.carrito.length === 0) {
            this.mostrarError('El carrito está vacío');
            return;
        }

        // Validaciones específicas por método de pago
        if (!this.validarVenta()) {
            return;
        }

        // Tipo de comprobante por defecto: se decidirá tras procesar (para impresión)
        const tipoComprobante = 'ticket';
        
        // Obtener efectivo recibido de forma segura
        const efectivoElement = document.getElementById('efectivoRecibido');
        const efectivoRecibido = this.metodosPagoSeleccionado === 'efectivo' && efectivoElement ? 
            parseFloat(efectivoElement.value) || 0 : null;

        // Formatear productos para el backend (optimizado)
        const datosVenta = {
            productos: this.carrito.map(item => ({
                id: item.id,
                cantidad: item.cantidad,
                precio: item.precio,
                lote_id: item.lote_id // Include selected lote ID
            })),
            metodo_pago: this.metodosPagoSeleccionado,
            tipo_comprobante: tipoComprobante,
            cliente_id: this.obtenerClienteIdParaVenta(),
            efectivo_recibido: efectivoRecibido,
            descuento_tipo: this.descuento.tipo,
            descuento_valor: this.descuento.valor
        };

        console.log('📋 Procesando venta...', { metodo: this.metodosPagoSeleccionado, comprobante: tipoComprobante });
        
        // Loading optimizado
        const btnProcesar = document.getElementById('btnProcesarVenta');
        const textoOriginal = btnProcesar ? btnProcesar.innerHTML : null;
        if (btnProcesar) {
            btnProcesar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESANDO...';
            btnProcesar.disabled = true;
            btnProcesar.style.opacity = '0.7';
        }

        try {
            // Obtener token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                throw new Error('Token CSRF no encontrado');
            }

            const response = await fetch('/punto-venta/procesar-venta', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                },
                body: JSON.stringify(datosVenta)
            });

            // Manejo robusto de respuesta
            let data;
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                data = await response.json();
                if (!response.ok || !data?.success) {
                    throw new Error(data?.message || `Error HTTP: ${response.status}`);
                }
            } else {
                const texto = await response.text();
                console.error('Respuesta no JSON del servidor:', texto.substring(0, 200));
                throw new Error('Error interno del servidor');
            }

            if (data.success) {
                const venta = data.venta;
                // Limpiar para mejor UX
                this.limpiarCarrito();
                this.limpiarFormulario();

                // Mostrar éxito con opciones de impresión
                Swal.fire({
                    icon: 'success',
                    title: 'Venta procesada con éxito',
                    html: `
                        <div style="text-align:left; margin-top: 8px;">
                            <div style="color:#374151; margin-bottom: 8px;">Número: <strong>${venta.numero_sunat || venta.numero_venta}</strong></div>
                            <div style="color:#374151; margin-bottom: 16px;">Total: <strong>S/. ${parseFloat(venta.total || 0).toFixed(2)}</strong></div>
                            <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                                <button id="swalBoleta" style="padding:10px 14px; border-radius:8px; background:#dc2626; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                                    <iconify-icon icon="mdi:file-document-outline" style="font-size:18px"></iconify-icon>
                                    Boleta
                                </button>
                                <button id="swalTicket" style="padding:10px 14px; border-radius:8px; background:#2563eb; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                                    <iconify-icon icon="mdi:receipt-outline" style="font-size:18px"></iconify-icon>
                                    Ticket
                                </button>
                                <button id="swalWhatsApp" style="padding:10px 14px; border-radius:8px; background:#25d366; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                                    <iconify-icon icon="mdi:whatsapp" style="font-size:18px"></iconify-icon>
                                    WhatsApp
                                </button>
                            </div>
                        </div>
                    `,
                    showConfirmButton: false,
                    showCloseButton: true,
                    customClass: { closeButton: 'swal-close-modern' },
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        const b = document.getElementById('swalBoleta');
                        const t = document.getElementById('swalTicket');
                        const w = document.getElementById('swalWhatsApp');
                        if (b) b.addEventListener('click', () => this.imprimirBoletaDirecta(venta.id));
                        if (t) t.addEventListener('click', () => this.imprimirTicketDirecta(venta.id));
                        if (w) w.addEventListener('click', () => this.mostrarModalWhatsApp(venta));
                        if (w) w.addEventListener('click', () => this.mostrarModalWhatsApp(venta));

                        try {
                            if (!document.getElementById('swal-close-modern-style')){
                                const style = document.createElement('style');
                                style.id = 'swal-close-modern-style';
                                style.textContent = '.swal2-close.swal-close-modern{position:absolute;right:12px;top:12px;width:36px;height:36px;border-radius:9999px;background:#f1f5f9;color:#111827;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;font-size:22px;line-height:1;box-shadow:0 2px 6px rgba(0,0,0,.06)}.swal2-close.swal-close-modern:hover{background:#fee2e2;color:#b91c1c;border-color:#fecaca;transform:scale(1.05)}';
                                document.head.appendChild(style);
                            }
                        } catch(e) {}
                        const closeBtn = document.querySelector('.swal2-close');
                        if (closeBtn) {
                            closeBtn.setAttribute('aria-label', 'Cerrar');
                            closeBtn.classList.add('swal-close-modern');
                        }
                    }
                });

                // Recargar datos en segundo plano
                setTimeout(() => {
                    this.cargarProductosMasVendidos();
                    this.cargarEstadisticasHoy();
                }, 800);
            } else {
                throw new Error(data.message || 'Error al procesar la venta');
            }
        } catch (error) {
            console.error('❌ Error procesando venta:', error);
            this.mostrarError(
                error.message || 'Error al procesar la venta. Intente nuevamente.'
            );
        } finally {
            // Restaurar botón si existe
            if (btnProcesar) {
                btnProcesar.innerHTML = textoOriginal;
                btnProcesar.disabled = false;
                btnProcesar.style.opacity = '1';
            }
            this.validarProcesamientoVenta();
        }
    }

    async consultarDniCliente() {
        try {
            const dniInput = document.getElementById('dniCliente');
            // Restringir a números y máximo 8 dígitos
            if (dniInput) {
                dniInput.value = (dniInput.value || '').replace(/\D/g, '').slice(0, 8);
            }
            const dni = (dniInput?.value || '').trim();
            if (dni.length !== 8 || !/^[0-9]{8}$/.test(dni)) {
                this.mostrarError('El DNI debe tener 8 dígitos');
                dniInput && dniInput.focus();
                return;
            }

            // UI: deshabilitar botón y mostrar loading
            const btn = document.getElementById('consultarDniBtn');
            let originalHtml = '';
            if (btn) {
                btn.disabled = true;
                originalHtml = btn.innerHTML;
                btn.innerHTML = '<iconify-icon icon="solar:user-search-bold-duotone"></iconify-icon> Consultando... <span class="spinner"></span>';
            }

            const csrf = document.querySelector('meta[name="csrf-token"]');
            if (!csrf) {
                this.mostrarError('Token CSRF no encontrado');
                return;
            }

            const resp = await fetch('/punto-venta/consultar-dni', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf.getAttribute('content')
                },
                body: JSON.stringify({ dni })
            });

            const contentType = resp.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const txt = await resp.text();
                console.error('Respuesta no JSON al consultar DNI:', txt.substring(0, 200));
                throw new Error('Error interno del servidor');
            }

            const data = await resp.json();
            if (data.success && data.cliente) {
                this.clienteSeleccionado = data.cliente;
                this.actualizarInfoClienteUI(data.cliente, data.message);
                this.mostrarExito(data.message || 'Cliente encontrado');
                try {
                    localStorage.setItem('pos_last_dni', dni);
                    localStorage.setItem('pos_last_cliente', JSON.stringify(data.cliente));
                } catch (_) {}
            } else {
                this.mostrarErrorDni('No pudimos validar el DNI ingresado. Verifique los 8 dígitos e intente nuevamente.', dni);
            }

            // Validar botón procesar tras actualizar cliente
            this.validarProcesamientoVenta();
            // Restaurar botón
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml || '<iconify-icon icon="solar:user-search-bold-duotone"></iconify-icon> Consultar';
            }
        } catch (err) {
            console.error('Error consultando DNI:', err);
            this.mostrarErrorDni('No pudimos validar el DNI ingresado. Verifique los 8 dígitos e intente nuevamente.', (dniInput?.value || '').trim());
            const btn = document.getElementById('consultarDniBtn');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<iconify-icon icon="solar:user-search-bold-duotone"></iconify-icon> Consultar';
            }
        }
    }

    actualizarInfoClienteUI(cliente, message = '') {
        const info = document.getElementById('infoCliente');
        const nombreEl = document.getElementById('nombreCompleto');
        const dniEl = document.getElementById('dniCompleto');
        const fuenteEl = document.getElementById('fuenteConsulta');

        if (nombreEl) nombreEl.textContent = cliente.nombre_completo || `${cliente.nombres} ${cliente.apellido_paterno} ${cliente.apellido_materno || ''}`.trim();
        if (dniEl) dniEl.textContent = `DNI: ${cliente.dni || ''}`;
        // Ocultar el indicador de fuente (ya no se muestra)
        if (fuenteEl) { fuenteEl.textContent = ''; fuenteEl.style.display = 'none'; }
        if (info) info.style.display = 'block';
    }

    limpiarCliente() {
        this.clienteSeleccionado = null;
        const info = document.getElementById('infoCliente');
        const dniInput = document.getElementById('dniCliente');
        const nombreEl = document.getElementById('nombreCompleto');
        const dniEl = document.getElementById('dniCompleto');
        const fuenteEl = document.getElementById('fuenteConsulta');
        if (dniInput) dniInput.value = '';
        if (nombreEl) nombreEl.textContent = '-';
        if (dniEl) dniEl.textContent = 'DNI: -';
        if (fuenteEl) fuenteEl.textContent = '';
        if (info) info.style.display = 'none';
    }

    obtenerClienteIdParaVenta() {
        const dni = (document.getElementById('dniCliente')?.value || '').trim();
        const dniValido = /^[0-9]{8}$/.test(dni);
        if (this.clienteSeleccionado?.id && dniValido) {
            return this.clienteSeleccionado.id;
        }
        return null;
    }

// (El manejo de restricción del DNI se realiza dentro de setupEventListeners)

    limpiarFormulario() {
        console.log('🧹 Limpiando formulario...');
        
        // Limpiar campos de efectivo de forma segura
        const efectivoInput = document.getElementById('efectivoRecibido');
        if (efectivoInput) {
            efectivoInput.value = '';
        }

        // Restablecer comprobante de forma segura
        const comprobanteCheck = document.getElementById('conComprobante');
        if (comprobanteCheck) {
            comprobanteCheck.checked = false;
        }

        // Restablecer método de pago a efectivo
        this.metodosPagoSeleccionado = 'efectivo';
        
        // Actualizar botones de métodos de pago de forma segura
        const botonesMetodo = document.querySelectorAll('.pos-metodo-rapido');
        botonesMetodo.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.metodo === 'efectivo') {
                btn.classList.add('active');
            }
        });

        // Mostrar campo de efectivo
        this.actualizarInterfazPago(this.metodosPagoSeleccionado);

        // Limpiar vuelto
        this.actualizarVuelto(0);
        
        // Limpiar campo de búsqueda y darle el enfoque
        const buscarInput = document.getElementById('buscarProductos');
        if (buscarInput) {
            buscarInput.value = '';
            // Dar enfoque al campo de búsqueda después de un pequeño delay
            setTimeout(() => {
                buscarInput.focus();
            }, 100);
        }

        // Limpiar cliente por DNI y ocultar sección
        const toggleDni = document.getElementById('toggleDniCliente');
        const seccionDni = document.getElementById('clienteDniSection');
        if (toggleDni) {
            toggleDni.checked = false; // dejar desactivado por defecto después de vender
        }
        if (seccionDni) {
            seccionDni.style.display = 'none';
        }
        // Vaciar datos del cliente y el campo de DNI
        this.limpiarCliente();
        try {
            localStorage.removeItem('pos_last_dni');
            localStorage.removeItem('pos_last_cliente');
        } catch (_) {}

        // Restablecer descuento: apagar toggle y ocultar sección
        const toggleDescuento = document.getElementById('conDescuento');
        const seccionDescuento = document.getElementById('seccionDescuento');
        if (toggleDescuento) {
            toggleDescuento.checked = false;
        }
        if (seccionDescuento) {
            seccionDescuento.style.display = 'none';
            seccionDescuento.style.visibility = 'hidden';
            seccionDescuento.style.height = '0';
            seccionDescuento.style.margin = '0';
            seccionDescuento.style.padding = '0';
            seccionDescuento.style.overflow = 'hidden';
        }
        if (typeof quitarDescuento === 'function') {
            quitarDescuento();
        } else {
            // Fallback por si la función global no está disponible
            this.descuento = { tipo: 'porcentaje', valor: 0 };
        }
        
        console.log('✅ Formulario limpiado');
    }

    // Función para actualizar el vuelto en la interfaz
    actualizarVuelto(vuelto) {
        const vueltoElement = document.getElementById('vueltoCalculado');
        if (vueltoElement) {
            vueltoElement.textContent = `S/. ${vuelto.toFixed(2)}`;
        }
    }

    validarVenta() {
        // Verificar que hay productos en el carrito
        if (this.carrito.length === 0) {
            this.mostrarError('El carrito está vacío');
            return false;
        }

        // Validar que todos los productos tienen stock suficiente
        for (const item of this.carrito) {
            if (item.cantidad > item.stock_disponible) {
                this.mostrarError(`Stock insuficiente para ${item.nombre}. Disponible: ${item.stock_disponible}, en carrito: ${item.cantidad}`);
                return false;
            }
        }

        // Validaciones específicas por método de pago
        switch (this.metodosPagoSeleccionado) {
            case 'efectivo':
                const efectivoInput = document.getElementById('efectivoRecibido');
                const efectivoRecibido = efectivoInput ? parseFloat(efectivoInput.value) || 0 : 0;
                const total = this.calcularTotal();
                
                if (efectivoRecibido <= 0) {
                    this.mostrarError('Debe ingresar el monto de efectivo recibido');
                    efectivoInput?.focus();
                    return false;
                }
                
                if (efectivoRecibido < total) {
                    this.mostrarError(`El efectivo recibido (S/. ${efectivoRecibido.toFixed(2)}) es insuficiente. Total: S/. ${total.toFixed(2)}`);
                    efectivoInput?.focus();
                    return false;
                }
                break;
                
            case 'tarjeta':
                // Para tarjeta verificar que el total sea mayor a 0
                if (this.calcularTotal() <= 0) {
                    this.mostrarError('El total de la venta debe ser mayor a cero');
                    return false;
                }
                console.log('💳 Venta con tarjeta validada');
                break;
                
            case 'yape':
                // Para yape verificar que el total sea mayor a 0
                if (this.calcularTotal() <= 0) {
                    this.mostrarError('El total de la venta debe ser mayor a cero');
                    return false;
                }
                console.log('📱 Venta con Yape validada');
                break;
                
            default:
                this.mostrarError('Método de pago no válido');
                return false;
        }

        // Validar descuentos si están habilitados
        if (this.descuento.valor > 0) {
            const subtotal = this.calcularSubtotal();
            if (this.descuento.tipo === 'porcentaje' && this.descuento.valor > 100) {
                this.mostrarError('El descuento no puede ser mayor al 100%');
                return false;
            }
            if (this.descuento.tipo === 'monto' && this.descuento.valor > subtotal) {
                this.mostrarError('El descuento no puede ser mayor al subtotal');
                return false;
            }
        }

        return true;
    }



    // Utility functions
    mostrarLoading(mostrar) {
        const loading = document.getElementById('posLoading');
        if (loading) {
            loading.style.display = mostrar ? 'flex' : 'none';
        }
    }

    mostrarToast(mensaje, tipo = 'info') {
        // Implementar toast notification
        console.log(`${tipo.toUpperCase()}: ${mensaje}`);
    }

    // SweetAlert para ventas CON comprobante
    mostrarExitoConComprobante(venta) {
        Swal.fire({
            icon: false,
            title: false,
            html: `
                <div style="background: #f8f9fa; border-radius: 8px; padding: 30px 20px; margin: -20px; border: 1px solid #e9ecef;">
                    <!-- Icono de éxito simple -->
                    <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                        <div style="background: #6c757d; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                            <svg width="30" height="30" fill="white" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Título principal -->
                    <h2 style="margin: 0 0 20px 0; color: #495057; font-size: 1.5em; font-weight: 600; text-align: center;">
                        Venta Procesada
                    </h2>
                    
                    <!-- Información de la venta -->
                    <div style="background: white; border-radius: 6px; padding: 20px; margin: 15px 0; border: 1px solid #dee2e6;">
                        <!-- Total de venta -->
                        <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px; border: 1px solid #e9ecef;">
                            <div style="color: #6c757d; font-size: 0.9em; font-weight: 500; margin-bottom: 5px;">
                                Total de Venta
                            </div>
                            <div style="color: #495057; font-size: 1.3em; font-weight: 700;">
                                S/. ${venta.total}
                            </div>
                        </div>
                        
                        <!-- Mensaje de selección -->
                        <div style="text-align: center; padding: 15px; margin-top: 15px; background: #f8f9fa; border-radius: 6px; border: 1px solid #e9ecef;">
                            <div style="color: #6c757d; font-size: 0.95em; font-weight: 500;">
                                Seleccione una opción
                            </div>
                        </div>
                    </div>
                </div>
                
                <style>
                    .swal2-actions {
                        gap: 8px !important;
                        margin-top: 15px !important;
                        flex-wrap: wrap !important;
                        justify-content: center !important;
                    }
                    .swal2-confirm, .swal2-deny, .swal2-cancel {
                        padding: 8px 16px !important;
                        border-radius: 6px !important;
                        font-weight: 500 !important;
                        font-size: 0.9em !important;
                        margin: 2px !important;
                        min-width: 140px !important;
                    }
                </style>
            `,
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-receipt"></i> Boleta Simple',
            denyButtonText: '<i class="fas fa-file-pdf"></i> Boleta A4',
            cancelButtonText: '<i class="fab fa-whatsapp"></i> Enviar WhatsApp',
            confirmButtonColor: '#059669',
            denyButtonColor: '#dc2626',
            cancelButtonColor: '#25d366',
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: {
                confirmButton: 'swal-btn-always-visible',
                denyButton: 'swal-btn-always-visible',
                cancelButton: 'swal-btn-always-visible',
                popup: 'swal-no-scroll'
            },
            width: '480px',
            height: 'auto',
            padding: '0',
            background: 'transparent',
            didOpen: () => {
                // Eliminar scroll del modal completamente
                const popup = document.querySelector('.swal2-popup');
                const container = document.querySelector('.swal2-container');
                const htmlContent = document.querySelector('.swal2-html-container');
                
                if (popup) {
                    popup.style.overflow = 'hidden';
                    popup.style.maxHeight = 'none';
                    popup.style.height = 'auto';
                }
                
                if (container) {
                    container.style.overflow = 'hidden';
                }
                
                if (htmlContent) {
                    htmlContent.style.overflow = 'hidden';
                    htmlContent.style.maxHeight = 'none';
                }
                
                // Agregar estilos CSS para eliminar scroll
                const style = document.createElement('style');
                style.textContent = `
                    .swal2-popup.swal-no-scroll {
                        overflow: hidden !important;
                        max-height: none !important;
                    }
                    .swal2-html-container {
                        overflow: hidden !important;
                        max-height: none !important;
                    }
                    .swal2-container {
                        overflow: hidden !important;
                    }
                `;
                document.head.appendChild(style);
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Generar Boleta Simple (80mm)
                this.imprimirTicket(venta);
            } else if (result.isDenied) {
                // Generar Boleta A4
                this.descargarPDF(venta);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // Enviar por WhatsApp
                this.mostrarModalWhatsApp(venta);
            }
            // Limpiar formulario y enfocar búsqueda después de cerrar el modal
            this.limpiarFormulario();
        });
    }

    // SweetAlert para ventas SIN comprobante
    mostrarExitoSinComprobante(venta) {
        Swal.fire({
            icon: false,
            title: false,
            html: `
                <div style="background: #f0fdf4; border-radius: 8px; padding: 30px 20px; margin: -20px; border: 1px solid #bbf7d0;">
                    <!-- Icono de éxito simple -->
                    <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                        <div style="background: #22c55e; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                            <svg width="30" height="30" fill="white" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Título principal -->
                    <h2 style="margin: 0 0 20px 0; color: #15803d; font-size: 1.5em; font-weight: 600; text-align: center;">
                        Venta Procesada
                    </h2>
                    
                    <!-- Información de la venta -->
                    <div style="background: white; border-radius: 6px; padding: 20px; margin: 15px 0; border: 1px solid #d1fae5;">
                        <!-- Total de venta -->
                        <div style="text-align: center; padding: 15px; background: #f0fdf4; border-radius: 6px; border: 1px solid #bbf7d0;">
                            <div style="color: #16a34a; font-size: 0.9em; font-weight: 500; margin-bottom: 5px;">
                                Total de Venta
                            </div>
                            <div style="color: #15803d; font-size: 1.3em; font-weight: 700;">
                                S/. ${venta.total}
                            </div>
                        </div>
                        
                        <!-- Mensaje adicional -->
                        <div style="text-align: center; padding: 15px; margin-top: 15px; background: #f0fdf4; border-radius: 6px; border: 1px solid #bbf7d0;">
                            <div style="color: #16a34a; font-size: 0.95em; font-weight: 500;">
                                Transacción completada exitosamente
                            </div>
                        </div>
                    </div>
                </div>
            `,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: false,
            width: '400px',
            height: 'auto',
            padding: '0',
            background: 'transparent',
            allowOutsideClick: false,
            customClass: {
                popup: 'swal-no-scroll'
            },
            didOpen: () => {
                // Eliminar scroll del modal completamente
                const popup = document.querySelector('.swal2-popup');
                const container = document.querySelector('.swal2-container');
                const htmlContent = document.querySelector('.swal2-html-container');
                
                if (popup) {
                    popup.style.overflow = 'hidden';
                    popup.style.maxHeight = 'none';
                    popup.style.height = 'auto';
                }
                
                if (container) {
                    container.style.overflow = 'hidden';
                }
                
                if (htmlContent) {
                    htmlContent.style.overflow = 'hidden';
                    htmlContent.style.maxHeight = 'none';
                }
                
                // Agregar estilos CSS para eliminar scroll
                const style = document.createElement('style');
                style.textContent = `
                    .swal2-popup.swal-no-scroll {
                        overflow: hidden !important;
                        max-height: none !important;
                    }
                    .swal2-html-container {
                        overflow: hidden !important;
                        max-height: none !important;
                    }
                    .swal2-container {
                        overflow: hidden !important;
                    }
                `;
                document.head.appendChild(style);
            }
        }).then(() => {
            // Limpiar formulario y enfocar búsqueda después de cerrar el modal
            this.limpiarFormulario();
        });
    }

    // Función para generar ticket
    generarTicket(venta) {
        Swal.fire({
            icon: 'info',
            title: 'Generando Ticket',
            html: `
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 3em; margin-bottom: 16px;">🎫</div>
                    <p>Generando ticket para la venta ${venta.numero_venta}...</p>
                </div>
            `,
            showConfirmButton: false,
            timer: 2000
        }).then(() => {
            // Aquí implementarás la lógica real del ticket
            console.log('🎫 Generando ticket para venta:', venta.numero_venta);
            Swal.fire({
                icon: 'success',
                title: 'Ticket Generado',
                text: 'El ticket se ha generado correctamente',
                timer: 2000,
                showConfirmButton: false
            });
        });
    }

    // Función para generar PDF
    generarPDF(venta) {
        Swal.fire({
            title: '🧾 Generar Boleta Electrónica',
            text: 'Seleccione el formato de boleta que desea generar',
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '🧾 Boleta Simple (80mm)',
            denyButtonText: '📄 Boleta A4',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#059669',
            denyButtonColor: '#dc2626'
        }).then((result) => {
            if (result.isConfirmed) {
                // Imprimir boleta térmica
                this.imprimirTicket(venta);
            } else if (result.isDenied) {
                // Descargar boleta A4
                this.descargarPDF(venta);
            }
        });
    }

    // Función para imprimir boleta térmica (80mm)
    imprimirTicket(venta) {
        console.log('🧾 Imprimiendo boleta térmica para venta:', venta.id);
        
        // Mostrar loading
        Swal.fire({
            title: 'Preparando boleta térmica...',
            html: 'Generando boleta simple para impresora térmica (80mm)',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Abrir ventana de impresión
        const printWindow = window.open(`/punto-venta/ticket/${venta.id}`, '_blank', 'width=400,height=600');
        
        if (printWindow) {
            Swal.close();
            Swal.fire({
                icon: 'success',
                title: '🧾 Boleta Simple Preparada',
                text: 'Se abrió la ventana de impresión térmica',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo abrir la ventana de impresión. Verifica que no esté bloqueada por el navegador.'
            });
        }
    }

    // Función para descargar boleta A4
    descargarPDF(venta) {
        console.log('📄 Descargando boleta A4 para venta:', venta.id);
        
        // Mostrar loading
        Swal.fire({
            title: 'Generando Boleta A4...',
            html: 'Preparando boleta electrónica en formato A4',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Crear enlace de descarga
        const link = document.createElement('a');
        link.href = `/punto-venta/pdf/${venta.id}`;
        link.download = `boleta_a4_${venta.numero_venta}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Cerrar loading después de un momento
        setTimeout(() => {
            Swal.close();
            Swal.fire({
                icon: 'success',
                title: '📄 Boleta A4 Generada',
                text: 'La boleta en formato A4 se está descargando',
                timer: 2000,
                showConfirmButton: false
            });
        }, 1000);
    }



    // Función específica para errores de stock
    mostrarErrorStock(nombreProducto, stockDisponible, cantidadSolicitada) {
        Swal.fire({
            icon: 'error',
            title: '<span style="color: #dc2626; font-weight: 700;">Stock Insuficiente</span>',
            html: `
                <div style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-radius: 12px; padding: 20px; margin: 16px 0; border: 2px solid #dc2626;">
                    <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <div style="background: #dc2626; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                            <iconify-icon icon="material-symbols:inventory-2" style="color: white; font-size: 24px;"></iconify-icon>
                        </div>
                        <div style="text-align: left;">
                            <h3 style="margin: 0; color: #b91c1c; font-size: 1.1em; font-weight: 600;">Error de Stock</h3>
                            <p style="margin: 4px 0 0 0; color: #991b1b; font-size: 0.9em;">${new Date().toLocaleString('es-PE')}</p>
                        </div>
                    </div>
                    <div style="background: white; border-radius: 8px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <p style="color: #374151; margin: 0 0 12px 0; font-size: 1em; line-height: 1.5; font-weight: 600;">
                            El producto <strong style="color: #dc2626;">"${nombreProducto}"</strong> solo tiene <strong style="color: #059669;">${stockDisponible} unidades</strong> disponibles.
                        </p>
                        <p style="color: #6b7280; margin: 0; font-size: 0.95em; line-height: 1.4;">
                            Solicitaste <strong>${cantidadSolicitada} unidades</strong>. Por favor, ingresa una cantidad menor o igual a <strong style="color: #059669;">${stockDisponible}</strong>.
                        </p>
                    </div>
                </div>
            `,
            confirmButtonText: '<i class="fas fa-check"></i> Entendido',
            confirmButtonColor: '#dc2626',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'swal2-confirm-custom'
            },
            width: '500px',
            showClass: {
                popup: 'animate__animated animate__shakeX animate__faster'
            },
            didOpen: () => {
                // Asegurar que el botón sea siempre visible
                const confirmButton = document.querySelector('.swal2-confirm-custom');
                if (confirmButton) {
                    confirmButton.style.cssText = `
                        background-color: #dc2626 !important;
                        color: white !important;
                        border: none !important;
                        padding: 12px 24px !important;
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        font-size: 14px !important;
                        cursor: pointer !important;
                        transition: all 0.2s ease !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        gap: 8px !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                    `;
                    
                    // Agregar efectos hover
                    confirmButton.addEventListener('mouseenter', () => {
                        confirmButton.style.backgroundColor = '#b91c1c';
                        confirmButton.style.transform = 'translateY(-1px)';
                    });
                    
                    confirmButton.addEventListener('mouseleave', () => {
                        confirmButton.style.backgroundColor = '#dc2626';
                        confirmButton.style.transform = 'translateY(0)';
                    });
                }
            }
        });
    }

    // Mejorar la función mostrarError
    mostrarError(mensaje) {
        Swal.fire({
            icon: 'error',
            title: '<span style="color: #dc2626; font-weight: 700;">¡Oops! Algo salió mal</span>',
            html: `
                <div style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-radius: 12px; padding: 20px; margin: 16px 0; border: 2px solid #dc2626;">
                    <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <div style="background: #dc2626; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                            <svg width="24" height="24" fill="white" viewBox="0 0 24 24">
                                <path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div style="text-align: left;">
                            <h3 style="margin: 0; color: #b91c1c; font-size: 1.1em; font-weight: 600;">Error en la Operación</h3>
                            <p style="margin: 4px 0 0 0; color: #991b1b; font-size: 0.9em;">${new Date().toLocaleString('es-PE')}</p>
                        </div>
                    </div>
                    <div style="background: white; border-radius: 8px; padding: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <p style="color: #374151; margin: 0; font-size: 1em; line-height: 1.5;">${mensaje}</p>
                    </div>
                </div>
            `,
            confirmButtonText: '<i class="fas fa-check"></i> Entendido',
            confirmButtonColor: '#dc2626',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'swal2-confirm-custom'
            },
            width: '450px',
            showClass: {
                popup: 'animate__animated animate__shakeX animate__faster'
            },
            didOpen: () => {
                // Asegurar que el botón sea siempre visible
                const confirmButton = document.querySelector('.swal2-confirm-custom');
                if (confirmButton) {
                    confirmButton.style.cssText = `
                        background-color: #dc2626 !important;
                        color: white !important;
                        border: none !important;
                        padding: 12px 24px !important;
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        font-size: 14px !important;
                        cursor: pointer !important;
                        transition: all 0.2s ease !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        gap: 8px !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                    `;
                    
                    // Agregar efectos hover
                    confirmButton.addEventListener('mouseenter', () => {
                        confirmButton.style.backgroundColor = '#b91c1c';
                        confirmButton.style.transform = 'translateY(-1px)';
                    });
                    
                    confirmButton.addEventListener('mouseleave', () => {
                        confirmButton.style.backgroundColor = '#dc2626';
                        confirmButton.style.transform = 'translateY(0)';
                    });
                }
            }
        });
    }

    mostrarErrorSimple(mensaje) {
        Swal.fire({
            icon: 'error',
            title: mensaje,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc2626',
            width: '380px',
            buttonsStyling: false,
            customClass: { confirmButton: 'swal2-confirm-simple' },
            didOpen: () => {
                const btn = document.querySelector('.swal2-confirm-simple');
                if (btn) {
                    btn.style.cssText = `
                        background-color: #dc2626 !important;
                        color: #ffffff !important;
                        border: none !important;
                        padding: 10px 18px !important;
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        font-size: 14px !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                    `;
                }
            }
        });
    }

    mostrarErrorDni(mensaje, dniNumero = '') {
        Swal.fire({
            icon: false,
            title: false,
            html: `
                <div style="display:flex;flex-direction:column;align-items:center;gap:16px;">
                    <div style="width:70px;height:70px;border-radius:50%;border:4px solid #ef4444;display:flex;align-items:center;justify-content:center;">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="#ef4444">
                            <path d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div style="text-align:center;color:#374151;font-size:18px;line-height:1.5;font-weight:600;">
                        <span style="color:#b91c1c;font-weight:700;">El número de DNI</span>
                        <span style="color:#1d4ed8;background:#e0e7ff;border:1px solid #93c5fd;padding:2px 8px;border-radius:6px;font-weight:700;">${dniNumero || '—'}</span>
                        <span style="color:#374151;">no existe o es incorrecto.</span>
                    </div>
                    <div style="text-align:center;color:#6b7280;font-size:14px;">Verifique que el DNI sea correcto.</div>
                </div>
            `,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc2626',
            width: '420px',
            buttonsStyling: false,
            customClass: { confirmButton: 'swal2-confirm-dni' },
            didOpen: () => {
                const btn = document.querySelector('.swal2-confirm-dni');
                if (btn) {
                    btn.style.cssText = `
                        background-color: #dc2626 !important;
                        color: #ffffff !important;
                        border: none !important;
                        padding: 12px 22px !important;
                        border-radius: 8px !important;
                        font-weight: 700 !important;
                        font-size: 14px !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                    `;
                }
            }
        });
    }

    mostrarExito(mensaje) {
        this.mostrarToast(mensaje, 'success');
    }

    // Imprimir Boleta directamente sin abrir nueva pestaña
    imprimirBoletaDirecta(ventaId) {
        try {
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            iframe.setAttribute('aria-hidden', 'true');
        iframe.src = `/punto-venta/pdf/${ventaId}`;
            document.body.appendChild(iframe);
            iframe.onload = () => {
                try {
                    const iw = iframe.contentWindow;
                    iw.focus();
                    iw.print();
                } catch (err) {
                    console.warn('No se pudo imprimir desde iframe, abriendo nueva pestaña.', err);
                    window.open(iframe.src, '_blank');
                }
            };
        } catch (e) {
            console.error('Error al preparar impresión directa de boleta:', e);
            window.open(`/punto-venta/boleta/${ventaId}`, '_blank');
        }
    }

    // Imprimir Ticket directamente desde un iframe (igual que Boleta)
    imprimirTicketDirecta(ventaId) {
        try {
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            iframe.setAttribute('aria-hidden', 'true');
            iframe.src = `/punto-venta/ticket/${ventaId}`;
            document.body.appendChild(iframe);
            iframe.onload = () => {
                try {
                    const iw = iframe.contentWindow;
                    iw.focus();
                    iw.print();
                } catch (err) {
                    console.warn('No se pudo imprimir Ticket desde iframe, abriendo nueva pestaña.', err);
                    window.open(iframe.src, '_blank');
                }
            };
        } catch (e) {
            console.error('Error al preparar impresión directa de ticket:', e);
            window.open(`/punto-venta/ticket/${ventaId}`, '_blank');
        }
    }
    
    
    mostrarMensajeExito(mensaje) {
        // Mensaje de éxito simple que se cierra automáticamente
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        
        Toast.fire({
            icon: 'success',
            title: mensaje
        });
    }

    imprimirDirecto(venta, tipoComprobante) {
        console.log('🖨️ Imprimiendo directamente:', tipoComprobante, venta);
        
        let url = '';
        let titulo = '';
        let descripcion = '';
        
        switch(tipoComprobante) {
            case 'boleta':
                url = `/punto-venta/pdf/${venta.id}`;
                titulo = 'Imprimiendo Boleta';
                descripcion = 'Generando boleta electrónica...';
                break;
            case 'ticket':
                url = `/punto-venta/ticket/${venta.id}`;
                titulo = 'Imprimiendo Ticket';
                descripcion = 'Generando ticket térmico (80mm)...';
                break;

            default:
                console.error('❌ Tipo de comprobante no válido:', tipoComprobante);
                this.mostrarError('Tipo de comprobante no válido');
                return;
        }

        // Mostrar loading mientras se prepara
        Swal.fire({
            title: titulo,
            html: descripcion,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Para Ticket usar iframe y disparar impresión automática; para Boleta abrir ventana
        if (tipoComprobante === 'ticket') {
            try {
                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                iframe.setAttribute('aria-hidden', 'true');
                iframe.src = url;
                document.body.appendChild(iframe);
                iframe.onload = () => {
                    try {
                        const iw = iframe.contentWindow;
                        iw.focus();
                        iw.print();
                        setTimeout(() => {
                            Swal.close();
                            this.mostrarMensajeExito('Ticket procesado correctamente');
                        }, 800);
                    } catch (err) {
                        console.warn('No se pudo imprimir Ticket desde iframe, abriendo nueva pestaña.', err);
                        const win = window.open(url, '_blank', 'fullscreen=yes,scrollbars=yes');
                        if (win) {
                            setTimeout(() => {
                                Swal.close();
                                this.mostrarMensajeExito('Ticket procesado correctamente');
                            }, 1500);
                            win.focus();
                        } else {
                            Swal.close();
                            this.mostrarError('No se pudo abrir la ventana de impresión. Verifica que no esté bloqueada por el navegador.');
                        }
                    }
                };
            } catch (e) {
                console.error('Error al preparar impresión automática de Ticket:', e);
                const win = window.open(url, '_blank', 'fullscreen=yes,scrollbars=yes');
                if (win) {
                    setTimeout(() => {
                        Swal.close();
                        this.mostrarMensajeExito('Ticket procesado correctamente');
                    }, 1500);
                    win.focus();
                } else {
                    Swal.close();
                    this.mostrarError('No se pudo abrir la ventana de impresión. Verifica que no esté bloqueada por el navegador.');
                }
            }
        } else {
            const printWindow = window.open(url, '_blank', 'fullscreen=yes,scrollbars=yes');
            if (printWindow) {
                setTimeout(() => {
                    Swal.close();
                    this.mostrarMensajeExito(`${titulo.replace('Imprimiendo', 'Procesado')} correctamente`);
                }, 1500);
                printWindow.focus();
            } else {
                Swal.close();
                this.mostrarError('No se pudo abrir la ventana de impresión. Verifica que no esté bloqueada por el navegador.');
            }
        }
    }
    
    mostrarNotificacionRapida(tipo, titulo, mensaje) {
        // Notificación súper rápida sin bloquear interfaz
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: tipo,
            title: titulo,
            text: mensaje
        });
    }

    actualizarEstadisticas(stats) {
        const safe = stats || {};
        const ventasValor = (safe.ventas ?? safe.ventasHoy ?? safe.totalVentas ?? 0);
        const totalValor = (safe.total ?? safe.totalHoy ?? safe.montoTotal ?? 0);

        const ventasEl = document.getElementById('ventasHoy');
        const totalEl = document.getElementById('totalHoy');
        if (ventasEl) {
            ventasEl.textContent = `${ventasValor}`;
        }
        if (totalEl) {
            const monto = parseFloat(totalValor || 0);
            totalEl.textContent = `S/. ${isNaN(monto) ? '0.00' : monto.toFixed(2)}`;
        }
    }

    actualizarContadorProductos(cantidad) {
        document.getElementById('productosCount').textContent = `${cantidad} productos encontrados`;
    }

    actualizarContadorCarrito() {
        const total = this.carrito.reduce((sum, item) => sum + item.cantidad, 0);
        
        // Actualizar contador en el elemento principal
        const contadorElement = document.getElementById('contadorProductos');
        if (contadorElement) {
            contadorElement.textContent = `(${total})`;
        }
        
        // Actualizar título del carrito
        const carritoTitulo = document.querySelector('.pos-carrito-header h3');
        if (carritoTitulo) {
            carritoTitulo.innerHTML = `<iconify-icon icon="solar:cart-large-2-bold-duotone"></iconify-icon> Carrito (${total})`;
        }
        
        // Mostrar/ocultar botón limpiar
        const btnLimpiar = document.querySelector('.pos-btn-limpiar-compacto');
        if (btnLimpiar) {
            btnLimpiar.style.display = total > 0 ? 'flex' : 'none';
        }
    }

    actualizarTituloProductos(titulo) {
        document.getElementById('productosTitulo').innerHTML = `
            <iconify-icon icon="solar:crown-bold-duotone"></iconify-icon>
            ${titulo}
        `;
    }

    // Función para mostrar filtros
    mostrarFiltros() {
        const filtrosContainer = document.querySelector('.pos-filters-buttons');
        if (filtrosContainer) {
            filtrosContainer.style.display = 'flex';
        }
    }

    // Función para ocultar filtros
    ocultarFiltros() {
        const filtrosContainer = document.querySelector('.pos-filters-buttons');
        if (filtrosContainer) {
            filtrosContainer.style.display = 'none';
        }
        // También resetear el filtro activo al botón "Todos"
        document.querySelectorAll('.pos-filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        const botonTodos = document.querySelector('.pos-filter-btn[data-filtro=""]');
        if (botonTodos) {
            botonTodos.classList.add('active');
        }
    }

    limpiarBusqueda() {
        const input = document.getElementById('buscarProductos');
        if (input) {
            input.value = '';
            this.ocultarFiltros(); // Ocultar filtros al limpiar búsqueda
            this.cargarProductosMasVendidos();
            this.actualizarTituloProductos('Top 10 Productos Disponibles');
        }
    }

    inicializarContadores() {
        this.actualizarContadorCarrito();
        this.calcularTotales();
    }

    // Nueva función para mostrar modal de WhatsApp
    mostrarModalWhatsApp(venta) {
        Swal.fire({
            title: '<i class="fab fa-whatsapp" style="color: #25d366;"></i> Enviar por WhatsApp',
            html: `
                <div style="text-align: left; padding: 10px;">
                    <input type="hidden" id="whatsapp-formato" value="ticket" />
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                            Número de teléfono del cliente:
                        </label>
                        <input type="tel" id="whatsapp-phone" class="swal2-input" 
                               placeholder="Ej: 987654321" 
                               style="margin: 0; width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;"
                               maxlength="9" pattern="[0-9]{9}">
                        <small style="color: #666; font-size: 12px;">Ingrese solo los 9 dígitos (sin +51)</small>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;">
                        <h4 style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">
                            <i class="fas fa-info-circle" style="color: #17a2b8;"></i> Información de la venta
                        </h4>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="color: #666;">Total:</span>
                            <span style="font-weight: 600; color: #333;">S/. ${venta.total}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="color: #666;">Fecha:</span>
                            <span style="color: #333;">${new Date().toLocaleDateString('es-PE')}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #666;">Hora:</span>
                            <span style="color: #333;">${new Date().toLocaleTimeString('es-PE')}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 6px;">
                            <span style="color: #666;">Comprobante:</span>
                            <span style="color: #333; font-weight:600;">Ticket térmico</span>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: false,
            confirmButtonText: '<i class="fab fa-whatsapp"></i> Enviar WhatsApp',
            cancelButtonText: '<i class="fas fa-arrow-left"></i> Volver',
            confirmButtonColor: '#25d366',
            cancelButtonColor: '#6c757d',
            width: '450px',
            allowOutsideClick: false,
            buttonsStyling: false,
            preConfirm: () => {
                const phone = document.getElementById('whatsapp-phone').value.trim();
                
                if (!phone) {
                    Swal.showValidationMessage('Por favor ingrese el número de teléfono');
                    return false;
                }
                
                if (!/^[0-9]{9}$/.test(phone)) {
                    Swal.showValidationMessage('El número debe tener exactamente 9 dígitos');
                    return false;
                }
                
                return phone;
            },
            didOpen: () => {
                // Enfocar el input de teléfono
                const phoneInput = document.getElementById('whatsapp-phone');
                if (phoneInput) {
                    phoneInput.focus();
                    
                    // Solo permitir números
                    phoneInput.addEventListener('input', function(e) {
                        this.value = this.value.replace(/[^0-9]/g, '');
                        if (this.value.length > 9) {
                            this.value = this.value.slice(0, 9);
                        }
                    });
                    
                    // Permitir envío con Enter
                    phoneInput.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            document.querySelector('.swal2-confirm').click();
                        }
                    });
                }

                // Asegurar que los botones sean visibles sin hover
                const actions = document.querySelector('.swal2-actions');
                if (actions) {
                    actions.style.display = 'flex';
                    actions.style.gap = '10px';
                    actions.querySelectorAll('button').forEach(btn => {
                        btn.style.opacity = '1';
                        btn.style.visibility = 'visible';
                        btn.style.display = 'inline-flex';
                        btn.style.alignItems = 'center';
                        btn.style.gap = '8px';
                        btn.style.borderRadius = '8px';
                        btn.style.padding = '10px 16px';
                        btn.style.fontWeight = '600';
                    });
                    const confirmBtn = actions.querySelector('.swal2-confirm');
                    const cancelBtn = actions.querySelector('.swal2-cancel');
                    if (confirmBtn) {
                        confirmBtn.style.backgroundColor = '#25d366';
                        confirmBtn.style.color = '#fff';
                        confirmBtn.style.border = 'none';
                    }
                    if (cancelBtn) {
                        cancelBtn.style.backgroundColor = '#e5e7eb'; // gris plomo
                        cancelBtn.style.color = '#111827';
                        cancelBtn.style.border = 'none';
                    }
                    // Ocultar cualquier botón de negar/cerrar si existiera por estilos globales
                    const denyBtn = actions.querySelector('.swal2-deny');
                    const closeBtn = actions.querySelector('.swal2-close');
                    if (denyBtn) denyBtn.style.display = 'none';
                    if (closeBtn) closeBtn.style.display = 'none';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.enviarWhatsApp(venta, result.value);
            }
        });
    }

    // Función para enviar por WhatsApp
    async enviarWhatsApp(venta, telefono) {
        try {
            // Mostrar loading
            Swal.fire({
                title: 'Enviando...',
                html: '<i class="fab fa-whatsapp fa-spin" style="font-size: 3em; color: #25d366;"></i><br><br>Preparando mensaje de WhatsApp...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });

            // Llamar al endpoint para enviar WhatsApp
            const response = await fetch('/api/whatsapp/enviar-boleta', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    venta_id: venta.id,
                    telefono: telefono,
                    tipo_comprobante: (document.getElementById('whatsapp-formato')?.value) || 'ticket',
                    guardar_en_cliente: !!(document.getElementById('whatsapp-guardar')?.checked)
                })
            });

            const data = await response.json();

            if (data.success) {
                // Mostrar éxito y abrir WhatsApp
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo para enviar!',
                    html: `
                        <div style="text-align: center; padding: 10px;">
                            <p style="margin-bottom: 15px;">El mensaje está listo. Se abrirá WhatsApp en unos segundos...</p>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin: 10px 0;">
                                <small style="color: #666;">Número: +51 ${telefono}</small>
                            </div>
                        </div>
                    `,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                }).then(() => {
                    // Abrir WhatsApp
                    window.open(data.url_whatsapp || data.whatsapp_url, '_blank');
                });
            } else {
                throw new Error(data.message || 'Error al preparar el mensaje');
            }

        } catch (error) {
            console.error('Error al enviar WhatsApp:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error al enviar',
                text: error.message || 'No se pudo preparar el mensaje de WhatsApp. Inténtelo nuevamente.',
                confirmButtonColor: '#dc3545'
            });
        }
    }

    debounce(func, delay) {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }
}

/**
 * FUNCIÓN GLOBAL PARA COMPATIBILIDAD - MEJORADA
 */
function procesarVenta() {
    console.log('🎯 Llamando a procesarVenta desde función global');
    
    // Verificar que el objeto pos existe
    if (!window.pos) {
        console.error('❌ Objeto pos no encontrado');
        Swal.fire({
            icon: 'error',
            title: 'Error del Sistema',
            text: 'Sistema de ventas no inicializado correctamente',
            confirmButtonText: 'Recargar',
            confirmButtonColor: '#dc2626'
        }).then(() => {
            location.reload();
        });
        return;
    }
    
    try {
        window.pos.procesarVenta();
    } catch (error) {
        console.error('❌ Error en procesarVenta:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error al Procesar Venta',
            text: error.message || 'Error inesperado al procesar la venta',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc2626'
        });
    }
}

// Asegurar que esta función esté disponible globalmente}

// Función global para procesar venta con tipo específico
function procesarVentaConTipo(tipoComprobante) {
    console.log('🎯 Procesando venta con tipo:', tipoComprobante);
    
    // Verificar que el objeto pos existe
    if (!window.pos) {
        console.error('❌ Objeto pos no encontrado');
        Swal.fire({
            icon: 'error',
            title: 'Error del Sistema',
            text: 'Sistema de ventas no inicializado correctamente',
            confirmButtonText: 'Recargar',
            confirmButtonColor: '#dc2626'
        }).then(() => {
            location.reload();
        });
        return;
    }
    
    try {
        window.pos.procesarVentaConTipo(tipoComprobante);
    } catch (error) {
        console.error('❌ Error en procesarVentaConTipo:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error al Procesar Venta',
            text: error.message || 'Error inesperado al procesar la venta',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#dc2626'
        });
    }
}

window.procesarVenta = procesarVenta;
window.procesarVentaConTipo = procesarVentaConTipo;
// Funciones globales para descuento inline
function toggleDescuento() {
    const checkbox = document.getElementById('conDescuento');
    const seccionDescuento = document.getElementById('seccionDescuento');
    
    if (checkbox.checked) {
        // Mostrar sección de descuento
        seccionDescuento.style.display = 'block';
        seccionDescuento.style.visibility = 'visible';
        seccionDescuento.style.height = 'auto';
        seccionDescuento.style.margin = '0 0 10px 0';
        seccionDescuento.style.padding = '10px';
        seccionDescuento.style.overflow = 'visible';
        
        // Enfocar el input de descuento
        setTimeout(() => {
            const input = document.getElementById('descuentoInlineInput');
            if (input) {
                input.focus();
            }
        }, 100);
    } else {
        // Ocultar completamente la sección de descuento
        seccionDescuento.style.display = 'none';
        seccionDescuento.style.visibility = 'hidden';
        seccionDescuento.style.height = '0';
        seccionDescuento.style.margin = '0';
        seccionDescuento.style.padding = '0';
        seccionDescuento.style.overflow = 'hidden';
        
        // Quitar cualquier descuento aplicado
        quitarDescuento();
    }
}

// Asegurar que la sección esté oculta al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const seccionDescuento = document.getElementById('seccionDescuento');
    const checkbox = document.getElementById('conDescuento');
    
    if (seccionDescuento) {
        seccionDescuento.style.display = 'none';
        seccionDescuento.style.visibility = 'hidden';
        seccionDescuento.style.height = '0';
        seccionDescuento.style.margin = '0';
        seccionDescuento.style.padding = '0';
        seccionDescuento.style.overflow = 'hidden';
    }
    
    if (checkbox) {
        checkbox.checked = false;
    }
});

function cambiarTipoDescuentoInline(tipo) {
    const input = document.getElementById('descuentoInlineInput');
    const symbol = document.getElementById('descuentoSimbolo');
    
    if (tipo === 'porcentaje') {
        symbol.textContent = '%';
        if (window.pos && window.pos.configuracion) {
            input.max = window.pos.configuracion.descuento_maximo_porcentaje;
        }
        input.placeholder = '0';
    } else {
        symbol.textContent = 'S/.';
        input.max = '';
        input.placeholder = '0.00';
    }
    
    // Limpiar valor y recalcular
    input.value = '';
    aplicarDescuentoInline();
}

function aplicarDescuentoInline() {
    if (!window.pos) return;
    
    const tipoRadio = document.querySelector('input[name="tipoDescuento"]:checked');
    const tipo = tipoRadio ? tipoRadio.value : 'porcentaje';
    const input = document.getElementById('descuentoInlineInput');
    const valor = parseFloat(input.value) || 0;
    
    // Si el valor es 0, quitar el descuento
    if (valor <= 0) {
        window.pos.descuento = {
            tipo: tipo,
            valor: 0,
            monto: 0
        };
        window.pos.calcularTotales();
        return;
    }
    
    // Validar descuento
    if (tipo === 'porcentaje') {
        const maxPorcentaje = window.pos.configuracion.descuento_maximo_porcentaje;
        if (valor > maxPorcentaje) {
            input.value = maxPorcentaje;
            window.pos.mostrarToast(`Descuento máximo: ${maxPorcentaje}%`, 'warning');
            return;
        }
    } else {
        // Para monto fijo, validar que no sea mayor al subtotal
        const subtotal = window.pos.calcularSubtotal();
        if (valor > subtotal) {
            input.value = subtotal.toFixed(2);
            window.pos.mostrarToast('El descuento no puede ser mayor al subtotal', 'warning');
            return;
        }
    }
    
    // Aplicar descuento
    window.pos.descuento = {
        tipo: tipo,
        valor: valor,
        monto: 0 // Se calculará dinámicamente
    };
    
    // Actualizar totales
    window.pos.calcularTotales();
}

function quitarDescuento() {
    if (!window.pos) return;
    
    // Limpiar input
    const input = document.getElementById('descuentoInlineInput');
    if (input) {
        input.value = '';
    }
    
    // Resetear tipo a porcentaje
    const radioPorcentaje = document.querySelector('input[name="tipoDescuento"][value="porcentaje"]');
    if (radioPorcentaje) {
        radioPorcentaje.checked = true;
        cambiarTipoDescuentoInline('porcentaje');
    }
    
    // Quitar descuento
    window.pos.descuento = {
        tipo: 'porcentaje',
        valor: 0,
        monto: 0
    };
    
    // Actualizar totales
    window.pos.calcularTotales();
    
    window.pos.mostrarToast('Descuento eliminado', 'info');
}

// Otras funciones globales para compatibilidad
function limpiarBusqueda() {
    if (window.pos) {
        window.pos.limpiarBusqueda();
    }
}

function cambiarFiltro(filtroTipo, botonElement) {
    console.log('🔄 Cambiando filtro a:', filtroTipo);
    
    // Verificar si hay productos para filtrar
    const inputBusqueda = document.getElementById('buscarProductos');
    const termino = inputBusqueda ? inputBusqueda.value.trim() : '';
    
    // Si no hay término de búsqueda o no hay productos, no permitir cambio de filtro
    if (!termino || !window.pos || !window.pos.productos || window.pos.productos.length === 0) {
        console.log('ℹ️ No hay productos para filtrar, ignorando cambio de filtro');
        return;
    }
    
    // Remover clase active de todos los botones
    document.querySelectorAll('.pos-filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Agregar clase active al botón seleccionado
    botonElement.classList.add('active');
    
    // Si es el filtro de alternativas, mostrar la sección de alternativas
    if (filtroTipo === 'alternativas') {
        console.log('🔍 Activando filtro de alternativas');
        const contenedor = document.getElementById('productosAlternativos');
        if (contenedor) {
            contenedor.style.display = 'block';
            console.log('✅ Contenedor de alternativas mostrado');
            
            console.log('🔍 Término de búsqueda actual:', termino);
            
            if (termino && window.pos) {
                console.log('🚀 Iniciando búsqueda de alternativas para:', termino);
                // Mostrar mensaje de carga primero
                const grid = document.getElementById('alternativasGrid');
                if (grid) {
                    grid.innerHTML = `
                        <div class="mensaje-info-alternativas">
                            <iconify-icon icon="solar:refresh-linear" class="info-icon" style="animation: spin 1s linear infinite;"></iconify-icon>
                            <h4>Buscando Alternativas</h4>
                            <p>Analizando alternativas farmacológicas para "${termino}"...</p>
                        </div>
                    `;
                }
                window.pos.buscarAlternativas(termino);
            }
        } else {
            console.error('❌ No se encontró el contenedor de alternativas');
        }
    } else {
        // Para otros filtros, ocultar la sección de alternativas
        console.log('🙈 Ocultando sección de alternativas para filtro:', filtroTipo);
        const contenedor = document.getElementById('productosAlternativos');
        if (contenedor) {
            contenedor.style.display = 'none';
        }
    }
    
    // Aplicar el filtro
    if (window.pos) {
        window.pos.aplicarFiltros(filtroTipo);
    }
}

function limpiarCarrito() {
    if (window.pos) {
        window.pos.limpiarCarrito();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando Sistema POS Profesional...');
    
    try {
        // Verificar elementos base antes de inicializar (el botón se crea dinámicamente)
        const elementosBase = {
            buscarProductos: document.getElementById('buscarProductos'),
            carritoProductos: document.getElementById('carritoProductos'),
            productosGrid: document.getElementById('productosGrid')
        };

        const faltantesBase = Object.entries(elementosBase)
            .filter(([_, el]) => !el)
            .map(([nombre]) => nombre);

        if (faltantesBase.length > 0) {
            // Registrar como información (evitar ruido de errores en consola)
            console.info('ℹ️ Elementos de interfaz aún no presentes:', faltantesBase);
        }
        
        // Crear instancia global del POS
        window.pos = new POSProfesional();
        console.log('✅ Sistema POS Profesional inicializado correctamente');
        
    } catch (error) {
        console.error('❌ Error crítico al inicializar POS:', error);
        
        // Mostrar error amigable al usuario
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error del Sistema',
                text: 'Error al inicializar el sistema de ventas. Recargue la página.',
                confirmButtonText: 'Recargar',
                confirmButtonColor: '#dc2626'
            }).then(() => {
                location.reload();
            });
        } else {
            alert('Error al inicializar el sistema. Recargue la página.');
        }
    }
});

// Inicializar también con Turbo Drive (navegación sin recarga)
document.addEventListener('turbo:load', function() {
    // Evitar reinicializar si ya existe y estamos en la misma vista
    const posContainer = document.querySelector('.pos-container');
    if (!posContainer) return; // Solo inicializar en la vista POS

    if (window.pos) {
        console.log('♻️ POS ya inicializado, reusando instancia con Turbo');
        return;
    }

    console.log('🚀 [Turbo] Inicializando Sistema POS Profesional...');
    try {
        window.pos = new POSProfesional();
        console.log('✅ [Turbo] Sistema POS Profesional inicializado correctamente');
    } catch (error) {
        console.error('❌ [Turbo] Error crítico al inicializar POS:', error);
    }
});

// Limpieza antes de cachear la página con Turbo (opcional)
document.addEventListener('turbo:before-cache', function() {
    // Evitar que el estado del POS se cachee y cause comportamientos raros
    if (window.pos) {
        try {
            // Limpieza mínima: vaciar referencias pesadas
            window.pos.carrito = [];
            window.pos.productos = [];
        } catch (e) {}
    }
});
