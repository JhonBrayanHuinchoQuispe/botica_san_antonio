// public/assets/js/inventario/presentaciones-tabla-mejorado.js
// Sistema unificado para Agregar y Editar productos con presentaciones

class PresentacionesManager {
    constructor(mode) {
        this.mode = mode; // 'add' o 'edit'
        this.counter = 0;
        this.editingId = null;
        
        // Seleccionar elementos según el modo
        if (mode === 'add') {
            this.tableBody = document.getElementById('presentaciones-table-body');
            this.btnAbrir = document.getElementById('btn-agregar-presentacion');
            this.precioCompraBase = document.getElementById('precio_compra_base');
            this.precioVentaBase = document.getElementById('precio_venta_base');
            this.productoIdHidden = null; // No hay ID en modo Agregar
            this.stockActualInput = document.getElementById('stock_actual');
            this.formPrincipal = document.getElementById('formAgregarProducto');
        } else {
            this.tableBody = document.getElementById('presentaciones-table-body-edit');
            this.btnAbrir = document.getElementById('btn-abrir-modal-presentacion-edit');
            this.precioCompraBase = document.getElementById('precio_compra_base_edit');
            this.precioVentaBase = document.getElementById('precio_venta_base_edit');
            this.productoIdHidden = document.getElementById('producto_id_hidden_edit');
            this.stockActualInput = document.getElementById('edit-stock_actual');
            this.formPrincipal = document.getElementById('formEditarProducto');
        }
        
        // Elementos del modal (compartidos)
        this.modal = document.getElementById('modal-presentacion');
        this.form = document.getElementById('form-presentacion');
        this.btnCerrar = document.getElementById('btn-cerrar-modal-presentacion');
        this.btnCancelar = document.getElementById('btn-cancelar-modal-presentacion');
        
        this.init();
    }
    
    init() {
        console.log(`[PresentacionesManager ${this.mode}] Inicializando...`);
        
        if (!this.tableBody || !this.btnAbrir) {
            console.warn(`[PresentacionesManager ${this.mode}] Faltan elementos críticos. No se inicializará.`);
            return;
        }
        
        // Event listeners
        this.btnAbrir.addEventListener('click', (e) => {
            console.log(`[${this.mode}] Click en botón Abrir Modal`);
            e.preventDefault();
            window.activePresentacionesManager = this; // Marcar como manager activo
            this.abrirModal();
        });
        
        // Los elementos del modal son compartidos, solo actuar si somos el manager activo
        this.btnCerrar?.addEventListener('click', () => {
            if (window.activePresentacionesManager === this) this.cerrarModal();
        });
        this.btnCancelar?.addEventListener('click', () => {
            if (window.activePresentacionesManager === this) this.cerrarModal();
        });
        this.form?.addEventListener('submit', (e) => {
            if (window.activePresentacionesManager === this) this.guardarPresentacion(e);
        });
        
        // Delegación de eventos para botones de editar/eliminar (específicos de cada tabla)
        this.tableBody.addEventListener('click', (e) => {
            const btnEditar = e.target.closest('.btn-editar-presentacion');
            const btnEliminar = e.target.closest('.btn-eliminar-presentacion');
            
            if (btnEditar) {
                const id = btnEditar.dataset.id;
                this.editarPresentacion(id);
            } else if (btnEliminar) {
                const id = btnEliminar.dataset.id;
                this.eliminarPresentacion(id);
            }
        });
        
        // Actualizar precios cuando cambian los campos base (específicos de cada manager)
        this.precioCompraBase?.addEventListener('input', () => this.actualizarPrecioUnidad());
        this.precioVentaBase?.addEventListener('input', () => this.actualizarPrecioUnidad());
        
        // Auto-calcular precio sugerido al cambiar unidades (compartido)
        const unidadesInput = document.getElementById('unidades_presentacion_modal');
        unidadesInput?.addEventListener('input', () => {
            if (window.activePresentacionesManager === this) this.calcularPrecioSugerido();
        });
        
        console.log(`[PresentacionesManager ${this.mode}] Inicialización completa ✅`);
    }
    
