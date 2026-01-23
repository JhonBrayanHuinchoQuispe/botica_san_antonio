/* GESTIÓN DE ROLES Y PERMISOS - JAVASCRIPT */

// Variables globales
let currentRoleId = null;
let isEditMode = false;
let rolesData = [];

// Configuración CSRF
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Event Listeners al cargar la página
// Evitar dobles toggles simultáneos
const togglingRoles = new Set();

// Recarga dura para evitar cache/turbo y garantizar HTML fresco
function hardReload() {
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('_r', Date.now());
        window.location.replace(url.toString());
    } catch (_) {
        window.location.href = window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_r=' + Date.now();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initializeRoleManagement();
});

// Soporte para Turbo/Hotwire: re-inicializar al cargar cada visita
document.addEventListener('turbo:load', function() {
    initializeRoleManagement();
});

// Exponer funciones al ámbito global para los onclick del HTML
window.openCreateRoleModal = openCreateRoleModal;
window.editRole = editRole;
window.viewRole = viewRole;
window.deleteRole = deleteRole;
window.duplicateRole = duplicateRole;

// Helpers de preloader para esta vista
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

function mostrarSkeletonInicial() {
    // Mostrar skeleton mientras se cargan los datos desde el servidor
    const skeletonBody = document.getElementById('rolesSkeletonBody');
    const tbody = document.querySelector('.roles-table tbody:not(#rolesSkeletonBody)');
    
    if (skeletonBody && tbody) {
        skeletonBody.style.display = 'table-row-group';
        tbody.style.display = 'none';
        
        // Simular tiempo de carga y luego mostrar los datos reales
        setTimeout(() => {
            skeletonBody.style.display = 'none';
            tbody.style.display = 'table-row-group';
        }, 500); // 500ms para que se aprecie el efecto
    }
}

/* INICIALIZACIÓN */
function initializeRoleManagement() {
    setupEventListeners();
    mostrarSkeletonInicial();
    loadRolesData();
    setupPermissionControls();
}

function setupEventListeners() {
    // Formulario de rol
    const roleForm = document.getElementById('roleForm');
    if (roleForm) {
        roleForm.addEventListener('submit', handleRoleFormSubmit);
    }

    // Filtros
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterRoles, 300));
    }

    // Cerrar modal con escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRoleModal();
        }
    });

    // Cerrar modal al hacer clic fuera
    const modal = document.getElementById('roleModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeRoleModal();
            }
        });
    }

    // Color presets
    setupColorPresets();

    // Toggle de estado en la tabla de roles
    document.addEventListener('change', function(e) {
        const toggle = e.target.closest('.role-status-toggle');
        if (toggle) {
            const roleId = toggle.dataset.roleId;
            if (!roleId) return;
            if (togglingRoles.has(roleId)) return; // bloquear repetidos
            togglingRoles.add(roleId);
            const prevChecked = !toggle.checked; // estado previo antes del cambio
            toggle.disabled = true; // prevenir más clics mientras se procesa
            toggleRoleStatus(roleId, toggle, prevChecked);
        }
    });

    // Vincular comportamiento de Dashboard colapsado
    document.addEventListener('change', function(e) {
        const chk = e.target.closest('.permission-checkbox');
        if (!chk) return;
        const permName = chk.dataset.name || '';
        if (permName.startsWith('dashboard.')) {
            const hidden = document.querySelectorAll('.permission-checkbox[data-dashboard-hidden="true"]');
            hidden.forEach(h => { h.checked = chk.checked; });
        }
    });

    // Restringir config.* a Administrador/Dueño/Gerente
    const displayInput = document.getElementById('display_name');
    function updateConfigPermissionsAvailability() {
        const roleName = (displayInput?.value || '').toLowerCase().trim();
        const allowed = ['administrador', 'dueño', 'dueno', 'gerente'];
        const isAllowed = allowed.includes(roleName);
        document.querySelectorAll('.permission-checkbox').forEach(ch => {
            const name = (ch.dataset.name || '').toLowerCase();
            if (name.startsWith('config.')) {
                ch.disabled = !isAllowed;
                const card = ch.closest('.permiso-card');
                if (card) {
                    card.style.opacity = isAllowed ? '1' : '0.6';
                }
                if (!isAllowed) ch.checked = false;
            }
        });
    }
    if (displayInput) {
        displayInput.addEventListener('input', updateConfigPermissionsAvailability);
        setTimeout(updateConfigPermissionsAvailability, 50);
    }
}

/* GESTIÓN DE DATOS */
function loadRolesData() {
    const roleRows = document.querySelectorAll('.role-row');
    rolesData = Array.from(roleRows).map(row => {
        const roleData = {
            id: row.dataset.roleId,
            name: row.querySelector('.role-name')?.textContent.trim(),
            description: row.querySelector('.description-text')?.textContent.trim() || '',
            systemName: row.querySelector('.role-system-name')?.textContent.trim() || '',
            element: row
        };
        return roleData;
    });
}

/* FILTROS Y BÚSQUEDA */
function filterRoles() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';

    rolesData.forEach(role => {
        let shouldShow = true;

        // Filtro de búsqueda (buscar en nombre y descripción)
        if (searchTerm) {
            const searchableText = (role.name + ' ' + role.systemName + ' ' + role.description).toLowerCase();
            shouldShow = shouldShow && searchableText.includes(searchTerm);
        }

        // Mostrar/ocultar fila
        role.element.style.display = shouldShow ? '' : 'none';
    });

    // Mostrar mensaje si no hay resultados
    updateEmptyState();
}

function updateEmptyState() {
    const visibleRoles = rolesData.filter(role => role.element.style.display !== 'none');
    const tbody = document.querySelector('.roles-table tbody');
    const existingEmptyState = tbody.querySelector('.filter-empty-state');

    if (visibleRoles.length === 0 && rolesData.length > 0) {
        if (!existingEmptyState) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'filter-empty-state';
            emptyRow.innerHTML = `
                <td colspan="6" class="empty-state">
                    <div class="empty-content">
                        <iconify-icon icon="solar:shield-user-bold-duotone" class="empty-icon"></iconify-icon>
                        <h3>No se encontraron roles</h3>
                        <p>Intenta con otros términos de búsqueda</p>
                        <button type="button" class="btn-action-elegant btn-primary" onclick="clearFilters()">
                            <iconify-icon icon="solar:arrow-path-bold-duotone"></iconify-icon>
                            <span>Limpiar Filtros</span>
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(emptyRow);
        }
    } else if (existingEmptyState) {
        existingEmptyState.remove();
    }
}

function clearFilters() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        filterRoles();
    }
}

/* MODAL DE ROL */
function openCreateRoleModal() {
    isEditMode = false;
    currentRoleId = null;
    
    // Resetear formulario
    resetRoleForm();
    
    // Configurar modal para crear
    document.getElementById('modalTitle').textContent = 'Crear Nuevo Rol';
    document.getElementById('modalIcon').setAttribute('icon', 'solar:shield-plus-bold-duotone');
    document.getElementById('saveButtonText').textContent = 'Crear Rol';
    
    // Mostrar modal profesional
    const modal = document.getElementById('roleModal');
    modal.classList.remove('hidden');
    // Asegurar tema rojo para crear
    const container = modal.querySelector('.modal-profesional-container');
    if (container) {
        container.classList.remove('tema-verde');
    }
    
    // Inicializar progress bar
    updateProgressBar();
    
    // Inicializar estado de botones (mostrar solo "Seleccionar Todo")
    updateControlButtonsVisibility(0);
    
    // Focus en primer campo
    setTimeout(() => {
        document.getElementById('display_name').focus();
    }, 100);
}

async function editRole(roleId) {
    try {
        showLoading('Cargando datos para editar...');
        const response = await fetch(`/admin/roles/${roleId}/editar`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            if (data.role.is_protected) {
                showAlert('warning', 'Rol Protegido', 'Este rol no puede ser modificado');
                return;
            }
            
    isEditMode = true;
    currentRoleId = roleId;
    
    // Configurar modal para editar
    document.getElementById('modalTitle').textContent = 'Editar Rol';
    document.getElementById('modalIcon').setAttribute('icon', 'solar:shield-edit-bold-duotone');
    document.getElementById('saveButtonText').textContent = 'Actualizar Rol';
    
    // Cargar datos del rol
            populateRoleFormFromEdit(data.role);
    
    // Mostrar modal profesional
    const modal = document.getElementById('roleModal');
    modal.classList.remove('hidden');
    // Aplicar tema verde para editar
    const container = modal.querySelector('.modal-profesional-container');
    if (container) {
        container.classList.add('tema-verde');
    }
    
    // Inicializar progress bar
    updateProgressBar();
            
            // Focus en primer campo
            setTimeout(() => {
                document.getElementById('display_name').focus();
            }, 100);
            hideLoading();
        } else {
            throw new Error(data.message || 'Error al cargar datos del rol');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', 'No se pudieron cargar los datos del rol');
        hideLoading();
    }
}

function closeRoleModal() {
    const modal = document.getElementById('roleModal');
    modal.classList.add('hidden');
    resetRoleForm();
    currentRoleId = null;
    isEditMode = false;
    
    // Resetear progress bar
    const progressBar = document.getElementById('roleProgressBar');
    if (progressBar) {
        progressBar.style.width = '0%';
    }
}

function resetRoleForm() {
    const form = document.getElementById('roleForm');
    form.reset();
    
    // Resetear color
    document.getElementById('color').value = '#e53e3e';
    
    // Limpiar errores de validación
    clearFormErrors();
    
    // Desmarcar permisos
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
    permissionCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Actualizar contador
    updatePermissionsCounter();
}

/* CARGA DE DATOS DEL ROL */
async function loadRoleData(roleId) {
    try {
        showFormLoading(true);
        
        const response = await fetch(`${ROLES_BASE_URL}/${roleId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });

        const data = await response.json();
        
        if (data.success) {
            populateRoleForm(data.role);
        } else {
            throw new Error(data.message || 'Error al cargar datos del rol');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', 'No se pudieron cargar los datos del rol');
    } finally {
        showFormLoading(false);
    }
}

