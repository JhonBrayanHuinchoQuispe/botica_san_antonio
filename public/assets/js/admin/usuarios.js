/* ==============================================
   GESTIÓN DE USUARIOS - JAVASCRIPT
   ============================================== */

// Variables globales
let currentUserId = null;
let isEditMode = false;
let usersData = [];

// Configuración CSRF
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
console.log('CSRF Token:', csrfToken); // Debug

// Event Listeners al cargar la página
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM cargado, inicializando gestión de usuarios...'); // Debug
    initializeUserManagement();
});

// Soporte para Turbo/Hotwire: re-inicializar al cargar cada visita
document.addEventListener('turbo:load', function () {
    console.log('turbo:load detectado, re-inicializando gestión de usuarios...');
    initializeUserManagement();
});

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

// Helpers de preloader para Usuarios
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
    const skeletonBody = document.getElementById('usuariosSkeletonBody');
    const tbody = document.getElementById('usuariosTableBody');

    if (skeletonBody && tbody) {
        skeletonBody.style.display = 'table-row-group';
        tbody.style.display = 'none';

        // Simular tiempo de carga y luego mostrar los datos reales
        setTimeout(() => {
            skeletonBody.style.display = 'none';
            tbody.style.display = 'table-row-group';
        }, 800); // 800ms para que se aprecie mejor el efecto profesional
    }
}

// Función de test para verificar que las funciones están disponibles
window.testViewUser = function (userId) {
    console.log('Test viewUser con ID:', userId);
    viewUser(userId);
};

// Hacer funciones accesibles globalmente para onclick
window.viewUser = viewUser;
window.deleteUser = deleteUser;
window.editUser = editUser;
window.exportUsers = exportUsers;
window.openCreateUserModal = openCreateUserModal;
window.closeUserModal = closeUserModal;
window.removeAvatar = removeAvatar;
window.togglePassword = togglePassword;
window.toggleRoleSelection = toggleRoleSelection;
window.submitUserForm = submitUserForm;

/* ==============================================
   INICIALIZACIÓN
   ============================================== */
function initializeUserManagement() {
    setupEventListeners();
    mostrarSkeletonInicial();
    loadUsersData();
    setupFilters();
    setupAvatarUpload();
}

function setupEventListeners() {
    // Formulario de usuario
    const userForm = document.getElementById('userForm');
    console.log('🔧 Configurando event listeners...');
    console.log('📝 Formulario encontrado:', userForm ? 'SÍ' : 'NO');

    if (userForm) {
        userForm.addEventListener('submit', handleUserFormSubmit);
        console.log('✅ Event listener agregado al formulario');
    } else {
        console.log('❌ No se encontró el formulario userForm');
    }

    // Filtros
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');

    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterUsers, 300));
    }
    if (roleFilter) {
        roleFilter.addEventListener('change', filterUsers);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterUsers);
    }

    // Cerrar modal con escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeUserModal();
        }
    });

    // Cerrar modal al hacer clic fuera
    const modal = document.getElementById('userModal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeUserModal();
            }
        });
    }

    // Toggle de estado en la tabla de usuarios
    document.addEventListener('change', function (e) {
        const toggle = e.target.closest('.user-status-toggle');
        if (toggle) {
            const userId = toggle.dataset.userId;
            if (userId) {
                toggleUserStatus(userId);
            }
        }
    });
}

/* ==============================================
   GESTIÓN DE DATOS
   ============================================== */
function loadUsersData() {
    const tableRows = document.querySelectorAll('.user-row');
    usersData = Array.from(tableRows).map(row => {
        const userData = {
            id: row.dataset.userId,
            name: row.querySelector('.user-name')?.textContent.trim(),
            email: row.querySelector('.email-cell')?.textContent.trim(),
            telefono: row.dataset.telefono || row.querySelector('.user-phone')?.textContent.trim(),
            direccion: row.dataset.direccion || '',
            roles: Array.from(row.querySelectorAll('.role-badge')).map(badge => badge.textContent.trim()),
            isActive: row.querySelector('.status-active') ? true : false,
            element: row
        };
        return userData;
    });
}

/* ==============================================
   FILTROS Y BÚSQUEDA
   ============================================== */
function filterUsers() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const roleFilter = document.getElementById('roleFilter')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';

    usersData.forEach(user => {
        let shouldShow = true;

        // Filtro de búsqueda
        if (searchTerm) {
            const searchableText = `${user.name} ${user.email} ${user.cargo}`.toLowerCase();
            shouldShow = shouldShow && searchableText.includes(searchTerm);
        }

        // Filtro de rol
        if (roleFilter) {
            const userRoleIds = Array.from(user.element.querySelectorAll('.role-badge')).map(badge => {
                // Aquí deberías obtener el ID del rol, por ahora uso el texto
                return badge.textContent.trim();
            });
            shouldShow = shouldShow && userRoleIds.some(roleId => roleId.includes(roleFilter));
        }

        // Filtro de estado
        if (statusFilter !== '') {
            const isActive = statusFilter === '1';
            shouldShow = shouldShow && (user.isActive === isActive);
        }

        // Mostrar/ocultar fila
        user.element.style.display = shouldShow ? '' : 'none';
    });

    // Mostrar mensaje si no hay resultados
    updateEmptyState();
}