    getPreciosBase() {
        let compra = 0;
        let venta = 0;
        
        if (this.precioCompraBase && this.precioCompraBase.value) {
            compra = parseFloat(this.precioCompraBase.value) || 0;
        }
        
        if (this.precioVentaBase && this.precioVentaBase.value) {
            venta = parseFloat(this.precioVentaBase.value) || 0;
        }
        
        console.log(`[${this.mode}] Precios base obtenidos:`, {
            compra,
            venta,
            elementoCompra: this.precioCompraBase ? '✅' : '❌',
            elementoVenta: this.precioVentaBase ? '✅' : '❌'
        });
        
        return { compra, venta };
    }
    
    calcularPrecioSugerido() {
        const unidadesInput = document.getElementById('unidades_presentacion_modal');
        const precioSugeridoSpan = document.getElementById('precio-sugerido');
        const costoCalculadoSpan = document.getElementById('costo-calculado');
        const gananciaCalculadaSpan = document.getElementById('ganancia-calculada');
        const precioCalculadoInfo = document.getElementById('precio-calculado-info');
        const precioInput = document.getElementById('precio_venta_modal');
        
        if (!unidadesInput) {
            console.warn('⚠️ Input de unidades no encontrado');
            return;
        }
        
        const unidades = parseInt(unidadesInput.value);
        
        // Si no hay unidades o es 1, ocultar el cálculo automático
        if (!unidades || unidades < 2) {
            if (precioCalculadoInfo) {
                precioCalculadoInfo.style.display = 'none';
            }
            console.log('ℹ️ Unidades < 2, ocultando cálculo automático');
            return;
        }
        
        const precios = this.getPreciosBase();
        
        console.log('📊 Calculando precios:', {
            unidades,
            precioCompraBase: precios.compra,
            precioVentaBase: precios.venta
        });
        
        const costoCalculado = (precios.compra * unidades);
        const precioSugerido = (precios.venta * unidades);
        const gananciaCalculada = (precioSugerido - costoCalculado);
        
        console.log('💰 Resultados:', {
            costoCalculado,
            precioSugerido,
            gananciaCalculada
        });
        
        // Actualizar los spans si existen
        if (costoCalculadoSpan) {
            costoCalculadoSpan.textContent = `S/ ${costoCalculado.toFixed(2)}`;
            console.log('✅ Costo actualizado');
        }
        if (precioSugeridoSpan) {
            precioSugeridoSpan.textContent = `S/ ${precioSugerido.toFixed(2)}`;
            console.log('✅ Precio sugerido actualizado');
        }
        if (gananciaCalculadaSpan) {
            const color = gananciaCalculada > 0 ? 'text-green-600' : 'text-red-600';
            gananciaCalculadaSpan.className = `font-semibold ${color}`;
            gananciaCalculadaSpan.textContent = `S/ ${gananciaCalculada.toFixed(2)}`;
            console.log('✅ Ganancia actualizada');
        }
        
        // Mostrar el div de información
        if (precioCalculadoInfo) {
            precioCalculadoInfo.style.display = 'block';
            console.log('ℹ️ Info box: visible');
        }
        
        // Auto-llenar precio sugerido
        if (precioInput) {
            precioInput.value = precioSugerido.toFixed(2);
            console.log('✅ Precio auto-llenado con precio sugerido:', precioSugerido.toFixed(2));
        }
    }
    
    actualizarPrecioUnidad() {
        const rowUnidad = this.tableBody.querySelector('[data-presentacion-id^="unidad_"]');
        if (!rowUnidad) return;
        
        const precios = this.getPreciosBase();
        const data = JSON.parse(rowUnidad.dataset.presentacionData);
        data.precio_venta_presentacion = precios.venta;
        
        rowUnidad.dataset.presentacionData = JSON.stringify(data);
        const precioCell = rowUnidad.querySelector('td:nth-child(3)');
        if (precioCell) {
            precioCell.innerHTML = `S/ ${precios.venta.toFixed(2)}`;
        }
    }
    
