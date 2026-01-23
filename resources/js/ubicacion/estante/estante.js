// ===================================================
// ESTANTE MANAGER - DRAG AND DROP PROFESIONAL
// ===================================================

class EstanteManager {
    constructor() {
        this.draggedSlot = null;
        this.dragPreview = null;
        this.currentTab = 'productos';
    }

    // ===============================================
    // INICIALIZACIÓN
    // ===============================================
    init() {
        console.log('🚀 Inicializando EstanteManager...');
        
        this.initTabs();
        this.initDragAndDrop();
        this.initSlotInteractions();
        this.initModalHandlers();
        this.bindEvents();
        this.runTests();
        
        console.log('✅ EstanteManager inicializado correctamente');
    }

    // ===============================================
    // PESTAÑAS
    // ===============================================
    initTabs() {
        const tabs = document.querySelectorAll('.estante-tab');
        const contents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remover active de todas las pestañas
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                
                // Activar pestaña seleccionada
                tab.classList.add('active');
                this.currentTab = tab.dataset.tab;
                
                // Mostrar contenido correspondiente
                const targetContent = document.getElementById(`tab-${this.currentTab}`);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }

    // ===============================================
    // DRAG AND DROP PRINCIPAL
    // ===============================================
    initDragAndDrop() {
        console.log('🔄 Inicializando Drag and Drop...');
        
        // Forzar atributo draggable en todos los slots ocupados
        document.querySelectorAll('.slot-container.ocupado').forEach(slot => {
            slot.setAttribute('draggable', 'true');
            console.log('✅ Slot habilitado para drag:', slot.dataset.slot);
        });
        
        this.bindDragEvents();
        console.log('✅ Drag and Drop inicializado correctamente');
    }

    bindDragEvents() {
        // Drag Start
        document.addEventListener('dragstart', (e) => {
            // Evitar drag desde botones
            if (e.target.closest('.btn-slot-accion')) {
                e.preventDefault();
                return false;
            }
            
            const slot = e.target.closest('.slot-container');
            if (!slot || !slot.classList.contains('ocupado')) {
                e.preventDefault();
                return false;
            }

            console.log('🎯 Drag iniciado:', slot.dataset.slot);
            this.draggedSlot = slot;
            slot.classList.add('dragging');
            
            // Crear preview
            this.dragPreview = this.createDragPreview(slot);
            
            // Marcar slots válidos para drop
            document.querySelectorAll('.slot-container.ocupado').forEach(s => {
                if (s !== slot) s.classList.add('drop-zone');
            });

            // Marcar slots vacíos como no disponibles
            document.querySelectorAll('.slot-container.vacio').forEach(s => {
                s.classList.add('no-drop');
            });

            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', slot.dataset.slot);
        });

        // Drag Over
        document.addEventListener('dragover', (e) => {
            e.preventDefault();
            
            // Actualizar posición del preview
            if (this.dragPreview) {
                this.dragPreview.style.left = (e.clientX + 10) + 'px';
                this.dragPreview.style.top = (e.clientY + 10) + 'px';
            }

            const slot = e.target.closest('.slot-container');
            if (slot && slot.classList.contains('ocupado') && slot !== this.draggedSlot) {
                e.dataTransfer.dropEffect = 'move';
                
                // Limpiar otros drag-over
                document.querySelectorAll('.slot-container.drag-over').forEach(s => {
                    if (s !== slot) s.classList.remove('drag-over');
                });
                
                slot.classList.add('drag-over');
            }
        });

        // Drag Enter
        document.addEventListener('dragenter', (e) => {
            e.preventDefault();
        });

        // Drag Leave
        document.addEventListener('dragleave', (e) => {
            const slot = e.target.closest('.slot-container');
            if (slot) {
                slot.classList.remove('drag-over');
            }
        });

        // Drop - EVENTO PRINCIPAL
        document.addEventListener('drop', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('📦 Drop event triggered');
            
            const targetSlot = e.target.closest('.slot-container');
            
            if (targetSlot && targetSlot.classList.contains('ocupado') && 
                targetSlot !== this.draggedSlot && this.draggedSlot) {
                
                console.log('✅ Drop válido:', this.draggedSlot.dataset.slot, '→', targetSlot.dataset.slot);
                
                try {
                    // Realizar intercambio y esperar resultado
                    const resultado = await this.intercambiarProductos(this.draggedSlot, targetSlot);
                    
                    if (resultado && resultado.success !== false) {
                        console.log('🎉 Intercambio completado exitosamente');
                    }
                } catch (error) {
                    console.error('❌ Error en el intercambio:', error);
                }
            } else {
                console.log('❌ Drop cancelado - destino inválido');
            }
        });