function updateEmptyState() {
    const visibleRows = usersData.filter(user => user.element.style.display !== 'none');
    const tbody = document.querySelector('.users-table tbody');
    const existingEmptyState = tbody.querySelector('.filter-empty-state');

    if (visibleRows.length === 0 && usersData.length > 0) {
        if (!existingEmptyState) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'filter-empty-state';
            emptyRow.innerHTML = `
                <td colspan="7" class="empty-state">
                    <div class="empty-content">
                        <iconify-icon icon="heroicons:magnifying-glass" class="empty-icon"></iconify-icon>
                        <h3>No se encontraron usuarios</h3>
                        <p>Intenta con otros filtros de búsqueda</p>
                        <button type="button" class="btn-action btn-primary" onclick="clearFilters()">
                            <iconify-icon icon="heroicons:arrow-path"></iconify-icon>
                            Limpiar Filtros
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
    document.getElementById('searchInput').value = '';
    document.getElementById('roleFilter').value = '';
    document.getElementById('statusFilter').value = '';
    filterUsers();
}

/* ==============================================
   MODAL DE USUARIO
   ============================================== */
function openCreateUserModal() {
    isEditMode = false;
    currentUserId = null;

    // Resetear formulario
    resetUserForm();

    // Quitar clase modo-editar si existe
    const modalContainer = document.querySelector('.modal-profesional-container');
    const userModal = document.getElementById('userModal');
    if (userModal) {
        userModal.classList.remove('modo-editar');
    }
    if (modalContainer) {
        modalContainer.classList.remove('modo-editar');
    }

    // Configurar modal para crear
    document.getElementById('modalTitle').textContent = 'Crear Nuevo Usuario';
    document.getElementById('modalIcon').setAttribute('icon', 'solar:user-plus-bold-duotone');
    document.getElementById('submitButtonText').textContent = 'Crear Usuario';
    document.getElementById('passwordRequired').style.display = 'inline';
    document.getElementById('confirmPasswordRequired').style.display = 'inline';

    // Hacer contraseñas requeridas
    document.getElementById('password').required = true;
    document.getElementById('password_confirmation').required = true;

    // Inicializar medidor de contraseña en estado vacío
    setTimeout(() => {
        const strengthBar = document.getElementById('password-strength');
        const strengthText = document.getElementById('password-strength-text');
        if (strengthBar && strengthText) {
            strengthBar.className = 'strength-fill';
            strengthBar.style.width = '0%';
            strengthText.textContent = 'Muy débil';
        }
    }, 100);

    // Mostrar modal
    userModal.classList.remove('hidden');

    // Focus en primer campo
    setTimeout(() => {
        document.getElementById('nombres').focus();
    }, 100); // Reducido de 200ms a 100ms
}

async function editUser(userId) {
    try {
        showLoading('Cargando datos para editar...');
        const response = await fetch(`/admin/usuarios/${userId}/editar`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            if (!data.user.can_edit) {
                showAlert('warning', 'Usuario Protegido', 'Este usuario no puede ser modificado');
                return;
            }

            isEditMode = true;
            currentUserId = userId;

            // Configurar modal para editar
            document.getElementById('modalTitle').textContent = 'Editar Usuario';
            document.getElementById('modalIcon').setAttribute('icon', 'solar:user-edit-bold-duotone');
            document.getElementById('submitButtonText').textContent = 'Actualizar Usuario';
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('confirmPasswordRequired').style.display = 'none';

            // Hacer contraseñas opcionales
            document.getElementById('password').required = false;
            document.getElementById('password_confirmation').required = false;

            // Cargar datos del usuario
            populateUserFormFromEdit(data.user);

            // Agregar clase para cambiar color del header a verde (modo editar)
            const modal = document.getElementById('userModal');
            const modalContainer = document.querySelector('.modal-profesional-container');
            if (modal) {
                modal.classList.add('modo-editar');
            }
            if (modalContainer) {
                modalContainer.classList.add('modo-editar');
            }

            // Mostrar modal
            modal.classList.remove('hidden');
            hideLoading();

            // Focus en primer campo
            setTimeout(() => {
                document.getElementById('nombres').focus();
            }, 100);
        } else {
            throw new Error(data.message || 'Error al cargar datos del usuario');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', 'No se pudieron cargar los datos del usuario');
        hideLoading();
    }
}

function closeUserModal() {
    const modal = document.getElementById('userModal');
    const modalContainer = document.querySelector('.modal-profesional-container');

    modal.classList.add('hidden');
    modal.classList.remove('modo-editar');
    if (modalContainer) {
        modalContainer.classList.remove('modo-editar');
    }

    resetUserForm();
    currentUserId = null;
    isEditMode = false;
}

function resetUserForm() {
    const form = document.getElementById('userForm');
    form.reset();

    // Resetear avatar
    resetAvatarPreview();

    // Limpiar errores de validación
    clearFormErrors();

    // Resetear medidor de contraseña
    const strengthBar = document.getElementById('password-strength');
    const strengthText = document.getElementById('password-strength-text');
    if (strengthBar && strengthText) {
        strengthBar.className = 'strength-fill';
        strengthBar.style.width = '0%';
        strengthText.textContent = 'Muy débil';
    }

    // Ocultar indicador de contraseñas coincidentes
    const matchIndicator = document.getElementById('password-match-indicator');
    if (matchIndicator) {
        matchIndicator.style.display = 'none';
    }

    // Desmarcar roles y remover clase selected
    const roleCheckboxes = document.querySelectorAll('.role-checkbox-hidden');
    roleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });

    // Remover clase selected de las cards de roles
    const roleCards = document.querySelectorAll('.role-card-moderno');
    roleCards.forEach(card => {
        card.classList.remove('selected');
    });

    // Resetear placeholders y requerimientos de contraseña para modo crear
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('password_confirmation');
    const passwordRequired = document.getElementById('passwordRequired');
    const confirmPasswordRequired = document.getElementById('confirmPasswordRequired');

    if (passwordField) {
        passwordField.setAttribute('required', 'required');
        passwordField.placeholder = 'Mínimo 8 caracteres';
    }
    if (confirmPasswordField) {
        confirmPasswordField.setAttribute('required', 'required');
        confirmPasswordField.placeholder = 'Repita la contraseña';
    }
    if (passwordRequired) {
        passwordRequired.style.display = 'inline';
    }
    if (confirmPasswordRequired) {
        confirmPasswordRequired.style.display = 'inline';
    }
}

function resetAvatarPreview() {
    const avatarImage = document.getElementById('avatarImage');
    const avatarPlaceholder = document.getElementById('avatarPlaceholder');
    const removeBtn = document.getElementById('removeAvatarBtn');
    const buttonsContainer = document.getElementById('avatarButtonsContainer');

    avatarImage.style.display = 'none';
    avatarPlaceholder.style.display = 'flex';
    removeBtn.style.display = 'none';
    avatarImage.src = '';

    // Remover clase de imagen cuando no hay imagen
    if (buttonsContainer) {
        buttonsContainer.classList.remove('has-image');
    }
}

/* ==============================================
   CARGA DE DATOS DEL USUARIO
   ============================================== */
async function loadUserData(userId) {
    try {
        showFormLoading(true);

        const response = await fetch(`/admin/usuarios/${userId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });

        const data = await response.json();

        if (data.success) {
            populateUserForm(data.user);
        } else {
            throw new Error(data.message || 'Error al cargar datos del usuario');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', 'No se pudieron cargar los datos del usuario');
    } finally {
        showFormLoading(false);
    }
}

function populateUserForm(user) {
    // Datos básicos
    document.getElementById('userId').value = user.id;
    document.getElementById('nombres').value = user.nombres || '';
    document.getElementById('apellidos').value = user.apellidos || '';
    document.getElementById('email').value = user.email || '';
    document.getElementById('telefono').value = user.telefono || '';
    document.getElementById('cargo').value = user.cargo || '';
    document.getElementById('direccion').value = user.direccion || '';

    // Avatar
    if (user.avatar) {
        const avatarImage = document.getElementById('avatarImage');
        const avatarPlaceholder = document.getElementById('avatarPlaceholder');
        const removeBtn = document.getElementById('removeAvatarBtn');
        const buttonsContainer = document.getElementById('avatarButtonsContainer');

        avatarImage.src = `/storage/${user.avatar}`;
        avatarImage.style.display = 'block';
        avatarPlaceholder.style.display = 'none';
        removeBtn.style.display = 'flex';

        // Agregar clase para botones en fila
        if (buttonsContainer) {
            buttonsContainer.classList.add('has-image');
        }
    }

    // Roles
    if (user.roles && user.roles.length > 0) {
        const roleCheckboxes = document.querySelectorAll('.role-checkbox-hidden');
        const roleCards = document.querySelectorAll('.role-card-moderno');

        roleCheckboxes.forEach(checkbox => {
            const roleValue = checkbox.value;
            const isSelected = user.roles.some(role => role.name === roleValue);

            checkbox.checked = isSelected;

            // Actualizar la card correspondiente
            if (isSelected) {
                const roleCard = checkbox.closest('.role-card-moderno');
                if (roleCard) {
                    roleCard.classList.add('selected');
                }
            }
        });
    }
}

/* ==============================================
   ENVÍO DE FORMULARIO
   ============================================== */
