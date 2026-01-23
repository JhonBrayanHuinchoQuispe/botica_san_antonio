/**
 * ========================================
 * GESTOR DE PRESENTACIONES MÚLTIPLES
 * Sistema profesional para productos con diferentes presentaciones
 * ========================================
 */

class GestorPresentaciones {
    constructor(productoId = null) {
        this.productoId = productoId;
        this.presentaciones = [];
        this.init();
    }

    init() {
        this.cargarPresentaciones();
        this.setupEventListeners();
    }

    /**
     * Cargar presentaciones existentes del producto
     */
    async cargarPresentaciones() {
        if (!this.productoId) {
            this.renderPresentacionesVacias();
            return;
        }

        try {
            const response = await fetch(`/inventario/producto/presentaciones/api?producto_id=${this.productoId}`);
            const data = await response.json();

            if (data.success) {
                this.presentaciones = data.data;
                this.renderPresentaciones();
            }
        } catch (error) {
            console.error('Error cargando presentaciones:', error);
            this.mostrarError('Error al cargar presentaciones');
        }
    }

    /**
     * Configurar eventos
     */
    setupEventListeners() {
        // Botón agregar presentación
        document.getElementById('btnAgregarPresentacion')?.addEventListener('click', () => {
            this.mostrarModalPresentacion();
        });

        // Calcular precio sugerido al cambiar unidades
        document.getElementById('unidadesPresForm')?.addEventListener('input', (e) => {
            this.calcularPrecioSugerido();
        });

        // Guardar presentación
        document.getElementById('btnGuardarPresentacion')?.addEventListener('click', () => {
            this.guardarPresentacion();
        });
    }

    /**
     * Render de presentaciones vacías (modo creación de producto)
     */
    renderPresentacionesVacias() {
        const container = document.getElementById('listaPresentaciones');
        if (!container) return;

        container.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <p><strong>Presentaciones por defecto</strong></p>
                <p>Al crear el producto, se generará automáticamente la presentación "Unidad" con el precio ingresado.</p>
                <p>Podrás agregar más presentaciones (Blíster, Caja, etc.) después de guardar el producto.</p>
            </div>
        `;
    }

    /**
     * Renderizar lista de presentaciones
     */
    renderPresentaciones() {
        const container = document.getElementById('listaPresentaciones');
        if (!container) return;

        if (this.presentaciones.length === 0) {
            this.renderPresentacionesVacias();
            return;
        }

        let html = '<div class="presentaciones-grid">';

        this.presentaciones.forEach(pres => {
            const badge = pres.es_presentacion_base 
                ? '<span class="badge badge-primary">Base</span>' 
                : '';
            
            const fraccionable = pres.permite_fraccionamiento 
                ? '<i class="fas fa-cut text-success" title="Permite fraccionamiento"></i>' 
                : '';

            const ahorro = this.calcularAhorro(pres);
            const ahorroHtml = ahorro > 0 
                ? `<div class="ahorro-badge">Ahorra S/ ${ahorro.toFixed(2)}</div>` 
                : '';

            html += `
                <div class="card presentacion-card" data-id="${pres.id}">
                    <div class="card-header">
                        <h5>${pres.nombre_presentacion} ${badge}</h5>
                        <div class="card-actions">
                            <button class="btn btn-sm btn-primary" onclick="gestorPresentaciones.editarPresentacion(${pres.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${!pres.es_presentacion_base ? `
                            <button class="btn btn-sm btn-danger" onclick="gestorPresentaciones.eliminarPresentacion(${pres.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                    </div>
                    <div class="card-body">
                        ${ahorroHtml}
                        <div class="pres-info">
                            <div class="info-row">
                                <span class="label">Unidades:</span>
                                <span class="value"><strong>${pres.unidades_por_presentacion}</strong> ${fraccionable}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Precio:</span>
                                <span class="value precio">S/ ${pres.precio_venta}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Precio/unidad:</span>
                                <span class="value">S/ ${pres.precio_unitario}</span>
                            </div>
                            <div class="info-row">
                                <span class="label">Stock disponible:</span>
                                <span class="value stock">${pres.stock_disponible} ${pres.nombre_presentacion.toLowerCase()}s</span>
                            </div>
                            ${pres.codigo_barras ? `
                            <div class="info-row">
                                <span class="label">Código:</span>
                                <span class="value"><code>${pres.codigo_barras}</code></span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    /**
     * Calcular ahorro de una presentación
     */
    calcularAhorro(presentacion) {
        if (presentacion.es_presentacion_base || presentacion.unidades_por_presentacion <= 1) {
            return 0;
        }

        // Buscar la presentación base
        const base = this.presentaciones.find(p => p.es_presentacion_base);
        if (!base) return 0;

        const precioSiCompraIndividual = parseFloat(base.precio_venta_raw) * presentacion.unidades_por_presentacion;
        const precioReal = parseFloat(presentacion.precio_venta_raw);
        
        return precioSiCompraIndividual - precioReal;
    }

    /**
     * Mostrar modal para agregar/editar presentación
     */
    mostrarModalPresentacion(presentacionId = null) {
        const modal = document.getElementById('modalPresentacion');
        const form = document.getElementById('formPresentacion');
        
        if (!modal || !form) {
            console.error('Modal o formulario no encontrado');
            return;
        }

        // Resetear formulario
        form.reset();
        document.getElementById('presentacionId').value = '';
        document.getElementById('modalPresentacionTitle').textContent = 'Agregar Presentación';

        // Si es edición, cargar datos
        if (presentacionId) {
            this.cargarDatosPresentacion(presentacionId);
            document.getElementById('modalPresentacionTitle').textContent = 'Editar Presentación';
        }

        // Mostrar modal (Bootstrap)
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    /**
     * Cargar datos de presentación para edición
     */
    async cargarDatosPresentacion(id) {
        try {
            const response = await fetch(`/inventario/producto/presentaciones/api/${id}`);
            const data = await response.json();

            if (data.success) {
                const pres = data.data;
                document.getElementById('presentacionId').value = pres.id;
                document.getElementById('nombrePresForm').value = pres.nombre_presentacion;
                document.getElementById('unidadesPresForm').value = pres.unidades_por_presentacion;
                document.getElementById('precioPresForm').value = pres.precio_venta;
                document.getElementById('codigoBarrasPresForm').value = pres.codigo_barras_presentacion || '';
                document.getElementById('fraccionamientoPresForm').checked = pres.permite_fraccionamiento;
            }
        } catch (error) {
            console.error('Error cargando presentación:', error);
            this.mostrarError('Error al cargar datos de la presentación');
        }
    }

    /**
     * Calcular precio sugerido basado en unidades
     */
    async calcularPrecioSugerido() {
        if (!this.productoId) return;

        const unidades = document.getElementById('unidadesPresForm')?.value;
        if (!unidades || unidades <= 0) return;

        try {
            const response = await fetch(`/inventario/producto/presentaciones/api/precio-sugerido`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    producto_id: this.productoId,
                    unidades: unidades
                })
            });

            const data = await response.json();

            if (data.success) {
                const precioInput = document.getElementById('precioPresForm');
                const sugerenciaDiv = document.getElementById('precioSugerido');

                if (sugerenciaDiv) {
                    sugerenciaDiv.innerHTML = `
                        <div class="alert alert-info">
                            <strong>💡 Precio sugerido:</strong> S/ ${data.data.precio_sugerido}
                            <br>
                            <small>
                                Descuento: ${data.data.descuento_porcentaje}% 
                                (S/ ${data.data.descuento_monto})
                            </small>
                            <br>
                            <button type="button" class="btn btn-sm btn-success mt-2" onclick="document.getElementById('precioPresForm').value = ${data.data.precio_sugerido}">
                                Usar precio sugerido
                            </button>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('Error calculando precio:', error);
        }
    }