function populateRoleForm(role) {
    // Datos básicos
    document.getElementById('roleId').value = role.id;
    document.getElementById('display_name').value = role.display_name || '';
    document.getElementById('description').value = role.description || '';
    document.getElementById('color').value = role.color || '#e53e3e';
    
    // Permisos
    if (role.permissions && role.permissions.length > 0) {
        const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
        permissionCheckboxes.forEach(checkbox => {
            const permissionId = checkbox.value;
            checkbox.checked = role.permissions.some(permission => permission.id == permissionId);
        });
    }
    
    // Actualizar contador
    updatePermissionsCounter();
}

/* ENVÍO DE FORMULARIO */
async function handleRoleFormSubmit(e) {
    e.preventDefault();
    
    if (!validateRoleForm()) {
        return;
    }
    
    const formData = new FormData(e.target);
    // Derivar 'name' (técnico) desde 'display_name' (visible)
    const displayName = document.getElementById('display_name')?.value || '';
    const slug = displayName
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // quitar acentos
        .replace(/[^a-z0-9\s-]/g, '') // caracteres no permitidos
        .trim()
        .replace(/\s+/g, '-') // espacios por guiones
        .replace(/-+/g, '-');
    formData.append('name', slug);
    
    // Agregar permisos seleccionados
    const selectedPermissions = Array.from(document.querySelectorAll('.permission-checkbox:checked'))
        .map(checkbox => checkbox.value);
    
    if (selectedPermissions.length === 0) {
        showAlert('warning', 'Validación', 'Debe seleccionar al menos un permiso');
        return;
    }
    
    selectedPermissions.forEach(permissionId => {
        formData.append('permisos[]', permissionId);
    });
    
    try {
        showFormLoading(true);
        
        const url = isEditMode ? `${ROLES_BASE_URL}/${currentRoleId}` : ROLES_BASE_URL;
        const method = isEditMode ? 'POST' : 'POST';
        
        if (isEditMode) {
            formData.append('_method', 'PUT');
        }
        
        // Ocultar el modal inmediatamente (sin resetear estados) para evitar la sensación de espera
        const modalSoft = document.getElementById('roleModal');
        if (modalSoft) { modalSoft.classList.add('hidden'); }
        // No mostrar overlay global

        const response = await fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            // Mostrar mensaje y recargar automáticamente para ver los cambios como si se hubiese refrescado
            showAlert('success', '¡Éxito!', data.message);
            setTimeout(() => {
                try { closeRoleModal(); } catch (_) {}
                try { hardReload(); } catch (_) { window.location.reload(); }
            }, 1600);
            return;
        } else {
            if (data.errors) {
                // Reabrir modal para mostrar errores del backend
                const modalSoft2 = document.getElementById('roleModal');
                if (modalSoft2) { modalSoft2.classList.remove('hidden'); }
                showFormErrors(data.errors);
            } else {
                throw new Error(data.message || 'Error al procesar la solicitud');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', error.message || 'Error al procesar la solicitud');
        // Reabrir modal en caso de error
        const modalSoft3 = document.getElementById('roleModal');
        if (modalSoft3) { modalSoft3.classList.remove('hidden'); }
    } finally {
        showFormLoading(false);
    }
}

// Refrescar una fila con datos completos del rol (incluye users_count)
async function refrescarFilaRol(roleId) {
    const response = await fetch(`${ROLES_BASE_URL}/${roleId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    });
    if (!response.ok) return;
    const data = await response.json();
    if (!data.success || !data.role) return;
    const role = data.role;
    const row = document.querySelector(`.roles-table tr.role-row[data-role-id="${roleId}"]`);
    if (!row) return;
    // Nombre
    const roleNameEl = row.querySelector('.role-name');
    const roleSystemEl = row.querySelector('.role-system-name');
    if (roleNameEl) roleNameEl.textContent = role.display_name || role.name || '';
    if (roleSystemEl) {
        const dn = (role.display_name || '').toString();
        const sn = (role.name || '').toString();
        if (sn && dn && sn === dn) {
            // Si ambos nombres son iguales, evitar duplicado visual
            roleSystemEl.remove();
        } else {
            roleSystemEl.textContent = sn;
        }
    }
    // Descripción
    const descCell = row.querySelector('.description-cell');
    if (descCell) {
        descCell.innerHTML = role.description
            ? `<span class="description-text">${role.description}</span>`
            : `<span class="no-description">Sin descripción</span>`;
    }
    // Permisos: proteger dueños/gerente
    const permCell = row.querySelector('.permissions-cell .permissions-count');
    if (permCell) {
        if (role.is_protected) {
            permCell.innerHTML = `
                <iconify-icon icon="solar:shield-check-bold-duotone" class="permission-icon"></iconify-icon>
                <span class="count">Acceso</span>
                <span class="label">completo</span>
            `;
        } else {
            const count = Array.isArray(role.permissions) ? role.permissions.length : (role.permissions_count || 0);
            permCell.innerHTML = `
                <iconify-icon icon="solar:shield-check-bold-duotone" class="permission-icon"></iconify-icon>
                <span class="count">${count}</span>
                <span class="label">permisos</span>
            `;
        }
    }
    // Usuarios
    const usersCell = row.querySelector('.users-cell .users-count');
    if (usersCell) {
        const count = role.users_count ?? 0;
        usersCell.innerHTML = `
            <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="users-icon"></iconify-icon>
            <span class="count">${count}</span>
            <span class="label">${count === 1 ? 'usuario' : 'usuarios'}</span>
        `;
    }
    // Realce visual
    row.style.backgroundColor = '#dbeafe';
    setTimeout(() => { row.style.backgroundColor = ''; }, 800);
}

/* VALIDACIÓN DEL FORMULARIO */
function validateRoleForm() {
    clearFormErrors();
    
    let isValid = true;
    const errors = {};
    
    // Validar nombre mostrado
    const displayName = document.getElementById('display_name').value.trim();
    if (!displayName) {
        errors.display_name = 'El nombre mostrado es requerido';
        isValid = false;
    }
    
    // Validar permisos
    const selectedPermissions = document.querySelectorAll('.permission-checkbox:checked');
    if (selectedPermissions.length === 0) {
        errors.permisos = 'Debe seleccionar al menos un permiso';
        isValid = false;
    }
    
    if (!isValid) {
        showFormErrors(errors);
    }
    
    return isValid;
}

function showFormErrors(errors) {
    Object.keys(errors).forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.classList.add('border-red-500');
            
            // Crear mensaje de error
            const errorDiv = document.createElement('div');
            errorDiv.className = 'text-red-500 text-sm mt-1';
            errorDiv.textContent = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
            
            // Insertar después del input
            input.parentNode.appendChild(errorDiv);
        }
    });
}

function clearFormErrors() {
    // Remover clases de error
    const inputs = document.querySelectorAll('.form-input');
    inputs.forEach(input => {
        input.classList.remove('border-red-500');
    });
    
    // Remover mensajes de error
    const errorMessages = document.querySelectorAll('.text-red-500');
    errorMessages.forEach(msg => {
        if (msg.className.includes('text-sm mt-1')) {
            msg.remove();
        }
    });
}

/* GESTIÓN DE PERMISOS */
function setupPermissionControls() {
    // Checkboxes de módulos
    const moduleCheckboxes = document.querySelectorAll('.module-checkbox');
    moduleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const module = this.dataset.module;
            const permissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
            
            permissionCheckboxes.forEach(permCheckbox => {
                permCheckbox.checked = this.checked;
            });
            
            updatePermissionsCounter();
        });
    });
    
    // Checkboxes de permisos individuales
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updatePermissionsCounter();
            
            // Actualizar checkbox del módulo
            const module = this.dataset.module;
            const moduleCheckbox = document.querySelector(`.module-checkbox[data-module="${module}"]`);
            const modulePermissions = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
            const checkedModulePermissions = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]:checked`);
            
            if (moduleCheckbox) {
                if (checkedModulePermissions.length === 0) {
                    moduleCheckbox.checked = false;
                    moduleCheckbox.indeterminate = false;
                } else if (checkedModulePermissions.length === modulePermissions.length) {
                    moduleCheckbox.checked = true;
                    moduleCheckbox.indeterminate = false;
                } else {
                    moduleCheckbox.checked = false;
                    moduleCheckbox.indeterminate = true;
                }
            }
        });
    });
}

function selectAllPermissions() {
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
    permissionCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    
    const moduleCheckboxes = document.querySelectorAll('.module-checkbox');
    moduleCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
        checkbox.indeterminate = false;
    });
    
    updatePermissionsCounter();
}

function deselectAllPermissions() {
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
    permissionCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    const moduleCheckboxes = document.querySelectorAll('.module-checkbox');
    moduleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.indeterminate = false;
    });
    
    updatePermissionsCounter();
}