    abrirModal(presentacion = null) {
        console.log(`[${this.mode}] 🚀 Intentando abrir modal de presentación...`, presentacion ? 'Modo Edición' : 'Modo Nuevo');
        
        if (!this.modal || !this.form) {
            console.error(`[${this.mode}] ❌ Error crítico: Modal o Formulario no encontrado en el DOM.`);
            return;
        }

        // --- VALIDACIÓN 1: Campos previos obligatorios ---
        if (!presentacion) {
            console.log(`[${this.mode}] 🔍 Validando campos previos...`);
            const camposAValidar = [
                { id: this.mode === 'add' ? 'nombreProducto' : 'edit-nombre', label: 'Nombre del Producto' },
                { name: 'marca', label: 'Marca' },
                { name: 'categoria', label: 'Categoría' },
                { name: 'lote', label: 'Lote' }
            ];

            let faltantes = [];
            camposAValidar.forEach(campo => {
                let el = campo.id ? document.getElementById(campo.id) : this.formPrincipal?.querySelector(`[name="${campo.name}"]`);
                if (!el || !el.value.trim() || el.value === "") {
                    console.warn(`[${this.mode}] ⚠️ Campo faltante: ${campo.label}`);
                    faltantes.push(campo.label);
                }
            });

            const precios = this.getPreciosBase();
            if (precios.compra <= 0) {
                console.warn(`[${this.mode}] ⚠️ Precio de compra no válido:`, precios.compra);
                faltantes.push('Precio Compra Unitario');
            }
            if (precios.venta <= 0) {
                console.warn(`[${this.mode}] ⚠️ Precio de venta no válido:`, precios.venta);
                faltantes.push('Precio Venta Unitario');
            }

            if (faltantes.length > 0) {
                console.log(`[${this.mode}] ❌ Validación fallida. Mostrando alerta.`);
                Swal.fire({
                    icon: 'warning',
                    title: '¡Faltan datos previos!',
                    html: `<div class="text-left"><p class="mb-2">Para agregar una presentación, primero completa:</p>
                           <ul class="list-disc ml-5 text-red-600">
                               ${faltantes.map(f => `<li>${f}</li>`).join('')}
                           </ul></div>`,
                    confirmButtonColor: '#6366f1'
                });
                return;
            }
            console.log(`[${this.mode}] ✅ Validación exitosa.`);
        }
        
        this.form.reset();
        this.editingId = presentacion ? presentacion.id : null;
        
        const modalTitle = document.getElementById('modal-presentacion-title');
        if (modalTitle) {
            modalTitle.textContent = presentacion ? 'Editar Presentación' : 'Agregar Presentación';
        }
        
        if (presentacion) {
            // Modo edición: llenar con datos existentes
            document.getElementById('nombre_presentacion_modal').value = presentacion.nombre_presentacion;
            document.getElementById('unidades_presentacion_modal').value = presentacion.unidades_por_presentacion;
            document.getElementById('precio_venta_modal').value = presentacion.precio_venta_presentacion;
        } else {
            // Modo agregar: limpiar el precio de venta (NO auto-llenar aún)
            const precioInput = document.getElementById('precio_venta_modal');
            if (precioInput) {
                precioInput.value = '';
            }
        }
        
        // Ocultar el cálculo automático al inicio
        const precioCalculadoInfo = document.getElementById('precio-calculado-info');
        if (precioCalculadoInfo) {
            precioCalculadoInfo.style.display = 'none';
        }
        
        // Mostrar modal (clases y estilo inline)
        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
        this.modal.style.display = 'flex'; // IMPORTANTE: Quitar display:none
        
        console.log(`[${this.mode}] ✅ Modal de presentación abierto correctamente.`);
    }
    
    cerrarModal() {
        if (!this.modal) return;
        this.modal.classList.add('hidden');
        this.modal.classList.remove('flex');
        this.modal.style.display = 'none'; // Restaurar display:none
        this.editingId = null;
        this.form?.reset();
    }
    