    /**
     * Guardar presentación (crear o actualizar)
     */
    async guardarPresentacion() {
        const form = document.getElementById('formPresentacion');
        if (!form) return;

        const presentacionId = document.getElementById('presentacionId').value;
        const datos = {
            producto_id: this.productoId,
            nombre_presentacion: document.getElementById('nombrePresForm').value,
            unidades_por_presentacion: document.getElementById('unidadesPresForm').value,
            precio_venta: document.getElementById('precioPresForm').value,
            codigo_barras_presentacion: document.getElementById('codigoBarrasPresForm').value || null,
            permite_fraccionamiento: document.getElementById('fraccionamientoPresForm').checked
        };

        // Validación básica
        if (!datos.nombre_presentacion || !datos.unidades_por_presentacion || !datos.precio_venta) {
            this.mostrarError('Por favor complete todos los campos obligatorios');
            return;
        }

        try {
            const url = presentacionId 
                ? `/inventario/producto/presentaciones/api/${presentacionId}`
                : `/inventario/producto/presentaciones/api`;
            
            const method = presentacionId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(datos)
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarExito(data.message);
                this.cerrarModal();
                this.cargarPresentaciones(); // Recargar lista
            } else {
                this.mostrarError(data.message || 'Error al guardar la presentación');
            }
        } catch (error) {
            console.error('Error guardando presentación:', error);
            this.mostrarError('Error al guardar la presentación');
        }
    }

    /**
     * Editar presentación
     */
    editarPresentacion(id) {
        this.mostrarModalPresentacion(id);
    }

    /**
     * Eliminar presentación
     */
    async eliminarPresentacion(id) {
        if (!confirm('¿Está seguro de eliminar esta presentación?')) {
            return;
        }

        try {
            const response = await fetch(`/inventario/producto/presentaciones/api/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarExito('Presentación eliminada exitosamente');
                this.cargarPresentaciones();
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error eliminando presentación:', error);
            this.mostrarError('Error al eliminar la presentación');
        }
    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        const modal = document.getElementById('modalPresentacion');
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }
    }

    /**
     * Mostrar mensaje de error
     */
    mostrarError(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje,
                confirmButtonColor: '#dc3545'
            });
        } else {
            alert(mensaje);
        }
    }

    /**
     * Mostrar mensaje de éxito
     */
    mostrarExito(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: mensaje,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            alert(mensaje);
        }
    }
}

// Instancia global
let gestorPresentaciones = null;

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', () => {
    const productoIdElement = document.getElementById('productoIdForPresentaciones');
    if (productoIdElement) {
        const productoId = productoIdElement.value || null;
        gestorPresentaciones = new GestorPresentaciones(productoId);
    }
});