async function handleUserFormSubmit(e) {
    e.preventDefault();
    console.log('🚀 Formulario enviado - handleUserFormSubmit ejecutado');

    if (!validateUserForm()) {
        console.log('❌ Validación falló');
        return;
    }

    console.log('✅ Validación pasó');
    const formData = new FormData(e.target);

    // Agregar roles seleccionados
    const selectedRoles = Array.from(document.querySelectorAll('.role-checkbox-hidden:checked'))
        .map(checkbox => checkbox.value);

    if (selectedRoles.length === 0) {
        showAlert('warning', 'Validación', 'Debe seleccionar al menos un rol');
        return;
    }

    selectedRoles.forEach(roleId => {
        formData.append('roles[]', roleId);
    });

    try {
        showFormLoading(true);

        const base = `${getAppPrefix()}/admin/usuarios`;
        const url = isEditMode ? `${base}/${currentUserId}` : base;
        const method = isEditMode ? 'POST' : 'POST';

        console.log('📡 Preparando petición:', {
            url: url,
            method: method,
            isEditMode: isEditMode,
            csrfToken: csrfToken ? 'Presente' : 'Ausente'
        });

        if (isEditMode) {
            formData.append('_method', 'PUT');
        }

        // Log de los datos del formulario
        console.log('📋 Datos del formulario:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}:`, value);
        }

        const response = await fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData
        });

        console.log('📨 Respuesta recibida:', response.status, response.statusText);

        const data = await response.json();

        if (data.success) {
            showAlert('success', '¡Éxito!', data.message);
            // Preservar el modo y el ID antes de cerrar el modal
            const wasEditMode = isEditMode;
            const idToUpdate = currentUserId;
            closeUserModal();
            const usuarioResp = data.user || data.usuario;
            if (wasEditMode && usuarioResp) {
                actualizarUsuarioEnTabla(idToUpdate, usuarioResp);
            } else if (!wasEditMode && usuarioResp) {
                agregarUsuarioATabla(usuarioResp);
            } else {
                // Fallback sin recargar toda la tabla: obtener usuario por ID y actualizar fila
                try {
                    const baseU = window.USERS_BASE_URL || (new URL('/admin/usuarios', window.location.origin)).href;
                    const r = await fetch(`${baseU}/${idToUpdate}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (r.ok) {
                        const j = await r.json();
                        if (j.success && j.user) {
                            actualizarUsuarioEnTabla(idToUpdate, j.user);
                        }
                    }
                } catch (_) {
                    // Si falla, al menos intentar actualizar estadísticas
                    try { actualizarEstadisticasUsuarios(); } catch (e) { }
                }
            }
        } else {
            if (data.errors) {
                showFormErrors(data.errors);
            } else {
                throw new Error(data.message || 'Error al procesar la solicitud');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', error.message || 'Error al procesar la solicitud');
    } finally {
        showFormLoading(false);
    }
}

/* ==============================================
   VALIDACIÓN DEL FORMULARIO
   ============================================== */
function validateUserForm() {
    console.log('🔍 Iniciando validación del formulario...');
    clearFormErrors();

    let isValid = true;
    const errors = {};

    // Validar nombres
    const nombres = document.getElementById('nombres').value.trim();
    console.log('📝 Nombres:', nombres);
    if (!nombres) {
        errors.nombres = 'Los nombres son requeridos';
        isValid = false;
        console.log('❌ Error: Nombres vacío');
    }

    // Validar apellidos
    const apellidos = document.getElementById('apellidos').value.trim();
    if (!apellidos) {
        errors.apellidos = 'Los apellidos son requeridos';
        isValid = false;
    }

    // Validar email
    const email = document.getElementById('email').value.trim();
    if (!email) {
        errors.email = 'El email es requerido';
        isValid = false;
    } else if (!isValidEmail(email)) {
        errors.email = 'El formato del email no es válido';
        isValid = false;
    }

    // Validar contraseñas solo en modo crear o si se están cambiando
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirmation').value;

    if (!isEditMode || password) {
        if (!password) {
            errors.password = 'La contraseña es requerida';
            isValid = false;
        } else if (password.length < 8) {
            errors.password = 'La contraseña debe tener al menos 8 caracteres';
            isValid = false;
        }

        if (password !== confirmPassword) {
            errors.password_confirmation = 'Las contraseñas no coinciden';
            isValid = false;
        }
    }

    // Validar roles
    const selectedRoles = document.querySelectorAll('.role-checkbox-hidden:checked');
    console.log('👥 Roles seleccionados:', selectedRoles.length);
    if (selectedRoles.length === 0) {
        errors.roles = 'Debe seleccionar al menos un rol';
        isValid = false;
        console.log('❌ Error: No hay roles seleccionados');
    }

    console.log('📊 Resultado de validación:', isValid ? 'VÁLIDO' : 'INVÁLIDO');
    console.log('📋 Errores encontrados:', errors);

    if (!isValid) {
        showFormErrors(errors);
    }

    return isValid;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
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
    const inputs = document.querySelectorAll('.campo-input');
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

/* ==============================================
   MANEJO DE AVATAR
   ============================================== */
function setupAvatarUpload() {
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', handleAvatarUpload);
    }
}

function handleAvatarUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Validar tipo de archivo
    if (!file.type.startsWith('image/')) {
        showAlert('warning', 'Archivo inválido', 'Solo se permiten archivos de imagen');
        e.target.value = '';
        return;
    }

    // Validar tamaño (2MB)
    if (file.size > 2 * 1024 * 1024) {
        showAlert('warning', 'Archivo muy grande', 'La imagen no debe superar los 2MB');
        e.target.value = '';
        return;
    }

    // Mostrar preview
    const reader = new FileReader();
    reader.onload = function (e) {
        const avatarImage = document.getElementById('avatarImage');
        const avatarPlaceholder = document.getElementById('avatarPlaceholder');
        const removeBtn = document.getElementById('removeAvatarBtn');

        avatarImage.src = e.target.result;
        avatarImage.style.display = 'block';
        avatarPlaceholder.style.display = 'none';
        removeBtn.style.display = 'flex';

        // Agregar clase para botones en fila
        const buttonsContainer = document.getElementById('avatarButtonsContainer');
        if (buttonsContainer) {
            buttonsContainer.classList.add('has-image');
        }
    };
    reader.readAsDataURL(file);
}

function removeAvatar() {
    const avatarInput = document.getElementById('avatarInput');
    avatarInput.value = '';
    resetAvatarPreview();
}

/* ==============================================
   ACCIONES DE USUARIO
   ============================================== */
async function toggleUserStatus(userId) {
    try {
        const baseU = window.USERS_BASE_URL || (new URL('/admin/usuarios', window.location.origin)).href;
        const response = await fetch(`${baseU}/${userId}/cambiar-estado`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', '¡Éxito!', data.message);
            // Actualizar UI sin recargar: badge y estadísticas
            actualizarEstadoUsuarioEnTabla(userId, { is_active: data.is_active });
            try { actualizarEstadisticasUsuarios(); } catch (e) { }
        } else {
            throw new Error(data.message || 'Error al cambiar el estado');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', error.message);
    }
}

async function resetUserPassword(userId) {
    const result = await Swal.fire({
        title: '¿Resetear contraseña?',
        text: 'Se generará una nueva contraseña aleatoria para este usuario',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#e53e3e',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, resetear',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch(`${getAppPrefix()}/admin/usuarios/${userId}/resetear-password`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                title: '¡Contraseña reseteada!',
                html: `<p>Nueva contraseña: <strong>${data.nueva_password}</strong></p><p><small>Asegúrate de compartir esta contraseña con el usuario</small></p>`,
                icon: 'success',
                confirmButtonColor: '#e53e3e',
                confirmButtonText: 'Entendido'
            });
        } else {
            throw new Error(data.message || 'Error al resetear la contraseña');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', error.message);
    }
}

async function deleteUser(userId) {
    // Construir información del usuario desde la fila de la tabla
    const row = document.querySelector(`.user-row[data-user-id="${userId}"]`);
    const nombre = row?.querySelector('.user-name')?.textContent.trim() || 'Sin nombre';
    const telefono = row?.querySelector('.user-phone')?.textContent.trim() || '—';
    const email = row?.querySelector('.email-cell')?.textContent.trim() || '—';
    const roles = Array.from(row?.querySelectorAll('.role-badge') || []).map(b => b.textContent.trim());
    const estado = row?.querySelector('.status-badge')?.textContent.trim() || '—';

    const detalleHtml = `
        <div class="swal-user-card">
            <div class="swal-user-row">
                <span class="swal-label">Nombre:</span>
                <span class="swal-value">${nombre}</span>
            </div>
            <div class="swal-user-row">
                <span class="swal-label">Email:</span>
                <span class="swal-value">${email}</span>
            </div>
            <div class="swal-user-row">
                <span class="swal-label">Teléfono:</span>
                <span class="swal-value">${telefono}</span>
            </div>
            <div class="swal-user-row">
                <span class="swal-label">Roles:</span>
                <span class="swal-value">${roles.length ? roles.join(', ') : 'Sin roles'}</span>
            </div>
            <div class="swal-user-row">
                <span class="swal-label">Estado:</span>
                <span class="swal-value">${estado}</span>
            </div>
        </div>
        <div class="swal-warning-text">Esta acción eliminará permanentemente al usuario y no se puede deshacer.</div>
    `;

    const result = await Swal.fire({
        title: '¿Eliminar usuario?',
        html: detalleHtml,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: false,
        buttonsStyling: false,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'swal2-confirm-custom-rojo',
            cancelButton: 'swal2-cancel-custom',
            popup: 'swal2-popup-usuarios'
        },
        didOpen: () => {
            // Asegurar que no se muestre el botón "No" (deny)
            const denyBtn = document.querySelector('.swal2-deny');
            if (denyBtn) denyBtn.remove();
        }
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch(`${getAppPrefix()}/admin/usuarios/${userId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', '¡Éxito!', data.message);
            eliminarUsuarioDeTabla(userId);
            try { actualizarEstadisticasUsuarios(); } catch (e) { }
        } else {
            throw new Error(data.message || 'Error al eliminar el usuario');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'Error', error.message);
    }
}

async function viewUser(userId) {
    console.log('viewUser llamado con ID:', userId); // Debug

    try {
        console.log('Enviando petición AJAX...'); // Debug
        showLoading('Cargando datos...');

        const response = await fetch(`${getAppPrefix()}/admin/usuarios/${userId}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        console.log('Respuesta recibida:', response); // Debug

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
        }

        const data = await response.json();
        console.log('Datos recibidos:', data); // Debug

        if (data.success && data.user) {
            console.log('Datos válidos, abriendo modal...'); // Debug
            openViewUserModal(data.user);
            hideLoading();
        } else {
            console.error('Respuesta inválida:', data); // Debug
            throw new Error(data.message || 'Error al cargar datos del usuario');
        }
    } catch (error) {
        console.error('Error en viewUser:', error);
        showAlert('error', 'Error', 'No se pudieron cargar los datos del usuario');
        hideLoading();
    }
}

/* ==============================================
   MODAL DE VISTA DE USUARIO
   ============================================== */
function openViewUserModal(user) {
    console.log('openViewUserModal llamado con:', user);
    // Construir chips de roles únicos (evitar duplicados)
    const rolesArray = Array.isArray(user.roles) ? user.roles : [];
    const uniqueRoles = [];
    rolesArray.forEach(r => {
        const label = (r.display_name || r.name || '').trim();
        const key = label.toLowerCase();
        if (label && !uniqueRoles.some(x => x.key === key)) {
            uniqueRoles.push({ key, label, color: r.color || '#8b5cf6' });
        }
    });
    const rolePalette = {
        'dueño': '#f59e0b',
        'dueno': '#f59e0b',
        'administrador': '#8b5cf6',
        'vendedor': '#f97316',
        'almacenero': '#14b8a6',
        'vendedor/almacenero': '#3b82f6',
        'gerente': '#10b981'
    };
    const rolesChipsHtml = uniqueRoles.length
        ? uniqueRoles.map(r => {
            const base = rolePalette[r.label.toLowerCase()] || r.color || '#4338ca';
            const bg = base.startsWith('#') ? `${base}22` : '#eef2ff';
            return `
                <span class="role-chip" style="border-color:${base}; background:${bg}; color:${base};">
                    <iconify-icon icon="solar:shield-check-bold-duotone"></iconify-icon>
                    ${r.label}
                </span>
            `;
        }).join('')
        : '<div class="detail-box">Sin roles asignados</div>';
    const hasAvatar = !!(user.avatar_url
        && /\.(png|jpe?g|gif|webp)$/i.test(user.avatar_url)
        && !/(80x80|placeholder|default)/i.test(user.avatar_url));
    const statusClass = user.is_active ? 'active' : 'inactive';
    const statusIcon = user.is_active ? 'solar:check-circle-bold-duotone' : 'solar:x-circle-bold-duotone';
    const modalHtml = `
        <div id="viewUserModal" class="modal-profesional">
            <div class="modal-profesional-container">
                <div class="header-profesional">
                    <div class="header-content">
                        <div class="header-left">
                            <div class="header-icon icon-normal">
                                <iconify-icon icon="solar:user-bold-duotone"></iconify-icon>
                            </div>
                            <div class="header-text">
                                <h3>Información del Usuario</h3>
                                <p>Detalle de cuenta y estado</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" onclick="closeViewUserModal()">
                            <iconify-icon icon="heroicons:x-mark"></iconify-icon>
                        </button>
                    </div>
                </div>

                <div class="modal-content-profesional">
                    <div class="grid-campos columnas-2">
                        <!-- Izquierda: avatar + estado cercano + roles -->
                        <div class="campo-grupo">
                            <div class="avatar-row">
                                <div class="avatar-preview-modern" id="viewAvatarPreview">
                                    ${hasAvatar ? `<img src="${user.avatar_url}" alt="Avatar" style="width:96px;height:96px;border-radius:50%;object-fit:cover;">` : `
                                        <div class="avatar-placeholder-modern">
                                            <iconify-icon icon="solar:user-bold-duotone"></iconify-icon>
                                        </div>
                                    `}
                                </div>
                            <div class="avatar-side">
                                <div class="field-pill status-pill ${statusClass}">
                                    <iconify-icon icon="${statusIcon}"></iconify-icon>
                                    <span>${user.is_active ? 'Activo' : 'Inactivo'}</span>
                                </div>
                                <div class="roles-chips">
                                    ${rolesChipsHtml}
                                </div>
                            </div>
                        </div>
                        <div class="field-group">
                            <div class="field-label">Nombre</div>
                            <div class="field-pill">
                                <iconify-icon class="field-pill-icon" icon="solar:user-bold-duotone"></iconify-icon>
                                <span>${user.nombres || user.name || '—'}</span>
                            </div>
                        </div>
                        <div class="field-group">
                            <div class="field-label">Apellidos</div>
                            <div class="field-pill">
                                <iconify-icon class="field-pill-icon" icon="solar:user-bold-duotone"></iconify-icon>
                                <span>${user.apellidos || '—'}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Derecha: contacto con diseño pill e iconos -->
                    <div class="campo-grupo">
                        <div class="field-group">
                            <div class="field-label">Email</div>
                            <div class="field-pill">
                                <iconify-icon class="field-pill-icon" icon="solar:letter-bold-duotone"></iconify-icon>
                                <span>${user.email || '—'}</span>
                            </div>
                        </div>
                            <div class="field-group">
                                <div class="field-label">Teléfono</div>
                                <div class="field-pill">
                                    <iconify-icon class="field-pill-icon" icon="solar:phone-bold-duotone"></iconify-icon>
                                    <span>${user.telefono || '—'}</span>
                                </div>
                            </div>
                            <div class="field-group">
                                <div class="field-label">Dirección</div>
                                <div class="field-pill">
                                    <iconify-icon class="field-pill-icon" icon="solar:map-point-bold-duotone"></iconify-icon>
                                    <span>${user.direccion || '—'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

    // Insertar modal en el DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    const modal = document.getElementById('viewUserModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        // Cerrar al hacer click fuera del contenedor
        modal.addEventListener('click', function (e) {
            if (e.target && e.target.id === 'viewUserModal') {
                closeViewUserModal();
            }
        });
    }
}

function closeViewUserModal() {
    const modal = document.getElementById('viewUserModal');
    if (modal) {
        modal.classList.remove('show');

        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = 'auto';
        }, 300);
    }
}

/* FUNCIÓN PARA POBLAR FORMULARIO DE EDICIÓN */
function populateUserFormFromEdit(user) {
    // Datos básicos
    document.getElementById('userId').value = user.id;

    // Llenar nombres y apellidos separados
    const nombresField = document.getElementById('nombres');
    const apellidosField = document.getElementById('apellidos');

    if (nombresField) {
        nombresField.value = user.nombres || '';
    }
    if (apellidosField) {
        apellidosField.value = user.apellidos || '';
    }

    // Otros campos
    document.getElementById('email').value = user.email || '';

    const telefonoField = document.getElementById('telefono');
    if (telefonoField) {
        telefonoField.value = user.telefono || '';
    }

    const direccionField = document.getElementById('direccion');
    if (direccionField) {
        direccionField.value = user.direccion || '';
    }

    const cargoField = document.getElementById('cargo');
    if (cargoField) {
        cargoField.value = user.cargo || '';
    }

    // Estado activo
    const isActiveCheckbox = document.getElementById('is_active');
    if (isActiveCheckbox) {
        isActiveCheckbox.checked = user.is_active;
    }

    // En modo edición, hacer que la contraseña sea opcional
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('password_confirmation');
    const passwordRequired = document.getElementById('passwordRequired');
    const confirmPasswordRequired = document.getElementById('confirmPasswordRequired');

    if (passwordField) {
        passwordField.removeAttribute('required');
        passwordField.placeholder = 'Dejar vacío para mantener contraseña actual';
    }
    if (confirmPasswordField) {
        confirmPasswordField.removeAttribute('required');
        confirmPasswordField.placeholder = 'Confirmar nueva contraseña';
    }
    if (passwordRequired) {
        passwordRequired.style.display = 'none';
    }
    if (confirmPasswordRequired) {
        confirmPasswordRequired.style.display = 'none';
    }

    // Limpiar roles primero
    const roleCheckboxes = document.querySelectorAll('input[name="roles[]"]');
    roleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });

    // Limpiar selecciones visuales previas
    const roleCards = document.querySelectorAll('.role-card-moderno');
    roleCards.forEach(card => {
        card.classList.remove('selected');
    });

    // Marcar roles seleccionados
    console.log('Roles del usuario para marcar:', user.roles); // Debug

    if (user.roles && user.roles.length > 0) {
        user.roles.forEach(roleName => {
            console.log('Intentando marcar rol:', roleName); // Debug

            // Buscar checkbox por nombre de rol
            const checkbox = document.querySelector(`input[name="roles[]"][value="${roleName}"]`);

            if (checkbox) {
                console.log('Marcando checkbox para rol:', roleName); // Debug
                checkbox.checked = true;

                // Actualizar la UI visual de la card
                const roleCard = checkbox.closest('.role-card-moderno');
                if (roleCard && !roleCard.classList.contains('role-disabled')) {
                    roleCard.classList.add('selected');
                }

                // Disparar evento change
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                console.log('No se encontró checkbox para rol:', roleName); // Debug
            }
        });
    }

    console.log('Usuario cargado para edición:', user); // Debug
}

