/**
 * 🏢 GESTIÓN DE PROVEEDORES - BOTICA SAN ANTONIO
 * JavaScript para manejo completo de proveedores
 */

class ProveedoresManager {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.verificarSweetAlert();
        this.mostrarSkeletonInicial();
        console.log('✅ ProveedoresManager iniciado correctamente');
    }
    
    setupEventListeners() {
        // Filtros en tiempo real
        const searchInput = document.getElementById('buscarProveedor');
        if (searchInput) {
            searchInput.addEventListener('input', () => this.aplicarFiltros());
        }
        
        const estadoSelect = document.getElementById('filtroEstado');
        if (estadoSelect) {
            estadoSelect.addEventListener('change', () => this.aplicarFiltros());
        }

        // Toggle de estado (activo/inactivo)
        document.addEventListener('change', (e) => {
            const toggle = e.target.closest('.proveedor-status-toggle');
            if (!toggle) return;
            const proveedorId = toggle.dataset.proveedorId;
            if (!proveedorId) return;
            // Bloquear mientras procesa
            toggle.disabled = true;
            const fila = document.querySelector(`.proveedores-data-row[data-proveedor-id="${proveedorId}"]`);
            const estadoPrevio = toggle.checked ? 'inactivo' : 'activo'; // antes del cambio
            this.toggleEstadoProveedor(proveedorId, toggle, fila, estadoPrevio);
        });
    }
    
    verificarSweetAlert() {
        if (typeof Swal === 'undefined') {
            console.error('❌ SweetAlert2 no está cargado');
            alert('Error: SweetAlert2 no está disponible. Recarga la página.');
            return false;
        }
        console.log('✅ SweetAlert2 disponible');
        return true;
    }
    
    mostrarSkeletonInicial() {
        // Mostrar skeleton mientras se cargan los datos desde el servidor
        const skeleton = document.getElementById('proveedoresSkeleton');
        const tbody = document.querySelector('#tablaProveedores tbody');
        
        if (skeleton && tbody) {
            skeleton.style.display = 'block';
            tbody.style.display = 'none';
            
            // Simular tiempo de carga y luego mostrar los datos reales
            setTimeout(() => {
                skeleton.style.display = 'none';
                tbody.style.display = '';
            }, 200); // Reducido de 800ms a 200ms para mayor velocidad
        }
    }
    
    /**
     * Abrir modal para agregar nuevo proveedor
     */
    abrirModalAgregar() {
        if (!this.verificarSweetAlert()) return;
        
        Swal.fire({
            title: '<div style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 1rem; margin: -1rem -1rem 1rem -1rem; border-radius: 8px;"><i class="fas fa-plus-circle"></i> Nuevo Proveedor</div>',
            html: `
                <div style="text-align: left; padding: 1rem;">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-building" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                            Razón Social *
                        </label>
                        <input type="text" id="new_razon_social" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;" placeholder="Ej: Distribuidora Médica S.A.C.">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-store" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                            Nombre Comercial
                        </label>
                        <input type="text" id="new_nombre_comercial" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;" placeholder="Ej: Dismesa">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-id-card" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                            RUC
                        </label>
                        <input type="text" id="new_ruc" maxlength="11" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;" placeholder="20123456789">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-phone" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                            Teléfono
                        </label>
                        <input type="text" id="new_telefono" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;" placeholder="01-1234567">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-envelope" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                            Email
                        </label>
                        <input type="email" id="new_email" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;" placeholder="contacto@empresa.com">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-map-marker-alt" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                            Dirección
                        </label>
                        <textarea id="new_direccion" rows="2" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem; resize: none;" placeholder="Dirección completa"></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: false,
            showCloseButton: false,
            confirmButtonText: 'Guardar Proveedor',
            cancelButtonText: 'Cancelar',
            denyButtonText: '',
            width: '600px',
            customClass: {
                confirmButton: 'swal2-confirm-blue',
                cancelButton: 'swal2-cancel-gray'
            },
            buttonsStyling: false,
            didOpen: () => {
                // Eliminar botones no deseados
                setTimeout(() => {
                    const buttons = document.querySelectorAll('.swal2-deny, .swal2-close');
                    buttons.forEach(btn => btn.remove());
                }, 100);
                
                const style = document.createElement('style');
                style.textContent = `
                    .swal2-deny, .swal2-close {
                        display: none !important;
                        visibility: hidden !important;
                    }
                    .swal2-confirm-blue {
                        background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
                        color: white !important;
                        border: none !important;
                        padding: 0.75rem 1.5rem !important;
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        margin: 0 0.5rem !important;
                    }
                    .swal2-cancel-gray {
                        background: linear-gradient(135deg, #6b7280, #4b5563) !important;
                        color: white !important;
                        border: none !important;
                        padding: 0.75rem 1.5rem !important;
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        margin: 0 0.5rem !important;
                    }
                `;
                document.head.appendChild(style);
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.guardarProveedor();
            }
        });
    }
    
    /**
     * Guardar nuevo proveedor
     */
    guardarProveedor() {
        const formData = {
            razon_social: document.getElementById('prov_razon_social')?.value.trim() || '',
            nombre_comercial: document.getElementById('prov_nombre_comercial')?.value.trim() || '',
            ruc: document.getElementById('prov_ruc')?.value.trim() || '',
            telefono: document.getElementById('prov_telefono')?.value.trim() || '',
            email: document.getElementById('prov_email')?.value.trim() || '',
            direccion: document.getElementById('prov_direccion')?.value.trim() || ''
        };
        
        // Validaciones
        if (!formData.razon_social) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'La razón social es obligatoria'
            });
            return;
        }
        
        if (formData.ruc && (formData.ruc.length !== 11 || !/^\d+$/.test(formData.ruc))) {
            Swal.fire({
                icon: 'error',
                title: 'RUC inválido',
                text: 'El RUC debe tener exactamente 11 dígitos numéricos'
            });
            return;
        }
        
        // Ocultar el modal mientras se procesa
        try { closeProveedorModal(); } catch(e) {}
        // Mostrar loading
        Swal.fire({
            title: 'Guardando proveedor...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });
        
        // Enviar datos
        fetch('/compras/proveedores/guardar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Iniciar recarga inmediatamente mientras se muestra el mensaje
                if (data.data) {
                    this.agregarProveedorATabla(data.data);
                } else {
                    this.recargarTablaProveedores(); // Recarga inmediata
                }
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Proveedor guardado!',
                    text: `${formData.razon_social} ha sido registrado exitosamente`,
                    timer: 1500, // Reducido de 2000ms a 1500ms
                    showConfirmButton: false
                });
            } else {
                let errorMessage = 'Error al guardar el proveedor';
                if (data.errors) {
                    errorMessage = Object.values(data.errors).flat().join('\n');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error al guardar',
                    text: errorMessage
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
        });
    }
    
    /**
     * Ver detalles del proveedor
     */
    verProveedor(id) {
        if (!this.verificarSweetAlert()) return;
        
        Swal.fire({
            title: 'Cargando información...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });
        
        fetch(`/api/compras/proveedor/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const proveedor = data.data;
                Swal.fire({
                    title: '<div style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 1rem; margin: -1rem -1rem 1rem -1rem; border-radius: 8px;"><i class="fas fa-eye"></i> Información del Proveedor</div>',
                    html: `
                        <div style="text-align: left; padding: 1.5rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label style="font-weight: 600; color: #374151; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">
                                        <i class="fas fa-building" style="margin-right: 0.5rem; color: #10b981;"></i>
                                        Razón Social
                                    </label>
                                    <div style="background: #f9fafb; padding: 0.75rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                                        ${proveedor.razon_social || 'No especificado'}
                                    </div>
                                </div>
                                <div>
                                    <label style="font-weight: 600; color: #374151; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">
                                        <i class="fas fa-store" style="margin-right: 0.5rem; color: #10b981;"></i>
                                        Nombre Comercial
                                    </label>
                                    <div style="background: #f9fafb; padding: 0.75rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                                        ${proveedor.nombre_comercial || 'No especificado'}
                                    </div>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label style="font-weight: 600; color: #374151; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">
                                        <i class="fas fa-id-card" style="margin-right: 0.5rem; color: #10b981;"></i>
                                        RUC
                                    </label>
                                    <div style="background: #f9fafb; padding: 0.75rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                                        ${proveedor.ruc || 'No especificado'}
                                    </div>
                                </div>
                                <div>
                                    <label style="font-weight: 600; color: #374151; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">
                                        <i class="fas fa-toggle-${proveedor.estado === 'activo' ? 'on' : 'off'}" style="margin-right: 0.5rem; color: ${proveedor.estado === 'activo' ? '#10b981' : '#ef4444'};"></i>
                                        Estado
                                    </label>
                                    <div style="background: #f9fafb; padding: 0.75rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                                        <span style="color: ${proveedor.estado === 'activo' ? '#10b981' : '#ef4444'}; font-weight: 600;">
                                            ${proveedor.estado === 'activo' ? 'Activo' : 'Inactivo'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label style="font-weight: 600; color: #374151; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">
                                        <i class="fas fa-phone" style="margin-right: 0.5rem; color: #10b981;"></i>
                                        Teléfono
                                    </label>
                                    <div style="background: #f9fafb; padding: 0.75rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                                        ${proveedor.telefono || 'No especificado'}
                                    </div>
                                </div>
                                <div>
                                    <label style="font-weight: 600; color: #374151; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">
                                        <i class="fas fa-envelope" style="margin-right: 0.5rem; color: #10b981;"></i>
                                        Email
                                    </label>
                                    <div style="background: #f9fafb; padding: 0.75rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                                        ${proveedor.email || 'No especificado'}
                                    </div>
                                </div>
                            </div>
                            <div style="margin-bottom: 1.5rem;">
                                <label style="font-weight: 600; color: #374151; font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">
                                    <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: #10b981;"></i>
                                    Dirección
                                </label>
                                <div style="background: #f9fafb; padding: 0.75rem; border-radius: 8px; border: 1px solid #e5e7eb;">
                                    ${proveedor.direccion || 'No especificado'}
                                </div>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'Cerrar',
                    width: '700px',
                    showCancelButton: false,
                    showDenyButton: false,
                    showCloseButton: false,
                    cancelButtonText: '',
                    denyButtonText: '',
                    customClass: {
                        confirmButton: 'swal2-confirm-green'
                    },
                    buttonsStyling: false,
                    didOpen: () => {
                        // Eliminar botones no deseados
                        setTimeout(() => {
                            const buttons = document.querySelectorAll('.swal2-deny, .swal2-close, .swal2-cancel');
                            buttons.forEach(btn => btn.remove());
                        }, 100);
                        
                        const style = document.createElement('style');
                        style.textContent = `
                            .swal2-deny, .swal2-close, .swal2-cancel {
                                display: none !important;
                                visibility: hidden !important;
                            }
                            .swal2-confirm-green {
                                background: linear-gradient(135deg, #10b981, #059669) !important;
                                color: white !important;
                                border: none !important;
                                padding: 0.75rem 1.5rem !important;
                                border-radius: 8px !important;
                                font-weight: 600 !important;
                            }
                        `;
                        document.head.appendChild(style);
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar la información del proveedor'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
        });
    }
    
    /**
     * Editar proveedor
     */
    editarProveedor(id) {
        if (!this.verificarSweetAlert()) return;
        
        Swal.fire({
            title: 'Cargando información...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });
        
        fetch(`/api/compras/proveedor/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const proveedor = data.data;
                this.mostrarModalEditar(proveedor);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar la información del proveedor'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
        });
    }
    
    /**
     * Mostrar modal de edición
     */
    mostrarModalEditar(proveedor) {
        Swal.fire({
            title: '<div style="background: linear-gradient(135deg, #e53e3e, #dc2626); color: white; padding: 1rem; margin: -1rem -1rem 1rem -1rem; border-radius: 8px;"><i class="fas fa-edit"></i> Editar Proveedor</div>',
            html: `
                <div style="text-align: left; padding: 1rem;">
                    <input type="hidden" id="edit_proveedor_id" value="${proveedor.id}">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-building" style="color: #e53e3e; margin-right: 0.5rem;"></i>
                            Razón Social *
                        </label>
                        <input type="text" id="edit_razon_social" value="${proveedor.razon_social || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-store" style="color: #e53e3e; margin-right: 0.5rem;"></i>
                            Nombre Comercial
                        </label>
                        <input type="text" id="edit_nombre_comercial" value="${proveedor.nombre_comercial || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-id-card" style="color: #e53e3e; margin-right: 0.5rem;"></i>
                            RUC
                        </label>
                        <input type="text" id="edit_ruc" value="${proveedor.ruc || ''}" maxlength="11" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-phone" style="color: #e53e3e; margin-right: 0.5rem;"></i>
                            Teléfono
                        </label>
                        <input type="text" id="edit_telefono" value="${proveedor.telefono || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-envelope" style="color: #e53e3e; margin-right: 0.5rem;"></i>
                            Email
                        </label>
                        <input type="email" id="edit_email" value="${proveedor.email || ''}" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">
                            <i class="fas fa-map-marker-alt" style="color: #e53e3e; margin-right: 0.5rem;"></i>
                            Dirección
                        </label>
                        <textarea id="edit_direccion" rows="2" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem; resize: none;">${proveedor.direccion || ''}</textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: false,
            showCloseButton: false,
            confirmButtonText: 'Guardar Cambios',
            cancelButtonText: 'Cancelar',
            denyButtonText: '',
            width: '600px',
            customClass: {
                confirmButton: 'swal2-confirm-red',
                cancelButton: 'swal2-cancel-gray'
            },
            buttonsStyling: false,
            allowOutsideClick: false,
            didOpen: () => {
                // Eliminar botones no deseados
                setTimeout(() => {
                    const buttons = document.querySelectorAll('.swal2-deny, .swal2-close');
                    buttons.forEach(btn => btn.remove());
                }, 100);
                
                const style = document.createElement('style');
                style.textContent = `
                    .swal2-deny, .swal2-close {
                        display: none !important;
                        visibility: hidden !important;
                    }
                    .swal2-confirm-red {
                        background: linear-gradient(135deg, #e53e3e, #dc2626) !important;
                        color: white !important;
                        border: none !important;
                        padding: 0.75rem 1.5rem !important;
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        margin: 0 0.5rem !important;
                    }
                    .swal2-cancel-gray {
                        background: linear-gradient(135deg, #6b7280, #4b5563) !important;
                        color: white !important;
                        border: none !important;
                        padding: 0.75rem 1.5rem !important;
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        margin: 0 0.5rem !important;
                    }
                `;
                document.head.appendChild(style);
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.actualizarProveedor();
            }
        });
    }
    
    /**
     * Actualizar proveedor
     */
    actualizarProveedor() {
        const id = document.getElementById('prov_id')?.value;
        const formData = {
            razon_social: document.getElementById('prov_razon_social')?.value.trim(),
            nombre_comercial: document.getElementById('prov_nombre_comercial')?.value.trim(),
            ruc: document.getElementById('prov_ruc')?.value.trim(),
            telefono: document.getElementById('prov_telefono')?.value.trim(),
            email: document.getElementById('prov_email')?.value.trim(),
            direccion: document.getElementById('prov_direccion')?.value.trim()
        };
        
        // Validaciones
        if (!formData.razon_social) {
            Swal.fire({
                icon: 'error',
                title: 'Campo requerido',
                text: 'La razón social es obligatoria'
            });
            return;
        }
        
        if (formData.ruc && (formData.ruc.length !== 11 || !/^\d+$/.test(formData.ruc))) {
            Swal.fire({
                icon: 'error',
                title: 'RUC inválido',
                text: 'El RUC debe tener exactamente 11 dígitos numéricos'
            });
            return;
        }
        
        // Ocultar el modal mientras se procesa
        try { closeProveedorModal(); } catch(e) {}
        // Mostrar loading
        Swal.fire({
            title: 'Actualizando proveedor...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });
        
        // Enviar datos
        fetch(`/compras/proveedores/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Iniciar actualización inmediatamente mientras se muestra el mensaje
                if (data.data) {
                    this.actualizarProveedorEnTabla(id, data.data);
                } else {
                    this.recargarTablaProveedores(); // Recarga inmediata
                }
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Proveedor actualizado!',
                    text: `${formData.razon_social} ha sido actualizado exitosamente`,
                    timer: 1500, // Reducido de 2000ms a 1500ms
                    showConfirmButton: false
                });
            } else {
                let errorMessage = 'Error al actualizar el proveedor';
                if (data.errors) {
                    errorMessage = Object.values(data.errors).flat().join('\n');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error al actualizar',
                    text: errorMessage
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
        });
    }
    
    /**
     * Cambiar estado del proveedor
     */
    cambiarEstado(id, nuevoEstado) {
        if (!this.verificarSweetAlert()) return;
        
        const accion = nuevoEstado === 'activo' ? 'activar' : 'desactivar';
        const colorHeader = nuevoEstado === 'activo' ? '#10b981' : '#e53e3e';
        const colorSecondary = nuevoEstado === 'activo' ? '#059669' : '#dc2626';
        const icono = nuevoEstado === 'activo' ? 'fas fa-check-circle' : 'fas fa-times-circle';
        const titulo = nuevoEstado === 'activo' ? 'Activar Proveedor' : 'Desactivar Proveedor';
        
        Swal.fire({
            title: `<div style="background: linear-gradient(135deg, ${colorHeader}, ${colorSecondary}); color: white; padding: 1rem; margin: -1rem -1rem 1rem -1rem; border-radius: 8px;"><i class="${icono}"></i> ${titulo}</div>`,
            html: `
                <div style="padding: 1rem;">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <p style="color: #374151; font-size: 1.1rem; margin-bottom: 1.5rem; font-weight: 500;">
                            ¿Está seguro que desea <strong style="color: ${colorHeader};">${accion}</strong> este proveedor?
                        </p>
                        <div style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); padding: 1.5rem; border-radius: 12px; border-left: 4px solid ${colorHeader};">
                            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                <i class="fas fa-info-circle" style="color: ${colorHeader}; font-size: 1.25rem; margin-top: 0.125rem;"></i>
                                <div style="text-align: left;">
                                    <h4 style="color: #374151; margin: 0 0 0.5rem 0; font-size: 0.95rem; font-weight: 600;">
                                        ${nuevoEstado === 'activo' ? 'Consecuencias de activar:' : 'Consecuencias de desactivar:'}
                                    </h4>
                                    <p style="color: #6b7280; font-size: 0.875rem; margin: 0; line-height: 1.5;">
                                        ${nuevoEstado === 'activo' 
                                            ? '• El proveedor estará disponible para nuevas compras<br>• Aparecerá en todas las listas de selección<br>• Podrá recibir órdenes de compra' 
                                            : '• No podrá realizar nuevas compras con este proveedor<br>• Se ocultará de las listas de selección<br>• Se mantendrá todo el historial de compras previas'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: false,
            showCloseButton: false,
            confirmButtonText: titulo,
            cancelButtonText: 'Cancelar',
            denyButtonText: '',
            width: '520px',
            customClass: {
                confirmButton: 'swal2-confirm-estado',
                cancelButton: 'swal2-cancel-gray'
            },
            buttonsStyling: false,
            allowOutsideClick: false,
            didOpen: () => {
                // Eliminar botones no deseados
                setTimeout(() => {
                    const buttons = document.querySelectorAll('.swal2-deny, .swal2-close');
                    buttons.forEach(btn => btn.remove());
                }, 100);
                
                const style = document.createElement('style');
                style.textContent = `
                    .swal2-deny, .swal2-close {
                        display: none !important;
                        visibility: hidden !important;
                    }
                    .swal2-confirm-estado {
                        background: linear-gradient(135deg, ${colorHeader}, ${colorSecondary}) !important;
                        color: white !important;
                        border: none !important;
                        padding: 0.75rem 1.5rem !important;
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        margin: 0 0.5rem !important;
                    }
                    .swal2-cancel-gray {
                        background: linear-gradient(135deg, #6b7280, #4b5563) !important;
                        color: white !important;
                        border: none !important;
                        padding: 0.75rem 1.5rem !important;
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                        margin: 0 0.5rem !important;
                    }
                `;
                document.head.appendChild(style);
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: `${accion.charAt(0).toUpperCase() + accion.slice(1)}ando proveedor...`,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });
                
                // Enviar cambio de estado
                fetch(`/compras/proveedores/${id}/cambiar-estado`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: `¡Proveedor ${nuevoEstado === 'activo' ? 'activado' : 'desactivado'}!`,
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al cambiar el estado'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor'
                    });
                });
            }
        });
    }

    /**
     * Toggle rápido de estado desde el switch
     */
    toggleEstadoProveedor(id, toggleEl, filaEl, estadoPrevio) {
        fetch(`/compras/proveedores/${id}/cambiar-estado`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'No se pudo cambiar estado');
            const nuevoEstado = (data.data && data.data.estado) ? data.data.estado : (toggleEl.checked ? 'activo' : 'inactivo');
            // Ajustar toggle según backend
            toggleEl.checked = nuevoEstado === 'activo';
            // Actualizar badge
            if (filaEl) {
                const estadoBadge = filaEl.querySelector('.estado-badge');
                if (estadoBadge) {
                    if (nuevoEstado === 'activo') {
                        estadoBadge.classList.remove('proveedores-badge-secondary');
                        estadoBadge.classList.add('proveedores-badge-success');
                        estadoBadge.innerHTML = '<iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Activo';
                        filaEl.classList.remove('opacity-75');
                    } else {
                        estadoBadge.classList.remove('proveedores-badge-success');
                        estadoBadge.classList.add('proveedores-badge-secondary');
                        estadoBadge.innerHTML = '<iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon> Inactivo';
                        filaEl.classList.add('opacity-75');
                    }
                }
            }
        })
        .catch(err => {
            console.error(err);
            // Revertir toggle
            toggleEl.checked = (estadoPrevio === 'activo');
            Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'No se pudo cambiar estado del proveedor' });
        })
        .finally(() => {
            toggleEl.disabled = false;
        });
    }
    
    /**
     * Eliminar proveedor
     */
    eliminarProveedor(id) {
        if (!this.verificarSweetAlert()) return;
        
        // Obtener datos del proveedor desde la fila de la tabla
        const fila = document.querySelector(`tr.proveedores-data-row[data-proveedor-id="${id}"]`);
        if (!fila) {
            console.error('No se encontró la fila del proveedor');
            return;
        }
        
        const razonSocial = fila.querySelector('td:nth-child(2)')?.textContent.trim() || '';
        const ruc = fila.querySelector('td:nth-child(3)')?.textContent.trim() || '';
        const contactoCell = fila.querySelector('td:nth-child(4)');
        let telefono = '';
        let email = '';
        if (contactoCell) {
            const items = contactoCell.querySelectorAll('.proveedores-contact-item');
            if (items[0]) telefono = items[0].textContent.trim();
            if (items[1]) email = items[1].textContent.trim();
        }
        
        Swal.fire({
            title: '¿Eliminar proveedor?',
            html: `
                <div style="text-align: left; padding: 1rem;">
                    <p style="margin-bottom: 1rem; color: #dc2626; font-weight: 600;">
                        Esta acción eliminará permanentemente el proveedor y no se puede deshacer.
                    </p>
                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <p style="margin: 0.25rem 0;"><strong>Razón Social:</strong> ${razonSocial}</p>
                        ${ruc ? `<p style=\"margin: 0.25rem 0;\"><strong>RUC:</strong> ${ruc}</p>` : ''}
                        ${telefono ? `<p style=\"margin: 0.25rem 0;\"><strong>Teléfono:</strong> ${telefono}</p>` : ''}
                        ${email ? `<p style=\"margin: 0.25rem 0;\"><strong>Email:</strong> ${email}</p>` : ''}
                        ${!telefono && !email ? `<p style=\"margin: 0.25rem 0; color:#6b7280;\"><strong>Contacto:</strong> Sin contacto</p>` : ''}
                    </div>
                    <p style="margin-top: 1rem; color: #6b7280; font-size: 0.9rem;">
                        Si el proveedor tiene registros asociados, no podrá ser eliminado.
                    </p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            showDenyButton: false,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            buttonsStyling: false,
            didOpen: () => {
                const c = Swal.getConfirmButton();
                const k = Swal.getCancelButton();
                const d = Swal.getDenyButton();
                // Eliminar el botón "No" si por alguna configuración global aparece
                if (d) { d.remove(); }
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
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loader
                Swal.fire({
                    title: 'Eliminando proveedor...',
                    html: '<div class="spinner-border text-danger" role="status"><span class="sr-only">Cargando...</span></div>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'swal2-popup-custom'
                    }
                });
                
                // Realizar petición DELETE
                fetch(`/compras/proveedores/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recargar tabla inmediatamente mientras se muestra el mensaje
                        this.recargarTablaProveedores();
                        
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: data.message,
                            icon: 'success',
                            timer: 1500, // Reducido de 2000ms a 1500ms
                            showConfirmButton: false,
                            customClass: {
                                popup: 'swal2-popup-custom'
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message,
                            icon: 'error',
                            confirmButtonText: 'Entendido',
                            customClass: {
                                popup: 'swal2-popup-custom',
                                confirmButton: 'swal2-confirm-custom'
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un error al eliminar el proveedor',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        customClass: {
                            popup: 'swal2-popup-custom',
                            confirmButton: 'swal2-confirm-custom'
                        }
                    });
                });
            }
        });
    }
    
    /**
     * Aplicar filtros de búsqueda y estado
     */
    aplicarFiltros() {
        const searchTerm = document.getElementById('buscarProveedor').value.toLowerCase();
        const selectedEstado = document.getElementById('filtroEstado').value;
        const dataRows = document.querySelectorAll('#tablaProveedores tbody tr.proveedores-data-row');
        const noResultsRow = document.getElementById('noResultsRow');
        const noResultsText = document.getElementById('noResultsText');
        
        let visibleRows = 0;
        let filtroAplicado = false;
        
        dataRows.forEach(row => {
            let mostrarFila = true;
            
            if (searchTerm) {
                filtroAplicado = true;
                const text = row.textContent.toLowerCase();
                if (!text.includes(searchTerm)) {
                    mostrarFila = false;
                }
            }
            
            if (selectedEstado && mostrarFila) {
                filtroAplicado = true;
                const estadoCell = row.querySelector('td:nth-child(5)');
                if (estadoCell) {
                    const estadoText = estadoCell.textContent.toLowerCase();
                    if (selectedEstado === 'activo' && !estadoText.includes('activo')) {
                        mostrarFila = false;
                    } else if (selectedEstado === 'inactivo' && !estadoText.includes('inactivo')) {
                        mostrarFila = false;
                    }
                }
            }
            
            if (mostrarFila) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });
        
        if (visibleRows === 0 && filtroAplicado) {
            let mensaje = 'No hay proveedores que coincidan con los criterios de búsqueda';
            
            if (searchTerm && selectedEstado) {
                mensaje = `No se encontraron proveedores "${searchTerm}" con estado ${selectedEstado}`;
            } else if (searchTerm) {
                mensaje = `No se encontraron resultados para "${searchTerm}"`;
            } else if (selectedEstado) {
                mensaje = `No hay proveedores con estado ${selectedEstado}`;
            }
            
            noResultsText.textContent = mensaje;
            noResultsRow.style.display = 'table-row';
        } else {
            noResultsRow.style.display = 'none';
        }
    }
    
    /**
     * Limpiar todos los filtros
     */
    limpiarTodosFiltros() {
        document.getElementById('buscarProveedor').value = '';
        document.getElementById('filtroEstado').value = '';
        document.getElementById('perPageSelect').value = '10';
        
        const dataRows = document.querySelectorAll('#tablaProveedores tbody tr.proveedores-data-row');
        const noResultsRow = document.getElementById('noResultsRow');
        
        dataRows.forEach(row => {
            row.style.display = '';
        });
        
        noResultsRow.style.display = 'none';
    }

    /**
     * Agregar nuevo proveedor a la tabla dinámicamente
     */
    agregarProveedorATabla(proveedor) {
        const tbody = document.querySelector('#tablaProveedores tbody');
        const noResultsRow = document.getElementById('noResultsRow');
        
        // Ocultar mensaje de "no hay proveedores" si existe
        const emptyRow = tbody.querySelector('tr:not(.proveedores-data-row):not(#noResultsRow)');
        if (emptyRow) {
            emptyRow.style.display = 'none';
        }
        
        // Crear nueva fila
        const newRow = document.createElement('tr');
        newRow.className = `proveedores-data-row ${proveedor.estado !== 'activo' ? 'opacity-75' : ''}`;
        newRow.setAttribute('data-proveedor-id', proveedor.id);
        
        // Calcular el nuevo índice (número de filas existentes + 1)
        const existingRows = tbody.querySelectorAll('.proveedores-data-row').length;
        const newIndex = existingRows + 1;
        
        newRow.innerHTML = `
            <td>
                <div class="proveedores-id">${newIndex}</div>
            </td>
            <td>
                <div class="proveedores-company">
                    <div class="proveedores-company-icon">
                        <iconify-icon icon="solar:buildings-bold-duotone"></iconify-icon>
                    </div>
                    <div class="proveedores-company-info">
                        <div class="proveedores-company-name">${proveedor.razon_social}</div>
                        ${proveedor.nombre_comercial ? `<div class="proveedores-company-commercial">${proveedor.nombre_comercial}</div>` : ''}
                    </div>
                </div>
            </td>
            <td>
                ${proveedor.ruc ? 
                    `<span class="proveedores-badge proveedores-badge-info">${proveedor.ruc}</span>` : 
                    `<span class="proveedores-badge proveedores-badge-gray">Sin RUC</span>`
                }
            </td>
            <td>
                <div class="proveedores-contact">
                    ${proveedor.telefono ? 
                        `<div class="proveedores-contact-item">
                            <iconify-icon icon="solar:phone-bold-duotone"></iconify-icon>
                            <span>${proveedor.telefono}</span>
                        </div>` : ''
                    }
                    ${proveedor.email ? 
                        `<div class="proveedores-contact-item">
                            <iconify-icon icon="solar:letter-bold-duotone"></iconify-icon>
                            <span>${proveedor.email}</span>
                        </div>` : ''
                    }
                    ${!proveedor.telefono && !proveedor.email ? 
                        `<span class="proveedores-badge proveedores-badge-gray">Sin contacto</span>` : ''
                    }
                </div>
            </td>
            <td>
                ${proveedor.estado === 'activo' ? 
                    `<span class="proveedores-badge proveedores-badge-success estado-badge">
                        <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                        Activo
                    </span>` :
                    `<span class="proveedores-badge proveedores-badge-secondary estado-badge">
                        <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                        Inactivo
                    </span>`
                }
            </td>
            <td>
                <div class="proveedores-date">
                    ${new Date().toLocaleDateString('es-PE')}
                </div>
            </td>
            <td>
                <div class="proveedores-action-buttons">
                    <button class="proveedores-action-btn proveedores-action-btn-view" 
                            onclick="verProveedor(${proveedor.id})" 
                            title="Ver detalles">
                        <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                    </button>
                    <button class="proveedores-action-btn proveedores-action-btn-edit" 
                            onclick="editarProveedor(${proveedor.id})" 
                            title="Editar">
                        <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                    </button>
                    <label class="toggle-switch proveedor-toggle" title="Activar/Desactivar">
                        <input type="checkbox" class="proveedor-status-toggle" data-proveedor-id="${proveedor.id}" ${proveedor.estado === 'activo' ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                    <button class="proveedores-action-btn proveedores-action-btn-delete" 
                            onclick="eliminarProveedor(${proveedor.id})" 
                            title="Eliminar">
                        <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                    </button>
                </div>
            </td>
        `;
        
        // Agregar la fila al final de la tabla
        tbody.appendChild(newRow);
        
        // Animar la nueva fila
        newRow.style.backgroundColor = '#dcfce7';
        setTimeout(() => {
            newRow.style.backgroundColor = '';
        }, 1000); // Reducido de 2000ms a 1000ms
    }

    /**
     * Actualizar proveedor existente en la tabla
     */
    actualizarProveedorEnTabla(id, proveedor) {
        console.log('🔄 Actualizando proveedor en tabla:', id, proveedor);
        
        // Buscar la fila por data-proveedor-id
        let row = document.querySelector(`tr[data-proveedor-id="${id}"]`);
        
        // Si no se encuentra por data-proveedor-id, buscar por el ID en la primera celda
        if (!row) {
            const rows = document.querySelectorAll('#tablaProveedores tbody tr');
            for (let r of rows) {
                if (r.cells[0] && r.cells[0].textContent.trim() === id.toString()) {
                    row = r;
                    break;
                }
            }
        }
        
        if (!row) {
            console.warn('❌ No se encontró la fila del proveedor para actualizar:', id);
            return;
        }
        
        console.log('✅ Fila encontrada, actualizando contenido...');
        
        // Actualizar contenido de las celdas
        const cells = row.querySelectorAll('td');
        
        // Actualizar nombre de la empresa (celda 1)
        if (cells[1]) {
            cells[1].innerHTML = `
                <div class="proveedores-company">
                    <div class="proveedores-company-icon">
                        <iconify-icon icon="solar:buildings-bold-duotone"></iconify-icon>
                    </div>
                    <div class="proveedores-company-info">
                        <div class="proveedores-company-name">${proveedor.razon_social || ''}</div>
                        ${proveedor.nombre_comercial ? `<div class="proveedores-company-commercial">${proveedor.nombre_comercial}</div>` : ''}
                    </div>
                </div>
            `;
        }
        
        // Actualizar RUC (celda 2)
        if (cells[2]) {
            cells[2].innerHTML = proveedor.ruc ? 
                `<span class="proveedores-badge proveedores-badge-info">${proveedor.ruc}</span>` : 
                `<span class="proveedores-badge proveedores-badge-gray">Sin RUC</span>`;
        }
        
        // Actualizar contacto (celda 3)
        if (cells[3]) {
            cells[3].innerHTML = `
                <div class="proveedores-contact">
                    ${proveedor.telefono ? 
                        `<div class="proveedores-contact-item">
                            <iconify-icon icon="solar:phone-bold-duotone"></iconify-icon>
                            <span>${proveedor.telefono}</span>
                        </div>` : ''
                    }
                    ${proveedor.email ? 
                        `<div class="proveedores-contact-item">
                            <iconify-icon icon="solar:letter-bold-duotone"></iconify-icon>
                            <span>${proveedor.email}</span>
                        </div>` : ''
                    }
                    ${!proveedor.telefono && !proveedor.email ? 
                        `<span class="proveedores-badge proveedores-badge-gray">Sin contacto</span>` : ''
                    }
                </div>
            `;
        }
        
        // Actualizar estado (celda 4)
        if (cells[4]) {
            const estadoBadge = proveedor.estado === 'activo' ? 
                '<span class="proveedores-badge proveedores-badge-success">Activo</span>' :
                '<span class="proveedores-badge proveedores-badge-danger">Inactivo</span>';
            cells[4].innerHTML = estadoBadge;
        }
        
        // Actualizar fecha de registro (celda 5) si existe
        if (cells[5] && proveedor.created_at) {
            const fecha = new Date(proveedor.created_at);
            cells[5].textContent = fecha.toLocaleDateString('es-PE');
        }
        
        // Animar la fila actualizada
        row.style.transition = 'background-color 0.3s ease';
        row.style.backgroundColor = '#dbeafe';
        setTimeout(() => {
            row.style.backgroundColor = '';
        }, 1000); // Reducido de 2000ms a 1000ms
        
        console.log('✅ Proveedor actualizado dinámicamente en la tabla');
    }

    /**
     * Eliminar proveedor de la tabla dinámicamente
     */
    eliminarProveedorDeTabla(id) {
        const row = document.querySelector(`tr[data-proveedor-id="${id}"]`);
        if (!row) return;
        
        // Animar la eliminación
        row.style.backgroundColor = '#fee2e2';
        row.style.transform = 'scale(0.95)';
        row.style.opacity = '0.5';
        
        setTimeout(() => {
            row.remove();
            
            // Reindexar las filas restantes
            this.reindexarFilasTabla();
            
            // Verificar si la tabla está vacía
            this.verificarTablaVacia();
        }, 300);
    }

    /**
     * Reindexar las filas de la tabla después de eliminar
     */
    reindexarFilasTabla() {
        const rows = document.querySelectorAll('#tablaProveedores tbody .proveedores-data-row');
        rows.forEach((row, index) => {
            const idCell = row.querySelector('.proveedores-id');
            if (idCell) {
                idCell.textContent = index + 1;
            }
        });
    }

    /**
     * Verificar si la tabla está vacía y mostrar mensaje
     */
    verificarTablaVacia() {
        const tbody = document.querySelector('#tablaProveedores tbody');
        const rows = tbody.querySelectorAll('.proveedores-data-row');
        
        if (rows.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="7" class="text-center py-4">
                    <div class="empty-state">
                        <iconify-icon icon="solar:box-bold-duotone" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem;"></iconify-icon>
                        <p style="color: #6b7280; font-size: 1.1rem; margin: 0;">No hay proveedores registrados</p>
                        <p style="color: #9ca3af; font-size: 0.9rem; margin: 0.5rem 0 0 0;">Agrega el primer proveedor para comenzar</p>
                    </div>
                </td>
            `;
            tbody.appendChild(emptyRow);
        }
    }

    /**
     * Actualizar estadísticas dinámicamente
     */
    actualizarEstadisticas() {
        // Contar proveedores actuales en la tabla
        const rows = document.querySelectorAll('#tablaProveedores tbody .proveedores-data-row');
        const totalProveedores = rows.length;
        
        let activosCount = 0;
        let conRucCount = 0;
        let conContactoCount = 0;
        
        rows.forEach(row => {
            // Contar activos
            const estadoBadge = row.querySelector('.estado-badge');
            if (estadoBadge && estadoBadge.textContent.trim().includes('Activo')) {
                activosCount++;
            }
            
            // Contar con RUC
            const rucBadge = row.querySelector('.proveedores-badge-info');
            if (rucBadge) {
                conRucCount++;
            }
            
            // Contar con contacto
            const contactItems = row.querySelectorAll('.proveedores-contact-item');
            if (contactItems.length > 0) {
                conContactoCount++;
            }
        });
        
        // Actualizar valores en las estadísticas
        const statCards = document.querySelectorAll('.proveedores-stat-value');
        if (statCards[0]) statCards[0].textContent = totalProveedores;
        if (statCards[1]) statCards[1].textContent = activosCount;
        if (statCards[2]) statCards[2].textContent = conRucCount;
        if (statCards[3]) statCards[3].textContent = conContactoCount;
        
        // Actualizar porcentajes
        const percentageElements = document.querySelectorAll('.proveedores-stat-change');
        if (percentageElements[1]) {
            const activosPercent = totalProveedores > 0 ? Math.round((activosCount / totalProveedores) * 100) : 0;
            percentageElements[1].innerHTML = `<iconify-icon icon="solar:arrow-up-bold"></iconify-icon>+${activosPercent}% Activos`;
        }
        if (percentageElements[2]) {
            const rucPercent = totalProveedores > 0 ? Math.round((conRucCount / totalProveedores) * 100) : 0;
            percentageElements[2].innerHTML = `<iconify-icon icon="solar:arrow-up-bold"></iconify-icon>+${rucPercent}% Con RUC`;
        }
    }

    /**
     * Renderizar tabla completa de proveedores
     */
    renderProveedoresTabla(proveedores) {
        const tbody = document.querySelector('#tablaProveedores tbody');
        if (!tbody) return;
        
        // Limpiar tabla
        tbody.innerHTML = '';
        
        if (!proveedores || proveedores.length === 0) {
            tbody.innerHTML = `
                <tr id="noResultsRow">
                    <td colspan="7" class="text-center py-4">
                        <div class="proveedores-empty-state">
                            <iconify-icon icon="solar:box-bold-duotone" class="text-gray-400 text-4xl mb-2"></iconify-icon>
                            <p class="text-gray-500">No hay proveedores registrados</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        // Renderizar cada proveedor
        proveedores.forEach((proveedor, index) => {
            const row = document.createElement('tr');
            row.className = `proveedores-data-row ${proveedor.estado !== 'activo' ? 'opacity-75' : ''}`;
            row.setAttribute('data-proveedor-id', proveedor.id);
            
            row.innerHTML = `
                <td>
                    <div class="proveedores-id">${index + 1}</div>
                </td>
                <td>
                    <div class="proveedores-company">
                        <div class="proveedores-company-icon">
                            <iconify-icon icon="solar:buildings-bold-duotone"></iconify-icon>
                        </div>
                        <div class="proveedores-company-info">
                            <div class="proveedores-company-name">${proveedor.razon_social}</div>
                            ${proveedor.nombre_comercial ? `<div class="proveedores-company-commercial">${proveedor.nombre_comercial}</div>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    ${proveedor.ruc ? 
                        `<span class="proveedores-badge proveedores-badge-info">${proveedor.ruc}</span>` : 
                        `<span class="proveedores-badge proveedores-badge-gray">Sin RUC</span>`
                    }
                </td>
                <td>
                    <div class="proveedores-contact">
                        ${proveedor.telefono ? 
                            `<div class="proveedores-contact-item">
                                <iconify-icon icon="solar:phone-bold-duotone"></iconify-icon>
                                <span>${proveedor.telefono}</span>
                            </div>` : ''
                        }
                        ${proveedor.email ? 
                            `<div class="proveedores-contact-item">
                                <iconify-icon icon="solar:letter-bold-duotone"></iconify-icon>
                                <span>${proveedor.email}</span>
                            </div>` : ''
                        }
                        ${!proveedor.telefono && !proveedor.email ? 
                            `<span class="proveedores-badge proveedores-badge-gray">Sin contacto</span>` : ''
                        }
                    </div>
                </td>
                <td>
                    ${proveedor.estado === 'activo' ? 
                        `<span class="proveedores-badge proveedores-badge-success estado-badge">
                            <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                            Activo
                        </span>` :
                        `<span class="proveedores-badge proveedores-badge-secondary estado-badge">
                            <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                            Inactivo
                        </span>`
                    }
                </td>
                <td>
                    <div class="proveedores-date">
                        ${proveedor.created_at ? new Date(proveedor.created_at).toLocaleDateString('es-PE') : new Date().toLocaleDateString('es-PE')}
                    </div>
                </td>
                <td>
                    <div class="proveedores-action-buttons">
                        <button class="proveedores-action-btn proveedores-action-btn-view" 
                                onclick="verProveedor(${proveedor.id})" 
                                title="Ver detalles">
                            <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                        </button>
                        <button class="proveedores-action-btn proveedores-action-btn-edit" 
                                onclick="editarProveedor(${proveedor.id})" 
                                title="Editar">
                            <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                        </button>
                        <label class="toggle-switch proveedor-toggle" title="Activar/Desactivar">
                            <input type="checkbox" class="proveedor-status-toggle" data-proveedor-id="${proveedor.id}" ${proveedor.estado === 'activo' ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                        <button class="proveedores-action-btn proveedores-action-btn-delete" 
                                onclick="eliminarProveedor(${proveedor.id})" 
                                title="Eliminar">
                            <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                        </button>
                    </div>
                </td>
            `;
            
            tbody.appendChild(row);
        });
    }

    /**
     * Recargar tabla completa de proveedores con skeleton loading (AJAX)
     */
    async recargarTablaProveedores() {
        console.log('🔄 Recargando tabla de proveedores...');
        
        try {
            // Mostrar skeleton loading
            const skeleton = document.getElementById('proveedoresSkeleton');
            const tbody = document.querySelector('#tablaProveedores tbody');
            
            if (skeleton && tbody) {
                skeleton.style.display = 'block';
                tbody.style.display = 'none';
            }
            
            // Hacer petición AJAX para obtener datos actualizados
            const response = await fetch('/compras/proveedores/api');
            if (!response.ok) throw new Error('Error al recargar los proveedores');
            
            const data = await response.json();
            if (data.success) {
                // Renderizar tabla con los nuevos datos
                this.renderProveedoresTabla(data.data);
                
                // Ocultar skeleton y mostrar tabla
                if (skeleton && tbody) {
                    skeleton.style.display = 'none';
                    tbody.style.display = '';
                }
                
                console.log('✅ Tabla de proveedores recargada exitosamente');
            }
        } catch (error) {
            console.error('Error al recargar tabla:', error);
            
            // Ocultar skeleton en caso de error
            const skeleton = document.getElementById('proveedoresSkeleton');
            const tbody = document.querySelector('#tablaProveedores tbody');
            if (skeleton && tbody) {
                skeleton.style.display = 'none';
                tbody.style.display = '';
            }
            
            // Mostrar mensaje de error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo recargar la tabla de proveedores'
            });
        }
    }
}

// Instanciar el manager cuando el DOM esté listo
let proveedoresManager;

document.addEventListener('DOMContentLoaded', function() {
    proveedoresManager = new ProveedoresManager();
});

// Funciones globales para mantener compatibilidad con los onclick en HTML
window.abrirModalAgregar = function() {
    // Abrir modal profesional (tema rojo)
    if (document.getElementById('proveedorModal')) return;
    openCreateProveedorModal();
};

window.verProveedor = function(id) {
    if (document.getElementById('proveedorModal')) return;
    Swal.fire({ title: 'Cargando información...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    fetch(`/api/compras/proveedor/${id}`)
      .then(r => r.json())
      .then(d => { Swal.close(); if (d.success) openViewProveedorModal(d.data); else Swal.fire({icon:'error', title:'Error', text:d.message || 'No se pudo cargar proveedor'}); })
      .catch(_ => { Swal.close(); Swal.fire({icon:'error', title:'Error de conexión', text:'No se pudo conectar con el servidor'}); });
};

window.editarProveedor = function(id) {
    if (document.getElementById('proveedorModal')) return;
    Swal.fire({ title: 'Cargando información...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    fetch(`/api/compras/proveedor/${id}`)
      .then(r => r.json())
      .then(d => { Swal.close(); if (d.success) openEditProveedorModal(d.data); else Swal.fire({icon:'error', title:'Error', text:d.message || 'No se pudo cargar proveedor'}); })
      .catch(_ => { Swal.close(); Swal.fire({icon:'error', title:'Error de conexión', text:'No se pudo conectar con el servidor'}); });
};

window.cambiarEstado = function(id, estado) {
    if (proveedoresManager) {
        proveedoresManager.cambiarEstado(id, estado);
    }
};

window.limpiarTodosFiltros = function() {
    if (proveedoresManager) {
        proveedoresManager.limpiarTodosFiltros();
    }
};

window.eliminarProveedor = function(id) {
    if (proveedoresManager) {
        proveedoresManager.eliminarProveedor(id);
    }
};

console.log('✅ Script de proveedores cargado correctamente');

// ================= Modal profesional reutilizado (roles) =================
function closeProveedorModal() {
    const m = document.getElementById('proveedorModal');
    if (m) m.remove();
    document.body.style.overflow = '';
}

function openCreateProveedorModal() {
    const html = `
    <div id="proveedorModal" class="modal-profesional">
      <div class="modal-profesional-container tema-rojo">
        <div class="header-profesional">
          <div class="header-content">
            <div class="header-left">
              <div class="header-icon icon-normal"><iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon></div>
              <div class="header-text"><h3>Nuevo Proveedor</h3></div>
            </div>
            <button type="button" class="btn-close" onclick="closeProveedorModal()"><iconify-icon icon="heroicons:x-mark"></iconify-icon></button>
          </div>
        </div>
        <div class="modal-content-profesional">
          <div class="seccion-form seccion-azul">
            <div class="grid-campos columnas-2">
              <div class="campo-grupo campo-completo"><label class="campo-label"><iconify-icon icon="solar:id-card-bold-duotone" class="label-icon"></iconify-icon> RUC</label><div class="ruc-row"><input type="text" id="prov_ruc" maxlength="11" class="campo-input"><button type="button" id="btnConsultarRuc" class="btn-guardar btn-buscar-ruc">Buscar</button></div><div id="prov_ruc_error" class="input-error" style="display:none"></div></div>
              <div class="campo-grupo campo-completo"><label class="campo-label"><iconify-icon icon="solar:buildings-bold-duotone" class="label-icon"></iconify-icon> Razón Social *</label><input type="text" id="prov_razon_social" class="campo-input" readonly></div>
              <div class="campo-grupo campo-completo"><label class="campo-label"><iconify-icon icon="solar:store-2-bold-duotone" class="label-icon"></iconify-icon> Nombre Comercial</label><input type="text" id="prov_nombre_comercial" class="campo-input" readonly></div>
              <div class="campo-grupo"><label class="campo-label"><iconify-icon icon="solar:phone-bold-duotone" class="label-icon"></iconify-icon> Teléfono</label><input type="text" id="prov_telefono" class="campo-input" placeholder="987654321" maxlength="9"></div>
              <div class="campo-grupo"><label class="campo-label"><iconify-icon icon="solar:letter-bold-duotone" class="label-icon"></iconify-icon> Email</label><input type="email" id="prov_email" class="campo-input" placeholder="correo@dominio.com"></div>
              <div class="campo-grupo campo-completo"><label class="campo-label"><iconify-icon icon="solar:map-point-bold-duotone" class="label-icon"></iconify-icon> Dirección</label><textarea id="prov_direccion" rows="3" class="campo-input" style="min-height: 80px;"></textarea></div>
            </div>
          </div>
        </div>
        <div class="footer-profesional"><div class="footer-botones"><button type="button" class="btn-cancelar" onclick="closeProveedorModal()">Cancelar</button><button type="button" class="btn-guardar" id="btnGuardarProveedor" disabled><iconify-icon icon="solar:disk-bold-duotone"></iconify-icon> Guardar Proveedor</button></div></div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    document.body.style.overflow = 'hidden';
    // Restricción numérica y autocompletar por RUC
    try {
        const rucInput = document.getElementById('prov_ruc');
        if (rucInput) {
            let lastQueried = '';
            rucInput.addEventListener('input', () => {
                // Solo dígitos, máximo 11
                rucInput.value = rucInput.value.replace(/\D+/g, '').slice(0, 11);
                const errEl = document.getElementById('prov_ruc_error');
                if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
                // Consultar automáticamente al completar 11 dígitos
                const val = rucInput.value;
                if (val.length === 11 && val !== lastQueried) {
                    lastQueried = val;
                    consultarRucAutoFill(val);
                }
                updateGuardarButtonState();
            });
            rucInput.addEventListener('blur', () => {
                const val = rucInput.value;
                if (val.length === 11 && val !== lastQueried) {
                    lastQueried = val;
                    consultarRucAutoFill(val);
                }
                updateGuardarButtonState();
            });
        }
        const btnBuscar = document.getElementById('btnConsultarRuc');
        if (btnBuscar) {
            btnBuscar.addEventListener('click', async () => {
                const val = (document.getElementById('prov_ruc')?.value || '').replace(/\D+/g, '').slice(0, 11);
                if (val.length !== 11) {
                    const errEl = document.getElementById('prov_ruc_error');
                    if (errEl) { errEl.innerHTML = '<span class="error-icon"><iconify-icon icon="heroicons:exclamation-triangle-20-solid"></iconify-icon></span><span>Ingresa 11 dígitos para consultar</span>'; errEl.style.display = 'block'; }
                    return;
                }
                // Estado de carga en botón
                btnBuscar.disabled = true;
                btnBuscar.style.opacity = '0.85';
                btnBuscar.classList.add('loading');
                btnBuscar.innerHTML = '<iconify-icon icon="line-md:loading-twotone-loop"></iconify-icon>';
                await consultarRucAutoFill(val);
                btnBuscar.disabled = false;
                btnBuscar.style.opacity = '';
                btnBuscar.classList.remove('loading');
                btnBuscar.innerHTML = 'Buscar';
                updateGuardarButtonState();
            });
        }
        const razonInput = document.getElementById('prov_razon_social');
        if (razonInput) razonInput.addEventListener('input', updateGuardarButtonState);
    } catch (e) { console.warn('No se pudo conectar autocompletado RUC (crear):', e); }
    document.getElementById('btnGuardarProveedor').addEventListener('click', () => {
        if (!proveedoresManager) proveedoresManager = new ProveedoresManager();
        proveedoresManager.guardarProveedor();
    });
    updateGuardarButtonState();
}

function openEditProveedorModal(p) {
    const html = `
    <div id="proveedorModal" class="modal-profesional">
      <div class="modal-profesional-container tema-verde">
        <div class="header-profesional"><div class="header-content"><div class="header-left"><div class="header-icon icon-normal"><iconify-icon icon="solar:pen-bold-duotone"></iconify-icon></div><div class="header-text"><h3>Editar Proveedor</h3></div></div><button type="button" class="btn-close" onclick="closeProveedorModal()"><iconify-icon icon="heroicons:x-mark"></iconify-icon></button></div></div>
        <div class="modal-content-profesional">
          <div class="seccion-form seccion-azul">
            <div class="grid-campos columnas-2">
              <input type="hidden" id="prov_id" value="${p.id}">
              <div class="campo-grupo campo-completo"><label class="campo-label">RUC</label><div class="ruc-row"><input type="text" id="prov_ruc" maxlength="11" class="campo-input" value="${p.ruc||''}"><button type="button" class="btn-guardar btn-buscar-ruc" id="btnConsultarRucEdit">Buscar</button></div><div id="prov_ruc_error" class="input-error" style="display:none"></div></div>
              <div class="campo-grupo campo-completo"><label class="campo-label">Razón Social *</label><input type="text" id="prov_razon_social" class="campo-input" value="${p.razon_social||''}" readonly></div>
              <div class="campo-grupo campo-completo"><label class="campo-label">Nombre Comercial</label><input type="text" id="prov_nombre_comercial" class="campo-input" value="${p.nombre_comercial||''}" readonly></div>
              <div class="campo-grupo"><label class="campo-label">Teléfono</label><input type="text" id="prov_telefono" class="campo-input" value="${p.telefono||''}"></div>
              <div class="campo-grupo"><label class="campo-label">Email</label><input type="email" id="prov_email" class="campo-input" value="${p.email||''}"></div>
              <div class="campo-grupo campo-completo"><label class="campo-label">Dirección</label><textarea id="prov_direccion" rows="3" class="campo-input" style="min-height: 80px;">${p.direccion||''}</textarea></div>
            </div>
          </div>
        </div>
        <div class="footer-profesional"><div class="footer-botones"><button type="button" class="btn-cancelar" onclick="closeProveedorModal()">Cancelar</button><button type="button" class="btn-guardar" id="btnActualizarProveedor" disabled><iconify-icon icon="solar:disk-bold-duotone"></iconify-icon> Actualizar Proveedor</button></div></div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    document.body.style.overflow = 'hidden';
    // Restricción numérica y autocompletar por RUC (editar)
    try {
        const rucInput = document.getElementById('prov_ruc');
        if (rucInput) {
            let lastQueried = '';
            rucInput.addEventListener('input', () => {
                rucInput.value = rucInput.value.replace(/\D+/g, '').slice(0, 11);
                const val = rucInput.value;
                if (val.length === 11 && val !== lastQueried) {
                    lastQueried = val;
                    consultarRucAutoFill(val);
                }
                updateActualizarButtonState();
            });
            rucInput.addEventListener('blur', () => {
                const val = rucInput.value;
                if (val.length === 11 && val !== lastQueried) {
                    lastQueried = val;
                    consultarRucAutoFill(val);
                }
                updateActualizarButtonState();
            });
        }
        const btnBuscarEdit = document.getElementById('btnConsultarRucEdit');
        if (btnBuscarEdit) {
            btnBuscarEdit.addEventListener('click', async () => {
                const val = (document.getElementById('prov_ruc')?.value || '').replace(/\D+/g, '').slice(0, 11);
                if (val.length !== 11) {
                    const errEl = document.getElementById('prov_ruc_error');
                    if (errEl) { errEl.innerHTML = '<span class="error-icon"><iconify-icon icon="heroicons:exclamation-triangle-20-solid"></iconify-icon></span><span>Ingresa 11 dígitos para consultar</span>'; errEl.style.display = 'block'; }
                    return;
                }
                btnBuscarEdit.disabled = true;
                btnBuscarEdit.style.opacity = '0.85';
                btnBuscarEdit.classList.add('loading');
                btnBuscarEdit.innerHTML = '<iconify-icon icon="line-md:loading-twotone-loop"></iconify-icon>';
                await consultarRucAutoFill(val);
                btnBuscarEdit.disabled = false;
                btnBuscarEdit.style.opacity = '';
                btnBuscarEdit.classList.remove('loading');
                btnBuscarEdit.innerHTML = 'Buscar';
                updateActualizarButtonState();
            });
        }
        const razonInput = document.getElementById('prov_razon_social');
        if (razonInput) razonInput.addEventListener('input', updateActualizarButtonState);
    } catch (e) { console.warn('No se pudo conectar autocompletado RUC (editar):', e); }
    document.getElementById('btnActualizarProveedor').addEventListener('click', () => {
        if (!proveedoresManager) proveedoresManager = new ProveedoresManager();
        proveedoresManager.actualizarProveedor();
    });
    updateActualizarButtonState();
}

function openViewProveedorModal(p) {
    const html = `
    <div id="proveedorModal" class="modal-profesional">
      <div class="modal-profesional-container tema-azul">
        <div class="header-profesional"><div class="header-content"><div class="header-left"><div class="header-icon icon-normal"><iconify-icon icon="solar:eye-bold-duotone"></iconify-icon></div><div class="header-text"><h3>Información del Proveedor</h3></div></div><button type="button" class="btn-close" onclick="closeProveedorModal()"><iconify-icon icon="heroicons:x-mark"></iconify-icon></button></div></div>
        <div class="modal-content-profesional">
          <div class="seccion-form seccion-azul">
            <div class="seccion-header"><div class="seccion-icon icon-azul"><iconify-icon icon="solar:info-circle-bold-duotone"></iconify-icon></div><div class="seccion-titulo"><h3>Datos del Proveedor</h3><p>Resumen</p></div></div>
            <div class="grid-campos columnas-2">
              <div class="campo-grupo campo-completo"><label class="campo-label">Razón Social</label><div class="field-pill">${p.razon_social||'-'}</div></div>
              <div class="campo-grupo campo-completo"><label class="campo-label">Nombre Comercial</label><div class="field-pill">${p.nombre_comercial||'-'}</div></div>
              <div class="campo-grupo"><label class="campo-label">RUC</label><div class="field-pill">${p.ruc||'-'}</div></div>
              <div class="campo-grupo"><label class="campo-label">Teléfono</label><div class="field-pill">${p.telefono||'-'}</div></div>
              <div class="campo-grupo campo-completo"><label class="campo-label">Email</label><div class="field-pill">${p.email||'-'}</div></div>
              <div class="campo-grupo campo-completo"><label class="campo-label">Dirección</label><div class="field-pill">${p.direccion||'-'}</div></div>
            </div>
          </div>
        </div>
        <div class="footer-profesional"><div class="footer-botones"><button type="button" class="btn-cancelar" onclick="closeProveedorModal()">Entendido</button><button type="button" class="btn-guardar" onclick="closeProveedorModal(); editarProveedor(${p.id});">Editar</button></div></div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', html);
  document.body.style.overflow = 'hidden';
}

/**
 * Consultar RUC en backend y autocompletar campos del modal de proveedor.
 */
async function consultarRucAutoFill(ruc) {
    try {
        const razonEl = document.getElementById('prov_razon_social');
        const comercialEl = document.getElementById('prov_nombre_comercial');
        const direccionEl = document.getElementById('prov_direccion');
        const rucEl = document.getElementById('prov_ruc');
        const errEl = document.getElementById('prov_ruc_error');

        if (rucEl) rucEl.classList.add('loading-input');

        const resp = await fetch(`/api/compras/consultar-ruc/${encodeURIComponent(ruc)}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await resp.json();
        if (rucEl) rucEl.classList.remove('loading-input');

        if (data && data.success && data.data) {
            const info = data.data;
            if (razonEl && (!razonEl.value || razonEl.value.trim() === '')) razonEl.value = info.razon_social || '';
            if (comercialEl && (!comercialEl.value || comercialEl.value.trim() === '')) comercialEl.value = info.nombre_comercial || '';
            if (direccionEl && (!direccionEl.value || direccionEl.value.trim() === '')) direccionEl.value = info.direccion || '';
            if (rucEl && (!rucEl.value || rucEl.value.trim() === '')) rucEl.value = info.ruc || ruc;
            if (rucEl) rucEl.style.backgroundColor = '#dcfce7';
            if (errEl) { errEl.textContent = ''; errEl.style.display = 'none'; }
            // Actualizar estado de botones tras autocompletar
            updateGuardarButtonState?.();
            updateActualizarButtonState?.();
        } else {
            if (errEl) { errEl.innerHTML = `<span class="error-icon"><iconify-icon icon="heroicons:exclamation-triangle-20-solid"></iconify-icon></span><span>No se encontró el RUC ${ruc}</span>`; errEl.style.display = 'block'; }
            if (rucEl) rucEl.style.backgroundColor = '';
        }
    } catch (err) {
        console.error('Error consultando RUC:', err);
        const errEl = document.getElementById('prov_ruc_error');
        if (errEl) { errEl.innerHTML = '<span class="error-icon"><iconify-icon icon="heroicons:exclamation-triangle-20-solid"></iconify-icon></span><span>Error consultando RUC. Intenta nuevamente.</span>'; errEl.style.display = 'block'; }
    }
}

// Habilitar/deshabilitar botones según validación de campos requeridos
function camposProveedorValidos() {
    const ruc = (document.getElementById('prov_ruc')?.value || '').trim();
    const razon = (document.getElementById('prov_razon_social')?.value || '').trim();
    return /^\d{11}$/.test(ruc) && razon.length > 0;
}

function updateGuardarButtonState() {
    const btn = document.getElementById('btnGuardarProveedor');
    if (btn) btn.disabled = !camposProveedorValidos();
}

function updateActualizarButtonState() {
    const btn = document.getElementById('btnActualizarProveedor');
    if (btn) btn.disabled = !camposProveedorValidos();
}