    guardarPresentacion(e) {
        e.preventDefault();
        
        const nombre = document.getElementById('nombre_presentacion_modal').value.trim();
        const unidades = parseInt(document.getElementById('unidades_presentacion_modal').value);
        const precio = parseFloat(document.getElementById('precio_venta_modal').value);
        
        if (!nombre || unidades < 1 || isNaN(precio) || precio < 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Datos incompletos',
                text: 'Por favor, completa todos los campos correctamente.',
                confirmButtonColor: '#6366f1'
            });
            return;
        }

        // --- VALIDACIÓN 2: Unidades vs Stock Actual ---
        const stockActual = parseInt(this.stockActualInput?.value || 0);
        if (unidades > stockActual) {
            Swal.fire({
                icon: 'error',
                title: '¡Exceso de unidades!',
                html: `<p>No puedes crear una presentación de <strong>${unidades}</strong> unidades porque el stock actual del producto es solo de <strong>${stockActual}</strong>.</p>
                       <p class="text-sm text-gray-500 mt-2">Aumenta el stock actual primero si es necesario.</p>`,
                confirmButtonColor: '#ef4444'
            });
            return;
        }
        
        // Verificar duplicados (solo si no estamos editando)
        if (!this.editingId) {
            const existente = Array.from(this.tableBody.querySelectorAll('tr')).some(row => {
                const data = JSON.parse(row.dataset.presentacionData);
                return data.nombre_presentacion.toLowerCase() === nombre.toLowerCase();
            });
            
            if (existente) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Presentación duplicada',
                    text: `Ya existe una presentación llamada "${nombre}".`,
                    confirmButtonColor: '#6366f1'
                });
                return;
            }
        }
        
        const presentacion = {
            id: this.editingId || `new_${this.counter++}`,
            nombre_presentacion: nombre,
            unidades_por_presentacion: unidades,
            precio_venta_presentacion: precio
        };
        
        if (this.editingId) {
            this.actualizarPresentacionEnTabla(presentacion);
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: 'Presentación actualizada correctamente',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            this.agregarPresentacionATabla(presentacion);
            Swal.fire({
                icon: 'success',
                title: '¡Agregado!',
                text: 'Presentación agregada a la lista',
                timer: 1500,
                showConfirmButton: false
            });
        }
        
        this.cerrarModal();
    }
    
    agregarPresentacionATabla(presentacion) {
        const id = presentacion.id || `new_${this.counter++}`;
        const precioVenta = presentacion.precio_venta_presentacion || 0;
        const esUnidad = presentacion.nombre_presentacion === 'Unidad' && presentacion.unidades_por_presentacion === 1;
        
        const row = document.createElement('tr');
        row.setAttribute('data-presentacion-id', id);
        row.innerHTML = `
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">${presentacion.nombre_presentacion}</td>
            <td class="px-4 py-3 text-sm text-gray-600 text-center">${presentacion.unidades_por_presentacion}</td>
            <td class="px-4 py-3 text-sm text-gray-900 text-center font-semibold">S/ ${parseFloat(precioVenta).toFixed(2)}</td>
            <td class="px-4 py-3 text-sm text-center">
                ${esUnidad ? `
                    <span class="text-gray-400 text-xs italic">Automático</span>
                ` : `
                    <button type="button" class="btn-editar-presentacion text-blue-600 hover:text-blue-800 mr-3 transition-colors" data-id="${id}" title="Editar">
                        <i class="fas fa-edit text-lg"></i>
                    </button>
                    <button type="button" class="btn-eliminar-presentacion text-red-600 hover:text-red-800 transition-colors" data-id="${id}" title="Eliminar">
                        <i class="fas fa-trash-alt text-lg"></i>
                    </button>
                `}
            </td>
        `;
        
        row.dataset.presentacionData = JSON.stringify({
            id: id,
            nombre_presentacion: presentacion.nombre_presentacion,
            unidades_por_presentacion: presentacion.unidades_por_presentacion,
            precio_venta_presentacion: precioVenta
        });
        
        this.tableBody.appendChild(row);
    }
    
    actualizarPresentacionEnTabla(presentacion) {
        const row = this.tableBody.querySelector(`[data-presentacion-id="${presentacion.id}"]`);
        if (!row) return;
        
        const esUnidad = presentacion.nombre_presentacion === 'Unidad' && presentacion.unidades_por_presentacion === 1;
        
        row.innerHTML = `
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">${presentacion.nombre_presentacion}</td>
            <td class="px-4 py-3 text-sm text-gray-600 text-center">${presentacion.unidades_por_presentacion}</td>
            <td class="px-4 py-3 text-sm text-gray-900 text-center font-semibold">S/ ${parseFloat(presentacion.precio_venta_presentacion).toFixed(2)}</td>
            <td class="px-4 py-3 text-sm text-center">
                ${esUnidad ? `
                    <span class="text-gray-400 text-xs italic">Automático</span>
                ` : `
                    <button type="button" class="btn-editar-presentacion text-blue-600 hover:text-blue-800 mr-3 transition-colors" data-id="${presentacion.id}" title="Editar">
                        <i class="fas fa-edit text-lg"></i>
                    </button>
                    <button type="button" class="btn-eliminar-presentacion text-red-600 hover:text-red-800 transition-colors" data-id="${presentacion.id}" title="Eliminar">
                        <i class="fas fa-trash-alt text-lg"></i>
                    </button>
                `}
            </td>
        `;
        
        row.dataset.presentacionData = JSON.stringify({
            id: presentacion.id,
            nombre_presentacion: presentacion.nombre_presentacion,
            unidades_por_presentacion: presentacion.unidades_por_presentacion,
            precio_venta_presentacion: presentacion.precio_venta_presentacion
        });
    }
    
    editarPresentacion(id) {
        const row = this.tableBody.querySelector(`[data-presentacion-id="${id}"]`);
        if (!row) return;
        
        window.activePresentacionesManager = this; // Asegurar que este manager sea el activo al editar
        const data = JSON.parse(row.dataset.presentacionData);
        this.abrirModal(data);
    }
    
    eliminarPresentacion(id) {
        Swal.fire({
            title: '¿Eliminar presentación?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const row = this.tableBody.querySelector(`[data-presentacion-id="${id}"]`);
                if (row) {
                    row.remove();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: 'Presentación eliminada correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            }
        });
    }
    
    async loadPresentaciones(productoId, loteId = null) {
        console.log(`[${this.mode}] 🔄 Cargando presentaciones para producto:`, productoId, loteId ? `y lote: ${loteId}` : '');
        
        // Verificar que los elementos existen
        console.log(`[${this.mode}] Verificando elementos:`, {
            tableBody: this.tableBody ? '✅' : '❌',
            precioCompraBase: this.precioCompraBase ? '✅' : '❌',
            precioVentaBase: this.precioVentaBase ? '✅' : '❌'
        });
        
        // Limpiar tabla
        this.tableBody.innerHTML = '';
        
        if (!productoId) {
            // Producto nuevo: agregar solo "Unidad"
            console.log(`[${this.mode}] Producto nuevo, creando presentación "Unidad"`);
            const precios = this.getPreciosBase();
            console.log(`[${this.mode}] Precios base para Unidad:`, precios);
            
            this.agregarPresentacionATabla({
                id: `unidad_${this.counter++}`,
                nombre_presentacion: 'Unidad',
                unidades_por_presentacion: 1,
                precio_venta_presentacion: precios.venta
            });
            return;
        }
        
        // Producto existente: cargar desde la BD
        console.log(`[${this.mode}] Producto existente, cargando desde BD...`);
        try {
            let url = `/inventario/producto/presentaciones/api/${productoId}`;
            if (loteId) {
                url += `?lote_id=${loteId}`;
            }
            
            const response = await fetch(url);
            const result = await response.json();
            
            console.log(`[${this.mode}] Respuesta del servidor:`, result);
            
            if (result.success && result.data && result.data.length > 0) {
                console.log(`[${this.mode}] ✅ ${result.data.length} presentaciones encontradas`);
                result.data.forEach((presentacion, index) => {
                    console.log(`[${this.mode}] Agregando presentación ${index + 1}:`, presentacion);
                    this.agregarPresentacionATabla(presentacion);
                });
            } else {
                // Si no hay presentaciones, agregar "Unidad" por defecto
                console.log(`[${this.mode}] ⚠️ No hay presentaciones, creando "Unidad" por defecto`);
                const precios = this.getPreciosBase();
                console.log(`[${this.mode}] Precios base para Unidad por defecto:`, precios);
                
                this.agregarPresentacionATabla({
                    id: `unidad_${this.counter++}`,
                    nombre_presentacion: 'Unidad',
                    unidades_por_presentacion: 1,
                    precio_venta_presentacion: precios.venta
                });
            }
            
            console.log(`[${this.mode}] ✅ Presentaciones cargadas. Total en tabla:`, this.tableBody.querySelectorAll('tr').length);
        } catch (error) {
            console.error(`[${this.mode}] ❌ Error loading presentaciones:`, error);
            
            // En caso de error, crear "Unidad" por defecto
            const precios = this.getPreciosBase();
            this.agregarPresentacionATabla({
                id: `unidad_${this.counter++}`,
                nombre_presentacion: 'Unidad',
                unidades_por_presentacion: 1,
                precio_venta_presentacion: precios.venta
            });
        }
    }
    
    getPresentacionesData() {
        const presentaciones = {};
        const rows = this.tableBody.querySelectorAll('tr');
        
        rows.forEach((row) => {
            const data = JSON.parse(row.dataset.presentacionData);
            presentaciones[data.id] = {
                id: data.id.toString().startsWith('new_') || data.id.toString().startsWith('unidad_') ? '' : data.id,
                nombre_presentacion: data.nombre_presentacion,
                unidades_por_presentacion: data.unidades_por_presentacion,
                precio_venta_presentacion: data.precio_venta_presentacion
            };
        });
        
        return presentaciones;
    }
}

