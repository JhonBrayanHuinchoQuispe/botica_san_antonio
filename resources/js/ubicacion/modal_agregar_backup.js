// ===============================================
// MODAL AGREGAR PRODUCTO - VERSION MEJORADA
// ===============================================

class ModalAgregar {
    constructor() {
        this.modal = null;
        this.productos = [];
        this.slotsLibres = [];
        this.estanteId = null;
        this.modoSlotEspecifico = false;
        this.slotSeleccionado = null;
        this.init();
    }

    init() {
        console.log('📦 Inicializando Modal Agregar...');
        this.modal = document.getElementById('modalAgregarProducto');
        if (!this.modal) {
            console.warn('⚠️ Modal agregar no encontrado');
            return;
        }

        // Obtener ID del estante desde la URL
        this.estanteId = this.obtenerEstanteIdDesdeURL();
        
        this.bindEvents();
        this.cargarProductos();
        console.log('✅ Modal Agregar inicializado');
    }

    obtenerEstanteIdDesdeURL() {
        const pathSegments = window.location.pathname.split('/');
        const estanteIndex = pathSegments.indexOf('estante');
        if (estanteIndex !== -1 && pathSegments[estanteIndex + 1]) {
            return pathSegments[estanteIndex + 1];
        }
        return null;
    }

