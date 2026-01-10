// ===============================================
// ESTANTE DETALLE - FUNCIONALIDADES ESPECÍFICAS
// ===============================================

class EstanteDetalle {
    constructor() {
        this.modales = {};
        this.init();
    }

    init() {
        console.log('📋 Inicializando Estante Detalle...');
        this.initModales();
        this.initSlotActions();
        this.initConfigModal();
        this.initMetricas();
        console.log('✅ Estante Detalle inicializado');
    }

    // ===============================================
    // INICIALIZACIÓN DE MODALES
    // ===============================================
    initModales() {
        // Modal Ver Producto
        this.modales.ver = document.getElementById('modalVerProducto');
        
        // Modal Editar Producto
        this.modales.editar = document.getElementById('modalEditarProducto');
        
        // Modal Configurar Estante
        this.modales.config = document.getElementById('modalConfigurarEstante');
        
        // Verificar que todos los modales existan
        Object.keys(this.modales).forEach(key => {
            if (!this.modales[key]) {
                console.warn(`⚠️ Modal ${key} no encontrado`);
            }
        });
        
        this.bindModalEvents();
    }

    bindModalEvents() {
        // Event listeners para todos los modales
        Object.values(this.modales).forEach(modal => {
            if (!modal) return;
            
            // Cerrar con botón X
            const closeBtn = modal.querySelector('.modal-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    modal.classList.add('hidden');
                });
            }
            
            // Cerrar con botón cancelar
            const cancelBtn = modal.querySelector('.btn-modal-secondary');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    modal.classList.add('hidden');
                });
            }
            
            // Cerrar al hacer click fuera
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });
    }

    // ===============================================
    // ACCIONES DE SLOTS
    // ===============================================
    initSlotActions() {
        // Event delegation para botones de acción de slots
        document.addEventListener('click', (e) => {
            const button = e.target.closest('.btn-slot-accion');
            if (!button) return;
            
            const action = button.dataset.action;
            if (action !== 'eliminar') return; // Solo manejar eliminar
            
            const slot = button.closest('.slot-container');
            
            e.preventDefault();
            e.stopPropagation();
            
            // Verificación adicional para asegurar que el slot tiene productos
            if (slot && !slot.classList.contains('ocupado')) {
                console.warn('⚠️ Intento de eliminar un slot vacío');
                return;
            }
            
            this.eliminarProductoDeSlot(slot);
        });
    }

    // ===============================================
    // MODAL VER PRODUCTO
    // ===============================================
    abrirModalVer(slot) {
        if (!this.modales.ver || !slot) return;
        
        const slotId = slot.dataset.slot;
        const productoData = {
            id: slot.dataset.productoId || '',
            nombre: slot.dataset.productoNombre || slot.querySelector('.producto-nombre')?.textContent || 'Producto desconocido',
            marca: slot.dataset.productoMarca || 'Sin marca',
            concentracion: slot.dataset.productoConcentracion || '',
            precio: slot.dataset.productoPrecio || '0.00',
            stock: slot.dataset.productoStock || '0',
            vencimiento: slot.dataset.productoVencimiento || '',
            estado: slot.dataset.estado || 'desconocido'
        };
        
        console.log('👁️ Abriendo modal ver para:', productoData.nombre, 'en slot:', slotId);
        console.log('📊 Datos del producto:', productoData);
        
        // Actualizar información del modal con todos los datos
        this.actualizarModalVerCompleto(slotId, productoData);
        
        // Mostrar modal
        this.modales.ver.classList.remove('hidden');
    }

    actualizarModalVerCompleto(slotId, productoData) {
        const modal = this.modales.ver;
        
        // Título del modal
        const titulo = modal.querySelector('#verProductoTitulo');
        if (titulo) {
            titulo.textContent = `Detalles del Producto`;
        }
        
        // INFORMACIÓN PRINCIPAL (sin imagen)
        const elementos = {
            '#verProductoNombreCompleto': productoData.nombre,
            '#verProductoConcentracion': productoData.concentracion || '1g',
            '#verProductoUbicacion': this.formatearUbicacion(slotId),
            '#verProductoStockValor': `${productoData.stock} unidades`,
            '#verProductoMarca': productoData.marca || 'Kiotex',
            '#verProductoPrecio': `S/ ${parseFloat(productoData.precio || 0).toFixed(2)}`
        };
        
        Object.entries(elementos).forEach(([selector, valor]) => {
            const elemento = modal.querySelector(selector);
            if (elemento) {
                elemento.textContent = valor;
            }
        });
        
        // ESTADO DEL STOCK
        const estadoPill = modal.querySelector('#verProductoEstadoPill');
        if (estadoPill) {
            const estadoTexto = this.getEstadoTexto(productoData.estado);
            estadoPill.textContent = estadoTexto;
            estadoPill.className = `estado-pill estado-${productoData.estado}`;
        }
        
        // FECHA DE VENCIMIENTO - Obtener del data attribute igual que marca y precio
        const vencimientoElement = modal.querySelector('#verProductoVencimiento');
        if (vencimientoElement) {
            if (productoData.vencimiento && productoData.vencimiento !== '') {
                const fechaFormateada = this.formatearFecha(productoData.vencimiento);
                vencimientoElement.textContent = fechaFormateada;
                console.log('✅ Fecha de vencimiento actualizada:', fechaFormateada);
            } else {
                vencimientoElement.textContent = 'No especificada';
                console.log('⚠️ No hay fecha de vencimiento disponible');
            }
        }
        
        console.log('✅ Modal actualizado con datos completos');
        console.log('📊 Datos del producto recibidos:', productoData);
    }
    

    
    formatearUbicacion(slotId) {
        if (!slotId) return 'Sin ubicación';
        const partes = slotId.split('-');
        if (partes.length === 2) {
            return `Nivel ${partes[0]}, Posición ${partes[1]}`;
        }
        return slotId;
    }
    
    formatearFecha(fecha) {
        if (!fecha) return 'No especificada';
        
        try {
            // Si la fecha viene en formato ISO (YYYY-MM-DD)
            if (fecha.includes('-')) {
                const fechaObj = new Date(fecha);
                return fechaObj.toLocaleDateString('es-PE', {
                    day: '2-digit',
                    month: '2-digit', 
                    year: 'numeric'
                });
            }
            // Si ya viene formateada, devolverla tal como está
            return fecha;
        } catch (error) {
            console.warn('Error al formatear fecha:', fecha, error);
            return fecha;
        }
    }

    getEstadoTexto(estado) {
        const estados = {
            'ok': 'Stock Normal',
            'alerta': 'Stock Bajo',
            'peligro': 'Stock Crítico',
            'vacio': 'Vacío'
        };
        return estados[estado] || 'Desconocido';
    }

    // ===============================================
    // MODAL EDITAR PRODUCTO
    // ===============================================
    abrirModalEditar(slot) {
        if (!this.modales.editar || !slot) {
            console.error('❌ Modal editar o slot no encontrado');
            return;
        }
        
        const slotId = slot.dataset.slot;
        const nombreProducto = slot.dataset.productoNombre || slot.querySelector('.producto-nombre')?.textContent || '';
        const stockTexto = slot.dataset.productoStock || slot.querySelector('.producto-stock')?.textContent || '0';
        const stockNumero = stockTexto.replace('Stock: ', '');
        
        console.log('✏️ Abriendo modal editar para:', nombreProducto, 'en slot:', slotId);
        console.log('📊 Stock actual:', stockNumero);
        
        // Actualizar información del modal
        this.actualizarModalEditar(slotId, nombreProducto, stockNumero);
        
        // Mostrar modal primero
        this.modales.editar.classList.remove('hidden');
        
        // Esperar un momento para que el modal se renderice completamente
        setTimeout(() => {
            // Asegurar que el evento del botón guardar esté vinculado
            this.vincularEventoGuardar(slotId);
            
            // Focus en primer campo
            const primerCampo = this.modales.editar.querySelector('#editarStock');
            if (primerCampo) {
                primerCampo.focus();
                primerCampo.select(); // Seleccionar todo el texto para facilitar edición
            }
        }, 100);
    }

    actualizarModalEditar(slotId, nombre, stock) {
        const modal = this.modales.editar;
        
        // Título
        const titulo = modal.querySelector('#editarProductoTitulo');
        if (titulo) {
            titulo.textContent = `Editar ${nombre}`;
        }
        
        // Campos del formulario
        const campos = {
            '#editarNombre': nombre,
            '#editarStock': stock,
            '#editarStockMin': '10', // Valor por defecto
            '#editarPrecio': '0.50', // Valor por defecto
            '#editarCodigo': this.generarCodigo(nombre),
            '#editarLaboratorio': 'Laboratorio Genérico'
        };
        
        Object.entries(campos).forEach(([selector, valor]) => {
            const elemento = modal.querySelector(selector);
            if (elemento) {
                elemento.value = valor;
            }
        });
        

    }

    generarCodigo(nombre) {
        // Generar código simple basado en el nombre
        return nombre.substring(0, 3).toUpperCase() + '500';
    }

    vincularEventoGuardar(slotId) {
        console.log('🔗 Vinculando evento guardar para slot:', slotId);
        
        const modal = this.modales.editar;
        let btnGuardar = modal.querySelector('#btnGuardarEdicion');
        
        if (!btnGuardar) {
            console.error('❌ No se encontró el botón #btnGuardarEdicion');
            // Intentar buscar en todo el documento
            btnGuardar = document.querySelector('#btnGuardarEdicion');
            if (!btnGuardar) {
                console.error('❌ Botón #btnGuardarEdicion no encontrado en todo el documento');
                return;
            }
        }
        
        console.log('✅ Botón encontrado:', btnGuardar);
        
        // Remover todos los event listeners anteriores
        btnGuardar.removeEventListener('click', this.handleGuardarClick);
        
        // Crear función bound para poder removerla después
        this.handleGuardarClick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔥 CLICK EN GUARDAR CAMBIOS - Slot:', slotId);
            this.guardarEdicion(slotId);
        };
        
        // Agregar nuevo event listener
        btnGuardar.addEventListener('click', this.handleGuardarClick);
        
        console.log('✅ Evento vinculado correctamente al botón guardar');
    }

    guardarEdicion(slotId) {
        console.log('🚀 INICIANDO guardarEdicion para slot:', slotId);
        
        const modal = this.modales.editar;
        console.log('📋 Modal encontrado:', modal);
        
        const formData = this.obtenerDatosFormulario(modal);
        console.log('📝 Datos del formulario:', formData);
        
        if (!this.validarFormularioEdicion(formData)) {
            console.log('❌ Validación fallida');
            return;
        }
        
        console.log('✅ Validación exitosa, procediendo a guardar...');
        console.log('💾 Guardando edición para slot:', slotId, formData);
        
        // Obtener el producto_ubicacion_id del slot
        const slot = document.querySelector(`[data-slot="${slotId}"]`);
        const productoUbicacionId = slot?.dataset.productoUbicacionId;
        
        if (!productoUbicacionId) {
            this.mostrarNotificacion('Error: No se pudo identificar la ubicación del producto', 'error');
            return;
        }
        
        // Mostrar loading mientras se procesa
        Swal.fire({
            title: 'Actualizando...',
            text: 'Guardando cambios en el producto',
            icon: 'info',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
            showClass: { popup: '' },
            hideClass: { popup: '' }
        });
        
        // Crear FormData para envío
        const formDataToSend = new FormData();
        formDataToSend.append('producto_ubicacion_id', parseInt(productoUbicacionId));
        formDataToSend.append('nueva_cantidad', parseInt(formData.stock));
        formDataToSend.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
        
        console.log('📤 Enviando datos para actualizar:', {
            producto_ubicacion_id: parseInt(productoUbicacionId),
            nueva_cantidad: parseInt(formData.stock)
        });
        
        fetch('/api/ubicaciones/actualizar-producto', {
            method: 'POST',
            body: formDataToSend
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar slot con nuevos datos
                this.actualizarSlotConDatos(slotId, formData);
                
                // Cerrar modal
                modal.classList.add('hidden');
                
                // Obtener nombre del producto del slot
                const slot = document.querySelector(`[data-slot="${slotId}"]`);
                const nombreProducto = slot?.dataset.productoNombre || slot?.querySelector('.producto-nombre')?.textContent || 'Producto';
                
                // Mostrar notificación
                this.mostrarNotificacion(`${nombreProducto} actualizado correctamente`, 'success');
                
                // Cerrar SweetAlert
                Swal.close();
            } else {
                throw new Error(data.message || 'Error al actualizar el producto');
            }
        })
        .catch(error => {
            console.error('Error actualizando producto:', error);
            Swal.close();
            this.mostrarNotificacion(`Error al actualizar: ${error.message}`, 'error');
        });
    }

    obtenerDatosFormulario(modal) {
        console.log('📊 Obteniendo datos del formulario...');
        const stockInput = modal.querySelector('#editarStock');
        console.log('🔍 Input de stock encontrado:', stockInput);
        console.log('💰 Valor del stock:', stockInput?.value);
        
        const stockValue = stockInput?.value || '0';
        const datos = {
            stock: stockValue
        };
        
        console.log('📦 Datos obtenidos:', datos);
        return datos;
    }

    validarFormularioEdicion(data) {
        const errores = [];
        
        if (!data.stock || parseInt(data.stock) < 0) {
            errores.push('El stock debe ser un número mayor o igual a 0');
        }
        
        if (errores.length > 0) {
            this.mostrarNotificacion(errores.join('<br>'), 'error');
            return false;
        }
        
        return true;
    }

    actualizarSlotConDatos(slotId, data) {
        const slot = document.querySelector(`[data-slot="${slotId}"]`);
        if (!slot) return;
        
        // Actualizar dataset de stock
        slot.dataset.productoStock = data.stock;
        
        // Determinar estado basado en stock
        const stockNum = parseInt(data.stock);
        let estado = 'ok';
        
        if (stockNum <= 5) {
            estado = 'peligro';
        } else if (stockNum <= 20) {
            estado = 'alerta';
        }
        
        slot.dataset.estado = estado;
        
        // Actualizar HTML del stock
        const stockElement = slot.querySelector('.producto-stock');
        if (stockElement) {
            stockElement.textContent = `Stock: ${data.stock}`;
        }
        
        // Actualizar clases de estado visual
        slot.classList.remove('estado-ok', 'estado-alerta', 'estado-peligro');
        slot.classList.add(`estado-${estado}`);
        
        console.log('✅ Slot actualizado:', slotId, 'Nuevo stock:', data.stock);
    }

    // ===============================================
    // ELIMINAR PRODUCTO DE SLOT
    // ===============================================
    eliminarProductoDeSlot(slot) {
        if (!slot) {
            console.error('❌ No se encontró el slot');
            return;
        }
        
        if (typeof Swal === 'undefined') {
            console.error('❌ SweetAlert no está disponible');
            alert('Error: SweetAlert no está cargado. ¿Deseas eliminar el producto?');
            return;
        }
        
        const slotId = slot.dataset.slot;
        const nombreProducto = slot.dataset.productoNombre || slot.querySelector('.producto-nombre')?.textContent || 'Producto';
        const productoUbicacionId = slot.dataset.productoUbicacionId || slot.dataset.ubicacionId;
        
        if (!productoUbicacionId) {
            console.error('❌ No se encontró producto_ubicacion_id');
            Swal.fire({
                title: 'Error',
                text: 'No se pudo identificar el producto en esta ubicación',
                icon: 'error',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        // Confirmar eliminación con SweetAlert2
        Swal.fire({
            title: '¿Eliminar producto?',
            html: `
                <div style="text-align: center; padding: 20px;">
                    <div style="background: linear-gradient(135deg, #ff6b6b, #ee5a52); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 8px 25px rgba(238, 90, 82, 0.3);">
                        <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone" style="color: white; font-size: 36px;"></iconify-icon>
                    </div>
                    
                    <div style="background: #f8f9fa; border-radius: 12px; padding: 20px; margin: 20px 0; border-left: 4px solid #007bff;">
                        <div style="display: flex; align-items: center; margin-bottom: 12px;">
                            <iconify-icon icon="solar:pill-bold-duotone" style="color: #007bff; font-size: 20px; margin-right: 10px;"></iconify-icon>
                            <span style="font-weight: 600; color: #2c3e50;">${nombreProducto}</span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <iconify-icon icon="solar:map-point-bold-duotone" style="color: #28a745; font-size: 20px; margin-right: 10px;"></iconify-icon>
                            <span style="color: #6c757d;">Ubicación: <strong>${slotId}</strong></span>
                        </div>
                    </div>
                    
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 15px 0;">
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <iconify-icon icon="solar:danger-triangle-bold-duotone" style="color: #f39c12; font-size: 24px; margin-right: 10px;"></iconify-icon>
                            <span style="color: #856404; font-weight: 500;">El stock volverá a "Sin ubicar"</span>
                        </div>
                    </div>
                    
                    <p style="color: #6c757d; font-size: 14px; margin-top: 15px;">Esta acción no se puede deshacer</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<iconify-icon icon="solar:trash-bin-minimalistic-bold" style="margin-right: 8px;"></iconify-icon>Sí, eliminar',
            cancelButtonText: '<iconify-icon icon="solar:close-circle-bold" style="margin-right: 8px;"></iconify-icon>Cancelar',
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            customClass: {
                popup: 'swal2-modern-popup',
                confirmButton: 'swal2-modern-confirm',
                cancelButton: 'swal2-modern-cancel'
            },
            showClass: { popup: '' },
            hideClass: { popup: '' }
        }).then((result) => {
            if (result.isConfirmed) {
                this.confirmarEliminacionSlot(slotId, nombreProducto, productoUbicacionId);
            }
        }).catch(error => {
            console.error('❌ Error en SweetAlert:', error);
        });
    }

    confirmarEliminacionSlot(slotId, nombreProducto, productoUbicacionId) {
        // Mostrar loading
        Swal.fire({
            title: 'Eliminando producto...',
            html: `
                <div style="text-align: center; padding: 20px;">
                    <div style="background: linear-gradient(135deg, #4facfe, #00f2fe); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3); animation: pulse 2s infinite;">
                        <iconify-icon icon="solar:box-minimalistic-bold-duotone" style="color: white; font-size: 36px;"></iconify-icon>
                    </div>
                    <p style="color: #6c757d; margin: 0;">Moviendo stock a "Sin ubicar"...</p>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            customClass: {
                popup: 'swal2-modern-popup'
            },
            didOpen: () => {
                Swal.showLoading();
            },
            showClass: { popup: '' },
            hideClass: { popup: '' }
        });
        
        // Crear FormData para envío
        const formDataEliminar = new FormData();
        formDataEliminar.append('producto_ubicacion_id', parseInt(productoUbicacionId));
        formDataEliminar.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
        
        console.log('📤 Enviando datos para eliminar:', {
            producto_ubicacion_id: parseInt(productoUbicacionId)
        });
        
        fetch('/api/ubicaciones/eliminar-producto', {
            method: 'POST',  // POST directo
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formDataEliminar
        })
        .then(response => {
            // Verificar si la respuesta es JSON válida
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('El servidor no devolvió una respuesta JSON válida. Posible error de autenticación o servidor.');
            }
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Limpiar el slot
                this.limpiarSlot(slotId);
                
                // Mostrar notificación de éxito
                Swal.fire({
                    title: '¡Producto eliminado!',
                    html: `
                        <div style="text-align: center; padding: 20px;">
                            <div style="background: linear-gradient(135deg, #00b894, #00cec9); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 8px 25px rgba(0, 184, 148, 0.3);">
                                <iconify-icon icon="solar:check-circle-bold-duotone" style="color: white; font-size: 36px;"></iconify-icon>
                            </div>
                            
                            <div style="background: #f8f9fa; border-radius: 12px; padding: 20px; margin: 20px 0; border-left: 4px solid #00b894;">
                                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                                    <iconify-icon icon="solar:pill-bold-duotone" style="color: #00b894; font-size: 20px; margin-right: 10px;"></iconify-icon>
                                    <span style="font-weight: 600; color: #2c3e50;">${nombreProducto}</span>
                                </div>
                                <p style="color: #6c757d; margin: 0; font-size: 14px;">Stock movido a "Sin ubicar" exitosamente</p>
                            </div>
                        </div>
                    `,
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'swal2-modern-popup'
                    },
                    showClass: { popup: '' },
                    hideClass: { popup: '' }
                });
                
                // Opcional: recargar página después de 3 segundos para actualizar todo
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
                
            } else {
                throw new Error(data.message || 'Error al eliminar el producto');
            }
        })
        .catch(error => {
            console.error('Error eliminando producto:', error);
            Swal.fire({
                title: 'Error',
                text: `Error al eliminar: ${error.message}`,
                icon: 'error',
                confirmButtonText: 'Entendido',
                showClass: { popup: '' },
                hideClass: { popup: '' }
            });
        });
    }

    limpiarSlot(slotId) {
        const slot = document.querySelector(`[data-slot="${slotId}"]`);
        if (!slot) return;
        
        // Cambiar clases
        slot.classList.remove('ocupado');
        slot.classList.add('vacio');
        
        // Limpiar datos del producto
        slot.removeAttribute('data-producto-id');
        slot.removeAttribute('data-producto-nombre');
        slot.removeAttribute('data-producto-marca');
        slot.removeAttribute('data-producto-concentracion');
        slot.removeAttribute('data-producto-precio');
        slot.removeAttribute('data-producto-vencimiento');
        slot.removeAttribute('data-producto-stock');
        slot.removeAttribute('data-producto-ubicacion-id');
        
        // Actualizar contenido HTML
        const contenido = slot.querySelector('.slot-contenido');
        if (contenido) {
            contenido.innerHTML = `
                <div class="slot-vacio-content">
                    <iconify-icon icon="solar:add-circle-bold-duotone" class="slot-vacio-icon"></iconify-icon>
                    <span class="slot-numero">${slotId}</span>
                </div>
            `;
        }
        
        console.log('✅ Slot limpiado:', slotId);
    }

    // ===============================================
    // MODAL CONFIGURACIÓN
    // ===============================================
    initConfigModal() {
        const btnConfig = document.getElementById('btnConfigurarEstante');
        if (btnConfig && this.modales.config) {
            btnConfig.addEventListener('click', () => {
                this.modales.config.classList.remove('hidden');
            });
            
            this.initConfigTabs();
        }
    }

    initConfigTabs() {
        const modal = this.modales.config;
        if (!modal) return;
        
        const tabs = modal.querySelectorAll('.config-tab');
        const contents = modal.querySelectorAll('.config-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.dataset.configTab;
                
                // Remover clases active
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => {
                    c.classList.remove('active');
                    c.classList.add('hidden');
                });
                
                // Activar pestaña seleccionada
                tab.classList.add('active');
                const targetContent = modal.querySelector(`#config-${tabId}`);
                if (targetContent) {
                    targetContent.classList.add('active');
                    targetContent.classList.remove('hidden');
                }
            });
        });
    }

    // ===============================================
    // MÉTRICAS Y ANIMACIONES
    // ===============================================
    initMetricas() {
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animarProgreso(entry.target);
                }
            });
        }, observerOptions);

        // Observar barras de progreso
        document.querySelectorAll('.metrica-compacta-barra').forEach(barra => {
            observer.observe(barra);
        });
    }

    animarProgreso(barra) {
        const progreso = barra.querySelector('.barra-progreso');
        if (progreso) {
            const width = progreso.style.width;
            progreso.style.width = '0%';
            
            setTimeout(() => {
                progreso.style.width = width;
            }, 100);
        }
    }

    // ===============================================
    // UTILIDADES
    // ===============================================
    mostrarNotificacion(mensaje, tipo = 'success') {
        // SweetAlert2 SIN ANIMACIONES según el tipo
        if (tipo === 'success') {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: mensaje,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                showClass: { popup: '' },
                hideClass: { popup: '' }
            });
        } else if (tipo === 'error') {
            Swal.fire({
                icon: 'error',
                title: '¡Error!',
                html: mensaje,
                confirmButtonText: 'Entendido',
                showClass: { popup: '' },
                hideClass: { popup: '' }
            });
        }
    }
}

// Inicialización automática
document.addEventListener('DOMContentLoaded', function() {
    window.estanteDetalle = new EstanteDetalle();
});
