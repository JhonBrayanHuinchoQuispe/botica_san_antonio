// ===============================================
// MODAL MOVER PRODUCTO - VERSIÓN SIMPLIFICADA
// ===============================================

class ModalMover {
    constructor() {
        this.modal = null;
        this.currentSlot = null;
        this.init();
    }

    init() {
        console.log('🔄 Inicializando Modal Mover...');
        this.modal = document.getElementById('modalMoverProducto');
        if (!this.modal) {
            console.warn('⚠️ Modal mover no encontrado');
            return;
        }
        
        this.bindEvents();
        console.log('✅ Modal Mover inicializado');
    }

    bindEvents() {
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

        // Botón confirmar movimiento
        const confirmBtn = this.modal.querySelector('#btnConfirmarMovimiento');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => this.confirmMove());
        }

        // Escuchar clicks en botones de mover
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-slot-accion[data-action="mover"]');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                
                const slot = btn.closest('.slot-container');
                if (slot) {
                    this.openForSlot(slot);
                }
            }
        });

        // Validación en tiempo real del formulario
        const slotDestino = this.modal.querySelector('#moverSlotDestino');
        if (slotDestino) {
            slotDestino.addEventListener('change', () => this.validateForm());
        }
    }

    openForSlot(slot) {
        if (!this.modal || !slot) {
            console.error('❌ Modal o slot no válido');
            return;
        }
        
        // Validar que el slot tiene producto
        if (!slot.classList.contains('ocupado')) {
            console.error('❌ El slot no tiene producto para mover');
            Swal.fire({
                icon: 'warning',
                title: 'Slot vacío',
                text: 'Este slot no tiene ningún producto para mover',
                confirmButtonText: 'Entendido',
                customClass: {
                    confirmButton: 'swal-btn-error'
                }
            });
            return;
        }
        
        this.currentSlot = slot;
        
        // Obtener datos del slot con fallbacks seguros
        const slotId = slot.dataset.slot || slot.getAttribute('data-slot') || 'Sin ID';
        const nombreProducto = slot.dataset.productoNombre || 
                              slot.getAttribute('data-producto-nombre') ||
                              slot.querySelector('.producto-nombre')?.textContent || 
                              'Producto sin nombre';
        const stockTexto = slot.dataset.productoStock || 
                          slot.getAttribute('data-producto-stock') ||
                          slot.querySelector('.producto-stock')?.textContent || 
                          'Stock: 0';
        
        // Verificar que tenemos los datos esenciales
        const ubicacionId = slot.dataset.ubicacionId || slot.getAttribute('data-ubicacion-id');
        const productoId = slot.dataset.productoId || slot.getAttribute('data-producto-id');
        
        // Debug completo del slot
        console.log('🎯 Abriendo modal mover para slot:', slotId);
        console.log('📋 Datos del slot:', {
            nombreProducto,
            stockTexto,
            ubicacionId,
            productoId,
            slotClasses: slot.className,
            allDataAttributes: Array.from(slot.attributes).filter(attr => attr.name.startsWith('data-'))
        });
        
        // Validar ubicacion_id
        if (!ubicacionId || ubicacionId === '' || ubicacionId === 'undefined') {
            console.error('❌ ubicacion_id inválido:', ubicacionId);
            console.log('🔍 Intentando obtener ubicacion_id desde la API...');
            
            // Intentar obtener desde la API como fallback
            this.obtenerUbicacionIdDesdeAPI(slotId)
                .then(id => {
                    if (id) {
                        console.log('✅ ubicacion_id obtenido desde API:', id);
                        slot.dataset.ubicacionId = id;
                        this.openForSlot(slot); // Reintentar
                    } else {
                        this.mostrarErrorDatos('No se pudo obtener el ID de ubicación');
                    }
                })
                .catch(error => {
                    console.error('❌ Error obteniendo ubicacion_id:', error);
                    this.mostrarErrorDatos('Error al obtener datos de ubicación');
                });
            return;
        }
        
        // Validar producto_id
        if (!productoId || productoId === '' || productoId === 'undefined') {
            console.error('❌ producto_id inválido:', productoId);
            this.mostrarErrorDatos('No se encontró el ID del producto');
            return;
        }
        
        // Actualizar información del modal
        this.updateModalInfo(slotId, nombreProducto, stockTexto);
        
        // Actualizar opciones de slots destino
        this.updateDestinationOptions(slotId);
        
        // Mostrar modal
        this.modal.classList.remove('hidden');
        
        // Focus en select de destino
        const selectDestino = this.modal.querySelector('#moverSlotDestino');
        if (selectDestino) {
            selectDestino.focus();
        }
    }

    updateModalInfo(slotId, nombreProducto, stockTexto) {
        // Actualizar título del modal
        const titulo = this.modal.querySelector('#moverProductoTitulo');
        if (titulo) {
            titulo.textContent = `Mover ${nombreProducto}`;
        }
        
        // Actualizar slot origen
        const slotOrigen = this.modal.querySelector('#moverSlotOrigen');
        if (slotOrigen) {
            slotOrigen.textContent = slotId;
        }
        
        // Actualizar descripción del slot origen
        const [nivel, posicion] = slotId.split('-');
        const descripcion = this.modal.querySelector('.slot-descripcion');
        if (descripcion) {
            descripcion.textContent = `Nivel ${nivel}, Posición ${posicion}`;
        }
        
        // Extraer información del producto
        const productoInfo = this.extractProductInfo(nombreProducto, stockTexto);
        
        // Actualizar información del producto en la card
        const nombreElement = this.modal.querySelector('#moverProductoNombre');
        const concentracionElement = this.modal.querySelector('#moverProductoConcentracion');
        const stockElement = this.modal.querySelector('#moverProductoStock');
        
        if (nombreElement) {
            nombreElement.textContent = productoInfo.nombre;
        }
        
        if (concentracionElement) {
            concentracionElement.textContent = productoInfo.concentracion;
        }
        
        if (stockElement) {
            stockElement.textContent = `Stock: ${productoInfo.stock}`;
        }
        
        console.log('📄 Información del producto actualizada:', productoInfo);
    }

    extractProductInfo(nombreCompleto, stockTexto) {
        // Extraer concentración del nombre
        const concentracionPatterns = [
            /(\d+(?:\.\d+)?)\s*mg/i,
            /(\d+(?:\.\d+)?)\s*ml/i,
            /(\d+(?:\.\d+)?)\s*g/i,
            /(\d+(?:\.\d+)?)\s*mcg/i,
            /(\d+(?:\.\d+)?)\s*µg/i,
            /(\d+(?:\.\d+)?)\s*ug/i,
            /(\d+(?:\.\d+)?)\s*%/i
        ];
        
        let concentracion = 'Sin especificar';
        let nombreLimpio = nombreCompleto;
        
        for (const pattern of concentracionPatterns) {
            const match = nombreCompleto.match(pattern);
            if (match) {
                concentracion = match[0];
                // Remover la concentración del nombre para obtener nombre limpio
                nombreLimpio = nombreCompleto.replace(pattern, '').trim();
                break;
            }
        }
        
        // Extraer número del stock
        const stockMatch = stockTexto.match(/\d+/);
        const stock = stockMatch ? stockMatch[0] : '0';
        
        return {
            nombre: nombreLimpio || nombreCompleto,
            concentracion: concentracion,
            stock: stock
        };
    }

    updateDestinationOptions(currentSlotId) {
        const selectDestino = this.modal.querySelector('#moverSlotDestino');
        if (!selectDestino) return;
        
        // Limpiar opciones actuales
        selectDestino.innerHTML = '<option value="">Seleccionar slot destino...</option>';
        
        // Obtener SOLO los slots vacíos (excluir ocupados y el slot actual)
        const slotsVacios = document.querySelectorAll('.slot-container.vacio');
        
        // Filtrar el slot actual para que no aparezca como opción
        const slotsDisponibles = Array.from(slotsVacios).filter(slot => 
            slot.dataset.slot !== currentSlotId
        );
        
        // Agrupar por nivel
        const slotsPorNivel = {};
        slotsDisponibles.forEach(slot => {
            const slotId = slot.dataset.slot;
            const [nivel, posicion] = slotId.split('-');
            
            if (!slotsPorNivel[nivel]) {
                slotsPorNivel[nivel] = [];
            }
            
            slotsPorNivel[nivel].push({
                id: slotId,
                posicion: posicion
            });
        });
        
        // Crear optgroups ordenados por nivel (descendente)
        Object.keys(slotsPorNivel).sort((a, b) => parseInt(b) - parseInt(a)).forEach(nivel => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = `Nivel ${nivel} (${slotsPorNivel[nivel].length} disponibles)`;
            
            // Ordenar posiciones dentro del nivel
            slotsPorNivel[nivel].sort((a, b) => parseInt(a.posicion) - parseInt(b.posicion)).forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.id;
                option.textContent = `${slot.id} (Nivel ${nivel}, Posición ${slot.posicion}) - Disponible`;
                optgroup.appendChild(option);
            });
            
            selectDestino.appendChild(optgroup);
        });
        
        // Mostrar información de slots ocupados
        const totalSlots = document.querySelectorAll('.slot-container').length;
        const slotsOcupados = document.querySelectorAll('.slot-container.ocupado').length;
        
        console.log('📋 Opciones actualizadas:');
        console.log(`   • Slots disponibles: ${slotsDisponibles.length}`);
        console.log(`   • Slots ocupados: ${slotsOcupados} (no seleccionables)`);
        console.log(`   • Total slots: ${totalSlots}`);
    }

    validateForm() {
        const slotDestino = this.modal.querySelector('#moverSlotDestino')?.value;
        const confirmBtn = this.modal.querySelector('#btnConfirmarMovimiento');
        
        const isValid = !!slotDestino;
        
        if (confirmBtn) {
            confirmBtn.disabled = !isValid;
            if (isValid) {
                confirmBtn.classList.remove('disabled');
                confirmBtn.style.opacity = '1';
            } else {
                confirmBtn.classList.add('disabled');
                confirmBtn.style.opacity = '0.6';
            }
        }
        
        return isValid;
    }

    confirmMove() {
        if (!this.currentSlot) {
            console.error('❌ No hay slot seleccionado');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se ha seleccionado ningún producto para mover',
                confirmButtonText: 'Entendido',
                customClass: {
                    confirmButton: 'swal-btn-error'
                }
            });
            return;
        }
        
        const formData = this.getFormData();
        
        if (!formData) {
            console.error('❌ No se pudieron obtener los datos del formulario');
            Swal.fire({
                icon: 'error',
                title: 'Error de datos',
                text: 'No se pudieron obtener los datos del producto. Intenta nuevamente.',
                confirmButtonText: 'Entendido',
                customClass: {
                    confirmButton: 'swal-btn-error'
                }
            });
            return;
        }
        
        if (!this.validateMoveData(formData)) {
            console.log('❌ Validación fallida');
            return;
        }
        
        console.log('✅ Confirmando movimiento:', formData);
        
        // Obtener datos adicionales necesarios ANTES de cerrar el modal
        const ubicacionId = this.currentSlot.dataset.ubicacionId || 
                           this.currentSlot.getAttribute('data-ubicacion-id');
        const productoId = this.currentSlot.dataset.productoId || 
                          this.currentSlot.getAttribute('data-producto-id');
        
        // Agregar datos adicionales al formData
        formData.ubicacionId = ubicacionId;
        formData.productoId = productoId;
        formData.slotElement = this.currentSlot; // Guardar referencia al elemento
        
        // Cerrar el modal INMEDIATAMENTE
        this.close();
        
        // Mostrar confirmación con SweetAlert
        Swal.fire({
            title: '¿Confirmar movimiento?',
            html: `
                <div class="confirmacion-movimiento">
                    <div class="producto-confirmacion">
                        <div class="icono-producto">
                            <iconify-icon icon="solar:pill-bold-duotone"></iconify-icon>
                        </div>
                        <div class="info-producto">
                            <h4>${formData.producto.nombre}</h4>
                            <p class="concentracion">${formData.producto.concentracion}</p>
                        </div>
                    </div>
                    <div class="movimiento-info">
                        <div class="posicion-item origen">
                            <span class="label">Desde:</span>
                            <span class="posicion">${formData.origen}</span>
                        </div>
                        <div class="flecha-confirmacion">
                            <iconify-icon icon="solar:arrow-right-bold"></iconify-icon>
                        </div>
                        <div class="posicion-item destino">
                            <span class="label">Hacia:</span>
                            <span class="posicion">${formData.destino}</span>
                        </div>
                    </div>
                    <div class="stock-info">
                        <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                        <span>Stock: ${formData.producto.stock} unidades</span>
                    </div>
                </div>
            `,
            icon: null,
            showCancelButton: true,
            confirmButtonText: '✓ Sí, mover',
            cancelButtonText: '✕ Cancelar',
            customClass: {
                popup: 'swal-modal-mover',
                confirmButton: 'swal-btn-confirmar',
                cancelButton: 'swal-btn-cancelar',
                htmlContainer: 'swal-html-mover'
            },
            buttonsStyling: false,
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar spinner de carga
                Swal.fire({
                    title: 'Moviendo producto...',
                    html: 'Por favor espera mientras se actualiza la ubicación',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Ejecutar el movimiento con la API
                this.executeRealMove(formData);
            }
        });
    }

    getFormData() {
        if (!this.currentSlot) {
            console.error('❌ No hay slot actual seleccionado');
            return null;
        }
        
        // Obtener datos de forma segura
        const nombreProducto = this.currentSlot.dataset.productoNombre || 
                              this.currentSlot.getAttribute('data-producto-nombre') || 
                              'Producto sin nombre';
        const stockTexto = this.currentSlot.dataset.productoStock || 
                          this.currentSlot.getAttribute('data-producto-stock') || 
                          'Stock: 0';
        const slotOrigen = this.currentSlot.dataset.slot || 
                          this.currentSlot.getAttribute('data-slot') || 
                          'Sin slot';
        const estado = this.currentSlot.dataset.estado || 
                      this.currentSlot.getAttribute('data-estado') || 
                      'vacio';
        
        const productoInfo = this.extractProductInfo(nombreProducto, stockTexto);
        
        const formData = {
            origen: slotOrigen,
            destino: this.modal.querySelector('#moverSlotDestino')?.value || '',
            producto: {
                nombre: productoInfo.nombre,
                concentracion: productoInfo.concentracion,
                stock: productoInfo.stock,
                nombreCompleto: nombreProducto,
                estado: estado
            }
        };
        
        console.log('📋 Form data generado:', formData);
        return formData;
    }

    validateMoveData(data) {
        let isValid = true;
        const errors = [];
        
        if (!data.destino) {
            errors.push('Debe seleccionar un slot de destino');
            this.markFieldError('#moverSlotDestino');
            isValid = false;
        }
        
        if (data.origen === data.destino) {
            errors.push('El slot de destino debe ser diferente al origen');
            this.markFieldError('#moverSlotDestino');
            isValid = false;
        }
        
        // Verificar que el slot destino esté disponible
        const slotDestino = document.querySelector(`[data-slot="${data.destino}"]`);
        if (slotDestino && !slotDestino.classList.contains('vacio')) {
            errors.push('El slot de destino ya está ocupado');
            this.markFieldError('#moverSlotDestino');
            isValid = false;
        }
        
        if (errors.length > 0) {
            this.showErrors(errors);
        }
        
        return isValid;
    }

    async executeRealMove(data) {
        try {
            console.log('🔄 Ejecutando movimiento real con API:', data);
            
            // Validar que tenemos los datos necesarios en formData
            if (!data.ubicacionId) {
                throw new Error('No se encontró el ID de ubicación del slot origen');
            }
            
            if (!data.productoId) {
                throw new Error('No se encontró el ID del producto');
            }
            
            console.log('📋 Datos extraídos del formData:', {
                ubicacionId: data.ubicacionId,
                productoId: data.productoId,
                estanteActual: window.estanteActual
            });
            
            // Datos para enviar a la API
            const moveData = {
                estante_id: window.estanteActual || 1,
                ubicacion_origen_id: parseInt(data.ubicacionId),
                slot_origen: data.origen,
                slot_destino: data.destino,
                producto_id: parseInt(data.productoId),
                motivo: 'Reorganización del almacén'
            };
            
            console.log('📤 Enviando datos a API:', moveData);
            
            // Verificar que tenemos el token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                console.warn('⚠️ Token CSRF no encontrado');
            }
            
            // Llamada a la API con timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 segundos timeout
            
            const response = await fetch('/api/ubicaciones/mover-producto', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(moveData),
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                console.log('✅ Movimiento exitoso en API:', result);
                
                // Actualizar la interfaz
                this.executeMove(data);
                
                // Cerrar spinner y mostrar éxito
                Swal.close();
                this.showSuccessNotification(
                    `${data.producto.nombre} movido de ${data.origen} a ${data.destino}`
                );
                
                // Opcional: recargar la página después de un tiempo
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
                
            } else {
                console.error('❌ Error en API:', result);
                throw new Error(result.message || 'Error al mover el producto');
            }
            
        } catch (error) {
            console.error('❌ Error en movimiento:', error);
            
            // Cerrar spinner
            Swal.close();
            
            // Determinar el tipo de error y mostrar mensaje apropiado
            let titulo = 'Error al mover producto';
            let mensaje = 'Ocurrió un error inesperado. Inténtalo nuevamente.';
            
            if (error.name === 'AbortError') {
                titulo = 'Timeout';
                mensaje = 'La operación tardó demasiado tiempo. Verifica tu conexión e inténtalo nuevamente.';
            } else if (error.message.includes('fetch')) {
                titulo = 'Error de conexión';
                mensaje = 'No se pudo conectar con el servidor. Verifica tu conexión a internet.';
            } else if (error.message.includes('token')) {
                titulo = 'Error de autenticación';
                mensaje = 'Sesión expirada. Recarga la página e inténtalo nuevamente.';
            } else if (error.message) {
                mensaje = error.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: titulo,
                text: mensaje,
                confirmButtonText: 'Entendido',
                customClass: {
                    confirmButton: 'swal-btn-error'
                },
                footer: '<small>Si el problema persiste, contacta al administrador</small>'
            });
        }
    }

    executeMove(data) {
        if (!data || !data.origen || !data.destino) {
            console.error('❌ Datos de movimiento inválidos:', data);
            return;
        }
        
        const slotOrigen = data.slotElement || document.querySelector(`[data-slot="${data.origen}"]`);
        const slotDestino = document.querySelector(`[data-slot="${data.destino}"]`);
        
        if (!slotOrigen) {
            console.error('❌ Slot origen no encontrado para:', data.origen);
            return;
        }
        
        if (!slotDestino) {
            console.error('❌ Slot destino no encontrado para:', data.destino);
            return;
        }
        
        if (!data.producto || !data.producto.nombreCompleto) {
            console.error('❌ Datos del producto inválidos:', data.producto);
            return;
        }
        
        console.log('🔄 Ejecutando movimiento:', data.origen, '→', data.destino);
        
        // Actualizar slot destino
        slotDestino.classList.remove('vacio');
        slotDestino.classList.add('ocupado');
        slotDestino.setAttribute('draggable', 'true');
        slotDestino.dataset.estado = data.producto.estado;
        slotDestino.dataset.productoNombre = data.producto.nombreCompleto;
        slotDestino.dataset.productoStock = data.producto.stock;
        
        // Actualizar HTML del slot destino
        const destinoContent = slotDestino.querySelector('.slot-content');
        if (destinoContent) {
            destinoContent.innerHTML = `
                <div class="slot-posicion">${data.destino}</div>
                <div class="producto-info">
                    <div class="producto-nombre">${data.producto.nombreCompleto}</div>
                    <div class="producto-stock">Stock: ${data.producto.stock}</div>
                </div>
                <div class="slot-acciones">
                    <button class="btn-slot-accion" data-action="ver" title="Ver detalles">
                        <iconify-icon icon="solar:eye-bold"></iconify-icon>
                    </button>
                    <button class="btn-slot-accion" data-action="editar" title="Editar producto">
                        <iconify-icon icon="solar:pen-bold"></iconify-icon>
                    </button>
                    <button class="btn-slot-accion" data-action="mover" title="Mover producto">
                        <iconify-icon icon="solar:transfer-horizontal-bold"></iconify-icon>
                    </button>
                </div>
            `;
        }
        
        // Limpiar slot origen
        slotOrigen.classList.remove('ocupado');
        slotOrigen.classList.add('vacio');
        slotOrigen.removeAttribute('draggable');
        slotOrigen.dataset.estado = 'vacio';
        delete slotOrigen.dataset.productoNombre;
        delete slotOrigen.dataset.productoStock;
        
        // Actualizar HTML del slot origen
        const origenContent = slotOrigen.querySelector('.slot-content');
        if (origenContent) {
            origenContent.innerHTML = `
                <div class="slot-vacio">
                    <iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon>
                    <span>Slot Vacío</span>
                    <span class="slot-id">${data.origen}</span>
                </div>
            `;
        }
        
        // Animaciones de éxito
        slotDestino.classList.add('intercambio-exitoso');
        slotOrigen.classList.add('intercambio-exitoso');
        
        setTimeout(() => {
            slotDestino.classList.remove('intercambio-exitoso');
            slotOrigen.classList.remove('intercambio-exitoso');
        }, 800);
        
        console.log('✅ Movimiento ejecutado exitosamente');
    }

    markFieldError(selector) {
        const field = this.modal.querySelector(selector);
        if (field) {
            field.classList.add('error');
            field.style.borderColor = '#ef4444';
            field.addEventListener('change', () => {
                field.classList.remove('error');
                field.style.borderColor = '';
            }, { once: true });
        }
    }

    showErrors(errors) {
        console.error('❌ Errores en formulario de mover:', errors);
        
        Swal.fire({
            icon: 'error',
            title: 'Error en el formulario',
            html: `<ul style="text-align: left; padding-left: 20px; color: #374151;">${errors.map(error => `<li style="margin: 8px 0;">${error}</li>`).join('')}</ul>`,
            confirmButtonText: 'Entendido',
            customClass: {
                confirmButton: 'btn-modal-primary'
            }
        });
    }

    close() {
        if (!this.modal) return;
        
        console.log('❌ Cerrando modal mover producto');
        this.modal.classList.add('hidden');
        
        // Limpiar datos
        this.currentSlot = null;
        this.clearForm();
    }

    clearForm() {
        // Limpiar select de destino
        const selectDestino = this.modal.querySelector('#moverSlotDestino');
        if (selectDestino) {
            selectDestino.value = '';
        }
        
        // Remover clases de error
        this.modal.querySelectorAll('.error').forEach(field => {
            field.classList.remove('error');
            field.style.borderColor = '';
        });
        
        // Resetear botón de confirmar
        const confirmBtn = this.modal.querySelector('#btnConfirmarMovimiento');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.classList.add('disabled');
            confirmBtn.style.opacity = '0.6';
        }
    }

    showSuccessNotification(message) {
        Swal.fire({
            icon: 'success',
            title: '¡Movimiento Exitoso!',
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            backdrop: false, // ¡IMPORTANTE! Sin fondo negro
            customClass: {
                popup: 'success-toast-mover',
                title: 'success-title-mover',
                icon: 'success-icon-mover'
            },
            iconColor: '#10b981',
            background: 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)',
            color: '#047857',
            didOpen: (toast) => {
                // Asegurar que no tenga backdrop
                const backdrop = document.querySelector('.swal2-backdrop-show');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        });
    }

    // Función helper para obtener ubicacion_id desde la API
    async obtenerUbicacionIdDesdeAPI(slotCodigo) {
        try {
            const estanteId = window.estanteActual;
            if (!estanteId) {
                throw new Error('No se encontró el ID del estante');
            }

            const response = await fetch(`/api/ubicaciones/estante/${estanteId}/slot/${slotCodigo}/ubicacion-id`);
            const result = await response.json();

            if (response.ok && result.success) {
                return result.ubicacion_id;
            } else {
                console.error('Error en API:', result.message);
                return null;
            }
        } catch (error) {
            console.error('Error obteniendo ubicacion_id:', error);
            return null;
        }
    }

    // Función helper para mostrar errores de datos
    mostrarErrorDatos(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Error de datos',
            text: mensaje + ' Intenta recargar la página.',
            confirmButtonText: 'Recargar página',
            cancelButtonText: 'Cancelar',
            showCancelButton: true,
            customClass: {
                confirmButton: 'swal-btn-error',
                cancelButton: 'swal-btn-cancelar'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    }
}