function updatePermissionsCounter() {
    const selectedPermissions = document.querySelectorAll('.permission-checkbox:checked');
    const counter = document.getElementById('selectedPermissionsCount');
    
    if (counter) {
        counter.textContent = selectedPermissions.length;
    }
    
    // También actualizar visibilidad de botones (para compatibilidad)
    updateControlButtonsVisibility(selectedPermissions.length);
}

/* COLOR PICKER */
function setupColorPresets() {
    const colorPresets = document.querySelectorAll('.color-preset');
    colorPresets.forEach(preset => {
        preset.addEventListener('click', function() {
            const color = this.dataset.color;
            document.getElementById('color').value = color;
        });
    });
}

/* FUNCIONES DE TABLA - Ya no necesitamos menús dropdown */
// Las acciones ahora son botones directos en la tabla

/* ACCIONES DE ROL */
async function viewRole(roleId) {
    try {
        const response = await fetch(`${ROLES_BASE_URL}/${roleId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            openViewRoleModal(data.role);
            hideLoading();
        } else {
            throw new Error(data.message || 'Error al cargar datos del rol');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', 'No se pudieron cargar los datos del rol');
        hideLoading();
    }
}

function duplicateRole(roleId) {
    // Implementar duplicación de rol
    showAlert('info', 'Función en desarrollo', 'La duplicación de roles estará disponible pronto');
}

async function deleteRole(roleId) {
    // Cargar detalles del rol para mostrarlos en el modal de confirmación
    let roleDetails = null;
    try {
        const resp = await fetch(`${ROLES_BASE_URL}/${roleId}`, {
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();
        if (data?.success) roleDetails = data.role;
    } catch (_) {}

    const name = roleDetails?.display_name || roleDetails?.name || 'Este rol';
    const users = roleDetails?.users_count ?? 0;
    const perms = roleDetails?.permissions_count ?? 0;
    const html = `
        <div class="swal2-role-summary">
            <div class="role-card">
                <div class="role-header">
                    <span class="role-color" style="background:${roleDetails?.color || '#e5e7eb'}"></span>
                    <div class="role-name">${name}</div>
                </div>
                <div class="role-stats">
                    <div class="stat-pill stat-perms">
                        <iconify-icon icon="solar:shield-check-bold-duotone"></iconify-icon>
                        <span class="stat-count">${perms}</span>
                        <span class="stat-label">permisos</span>
                    </div>
                    <div class="stat-pill stat-users">
                        <iconify-icon icon="solar:users-group-rounded-bold-duotone"></iconify-icon>
                        <span class="stat-count">${users}</span>
                        <span class="stat-label">${users === 1 ? 'usuario' : 'usuarios'}</span>
                    </div>
                </div>
            </div>
            <div class="swal2-warning-text">Esta acción eliminará permanentemente el rol y no podrá ser recuperado.</div>
        </div>`;

    const result = await Swal.fire({
        title: '¿Eliminar rol?',
        html,
        icon: 'warning',
        showCancelButton: true,
        buttonsStyling: false,
        focusConfirm: false,
        customClass: {
            popup: 'swal2-popup-roles',
            confirmButton: 'swal2-confirm-roles',
            cancelButton: 'swal2-cancel-roles'
        },
        confirmButtonText: 'Sí, eliminar rol',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch(`${ROLES_BASE_URL}/${roleId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });

        const data = await response.json();
        
        if (data.success) {
            // Mostrar toast y recargar para que todo quede consistente
            showAlert('success', '¡Éxito!', data.message);
            setTimeout(() => { try { hardReload(); } catch (_) { window.location.reload(); } }, 1600);
        } else {
            throw new Error(data.message || 'Error al eliminar el rol');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', error.message);
    }
}

/* EXPORTACIÓN */
async function exportRoles() {
    const visibleRoles = rolesData.filter(role => role.element.style.display !== 'none');
    const source = visibleRoles.length > 0 ? visibleRoles : rolesData; // si no hay visibles, exportar todos
    const rows = source.map(r => {
        const rowEl = r.element;
        let name = rowEl.querySelector('.role-name')?.textContent.trim() || '';
        if (!name) {
            // Fallback al nombre del sistema si el display name no existe
            name = rowEl.querySelector('.role-system-name')?.textContent.trim() || '';
        }
        const desc = rowEl.querySelector('.description-text')?.textContent.trim() || '';
        const countEl = rowEl.querySelector('.permissions-cell .count');
        const labelEl = rowEl.querySelector('.permissions-cell .label');
        let permisos = '';
        const labelText = (labelEl?.textContent || '').trim().toLowerCase();
        if (labelText === 'completo') {
            permisos = 'Acceso completo';
        } else {
            permisos = (countEl?.textContent || '').trim();
        }
        const usuarios = rowEl.querySelector('.users-cell .count')?.textContent.trim() || '';
        const estado = rowEl.querySelector('.status-cell .status-badge')?.textContent.trim() || '';
        return [name, desc, permisos, usuarios, estado];
    });

    if (typeof XLSX === 'undefined') {
        const ok = await ensureXLSX();
        if (!ok || typeof XLSX === 'undefined') {
            showAlert('error', 'Error', 'No se pudo cargar la librería de Excel');
            return;
        }
    }

    const aoa = [];
    aoa.push(['Reporte de Roles']);
    aoa.push([]);
    aoa.push(['Rol','Descripción','Permisos','Usuarios','Estado']);
    rows.forEach(r => aoa.push(r));

    const ws = XLSX.utils.aoa_to_sheet(aoa);
    ws['!merges'] = [XLSX.utils.decode_range('A1:E1')];
    ws['!cols'] = [{wch:24},{wch:40},{wch:16},{wch:10},{wch:12}];

    // Estilos básicos (si el build de XLSX los soporta)
    try {
        ws['A1'].s = { font: { bold: true, sz: 18 }, alignment: { horizontal: 'center' } };
        ['A3','B3','C3','D3','E3'].forEach(addr => { if (ws[addr]) { ws[addr].s = { font: { bold: true } }; } });
    } catch (_) {}
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Roles');
    const filename = 'reporte_roles_' + new Date().toISOString().slice(0,10) + '.xlsx';
    XLSX.writeFile(wb, filename);
    showAlert('success', '¡Exportado!', 'El archivo Excel se ha descargado');
}

async function ensureXLSX() {
    function load(url) {
        return new Promise((resolve) => {
            const s = document.createElement('script');
            s.src = url; s.async = true; s.onload = () => resolve(true); s.onerror = () => resolve(false);
            document.head.appendChild(s);
        });
    }
    // Intentar primero xlsx-js-style (soporta estilos), luego fallback a xlsx estándar
    const primary = await load('https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx-js-style.min.js');
    if (primary && typeof XLSX !== 'undefined') return true;
    const fallback = await load('https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js');
    return fallback && typeof XLSX !== 'undefined';
}

/* UTILIDADES */
function showFormLoading(show) {
    const submitBtn = document.querySelector('.btn-guardar') || document.querySelector('.btn-save');
    const submitText = document.getElementById('saveButtonText');

    if (!submitBtn || !submitText) {
        // Si no existe el botón o el texto (por cambios de plantilla), salimos sin error
        return;
    }

    if (show) {
        submitBtn.disabled = true;
        submitText.textContent = 'Procesando...';
        submitBtn.style.opacity = '0.7';
    } else {
        submitBtn.disabled = false;
        submitText.textContent = isEditMode ? 'Actualizar Rol' : 'Crear Rol';
        submitBtn.style.opacity = '1';
    }
}

function showAlert(type, title, message) {
    const config = {
        title: title,
        text: message
    };

    switch (type) {
        case 'success':
            config.icon = 'success';
            // Modal centrado (como antes), sin botón y cierre automático
            config.showConfirmButton = false;
            config.timer = 1500;
            break;
        case 'error':
            config.icon = 'error';
            config.confirmButtonColor = '#e53e3e';
            config.confirmButtonText = 'Entendido';
            break;
        case 'warning':
            config.icon = 'warning';
            config.confirmButtonColor = '#e53e3e';
            config.confirmButtonText = 'Entendido';
            break;
        default:
            config.icon = 'info';
            config.confirmButtonColor = '#e53e3e';
            config.confirmButtonText = 'Entendido';
    }

    Swal.fire(config);
}

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

/* Cambiar estado de rol (activo/inactivo) */
async function toggleRoleStatus(roleId, checkboxEl = null, prevChecked = null) {
    try {
        const response = await fetch(`/admin/roles/${roleId}/cambiar-estado`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();
        if (data.success) {
            // Actualizar UI sin recargar página
            const row = document.querySelector(`.role-row[data-role-id="${roleId}"]`);
            if (row) {
                // Toggle checkbox según nuevo estado
                const checkbox = checkboxEl || row.querySelector('.role-status-toggle');
                if (checkbox) checkbox.checked = !!data.is_active;

                // Actualizar badge de estado
                const statusCell = row.querySelector('.status-cell');
                if (statusCell) {
                    statusCell.innerHTML = data.is_active
                        ? `<span class="status-badge status-active"><iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Activo</span>`
                        : `<span class="status-badge status-inactive" style="background: linear-gradient(135deg, rgba(239,68,68,0.12) 0%, rgba(239,68,68,0.08) 100%); color: #dc2626; border-color: rgba(239,68,68,0.3); box-shadow: 0 2px 4px rgba(239,68,68,0.1);"><iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon> Inactivo</span>`;
                }
            }
            showAlert('success', 'Actualizado', data.message || 'Estado del rol actualizado');
            try { actualizarEstadisticasRoles(); } catch (e) {}
        } else {
            throw new Error(data.message || 'No se pudo cambiar el estado');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', error.message);
        // Revertir UI si falló
        const row = document.querySelector(`.role-row[data-role-id="${roleId}"]`);
        const checkbox = checkboxEl || (row ? row.querySelector('.role-status-toggle') : null);
        if (checkbox !== null && prevChecked !== null) {
            checkbox.checked = prevChecked;
        }
    } finally {
        // Rehabilitar control y liberar bloqueo
        if (checkboxEl) checkboxEl.disabled = false;
        togglingRoles.delete(roleId);
    }
}

// Seleccionar todos los permisos de un módulo (sin checkbox de encabezado)
function selectAllModulePermissions(moduleId) {
    const permissionCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${moduleId}"]`);
    permissionCheckboxes.forEach(cb => { cb.checked = true; });
    updatePermissionCount();
}

/* MODAL DE PERMISOS DEL SISTEMA - ACTUALIZADO PARA NUEVO DISEÑO */
function openPermissionsModal() {
    const modal = document.getElementById('permissionsModal');
    if (!modal) return;
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Animación de entrada suave con nueva clase
    const container = modal.querySelector('.permisos-modal-container');
    if (container) {
        container.style.transform = 'scale(0.95)';
        container.style.opacity = '0';
        container.style.transition = 'all 0.2s ease';
        
        setTimeout(() => {
            container.style.transform = 'scale(1)';
            container.style.opacity = '1';
        }, 10);
    }
}

function closePermissionsModal() {
    const modal = document.getElementById('permissionsModal');
    if (!modal) return;
    
    const container = modal.querySelector('.permisos-modal-container');
    if (container) {
        container.style.transform = 'scale(0.95)';
        container.style.opacity = '0';
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }, 200);
    } else {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Event listener para cerrar modal de permisos al hacer clic fuera
document.addEventListener('click', function(event) {
    const permissionsModal = document.getElementById('permissionsModal');
    if (event.target === permissionsModal) {
        closePermissionsModal();
    }
});

// Event listener para cerrar modal de permisos con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const permissionsModal = document.getElementById('permissionsModal');
        if (permissionsModal && !permissionsModal.classList.contains('hidden')) {
            closePermissionsModal();
        }
    }
}); 

