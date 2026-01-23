console.log('✅ Historial de Ventas - JavaScript cargado');

// Variables globales
let timeout;

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando Historial de Ventas');
    
    // Configurar eventos
    configurarFiltros();
    configurarBusqueda();
    configurarAcciones();
    configurarScrollHorizontalTabla();
    
    console.log('✅ Historial de Ventas inicializado correctamente');
});

// Configurar filtros automáticos
function configurarFiltros() {
    const filtros = ['filtroMetodo', 'filtroComprobante', 'filtroUsuario'];
    
    filtros.forEach(filtroId => {
        const filtro = document.getElementById(filtroId);
        if (filtro) {
            filtro.addEventListener('change', function() {
                console.log(`📊 Filtro ${filtroId} cambiado a:`, this.value);
                document.getElementById('filtrosForm').submit();
            });
        }
    });
}

// Configurar búsqueda con delay
function configurarBusqueda() {
    const searchInput = document.getElementById('searchHistorial');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value;
            
            console.log('🔍 Búsqueda:', query);
            
            timeout = setTimeout(() => {
                if (query.length >= 3 || query.length === 0) {
                    document.getElementById('filtrosForm').submit();
                }
            }, 200); // Reducido de 500ms a 200ms para mayor velocidad
        });
        
        // Enter para buscar inmediato
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(timeout);
                document.getElementById('filtrosForm').submit();
            }
        });
    }
}

// Configurar acciones de los botones
function configurarAcciones() {
    // Botones de ver detalle
    document.querySelectorAll('[onclick^="verDetalleVenta"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const ventaId = this.getAttribute('onclick').match(/\d+/)[0];
            mostrarDetalleVenta(ventaId);
        });
    });
    
    // Botones de imprimir
    document.querySelectorAll('[onclick^="imprimirComprobante"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const ventaId = this.getAttribute('onclick').match(/\d+/)[0];
            imprimirComprobante(ventaId);
        });
    });
}

// Función mejorada para mostrar detalle de venta
function mostrarDetalleVenta(ventaId) {
    console.log('👁️ Mostrando detalle de venta:', ventaId);
    
    // Mostrar loading con diseño moderno
    Swal.fire({
        title: 'Cargando Detalle',
        html: `
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 2em; margin-bottom: 16px;">📋</div>
                <p style="color: #6b7280;">Obteniendo información de la venta...</p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Hacer petición AJAX para obtener datos reales
    fetch(`/ventas/detalle/${ventaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const venta = data.venta;
                mostrarModalDetalleVenta(venta);
            } else {
                throw new Error(data.message || 'Error al cargar detalle');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error al Cargar',
                text: 'No se pudo obtener el detalle de la venta',
                confirmButtonColor: '#dc2626'
            });
        });
}

