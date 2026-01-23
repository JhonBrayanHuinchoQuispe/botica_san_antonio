console.log('✅ Devoluciones - JavaScript cargado');

// Variable global para controlar el procesamiento
let procesandoDevolucion = false;

// Overlay de carga reutilizable (como Presentación/Categoría)
function showLoading(label = 'Cargando datos...') {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        const textEl = overlay.querySelector('.loading-text');
        if (textEl) textEl.textContent = label;
    }
}
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.style.display = 'none';
}

// Configurar eventos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('📋 Módulo de devoluciones iniciado');
    
    // Configurar evento del botón buscar venta
    const btnBuscarVenta = document.getElementById('buscarVenta');
    if (btnBuscarVenta) {
        btnBuscarVenta.addEventListener('click', buscarVenta);
    }
    
    // Configurar evento del formulario de devolución
    const formDevolucion = document.getElementById('devolucionForm');
    if (formDevolucion) {
        formDevolucion.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevenir envío normal del formulario
            procesarDevolucion();
        });
    }
    
    // Configurar evento del botón procesar devolución (fallback)
    const btnProcesarDevolucion = document.getElementById('procesarDevolucionBtn');
    if (btnProcesarDevolucion) {
        // Estado inicial: deshabilitado hasta que el formulario esté completo
        btnProcesarDevolucion.disabled = true;
        btnProcesarDevolucion.style.opacity = '0.7';
        btnProcesarDevolucion.style.cursor = 'not-allowed';
        btnProcesarDevolucion.addEventListener('click', function(e) {
            e.preventDefault();
            // No permitir click si está deshabilitado
            if (btnProcesarDevolucion.disabled) {
                return;
            }
            procesarDevolucion();
        });
    }
    
    // Configurar eventos de checkboxes de productos
    configurarEventosProductos();
    
    // Configurar eventos de cantidad
    configurarEventosCantidad();

    // Configurar eventos de motivo
    document.querySelectorAll('.motivo-select').forEach(select => {
        select.addEventListener('change', actualizarEstadoBotonProcesar);
    });

    // Evaluar estado inicial del botón
    actualizarEstadoBotonProcesar();
});

