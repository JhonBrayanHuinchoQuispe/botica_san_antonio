// === FUNCIONALIDAD PRODUCTOS SIN UBICAR ===
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando productos sin ubicar...');
    
    // Referencias DOM
    const buscarInput = document.getElementById('buscarProductosSinUbicar');
    const filtroCategoria = document.getElementById('filtroCategoria');
    const filtroPrioridad = document.getElementById('filtroPrioridad');
    const tbody = document.getElementById('tablaProductosSinUbicarBody');
    const registrosPorPagina = document.getElementById('registrosPorPaginaSinUbicar');
    const btnAsignarMasivo = document.querySelector('.btn-asignar-masivo-modern');
    const btnVerResumen = document.querySelector('.btn-alerta-accion.primario');
    const btnOrdenarPrioridad = document.querySelector('.btn-alerta-accion.secundario');
    const alertaClose = document.querySelector('.alerta-close-btn');
    
    // Validar elementos esenciales
    if (!tbody) {
        console.error('❌ Tabla de productos sin ubicar no encontrada');
        return;
    }
    
    // Variables globales
    let productosOriginales = [];
    let productosFiltrados = [];
    
    // Inicializar
    inicializarEventListeners();
    
    // === INICIALIZACIÓN ===
    function inicializarEventListeners() {
        // Búsqueda
        if (buscarInput) {
            buscarInput.addEventListener('input', debounce(filtrarProductos, 300));
        }
        
        // Filtros
        if (filtroCategoria) {
            filtroCategoria.addEventListener('change', filtrarProductos);
        }
        
        if (filtroPrioridad) {
            filtroPrioridad.addEventListener('change', filtrarProductos);
        }
        
        // Registros por página
        if (registrosPorPagina) {
            registrosPorPagina.addEventListener('change', function() {
                const valor = parseInt(this.value);
                console.log(`📄 Cambiando a ${valor} registros por página`);
                filtrarProductos();
            });
        }
        
        // Asignación masiva
        if (btnAsignarMasivo) {
            btnAsignarMasivo.addEventListener('click', manejarAsignacionMasiva);
        }
        
        // Ver resumen
        if (btnVerResumen) {
            btnVerResumen.addEventListener('click', mostrarResumenProductos);
        }
        
        // Ordenar por prioridad
        if (btnOrdenarPrioridad) {
            btnOrdenarPrioridad.addEventListener('click', ordenarPorPrioridad);
        }
        
        // Cerrar alerta
        if (alertaClose) {
            alertaClose.addEventListener('click', function() {
                this.closest('.alerta-sin-ubicar-mejorada').style.display = 'none';
            });
        }
        
        // Checkbox "Seleccionar todos"
        const selectAllCheckbox = document.getElementById('selectAllSinUbicar');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = tbody.querySelectorAll('.producto-checkbox:not([disabled])');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                actualizarContadorSeleccionados();
            });
        }
        
        // Acciones en la tabla
        tbody.addEventListener('click', manejarClicksEnTabla);
        tbody.addEventListener('change', manejarCambiosEnCheckboxes);
        
        console.log('✅ Event listeners inicializados');
    }
    
    // === FILTRADO Y BÚSQUEDA ===
    function filtrarProductos() {
        const busqueda = buscarInput?.value.toLowerCase() || '';
        const categoriaSeleccionada = filtroCategoria?.value || '';
        const prioridadSeleccionada = filtroPrioridad?.value || '';
        
        console.log(`🔍 Filtrando: búsqueda="${busqueda}", categoría="${categoriaSeleccionada}", prioridad="${prioridadSeleccionada}"`);
        
        const filas = tbody.querySelectorAll('tr[data-prioridad]');
        let productosVisibles = 0;
        
        filas.forEach(fila => {
            const nombreProducto = fila.querySelector('.producto-info-simple strong')?.textContent.toLowerCase() || '';
            const categoriaTexto = fila.querySelector('.categoria-badge span')?.textContent.toLowerCase() || '';
            const prioridad = fila.getAttribute('data-prioridad') || '';
            
            const coincideBusqueda = !busqueda || nombreProducto.includes(busqueda);
            const coincideCategoria = !categoriaSeleccionada || categoriaTexto === categoriaSeleccionada.toLowerCase();
            const coincidePrioridad = !prioridadSeleccionada || prioridad === prioridadSeleccionada;
            
            const esVisible = coincideBusqueda && coincideCategoria && coincidePrioridad;
            
            if (esVisible) {
                fila.style.display = '';
                productosVisibles++;
            } else {
                fila.style.display = 'none';
            }
        });
        
        // Actualizar contadores
        actualizarContadores(productosVisibles);
        
        console.log(`📊 Productos visibles: ${productosVisibles}`);
    }
    
    function actualizarContadores(productosVisibles) {
        const rangoElement = document.getElementById('rangoSinUbicar');
        const totalElement = document.getElementById('totalSinUbicar');
        
        if (rangoElement && totalElement) {
            rangoElement.textContent = `1-${productosVisibles}`;
            totalElement.textContent = productosVisibles;
        }
    }
    
    // === MANEJO DE EVENTOS ===
    function manejarClicksEnTabla(e) {
        const target = e.target.closest('button');
        if (!target) return;
        
        if (target.classList.contains('asignar')) {
            const productoId = target.getAttribute('data-producto-id');
            const productoNombre = target.getAttribute('data-producto-nombre');
            const productoStock = target.getAttribute('data-producto-stock');
            
            console.log(`🎯 Asignar producto: ID=${productoId}, Nombre=${productoNombre}`);
            abrirModalAsignacion(productoId, productoNombre, productoStock);
        }
        
        if (target.classList.contains('ver')) {
            const productoId = target.getAttribute('data-producto-id');
            const productoNombre = target.getAttribute('data-producto-nombre');
            
            console.log(`👁️ Ver detalles: ID=${productoId}, Nombre=${productoNombre}`);
            mostrarDetallesProducto(productoId, productoNombre);
        }
    }
    
    function manejarCambiosEnCheckboxes(e) {
        if (e.target.classList.contains('producto-checkbox')) {
            actualizarContadorSeleccionados();
        }
    }
    
    function actualizarContadorSeleccionados() {
        const checkboxesSeleccionados = tbody.querySelectorAll('.producto-checkbox:checked');
        const cantidad = checkboxesSeleccionados.length;
        
        // Actualizar botón de asignación masiva
    if (btnAsignarMasivo) {
            if (cantidad > 0) {
                btnAsignarMasivo.disabled = false;
                btnAsignarMasivo.innerHTML = `
                    <iconify-icon icon="solar:widget-add-bold"></iconify-icon>
                    <span>Asignar ${cantidad} Producto${cantidad !== 1 ? 's' : ''}</span>
                `;
            } else {
                btnAsignarMasivo.disabled = true;
                btnAsignarMasivo.innerHTML = `
                    <iconify-icon icon="solar:widget-add-bold"></iconify-icon>
                    <span>Asignar Masivo</span>
                `;
            }
        }
        
        console.log(`✅ Productos seleccionados: ${cantidad}`);
    }
    
    // === ACCIONES ===
    function manejarAsignacionMasiva() {
            const checkboxesSeleccionados = tbody.querySelectorAll('.producto-checkbox:checked');
            
            if (checkboxesSeleccionados.length === 0) {
            mostrarToast('Debes seleccionar al menos un producto para asignar ubicación', 'warning');
                return;
            }
            
        console.log(`📦 Iniciando asignación masiva de ${checkboxesSeleccionados.length} productos`);
            abrirModalAsignacionMasiva(checkboxesSeleccionados.length);
    }
    
    function ordenarPorPrioridad() {
        console.log('📋 Ordenando productos por prioridad...');
        
        const filas = Array.from(tbody.querySelectorAll('tr[data-prioridad]'));
            const prioridadOrden = { 'alta': 1, 'media': 2, 'baja': 3 };
            
            filas.sort((a, b) => {
                const prioridadA = a.getAttribute('data-prioridad');
                const prioridadB = b.getAttribute('data-prioridad');
                return prioridadOrden[prioridadA] - prioridadOrden[prioridadB];
            });
            
        // Reorganizar filas
            filas.forEach(fila => tbody.appendChild(fila));
            
            mostrarToast('Productos organizados por prioridad (Alta → Media → Baja)', 'success');
    }
    
    function mostrarResumenProductos() {
        const totalProductos = tbody.querySelectorAll('tr[data-prioridad]').length;
        const productosAlta = tbody.querySelectorAll('tr[data-prioridad="alta"]').length;
        const productosMedia = tbody.querySelectorAll('tr[data-prioridad="media"]').length;
        const productosBaja = tbody.querySelectorAll('tr[data-prioridad="baja"]').length;
        
        Swal.fire({
            title: '📊 Resumen de Productos Sin Ubicar',
            html: `
                <div style="text-align: left; padding: 20px;">
                    <div style="margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <strong style="font-size: 18px; color: #1e40af;">Total de productos:</strong> 
                        <span style="font-size: 18px; color: #059669; font-weight: bold;">${totalProductos}</span>
                    </div>
                    
                    <div style="display: grid; gap: 12px; margin-bottom: 20px;">
                        <div style="padding: 12px; background: #fef2f2; border-radius: 6px; border-left: 3px solid #ef4444;">
                            <span style="color: #dc2626; font-weight: 600;">🔴 Prioridad Alta:</span> 
                            <span style="font-weight: bold; color: #991b1b;">${productosAlta} producto${productosAlta !== 1 ? 's' : ''}</span>
                        </div>
                        <div style="padding: 12px; background: #fffbeb; border-radius: 6px; border-left: 3px solid #f59e0b;">
                            <span style="color: #ea580c; font-weight: 600;">🟡 Prioridad Media:</span> 
                            <span style="font-weight: bold; color: #92400e;">${productosMedia} producto${productosMedia !== 1 ? 's' : ''}</span>
                        </div>
                        <div style="padding: 12px; background: #f0fdf4; border-radius: 6px; border-left: 3px solid #22c55e;">
                            <span style="color: #16a34a; font-weight: 600;">🟢 Prioridad Baja:</span> 
                            <span style="font-weight: bold; color: #166534;">${productosBaja} producto${productosBaja !== 1 ? 's' : ''}</span>
                        </div>
                    </div>
                    
                    <div style="padding: 15px; background: #fafafa; border-radius: 8px; border: 1px solid #e5e7eb;">
                        <small style="color: #6b7280; font-style: italic;">
                            💡 <strong>Información:</strong> La prioridad se basa en el tiempo que lleva el producto sin ubicar.
                        </small>
                    </div>
                </div>
            `,
            icon: 'info',
            confirmButtonText: '✅ Entendido',
            confirmButtonColor: '#3b82f6',
            buttonsStyling: true,
            customClass: {
                confirmButton: 'swal2-confirm-custom'
            },
            width: '500px',
            padding: '20px'
        });
    }
    
    // === MODALES ===
    async function abrirModalAsignacion(productoId, nombreProducto, stockProducto) {
        const modal = document.getElementById('modalAsignarUbicacion');
        if (!modal) {
            console.error('❌ Modal de asignación no encontrado');
            return;
        }
        
        // Actualizar información del producto
        const nombreElement = document.getElementById('nombreProductoAsignar');
        const stockElement = document.getElementById('stockProductoAsignar');
        const productoIdInput = document.getElementById('productoIdAsignar');
        
        if (nombreElement) {
            nombreElement.textContent = nombreProducto;
        }
        
        if (stockElement) {
            stockElement.textContent = `Stock: ${stockProducto} unidades`;
        }
        
        if (productoIdInput) {
            productoIdInput.value = productoId;
        }
        
        // Configurar selects
        await configurarSelectsAsignacion();
        
        // Mostrar modal
        modal.classList.remove('hidden');
        
        console.log(`📋 Modal de asignación abierto para: ${nombreProducto} (ID: ${productoId})`);
    }
    
    async function configurarSelectsAsignacion() {
        const estanteSelect = document.getElementById('estanteAsignar');
        const slotSelect = document.getElementById('slotAsignar');
        const ubicacionIdInput = document.getElementById('ubicacionIdAsignar');
        
        if (!estanteSelect || !slotSelect) return;
        
        // Limpiar selects
        estanteSelect.innerHTML = '<option value="">Seleccionar estante...</option>';
        slotSelect.innerHTML = '<option value="">Primero selecciona un estante</option>';
        slotSelect.disabled = true;
        if (ubicacionIdInput) ubicacionIdInput.value = '';
        
        // Cargar estantes
        try {
            const response = await fetch('/api/ubicaciones/estantes');
            const data = await response.json();
            
            if (data.success) {
                data.data.forEach(estante => {
                    const option = document.createElement('option');
                    option.value = estante.id;
                    option.textContent = estante.nombre;
                    estanteSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error al cargar estantes:', error);
        }
        
        // Event listener para estante
        estanteSelect.addEventListener('change', async function() {
            const estanteId = this.value;
            slotSelect.innerHTML = '<option value="">Cargando ubicaciones...</option>';
            slotSelect.disabled = true;
            if (ubicacionIdInput) ubicacionIdInput.value = '';
            
            if (estanteId) {
                try {
                    const response = await fetch(`/api/ubicaciones/estante/${estanteId}/ubicaciones-libres`);
                    const data = await response.json();
                    
                    slotSelect.innerHTML = '<option value="">Seleccionar ubicación...</option>';
                    
                    if (data.success && data.data.length > 0) {
                        data.data.forEach(ubicacion => {
                            const option = document.createElement('option');
                            option.value = ubicacion.id;
                            option.textContent = `Nivel ${ubicacion.nivel} - Posición ${ubicacion.posicion} (${ubicacion.codigo})`;
                            slotSelect.appendChild(option);
                        });
                        slotSelect.disabled = false;
                    } else {
                        slotSelect.innerHTML = '<option value="">No hay ubicaciones disponibles</option>';
                    }
                } catch (error) {
                    console.error('Error al cargar ubicaciones:', error);
                    slotSelect.innerHTML = '<option value="">Error al cargar ubicaciones</option>';
                }
            } else {
                slotSelect.innerHTML = '<option value="">Primero selecciona un estante</option>';
            }
        });
        
        // Event listener para slot
        slotSelect.addEventListener('change', function() {
            if (ubicacionIdInput) {
                ubicacionIdInput.value = this.value;
            }
        });
    }
    
    function mostrarDetallesProducto(productoId, nombreProducto) {
        // Implementar modal de detalles
        console.log(`📄 Mostrar detalles del producto: ${nombreProducto}`);
        
        Swal.fire({
            title: `📦 ${nombreProducto}`,
            html: `
                <div style="text-align: left;">
                    <p><strong>ID:</strong> ${productoId}</p>
                    <p><strong>Estado:</strong> <span style="color: #dc2626;">Sin ubicación asignada</span></p>
                    <p><strong>Acción requerida:</strong> Asignar ubicación en el almacén</p>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Asignar Ubicación',
            cancelButtonText: 'Cerrar',
            confirmButtonColor: '#3b82f6'
        }).then((result) => {
            if (result.isConfirmed) {
                abrirModalAsignacion(productoId, nombreProducto, 0);
            }
        });
    }
    
    // === UTILIDADES ===
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    function mostrarToast(mensaje, tipo = 'info') {
        // Usar SweetAlert2 para toasts
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: tipo,
            title: mensaje
        });
    }
    
    console.log('✅ Productos sin ubicar inicializados correctamente');
});

// === FUNCIONES GLOBALES ===

// Cerrar modal de asignación
function cerrarModalAsignacion() {
    const modal = document.getElementById('modalAsignarUbicacion');
    if (modal) {
        modal.classList.add('hidden');
        
        // Limpiar formulario
        const form = document.getElementById('formUbicarProducto');
        if (form) {
            form.reset();
        }
        
        const estanteSelect = document.getElementById('estanteAsignar');
        const slotSelect = document.getElementById('slotAsignar');
        const productoIdInput = document.getElementById('productoIdAsignar');
        const ubicacionIdInput = document.getElementById('ubicacionIdAsignar');
        
        if (estanteSelect) estanteSelect.value = '';
        if (slotSelect) {
            slotSelect.value = '';
            slotSelect.innerHTML = '<option value="">Primero selecciona un estante</option>';
            slotSelect.disabled = true;
        }
        if (productoIdInput) productoIdInput.value = '';
        if (ubicacionIdInput) ubicacionIdInput.value = '';
        
        // Restaurar texto de elementos
        const nombreElement = document.getElementById('nombreProductoAsignar');
        const stockElement = document.getElementById('stockProductoAsignar');
        if (nombreElement) nombreElement.textContent = 'Selecciona un producto';
        if (stockElement) stockElement.textContent = 'Stock: 0 unidades';
    }
}

// Función para confirmar asignación individual
function confirmarAsignacion() {
    console.log('🚀 INICIANDO CONFIRMACIÓN DE ASIGNACIÓN');
    
    const form = document.getElementById('formUbicarProducto');
    if (!form) {
        console.error('❌ Formulario no encontrado');
        return;
    }
    
    const formData = new FormData(form);
    
    // Validar campos requeridos
    const productoId = formData.get('producto_id');
    const ubicacionId = formData.get('ubicacion_id');
    const cantidad = formData.get('cantidad') || '1';
    
    if (!productoId || !ubicacionId) {
        Swal.fire({
            title: '⚠️ Campos Requeridos',
            text: 'Por favor selecciona un estante y una ubicación específica',
            icon: 'warning',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    // Debug: Mostrar datos del formulario
    console.log('📋 Datos del formulario:');
    console.log(`  producto_id: ${productoId}`);
    console.log(`  ubicacion_id: ${ubicacionId}`);
    console.log(`  cantidad: ${cantidad}`);
    
    // Convertir FormData a objeto para JSON
    const jsonData = {};
    formData.forEach((value, key) => {
        jsonData[key] = value;
    });
    
    console.log('📦 JSON a enviar:', jsonData);
    
    // Mostrar loading
    const modal = document.getElementById('modalAsignarUbicacion');
    const submitBtn = modal.querySelector('.btn-primary');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Guardando...';
    submitBtn.disabled = true;
    
    // USAR LA NUEVA RUTA ULTRA-SIMPLE
    fetch('/api/ubicar-simple', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': jsonData._token,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(jsonData)
    })
    .then(response => {
        console.log('📨 Response status:', response.status);
        console.log('📨 Response headers:', [...response.headers.entries()]);
        return response.text(); // Obtener como texto primero
    })
    .then(textResponse => {
        console.log('📝 Response como texto:', textResponse);
        
        try {
            const data = JSON.parse(textResponse);
            console.log('✅ Response JSON parseado:', data);
            
            if (data.success) {
                console.log('🎉 ÉXITO EN LA ASIGNACIÓN!');
                console.log('📊 Datos devueltos:', data.data);
                
                // Cerrar modal
                cerrarModalAsignacion();
                
                // ELIMINAR LA FILA DE LA TABLA
                const productoId = jsonData.producto_id;
                const fila = document.querySelector(`tr[data-producto-id="${productoId}"]`);
                if (fila) {
                    console.log('🗑️ Eliminando fila del producto:', productoId);
                    fila.style.animation = 'fadeOut 0.5s ease-out forwards';
                    setTimeout(() => {
                        fila.remove();
                        // Actualizar contador
                        actualizarContadores();
                        console.log('✅ Fila eliminada y contadores actualizados');
                    }, 500);
                } else {
                    // Si no se encuentra la fila específica, recargar la tabla
                    console.log('🔄 Fila no encontrada, recargando tabla...');
                    window.location.reload();
                }
                
                // Mostrar mensaje de éxito
                Swal.fire({
                    title: '¡Producto Ubicado!',
                    text: `${data.data.producto_nombre} fue ubicado exitosamente en ${data.data.ubicacion_codigo}`,
                    icon: 'success',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
                
            } else {
                console.error('❌ Error en respuesta:', data.message);
                mostrarError('Error al ubicar producto', data.message, data);
            }
            
        } catch (parseError) {
            console.error('❌ Error parseando JSON:', parseError);
            console.error('📝 Texto recibido:', textResponse);
            mostrarError('Error de formato', 'La respuesta del servidor no es válida', { textResponse, parseError: parseError.message });
        }
    })
    .catch(error => {
        console.error('❌ Error de red:', error);
        mostrarError('Error de conexión', 'No se pudo conectar con el servidor', { error: error.message });
    })
    .finally(() => {
        // Restaurar botón
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Modal de asignación masiva
async function abrirModalAsignacionMasiva(cantidad) {
    const checkboxesSeleccionados = document.querySelectorAll('#tablaProductosSinUbicarBody .producto-checkbox:checked');
    const productosSeleccionados = Array.from(checkboxesSeleccionados).map(checkbox => {
        const fila = checkbox.closest('tr');
        const nombre = fila.querySelector('.producto-info-simple strong').textContent;
        const categoria = fila.querySelector('.categoria-badge span').textContent;
        const id = checkbox.getAttribute('data-producto-id');
        return { id, nombre, categoria };
    });

    let opcionesEstantes = '<option value="">Seleccionar estante...</option>';
    
    // Cargar estantes disponibles
    try {
        const response = await fetch('/api/ubicaciones/estantes');
        const data = await response.json();
        if (data.success) {
            data.data.forEach(estante => {
                opcionesEstantes += `<option value="${estante.id}">${estante.nombre}</option>`;
            });
        }
    } catch (error) {
        console.error('Error al cargar estantes:', error);
    }

    const productosHtml = productosSeleccionados.map((producto, index) => `
        <div style="padding: 8px; margin: 4px 0; background: #f8fafc; border-radius: 6px; border-left: 3px solid #3b82f6;">
            <strong>${index + 1}. ${producto.nombre}</strong>
            <span style="color: #6b7280; font-size: 12px; margin-left: 8px;">(${producto.categoria})</span>
            </div>
    `).join('');

    const { value: estrategia } = await Swal.fire({
        title: '📦 Asignación Masiva de Ubicaciones',
        html: `
            <div style="text-align: left; padding: 20px;">
                <div style="margin-bottom: 20px; padding: 15px; background: #eff6ff; border-radius: 8px; border-left: 4px solid #3b82f6;">
                    <h4 style="margin: 0 0 10px 0; color: #1e40af;">Productos Seleccionados (${cantidad})</h4>
                    <div style="max-height: 150px; overflow-y: auto;">
                        ${productosHtml}
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                        📍 Seleccionar Estante Destino:
                    </label>
                    <select id="estanteMasivo" style="width: 100%; padding: 10px; border: 2px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                        ${opcionesEstantes}
                    </select>
                </div>
                
                <div style="padding: 15px; background: #fef3c7; border-radius: 8px; border-left: 3px solid #f59e0b;">
                    <small style="color: #92400e;">
                        ⚠️ <strong>Importante:</strong> Se asignarán automáticamente a las primeras ubicaciones disponibles del estante seleccionado.
                    </small>
            </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '🚀 Asignar Todos',
        cancelButtonText: '❌ Cancelar',
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        customClass: {
            confirmButton: 'swal2-confirm-custom'
        },
        width: '600px',
        preConfirm: () => {
            const estanteSeleccionado = document.getElementById('estanteMasivo').value;
            if (!estanteSeleccionado) {
                Swal.showValidationMessage('Debes seleccionar un estante');
                return false;
            }
            return estanteSeleccionado;
        }
    });

    if (estrategia) {
        await ejecutarAsignacionMasiva(productosSeleccionados, estrategia);
    }
}

// Ejecutar asignación masiva
async function ejecutarAsignacionMasiva(productos, estanteId) {
    // Mostrar loading
    Swal.fire({
        title: '🔄 Asignando Productos...',
        html: `
            <div style="text-align: center; padding: 20px;">
                <div style="margin-bottom: 20px;">
                    <div style="font-size: 48px; margin-bottom: 16px;">📦</div>
                    <p>Asignando <strong>${productos.length} productos</strong> al estante seleccionado...</p>
                </div>
                <div style="margin-bottom: 20px;">
                    <div id="progreso-asignacion" style="width: 100%; background: #f3f4f6; border-radius: 10px; overflow: hidden;">
                        <div id="barra-progreso" style="width: 0%; height: 8px; background: linear-gradient(90deg, #3b82f6, #1d4ed8); transition: width 0.3s ease;"></div>
                </div>
                    <p id="producto-actual" style="margin-top: 10px; color: #6b7280; font-size: 14px;">Iniciando...</p>
                </div>
                </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let exitosos = 0;
    let fallidos = 0;
    const resultados = [];

    // Obtener ubicaciones libres del estante
    try {
        const response = await fetch(`/api/ubicaciones/estante/${estanteId}/ubicaciones-libres`);
        const data = await response.json();
        
        if (!data.success || data.data.length === 0) {
            throw new Error('No hay ubicaciones disponibles en el estante seleccionado');
        }

        const ubicacionesLibres = data.data;
        
        for (let i = 0; i < productos.length && i < ubicacionesLibres.length; i++) {
            const producto = productos[i];
            const ubicacion = ubicacionesLibres[i];
            
            // Actualizar progreso
            const progreso = ((i + 1) / productos.length) * 100;
            document.getElementById('barra-progreso').style.width = `${progreso}%`;
            document.getElementById('producto-actual').textContent = `Asignando: ${producto.nombre}...`;
            
            try {
                const asignacionResponse = await fetch('/api/ubicaciones/ubicar-producto', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        producto_id: producto.id,
                        ubicacion_id: ubicacion.id,
                        cantidad: 1
                    })
                });

                const resultado = await asignacionResponse.json();
                
                if (resultado.success) {
                    exitosos++;
                    resultados.push({ producto: producto.nombre, estado: 'success', ubicacion: ubicacion.codigo });
                } else {
                    fallidos++;
                    resultados.push({ producto: producto.nombre, estado: 'error', error: resultado.message });
                }
            } catch (error) {
                fallidos++;
                resultados.push({ producto: producto.nombre, estado: 'error', error: 'Error de conexión' });
            }
        }

        // Mostrar resultado final
        mostrarResultadoAsignacionMasiva(exitosos, fallidos, resultados);
        
    } catch (error) {
        Swal.fire({
            title: '❌ Error',
            text: error.message || 'Error al obtener ubicaciones del estante',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
    }
}

// Mostrar resultado de asignación masiva
function mostrarResultadoAsignacionMasiva(exitosos, fallidos, resultados) {
    const resultadosHtml = resultados.map(resultado => {
        if (resultado.estado === 'success') {
            return `<div style="padding: 8px; margin: 4px 0; background: #f0fdf4; border-radius: 6px; border-left: 3px solid #22c55e; color: #166534;">
                ✅ <strong>${resultado.producto}</strong> → ${resultado.ubicacion}
            </div>`;
        } else {
            return `<div style="padding: 8px; margin: 4px 0; background: #fef2f2; border-radius: 6px; border-left: 3px solid #ef4444; color: #991b1b;">
                ❌ <strong>${resultado.producto}</strong> → ${resultado.error}
            </div>`;
        }
    }).join('');

    Swal.fire({
        title: exitosos > 0 ? '🎉 Asignación Completada' : '❌ Error en Asignación',
        html: `
            <div style="text-align: left; padding: 20px;">
                <div style="margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div style="padding: 15px; background: #f0fdf4; border-radius: 8px; text-align: center; border: 2px solid #22c55e;">
                        <div style="font-size: 24px; font-weight: bold; color: #166534;">${exitosos}</div>
                        <div style="color: #166534; font-size: 14px;">Exitosos</div>
                    </div>
                    <div style="padding: 15px; background: #fef2f2; border-radius: 8px; text-align: center; border: 2px solid #ef4444;">
                        <div style="font-size: 24px; font-weight: bold; color: #991b1b;">${fallidos}</div>
                        <div style="color: #991b1b; font-size: 14px;">Fallidos</div>
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <h4 style="margin: 0 0 10px 0; color: #374151;">📋 Detalle de Resultados:</h4>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px;">
                        ${resultadosHtml}
                    </div>
                </div>
            </div>
        `,
        icon: exitosos > 0 ? 'success' : 'error',
        confirmButtonText: '✅ Entendido',
        confirmButtonColor: '#3b82f6',
        customClass: {
            confirmButton: 'swal2-confirm-custom'
        },
        width: '600px'
    }).then(() => {
        // Recargar la página para actualizar la tabla
        window.location.reload();
    });
}

// Función para mostrar errores de forma consistente
function mostrarError(titulo, mensaje, datos = null) {
    console.error(`❌ ${titulo}:`, mensaje, datos);
    
    let detalles = '';
    if (datos) {
        detalles = `
            <details style="margin-top: 10px; font-size: 12px;">
                <summary>Información técnica</summary>
                <pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; white-space: pre-wrap; max-height: 200px; overflow-y: auto;">${JSON.stringify(datos, null, 2)}</pre>
            </details>
        `;
    }
    
    Swal.fire({
        title: titulo,
        html: `
            <div style="text-align: left;">
                <p><strong>${mensaje}</strong></p>
                ${detalles}
            </div>
        `,
        icon: 'error',
        confirmButtonText: 'Entendido',
        width: '600px'
    });
}

// Función para actualizar contadores
function actualizarContadores() {
    const filas = document.querySelectorAll('#tablaProductos tbody tr');
    const totalProductos = filas.length;
    
    // Contar por prioridades
    let altaPrioridad = 0;
    let mediaPrioridad = 0;
    let bajaPrioridad = 0;
    
    filas.forEach(fila => {
        const prioridad = fila.querySelector('.badge')?.textContent?.trim();
        if (prioridad === 'ALTA') altaPrioridad++;
        else if (prioridad === 'MEDIA') mediaPrioridad++;
        else if (prioridad === 'BAJA') bajaPrioridad++;
    });
    
    // Actualizar texto del alert
    const alertText = document.querySelector('.alert-message .fw-semibold');
    if (alertText) {
        alertText.textContent = `${totalProductos}`;
    }
    
    // Si no hay más productos, mostrar mensaje de éxito completo
    if (totalProductos === 0) {
        const containerAlert = document.querySelector('.alert');
        if (containerAlert) {
            containerAlert.innerHTML = `
                <div class="alert-icon">
                    <i class="ri-checkbox-circle-line" style="font-size: 1.5rem; color: #10b981;"></i>
                    </div>
                <div class="alert-content">
                    <div class="alert-message">
                        <span class="fw-semibold text-success">¡Excelente! Todos los productos están ubicados correctamente</span>
            </div>
        </div>
    `;
        }
        
        // Ocultar tabla
        const tablaContainer = document.querySelector('.table-responsive');
        if (tablaContainer) {
            tablaContainer.style.display = 'none';
        }
    }
}

// Agregar CSS para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        0% {
            opacity: 1;
            transform: translateX(0);
        }
        100% {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
document.head.appendChild(style);