/* ==============================================
   EXPORTACIÓN
   ============================================== */
function exportUsers() {
    const visibleUsers = usersData.filter(user => user.element.style.display !== 'none');
    const source = visibleUsers.length > 0 ? visibleUsers : usersData;

    if (!source || source.length === 0) {
        showAlert('warning', 'Sin datos', 'No hay usuarios para exportar');
        return;
    }

    const rows = source.map(user => [
        user.name || '',
        user.email || '',
        user.telefono || '',
        user.direccion || '',
        (user.roles && user.roles.length ? user.roles.join(', ') : ''),
        user.isActive ? 'Activo' : 'Inactivo'
    ]);

    (async () => {
        if (typeof XLSX === 'undefined') {
            const ok = await ensureXLSX();
            if (!ok || typeof XLSX === 'undefined') {
                showAlert('error', 'Error', 'No se pudo cargar la librería de Excel');
                return;
            }
        }
        const aoa = [];
        aoa.push(['Reporte de Usuarios']);
        aoa.push([]);
        aoa.push(['Usuario', 'Email', 'Teléfono', 'Dirección', 'Roles', 'Estado']);
        rows.forEach(r => aoa.push(r));
        const ws = XLSX.utils.aoa_to_sheet(aoa);
        ws['!merges'] = [XLSX.utils.decode_range('A1:F1')];
        ws['!cols'] = [{ wch: 24 }, { wch: 30 }, { wch: 14 }, { wch: 30 }, { wch: 28 }, { wch: 12 }];
        try {
            ws['A1'].s = { font: { bold: true, sz: 18 }, alignment: { horizontal: 'center' } };
            ['A3', 'B3', 'C3', 'D3', 'E3', 'F3'].forEach(addr => { if (ws[addr]) ws[addr].s = { font: { bold: true } }; });
        } catch (_) { }
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Usuarios');
        const filename = 'reporte_usuarios_' + new Date().toISOString().slice(0, 10) + '.xlsx';
        XLSX.writeFile(wb, filename);
        showAlert('success', '¡Exportado!', 'El archivo Excel se ha descargado');
    })();
}