// Buscar venta por número vía AJAX
function buscarVenta() {
    const numeroVenta = document.getElementById('numeroVenta').value.trim();
    
    if (!numeroVenta) {
        Swal.fire({
            title: '⚠️ Número requerido',
            text: 'Ingresa el número de venta a buscar',
            icon: 'warning',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    // Mostrar overlay profesional
    showLoading('Buscando venta...');
    
    // Petición AJAX
    fetch(`${window.location.pathname}?numero_venta=${numeroVenta}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        hideLoading();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newContent = doc.getElementById('devolucionContentArea');
        const currentContent = document.getElementById('devolucionContentArea');
        
        if (newContent && currentContent) {
            currentContent.innerHTML = newContent.innerHTML;
            
            // Re-configurar eventos para los nuevos elementos
            configurarEventosProductos();
            configurarEventosCantidad();
            
            // Re-configurar formulario si existe
            const formDevolucion = document.getElementById('devolucionForm');
            if (formDevolucion) {
                formDevolucion.addEventListener('submit', function(e) {
                    e.preventDefault();
                    procesarDevolucion();
                });
            }

            // Configurar evento de motivo
            document.querySelectorAll('.motivo-select').forEach(select => {
                select.addEventListener('change', actualizarEstadoBotonProcesar);
            });

            // Evaluar estado inicial del botón
            actualizarEstadoBotonProcesar();

            // Actualizar URL sin recargar
            window.history.pushState({}, '', `${window.location.pathname}?numero_venta=${numeroVenta}`);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error al buscar venta:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo buscar la venta. Intente nuevamente.',
            confirmButtonColor: '#dc2626'
        });
    });
}

// Configurar eventos de productos
function configurarEventosProductos() {
    const checkboxes = document.querySelectorAll('.producto-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('.producto-row');
            const cantidadInput = row.querySelector('.cantidad-devolver');
            const motivoSelect = row.querySelector('.motivo-select');
            // Seguridad: algunas filas pueden estar totalmente devueltas y no tener inputs
            if (!cantidadInput || !motivoSelect) {
                console.warn('⚠️ Inputs de devolución no encontrados para la fila seleccionada. Probablemente ya está completamente devuelta.');
                actualizarEstadoBotonProcesar();
                return; // Evitar errores de null
            }
            
            if (this.checked) {
                cantidadInput.disabled = false;
                motivoSelect.disabled = false;
                cantidadInput.focus();
            } else {
                cantidadInput.disabled = true;
                motivoSelect.disabled = true;
                cantidadInput.value = '';
                motivoSelect.value = '';
            }

            actualizarEstadoBotonProcesar();
        });
    });
}

// Configurar eventos de cantidad
function configurarEventosCantidad() {
    const cantidadInputs = document.querySelectorAll('.cantidad-devolver');
    cantidadInputs.forEach(input => {
        input.addEventListener('input', function() {
            const maxCantidad = parseInt(this.getAttribute('max'));
            const cantidad = parseInt(this.value);
            
            if (cantidad > maxCantidad) {
                this.value = maxCantidad;
                
                Swal.fire({
                    title: '⚠️ Cantidad excedida',
                    text: `No puedes devolver más de ${maxCantidad} unidades`,
                    icon: 'warning',
                    timer: 2000,
                    showConfirmButton: false
                });
            }

            actualizarEstadoBotonProcesar();
        });
    });
}

// Habilitar/deshabilitar botón "Procesar Devolución" según validez
function actualizarEstadoBotonProcesar() {
    const btn = document.getElementById('procesarDevolucionBtn');
    if (!btn) return;

    const checkboxes = Array.from(document.querySelectorAll('.producto-checkbox:checked'));
    let valido = checkboxes.length > 0;

    // Verificar cada producto seleccionado
    if (valido) {
        for (const checkbox of checkboxes) {
            const row = checkbox.closest('.producto-row');
            const cantidadInput = row?.querySelector('.cantidad-devolver');
            const motivoSelect = row?.querySelector('.motivo-select');
            
            if (!cantidadInput || cantidadInput.disabled) { valido = false; break; }
            const cantidad = parseInt(cantidadInput.value);
            const max = parseInt(cantidadInput.getAttribute('max'));
            if (!cantidad || cantidad < 1 || (max && cantidad > max)) { valido = false; break; }

            if (!motivoSelect || motivoSelect.disabled) { valido = false; break; }
            const motivoVal = (motivoSelect.value || '').trim();
            if (!motivoVal || motivoVal === 'Seleccionar...' || motivoVal === 'seleccionar') { valido = false; break; }
        }
    }

    // Aplicar estado
    btn.disabled = !valido;
    if (btn.disabled) {
        btn.style.opacity = '0.7';
        btn.style.cursor = 'not-allowed';
    } else {
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }
}

// Procesar devolución
function procesarDevolucion() {
    console.log('🚀 Iniciando proceso de devolución...');
    
    if (procesandoDevolucion) {
        console.log('⚠️ Ya hay una devolución en proceso, ignorando...');
        return;
    }
    
    // Validar formulario con nueva función mejorada
    if (!validarFormularioDevolucion()) {
        return;
    }
    
    // Recopilar datos de productos seleccionados
    const productosParaDevolver = [];
    const checkboxes = document.querySelectorAll('.producto-checkbox:checked');
    
    console.log('🔍 Debug - Checkboxes seleccionados:', checkboxes.length);
    
    checkboxes.forEach((checkbox, index) => {
        const row = checkbox.closest('.producto-row');
        const detalleIdInput = row.querySelector('input[name*="detalle_id"]');
        const cantidadInput = row.querySelector('.cantidad-devolver');
        const motivoSelect = row.querySelector('.motivo-select');
        
        console.log(`🔍 Debug - Producto ${index + 1}:`, {
            detalleIdInput: detalleIdInput ? detalleIdInput.value : 'NO ENCONTRADO',
            cantidadInput: cantidadInput ? {
                value: cantidadInput.value,
                type: cantidadInput.type,
                disabled: cantidadInput.disabled,
                max: cantidadInput.getAttribute('max')
            } : 'NO ENCONTRADO',
            motivoSelect: motivoSelect ? {
                value: motivoSelect.value,
                disabled: motivoSelect.disabled
            } : 'NO ENCONTRADO'
        });
        
        // Validar que todos los elementos existen
        if (!detalleIdInput) {
            console.error(`❌ No se encontró input detalle_id para producto ${index + 1}`);
            return;
        }
        
        if (!cantidadInput) {
            console.error(`❌ No se encontró input cantidad para producto ${index + 1}`);
            return;
        }
        
        if (!motivoSelect) {
            console.error(`❌ No se encontró select motivo para producto ${index + 1}`);
            return;
        }
        
        const detalleId = detalleIdInput.value;
        
        // Solo procesar productos que tienen inputs válidos (no completamente devueltos)
        if (cantidadInput && cantidadInput.type !== 'hidden' && !cantidadInput.disabled) {
            const cantidad = cantidadInput.value;
            const motivo = motivoSelect.value;
            
            console.log(`🔍 Validando producto ${index + 1}:`, {
                detalleId: detalleId,
                cantidad: cantidad,
                motivo: motivo,
                cantidadValida: cantidad && cantidad > 0,
                motivoValido: motivo && motivo !== ''
            });
            
            if (cantidad && cantidad > 0 && motivo && motivo !== '') {
                const productoData = {
                    detalle_id: parseInt(detalleId),
                    cantidad_devolver: parseInt(cantidad),
                    motivo: motivo,
                    observaciones: null // Se puede agregar campo de observaciones en el futuro
                };
                
                console.log(`✅ Producto ${index + 1} agregado:`, productoData);
                productosParaDevolver.push(productoData);
            } else {
                console.warn(`⚠️ Producto ${index + 1} no cumple validaciones:`, {
                    cantidad: cantidad,
                    motivo: motivo,
                    cantidadValida: cantidad && cantidad > 0,
                    motivoValido: motivo && motivo !== ''
                });
            }
        } else {
            console.log(`ℹ️ Producto ${index + 1} saltado (input oculto o deshabilitado)`);
        }
    });
    
    console.log('📦 Productos a devolver validados:', productosParaDevolver);
    
    if (productosParaDevolver.length === 0) {
        Swal.fire({
            title: '⚠️ Sin productos válidos',
            text: 'No hay productos válidos para procesar la devolución',
            icon: 'warning',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    // Mostrar confirmación
    mostrarConfirmacionDevolucion(productosParaDevolver);
}

// Mostrar confirmación de devolución
function mostrarConfirmacionDevolucion(productos) {
    console.log('💭 Mostrando confirmación de devolución:', productos);
    
    Swal.fire({
        title: '<i class="material-icons" style="color: #f59e0b; font-size: 2rem; margin-bottom: 0.5rem;">help_outline</i><br>Confirmar Devolución',
        html: `
            <div style="text-align: left; background: #f8fafc; padding: 1.5rem; border-radius: 12px; margin: 1rem 0;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: #374151;">
                    <i class="material-icons" style="color: #3b82f6;">inventory_2</i>
                    <strong>Productos a devolver: ${productos.length}</strong>
                </div>
                <div style="background: white; border-radius: 8px; padding: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    ${productos.map(p => `
                        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb;">
                            <i class="material-icons" style="color: #6b7280; font-size: 1.1rem;">medication</i>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #374151;">Cantidad: ${p.cantidad_devolver}</div>
                                <div style="font-size: 0.875rem; color: #6b7280;">Motivo: ${p.motivo}</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; padding: 0.75rem; background: #fef3c7; border-radius: 8px; color: #92400e;">
                    <i class="material-icons" style="font-size: 1.1rem;">info</i>
                    <span style="font-size: 0.875rem;">Esta acción actualizará el stock y los totales de la venta</span>
                </div>
            </div>
        `,
        icon: null,
        showCancelButton: true,
        showDenyButton: false,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        didOpen: () => {
            // Asegurar que no haya botón intermedio "No"
            const denyBtn = Swal.getDenyButton();
            if (denyBtn) denyBtn.remove();
            // Estilos visibles para los botones
            const c = Swal.getConfirmButton();
            const k = Swal.getCancelButton();
            if (c) {
                c.style.opacity = '1';
                c.style.visibility = 'visible';
                c.style.backgroundColor = '#dc2626';
                c.style.color = '#fff';
                c.style.border = 'none';
                c.style.borderRadius = '8px';
                c.style.padding = '0.625rem 1rem';
                c.style.fontWeight = '600';
                c.style.boxShadow = 'none';
                c.style.cursor = 'pointer';
                c.style.marginRight = '0.75rem';
            }
            if (k) {
                k.style.opacity = '1';
                k.style.visibility = 'visible';
                k.style.backgroundColor = '#6b7280';
                k.style.color = '#fff';
                k.style.border = 'none';
                k.style.borderRadius = '8px';
                k.style.padding = '0.625rem 1rem';
                k.style.fontWeight = '600';
                k.style.boxShadow = 'none';
                k.style.cursor = 'pointer';
                k.style.marginLeft = '0.75rem';
            }
            const actions = Swal.getActions();
            if (actions) {
                actions.style.gap = '0.75rem';
                actions.style.marginTop = '1rem';
            }
        },
        customClass: {
            popup: 'swal-custom-popup',
            confirmButton: 'swal-custom-confirm',
            cancelButton: 'swal-custom-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            enviarDevolucion(productos);
        }
    });
}

// Enviar devolución al servidor
function enviarDevolucion(productos) {
    procesandoDevolucion = true;
    
    // Mostrar overlay profesional
    showLoading('Procesando devolución...');
    
    // Preparar datos
    const ventaIdInput = document.querySelector('input[name="venta_id"]');
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    
    console.log('🔍 Debug - Elementos del formulario:', {
        ventaIdInput: ventaIdInput ? ventaIdInput.value : 'NO ENCONTRADO',
        csrfTokenMeta: csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : 'NO ENCONTRADO'
    });
    
    if (!ventaIdInput) {
        console.error('❌ No se encontró input venta_id');
        hideLoading();
        Swal.fire({
            title: '❌ Error de Formulario',
            text: 'No se encontró el ID de la venta. Recarga la página e intenta nuevamente.',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
        procesandoDevolucion = false;
        return;
    }
    
    if (!csrfTokenMeta) {
        console.error('❌ No se encontró CSRF token');
        hideLoading();
        Swal.fire({
            title: '❌ Error de Seguridad',
            text: 'No se encontró el token de seguridad. Recarga la página e intenta nuevamente.',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
        procesandoDevolucion = false;
        return;
    }
    
    const ventaId = ventaIdInput.value;
    const csrfToken = csrfTokenMeta.getAttribute('content');
    
    // Validar que venta_id es un número válido
    if (!ventaId || isNaN(parseInt(ventaId))) {
        console.error('❌ ID de venta inválido:', ventaId);
        Swal.fire({
            title: '❌ Error de Datos',
            text: 'El ID de la venta no es válido. Recarga la página e intenta nuevamente.',
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
        procesandoDevolucion = false;
        return;
    }
    
    const data = {
        venta_id: parseInt(ventaId),
        productos: productos,
        _token: csrfToken
    };
    
    console.log('📤 Enviando datos completos:', data);
    console.log('🔗 URL:', '/ventas/procesar-devolucion');
    console.log('🔑 CSRF Token:', csrfToken);
    console.log('🔍 Validación final de datos:', {
        venta_id_tipo: typeof data.venta_id,
        venta_id_valor: data.venta_id,
        productos_cantidad: data.productos.length,
        productos_estructura: data.productos.map(p => ({
            detalle_id_tipo: typeof p.detalle_id,
            detalle_id_valor: p.detalle_id,
            cantidad_devolver_tipo: typeof p.cantidad_devolver,
            cantidad_devolver_valor: p.cantidad_devolver,
            motivo_tipo: typeof p.motivo,
            motivo_valor: p.motivo
        }))
    });
    
    // Enviar petición AJAX directamente
    fetch('/ventas/procesar-devolucion', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('📨 Respuesta recibida:', response);
        console.log('📊 Status:', response.status);
        console.log('📊 Status Text:', response.statusText);
        
        if (!response.ok) {
            return response.text().then(text => {
                console.error('❌ Respuesta de error:', text);
                
                // Intentar parsear como JSON para obtener errores de validación
                try {
                    const errorData = JSON.parse(text);
                    console.error('❌ Errores de validación:', errorData);
                    
                    if (errorData.errors) {
                        // Mostrar errores de validación específicos
                        const erroresFormateados = Object.entries(errorData.errors)
                            .map(([campo, mensajes]) => `<strong>${campo}:</strong> ${Array.isArray(mensajes) ? mensajes.join(', ') : mensajes}`)
                            .join('<br>');
                        
                        throw new Error(`VALIDATION_ERROR:${erroresFormateados}`);
                    } else if (errorData.message) {
                        throw new Error(`SERVER_ERROR:${errorData.message}`);
                    }
                } catch (parseError) {
                    // Si no se puede parsear como JSON, usar el texto original
                    console.error('❌ No se pudo parsear error como JSON:', parseError);
                }
                
                throw new Error(`Error HTTP ${response.status}: ${text}`);
            });
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        procesandoDevolucion = false;
        console.log('✅ Respuesta exitosa:', data);
        
        // Debug: mostrar tipos de datos recibidos
        if (data.data) {
            console.log('🔍 Debug - Tipos de datos recibidos:', {
                total_original: typeof data.data.total_original,
                total_actual: typeof data.data.total_actual,
                monto_total_devuelto: typeof data.data.monto_total_devuelto,
                total_devolucion_actual: typeof data.data.total_devolucion_actual
            });
        }
        
        if (data.success) {
            // Función auxiliar para formatear números
            const formatearMonto = (valor, defaultValue = '0.00') => {
                if (valor === null || valor === undefined || isNaN(valor)) {
                    return defaultValue;
                }
                return parseFloat(valor).toFixed(2);
            };
            
            // Éxito
            Swal.fire({
                title: '<i class="material-icons" style="color: #059669; font-size: 2.5rem; margin-bottom: 0.5rem;">check_circle</i><br>Devolución Exitosa',
                html: `
                    <div style="text-align: left; background: #f0fdf4; padding: 1.5rem; border-radius: 12px; margin: 1rem 0; border: 1px solid #bbf7d0;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; color: #059669;">
                            <i class="material-icons">task_alt</i>
                            <strong>Devolución procesada correctamente</strong>
                        </div>
                        
                        <div style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <i class="material-icons" style="color: #059669; font-size: 1.1rem;">monetization_on</i>
                                <span style="color: #374151; font-weight: 600;">Monto devuelto ahora: S/ ${formatearMonto(data.data?.total_devolucion_actual)}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <i class="material-icons" style="color: #3b82f6; font-size: 1.1rem;">inventory_2</i>
                                <span style="color: #374151; font-weight: 600;">Productos devueltos: ${data.data?.productos_devueltos_ahora || 0}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="material-icons" style="color: #f59e0b; font-size: 1.1rem;">assignment</i>
                                <span style="color: #374151; font-weight: 600;">Estado de venta: ${data.data?.nuevo_estado_formateado || 'actualizado'}</span>
                            </div>
                        </div>

                        ${data.data?.mensaje_estado ? `
                        <div style="display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.75rem; background: #eff6ff; border-radius: 8px; margin-bottom: 1rem;">
                            <i class="material-icons" style="color: #3b82f6; font-size: 1.1rem; margin-top: 0.1rem;">info</i>
                            <span style="color: #1e40af; font-size: 0.875rem;">${data.data.mensaje_estado}</span>
                        </div>
                        ` : ''}
                        
                        <div style="background: #fafafa; padding: 1rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                                <i class="material-icons" style="color: #6b7280;">calculate</i>
                                <strong style="color: #374151;">TOTALES ACTUALIZADOS</strong>
                            </div>
                            <div style="margin: 0.25rem 0;">
                                <span style="text-decoration: line-through; color: #6b7280; font-size: 0.875rem;">Total original: S/ ${formatearMonto(data.data?.total_original)}</span>
                            </div>
                            ${data.data?.nuevo_estado === 'devuelta' ? 
                                `<div style="margin: 0.25rem 0; color: #dc2626; font-weight: 600; font-size: 1.1rem;">Total actual: S/ 0.00</div>
                                 <div style="margin: 0.25rem 0; color: #dc2626; background: #fee2e2; padding: 4px 8px; border-radius: 4px; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                     <i class="material-icons" style="font-size: 0.9rem;">check_circle</i>
                                     DEVUELTO
                                 </div>` 
                                : 
                                `<div style="margin: 0.25rem 0; color: #059669; font-weight: 600;">Total actual: S/ ${formatearMonto(data.data?.total_actual)}</div>`
                            }
                            <div style="margin: 0.25rem 0; color: #d97706;">Total devuelto: S/ ${formatearMonto(data.data?.monto_total_devuelto)}</div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; color: #6b7280; font-size: 0.875rem;">
                            <i class="material-icons" style="font-size: 1rem;">schedule</i>
                            <span>La página se actualizará automáticamente en unos segundos...</span>
                        </div>
                    </div>
                `,
                icon: null,
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                allowOutsideClick: false,
                customClass: {
                    popup: 'swal-success-popup'
                }
            }).then(() => {
                actualizarInterfazDespuesDevolucion(data, productos);
                limpiarSeleccion();
            });
        } else {
            // Error del servidor
            Swal.fire({
                title: '❌ Error en Devolución',
                text: data.message || 'Ocurrió un error al procesar la devolución',
                icon: 'error',
                confirmButtonColor: '#dc2626'
            });
        }
    })
    .catch(error => {
        hideLoading();
        procesandoDevolucion = false;
        console.error('❌ Error completo:', error);
        
        let errorTitle = '❌ Error de Conexión';
        let errorMessage = 'No se pudo conectar con el servidor. Verifique su conexión e intente nuevamente.';
        
        if (error.message.startsWith('VALIDATION_ERROR:')) {
            errorTitle = '❌ Error de Validación';
            errorMessage = error.message.replace('VALIDATION_ERROR:', '');
        } else if (error.message.startsWith('SERVER_ERROR:')) {
            errorTitle = '❌ Error del Servidor';
            errorMessage = error.message.replace('SERVER_ERROR:', '');
        } else if (error.message.includes('HTTP 422')) {
            errorTitle = '❌ Error de Validación';
            errorMessage = `
                <div style="text-align: left;">
                    <p><strong>Algunos datos no son válidos:</strong></p>
                    <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                        <li>Verifica que todos los campos estén completos</li>
                        <li>Asegúrate de que la venta esté en estado válido</li>
                        <li>Revisa que las cantidades sean correctas</li>
                        <li>Confirma que los motivos estén seleccionados</li>
                    </ul>
                </div>
            `;
        } else if (error.message.includes('HTTP 404')) {
            errorTitle = '❌ Ruta No Encontrada';
            errorMessage = 'La ruta de devolución no fue encontrada. Contacte al administrador.';
        } else if (error.message.includes('HTTP 500')) {
            errorTitle = '❌ Error Interno';
            errorMessage = 'Error interno del servidor. Contacte al administrador.';
        } else if (error.message.includes('estado')) {
            errorTitle = '❌ Estado Inválido';
            errorMessage = 'La venta no está en un estado válido para devoluciones. Solo se pueden procesar devoluciones de ventas activas.';
        }
        
        Swal.fire({
            title: errorTitle,
            html: `
                <div style="text-align: left;">
                    <div style="margin-bottom: 1rem;">${errorMessage}</div>
                    <details style="margin-top: 1rem;">
                        <summary style="cursor: pointer; color: #6b7280;">Ver detalles técnicos</summary>
                        <pre style="background: #f3f4f6; padding: 0.5rem; margin-top: 0.5rem; font-size: 0.8rem; text-align: left; border-radius: 4px; overflow-x: auto;">${error.message}</pre>
                    </details>
                </div>
            `,
            icon: 'error',
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Entendido'
        });
    });
}

// Limpiar selección de productos
function limpiarSeleccion() {
    console.log('🧹 Limpiando selección');
    
    document.querySelectorAll('.producto-checkbox').forEach(checkbox => {
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change'));
    });
    
    // Mostrar toast
    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
    
    toast.fire({
        icon: 'info',
        title: 'Selección limpiada'
    });
}

// Buscar venta por código de barras (función futura)
function buscarPorCodigoBarras() {
    Swal.fire({
        title: '📷 Escanear Código',
        text: 'Función de escáner disponible próximamente',
        icon: 'info',
        confirmButtonColor: '#3b82f6'
    });
}

// Exportar reporte de devoluciones (función futura)
function exportarReporteDevoluciones() {
    Swal.fire({
        title: '📊 Exportar Reporte',
        text: '¿En qué formato deseas exportar el reporte de devoluciones?',
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: 'Excel',
        denyButtonText: 'PDF',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#059669',
        denyButtonColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '/ventas/devoluciones/export/excel';
        } else if (result.isDenied) {
            window.location.href = '/ventas/devoluciones/export/pdf';
        }
    });
}

// Funciones de utilidad
function calcularTotalDevolucion() {
    let total = 0;
    
    document.querySelectorAll('.producto-checkbox:checked').forEach(checkbox => {
        const row = checkbox.closest('.producto-row');
        const cantidadInput = row.querySelector('.cantidad-devolver');
        const precioCell = row.querySelector('.historial-price');
        
        if (cantidadInput.value && precioCell) {
            const precio = parseFloat(precioCell.textContent.replace('S/', '').replace(',', ''));
            const cantidad = parseInt(cantidadInput.value);
            total += precio * cantidad;
        }
    });
    
    return total;
}

function formatearMoneda(cantidad) {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN'
    }).format(cantidad);
}

// Event listeners adicionales
document.addEventListener('DOMContentLoaded', function() {
    // Efecto hover en filas de productos
    const filas = document.querySelectorAll('.producto-row');
    filas.forEach(fila => {
        fila.addEventListener('mouseenter', function() {
            if (!this.classList.contains('selected')) {
                this.style.backgroundColor = '#f8fafc';
            }
        });
        
        fila.addEventListener('mouseleave', function() {
            if (!this.classList.contains('selected')) {
                this.style.backgroundColor = '';
            }
        });
    });
    
    // Actualizar total en tiempo real
    document.querySelectorAll('.cantidad-devolver').forEach(input => {
        input.addEventListener('input', function() {
            actualizarTotalDevolucion();
        });
    });
});

function actualizarTotalDevolucion() {
    const total = calcularTotalDevolucion();
    
    // Mostrar total en algún lugar de la interfaz
    let totalDisplay = document.getElementById('totalDevolucion');
    if (!totalDisplay) {
        // Crear elemento si no existe
        totalDisplay = document.createElement('div');
        totalDisplay.id = 'totalDevolucion';
        totalDisplay.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #059669;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-weight: 600;
            display: none;
        `;
        document.body.appendChild(totalDisplay);
    }
    
    if (total > 0) {
        totalDisplay.textContent = `Total a devolver: ${formatearMoneda(total)}`;
        totalDisplay.style.display = 'block';
    } else {
        totalDisplay.style.display = 'none';
    }
}

function validarFormularioDevolucion() {
    const checkboxes = document.querySelectorAll('.producto-checkbox:checked');
    
    if (checkboxes.length === 0) {
        Swal.fire({
            title: '⚠️ Selección requerida',
            text: 'Debes seleccionar al menos un producto para devolver',
            icon: 'warning',
            confirmButtonColor: '#f59e0b'
        });
        return false;
    }
    
    let productosValidos = 0;
    let errores = [];
    
    checkboxes.forEach((checkbox, index) => {
        const row = checkbox.closest('.producto-row');
        const cantidadInput = row.querySelector('.cantidad-devolver');
        const motivoSelect = row.querySelector('.motivo-select');
        const productoNombre = row.querySelector('.historial-product-name')?.textContent || `Producto ${index + 1}`;
        
        // Saltear productos completamente devueltos
        if (!cantidadInput || cantidadInput.type === 'hidden') {
            errores.push(`${productoNombre}: Ya está completamente devuelto`);
            return;
        }
        
        const cantidad = parseInt(cantidadInput.value);
        const motivo = motivoSelect.value;
        const maxCantidad = parseInt(cantidadInput.getAttribute('max'));
        
        if (!cantidad || cantidad <= 0) {
            errores.push(`${productoNombre}: Especifica una cantidad válida para devolver`);
            return;
        }
        
        if (cantidad > maxCantidad) {
            errores.push(`${productoNombre}: No puedes devolver más de ${maxCantidad} unidades disponibles`);
            return;
        }
        
        if (!motivo) {
            errores.push(`${productoNombre}: Selecciona un motivo para la devolución`);
            return;
        }
        
        productosValidos++;
    });
    
    if (errores.length > 0) {
        Swal.fire({
            title: '❌ Errores en la validación',
            html: `
                <div style="text-align: left;">
                    <p>Corrige los siguientes errores:</p>
                    <ul style="margin-top: 1rem; padding-left: 1.5rem;">
                        ${errores.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                </div>
            `,
            icon: 'error',
            confirmButtonColor: '#dc2626'
        });
        return false;
    }
    
    if (productosValidos === 0) {
        Swal.fire({
            title: '⚠️ Sin productos válidos',
            text: 'No hay productos válidos seleccionados para devolver',
            icon: 'warning',
            confirmButtonColor: '#f59e0b'
        });
        return false;
    }
    
    return true;
}

/* ==============================================
   FUNCIONES AUXILIARES PARA ACTUALIZACIÓN DINÁMICA
   ============================================== */

/**
 * Actualizar interfaz después de procesar devolución
 */
function actualizarInterfazDespuesDevolucion(respuesta, productosProcesados) {
    const btnProcesar = document.getElementById('procesarDevolucionBtn');
    if (btnProcesar) {
        btnProcesar.disabled = true;
        btnProcesar.style.opacity = '0.7';
        btnProcesar.style.cursor = 'not-allowed';
    }

    if (Array.isArray(productosProcesados)) {
        productosProcesados.forEach(p => {
            const row = document.querySelector(`.producto-row[data-detalle-id="${p.detalle_id}"]`);
            if (!row) return;

            const checkbox = row.querySelector('.producto-checkbox');
            if (checkbox) {
                checkbox.checked = false;
                checkbox.disabled = true;
            }

            const cantidadInput = row.querySelector('.cantidad-devolver');
            const motivoSelect = row.querySelector('.motivo-select');
            const cantidadCell = row.querySelector('td:nth-child(3)');

            let devueltasEl = cantidadCell ? cantidadCell.querySelector('small:nth-of-type(1)') : null;
            let disponiblesEl = cantidadCell ? cantidadCell.querySelector('small:nth-of-type(2)') : null;

            const cantidadDevueltaAhora = parseInt(p.cantidad_devolver) || 0;
            let devueltasActual = 0;
            let disponiblesActual = null;

            if (!devueltasEl && cantidadCell) {
                const br1 = document.createElement('br');
                cantidadCell.appendChild(br1);
                devueltasEl = document.createElement('small');
                devueltasEl.style.color = '#d97706';
                devueltasEl.style.fontWeight = '600';
                cantidadCell.appendChild(devueltasEl);
                const br2 = document.createElement('br');
                cantidadCell.appendChild(br2);
                disponiblesEl = document.createElement('small');
                disponiblesEl.style.color = '#059669';
                disponiblesEl.style.fontWeight = '600';
                cantidadCell.appendChild(disponiblesEl);
            }

            if (devueltasEl && devueltasEl.textContent) {
                const match = devueltasEl.textContent.match(/Devueltas:\s*(\d+)/);
                if (match) devueltasActual = parseInt(match[1]) || 0;
            }
            if (disponiblesEl && disponiblesEl.textContent) {
                const match = disponiblesEl.textContent.match(/Disponibles:\s*(\d+)/);
                if (match) disponiblesActual = parseInt(match[1]) || 0;
            }
            if (disponiblesActual === null && cantidadInput) {
                const m = parseInt(cantidadInput.getAttribute('max'));
                if (!isNaN(m)) disponiblesActual = m;
            }

            const nuevasDevueltas = devueltasActual + cantidadDevueltaAhora;
            const nuevasDisponibles = disponiblesActual !== null ? Math.max(0, disponiblesActual - cantidadDevueltaAhora) : null;

            if (devueltasEl) devueltasEl.textContent = `Devueltas: ${nuevasDevueltas}`;
            if (disponiblesEl && nuevasDisponibles !== null) disponiblesEl.textContent = `Disponibles: ${nuevasDisponibles}`;

            if (cantidadInput) {
                if (nuevasDisponibles === 0) {
                    const span = document.createElement('span');
                    span.style.color = '#6b7280';
                    span.style.fontStyle = 'italic';
                    span.style.fontSize = '0.875rem';
                    span.textContent = 'Ya devuelto completamente';
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = cantidadInput.name;
                    hidden.value = '0';
                    const parent = cantidadInput.parentElement;
                    parent.innerHTML = '';
                    parent.appendChild(span);
                    parent.appendChild(hidden);
                } else {
                    cantidadInput.disabled = true;
                    cantidadInput.value = '';
                }
            }

            if (motivoSelect) {
                motivoSelect.disabled = true;
                motivoSelect.value = '';
            }

            row.classList.remove('selected');
        });
    }

    if (respuesta && respuesta.data) {
        const totalBoxValue = document.querySelector('.totals-neo .total-box.success .value');
        if (totalBoxValue) {
            const totalActual = respuesta.data.nuevo_estado === 'devuelta' ? 0 : parseFloat(respuesta.data.total_actual || 0);
            totalBoxValue.textContent = `S/ ${totalActual.toFixed(2)}`;
        }
    }

    actualizarEstadisticasDevoluciones();
}

/**
 * Actualizar estadísticas de devoluciones
 */
function actualizarEstadisticasDevoluciones() {
    // Incrementar contador de devoluciones si existe
    const contadorDevoluciones = document.querySelector('.stat-devoluciones .stat-value');
    if (contadorDevoluciones) {
        const currentValue = parseInt(contadorDevoluciones.textContent) || 0;
        contadorDevoluciones.textContent = currentValue + 1;
    }
    
    // Actualizar otros indicadores si existen
    const indicadores = document.querySelectorAll('.dashboard-stat');
    indicadores.forEach(indicador => {
        if (indicador.textContent.includes('Devoluciones')) {
            const valueElement = indicador.querySelector('.stat-value');
            if (valueElement) {
                const currentValue = parseInt(valueElement.textContent) || 0;
                valueElement.textContent = currentValue + 1;
            }
        }
    });
}

console.log('✅ Devoluciones - JavaScript completamente cargado');