    bindEvents() {
        // Botón para abrir modal desde estante (modo general)
        const btnNuevoProducto = document.getElementById('btnNuevoProducto');
        if (btnNuevoProducto) {
            btnNuevoProducto.addEventListener('click', () => this.abrirModoGeneral());
        }

        // Botones de cerrar
        const closeBtn = this.modal.querySelector('.modal-close-btn');
        const cancelBtn = this.modal.querySelector('.btn-modal-secondary');
        
        [closeBtn, cancelBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => this.close());
            }
        });

        // Cerrar al hacer click fuera del modal
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Botón guardar
        const saveBtn = this.modal.querySelector('.btn-modal-primary');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.save());
        }

        // Click en slots vacíos para abrir modal (modo slot específico)
        document.addEventListener('click', (e) => {
            const slot = e.target.closest('.slot-container');
            if (slot && slot.classList.contains('vacio') && !e.target.closest('.btn-slot-accion')) {
                const slotId = slot.dataset.slot;
                this.abrirModoSlotEspecifico(slotId);
            }
        });
    }

    async cargarProductos() {
        try {
            console.log('📥 Cargando productos desde la API...');
            const response = await fetch('/api/ubicaciones/todos-los-productos');
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.productos = data.data;
                this.actualizarSelectProductos();
                console.log(`✅ ${this.productos.length} productos cargados`);
            } else {
                throw new Error(data.message || 'Error al obtener productos');
            }
        } catch (error) {
            console.error('❌ Error al cargar productos:', error);
            this.mostrarError('No se pudieron cargar los productos disponibles');
        }
    }

    async cargarSlotsLibres() {
        if (!this.estanteId) return;

        try {
            console.log('📥 Cargando slots libres...');
            const response = await fetch(`/api/ubicaciones/estante/${this.estanteId}/ubicaciones-libres`);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.slotsLibres = data.data;
                this.actualizarSelectSlots();
                console.log(`✅ ${this.slotsLibres.length} slots libres cargados`);
            } else {
                throw new Error(data.message || 'Error al obtener slots libres');
            }
        } catch (error) {
            console.error('❌ Error al cargar slots libres:', error);
            this.mostrarError('No se pudieron cargar los slots disponibles');
        }
    }

    actualizarSelectProductos() {
        const select = this.modal.querySelector('#selectProducto');
        if (!select) return;

        // Limpiar opciones existentes excepto la primera
        select.innerHTML = '<option value="">Buscar producto...</option>';

        // Agregar productos
        this.productos.forEach(producto => {
            const option = document.createElement('option');
            option.value = producto.id;
            option.textContent = `${producto.nombre} - Stock: ${producto.stock_actual || 0}`;
            option.dataset.nombre = producto.nombre;
            option.dataset.stock = producto.stock_actual || 0;
            select.appendChild(option);
        });
    }

    actualizarSelectSlots() {
        const select = this.modal.querySelector('#selectSlot');
        if (!select) return;

        // Limpiar opciones existentes excepto la primera
        select.innerHTML = '<option value="">Seleccionar slot...</option>';

        // Agrupar slots por nivel
        const slotsPorNivel = {};
        this.slotsLibres.forEach(slot => {
            if (!slotsPorNivel[slot.nivel]) {
                slotsPorNivel[slot.nivel] = [];
            }
            slotsPorNivel[slot.nivel].push(slot);
        });

        // Agregar slots agrupados por nivel (de mayor a menor)
        const niveles = Object.keys(slotsPorNivel).sort((a, b) => b - a);
        niveles.forEach(nivel => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = `Nivel ${nivel}`;
            
            slotsPorNivel[nivel]
                .sort((a, b) => a.posicion - b.posicion)
                .forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.codigo;
                    option.textContent = `${slot.codigo} (Nivel ${slot.nivel}, Posición ${slot.posicion})`;
                    option.dataset.ubicacionId = slot.id;
                    optgroup.appendChild(option);
                });
            
            select.appendChild(optgroup);
        });
    }

    abrirModoGeneral() {
        console.log('🎯 Abriendo modal en modo general');
        this.modoSlotEspecifico = false;
        this.slotSeleccionado = null;
        
        // Configurar interfaz para modo general
        this.configurarModoGeneral();
        
        // Cargar slots libres y abrir modal
        this.cargarSlotsLibres();
        this.abrirModal();
    }

    abrirModoSlotEspecifico(slotId) {
        console.log('📍 Abriendo modal para slot específico:', slotId);
        this.modoSlotEspecifico = true;
        this.slotSeleccionado = slotId;
        
        // Configurar interfaz para slot específico
        this.configurarModoSlotEspecifico(slotId);
        
        this.abrirModal();
    }

    configurarModoGeneral() {
        // Cambiar título
        const titulo = this.modal.querySelector('#tituloModalAgregar');
        if (titulo) {
            titulo.textContent = 'Agregar Producto al Estante';
        }

        // Mostrar selector de slot, ocultar slot específico
        const grupoSlotDestino = this.modal.querySelector('#grupoSlotDestino');
        const grupoSlotEspecifico = this.modal.querySelector('#grupoSlotEspecifico');
        
        if (grupoSlotDestino) grupoSlotDestino.classList.remove('hidden');
        if (grupoSlotEspecifico) grupoSlotEspecifico.classList.add('hidden');
    }

    configurarModoSlotEspecifico(slotId) {
        // Cambiar título
        const titulo = this.modal.querySelector('#tituloModalAgregar');
        if (titulo) {
            titulo.textContent = `Agregar Producto a ${slotId}`;
        }

        // Ocultar selector de slot, mostrar slot específico
        const grupoSlotDestino = this.modal.querySelector('#grupoSlotDestino');
        const grupoSlotEspecifico = this.modal.querySelector('#grupoSlotEspecifico');
        
        if (grupoSlotDestino) grupoSlotDestino.classList.add('hidden');
        if (grupoSlotEspecifico) grupoSlotEspecifico.classList.remove('hidden');

        // Configurar información del slot
        this.configurarInfoSlot(slotId);
    }

    configurarInfoSlot(slotId) {
        const slotNumero = this.modal.querySelector('#slotNumeroDisplay');
        const slotDescripcion = this.modal.querySelector('#slotDescripcionDisplay');
        const slotValue = this.modal.querySelector('#slotEspecificoValue');

        if (slotNumero) slotNumero.textContent = slotId;
        if (slotValue) slotValue.value = slotId;

        // Parsear el slot ID para generar descripción
        const [nivel, posicion] = slotId.split('-');
        if (slotDescripcion && nivel && posicion) {
            slotDescripcion.textContent = `Nivel ${nivel}, Posición ${posicion}`;
        }
    }

    abrirModal() {
        if (!this.modal) return;
        
        this.modal.classList.remove('hidden');
        
        // Focus en el primer campo
        const firstInput = this.modal.querySelector('#selectProducto');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 300);
        }
    }

    close() {
        if (!this.modal) return;
        
        console.log('❌ Cerrando modal agregar producto');
        this.modal.classList.add('hidden');
        
        // Limpiar formulario
        this.clearForm();
        
        // Resetear modo
        this.modoSlotEspecifico = false;
        this.slotSeleccionado = null;
    }

    async save() {
        console.log('💾 Guardando nuevo producto...');
        
        // Obtener datos del formulario
        const formData = this.getFormData();
        
        // Validar datos
        if (!this.validateForm(formData)) {
            console.log('❌ Validación fallida');
            return;
        }
        
        // Guardar en el servidor
        try {
            await this.guardarProducto(formData);
            
            // Cerrar modal
            this.close();
            
            // Mostrar notificación de éxito y recargar página
            this.showSuccessNotification(`Producto agregado exitosamente al slot ${formData.slot}`);
            
            // Recargar página después de un breve delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            
        } catch (error) {
            console.error('❌ Error al guardar:', error);
            this.mostrarError('Error al guardar el producto. Inténtalo nuevamente.');
        }
    }

    getFormData() {
        const form = this.modal.querySelector('.form-agregar-producto');
        if (!form) return {};
        
        const productoSelect = form.querySelector('#selectProducto');
        const slotInput = this.modoSlotEspecifico ? 
            form.querySelector('#slotEspecificoValue') : 
            form.querySelector('#selectSlot');
        
        return {
            producto_id: productoSelect?.value || '',
            producto_nombre: productoSelect?.selectedOptions[0]?.dataset?.nombre || '',
            slot: slotInput?.value || '',
            cantidad: form.querySelector('[name="cantidad"]')?.value || '',
            stockMinimo: form.querySelector('[name="stockMinimo"]')?.value || '',
            ubicacion_id: this.obtenerUbicacionId(slotInput?.value)
        };
    }

    obtenerUbicacionId(slotCodigo) {
        if (this.modoSlotEspecifico) {
            // En modo específico, buscar en el DOM
            const slotElement = document.querySelector(`[data-slot="${slotCodigo}"]`);
            return slotElement?.dataset?.ubicacionId || null;
        } else {
            // En modo general, buscar en slots libres
            const slot = this.slotsLibres.find(s => s.codigo === slotCodigo);
            return slot?.id || null;
        }
    }

    validateForm(data) {
        let isValid = true;
        const errors = [];
        
        // Validar producto
        if (!data.producto_id) {
            errors.push('Debe seleccionar un producto');
            this.markFieldError('selectProducto');
            isValid = false;
        }
        
        // Validar slot
        if (!data.slot) {
            errors.push('Debe seleccionar un slot');
            const slotField = this.modoSlotEspecifico ? 'slotEspecificoValue' : 'selectSlot';
            this.markFieldError(slotField);
            isValid = false;
        }
        
        // Validar cantidad
        if (!data.cantidad || parseInt(data.cantidad) <= 0) {
            errors.push('La cantidad debe ser mayor a 0');
            this.markFieldError('cantidad');
            isValid = false;
        }
        
        // Validar stock mínimo
        if (!data.stockMinimo || parseInt(data.stockMinimo) <= 0) {
            errors.push('El stock mínimo debe ser mayor a 0');
            this.markFieldError('stockMinimo');
            isValid = false;
        }
        
        // Mostrar errores si los hay
        if (errors.length > 0) {
            this.showErrors(errors);
        }
        
        return isValid;
    }

    async guardarProducto(data) {
        const response = await fetch('/api/ubicaciones/ubicar-producto', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                producto_id: data.producto_id,
                ubicacion_id: data.ubicacion_id,
                cantidad: parseInt(data.cantidad),
                stock_minimo: parseInt(data.stockMinimo)
            })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Error al guardar el producto');
        }

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'Error al guardar el producto');
        }

        return result;
    }

    markFieldError(fieldId) {
        const field = this.modal.querySelector(`#${fieldId}, [name="${fieldId}"]`);
        if (field) {
            field.classList.add('error');
            field.addEventListener('input', () => {
                field.classList.remove('error');
            }, { once: true });
            field.addEventListener('change', () => {
                field.classList.remove('error');
            }, { once: true });
        }
    }

    showErrors(errors) {
        Swal.fire({
            icon: 'error',
            title: 'Errores en el formulario',
            html: `<ul style="text-align: left; padding-left: 20px;">${errors.map(error => `<li>${error}</li>`).join('')}</ul>`,
            confirmButtonText: 'Entendido',
            showClass: { popup: '' },
            hideClass: { popup: '' }
        });
    }

    mostrarError(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje,
            confirmButtonText: 'Entendido',
            showClass: { popup: '' },
            hideClass: { popup: '' }
        });
    }

    clearForm() {
        const form = this.modal.querySelector('.form-agregar-producto');
        if (form) {
            form.reset();
        }
        
        // Remover clases de error
        this.modal.querySelectorAll('.error').forEach(field => {
            field.classList.remove('error');
        });
    }

    showSuccessNotification(message) {
        Swal.fire({
            icon: 'success',
            title: '¡Producto Agregado!',
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            showClass: { popup: '' },
            hideClass: { popup: '' }
        });
    }
}

// Inicialización automática
document.addEventListener('DOMContentLoaded', function() {
    window.modalAgregar = new ModalAgregar();
});