// Inicializar managers
let managerAdd, managerEdit;

// Función para inicializar con reintentos
function inicializarManagers() {
    console.log('🚀 Iniciando sistema de presentaciones...');
    
    // Manager para "Agregar Producto"
    const tableBodyAdd = document.getElementById('presentaciones-table-body');
    const btnAgregarAdd = document.getElementById('btn-agregar-presentacion');
    
    console.log('Elementos para Agregar:');
    console.log('- presentaciones-table-body:', tableBodyAdd ? '✅' : '❌');
    console.log('- btn-agregar-presentacion:', btnAgregarAdd ? '✅' : '❌');
    
    if (tableBodyAdd && btnAgregarAdd) {
        console.log('✅ Creando PresentacionesManager para AGREGAR');
        managerAdd = new PresentacionesManager('add');
        
        // Cargar presentación por defecto al abrir el modal de agregar
        const modalAgregar = document.getElementById('modalAgregar');
        if (modalAgregar) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        const isVisible = !modalAgregar.classList.contains('hidden');
                        if (isVisible && managerAdd && managerAdd.tableBody && managerAdd.tableBody.children.length === 0) {
                            console.log('📋 Cargando presentación por defecto (Unidad)');
                            managerAdd.loadPresentaciones(null);
                        }
                    }
                });
            });
            observer.observe(modalAgregar, { attributes: true });
        }
    } else {
        console.warn('⚠️ No se pudo crear manager para AGREGAR (elementos no encontrados)');
    }
    
    // Manager para "Editar Producto"
    const tableBodyEdit = document.getElementById('presentaciones-table-body-edit');
    const btnAgregarEdit = document.getElementById('btn-abrir-modal-presentacion-edit');
    
    console.log('Elementos para Editar:');
    console.log('- presentaciones-table-body-edit:', tableBodyEdit ? '✅' : '❌');
    console.log('- btn-abrir-modal-presentacion-edit:', btnAgregarEdit ? '✅' : '❌');
    
    if (tableBodyEdit && btnAgregarEdit) {
        console.log('✅ Creando PresentacionesManager para EDITAR');
        managerEdit = new PresentacionesManager('edit');
    } else {
        console.warn('⚠️ No se pudo crear manager para EDITAR (elementos no encontrados)');
    }
    
    console.log('🎉 Sistema de presentaciones inicializado');
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarManagers);
} else {
    // El DOM ya está listo
    inicializarManagers();
}

// También intentar inicializar después de un delay (por si los elementos se cargan tarde)
setTimeout(() => {
    if (!managerAdd && !managerEdit) {
        console.log('🔄 Reintentando inicialización...');
        inicializarManagers();
    }
}, 500);

// Funciones globales para compatibilidad
window.getPresentacionesData = function() {
    if (managerAdd && managerAdd.tableBody.children.length > 0) {
        return managerAdd.getPresentacionesData();
    }
    if (managerEdit && managerEdit.tableBody.children.length > 0) {
        return managerEdit.getPresentacionesData();
    }
    return {};
};

window.loadExistingPresentaciones = async function(productoId, loteId = null) {
    if (managerEdit) {
        await managerEdit.loadPresentaciones(productoId, loteId);
    }
};