// Función de depuración inicial
function verificarConfiguracionMover() {
    console.log('🔍 Verificando configuración del modal mover...');
    
    // Verificar variables globales
    console.log('📋 Variables globales:', {
        estanteActual: window.estanteActual,
        estanteNombre: window.estanteNombre
    });
    
    // Verificar token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    console.log('🔐 Token CSRF:', csrfToken ? 'Presente' : 'Ausente');
    
    // Verificar modal
    const modal = document.getElementById('modalMoverProducto');
    console.log('🪟 Modal mover:', modal ? 'Presente' : 'Ausente');
    
    // Verificar slots con productos
    const slotsOcupados = document.querySelectorAll('.slot-container.ocupado');
    console.log('📦 Slots con productos:', slotsOcupados.length);
    
    // Verificar datos de los primeros 3 slots ocupados
    slotsOcupados.forEach((slot, index) => {
        if (index < 3) {
            console.log(`📋 Slot ${index + 1} datos:`, {
                slot: slot.dataset.slot,
                ubicacionId: slot.dataset.ubicacionId,
                productoId: slot.dataset.productoId,
                productoNombre: slot.dataset.productoNombre,
                productoStock: slot.dataset.productoStock
            });
        }
    });
    
    // Verificar slots vacíos
    const slotsVacios = document.querySelectorAll('.slot-container.vacio');
    console.log('🔳 Slots vacíos:', slotsVacios.length);
    
    console.log('✅ Verificación completada');
}

// Inicialización automática
document.addEventListener('DOMContentLoaded', function() {
    // Ejecutar verificación primero
    verificarConfiguracionMover();
    
    // Inicializar modal
    window.modalMover = new ModalMover();
    console.log('🚀 Modal Mover listo para usar');
});