async function ensureXLSX() {
    function load(url) {
        return new Promise((resolve) => {
            const s = document.createElement('script');
            s.src = url; s.async = true; s.onload = () => resolve(true); s.onerror = () => resolve(false);
            document.head.appendChild(s);
        });
    }
    const primary = await load('https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx-js-style.min.js');
    if (primary && typeof XLSX !== 'undefined') return true;
    const fallback = await load('https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js');
    return fallback && typeof XLSX !== 'undefined';
}

/* ==============================================
   UTILIDADES
   ============================================== */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('iconify-icon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('icon', 'heroicons:eye-slash');
    } else {
        input.type = 'password';
        icon.setAttribute('icon', 'heroicons:eye');
    }
}

function showFormLoading(show) {
    const submitBtn = document.querySelector('.btn-guardar');
    const submitText = document.getElementById('submitButtonText');

    if (show) {
        submitBtn.disabled = true;
        submitText.textContent = 'Procesando...';
        submitBtn.style.opacity = '0.7';
    } else {
        submitBtn.disabled = false;
        submitText.textContent = isEditMode ? 'Actualizar Usuario' : 'Crear Usuario';
        submitBtn.style.opacity = '1';
    }
}

function showAlert(type, title, message) {
    const config = { title, text: message };

    switch (type) {
        case 'success':
            // Éxito: sin botones y cierre automático
            config.icon = 'success';
            config.showConfirmButton = false;
            config.showCancelButton = false;
            config.timer = 1500;
            config.timerProgressBar = true;
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

function setupFilters() {
    // Configuración adicional de filtros si es necesaria
    console.log('Filtros configurados correctamente');
}

// ==============================================
// FUNCIONES PARA MODAL PROFESIONAL DE USUARIOS
// ==============================================

// Medidor de fuerza de contraseña
function checkPasswordStrength(password) {
    const strengthBar = document.getElementById('password-strength');
    const strengthText = document.getElementById('password-strength-text');

    if (!strengthBar || !strengthText) return;

    // Si la contraseña está vacía, resetear completamente
    if (!password || password.length === 0) {
        strengthBar.className = 'strength-fill';
        strengthBar.style.width = '0%';
        strengthText.textContent = 'Muy débil';
        return;
    }

    let strength = 0;
    let strengthClass = '';
    let strengthLabel = '';

    // Verificar longitud (mínimo 6)
    if (password.length >= 6) strength += 1;
    if (password.length >= 10) strength += 1;

    // Verificar mayúsculas
    if (/[A-Z]/.test(password)) strength += 1;

    // Verificar minúsculas
    if (/[a-z]/.test(password)) strength += 1;

    // Verificar números
    if (/[0-9]/.test(password)) strength += 1;

    // Verificar símbolos
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;

    // Determinar nivel de fuerza
    if (strength <= 2) {
        strengthClass = 'weak';
        strengthLabel = 'Muy débil';
    } else if (strength <= 3) {
        strengthClass = 'medium';
        strengthLabel = 'Débil';
    } else if (strength <= 4) {
        strengthClass = 'strong';
        strengthLabel = 'Fuerte';
    } else {
        strengthClass = 'very-strong';
        strengthLabel = 'Muy fuerte';
    }

    // Aplicar estilos
    strengthBar.className = `strength-fill ${strengthClass}`;
    strengthText.textContent = strengthLabel;
}

// Verificar coincidencia de contraseñas
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirmation').value;
    const indicator = document.getElementById('password-match-indicator');

    if (!indicator) return;

    // Solo mostrar si ambos campos tienen contenido y coinciden
    if (confirmPassword && password && password === confirmPassword) {
        indicator.style.display = 'block';
    } else {
        indicator.style.display = 'none';
    }
}

// Actualizar barra de progreso del modal
function updateProgressBar(step) {
    const progressBar = document.getElementById('progressBar');
    if (!progressBar) return;

    const totalSteps = 4; // Avatar, Personal, Credenciales, Roles
    const percentage = (step / totalSteps) * 100;
    progressBar.style.width = `${percentage}%`;
}

// Previsualizar imagen avatar
function previewAvatar(input) {
    const file = input.files[0];
    const avatarImage = document.getElementById('avatarImage');
    const avatarPlaceholder = document.getElementById('avatarPlaceholder');
    const removeBtn = document.getElementById('removeAvatarBtn');

    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            avatarImage.src = e.target.result;
            avatarImage.style.display = 'block';
            avatarPlaceholder.style.display = 'none';
            removeBtn.style.display = 'flex';

            // Agregar clase para botones en fila
            const buttonsContainer = document.getElementById('avatarButtonsContainer');
            if (buttonsContainer) {
                buttonsContainer.classList.add('has-image');
            }
        }
        reader.readAsDataURL(file);
    }
}

