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
        this.estanteId = this.obtenerEstanteIdDesdeURL();
        this.bindEvents();
        this.cargarProductos();
        console.log('✅ Modal Agregar inicializado');
    }

    getModal() {
        if (!this.modal) {
            this.modal = document.getElementById('modalAgregarProducto');
        }
        return this.modal;
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
        const btnNuevoProducto = document.getElementById('btnNuevoProducto');
        if (btnNuevoProducto) {
            btnNuevoProducto.addEventListener('click', () => this.abrirModoGeneral());
        }

        const modal = this.getModal();
        if (!modal) return;

        const closeBtn = modal.querySelector('.modal-close-btn');
        const cancelBtn = modal.querySelector('.btn-modal-secondary');
        
        [closeBtn, cancelBtn].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => this.close());
            }
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.close();
            }
        });

        const saveBtn = modal.querySelector('.btn-modal-primary');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.save());
        }

        // Usar event delegation con captura para asegurar que el evento se capture
        document.addEventListener('click', (e) => {
            const slot = e.target.closest('.slot-container');
            
            // Permitir clic en slots vacíos para agregar producto
            if (slot && slot.classList.contains('vacio') && !e.target.closest('.btn-slot-accion')) {
                // Verificar si estamos en modo fusión (no abrir modal si es así)
                if (document.body.classList.contains('modo-fusion-activo')) {
                    return;
                }
                
                const slotId = slot.dataset.slot;
                console.log('👆 Click en slot vacío:', slotId);
                console.log('🎯 Abriendo modal para slot:', slotId);
                
                // Prevenir propagación para evitar conflictos
                e.stopPropagation();
                
                this.abrirModoSlotEspecifico(slotId);
            }
        }, true); // Usar captura para asegurar que se ejecute primero
        
        // Agregar también listeners directos a los slots vacíos como backup
        this.agregarListenersASlotsVacios();
    }

    agregarListenersASlotsVacios() {
        // Agregar listeners directos a todos los slots vacíos
        const slotsVacios = document.querySelectorAll('.slot-container.vacio');
        console.log(`🎯 Agregando listeners a ${slotsVacios.length} slots vacíos`);
        
        slotsVacios.forEach(slot => {
            // Remover listener anterior si existe
            slot.removeEventListener('click', slot._clickHandler);
            
            // Crear nuevo handler
            slot._clickHandler = (e) => {
                // Verificar si estamos en modo fusión
                if (document.body.classList.contains('modo-fusion-activo')) {
                    return;
                }
                
                // Verificar que no sea un botón de acción
                if (e.target.closest('.btn-slot-accion')) {
                    return;
                }
                
                const slotId = slot.dataset.slot;
                console.log('🎯 Click directo en slot vacío:', slotId);
                
                e.stopPropagation();
                e.preventDefault();
                
                this.abrirModoSlotEspecifico(slotId);
            };
            
            // Agregar el listener
            slot.addEventListener('click', slot._clickHandler);
            
            // Agregar estilo de cursor para indicar que es clickeable
            slot.style.cursor = 'pointer';
        });
    }

    async cargarProductos() {
        try {
            console.log('📥 Cargando productos desde la API...');
            console.log('URL:', window.location.origin + '/api/ubicaciones/todos-los-productos');
            
            const response = await fetch('/api/ubicaciones/todos-los-productos');
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error response:', errorText);
                throw new Error(`Error HTTP: ${response.status} - ${errorText}`);
            }
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                this.productos = data.data;
                this.actualizarSelectProductos();
                console.log(`✅ ${this.productos.length} productos cargados:`, this.productos);
                
                // Agregar listeners a slots vacíos después de cargar productos
                setTimeout(() => this.agregarListenersASlotsVacios(), 500);
            } else {
                throw new Error(data.message || 'Error al obtener productos');
            }
        } catch (error) {
            console.error('❌ Error al cargar productos:', error);
            
            // Mostrar error al usuario
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cargar productos',
                    text: 'No se pudieron cargar los productos. Revisa la consola para más detalles.',
                    footer: error.message
                });
            }
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
            }
        } catch (error) {
            console.error('❌ Error al cargar slots libres:', error);
        }
    }

    actualizarSelectProductos() {
        const modal = this.getModal();
        if (!modal) return;

        const select = modal.querySelector('#selectProducto');
        if (!select) {
            console.warn('⚠️ Select de productos no encontrado');
            return;
        }

        console.log('🔄 Actualizando select de productos...');
        
        // Limpiar y agregar opción por defecto
        select.innerHTML = '<option value="">Buscar producto...</option>';

        if (!this.productos || this.productos.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No hay productos disponibles';
            option.disabled = true;
            select.appendChild(option);
            console.warn('⚠️ No hay productos para mostrar');
            return;
        }

        // Agregar productos
        this.productos.forEach(producto => {
            const option = document.createElement('option');
            option.value = producto.id;
            
            // Mostrar solo nombre y concentración
            let texto = producto.nombre || 'Sin nombre';
            if (producto.concentracion) {
                texto += ` ${producto.concentracion}`;
            }
            
            option.textContent = texto;
            select.appendChild(option);
        });
        
        console.log(`✅ Select actualizado con ${this.productos.length} productos`);
    }

    actualizarSelectSlots() {
        const modal = this.getModal();
        if (!modal) return;

        const select = modal.querySelector('#selectSlot');
        if (!select) return;

        select.innerHTML = '<option value="">Seleccionar slot...</option>';

        const slotsPorNivel = {};
        this.slotsLibres.forEach(slot => {
            if (!slotsPorNivel[slot.nivel]) {
                slotsPorNivel[slot.nivel] = [];
            }
            slotsPorNivel[slot.nivel].push(slot);
        });

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
        
        this.configurarModoGeneral();
        this.cargarSlotsLibres();
        this.abrirModal();
    }

    abrirModoSlotEspecifico(slotId) {
        console.log('📍 Abriendo modal para slot específico:', slotId);
        console.log('📋 Estado actual:', {
            estanteId: this.estanteId,
            modoSlotEspecifico: this.modoSlotEspecifico,
            slotSeleccionado: this.slotSeleccionado
        });
        
        this.modoSlotEspecifico = true;
        this.slotSeleccionado = slotId;
        
        // Debug: verificar que el slot existe en el DOM
        const slotElement = document.querySelector(`[data-slot="${slotId}"]`);
        console.log('🔍 Elemento DOM del slot:', {
            encontrado: !!slotElement,
            clases: slotElement?.className,
            ubicacionId: slotElement?.dataset?.ubicacionId,
            estado: slotElement?.dataset?.estado
        });
        
        this.configurarModoSlotEspecifico(slotId);
        this.abrirModal();
    }

    configurarModoGeneral() {
        const modal = this.getModal();
        if (!modal) return;

        const titulo = modal.querySelector('#tituloModalAgregar');
        if (titulo) {
            titulo.textContent = 'Agregar Producto al Estante';
        }

        const grupoSlotDestino = modal.querySelector('#grupoSlotDestino');
        const grupoSlotEspecifico = modal.querySelector('#grupoSlotEspecifico');
        
        if (grupoSlotDestino) grupoSlotDestino.classList.remove('hidden');
        if (grupoSlotEspecifico) grupoSlotEspecifico.classList.add('hidden');
    }

    configurarModoSlotEspecifico(slotId) {
        const modal = this.getModal();
        if (!modal) return;

        const titulo = modal.querySelector('#tituloModalAgregar');
        if (titulo) {
            titulo.textContent = `Agregar Producto a ${slotId}`;
        }

        const grupoSlotDestino = modal.querySelector('#grupoSlotDestino');
        const grupoSlotEspecifico = modal.querySelector('#grupoSlotEspecifico');
        
        if (grupoSlotDestino) grupoSlotDestino.classList.add('hidden');
        if (grupoSlotEspecifico) grupoSlotEspecifico.classList.remove('hidden');

        this.configurarInfoSlot(slotId);
    }

    configurarInfoSlot(slotId) {
        const modal = this.getModal();
        if (!modal) return;

        const slotNumero = modal.querySelector('#slotNumeroDisplay');
        const slotDescripcion = modal.querySelector('#slotDescripcionDisplay');
        const slotValue = modal.querySelector('#slotEspecificoValue');

        if (slotNumero) slotNumero.textContent = slotId;
        if (slotValue) slotValue.value = slotId;

        const [nivel, posicion] = slotId.split('-');
        if (slotDescripcion && nivel && posicion) {
            slotDescripcion.textContent = `Nivel ${nivel}, Posición ${posicion}`;
        }
    }

    abrirModal() {
        const modal = this.getModal();
        if (!modal) return;
        
        modal.classList.remove('hidden');
        
        const firstInput = modal.querySelector('#selectProducto');
        if (firstInput) {
            firstInput.focus();
        }
    }

    close() {
        const modal = this.getModal();
        if (!modal) return;
        
        console.log('❌ Cerrando modal agregar producto');
        modal.classList.add('hidden');
        
        this.clearForm();
        this.modoSlotEspecifico = false;
        this.slotSeleccionado = null;
    }

    clearForm() {
        const modal = this.getModal();
        if (!modal) return;

        const form = modal.querySelector('.form-agregar-producto');
        if (form) {
            form.reset();
        }
        
        modal.querySelectorAll('.error').forEach(field => {
            field.classList.remove('error');
        });
    }

    async save() {
        console.log('💾 Guardando nuevo producto...');
        
        try {
            const formData = await this.getFormData();
            
            console.log('📋 Datos del formulario:', formData);
            
            if (!this.validateForm(formData)) {
                console.log('❌ Validación fallida - no cerramos el modal');
                return;
            }
            
            if (!formData.ubicacion_id) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo determinar la ubicación del slot. Inténtalo nuevamente.',
                        backdrop: false // Sin fondo oscuro
                    });
                }
                return;
            }
            
            // Mostrar loading
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Guardando producto...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    backdrop: false, // Sin fondo oscuro
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
            
            await this.guardarProducto(formData);
            
            // Cerrar modal ANTES de mostrar mensaje de éxito
            this.close();
            
            // Cerrar cualquier SweetAlert abierto
            if (typeof Swal !== 'undefined') {
                Swal.close();
                
                // Mensaje eliminado - no es necesario mostrar confirmación
            }
            
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            
        } catch (error) {
            console.error('❌ Error al guardar:', error);
            
            // Cerrar cualquier loading anterior
            if (typeof Swal !== 'undefined') {
                Swal.close();
                
                setTimeout(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al agregar producto',
                        text: error.message || 'Error al guardar el producto. Inténtalo nuevamente.',
                        backdrop: false // Sin fondo oscuro
                    });
                }, 100);
            }
        }
    }

    async getFormData() {
        const modal = this.getModal();
        if (!modal) return {};

        const form = modal.querySelector('.form-agregar-producto');
        if (!form) return {};
        
        const productoSelect = form.querySelector('#selectProducto');
        const slotInput = this.modoSlotEspecifico ? 
            form.querySelector('#slotEspecificoValue') : 
            form.querySelector('#selectSlot');
        
        const ubicacionId = await this.obtenerUbicacionId(slotInput?.value);
        
        return {
            producto_id: productoSelect?.value || '',
            slot: slotInput?.value || '',
            cantidad: form.querySelector('[name="cantidad"]')?.value || '',
            stockMinimo: form.querySelector('[name="stockMinimo"]')?.value || '',
            ubicacion_id: ubicacionId
        };
    }

    async obtenerUbicacionId(slotCodigo) {
        console.log('🔍 Obteniendo ubicacion_id para:', {
            slotCodigo,
            modoSlotEspecifico: this.modoSlotEspecifico,
            estanteId: this.estanteId
        });

        if (this.modoSlotEspecifico) {
            // Para slots específicos, necesitamos obtener el ubicacion_id desde la API
            try {
                const apiUrl = `/api/ubicaciones/estante/${this.estanteId}/slot/${slotCodigo}/ubicacion-id`;
                console.log('📡 Llamando a API:', apiUrl);
                
                const response = await fetch(apiUrl);
                console.log('📥 Respuesta API:', response.status, response.statusText);
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('📋 Datos recibidos:', data);
                    
                    if (data.success) {
                        console.log('✅ ubicacion_id obtenido:', data.ubicacion_id);
                        return data.ubicacion_id;
                    } else {
                        console.warn('⚠️ API respondió con success: false:', data.message);
                    }
                } else {
                    console.error('❌ Error en respuesta API:', response.status);
                }
                
                // Si la API no funciona, intentar obtener desde el DOM como fallback
                console.log('🔄 Intentando fallback desde DOM...');
                const ubicacionId = await this.obtenerUbicacionIdDesdeDOM(slotCodigo);
                console.log('🏠 ubicacion_id desde DOM fallback:', ubicacionId);
                
                return ubicacionId;
                
            } catch (error) {
                console.error('❌ Error al obtener ubicacion_id:', error);
                
                // Fallback final: obtener desde el DOM
                console.log('🔄 Fallback final desde DOM...');
                const ubicacionId = await this.obtenerUbicacionIdDesdeDOM(slotCodigo);
                console.log('🏠 Fallback final - ubicacion_id:', ubicacionId);
                return ubicacionId;
            }
        } else {
            // Modo general: usar datos de slots libres
            const slot = this.slotsLibres.find(s => s.codigo === slotCodigo);
            console.log('📋 Slot encontrado en slotsLibres:', slot);
            return slot?.id || null;
        }
    }

    validateForm(data) {
        let isValid = true;
        const errors = [];
        const modal = this.getModal();
        if (!modal) return false;
        
        // Limpiar errores anteriores
        modal.querySelectorAll('.error').forEach(field => {
            field.classList.remove('error');
        });
        
        // Validar producto
        const productoSelect = modal.querySelector('#selectProducto');
        if (!data.producto_id) {
            errors.push('Debe seleccionar un producto');
            if (productoSelect) productoSelect.classList.add('error');
            isValid = false;
        }
        
        // Validar slot
        const slotSelect = this.modoSlotEspecifico ? 
            modal.querySelector('#slotEspecificoValue') : 
            modal.querySelector('#selectSlot');
        if (!data.slot) {
            errors.push('Debe seleccionar un slot de destino');
            if (slotSelect) slotSelect.classList.add('error');
            isValid = false;
        }
        
        // Validar cantidad
        const cantidadInput = modal.querySelector('[name="cantidad"]');
        if (!data.cantidad || parseInt(data.cantidad) <= 0) {
            errors.push('La cantidad debe ser mayor a 0');
            if (cantidadInput) cantidadInput.classList.add('error');
            isValid = false;
        }
        
        // Validar stock mínimo
        const stockMinInput = modal.querySelector('[name="stockMinimo"]');
        if (!data.stockMinimo || parseInt(data.stockMinimo) <= 0) {
            errors.push('El stock mínimo debe ser mayor a 0');
            if (stockMinInput) stockMinInput.classList.add('error');
            isValid = false;
        }
        
        if (errors.length > 0) {
            // Mostrar mensaje de error sin cerrar el modal
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Complete los campos requeridos',
                    html: `<div style="text-align: left; padding: 10px;">
                        <ul style="margin: 0; padding-left: 20px;">
                            ${errors.map(error => `<li style="margin: 5px 0;">${error}</li>`).join('')}
                        </ul>
                    </div>`,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#d92525',
                    backdrop: false, // Sin fondo oscuro
                    allowOutsideClick: true
                });
            }
            
            // Hacer scroll al primer campo con error
            const firstErrorField = modal.querySelector('.error');
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                firstErrorField.focus();
            }
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

    /**
     * Método para obtener ubicacion_id desde el DOM de manera inteligente
     */
    async obtenerUbicacionIdDesdeDOM(slotCodigo) {
        try {
            console.log('🔍 Buscando ubicacion_id en el DOM para slot:', slotCodigo);
            
            // Método 1: Buscar directamente el elemento con data-slot
            const slotElement = document.querySelector(`[data-slot="${slotCodigo}"]`);
            if (slotElement) {
                const ubicacionId = slotElement.dataset.ubicacionId;
                console.log('✅ Método 1 - Encontrado en elemento directo:', ubicacionId);
                if (ubicacionId && ubicacionId !== '') {
                    return ubicacionId;
                }
            }
            
            // Método 2: Buscar en slots vacíos específicamente
            const slotsVacios = document.querySelectorAll('.slot-container.vacio');
            console.log('🔍 Método 2 - Slots vacíos encontrados:', slotsVacios.length);
            
            for (const slot of slotsVacios) {
                const dataSlot = slot.dataset.slot;
                const ubicacionId = slot.dataset.ubicacionId;
                console.log(`📍 Slot ${dataSlot} tiene ubicacion_id: ${ubicacionId}`);
                
                if (dataSlot === slotCodigo && ubicacionId && ubicacionId !== '') {
                    console.log('✅ Método 2 - Encontrado en slot vacío:', ubicacionId);
                    return ubicacionId;
                }
            }
            
            // Método 3: Construir llamada directa a la API como último recurso
            console.log('🔄 Método 3 - Intentando API directa como último recurso...');
            try {
                const response = await fetch(`/api/ubicaciones/estante/${this.estanteId}/slot/${slotCodigo}/ubicacion-id`);
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.ubicacion_id) {
                        console.log('✅ Método 3 - API directa exitosa:', data.ubicacion_id);
                        return data.ubicacion_id;
                    }
                }
            } catch (apiError) {
                console.warn('⚠️ Método 3 - API directa falló:', apiError.message);
            }
            
            console.warn('❌ No se pudo obtener ubicacion_id por ningún método');
            return null;
            
        } catch (error) {
            console.error('❌ Error en obtenerUbicacionIdDesdeDOM:', error);
            return null;
        }
    }
}

// Inicialización robusta
const initModalAgregar = () => {
    if (!window.modalAgregar) {
        console.log('🚀 Inicializando ModalAgregar...');
        window.modalAgregar = new ModalAgregar();
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModalAgregar);
} else {
    initModalAgregar();
}
