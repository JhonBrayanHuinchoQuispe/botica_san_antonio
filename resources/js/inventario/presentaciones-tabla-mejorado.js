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
            this.productoIdHidden = document.getElementById('producto_id_hidden');
        } else {
            this.tableBody = document.getElementById('presentaciones-table-body-edit');
            this.btnAbrir = document.getElementById('btn-abrir-modal-presentacion-edit');
            this.precioCompraBase = document.getElementById('precio_compra_base_edit');
            this.precioVentaBase = document.getElementById('precio_venta_base_edit');
            this.productoIdHidden = document.getElementById('producto_id_hidden_edit');
        }
        
        // Elementos del modal (compartidos)
        this.modal = document.getElementById('modal-presentacion');
        this.form = document.getElementById('form-presentacion');
        this.btnCerrar = document.getElementById('btn-cerrar-modal-presentacion');
        this.btnCancelar = document.getElementById('btn-cancelar-modal-presentacion');
        
        this.init();
    }
    
    init() {
        if (!this.tableBody || !this.btnAbrir) return;
        
        // Event listeners
        this.btnAbrir?.addEventListener('click', () => this.abrirModal());
        this.btnCerrar?.addEventListener('click', () => this.cerrarModal());
        this.btnCancelar?.addEventListener('click', () => this.cerrarModal());
        this.form?.addEventListener('submit', (e) => this.guardarPresentacion(e));
        
        // Delegación de eventos para botones de editar/eliminar
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
        
        // Actualizar precios cuando cambian los campos base
        this.precioCompraBase?.addEventListener('input', () => this.actualizarPrecioUnidad());
        this.precioVentaBase?.addEventListener('input', () => this.actualizarPrecioUnidad());
        
        // Auto-calcular precio sugerido al cambiar unidades
        const unidadesInput = document.getElementById('unidades_presentacion_modal');
        unidadesInput?.addEventListener('input', () => this.calcularPrecioSugerido());
    }
    
    getPreciosBase() {
        const compra = parseFloat(this.precioCompraBase?.value || 0);
        const venta = parseFloat(this.precioVentaBase?.value || 0);
        return { compra, venta };
    }
    
    calcularPrecioSugerido() {
        const unidadesInput = document.getElementById('unidades_presentacion_modal');
        const precioSugeridoSpan = document.getElementById('precio-sugerido');
        const costoCalculadoSpan = document.getElementById('costo-calculado');
        const gananciaCalculadaSpan = document.getElementById('ganancia-calculada');
        const precioCalculadoInfo = document.getElementById('precio-calculado-info');
        const precioInput = document.getElementById('precio_venta_modal');
        
        if (!unidadesInput) return;
        
        const unidades = parseInt(unidadesInput.value) || 1;
        const precios = this.getPreciosBase();
        const costoCalculado = (precios.compra * unidades).toFixed(2);
        const precioSugerido = (precios.venta * unidades).toFixed(2);
        const gananciaCalculada = (precioSugerido - costoCalculado).toFixed(2);
        
        // Actualizar los spans si existen
        if (costoCalculadoSpan) costoCalculadoSpan.textContent = `S/ ${costoCalculado}`;
        if (precioSugeridoSpan) precioSugeridoSpan.textContent = `S/ ${precioSugerido}`;
        if (gananciaCalculadaSpan) gananciaCalculadaSpan.textContent = `S/ ${gananciaCalculada}`;
        
        // Mostrar el div de información si existe
        if (precioCalculadoInfo) {
            precioCalculadoInfo.style.display = unidades > 1 ? 'block' : 'none';
        }
        
        // Auto-llenar si está vacío
        if (precioInput && (!precioInput.value || parseFloat(precioInput.value) === 0)) {
            precioInput.value = precioSugerido;
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
        if (!this.modal || !this.form) return;
        
        this.form.reset();
        this.editingId = presentacion ? presentacion.id : null;
        
        const modalTitle = document.getElementById('modal-presentacion-title');
        if (modalTitle) {
            modalTitle.textContent = presentacion ? 'Editar Presentación' : 'Agregar Presentación';
        }
        
        if (presentacion) {
            document.getElementById('nombre_presentacion_modal').value = presentacion.nombre_presentacion;
            document.getElementById('unidades_presentacion_modal').value = presentacion.unidades_por_presentacion;
            document.getElementById('precio_venta_modal').value = presentacion.precio_venta_presentacion;
        }
        
        this.calcularPrecioSugerido();
        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
    }
    
    cerrarModal() {
        if (!this.modal) return;
        this.modal.classList.add('hidden');
        this.modal.classList.remove('flex');
        this.editingId = null;
        this.form?.reset();
    }
    
    guardarPresentacion(e) {
        e.preventDefault();
        
        const nombre = document.getElementById('nombre_presentacion_modal').value.trim();
        const unidades = parseInt(document.getElementById('unidades_presentacion_modal').value);
        const precio = parseFloat(document.getElementById('precio_venta_modal').value);
        
        if (!nombre || unidades < 1 || precio < 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Datos incompletos',
                text: 'Por favor, completa todos los campos correctamente.',
                confirmButtonColor: '#6366f1'
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
        } else {
            this.agregarPresentacionATabla(presentacion);
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
    
    async loadPresentaciones(productoId) {
        this.tableBody.innerHTML = '';
        
        if (!productoId) {
            // Producto nuevo: agregar solo "Unidad"
            const precios = this.getPreciosBase();
            this.agregarPresentacionATabla({
                id: `unidad_${this.counter++}`,
                nombre_presentacion: 'Unidad',
                unidades_por_presentacion: 1,
                precio_venta_presentacion: precios.venta
            });
            return;
        }
        
        // Producto existente: cargar desde la BD
        try {
            const response = await fetch(`/inventario/producto/presentaciones/api/${productoId}`);
            const result = await response.json();
            
            if (result.success && result.data.length > 0) {
                result.data.forEach(presentacion => {
                    this.agregarPresentacionATabla(presentacion);
                });
            } else {
                // Si no hay presentaciones, agregar "Unidad" por defecto
                const precios = this.getPreciosBase();
                this.agregarPresentacionATabla({
                    id: `unidad_${this.counter++}`,
                    nombre_presentacion: 'Unidad',
                    unidades_por_presentacion: 1,
                    precio_venta_presentacion: precios.venta
                });
            }
        } catch (error) {
            console.error('Error loading presentaciones:', error);
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

document.addEventListener('DOMContentLoaded', function() {
    // Manager para "Agregar Producto"
    if (document.getElementById('presentaciones-table-body')) {
        managerAdd = new PresentacionesManager('add');
        
        // Cargar presentación por defecto al abrir el modal de agregar
        const modalAgregar = document.getElementById('modalAgregar');
        if (modalAgregar) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        const isVisible = !modalAgregar.classList.contains('hidden');
                        if (isVisible && managerAdd.tableBody.children.length === 0) {
                            managerAdd.loadPresentaciones(null);
                        }
                    }
                });
            });
            observer.observe(modalAgregar, { attributes: true });
        }
    }
    
    // Manager para "Editar Producto"
    if (document.getElementById('presentaciones-table-body-edit')) {
        managerEdit = new PresentacionesManager('edit');
    }
});

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

window.loadExistingPresentaciones = async function(productoId) {
    if (managerEdit) {
        await managerEdit.loadPresentaciones(productoId);
    }
};