        // Drag End
        document.addEventListener('dragend', (e) => {
            this.cleanupDrag();
        });
    }

    createDragPreview(slot) {
        const preview = document.createElement('div');
        preview.className = 'drag-preview';
        preview.innerHTML = `
            <div style="font-weight: bold; font-size: 0.9rem; margin-bottom: 0.5rem;">
                ${slot.dataset.productoNombre}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-light);">
                Stock: ${slot.dataset.productoStock}
            </div>
        `;
        document.body.appendChild(preview);
        return preview;
    }

    // ===============================================
    // INTERCAMBIO DE PRODUCTOS - FUNCIÓN PRINCIPAL
    // ===============================================
    async intercambiarProductos(slot1, slot2) {
        console.log('🎯 === INICIANDO PROCESO DE INTERCAMBIO ===');
        
        // Limpiar clases de drag inmediatamente
        this.cleanupDrag();
        
        // Obtener datos de ambos slots
        const slot1Data = {
            nombre: slot1.dataset.productoNombre,
            stock: slot1.dataset.productoStock,
            estado: slot1.dataset.estado,
            slotId: slot1.dataset.slot,
            productoId: slot1.dataset.productoId,
            ubicacionId: slot1.dataset.ubicacionId
        };

        const slot2Data = {
            nombre: slot2.dataset.productoNombre,
            stock: slot2.dataset.productoStock,
            estado: slot2.dataset.estado,
            slotId: slot2.dataset.slot,
            productoId: slot2.dataset.productoId,
            ubicacionId: slot2.dataset.ubicacionId
        };

        console.log('📋 Datos obtenidos:', { slot1: slot1Data, slot2: slot2Data });

        // Validar que tenemos los datos necesarios
        if (!slot1Data.productoId || !slot1Data.ubicacionId || !slot2Data.productoId || !slot2Data.ubicacionId) {
            console.error('❌ Faltan datos necesarios para el intercambio');
            
            await Swal.fire({
                title: 'Error de Datos',
                text: 'No se encontraron todos los datos necesarios. Recarga la página e inténtalo nuevamente.',
                icon: 'error',
                confirmButtonText: 'Recargar Página',
                confirmButtonColor: '#ef4444'
            }).then(() => {
                window.location.reload();
            });
            
            return { success: false, error: 'Datos incompletos' };
        }

        // MOSTRAR CONFIRMACIÓN
        const confirmacion = await Swal.fire({
            title: '¿Intercambiar Productos?',
            html: `
                <div style="padding: 25px 20px;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin: 20px 0;">
                        <!-- Producto 1 -->
                        <div style="background: #f8fafc; border: 2px solid #e2e8f0; padding: 18px; border-radius: 12px; text-align: center; min-width: 130px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                            <div style="font-size: 20px; margin-bottom: 8px; color: #64748b;">💊</div>
                            <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px; color: #1e293b;">${slot1Data.nombre}</div>
                            <div style="font-size: 12px; color: #64748b;">Ubicación: ${slot1Data.slotId}</div>
                        </div>
                        
                        <!-- Icono de intercambio -->
                        <div style="background: #10b981; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                            <div style="color: white; font-size: 24px; font-weight: bold;">⇄</div>
                        </div>
                        
                        <!-- Producto 2 -->
                        <div style="background: #f8fafc; border: 2px solid #e2e8f0; padding: 18px; border-radius: 12px; text-align: center; min-width: 130px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                            <div style="font-size: 20px; margin-bottom: 8px; color: #64748b;">💊</div>
                            <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px; color: #1e293b;">${slot2Data.nombre}</div>
                            <div style="font-size: 12px; color: #64748b;">Ubicación: ${slot2Data.slotId}</div>
                        </div>
                    </div>
                    
                    <!-- Mensaje informativo sobrio -->
                    <div style="background: #e0f2fe; border: 1px solid #b3e5fc; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center;">
                        <div style="color: #0277bd; font-weight: 600; font-size: 14px; margin-bottom: 4px;">
                            <span style="font-size: 16px;">ℹ️</span> Confirmación de Intercambio
                        </div>
                        <div style="color: #01579b; font-size: 13px;">
                            Los productos cambiarán de ubicación en el almacén
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: false,
            showCloseButton: false,
            confirmButtonText: 'Sí, Intercambiar',
            cancelButtonText: 'Cancelar',
            denyButtonText: '',
            allowOutsideClick: false,
            allowEscapeKey: true,
            reverseButtons: false,
            focusConfirm: true,
            customClass: {
                popup: 'modal-intercambio-sobrio',
                confirmButton: 'btn-confirmar-sobrio',
                cancelButton: 'btn-cancelar-sobrio',
                actions: 'swal2-actions-clean'
            },
            buttonsStyling: false,
            width: '480px',
            didOpen: () => {
                // LIMPIAR BOTONES EXTRA DESPUÉS DE RENDERIZAR
                setTimeout(() => {
                    const actions = document.querySelector('.swal2-actions');
                    if (actions) {
                        // Eliminar solo botones específicos problemáticos
                        const buttons = actions.querySelectorAll('button');
                        buttons.forEach((btn) => {
                            const text = btn.textContent?.trim().toLowerCase();
                            // Eliminar solo botones "No" pero mantener "Cancelar" y "Sí, Intercambiar"
                            if (text === 'no' || text === 'deny' || btn.classList.contains('swal2-deny')) {
                                btn.remove();
                            }
                        });
                        
                        // Asegurar que tenemos exactamente 2 botones: Confirmar y Cancelar
                        const remainingButtons = actions.querySelectorAll('button');
                        if (remainingButtons.length > 2) {
                            // Si hay más de 2, eliminar los extras que no sean confirm ni cancel
                            remainingButtons.forEach((btn, index) => {
                                if (!btn.classList.contains('swal2-confirm') && 
                                    !btn.classList.contains('swal2-cancel') && 
                                    index >= 2) {
                                    btn.remove();
                                }
                            });
                        }
                    }
                }, 10);
            }
        });

        if (!confirmacion.isConfirmed) {
            console.log('❌ Intercambio cancelado por el usuario');
            return { success: false, cancelled: true };
        }

        // MOSTRAR LOADING
        Swal.fire({
            title: 'Intercambiando Productos...',
            html: `
                <div style="text-align: center; padding: 20px;">
                    <div style="color: #10b981; font-size: 48px; margin-bottom: 15px;">
                        <iconify-icon icon="solar:refresh-bold-duotone" class="rotating"></iconify-icon>
                    </div>
                    <div style="font-weight: 500; margin-bottom: 10px;">Procesando intercambio en la base de datos...</div>
                    <div style="color: #6b7280; font-size: 0.9em;">Por favor espera un momento</div>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });

        try {
            const resultado = await this.intercambiarEnBaseDatos(slot1Data, slot2Data);
            
            if (!resultado || resultado.success === false) {
                throw new Error(resultado?.message || 'Error en el guardado');
            }

            // MOSTRAR ÉXITO (SIN BOTONES)
            await Swal.fire({
                title: '¡Intercambio Exitoso!',
                html: `
                    <div style="text-align: center; padding: 20px;">
                        <div style="color: #10b981; font-size: 64px; margin-bottom: 20px;">
                            <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                        </div>
                        <div style="font-weight: 600; color: #1f2937; margin-bottom: 15px; font-size: 1.1em;">
                            Los productos se intercambiaron correctamente
                        </div>
                        <div style="background: #f0fdf4; padding: 15px; border-radius: 10px; border: 1px solid #bbf7d0;">
                            <div style="font-weight: 500; color: #15803d;">
                                <iconify-icon icon="solar:database-bold-duotone" style="margin-right: 8px;"></iconify-icon>
                                Cambios guardados en la base de datos
                            </div>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: false,
                showCloseButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: {
                    popup: 'modal-intercambio-sobrio'
                },
                timer: 2500,
                timerProgressBar: true
            });

            window.location.reload();
            return { success: true, data: resultado };
            
        } catch (error) {
            console.error('💥 Error en el intercambio:', error);
            
            await Swal.fire({
                title: 'Error en el Intercambio',
                text: error.message || 'No se pudieron guardar los cambios. Inténtalo nuevamente.',
                icon: 'error',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#ef4444'
            });
            
            return { success: false, error: error.message };
        }
    }

    // ===============================================
    // COMUNICACIÓN CON BACKEND
    // ===============================================
    async intercambiarEnBaseDatos(slot1Data, slot2Data) {
        console.log('💾 Guardando intercambio en base de datos...');
        
        // Verificar token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            throw new Error('Token CSRF no encontrado');
        }

        // Validar datos críticos
        const errores = [];
        if (!slot1Data.productoId || slot1Data.productoId === '' || slot1Data.productoId === 'undefined') {
            errores.push(`Producto 1 ID inválido`);
        }
        if (!slot1Data.ubicacionId || slot1Data.ubicacionId === '' || slot1Data.ubicacionId === 'undefined') {
            errores.push(`Ubicación 1 ID inválido`);
        }
        if (!slot2Data.productoId || slot2Data.productoId === '' || slot2Data.productoId === 'undefined') {
            errores.push(`Producto 2 ID inválido`);
        }
        if (!slot2Data.ubicacionId || slot2Data.ubicacionId === '' || slot2Data.ubicacionId === 'undefined') {
            errores.push(`Ubicación 2 ID inválido`);
        }
        
        if (errores.length > 0) {
            throw new Error('Errores de validación: ' + errores.join(', '));
        }

        // Preparar datos para envío
        const intercambioData = {
            slot1_codigo: slot1Data.slotId,
            slot1_producto_id: parseInt(slot1Data.productoId),
            slot1_ubicacion_id: parseInt(slot1Data.ubicacionId),
            slot2_codigo: slot2Data.slotId,
            slot2_producto_id: parseInt(slot2Data.productoId),
            slot2_ubicacion_id: parseInt(slot2Data.ubicacionId),
            estante_id: parseInt(window.estanteActual) || 1
        };

        console.log('📤 Enviando datos:', intercambioData);

        // Ejecutar petición
        const response = await fetch('/api/ubicaciones/drag-drop-intercambio', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(intercambioData)
        });

        const result = await response.json();
        console.log('📊 Respuesta del servidor:', result);

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Error en el intercambio');
        }

        console.log('✅ Intercambio guardado exitosamente en BD');
        
        return {
            success: true,
            message: result.message,
            data: result.data
        };
    }

    // ===============================================
    // UTILIDADES
    // ===============================================
    generateSlotHTML(slotId, productoData) {
        return `
            <div class="slot-posicion">${slotId}</div>
            <div class="producto-info">
                <div class="producto-nombre">${productoData.nombre}</div>
                <div class="producto-stock">Stock: ${productoData.stock}</div>
            </div>
            <div class="slot-acciones">
                <button class="btn-slot-accion" data-action="ver">
                    <iconify-icon icon="solar:eye-bold"></iconify-icon>
                </button>
                <button class="btn-slot-accion" data-action="editar">
                    <iconify-icon icon="solar:pen-bold"></iconify-icon>
                </button>
                <button class="btn-slot-accion" data-action="mover">
                    <iconify-icon icon="solar:transfer-horizontal-bold"></iconify-icon>
                </button>
            </div>
        `;
    }

    cleanupDrag() {
        // Limpiar slot arrastrado
        if (this.draggedSlot) {
            this.draggedSlot.classList.remove('dragging');
            this.draggedSlot = null;
        }
        
        // Limpiar preview
        if (this.dragPreview) {
            this.dragPreview.remove();
            this.dragPreview = null;
        }

        // Limpiar todas las clases de drag
        document.querySelectorAll('.slot-container').forEach(slot => {
            slot.classList.remove('drag-over', 'drop-zone', 'no-drop');
        });
    }

    // ===============================================
    // INTERACCIONES CON SLOTS
    // ===============================================
    initSlotInteractions() {
        // Acciones de slots
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-slot-accion');
            if (!btn) return;
            
            const slot = btn.closest('.slot-container');
            const action = btn.dataset.action;
            
            // Solo manejar las acciones específicas de este archivo
            if (!['ver', 'editar', 'mover'].includes(action)) {
                return; // Dejar que otros event listeners manejen otras acciones
            }
            
            e.preventDefault();
            e.stopPropagation();
            
            console.log('🎯 Acción de slot (estante.js):', action, 'en slot:', slot?.dataset.slot);
            
            switch(action) {
                case 'ver':
                    this.abrirModalVer(slot);
                    break;
                case 'editar':
                    this.abrirModalEditar(slot);
                    break;
                case 'mover':
                    this.abrirModalMover(slot);
                    break;
            }
        });
    }

    // ===============================================
    // MODALES (SIMPLIFICADOS)
    // ===============================================
    async abrirModalVer(slot) {
        const modal = document.getElementById('modalVerProducto');
        if (!modal || !slot) return;
        
        console.log('👁️ Abriendo modal ver para slot:', slot.dataset.slot);
        
        // Obtener todos los datos del producto desde los data attributes
        const productoData = {
            nombre: slot.dataset.productoNombre || 'Producto desconocido',
            marca: slot.dataset.productoMarca || 'Sin especificar',
            concentracion: slot.dataset.productoConcentracion || '',
            precio: slot.dataset.productoPrecio || '0',
            vencimiento: slot.dataset.productoVencimiento || '',
            stock: slot.dataset.productoStock?.replace('Stock: ', '') || '0',
            ubicacion: slot.dataset.slot || '',
            nivel: slot.dataset.nivel || '',
            posicion: slot.dataset.posicion || '',
            estado: slot.dataset.estado || 'desconocido'
        };
        
        console.log('📋 Datos del producto para modal ver:', productoData);
        
        // Llenar el modal con los datos
        this.llenarModalVer(productoData);
        
        modal.classList.remove('hidden');
    }
    
    llenarModalVer(data) {
        try {
            // Título del modal
            const titulo = document.getElementById('verProductoTitulo');
            if (titulo) {
                titulo.textContent = `Detalles del Producto`;
            }
            
            // Nombre completo del producto (separar nombre y concentración)
            let nombreCompleto = data.nombre;
            let concentracion = data.concentracion;
            
            // Si no hay concentración separada, intentar extraerla del nombre
            if (!concentracion && data.nombre) {
                const match = data.nombre.match(/(.+?)\s+(\d+(?:\.\d+)?(?:mg|g|ml|mcg|ui|iu))/i);
                if (match) {
                    nombreCompleto = match[1].trim();
                    concentracion = match[2];
                }
            }
            
            const nombreElement = document.getElementById('verProductoNombreCompleto');
            if (nombreElement) {
                nombreElement.textContent = nombreCompleto;
            }
            
            const concentracionElement = document.getElementById('verProductoConcentracion');
            if (concentracionElement) {
                concentracionElement.textContent = concentracion || 'Sin especificar';
            }
            
            // Ubicación
            const ubicacionElement = document.getElementById('verProductoUbicacion');
            if (ubicacionElement) {
                ubicacionElement.textContent = `Nivel ${data.nivel}, Posición ${data.posicion}`;
            }
            
            // Stock y estado
            const stockElement = document.getElementById('verProductoStockValor');
            if (stockElement) {
                stockElement.textContent = `${data.stock} unidades`;
            }
            
            const estadoPill = document.getElementById('verProductoEstadoPill');
            if (estadoPill) {
                const estadoTexto = this.obtenerEstadoTexto(data.estado);
                estadoPill.textContent = estadoTexto.texto;
                estadoPill.className = `estado-pill ${estadoTexto.clase}`;
            }
            
            // MARCA - Esta es la corrección principal
            const marcaElement = document.getElementById('verProductoMarca');
            if (marcaElement) {
                marcaElement.textContent = data.marca || 'Sin especificar';
                console.log('✅ Marca establecida en modal:', data.marca);
            }
            
            // Precio y vencimiento (datos reales)
            const precioElement = document.getElementById('verProductoPrecio');
            if (precioElement) {
                const precio = data.precio && data.precio !== '0' ? parseFloat(data.precio).toFixed(2) : '0.00';
                precioElement.textContent = `S/ ${precio}`;
                console.log('✅ Precio establecido en modal:', `S/ ${precio}`);
            }
            
            const vencimientoElement = document.getElementById('verProductoVencimiento');
            if (vencimientoElement) {
                const fechaVencimiento = data.vencimiento || 'No especificada';
                vencimientoElement.textContent = fechaVencimiento;
                console.log('✅ Fecha de vencimiento establecida en modal:', fechaVencimiento);
            }
            
            // Imagen del producto
            const imagenElement = document.getElementById('verProductoImagen');
            const placeholderElement = document.getElementById('verProductoImagenPlaceholder');
            
            if (imagenElement && placeholderElement) {
                // Por ahora mostrar placeholder, luego se puede agregar lógica para imágenes reales
                imagenElement.style.display = 'none';
                placeholderElement.style.display = 'flex';
            }
            
            console.log('✅ Modal de ver llenado correctamente');
            
        } catch (error) {
            console.error('❌ Error al llenar modal de ver:', error);
        }
    }
    
    obtenerEstadoTexto(estado) {
        const estados = {
            'ok': { texto: 'Normal', clase: 'estado-normal' },
            'alerta': { texto: 'Stock Bajo', clase: 'estado-alerta' },
            'peligro': { texto: 'Stock Crítico', clase: 'estado-critico' },
            'vacio': { texto: 'Vacío', clase: 'estado-vacio' }
        };
        
        return estados[estado] || { texto: 'Desconocido', clase: 'estado-desconocido' };
    }

    async abrirModalEditar(slot) {
        const modal = document.getElementById('modalEditarProducto');
        if (!modal || !slot) return;
        
        console.log('✏️ Abriendo modal editar para slot:', slot.dataset.slot);
        
        // Obtener datos del producto
        const productoData = {
            id: slot.dataset.productoId,
            nombre: slot.dataset.productoNombre || '',
            marca: slot.dataset.productoMarca || '',
            concentracion: slot.dataset.productoConcentracion || '',
            stock: slot.dataset.productoStock?.replace('Stock: ', '') || '0',
            slotId: slot.dataset.slot,
            ubicacionId: slot.dataset.ubicacionId
        };
        
        console.log('📋 Datos del producto a editar:', productoData);
        
        // Cargar datos en el formulario
        this.cargarDatosEnModalEditar(productoData);
        
        // Mostrar modal
        modal.classList.remove('hidden');
        
        // Focus en el primer campo
        setTimeout(() => {
            const primerCampo = modal.querySelector('#editarNombre');
            if (primerCampo) primerCampo.focus();
        }, 300);
    }
    
    cargarDatosEnModalEditar(productoData) {
        // Actualizar título del modal
        const titulo = document.getElementById('editarProductoTitulo');
        if (titulo) {
            titulo.textContent = `Editar ${productoData.nombre}`;
        }
        
        // Actualizar preview de información
        const previewUbicacion = document.getElementById('previewUbicacion');
        if (previewUbicacion) {
            previewUbicacion.textContent = `Ubicación: ${productoData.slotId}`;
        }
        
        // Cargar datos en los campos
        const nombreInput = document.getElementById('editarNombre');
        const marcaInput = document.getElementById('editarMarca');
        const stockInput = document.getElementById('editarStock');
        const stockMinInput = document.getElementById('editarStockMin');
        
        if (nombreInput) {
            nombreInput.value = productoData.nombre;
            // Agregar efecto visual para campos con datos
            nombreInput.classList.add('has-value');
        }
        
        if (marcaInput) {
            marcaInput.value = productoData.marca || '';
            if (productoData.marca) {
                marcaInput.classList.add('has-value');
            }
            marcaInput.placeholder = 'Ingresa la marca del producto';
        }
        
        if (stockInput) {
            stockInput.value = productoData.stock;
            stockInput.classList.add('has-value');
        }
        
        if (stockMinInput) {
            stockMinInput.value = '10'; // Valor por defecto
            stockMinInput.classList.add('has-value');
        }
        
        // Agregar listener para detectar cambios
        this.agregarListenersDeDeteccionDeCambios();
        
        // Guardar datos originales para comparación
        this.datosOriginalesEditar = { 
            ...productoData,
            stock_minimo: 10 // Incluir valor por defecto
        };
        
        console.log('✅ Datos cargados en el modal de editar');
        console.log('📋 Datos originales guardados:', this.datosOriginalesEditar);
    }
    
    agregarListenersDeDeteccionDeCambios() {
        const campos = ['editarNombre', 'editarMarca', 'editarStock', 'editarStockMin'];
        
        campos.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                // Agregar clase when tiene valor
                campo.addEventListener('input', () => {
                    if (campo.value.trim()) {
                        campo.classList.add('has-value');
                    } else {
                        campo.classList.remove('has-value');
                    }
                });
                
                // Efecto de focus
                campo.addEventListener('focus', () => {
                    campo.parentElement.classList.add('focused');
                });
                
                campo.addEventListener('blur', () => {
                    campo.parentElement.classList.remove('focused');
                });
            }
        });
    }
    
    async guardarEdicionProducto() {
        console.log('💾 === INICIANDO GUARDADO DE EDICIÓN ===');
        
        // CERRAR EL MODAL INMEDIATAMENTE AL HACER CLIC EN GUARDAR
        const modalEditar = document.getElementById('modalEditarProducto');
        if (modalEditar) {
            modalEditar.classList.add('hidden');
            console.log('✅ Modal cerrado inmediatamente al hacer clic en Guardar');
        }
        
        // Obtener valores del formulario
        const nombreInput = document.getElementById('editarNombre');
        const marcaInput = document.getElementById('editarMarca');
        const stockInput = document.getElementById('editarStock');
        const stockMinInput = document.getElementById('editarStockMin');
        
        if (!nombreInput || !stockInput) {
            console.error('❌ Error: Campos requeridos no encontrados');
            return;
        }
        
        // Recopilar datos del formulario
        const datosFormulario = {
            nombre: nombreInput.value.trim(),
            marca: marcaInput?.value.trim() || '',
            stock: parseInt(stockInput.value) || 0,
            stock_minimo: parseInt(stockMinInput?.value) || 10
        };
        
        // Validar datos
        const errores = this.validarDatosEdicion(datosFormulario);
        if (errores.length > 0) {
            // Reabrir modal para corregir errores
            const modalEditarError = document.getElementById('modalEditarProducto');
            if (modalEditarError) {
                modalEditarError.classList.remove('hidden');
                console.log('🔄 Modal reabierto para corregir errores');
            }
            
            await Swal.fire({
                title: 'Errores de Validación',
                html: `
                    <div style="text-align: left; padding: 20px;">
                        <ul style="color: #dc2626; margin-left: 20px;">
                            ${errores.map(error => `<li>${error}</li>`).join('')}
                        </ul>
                    </div>
                `,
                icon: 'error',
                confirmButtonText: 'Corregir',
                confirmButtonColor: '#ef4444'
            });
            return;
        }
        
        // Verificar si hay cambios
        const hayCambios = this.verificarCambios(datosFormulario);
        if (!hayCambios) {
            await Swal.fire({
                title: 'Sin Cambios',
                text: 'No se detectaron cambios en los datos del producto.',
                icon: 'info',
                confirmButtonText: 'Entendido',
                showCancelButton: false,
                showDenyButton: false,
                showCloseButton: false,
                allowOutsideClick: true,
                allowEscapeKey: true,
                customClass: {
                    popup: 'modal-intercambio-sobrio modal-sin-cambios',
                    confirmButton: 'btn-confirmar-sobrio'
                },
                buttonsStyling: false,
                didOpen: () => {
                    // Asegurar que no hay botones extra
                    const actions = document.querySelector('.modal-sin-cambios .swal2-actions');
                    if (actions) {
                        const buttons = actions.querySelectorAll('button');
                        buttons.forEach(btn => {
                            if (!btn.classList.contains('swal2-confirm')) {
                                btn.remove();
                            }
                        });
                    }
                }
            });
            // El modal ya está cerrado, no hacer nada más
            return;
        }
        
        // Mostrar confirmación
        const confirmacion = await Swal.fire({
            title: '¿Guardar Cambios?',
            html: `
                <div style="padding: 25px 20px; text-align: center;">
                    <div style="background: linear-gradient(135deg, #f0f9ff, #e0f2fe); padding: 20px; border-radius: 12px; border-left: 4px solid #3b82f6; margin: 20px 0; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);">
                        <div style="color: #1e40af; font-weight: 700; margin-bottom: 15px; font-size: 16px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <iconify-icon icon="solar:pen-bold-duotone" style="font-size: 20px;"></iconify-icon>
                            Cambios a Guardar
                        </div>
                        <div style="color: #1e3a8a; font-size: 14px; line-height: 1.8; text-align: left; background: rgba(255, 255, 255, 0.7); padding: 15px; border-radius: 8px;">
                            <div style="margin-bottom: 8px;"><strong>•</strong> <span style="color: #334155; font-weight: 600;">Nombre:</span> <span style="color: #1e40af;">${datosFormulario.nombre}</span></div>
                            ${datosFormulario.marca ? `<div style="margin-bottom: 8px;"><strong>•</strong> <span style="color: #334155; font-weight: 600;">Marca:</span> <span style="color: #1e40af;">${datosFormulario.marca}</span></div>` : ''}
                            <div style="margin-bottom: 8px;"><strong>•</strong> <span style="color: #334155; font-weight: 600;">Stock actual:</span> <span style="color: #1e40af;">${datosFormulario.stock} unidades</span></div>
                            <div><strong>•</strong> <span style="color: #334155; font-weight: 600;">Stock mínimo:</span> <span style="color: #1e40af;">${datosFormulario.stock_minimo} unidades</span></div>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: false,
            confirmButtonText: 'Sí, Guardar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: true,
            customClass: {
                popup: 'modal-intercambio-sobrio modal-guardar-cambios',
                confirmButton: 'btn-confirmar-sobrio',
                cancelButton: 'btn-cancelar-sobrio-rojo'
            },
            buttonsStyling: false
        });
        
        if (!confirmacion.isConfirmed) {
            console.log('❌ Guardado cancelado por el usuario');
            // Si cancela, volver a mostrar el modal
            const modalEditar2 = document.getElementById('modalEditarProducto');
            if (modalEditar2) {
                modalEditar2.classList.remove('hidden');
                console.log('🔄 Modal reabierto porque se canceló');
            }
            return;
        }
        
        // Mostrar loading
        Swal.fire({
            title: 'Guardando Cambios...',
            html: `
                <div style="text-align: center; padding: 20px;">
                    <div style="color: #3b82f6; font-size: 48px; margin-bottom: 15px;">
                        <iconify-icon icon="solar:diskette-bold-duotone" class="rotating"></iconify-icon>
                    </div>
                    <div style="font-weight: 500; margin-bottom: 10px;">Actualizando producto...</div>
                    <div style="color: #6b7280; font-size: 0.9em;">Por favor espera un momento</div>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });
        
        try {
            // Preparar datos para envío
            const datosParaEnvio = {
                producto_id: this.datosOriginalesEditar.id,
                ubicacion_id: this.datosOriginalesEditar.ubicacionId,
                ...datosFormulario
            };
            
            console.log('📤 Enviando datos:', datosParaEnvio);
            
            // Enviar al backend
            const resultado = await this.actualizarProductoEnBaseDatos(datosParaEnvio);
            
            if (!resultado || resultado.success === false) {
                throw new Error(resultado?.message || 'Error al guardar cambios');
            }
            
            // Mostrar éxito
            await Swal.fire({
                title: '¡Cambios Guardados!',
                html: `
                    <div style="text-align: center; padding: 20px;">
                        <div style="color: #10b981; font-size: 64px; margin-bottom: 20px;">
                            <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                        </div>
                        <div style="font-weight: 600; color: #1f2937; margin-bottom: 15px; font-size: 1.1em;">
                            El producto se actualizó correctamente
                        </div>
                        <div style="background: #f0fdf4; padding: 15px; border-radius: 10px; border: 1px solid #bbf7d0;">
                            <div style="font-weight: 500; color: #15803d;">
                                <iconify-icon icon="solar:database-bold-duotone" style="margin-right: 8px;"></iconify-icon>
                                Cambios guardados en la base de datos
                            </div>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCancelButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                customClass: {
                    popup: 'modal-intercambio-sobrio'
                },
                timer: 2500,
                timerProgressBar: true
            });
            
            // Recargar la página después del éxito
            setTimeout(() => {
                window.location.reload();
            }, 2500); // Tiempo del timer del SweetAlert de éxito
            
        } catch (error) {
            console.error('💥 Error al guardar:', error);
            
            // Reabrir modal en caso de error
            const modalEditarErrorSave = document.getElementById('modalEditarProducto');
            if (modalEditarErrorSave) {
                modalEditarErrorSave.classList.remove('hidden');
                console.log('🔄 Modal reabierto debido a error de guardado');
            }
            
            await Swal.fire({
                title: 'Error al Guardar',
                text: error.message || 'No se pudieron guardar los cambios. Inténtalo nuevamente.',
                icon: 'error',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#ef4444'
            });
        }
    }
    
    validarDatosEdicion(datos) {
        const errores = [];
        
        if (!datos.nombre || datos.nombre.length < 2) {
            errores.push('El nombre debe tener al menos 2 caracteres');
        }
        
        if (datos.stock < 0) {
            errores.push('El stock no puede ser negativo');
        }
        
        if (datos.stock_minimo < 1) {
            errores.push('El stock mínimo debe ser al menos 1');
        }
        
        return errores;
    }
    
    verificarCambios(datosNuevos) {
        if (!this.datosOriginalesEditar) return true;
        
        const originales = this.datosOriginalesEditar;
        
        return (
            datosNuevos.nombre !== originales.nombre ||
            datosNuevos.marca !== (originales.marca || '') ||
            datosNuevos.stock !== parseInt(originales.stock) ||
            datosNuevos.stock_minimo !== 10 // Comparar con valor por defecto
        );
    }
    
    async actualizarProductoEnBaseDatos(datos) {
        console.log('💾 Actualizando producto en base de datos...');
        
        // Verificar token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            throw new Error('Token CSRF no encontrado');
        }
        
        // Ejecutar petición
        const response = await fetch('/api/ubicaciones/actualizar-producto', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(datos)
        });
        
        const result = await response.json();
        console.log('📊 Respuesta del servidor:', result);
        
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Error al actualizar producto');
        }
        
        console.log('✅ Producto actualizado exitosamente en BD');
        
        return {
            success: true,
            message: result.message,
            data: result.data
        };
    }

    abrirModalMover(slot) {
        const modal = document.getElementById('modalMoverProducto');
        if (!modal || !slot) return;
        
        console.log('🔄 Abriendo modal mover para slot:', slot.dataset.slot);
        modal.classList.remove('hidden');
    }

    // ===============================================
    // MODAL HANDLERS
    // ===============================================
    initModalHandlers() {
        // Cerrar modales al hacer click en el botón de cerrar o fuera del modal
        document.querySelectorAll('.modal-close-btn, .btn-cerrar-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal-overlay-estante');
                if (modal) {
                    modal.classList.add('hidden');
                }
            });
        });

        // Cerrar modales al hacer click fuera del contenido
        document.querySelectorAll('.modal-overlay-estante').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });
    }

    // ===============================================
    // EVENT BINDING
    // ===============================================
    bindEvents() {
        // Cerrar modales con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay-estante:not(.hidden)').forEach(modal => {
                    modal.classList.add('hidden');
                });
            }
        });

        // Botón agregar producto eliminado: agregamos por clic en slots vacíos

        // Botones de cancelar en modales
        document.querySelectorAll('.btn-modal-secondary').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal-overlay-estante');
                if (modal) modal.classList.add('hidden');
            });
        });

        // Botón guardar edición
        const btnGuardarEdicion = document.getElementById('btnGuardarEdicion');
        if (btnGuardarEdicion) {
            btnGuardarEdicion.addEventListener('click', (e) => {
                e.preventDefault();
                this.guardarEdicionProducto();
            });
        }
    }

    // ===============================================
    // TESTING
    // ===============================================
    runTests() {
        setTimeout(() => {
            console.log('%c🎯 DRAG AND DROP PROFESIONAL CARGADO', 'background: #10b981; color: white; padding: 8px 12px; border-radius: 6px; font-weight: bold;');
            console.log('💡 Instrucciones: Simplemente arrastra un producto hacia otro');
            console.log('🔧 Para testing avanzado: testDirectoProfesional()');
            
            const slotsOcupados = document.querySelectorAll('.slot-container.ocupado');
            console.log(`📦 Productos disponibles: ${slotsOcupados.length}`);
            
            if (slotsOcupados.length >= 2) {
                console.log('✅ Sistema listo para drag and drop');
            } else {
                console.log('⚠️  Agrega más productos para probar');
            }
        }, 1000);
    }

    testDragAndDrop() {
        const slotsOcupados = document.querySelectorAll('.slot-container.ocupado');
        console.log(`📦 Productos disponibles: ${slotsOcupados.length}`);
        
        if (slotsOcupados.length >= 2) {
            console.log('✅ Sistema listo para drag and drop');
        } else {
            console.log('⚠️  Agrega más productos para probar');
        }
    }
}

// ===============================================
// INICIALIZACIÓN AUTOMÁTICA
// ===============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistema de estante...');
    
    // Crear instancia global del manager
    window.estanteManager = new EstanteManager();
    window.estanteManager.init();
    
    console.log('✅ Sistema de estante completamente cargado');
});

// ===============================================
// EXPORTAR PARA USO GLOBAL
// ===============================================
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EstanteManager;
}