/* ==============================================
   FUNCIONES PROFESIONALES PARA EL NUEVO DISEÑO MODAL
   ============================================== */

// Progress Bar para el modal profesional
function updateProgressBar() {
    const displayNameField = document.getElementById('display_name');
    const selectedPermissions = document.querySelectorAll('.checkbox-permiso:checked');
    const progressBar = document.getElementById('roleProgressBar');
    
    if (!progressBar) return;
    
    let progress = 0;
    
    // 50% por nombre del rol mostrado
    if (displayNameField && displayNameField.value.trim()) {
        progress += 50;
    }
    
    // 50% por permisos seleccionados
    if (selectedPermissions.length > 0) {
        progress += 50;
    }
    
    progressBar.style.width = progress + '%';
}

// Función específica para toggle de módulos (nueva clase CSS)
function toggleModulePermissions(moduleId) {
    const moduleCheckbox = document.getElementById(`module_${moduleId}`);
    const permissionCheckboxes = document.querySelectorAll(`.checkbox-permiso[data-module="${moduleId}"]`);
    
    if (moduleCheckbox && permissionCheckboxes.length > 0) {
        const isChecked = moduleCheckbox.checked;
        
        permissionCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
            // Fallback para navegadores sin soporte :has()
            const card = checkbox.closest('.permiso-card');
            if (card) {
                if (isChecked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            }
        });
        
        updatePermissionCount();
        updateProgressBar();
    }
}

// Actualizar contador de permisos (funciona con nuevas clases CSS)
function updatePermissionCount() {
    const selectedPermissions = document.querySelectorAll('.checkbox-permiso:checked');
    const counter = document.getElementById('selectedPermissionsCount');
    
    if (counter) {
        counter.textContent = selectedPermissions.length;
    }
    
    // También actualizar los contadores de módulos individuales
    updateModuleCounters();
    
    // Actualizar visibilidad de botones de control
    updateControlButtonsVisibility(selectedPermissions.length);
}

// Función para manejar la visibilidad de los botones de control
function updateControlButtonsVisibility(selectedCount) {
    const selectAllBtn = document.querySelector('.btn-seleccionar');
    const deselectAllBtn = document.querySelector('.btn-deseleccionar');
    
    if (selectAllBtn && deselectAllBtn) {
        if (selectedCount === 0) {
            // No hay nada seleccionado: mostrar solo "Seleccionar Todo"
            selectAllBtn.classList.remove('oculto');
            deselectAllBtn.classList.add('oculto');
        } else {
            // Hay algo seleccionado: mostrar solo "Deseleccionar Todo"
            selectAllBtn.classList.add('oculto');
            deselectAllBtn.classList.remove('oculto');
        }
    }
}

// Actualizar contadores de módulos individuales
function updateModuleCounters() {
    const modules = document.querySelectorAll('.modulo-permiso-card');
    
    modules.forEach(moduleCard => {
        const moduleCheckbox = moduleCard.querySelector('.checkbox-modulo');
        const moduleId = moduleCheckbox?.dataset.module;
        if (!moduleId) return;
        
        const allPermissions = moduleCard.querySelectorAll('.checkbox-permiso');
        const selectedPermissions = moduleCard.querySelectorAll('.checkbox-permiso:checked');
        
        // Actualizar estado del checkbox del módulo
        if (moduleCheckbox) {
            if (selectedPermissions.length === 0) {
                moduleCheckbox.checked = false;
                moduleCheckbox.indeterminate = false;
            } else if (selectedPermissions.length === allPermissions.length) {
                moduleCheckbox.checked = true;
                moduleCheckbox.indeterminate = false;
            } else {
                moduleCheckbox.checked = false;
                moduleCheckbox.indeterminate = true;
            }
        }
    });
}

// Setup para color presets del nuevo diseño
function setupColorPresetsRol() {
    const colorPresets = document.querySelectorAll('.color-preset-rol');
    const colorInput = document.getElementById('color');
    
    colorPresets.forEach(preset => {
        preset.addEventListener('click', function() {
            const color = this.dataset.color;
            if (colorInput) {
                colorInput.value = color;
            }
        });
    });
}

// Inicializar eventos del modal profesional
function initializeProfessionalModal() {
    // Setup para color presets del nuevo diseño
    setupColorPresetsRol();
    
    // Eventos para actualizar progress bar
    const nameField = document.getElementById('name');
    const displayNameField = document.getElementById('display_name');
    
    if (nameField) {
        nameField.addEventListener('input', updateProgressBar);
    }
    
    if (displayNameField) {
        displayNameField.addEventListener('input', updateProgressBar);
    }
    
    // Eventos para checkboxes de permisos (nuevas clases)
    const permissionCheckboxes = document.querySelectorAll('.checkbox-permiso');
    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Fallback para navegadores sin soporte :has()
            const card = this.closest('.permiso-card');
            if (card) {
                if (this.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            }
            
            updatePermissionCount();
            updateProgressBar();
        });
    });
    
    // Eventos para botones de seleccionar/deseleccionar todo
    const selectAllBtn = document.querySelector('.btn-seleccionar');
    const deselectAllBtn = document.querySelector('.btn-deseleccionar');
    
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            selectAllPermissionsProfessional();
            updatePermissionCount();
            updateProgressBar();
        });
    }
    
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function() {
            deselectAllPermissionsProfessional();
            updatePermissionCount();
            updateProgressBar();
        });
    }
}

// Funciones de selección actualizadas para el nuevo diseño
function selectAllPermissionsProfessional() {
    const permissionCheckboxes = document.querySelectorAll('.checkbox-permiso');
    const moduleCheckboxes = document.querySelectorAll('.checkbox-modulo');
    
    permissionCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
        // Fallback para navegadores sin soporte :has()
        const card = checkbox.closest('.permiso-card');
        if (card) {
            card.classList.add('selected');
        }
    });
    
    moduleCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
        checkbox.indeterminate = false;
    });
}

function deselectAllPermissionsProfessional() {
    const permissionCheckboxes = document.querySelectorAll('.checkbox-permiso');
    const moduleCheckboxes = document.querySelectorAll('.checkbox-modulo');
    
    permissionCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
        // Fallback para navegadores sin soporte :has()
        const card = checkbox.closest('.permiso-card');
        if (card) {
            card.classList.remove('selected');
        }
    });
    
    moduleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.indeterminate = false;
    });
}

// Actualizar resetRoleForm para funcionar con las nuevas clases
function resetRoleFormProfessional() {
    const form = document.getElementById('roleForm');
    form.reset();
    
    // Resetear color
    const colorInput = document.getElementById('color');
    if (colorInput) {
        colorInput.value = '#e53e3e';
    }
    
    // Limpiar errores de validación
    clearFormErrors();
    
    // Desmarcar permisos (nuevas clases CSS)
    const permissionCheckboxes = document.querySelectorAll('.checkbox-permiso');
    permissionCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
        // Fallback para navegadores sin soporte :has()
        const card = checkbox.closest('.permiso-card');
        if (card) {
            card.classList.remove('selected');
        }
    });
    
    const moduleCheckboxes = document.querySelectorAll('.checkbox-modulo');
    moduleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.indeterminate = false;
    });
    
    // Actualizar contadores y botones
    updatePermissionCount();
    updateProgressBar();
    
    // Asegurar que se muestre solo el botón "Seleccionar Todo"
    updateControlButtonsVisibility(0);
}