// Quitar avatar
function removeAvatar() {
    const avatarImage = document.getElementById('avatarImage');
    const avatarPlaceholder = document.getElementById('avatarPlaceholder');
    const removeBtn = document.getElementById('removeAvatarBtn');
    const avatarInput = document.getElementById('avatarInput');

    avatarImage.src = '';
    avatarImage.style.display = 'none';
    avatarPlaceholder.style.display = 'flex';
    removeBtn.style.display = 'none';
    avatarInput.value = '';

    // Remover clase de imagen cuando no hay imagen
    const buttonsContainer = document.getElementById('avatarButtonsContainer');
    if (buttonsContainer) {
        buttonsContainer.classList.remove('has-image');
    }
}

// Event listeners para el modal
document.addEventListener('DOMContentLoaded', function () {
    // Avatar input
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            previewAvatar(this);
        });
    }

    // Password strength
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            checkPasswordStrength(this.value);
            checkPasswordMatch(); // También verificar coincidencia
            updateProgressBar(3);
        });

        // También verificar en keyup para detectar cuando se borra todo
        passwordInput.addEventListener('keyup', function () {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });

        // Verificar cuando pierde el foco
        passwordInput.addEventListener('blur', function () {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
    }

    // Password confirmation
    const confirmPasswordInput = document.getElementById('password_confirmation');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        confirmPasswordInput.addEventListener('keyup', checkPasswordMatch);
        confirmPasswordInput.addEventListener('blur', checkPasswordMatch);
    }

    // Form inputs para progreso
    const personalInputs = ['nombres', 'apellidos'];
    personalInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', function () {
                updateProgressBar(2);
            });
        }
    });

    // Roles selection
    const roleCheckboxes = document.querySelectorAll('.role-checkbox-hidden');
    roleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateProgressBar(4);
        });
    });
});

// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggleIcon = document.getElementById(inputId + '-toggle-icon');

    if (input.type === 'password') {
        input.type = 'text';
        if (toggleIcon) {
            toggleIcon.setAttribute('icon', 'solar:eye-closed-bold-duotone');
        }
    } else {
        input.type = 'password';
        if (toggleIcon) {
            toggleIcon.setAttribute('icon', 'solar:eye-bold-duotone');
        }
    }
}

// Función duplicada eliminada - se mantiene la definición principal en línea 183

// Función duplicada eliminada - se mantiene la definición principal en línea 274

// Función para alternar la selección de roles
function toggleRoleSelection(roleId) {
    const card = document.querySelector(`.role-card-moderno[onclick="toggleRoleSelection(${roleId})"]`);
    const checkbox = document.getElementById(`role-${roleId}`);

    // No permitir seleccionar roles deshabilitados
    if (card && card.classList.contains('role-disabled')) {
        return;
    }

    if (checkbox && card && !checkbox.disabled) {
        checkbox.checked = !checkbox.checked;

        if (checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    }
}

// Función para enviar el formulario
function submitUserForm() {
    console.log('🎯 submitUserForm() ejecutado');
    const form = document.getElementById('userForm');
    console.log('📝 Formulario encontrado en submitUserForm:', form ? 'SÍ' : 'NO');

    if (form) {
        console.log('🚀 Disparando evento submit...');
        const event = new Event('submit', {
            bubbles: true,
            cancelable: true
        });
        form.dispatchEvent(event);
    } else {
        console.log('❌ No se pudo encontrar el formulario en submitUserForm');
    }
}

/* ==============================================
   FUNCIONES AUXILIARES PARA ACTUALIZACIÓN DINÁMICA
   ============================================== */

/**
 * Agregar nuevo usuario a la tabla dinámicamente
 */
function agregarUsuarioATabla(usuario) {
    const tbody = document.querySelector('.users-table tbody');
    if (!tbody) return;

    // Ocultar mensaje de "no hay usuarios" si existe
    const emptyRow = tbody.querySelector('.empty-state');
    if (emptyRow) {
        emptyRow.parentElement.style.display = 'none';
    }

    // Crear nueva fila
    const newRow = document.createElement('tr');
    newRow.className = 'user-row';
    newRow.setAttribute('data-user-id', usuario.id);
    newRow.setAttribute('data-telefono', usuario.telefono || '');
    newRow.setAttribute('data-direccion', usuario.direccion || '');

    // Generar HTML de la fila
    newRow.innerHTML = `
        <td>
            <div class="user-cell">
                <div class="user-avatar" style="border: 2px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    ${usuario.avatar ?
            `<img src="${usuario.avatar_url}" alt="Avatar de ${usuario.name}">` :
            `<div class="avatar-placeholder" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">${usuario.initials || (usuario.name ? usuario.name.charAt(0).toUpperCase() : 'U')}</div>`
        }
                </div>
                <div class="user-info">
                    <div class="user-name" style="font-weight: 700; color: #1e293b; font-size: 0.95rem;">${usuario.name}</div>
                    <div class="user-phone" style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
                        <iconify-icon icon="solar:phone-bold" style="font-size: 0.8rem; margin-right: 2px;"></iconify-icon>
                        ${usuario.telefono || 'Sin teléfono'}
                    </div>
                </div>
            </div>
        </td>
        <td>
            <div class="contact-cell">
                <div class="email-cell" style="font-weight: 600; color: #475569; font-size: 0.875rem; display: flex; align-items: center; gap: 4px;">
                    <iconify-icon icon="solar:letter-bold" style="color: #64748b; font-size: 0.9rem;"></iconify-icon>
                    ${usuario.email}
                </div>
                <div class="address-cell" style="font-size: 0.75rem; color: #64748b; font-weight: 500; margin-top: 2px; display: flex; align-items: center; gap: 4px;">
                    <iconify-icon icon="solar:map-point-bold" style="color: #94a3b8; font-size: 0.85rem;"></iconify-icon>
                    ${usuario.direccion || '-'}
                </div>
            </div>
        </td>
        <td>
            <div class="roles-container">
                ${usuario.roles && usuario.roles.length > 0 ?
            usuario.roles.map(role => `<span class="role-badge" style="background-color: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 0.25rem 0.6rem; border-radius: 8px;">${role.display_name || role.name}</span>`).join('') :
            '<span class="no-role-badge">Sin roles</span>'
        }
            </div>
        </td>
        <td style="text-align: center;">
            <span class="status-badge ${usuario.is_active ? 'status-active' : 'status-inactive'}" style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">
                <iconify-icon icon="${usuario.is_active ? 'solar:check-circle-bold' : 'solar:close-circle-bold'}"></iconify-icon>
                ${usuario.is_active ? 'Activo' : 'Inactivo'}
            </span>
        </td>
        <td>
            <div class="last-login-cell" style="font-size: 0.8rem; font-weight: 600; color: #64748b;">
                <span style="color: #1e293b;">Hace un momento</span>
            </div>
        </td>
        <td>
            <div class="action-buttons" style="justify-content: center;">
                <button type="button" class="action-btn btn-view" onclick="viewUser(${usuario.id})" title="Ver Detalles">
                    <iconify-icon icon="heroicons:eye"></iconify-icon>
                </button>
                <button type="button" class="action-btn btn-edit" onclick="editUser(${usuario.id})" title="Editar Usuario">
                    <iconify-icon icon="heroicons:pencil"></iconify-icon>
                </button>
                <label class="toggle-switch user-toggle" title="Activar/Desactivar">
                    <input type="checkbox" class="user-status-toggle" data-user-id="${usuario.id}" ${usuario.is_active ? 'checked' : ''}>
                    <span class="toggle-slider"></span>
                </label>
                <button type="button" class="action-btn btn-delete" onclick="deleteUser(${usuario.id})" title="Eliminar Usuario">
                    <iconify-icon icon="heroicons:trash"></iconify-icon>
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
    }, 2000);

    // Actualizar datos locales
    loadUsersData();
}