// Modal mejorado con Material Icons y información de devoluciones
function mostrarModalDetalleVenta(venta) {
    const fechaFormateada = new Date(venta.fecha_venta || venta.created_at).toLocaleString('es-PE');
    const clienteNombre = (
        (venta && venta.cliente_razon_social)
            ? venta.cliente_razon_social
            : (venta?.cliente && (venta.cliente.nombre_completo || venta.cliente.razon_social))
                ? (venta.cliente.nombre_completo || venta.cliente.razon_social)
                : 'CLIENTE GENERAL 999'
    );
    const clienteDoc = (
        (venta && venta.cliente_numero_documento)
            ? venta.cliente_numero_documento
            : (venta?.cliente && venta.cliente.dni)
                ? venta.cliente.dni
                : '99999999'
    );
    const vendedorNombre = venta?.usuario?.name || 'N/A';
    const vendedorCorreo = venta?.usuario?.email || 'N/A';
    
    // Estado de la venta con iconos
    let estadoHtml = '';
    if (venta.estado === 'devuelta') {
        estadoHtml = `
            <div style="background:#fef2f2; border-radius:12px; padding:10px; margin-bottom:16px; border:1px solid #fca5a5; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i class="material-icons" style="color:#dc2626;">check_circle</i>
                <span style="color:#dc2626; font-weight:700;">DEVUELTA</span>
            </div>
        `;
    } else if (venta.estado === 'parcialmente_devuelta') {
        estadoHtml = `
            <div style="background:#fff7ed; border-radius:10px; padding:10px; margin-bottom:12px; border:1px solid #fdba74; display:flex; align-items:center; gap:10px;">
                <i class="material-icons" style="color:#d97706; font-size:22px;">history</i>
                <span style="color:#92400e; font-weight:700;">Parcialmente devuelta</span>
            </div>
        `;
    }

    let descuentosHtml = '';

    let devolucionesHtml = '';
    
    let productosHtml = '';
    if (venta.detalles && venta.detalles.length > 0) {
        const filas = venta.detalles.map(detalle => {
            const tieneDevolucion = detalle.tiene_devolucion || false;
            const cantidadDevuelta = detalle.cantidad_devuelta || 0;
            const cantidadRestante = detalle.cantidad_restante || detalle.cantidad;
            const devolucionCompleta = detalle.devolucion_completa || false;
            const subtotal = (cantidadRestante * detalle.precio_unitario).toFixed(2);
            const estado = devolucionCompleta ? '<span style="background:#fef2f2; color:#dc2626; padding:4px 8px; border-radius:9999px; font-size:0.75rem; font-weight:600;">DEVUELTO</span>' : (tieneDevolucion ? '<span style="background:#fff7ed; color:#d97706; padding:4px 8px; border-radius:9999px; font-size:0.75rem; font-weight:600;">PARCIAL</span>' : '');
            
            // Procesar información de lotes
            let loteHtml = '<span style="color:#9ca3af; font-style:italic; font-size:0.8rem;">-</span>';
            
            if (detalle.lotes_info) {
                let lotes = detalle.lotes_info;
                if (typeof lotes === 'string') {
                    try { lotes = JSON.parse(lotes); } catch(e) { console.error('Error parsing lotes:', e); }
                }
                
                if (Array.isArray(lotes) && lotes.length > 0) {
                    loteHtml = lotes.map(l => 
                        `<div style="font-size:0.75rem; white-space:nowrap; line-height:1.2;">
                            <span style="font-weight:600; color:#4b5563;">${l.lote_codigo || 'S/L'}</span>
                            ${l.fecha_vencimiento ? `<br><span style="color:#dc2626; font-size:0.7rem;">Vence: ${l.fecha_vencimiento.split('T')[0]}</span>` : ''}
                         </div>`
                    ).join('<hr style="margin:4px 0; border:0; border-top:1px dashed #e5e7eb;">');
                }
            }

            return `
                <tr style="background:${devolucionCompleta ? '#fff' : '#fff'};">
                    <td style="padding:10px; color:#111827; font-weight:600;">${detalle.producto.nombre}<div style="color:#6b7280; font-size:0.8rem; font-weight:500;">${detalle.producto.concentracion || ''}</div></td>
                    <td style="padding:10px; text-align:center; vertical-align:middle;">${loteHtml}</td>
                    <td style="padding:10px; text-align:center; color:${devolucionCompleta ? '#dc2626' : '#059669'}; font-weight:700;">${cantidadRestante}${tieneDevolucion ? `<div style=\"font-size:0.7rem; color:#dc2626; text-decoration:line-through;\">-${cantidadDevuelta}</div>` : ''}</td>
                    <td style="padding:10px; text-align:center; color:#374151;">S/. ${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                    <td style="padding:10px; text-align:right; color:${devolucionCompleta ? '#dc2626' : '#059669'}; font-weight:700;">S/. ${subtotal}${tieneDevolucion ? `<div style=\"font-size:0.7rem; color:#dc2626; text-decoration:line-through;\">-S/. ${(cantidadDevuelta * detalle.precio_unitario).toFixed(2)}</div>` : ''}</td>
                    <td style="padding:10px; text-align:center;">${estado}</td>
                </tr>
            `;
        }).join('');
        productosHtml = `
            <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                <table style="width:100%; border-collapse:separate; border-spacing:0;">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th style="text-align:left; padding:12px; color:#6b7280; font-weight:600;">Producto</th>
                            <th style="text-align:center; padding:12px; color:#6b7280; font-weight:600;">Lote</th>
                            <th style="text-align:center; padding:12px; color:#6b7280; font-weight:600;">Cant.</th>
                            <th style="text-align:center; padding:12px; color:#6b7280; font-weight:600;">P. Unitario</th>
                            <th style="text-align:right; padding:12px; color:#6b7280; font-weight:600;">Subtotal</th>
                            <th style="text-align:center; padding:12px; color:#6b7280; font-weight:600;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${filas}
                    </tbody>
                </table>
            </div>
            <style>
                .swal-popup-detail table tbody tr:hover { background:#f3f4f6; }
                .swal-popup-detail table tbody tr:nth-child(odd) { background:#f9fafb; }
                .swal-popup-detail table tbody tr:nth-child(even) { background:#ffffff; }
            </style>
        `;
    }

        // Modificar el contenido si la venta está completamente devuelta
        let productosSection = '';
        let totalesSection = '';
        
        if (venta.estado === 'devuelta') {
            // Si está completamente devuelta, no mostrar "Productos (Estado Actual)"
            productosSection = '';
            totalesSection = '';
        } else {
            // Mostrar productos normalmente
            productosSection = `
                <!-- Productos -->
                <div style="margin-bottom: 16px;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <i class="material-icons" style="color: #059669; margin-right: 8px;">shopping_cart</i>
                        <h4 style="margin: 0; color: #059669; font-weight: 600; font-size: 1.15rem;">Productos ${venta.tiene_devoluciones ? '(Estado Actual)' : 'Vendidos'}</h4>
                    </div>
                    ${productosHtml}
                </div>
            `;
            
            // Mostrar totales normalmente
            totalesSection = `
                <!-- Totales -->
                <div style="background: #f9fafb; border-radius: 12px; padding: 16px; border: 1px solid #e5e7eb;">
                    ${venta.tiene_descuento ? `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #6b7280;">Subtotal Original:</span>
                            <span style="font-weight: 500; color: #374151;">S/. ${parseFloat(venta.subtotal_original || 0).toFixed(2)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #dc2626;">Descuento ${venta.descuento_tipo === 'porcentaje' ? `(${venta.descuento_porcentaje}%)` : ''}:</span>
                            <span style="font-weight: 600; color: #dc2626;">-S/. ${parseFloat(venta.descuento_monto || 0).toFixed(2)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="color: #6b7280;">Subtotal con Descuento:</span>
                            <span style="font-weight: 500; color: #374151;">S/. ${parseFloat(venta.subtotal || 0).toFixed(2)}</span>
                        </div>
                    ` : `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span style="color: #6b7280;">Subtotal:</span>
                            <span style="font-weight: 500; color: #374151;">S/. ${parseFloat(venta.subtotal || 0).toFixed(2)}</span>
                        </div>
                    `}
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: #6b7280;">IGV (18%):</span>
                        <span style="font-weight: 500; color: #374151;">S/. ${parseFloat(venta.igv || 0).toFixed(2)}</span>
                    </div>
                    ${venta.tiene_devoluciones ? `
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #dc2626;">Monto Devuelto:</span>
                            <span style="font-weight: 600; color: #dc2626;">-S/. ${parseFloat(venta.monto_total_devuelto || 0).toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <div style="display: flex; justify-content: space-between; padding-top: 12px; border-top: 2px solid #e5e7eb;">
                        <span style="font-weight: 700; color: #374151; font-size: 1.1em;">TOTAL ${venta.tiene_devoluciones ? 'ORIGINAL' : ''}:</span>
                        <span style="font-weight: 700; color: #dc2626; font-size: 1.2em;">S/. ${parseFloat(venta.total).toFixed(2)}</span>
                    </div>
                    ${venta.tiene_devoluciones ? `
                        <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid #e5e7eb; margin-top: 8px;">
                            <span style="font-weight: 700; color: #059669; font-size: 1.1em;">TOTAL ACTUAL:</span>
                            <span style="font-weight: 700; color: #059669; font-size: 1.2em;">S/. ${(parseFloat(venta.total) - parseFloat(venta.monto_total_devuelto || 0)).toFixed(2)}</span>
                        </div>
                    ` : ''}
                    
                    ${venta.metodo_pago === 'efectivo' && venta.vuelto > 0 ? `
                        <div style="background: #fef3c7; border-radius: 8px; padding: 8px; margin-top: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 600; color: #92400e;">💰 Vuelto:</span>
                                <span style="font-weight: 700; color: #92400e;">S/. ${parseFloat(venta.vuelto).toFixed(2)}</span>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        }
        
        Swal.fire({
        title: '',
            html: `
            <style>
                .swal2-popup.swal-popup-detail{padding:0 !important;border-radius:14px;}
                .swal2-popup.swal-popup-detail .swal2-title{display:none !important;margin:0 !important;padding:0 !important;}
                .swal2-popup.swal-popup-detail .swal2-html-container{margin:0 !important;padding:0 !important; width:100% !important; max-width:none !important;}
                .swal2-popup.swal-popup-detail .swal2-close{display:none !important}
                .detail-header{background:#eaf1ff; border-bottom:1px solid #dbe7ff; border-top-left-radius:14px; border-top-right-radius:14px; box-shadow:0 2px 6px rgba(0,0,0,0.06); padding:16px 20px; display:flex; align-items:center; gap:12px; width:100%; position:relative;}
                .detail-close-btn{position:absolute; right:12px; top:50%; transform:translateY(-50%); background:transparent; border:none; color:#374151; font-size:24px; line-height:1; cursor:pointer; font-weight:700}
                .detail-close-btn:hover{color:#1f2937}
            </style>
            <div style="width:100%; margin:0; box-sizing:border-box;">
                <div class="detail-header" style="position:sticky; top:0; z-index:5;">
                    <i class="material-icons" style="color:#2563eb; font-size:26px;">receipt_long</i>
                    <span style="color:#1f2937; font-weight:700; font-size:1.18rem;">Detalle de Venta</span>
                    <button type="button" class="detail-close-btn" onclick="Swal.close()" aria-label="Cerrar" style="font-weight:700;">×</button>
                </div>
                <div class="detail-scroll" style="padding:16px 24px; box-sizing:border-box; max-height:70vh; overflow-y:auto;">
                ${estadoHtml}
                
                <!-- Encabezado con tres "inputs" y tarjetas Cliente/Vendedor -->
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; margin-bottom:16px;">
                    <div>
                        <div style="color:#6b7280; font-weight:600; margin-bottom:6px;">N° Venta</div>
                        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:10px; min-height:42px; display:flex; align-items:center; box-shadow: 0 1px 2px rgba(0,0,0,0.04); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-weight:700; color:#1f2937;">${venta.numero_venta}</div>
                    </div>
                    <div>
                        <div style="color:#6b7280; font-weight:600; margin-bottom:6px;">Comprobante</div>
                        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:10px; min-height:42px; display:flex; align-items:center; box-shadow: 0 1px 2px rgba(0,0,0,0.04); color:${venta.tipo_comprobante === 'boleta' ? '#059669' : '#6b7280'}; font-weight:500;">
                            ${venta.tipo_comprobante === 'boleta' ? 'Boleta' : (venta.tipo_comprobante === 'ticket' ? 'Ticket' : 'Sin Comprobante')}
                        </div>
                    </div>
                    <div>
                        <div style="color:#6b7280; font-weight:600; margin-bottom:6px;">Fecha</div>
                        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:10px; min-height:42px; display:flex; align-items:center; box-shadow: 0 1px 2px rgba(0,0,0,0.04); color:#374151; font-weight:500;">${fechaFormateada}</div>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; border-left:4px solid #2563eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); text-align:left;">
                        <div style="display:flex; align-items:center; gap:8px; font-weight:700; color:#374151; margin-bottom:10px; font-size:1.08rem;">
                            <i class="material-icons" style="color:#2563eb; font-size:22px;">person</i>
                            Cliente
                        </div>
                        <div style="color:#6b7280;">Nombre</div>
                        <div style="color:#111827; font-weight:600;">${clienteNombre}</div>
                        <div style="color:#6b7280; margin-top:6px;">Documento</div>
                        <div style="color:#111827;">${clienteDoc}</div>
                    </div>
                    <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; border-left:4px solid #8b5cf6; box-shadow: 0 1px 3px rgba(0,0,0,0.06); text-align:left;">
                        <div style="display:flex; align-items:center; gap:8px; font-weight:700; color:#374151; margin-bottom:10px; font-size:1.08rem;">
                            <i class="material-icons" style="color:#8b5cf6; font-size:22px;">badge</i>
                            Vendedor
                        </div>
                        <div style="color:#6b7280;">Nombre</div>
                        <div style="color:#111827; font-weight:600;">${vendedorNombre}</div>
                        <div style="color:#6b7280; margin-top:6px;">Correo</div>
                        <div style="color:#111827;">${vendedorCorreo}</div>
                    </div>
                </div>

                
                
                ${productosSection}
                ${totalesSection}
                </div>
            `,
            showCancelButton: false,
            showConfirmButton: false,
            showCloseButton: false,
        width: '900px',
        customClass: {
            popup: 'swal-popup-detail',
            confirmButton: 'btn-entendido-visible'
        }
        });
}

// Función para reimprimir comprobante
function reimprimirComprobante(ventaId) {
    console.log('🖨️ Reimprimiendo comprobante para venta:', ventaId);
    
    Swal.fire({
        icon: 'info',
        title: 'Reimprimiendo...',
        html: `
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 3em; margin-bottom: 16px;">🖨️</div>
                <p>Preparando comprobante para impresión...</p>
            </div>
        `,
        showConfirmButton: false,
        timer: 2000
    }).then(() => {
        // Aquí implementarás la lógica real de impresión
        window.open(`/punto-venta/pdf/${ventaId}`, '_blank');
    });
}

// Imprimir comprobante
function imprimirComprobante(ventaId) {
    console.log('🖨️ Imprimir comprobante de venta:', ventaId);
    
    Swal.fire({
        title: '🖨️ Imprimir Comprobante',
        text: '¿Qué deseas hacer?',
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: 'Ver PDF',
        denyButtonText: 'Descargar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#059669',
        denyButtonColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            // Abrir PDF en nueva ventana
            window.open(`/punto-venta/pdf/${ventaId}`, '_blank');
            
            Swal.fire({
                title: '✅ PDF Abierto',
                text: 'El comprobante se abrió en una nueva ventana',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        } else if (result.isDenied) {
            // Descargar PDF
            const link = document.createElement('a');
            link.href = `/punto-venta/pdf/${ventaId}?download=1`;
            link.download = `venta_${ventaId}.pdf`;
            link.click();
            
            Swal.fire({
                title: '📥 Descargando...',
                text: 'El comprobante se está descargando',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}

// Limpiar todos los filtros
function limpiarFiltros() {
    console.log('🧹 Limpiando filtros');
    
    // Limpiar campos
    document.getElementById('searchHistorial').value = '';
    document.getElementById('filtroMetodo').value = '';
    document.getElementById('filtroComprobante').value = '';
    document.getElementById('filtroUsuario').value = '';
    
    // Enviar formulario
    document.getElementById('filtrosForm').submit();
}

// Exportar datos (función futura)
function exportarVentas() {
    console.log('📊 Exportar ventas');
    
    Swal.fire({
        title: '📊 Exportar Ventas',
        text: '¿En qué formato deseas exportar?',
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
            // Exportar a Excel
            window.location.href = '/ventas/export/excel';
        } else if (result.isDenied) {
            // Exportar a PDF
            window.location.href = '/ventas/export/pdf';
        }
    });
}

// Funciones de utilidad
function formatearFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-PE');
}

function formatearMoneda(cantidad) {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN'
    }).format(cantidad);
}

// Mostrar tooltip en hover
function mostrarTooltip(elemento, texto) {
    elemento.setAttribute('title', texto);
}

// Event listeners para efectos visuales
document.addEventListener('DOMContentLoaded', function() {
    // Efecto hover en filas de la tabla
    const filas = document.querySelectorAll('.historial-data-row');
    filas.forEach(fila => {
        fila.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        fila.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
});

// Función para actualizar estadísticas en tiempo real (si es necesario)
function actualizarEstadisticas() {
    console.log('📈 Actualizando estadísticas...');
    
    // Aquí podrías hacer una petición AJAX para obtener estadísticas actualizadas
    // Por ahora solo mostramos un mensaje
    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    
    toast.fire({
        icon: 'info',
        title: 'Estadísticas actualizadas'
    });
}

function mostrarModalImpresionHistorial(ventaId) {
    Swal.fire({
        title: '',
        html: `
            <div style="text-align:center; padding-top:6px;">
                <div style="display:inline-flex; align-items:center; justify-content:center; width:56px; height:56px; border-radius:50%; background:#f3f4f6; color:#374151; margin-bottom:10px;">
                    <iconify-icon icon="mdi:printer" style="font-size:28px"></iconify-icon>
                </div>
                <div style="font-size:20px; font-weight:700; color:#111827; margin-bottom:10px;">Imprimir comprobante de venta</div>
                <div id="impresionVentaContenido" style="text-align:left; padding-top:8px;">Cargando...</div>
            </div>
        `,
        showConfirmButton: false,
        showCloseButton: true,
        allowOutsideClick: false,
        width: '520px'
    });
    fetch(`/ventas/detalle/${ventaId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Error');
            const v = data.venta || {};
            const numero = v.numero_venta || '';
            const total = parseFloat(v.total || 0);
            const cont = document.getElementById('impresionVentaContenido');
            if (cont) {
                cont.innerHTML = `
                    <div style="color:#374151; margin-bottom: 6px;">Número de venta: <strong>${numero}</strong></div>
                    <div style="color:#374151; margin-bottom: 14px;">Total: <strong>S/. ${isNaN(total)?'0.00':total.toFixed(2)}</strong></div>
                    <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                        <button id="swalBoleta" style="padding:10px 14px; border-radius:10px; background:#dc2626; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                            <iconify-icon icon="mdi:file-document-outline" style="font-size:18px"></iconify-icon>
                            <span>Boleta</span>
                        </button>
                        <button id="swalTicket" style="padding:10px 14px; border-radius:10px; background:#2563eb; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                            <iconify-icon icon="mdi:receipt-outline" style="font-size:18px"></iconify-icon>
                            <span>Ticket</span>
                        </button>
                        <button id="swalWhatsApp" style="padding:10px 14px; border-radius:10px; background:#25d366; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">
                            <iconify-icon icon="mdi:whatsapp" style="font-size:18px"></iconify-icon>
                            <span>WhatsApp</span>
                        </button>
                    </div>
                `;
                const b = document.getElementById('swalBoleta');
                const t = document.getElementById('swalTicket');
                const w = document.getElementById('swalWhatsApp');
                if (b) b.addEventListener('click', () => {
                    if (window.pos && typeof window.pos.imprimirBoletaDirecta === 'function') {
                        window.pos.imprimirBoletaDirecta(ventaId);
                    } else {
                        imprimirBoletaDirecta(ventaId);
                    }
                });
                if (t) t.addEventListener('click', () => {
                    if (window.pos && typeof window.pos.imprimirTicketDirecta === 'function') {
                        window.pos.imprimirTicketDirecta(ventaId);
                    } else {
                        imprimirTicketDirecta(ventaId);
                    }
                });
                if (w) w.addEventListener('click', () => {
                    mostrarModalWhatsApp({ id: ventaId, total: total });
                });
            }
        })
        .catch(() => {
            const cont = document.getElementById('impresionVentaContenido');
            if (cont) {
                cont.innerHTML = `
                    <div style="color:#374151; margin-bottom: 16px;">Seleccione una opción de impresión:</div>
                    <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                        <button id="swalBoleta" style="padding:10px 14px; border-radius:8px; background:#dc2626; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">Boleta</button>
                        <button id="swalTicket" style="padding:10px 14px; border-radius:8px; background:#2563eb; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">Ticket</button>
                        <button id="swalWhatsApp" style="padding:10px 14px; border-radius:8px; background:#25d366; color:#fff; border:none; display:inline-flex; align-items:center; gap:8px; font-weight:600;">WhatsApp</button>
                    </div>
                `;
                const b = document.getElementById('swalBoleta');
                const t = document.getElementById('swalTicket');
                const w = document.getElementById('swalWhatsApp');
                if (b) b.addEventListener('click', () => imprimirBoletaDirecta(ventaId));
                if (t) t.addEventListener('click', () => imprimirTicketDirecta(ventaId));
                if (w) w.addEventListener('click', () => mostrarModalWhatsApp({ id: ventaId, total: 0 }));
            }
        });
}

function imprimirBoletaDirecta(ventaId) {
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
                window.open(iframe.src, '_blank');
            }
        };
    } catch (e) {
        window.open(`/punto-venta/pdf/${ventaId}`, '_blank');
    }
}

function imprimirTicketDirecta(ventaId) {
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
                window.open(iframe.src, '_blank');
            }
        };
    } catch (e) {
        window.open(`/punto-venta/ticket/${ventaId}`, '_blank');
    }
}

function mostrarModalWhatsApp(venta) {
    Swal.fire({
        title: '<i class="fab fa-whatsapp" style="color: #25d366;"></i> Enviar por WhatsApp',
        html: `
            <div style="text-align: left; padding: 10px;">
                <input type="hidden" id="whatsapp-formato" value="ticket" />
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Número de teléfono del cliente:</label>
                    <input type="tel" id="whatsapp-phone" class="swal2-input" placeholder="Ej: 987654321" style="margin: 0; width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;" maxlength="9" pattern="[0-9]{9}">
                    <small style="color: #666; font-size: 12px;">Ingrese solo los 9 dígitos (sin +51)</small>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #e9ecef;">
                    <h4 style="margin: 0 0 10px 0; color: #495057; font-size: 14px;"><i class="fas fa-info-circle" style="color: #17a2b8;"></i> Información de la venta</h4>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span style="color: #666;">Total:</span><span style="font-weight: 600; color: #333;">S/. ${parseFloat(venta.total || 0).toFixed(2)}</span></div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span style="color: #666;">Fecha:</span><span style="color: #333;">${new Date().toLocaleDateString('es-PE')}</span></div>
                    <div style="display: flex; justify-content: space-between;"><span style="color: #666;">Hora:</span><span style="color: #333;">${new Date().toLocaleTimeString('es-PE')}</span></div>
                    <div style="display: flex; justify-content: space-between; margin-top: 6px;"><span style="color: #666;">Comprobante:</span><span style="color: #333; font-weight:600;">Ticket térmico</span></div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fab fa-whatsapp"></i> Enviar WhatsApp',
        cancelButtonText: '<i class="fas fa-arrow-left"></i> Volver',
        confirmButtonColor: '#25d366',
        cancelButtonColor: '#6c757d',
        width: '450px',
        allowOutsideClick: false,
        buttonsStyling: false,
        preConfirm: () => {
            const phone = document.getElementById('whatsapp-phone').value.trim();
            if (!/^[0-9]{9}$/.test(phone)) {
                Swal.showValidationMessage('El número debe tener exactamente 9 dígitos');
                return false;
            }
            return phone;
        },
        didOpen: () => {
            const phoneInput = document.getElementById('whatsapp-phone');
            if (phoneInput) {
                phoneInput.focus();
                phoneInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);
                });
                phoneInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        document.querySelector('.swal2-confirm').click();
                    }
                });
            }
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
                    cancelBtn.style.backgroundColor = '#e5e7eb';
                    cancelBtn.style.color = '#111827';
                    cancelBtn.style.border = 'none';
                }
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            enviarWhatsApp(venta, result.value);
        }
    });
}

async function enviarWhatsApp(venta, telefono) {
    try {
        Swal.fire({ title: 'Enviando...', html: '<i class="fab fa-whatsapp fa-spin" style="font-size: 3em; color: #25d366;"></i><br><br>Preparando mensaje de WhatsApp...', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false });
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
                guardar_en_cliente: false
            })
        });
        const data = await response.json();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Listo para enviar!',
                html: `<div style="text-align: center; padding: 10px;"><p style="margin-bottom: 15px;">El mensaje está listo. Se abrirá WhatsApp en unos segundos...</p><div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin: 10px 0;"><small style="color: #666;">Número: +51 ${telefono}</small></div></div>`,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                window.open(data.url_whatsapp || data.whatsapp_url, '_blank');
            });
        } else {
            throw new Error(data.message || 'Error al preparar el mensaje');
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error al enviar', text: error.message || 'No se pudo preparar el mensaje de WhatsApp. Inténtelo nuevamente.', confirmButtonColor: '#dc3545' });
    }
}