// Actualizar función de validación para nuevas clases
function validateRoleFormProfessional() {
    clearFormErrors();
    
    let isValid = true;
    const errors = {};
    
    // Validar nombre del sistema
    const name = document.getElementById('name').value.trim();
    if (!name) {
        errors.name = 'El nombre del sistema es requerido';
        isValid = false;
    } else if (!/^[a-z0-9-]+$/.test(name)) {
        errors.name = 'Solo letras minúsculas, números y guiones';
        isValid = false;
    }
    
    // Validar nombre mostrado
    const displayName = document.getElementById('display_name').value.trim();
    if (!displayName) {
        errors.display_name = 'El nombre mostrado es requerido';
        isValid = false;
    }
    
    // Validar permisos (nuevas clases)
    const selectedPermissions = document.querySelectorAll('.checkbox-permiso:checked');
    if (selectedPermissions.length === 0) {
        errors.permisos = 'Debe seleccionar al menos un permiso';
        isValid = false;
    }
    
    if (!isValid) {
        showFormErrors(errors);
    }
    
    return isValid;
}

// Actualizar populate form para nuevas clases
function populateRoleFormProfessional(role) {
    // Datos básicos
    document.getElementById('roleId').value = role.id;
    document.getElementById('name').value = role.name || '';
    document.getElementById('display_name').value = role.display_name || '';
    document.getElementById('description').value = role.description || '';
    document.getElementById('color').value = role.color || '#e53e3e';
    
    // Permisos (nuevas clases CSS)
    let selectedCount = 0;
    if (role.permissions && role.permissions.length > 0) {
        const permissionCheckboxes = document.querySelectorAll('.checkbox-permiso');
        permissionCheckboxes.forEach(checkbox => {
            const permissionId = checkbox.value;
            const isChecked = role.permissions.some(permission => permission.id == permissionId);
            checkbox.checked = isChecked;
            if (isChecked) selectedCount++;
            
            // Fallback para navegadores sin soporte :has()
            const card = checkbox.closest('.permiso-card');
            if (card) {
                if (isChecked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            }
        });
    }
    
    // Actualizar contadores y botones
    updatePermissionCount();
    updateProgressBar();
    
    // Actualizar visibilidad de botones según permisos cargados
    updateControlButtonsVisibility(selectedCount);
}

// Override de funciones principales para usar las versiones profesionales
// Esto asegura compatibilidad con las nuevas clases CSS

// Actualizar la inicialización
document.addEventListener('DOMContentLoaded', function() {
    initializeRoleManagement();
    
    // Inicializar modal profesional
    initializeProfessionalModal();
});

// Override de selectAllPermissions para usar ambas versiones (compatibilidad)
const originalSelectAll = selectAllPermissions;
selectAllPermissions = function() {
    // Intentar con las clases nuevas primero
    const newCheckboxes = document.querySelectorAll('.checkbox-permiso');
    if (newCheckboxes.length > 0) {
        selectAllPermissionsProfessional();
        updatePermissionCount();
        updateProgressBar();
    } else {
        // Fallback a clases antiguas
        originalSelectAll();
        updatePermissionsCounter();
    }
};

// Override de deselectAllPermissions
const originalDeselectAll = deselectAllPermissions;
deselectAllPermissions = function() {
    // Intentar con las clases nuevas primero
    const newCheckboxes = document.querySelectorAll('.checkbox-permiso');
    if (newCheckboxes.length > 0) {
        deselectAllPermissionsProfessional();
        updatePermissionCount();
        updateProgressBar();
    } else {
        // Fallback a clases antiguas
        originalDeselectAll();
        updatePermissionsCounter();
    }
};

// Override de resetRoleForm
const originalResetForm = resetRoleForm;
resetRoleForm = function() {
    // Intentar con las nuevas clases primero
    const newCheckboxes = document.querySelectorAll('.checkbox-permiso');
    if (newCheckboxes.length > 0) {
        resetRoleFormProfessional();
    } else {
        // Fallback a versión original
        originalResetForm();
    }
};

/* MODAL DE VISTA DE ROL */
function openViewRoleModal(role) {
    // Modal profesional (tema azul) para vista de rol
    const modalHtml = `
        <div id="viewRoleModal" class="modal-profesional">
            <div class="modal-profesional-container tema-azul">
                <div class="header-profesional">
                    <div class="header-content">
                        <div class="header-left">
                            <div class="header-icon ${role.is_protected ? 'icon-protected' : 'icon-normal'}">
                                <iconify-icon icon="${role.is_protected ? 'solar:crown-bold-duotone' : 'solar:shield-user-bold-duotone'}"></iconify-icon>
                            </div>
                            <div class="header-text">
                                <h3>Información del Rol</h3>
                                <p>${role.is_protected ? 'Rol del Sistema (Protegido)' : 'Rol Personalizado'}</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" onclick="closeViewRoleModal()">
                            <iconify-icon icon="heroicons:x-mark"></iconify-icon>
                        </button>
                    </div>
                </div>

                <div class="modal-content-profesional">
                    <div class="seccion-form seccion-azul">
                        <div class="seccion-header">
                            <div class="seccion-icon icon-azul">
                                <iconify-icon icon="solar:info-circle-bold-duotone"></iconify-icon>
                            </div>
                            <div class="seccion-titulo">
                                <h3>Información del Rol</h3>
                                <p>Datos básicos y configuración</p>
                            </div>
                        </div>
                        <div class="grid-campos columnas-2">
                            <div class="campo-grupo">
                                <label class="campo-label"><iconify-icon icon="solar:code-bold-duotone" class="label-icon"></iconify-icon> Nombre del Sistema</label>
                                <div class="field-pill">${role.name}</div>
                            </div>
                            <div class="campo-grupo">
                                <label class="campo-label"><iconify-icon icon="solar:eye-bold-duotone" class="label-icon"></iconify-icon> Nombre Mostrado</label>
                                <div class="field-pill">${role.display_name}</div>
                            </div>
                            <div class="campo-grupo campo-completo">
                                <label class="campo-label"><iconify-icon icon="solar:document-text-bold-duotone" class="label-icon"></iconify-icon> Descripción</label>
                                <div class="field-pill">${role.description || 'Sin descripción'}</div>
                            </div>
                            <div class="campo-grupo">
                                <label class="campo-label"><iconify-icon icon="solar:pallete-bold-duotone" class="label-icon"></iconify-icon> Color</label>
                                <div class="field-pill"><span style="display:inline-block;width:16px;height:16px;border-radius:4px;background:${role.color};margin-right:8px"></span>${role.color || '-'}</div>
                            </div>
                            <div class="campo-grupo">
                                <label class="campo-label"><iconify-icon icon="solar:calendar-bold-duotone" class="label-icon"></iconify-icon> Creado</label>
                                <div class="field-pill">${role.created_at}</div>
                            </div>
                            <div class="campo-grupo">
                                <label class="campo-label"><iconify-icon icon="solar:calendar-bold-duotone" class="label-icon"></iconify-icon> Última modificación</label>
                                <div class="field-pill">${role.updated_at}</div>
                            </div>
                        </div>
                    </div>

                    <div class="seccion-form seccion-morado">
                        <div class="seccion-header">
                            <div class="seccion-icon icon-morado"><iconify-icon icon="solar:chart-square-bold-duotone"></iconify-icon></div>
                            <div class="seccion-titulo"><h3>Estadísticas</h3><p>Resumen del rol</p></div>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-card"><div class="stat-icon"><iconify-icon icon="solar:shield-check-bold-duotone"></iconify-icon></div><div class="stat-content"><div class="stat-number">${role.permissions_count}</div><div class="stat-label">Permisos Asignados</div></div></div>
                            <div class="stat-card"><div class="stat-icon"><iconify-icon icon="solar:users-group-rounded-bold-duotone"></iconify-icon></div><div class="stat-content"><div class="stat-number">${role.users_count}</div><div class="stat-label">${role.users_count === 1 ? 'Usuario' : 'Usuarios'}</div></div></div>
                        </div>
                    </div>

                    <div class="seccion-form seccion-azul">
                        <div class="seccion-header">
                            <div class="seccion-icon icon-azul"><iconify-icon icon="solar:shield-keyhole-bold-duotone"></iconify-icon></div>
                            <div class="seccion-titulo"><h3>Permisos Asignados (${role.permissions_count})</h3><p>Detalle de permisos del rol</p></div>
                        </div>
                        <div class="permissions-list">
                            ${formatPermissionsForView(role.permissions)}
                        </div>
                    </div>
                </div>

                <div class="footer-profesional">
                    <div class="footer-botones">
                        <button type="button" class="btn-cancelar" onclick="closeViewRoleModal()"><iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Entendido</button>
                        ${!role.is_protected ? `<button type="button" class="btn-guardar" onclick="closeViewRoleModal(); editRole(${role.id})"><iconify-icon icon="solar:pen-bold-duotone"></iconify-icon> Editar Rol</button>` : ''}
                    </div>
                </div>
            </div>
        </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = document.getElementById('viewRoleModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Formatear permisos para la vista con etiquetas en español y colapso de dashboard
function formatPermissionsForView(permissions = []) {
    const permLabels = {
        // Dashboard (colapsado a uno)
        'dashboard.access': 'Dashboard',
        // Ventas
        'ventas.view': 'Ver ventas',
        'ventas.create': 'Crear venta',
        'ventas.edit': 'Editar venta',
        'ventas.delete': 'Eliminar venta',
        'ventas.reports': 'Reportes de ventas',
        'ventas.devoluciones': 'Devoluciones de ventas',
        'ventas.clientes': 'Clientes de ventas',
        // Inventario
        'inventario.view': 'Ver inventario',
        'inventario.create': 'Crear inventario',
        'inventario.edit': 'Editar inventario',
        'inventario.delete': 'Eliminar inventario',
        // Productos
        'productos.view': 'Ver productos',
        'productos.create': 'Crear producto',
        'productos.edit': 'Editar producto',
        'productos.delete': 'Eliminar producto',
        // Usuarios
        'usuarios.view': 'Ver usuarios',
        'usuarios.create': 'Crear usuario',
        'usuarios.edit': 'Editar usuario',
        'usuarios.delete': 'Eliminar usuario',
        'usuarios.activate': 'Activar usuario',
        'usuarios.roles': 'Roles de usuario',
        // Compras
        'compras.view': 'Ver compras',
        'compras.create': 'Crear compra',
        'compras.edit': 'Editar compra',
        'compras.delete': 'Eliminar compra',
    };

    let dashboardShown = false;
    const items = [];

    permissions.forEach(permission => {
        const name = permission.name || '';
        let label = permLabels[name] || permission.display_name || name.replace(/\./g, ' ');

        if (name.startsWith('dashboard.')) {
            if (dashboardShown) return; // mostramos Dashboard una sola vez
            label = 'Dashboard';
            dashboardShown = true;
        }

        items.push(`
            <div class="permission-item">
                <div class="permission-icon"><iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon></div>
                <div class="permission-content">
                    <div class="permission-name">${label}</div>
                    <div class="permission-label">${name}</div>
                </div>
            </div>
        `);
    });

    return items.join('');
}

function closeViewRoleModal() {
    const modal = document.getElementById('viewRoleModal');
    if (modal) {
        modal.classList.remove('show');
        
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = 'auto';
        }, 300);
    }
}

/* FUNCIÓN PARA POBLAR FORMULARIO DE EDICIÓN */
function populateRoleFormFromEdit(role) {
    // Datos básicos
    document.getElementById('roleId').value = role.id;
    document.getElementById('display_name').value = role.display_name || '';
    document.getElementById('description').value = role.description || '';
    document.getElementById('color').value = role.color || '#e53e3e';
    
    // Limpiar todos los checkboxes primero
    const allCheckboxes = document.querySelectorAll('.checkbox-permiso, .permission-checkbox');
    allCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
        const card = checkbox.closest('.permiso-card');
        if (card) {
            card.classList.remove('selected');
        }
    });
    
    // Marcar permisos seleccionados
    if (role.permissions && role.permissions.length > 0) {
        role.permissions.forEach(permissionId => {
            // Intentar con las nuevas clases primero
            let checkbox = document.querySelector(`.checkbox-permiso[value="${permissionId}"]`);
            if (!checkbox) {
                // Fallback a clases antiguas
                checkbox = document.querySelector(`.permission-checkbox[value="${permissionId}"]`);
            }
            
            if (checkbox) {
                checkbox.checked = true;
                const card = checkbox.closest('.permiso-card');
                if (card) {
                    card.classList.add('selected');
                }
            }
        });
    }
}

/* ==============================================
   FUNCIONES AUXILIARES PARA ACTUALIZACIÓN DINÁMICA
   ============================================== */

/**
 * Agregar nuevo rol a la tabla dinámicamente
 */
function agregarRolATabla(rol) {
    // Si hay tabla de roles, agregar una fila
    const rolesTableBody = document.querySelector('.roles-table tbody');
    if (rolesTableBody) {
        const tr = document.createElement('tr');
        // Usar estilo neutro sin clases por slug para no alterar colores/fondos
        tr.className = 'role-row';
        tr.setAttribute('data-role-id', rol.id);

        const permisosCount = (rol.permissions && Array.isArray(rol.permissions)) ? rol.permissions.length : (rol.permissions_count || 0);
        const isActive = !!rol.is_active;

        const _displayName = rol.display_name || rol.name;
        const _systemName = rol.name || '';
        const _showSystem = _systemName && _systemName !== _displayName;
        const _color = rol.color || '#e5e7eb';
        tr.innerHTML = `
            <td class="role-cell">
                <div class="role-info">
                    <span class="role-color-indicator" style="background:${_color}"></span>
                    <div class="role-details">
                        <div class="role-name">${_displayName}</div>
                        ${_showSystem ? `<div class="role-system-name">${_systemName}</div>` : ''}
                    </div>
                </div>
            </td>
            <td class="description-cell">
                ${rol.description ? `<span class="description-text">${rol.description}</span>` : `<span class="no-description">Sin descripción</span>`}
            </td>
            <td class="permissions-cell">
                <div class="permissions-info">
                    <div class="permissions-count">
                        <iconify-icon icon="solar:shield-check-bold-duotone" class="permission-icon"></iconify-icon>
                        <span class="count">${permisosCount}</span>
                        <span class="label">permisos</span>
                    </div>
                </div>
            </td>
            <td class="users-cell">
                <div class="users-count">
                    <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="users-icon"></iconify-icon>
                    <span class="count">0</span>
                    <span class="label">usuarios</span>
                </div>
            </td>
            <td class="status-cell">
                ${isActive ? `
                    <span class="status-badge status-active">
                        <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                        Activo
                    </span>
                ` : `
                    <span class="status-badge status-inactive" style="background: linear-gradient(135deg, rgba(239,68,68,0.12) 0%, rgba(239,68,68,0.08) 100%); color: #dc2626; border-color: rgba(239,68,68,0.3); box-shadow: 0 2px 4px rgba(239,68,68,0.1);">
                        <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                        Inactivo
                    </span>
                `}
            </td>
            <td class="actions-cell">
                <div class="action-buttons">
                    <button class="action-btn btn-view" onclick="viewRole(${rol.id})" title="Ver Detalles">
                        <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                    </button>
                    <button class="action-btn btn-edit" onclick="editRole(${rol.id})" title="Editar Rol">
                        <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                    </button>
                    <label class="toggle-switch role-toggle" title="Activar/Desactivar">
                        <input type="checkbox" class="role-status-toggle" data-role-id="${rol.id}" ${isActive ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                    <button class="action-btn btn-delete" onclick="deleteRole(${rol.id})" title="Eliminar Rol">
                        <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                    </button>
                </div>
            </td>
        `;

        rolesTableBody.prepend(tr);
        tr.style.backgroundColor = '#dcfce7';
        setTimeout(() => { tr.style.backgroundColor = ''; }, 1200);
        loadRolesData();
        return;
    }

    // Fallback: agregar al grid si existe
    const rolesGrid = document.querySelector('.roles-grid');
    if (rolesGrid) {
        const newCard = document.createElement('div');
        newCard.className = 'role-card';
        newCard.setAttribute('data-role-id', rol.id);
        newCard.innerHTML = `
            <div class="role-header">
                <div class="role-color" style="background-color: ${rol.color || '#3b82f6'}"></div>
                <div class="role-info">
                    <h3 class="role-name">${rol.name}</h3>
                    <p class="role-description">${rol.description || 'Sin descripción'}</p>
                </div>
                <div class="role-status">
                    <label class="toggle-switch" title="Activar/Desactivar rol">
                        <input type="checkbox" class="role-status-toggle" data-role-id="${rol.id}" ${isActive ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div class="role-stats">
                <div class="stat-item">
                    <span class="stat-label">Permisos</span>
                    <span class="stat-value">${permisosCount}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Usuarios</span>
                    <span class="stat-value">0</span>
                </div>
            </div>
            <div class="role-actions">
                <button class="action-btn action-btn-view" onclick="viewRole(${rol.id})" title="Ver detalles">
                    <iconify-icon icon="heroicons:eye"></iconify-icon>
                </button>
                <button class="action-btn action-btn-edit" onclick="editRole(${rol.id})" title="Editar">
                    <iconify-icon icon="heroicons:pencil"></iconify-icon>
                </button>
                <button class="action-btn action-btn-duplicate" onclick="duplicateRole(${rol.id})" title="Duplicar">
                    <iconify-icon icon="heroicons:document-duplicate"></iconify-icon>
                </button>
                <button class="action-btn action-btn-delete" onclick="deleteRole(${rol.id})" title="Eliminar">
                    <iconify-icon icon="heroicons:trash"></iconify-icon>
                </button>
            </div>
        `;
        rolesGrid.prepend(newCard);
        newCard.style.backgroundColor = '#dcfce7';
        setTimeout(() => { newCard.style.backgroundColor = ''; }, 1200);
        loadRolesData();
    }
}

/**
 * Actualizar rol existente en la tabla
 */
function actualizarRolEnTabla(roleId, rol) {
    console.log('🔄 Actualizando rol en tabla:', roleId, rol);

    // 1) Intentar actualizar en tabla (vista actual)
    let row = document.querySelector(`.roles-table tr.role-row[data-role-id="${roleId}"]`);
    if (row) {
        // Remover cualquier clase basada en slug para mantener estilo neutro del chip
        Array.from(row.classList)
            .filter(c => c.startsWith('role-') && c !== 'role-row')
            .forEach(c => row.classList.remove(c));
        // Nombre mostrado y nombre del sistema
        const roleNameEl = row.querySelector('.role-name');
        const roleSystemEl = row.querySelector('.role-system-name');
        if (roleNameEl) roleNameEl.textContent = (rol.display_name || rol.name || '').toString();
        if (roleSystemEl) {
            const dn = (rol.display_name || rol.name || '').toString();
            const sn = (rol.name || '').toString();
            if (sn && dn && sn === dn) {
                roleSystemEl.remove();
            } else {
                roleSystemEl.textContent = sn;
            }
        }

        // Descripción
        const descCell = row.querySelector('.description-cell');
        if (descCell) {
            const descTextEl = descCell.querySelector('.description-text');
            const noDescEl = descCell.querySelector('.no-description');
            const newDesc = rol.description || '';
            if (newDesc) {
                if (noDescEl) noDescEl.remove();
                if (descTextEl) {
                    descTextEl.textContent = newDesc;
                } else {
                    const span = document.createElement('span');
                    span.className = 'description-text';
                    span.textContent = newDesc;
                    descCell.innerHTML = '';
                    descCell.appendChild(span);
                }
            } else {
                // Sin descripción
                if (descTextEl) descTextEl.remove();
                if (!noDescEl) {
                    const span = document.createElement('span');
                    span.className = 'no-description';
                    span.textContent = 'Sin descripción';
                    descCell.innerHTML = '';
                    descCell.appendChild(span);
                }
            }
        }

        // Contador de permisos
        const permCountEl = row.querySelector('.permissions-cell .permissions-count .count');
        const permCount = (rol.permissions && Array.isArray(rol.permissions)) ? rol.permissions.length : (rol.permissions_count || 0);
        if (permCountEl) permCountEl.textContent = permCount.toString();

        // Estado (badge + checkbox)
        const statusToggle = row.querySelector('.role-status-toggle');
        if (statusToggle && typeof rol.is_active === 'boolean') statusToggle.checked = rol.is_active;
        // Actualizar color del indicador
        const colorIndicator = row.querySelector('.role-color-indicator');
        if (colorIndicator && rol.color) {
            colorIndicator.style.background = rol.color;
        }
        const statusCell = row.querySelector('.status-cell');
        if (statusCell && typeof rol.is_active === 'boolean') {
            statusCell.innerHTML = rol.is_active
                ? `<span class="status-badge status-active"><iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Activo</span>`
                : `<span class="status-badge status-inactive" style="background: linear-gradient(135deg, rgba(239,68,68,0.12) 0%, rgba(239,68,68,0.08) 100%); color: #dc2626; border-color: rgba(239,68,68,0.3); box-shadow: 0 2px 4px rgba(239,68,68,0.1);"><iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon> Inactivo</span>`;
        }

        // Evitar alteraciones visuales: no aplicar cambios de fondo/animación en la fila

        // Actualizar datos locales y estadísticas
        loadRolesData();
        try { actualizarEstadisticasRoles(); } catch (e) {}
        return;
    }

    // 2) Fallback: actualizar en tarjetas (si la vista fuera grid)
    let card = document.querySelector(`[data-role-id="${roleId}"]`);
    if (card) {
        const permCount = (rol.permissions && Array.isArray(rol.permissions)) ? rol.permissions.length : (rol.permissions_count || 0);
        const roleNameEl = card.querySelector('.role-name');
        const roleDescEl = card.querySelector('.role-description');
        const roleColorEl = card.querySelector('.role-color');
        const permissionsCountEl = card.querySelector('.stat-value');
        const statusToggle = card.querySelector('.role-status-toggle');

        if (roleNameEl) roleNameEl.textContent = rol.name || '';
        if (roleDescEl) roleDescEl.textContent = rol.description || 'Sin descripción';
        if (roleColorEl) roleColorEl.style.backgroundColor = rol.color || '#3b82f6';
        if (permissionsCountEl) permissionsCountEl.textContent = permCount.toString();
        if (statusToggle && typeof rol.is_active === 'boolean') statusToggle.checked = rol.is_active;

        card.style.transition = 'background-color 0.3s ease';
        card.style.backgroundColor = '#dbeafe';
        setTimeout(() => { card.style.backgroundColor = ''; }, 1200);
        loadRolesData();
        try { actualizarEstadisticasRoles(); } catch (e) {}
        return;
    }

    console.warn('❌ No se encontró elemento de rol para actualizar:', roleId);
}

/**
 * Eliminar rol de la tabla dinámicamente
 */
function eliminarRolDeTabla(roleId) {
    const card = document.querySelector(`[data-role-id="${roleId}"]`);
    if (!card) return;
    
    // Animar eliminación
    card.style.backgroundColor = '#fecaca';
    card.style.transform = 'scale(0.95)';
    card.style.opacity = '0.5';
    
    setTimeout(() => {
        card.remove();
        
        // Verificar si quedan roles
        const rolesGrid = document.querySelector('.roles-grid');
        const remainingCards = rolesGrid.querySelectorAll('.role-card');
        
        if (remainingCards.length === 0) {
            // Mostrar mensaje de tabla vacía
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.innerHTML = `
                <div class="empty-content">
                    <iconify-icon icon="heroicons:shield-check" class="empty-icon"></iconify-icon>
                    <h3>No hay roles registrados</h3>
                    <p>Comienza creando el primer rol del sistema</p>
                    <button type="button" class="btn-action btn-primary" onclick="openCreateRoleModal()">
                        <iconify-icon icon="heroicons:plus"></iconify-icon>
                        Crear Rol
                    </button>
                </div>
            `;
            rolesGrid.appendChild(emptyState);
        }
        
        // Actualizar datos locales
        loadRolesData();
    }, 500);
}

/**
 * Actualizar estadísticas de roles dinámicamente
 */
function actualizarEstadisticasRoles() {
    // Contar roles actuales en el grid
    const cards = document.querySelectorAll('.roles-grid .role-card');
    const totalRoles = cards.length;
    
    let activosCount = 0;
    let totalPermisos = 0;
    let totalUsuarios = 0;
    
    cards.forEach(card => {
        // Contar activos
        const statusToggle = card.querySelector('.role-status-toggle');
        if (statusToggle && statusToggle.checked) {
            activosCount++;
        }
        
        // Sumar permisos y usuarios
        const statValues = card.querySelectorAll('.stat-value');
        if (statValues[0]) totalPermisos += parseInt(statValues[0].textContent) || 0;
        if (statValues[1]) totalUsuarios += parseInt(statValues[1].textContent) || 0;
    });
    
    // Actualizar valores en las estadísticas si existen
    const statCards = document.querySelectorAll('.stat-value');
    if (statCards[0]) statCards[0].textContent = totalRoles;
    if (statCards[1]) statCards[1].textContent = activosCount;
    if (statCards[2]) statCards[2].textContent = totalPermisos;
    if (statCards[3]) statCards[3].textContent = totalUsuarios;
    
    // Actualizar porcentajes si existen
    const percentageElements = document.querySelectorAll('.stat-change');
    if (percentageElements[0] && totalRoles > 0) {
        const activosPercent = Math.round((activosCount / totalRoles) * 100);
        percentageElements[0].innerHTML = `<iconify-icon icon="heroicons:arrow-trending-up"></iconify-icon>+${activosPercent}% Activos`;
    }
}


/**
 * Recargar tabla completa de roles con skeleton loading (AJAX)
 */
/* RENDERIZADO DINÁMICO DE TABLA */
function renderRolesTabla(roles) {
    const rolesGrid = document.querySelector('.roles-grid');
    if (!rolesGrid) return;
    
    // Limpiar grid actual
    rolesGrid.innerHTML = '';
    
    // Si no hay roles, mostrar mensaje
    if (!roles || roles.length === 0) {
        rolesGrid.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <iconify-icon icon="solar:shield-cross-bold-duotone"></iconify-icon>
                </div>
                <h3>No hay roles disponibles</h3>
                <p>Crea el primer rol para comenzar a gestionar permisos</p>
            </div>
        `;
        return;
    }
    
    // Renderizar cada rol
    roles.forEach(rol => {
        const roleCard = document.createElement('div');
        roleCard.className = 'role-card';
        roleCard.setAttribute('data-role-id', rol.id);
        
        roleCard.innerHTML = `
            <div class="role-header">
                <div class="role-color" style="background-color: ${rol.color || '#3b82f6'}"></div>
                <div class="role-info">
                    <h3 class="role-name">${rol.display_name || rol.name}</h3>
                    <p class="role-description">${rol.description || 'Sin descripción'}</p>
                </div>
                <div class="role-status">
                    <label class="toggle-switch" title="Activar/Desactivar rol">
                        <input type="checkbox" class="role-status-toggle" data-role-id="${rol.id}" ${rol.is_active ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <div class="role-stats">
                <div class="stat-item">
                    <span class="stat-label">Permisos</span>
                    <span class="stat-value">${rol.permissions_count || 0}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Usuarios</span>
                    <span class="stat-value">${rol.users_count || 0}</span>
                </div>
            </div>
            <div class="role-actions">
                <button class="action-btn action-btn-view" onclick="viewRole(${rol.id})" title="Ver detalles">
                    <iconify-icon icon="heroicons:eye"></iconify-icon>
                </button>
                ${!rol.is_protected ? `
                    <button class="action-btn action-btn-edit" onclick="editRole(${rol.id})" title="Editar">
                        <iconify-icon icon="heroicons:pencil"></iconify-icon>
                    </button>
                    <button class="action-btn action-btn-duplicate" onclick="duplicateRole(${rol.id})" title="Duplicar">
                        <iconify-icon icon="heroicons:document-duplicate"></iconify-icon>
                    </button>
                    <button class="action-btn action-btn-delete" onclick="deleteRole(${rol.id})" title="Eliminar">
                        <iconify-icon icon="heroicons:trash"></iconify-icon>
                    </button>
                ` : `
                    <span class="protected-badge" title="Rol protegido del sistema">
                        <iconify-icon icon="solar:shield-check-bold-duotone"></iconify-icon>
                        Protegido
                    </span>
                `}
            </div>
        `;
        
        rolesGrid.appendChild(roleCard);
    });
    
    // Actualizar datos locales
    rolesData = roles.map(rol => ({
        id: rol.id,
        name: rol.display_name || rol.name,
        description: rol.description || '',
        element: rolesGrid.querySelector(`[data-role-id="${rol.id}"]`)
    }));
}

async function recargarTablaRoles() {
    console.log('🔄 Recargando tabla de roles...');
    
    try {
        // Mostrar skeleton loading
        const skeleton = document.getElementById('rolesSkeleton');
        const rolesGrid = document.querySelector('.roles-grid');
        
        if (skeleton && rolesGrid) {
            skeleton.style.display = 'block';
            rolesGrid.style.display = 'none';
        }
        
        // Hacer petición AJAX para obtener datos actualizados
        const response = await fetch(ROLES_API_URL, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) throw new Error('Error al recargar los roles');
        
        const data = await response.json();
        if (data.success && data.data) {
            // Renderizar tabla dinámicamente
            renderRolesTabla(data.data);
            
            // Ocultar skeleton y mostrar grid
            if (skeleton && rolesGrid) {
                skeleton.style.display = 'none';
                rolesGrid.style.display = 'grid';
            }
        } else {
            throw new Error('Datos inválidos recibidos del servidor');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', 'No se pudo recargar la tabla de roles');
        
        // Ocultar skeleton en caso de error
        const skeleton = document.getElementById('rolesSkeleton');
        const rolesGrid = document.querySelector('.roles-grid');
        if (skeleton && rolesGrid) {
            skeleton.style.display = 'none';
            rolesGrid.style.display = 'grid';
        }
    }
}

// Recargar la tabla actual de roles (no grid) desde la API y actualizar filas existentes
async function recargarTablaRolesTabla() {
    try {
        const response = await fetch(ROLES_API_URL, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) throw new Error('Error al obtener roles');
        const data = await response.json();
        const roles = data.data || [];
        roles.forEach(role => {
            const row = document.querySelector(`.roles-table tr.role-row[data-role-id="${role.id}"]`);
            if (row) {
                // Nombre mostrado y nombre del sistema
                const roleNameEl = row.querySelector('.role-name');
                const roleSystemEl = row.querySelector('.role-system-name');
                if (roleNameEl) roleNameEl.textContent = role.display_name || role.name || '';
                if (roleSystemEl) roleSystemEl.textContent = role.name || '';

                // Descripción
                const descCell = row.querySelector('.description-cell');
                if (descCell) {
                    descCell.innerHTML = role.description
                        ? `<span class="description-text">${role.description}</span>`
                        : `<span class="no-description">Sin descripción</span>`;
                }

                // Permisos
                const permCount = Array.isArray(role.permissions) ? role.permissions.length : (role.permissions_count || 0);
                const permCell = row.querySelector('.permissions-cell .permissions-count');
                if (permCell) {
                    if (role.is_protected) {
                        permCell.innerHTML = `
                            <iconify-icon icon="solar:shield-check-bold-duotone" class="permission-icon"></iconify-icon>
                            <span class="count">Acceso</span>
                            <span class="label">completo</span>
                        `;
                    } else {
                        permCell.innerHTML = `
                            <iconify-icon icon="solar:shield-check-bold-duotone" class="permission-icon"></iconify-icon>
                            <span class="count">${permCount}</span>
                            <span class="label">permisos</span>
                        `;
                    }
                }

                // Usuarios
                const usersCell = row.querySelector('.users-cell .users-count');
                if (usersCell) {
                    const ucount = role.users_count ?? 0;
                    usersCell.innerHTML = `
                        <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="users-icon"></iconify-icon>
                        <span class="count">${ucount}</span>
                        <span class="label">${ucount === 1 ? 'usuario' : 'usuarios'}</span>
                    `;
                }

                // Estado
                const statusCell = row.querySelector('.status-cell');
                if (statusCell) {
                    statusCell.innerHTML = role.is_active
                        ? `<span class="status-badge status-active"><iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Activo</span>`
                        : `<span class="status-badge status-inactive" style="background: linear-gradient(135deg, rgba(239,68,68,0.12) 0%, rgba(239,68,68,0.08) 100%); color: #dc2626; border-color: rgba(239,68,68,0.3); box-shadow: 0 2px 4px rgba(239,68,68,0.1);"><iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon> Inactivo</span>`;
                }
                const statusToggle = row.querySelector('.role-status-toggle');
                if (statusToggle) statusToggle.checked = !!role.is_active;
            } else {
                // Si la fila no existe (nuevo rol), agregarla
                agregarRolATabla(role);
            }
        });
        try { actualizarEstadisticasRoles(); } catch (e) {}
    } catch (error) {
        console.error('Error al sincronizar tabla de roles:', error);
    }
}
// Construye el HTML de una fila de la tabla acorde al Blade
function construirFilaRolHTML(role) {
    const isProtected = ['dueño','gerente'].includes((role.name || '').toLowerCase());
    const permisosCount = Array.isArray(role.permissions) ? role.permissions.length : (role.permissions_count || 0);
    const usuariosCount = role.users_count ?? (role.users ? role.users.length : 0);
    return `
        <tr class="role-row role-${role.name}" data-role-id="${role.id}">
            <td class="role-cell">
                <div class="role-info">
                    <div class="role-details">
                        ${isProtected ? `
                            <div class="protected-badge-container">
                                <span class="protected-badge">
                                    <iconify-icon icon="solar:crown-bold-duotone"></iconify-icon>
                                    Protegido
                                </span>
                            </div>
                        ` : ''}
                        <div class="role-name">${role.display_name || role.name || ''}</div>
                        <div class="role-system-name">${role.name || ''}</div>
                    </div>
                </div>
            </td>
            <td class="description-cell">
                ${role.description ? `<span class="description-text">${role.description}</span>` : `<span class="no-description">Sin descripción</span>`}
            </td>
            <td class="permissions-cell">
                <div class="permissions-info">
                    <div class="permissions-count">
                        <iconify-icon icon="solar:shield-check-bold-duotone" class="permission-icon"></iconify-icon>
                        ${isProtected ? `
                            <span class="count">Acceso</span>
                            <span class="label">completo</span>
                        ` : `
                            <span class="count">${permisosCount}</span>
                            <span class="label">permisos</span>
                        `}
                    </div>
                </div>
            </td>
            <td class="users-cell">
                <div class="users-count">
                    <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="users-icon"></iconify-icon>
                    <span class="count">${usuariosCount}</span>
                    <span class="label">${usuariosCount === 1 ? 'usuario' : 'usuarios'}</span>
                </div>
            </td>
            <td class="status-cell">
                ${role.is_active ? `
                    <span class="status-badge status-active"><iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon> Activo</span>
                ` : `
                    <span class="status-badge status-inactive" style="background: linear-gradient(135deg, rgba(239,68,68,0.12) 0%, rgba(239,68,68,0.08) 100%); color: #dc2626; border-color: rgba(239,68,68,0.3); box-shadow: 0 2px 4px rgba(239,68,68,0.1);"><iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon> Inactivo</span>
                `}
            </td>
            <td class="actions-cell">
                <div class="action-buttons">
                    <button class="action-btn btn-view" onclick="viewRole(${role.id})" title="Ver Detalles">
                        <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                    </button>
                    ${isProtected ? `
                        <button class="action-btn btn-protected" title="Rol Protegido - No se puede editar">
                            <iconify-icon icon="solar:shield-keyhole-bold-duotone"></iconify-icon>
                        </button>
                        <button class="action-btn btn-protected" title="Rol Protegido - No se puede eliminar">
                            <iconify-icon icon="solar:shield-warning-bold-duotone"></iconify-icon>
                        </button>
                    ` : `
                        <button class="action-btn btn-edit" onclick="editRole(${role.id})" title="Editar Rol">
                            <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                        </button>
                        <label class="toggle-switch role-toggle" title="Activar/Desactivar">
                            <input type="checkbox" class="role-status-toggle" data-role-id="${role.id}" ${role.is_active ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                        <button class="action-btn btn-delete" onclick="deleteRole(${role.id})" title="Eliminar Rol">
                            <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                        </button>
                    `}
                </div>
            </td>
        </tr>
    `;
}

// Recarga la tabla reconstruyendo el tbody con datos del API
async function recargarTablaRolesFull() {
    const skeleton = document.getElementById('rolesSkeleton');
    const tbody = document.querySelector('.roles-table tbody');
    try {
        if (skeleton && tbody) {
            skeleton.style.display = 'block';
            tbody.style.display = 'none';
        }
        const response = await fetch('/admin/roles/api', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) throw new Error('Error al obtener roles');
        const data = await response.json();
        const roles = data.data || [];
        const html = roles.map(construirFilaRolHTML).join('');
        if (tbody) {
            tbody.innerHTML = html;
        }
        if (skeleton && tbody) {
            skeleton.style.display = 'none';
            tbody.style.display = '';
        }
        try { actualizarEstadisticasRoles(); } catch (e) {}
    } catch (error) {
        console.error('Error al reconstruir la tabla:', error);
        if (skeleton && tbody) {
            skeleton.style.display = 'none';
            tbody.style.display = '';
        }
    }
}