/**
 * Actualizar usuario existente en la tabla
 */
function actualizarUsuarioEnTabla(userId, usuario) {
    console.log('🔄 Actualizando usuario en tabla:', userId, usuario);

    // Buscar la fila por data-user-id
    let row = document.querySelector(`tr[data-user-id="${userId}"]`);

    if (!row) {
        console.warn('❌ No se encontró la fila del usuario para actualizar:', userId);
        return;
    }

    console.log('✅ Fila encontrada, actualizando contenido...');

    // Actualizar atributos de datos
    row.setAttribute('data-telefono', usuario.telefono || '');
    row.setAttribute('data-direccion', usuario.direccion || '');

    // Actualizar celdas
    const cells = row.cells;

    // 1. Usuario
    const userCell = cells[0];
    if (userCell) {
        userCell.innerHTML = `
            <div class="user-cell">
                <div class="user-avatar" style="border: 2px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    ${usuario.avatar ?
                `<img src="${usuario.avatar_url}" alt="Avatar de ${usuario.name}">` :
                `<div class="avatar-placeholder" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">${usuario.initials || (usuario.name ? usuario.name.charAt(0).toUpperCase() : 'U')}</div>`
            }
                </div>
                <div class="user-info">
                    <div class="user-name" style="font-weight: 700; color: #1e293b; font-size: 0.95rem;">${usuario.name}</div>
                    <div class="user-phone" style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
                        <iconify-icon icon="solar:phone-bold" style="font-size: 0.8rem; margin-right: 2px;"></iconify-icon>
                        ${usuario.telefono || 'Sin teléfono'}
                    </div>
                </div>
            </div>
        `;
    }

    // 2. Contacto
    const contactCell = cells[1];
    if (contactCell) {
        contactCell.innerHTML = `
            <div class="contact-cell">
                <div class="email-cell" style="font-weight: 600; color: #475569; font-size: 0.875rem; display: flex; align-items: center; gap: 4px;">
                    <iconify-icon icon="solar:letter-bold" style="color: #64748b; font-size: 0.9rem;"></iconify-icon>
                    ${usuario.email}
                </div>
                <div class="address-cell" style="font-size: 0.75rem; color: #64748b; font-weight: 500; margin-top: 2px; display: flex; align-items: center; gap: 4px;">
                    <iconify-icon icon="solar:map-point-bold" style="color: #94a3b8; font-size: 0.85rem;"></iconify-icon>
                    ${usuario.direccion || '-'}
                </div>
            </div>
        `;
    }

    // 3. Roles
    const rolesCell = cells[2];
    if (rolesCell) {
        rolesCell.innerHTML = `
            <div class="roles-container">
                ${usuario.roles && usuario.roles.length > 0 ?
                usuario.roles.map(role => `<span class="role-badge" style="background-color: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 0.25rem 0.6rem; border-radius: 8px;">${role.display_name || role.name}</span>`).join('') :
                '<span class="no-role-badge">Sin roles</span>'
            }
            </div>
        `;
    }

    // 4. Estado
    const statusCell = cells[3];
    if (statusCell) {
        statusCell.innerHTML = `
            <span class="status-badge ${usuario.is_active ? 'status-active' : 'status-inactive'}" style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">
                <iconify-icon icon="${usuario.is_active ? 'solar:check-circle-bold' : 'solar:close-circle-bold'}"></iconify-icon>
                ${usuario.is_active ? 'Activo' : 'Inactivo'}
            </span>
        `;
    }

    // Actualizar toggle de estado en la última celda
    const toggle = row.querySelector('.user-status-toggle');
    if (toggle) {
        toggle.checked = usuario.is_active;
    }

    // Animar la fila actualizada
    row.style.transition = 'background-color 0.3s ease';
    row.style.backgroundColor = '#dbeafe';
    setTimeout(() => {
        row.style.backgroundColor = '';
    }, 2000);

    console.log('✅ Usuario actualizado dinámicamente en la tabla');

    // Actualizar datos locales
    loadUsersData();
}

/**
 * Actualizar estado de usuario en la tabla
 */
function actualizarEstadoUsuarioEnTabla(userId, usuario) {
    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (!row) return;

    const isActive = usuario.is_active;

    // Actualizar badge de estado
    const statusCell = row.cells[3];
    if (statusCell) {
        statusCell.innerHTML = `
            <span class="status-badge ${isActive ? 'status-active' : 'status-inactive'}" style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">
                <iconify-icon icon="${isActive ? 'solar:check-circle-bold' : 'solar:close-circle-bold'}"></iconify-icon>
                ${isActive ? 'Activo' : 'Inactivo'}
            </span>
        `;
    }

    // Actualizar toggle
    const toggle = row.querySelector('.user-status-toggle');
    if (toggle) {
        toggle.checked = isActive;
    }

    // Animar la fila
    row.style.backgroundColor = '#fef3c7';
    setTimeout(() => {
        row.style.backgroundColor = '';
    }, 2000);

    // Actualizar datos locales
    loadUsersData();
}

/**
 * Eliminar usuario de la tabla dinámicamente
 */
function eliminarUsuarioDeTabla(userId) {
    const row = document.querySelector(`tr[data-user-id="${userId}"]`);
    if (!row) return;

    // Animar eliminación
    row.style.backgroundColor = '#fecaca';
    row.style.transform = 'scale(0.95)';
    row.style.opacity = '0.5';

    setTimeout(() => {
        row.remove();

        // Verificar si quedan usuarios
        const tbody = document.querySelector('.users-table tbody');
        const remainingRows = tbody.querySelectorAll('.user-row');

        if (remainingRows.length === 0) {
            // Mostrar mensaje de tabla vacía
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="6" class="empty-state">
                    <div class="empty-content">
                        <iconify-icon icon="heroicons:users" class="empty-icon"></iconify-icon>
                        <h3>No hay usuarios registrados</h3>
                        <p>Comienza agregando el primer usuario al sistema</p>
                        <button type="button" class="btn-action btn-primary" onclick="openCreateUserModal()">
                            <iconify-icon icon="heroicons:plus"></iconify-icon>
                            Agregar Usuario
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(emptyRow);
        }

        // Actualizar datos locales
        loadUsersData();
    }, 500);
}

/**
 * Actualizar estadísticas de usuarios dinámicamente
 */
function actualizarEstadisticasUsuarios() {
    // Contar usuarios actuales en la tabla
    const rows = document.querySelectorAll('.users-table tbody .user-row');
    const totalUsuarios = rows.length;

    let activosCount = 0;
    let inactivosCount = 0;

    rows.forEach(row => {
        // Contar activos e inactivos
        const statusActive = row.querySelector('.status-active');
        if (statusActive) {
            activosCount++;
        } else {
            inactivosCount++;
        }
    });

    // Actualizar valores en las nuevas tarjetas de métricas
    const totalCard = document.querySelector('.reportes-metric-card.gold .reportes-metric-value-medium');
    const activosCard = document.querySelector('.reportes-metric-card.teal .reportes-metric-value-medium');
    const inactivosCard = document.querySelector('.reportes-metric-card.red .reportes-metric-value-medium');

    if (totalCard) totalCard.textContent = totalUsuarios;
    if (activosCard) activosCard.textContent = activosCount;
    if (inactivosCard) inactivosCard.textContent = inactivosCount;
}

/**
 * Recargar tabla completa de usuarios con skeleton loading (AJAX)
 */
/**
 * Renderizar tabla completa de usuarios
 */
function renderUsuariosTabla(usuarios) {
    const tbody = document.querySelector('.users-table tbody');
    if (!tbody) return;

    // Limpiar tabla
    tbody.innerHTML = '';

    if (!usuarios || usuarios.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="empty-state">
                    <div class="empty-content">
                        <iconify-icon icon="heroicons:users" class="empty-icon"></iconify-icon>
                        <h3>No hay usuarios registrados</h3>
                        <p>Comienza agregando el primer usuario al sistema</p>
                        <button type="button" class="btn-action btn-primary" onclick="openCreateUserModal()">
                            <iconify-icon icon="heroicons:plus"></iconify-icon>
                            Agregar Usuario
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    // Renderizar cada usuario
    usuarios.forEach(usuario => {
        const row = document.createElement('tr');
        row.className = 'user-row';
        row.setAttribute('data-user-id', usuario.id);

        row.innerHTML = `
            <td>
                <div class="user-cell">
                    <div class="user-avatar">
                        ${usuario.avatar ?
                `<img src="${usuario.avatar_url}" alt="Avatar de ${usuario.name}">` :
                `<div class="avatar-placeholder">${usuario.initials || (usuario.name ? usuario.name.charAt(0).toUpperCase() : 'U')}</div>`
            }
                    </div>
                    <div class="user-info">
                        <div class="user-name">${usuario.name}</div>
                        <div class="user-meta">${usuario.cargo || 'Sin cargo'}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="email-cell">${usuario.email}</div>
            </td>
            <td>
                <div class="roles-cell">
                    ${usuario.roles && usuario.roles.length > 0 ?
                usuario.roles.map(role => `<span class="role-badge">${role.name}</span>`).join('') :
                '<span class="role-badge role-badge-empty">Sin roles</span>'
            }
                </div>
            </td>
            <td>
                <div class="status-cell">
                    ${usuario.estado === 'activo' || usuario.is_active ?
                '<span class="status-badge status-active">Activo</span>' :
                '<span class="status-badge status-inactive">Inactivo</span>'
            }
                </div>
            </td>
            <td>
                <div class="last-access-cell">
                    ${usuario.last_login_at ?
                new Date(usuario.last_login_at).toLocaleDateString('es-PE') :
                'Nunca'
            }
                </div>
            </td>
            <td>
                <div class="actions-cell">
                    <button type="button" class="action-btn view-btn" onclick="viewUser(${usuario.id})" title="Ver detalles">
                        <iconify-icon icon="heroicons:eye"></iconify-icon>
                    </button>
                    <button type="button" class="action-btn edit-btn" onclick="editUser(${usuario.id})" title="Editar">
                        <iconify-icon icon="heroicons:pencil-square"></iconify-icon>
                    </button>
                    <label class="toggle-switch" title="Activar/Desactivar">
                        <input type="checkbox" class="user-status-toggle" data-user-id="${usuario.id}" ${usuario.estado === 'activo' || usuario.is_active ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                    <button type="button" class="action-btn delete-btn" onclick="deleteUser(${usuario.id})" title="Eliminar">
                        <iconify-icon icon="heroicons:trash"></iconify-icon>
                    </button>
                </div>
            </td>
        `;

        tbody.appendChild(row);
    });

    // Actualizar datos locales
    loadUsersData();
}

async function recargarTablaUsuarios() {
    console.log('🔄 Recargando tabla de usuarios...');

    try {
        // Mostrar skeleton loading
        const skeleton = document.getElementById('usuariosSkeleton');
        const tbody = document.querySelector('#usersTable tbody') || document.querySelector('.users-table tbody');

        if (skeleton && tbody) {
            skeleton.style.display = 'block';
            tbody.style.display = 'none';
        }

        // Hacer petición AJAX para obtener datos actualizados
        const apiUrl = window.USERS_API_URL || (new URL('/admin/usuarios/api', window.location.origin)).href;
        const response = await fetch(apiUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) throw new Error('Error al recargar los usuarios');

        const data = await response.json();
        if (data.success && Array.isArray(data.data)) {
            // Renderizar tabla con los nuevos datos (clave correcta: data)
            renderUsuariosTabla(data.data);

            // Ocultar skeleton y mostrar tabla
            if (skeleton && tbody) {
                skeleton.style.display = 'none';
                tbody.style.display = '';
            }

            console.log('✅ Tabla de usuarios recargada exitosamente');
        }
    } catch (error) {
        console.error('Error al recargar tabla:', error);

        // Ocultar skeleton en caso de error
        const skeleton = document.getElementById('usuariosSkeleton');
        const tbody = document.querySelector('#usersTable tbody') || document.querySelector('.users-table tbody');
        if (skeleton && tbody) {
            skeleton.style.display = 'none';
            tbody.style.display = '';
        }

        // Mostrar mensaje de error
        showAlert('error', 'Error', 'No se pudo recargar la tabla de usuarios');
    }
}

// Obtener prefijo de la app (subcarpeta) desde el path actual o variable inyectada
function getAppPrefix() {
    if (window.APP_BASE_PATH) return window.APP_BASE_PATH;
    try {
        const p = window.location.pathname || '';
        const idx = p.indexOf('/admin');
        return idx > 0 ? p.substring(0, idx) : '';
    } catch (_) {
        return '';
    }
}