window.mostrarModalImpresionHistorial = mostrarModalImpresionHistorial;
window.mostrarDetalleVenta = mostrarDetalleVenta;
window.imprimirBoletaDirecta = imprimirBoletaDirecta;
window.imprimirTicketDirecta = imprimirTicketDirecta;
window.mostrarModalWhatsApp = mostrarModalWhatsApp;
window.enviarWhatsApp = enviarWhatsApp;

console.log('✅ Historial de Ventas - JavaScript completamente cargado');
function configurarScrollHorizontalTabla() {
    const wrapper = document.querySelector('.historial-table-wrapper-improved .historial-hscroll');
    const track = document.querySelector('.historial-hscroll-track');
    const thumb = document.querySelector('.historial-hscroll-thumb');
    if (!wrapper || !track || !thumb) return;
    const updateThumb = () => {
        const scrollWidth = wrapper.scrollWidth;
        const clientWidth = wrapper.clientWidth;
        const maxScroll = scrollWidth - clientWidth;
        const ratio = clientWidth / scrollWidth;
        track.style.display = (scrollWidth > clientWidth) ? 'block' : 'none';
        thumb.style.width = Math.max(60, track.clientWidth * ratio) + 'px';
        const pos = (maxScroll > 0) ? (wrapper.scrollLeft / maxScroll) * (track.clientWidth - thumb.offsetWidth) : 0;
        thumb.style.left = pos + 'px';
    };
    wrapper.addEventListener('scroll', updateThumb);
    new ResizeObserver(updateThumb).observe(wrapper);
    window.addEventListener('resize', updateThumb);
    updateThumb();
    let dragging = false; let startX = 0; let startLeft = 0;
    thumb.addEventListener('mousedown', (e) => { dragging = true; startX = e.clientX; startLeft = parseFloat(thumb.style.left || '0'); e.preventDefault(); });
    window.addEventListener('mouseup', () => { dragging = false; });
    window.addEventListener('mousemove', (e) => {
        if (!dragging) return;
        const delta = e.clientX - startX;
        const trackWidth = track.clientWidth - thumb.offsetWidth;
        let newLeft = Math.max(0, Math.min(trackWidth, startLeft + delta));
        thumb.style.left = newLeft + 'px';
        const maxScroll = wrapper.scrollWidth - wrapper.clientWidth;
        const scrollPos = (trackWidth > 0) ? (newLeft / trackWidth) * maxScroll : 0;
        wrapper.scrollLeft = scrollPos;
    });
}
