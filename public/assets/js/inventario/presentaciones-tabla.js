// public/assets/js/inventario/presentaciones-tabla.js
document.addEventListener('DOMContentLoaded', function() {
    const presentacionesTableBody = document.getElementById('presentaciones-table-body');
    const btnAgregarPresentacion = document.getElementById('btn-agregar-presentacion');
    const modalPresentacion = document.getElementById('modal-presentacion');
    const formPresentacion = document.getElementById('form-presentacion');
    const btnCerrarModal = document.getElementById('btn-cerrar-modal-presentacion');
    const btnCancelarModal = document.getElementById('btn-cancelar-modal-presentacion');
    const productoIdHidden = document.getElementById('producto_id_hidden');
    
    let presentacionCounter = 0;
    let editingPresentacionId = null;

    // Función para cargar presentaciones existentes
    async function loadPresentaciones() {
        const productoId = productoIdHidden?.value;
        
        if (!productoId) {
            // Si es un nuevo producto, agregar una presentación por defecto (Unidad)
            agregarPresentacionATabla({
                id: `new_${presentacionCounter++}`,
                nombre_presentacion: 'Unidad',
                unidades_por_presentacion: 1
            });
            return;
        }

        try {
            const response = await fetch(`/inventario/producto/presentaciones/api/${productoId}`);
            const result = await response.json();

            if (result.success && result.data.length > 0) {
                result.data.forEach(presentacion => {
                    agregarPresentacionATabla(presentacion);
                });
            } else {
                // Si no hay presentaciones, agregar una por defecto (Unidad)
                agregarPresentacionATabla({
                    id: `new_${presentacionCounter++}`,
                    nombre_presentacion: 'Unidad',
                    unidades_por_presentacion: 1
                });
            }
        } catch (error) {
            console.error('Error loading presentaciones:', error);
            Swal.fire('Error', 'No se pudieron cargar las presentaciones existentes.', 'error');
        }
    }

    // Función para agregar presentación a la tabla
    function agregarPresentacionATabla(presentacion) {
        const id = presentacion.id || `new_${presentacionCounter++}`;
        
        const row = document.createElement('tr');
        row.setAttribute('data-presentacion-id', id);
        row.innerHTML = `
            <td class="px-4 py-3 text-sm text-gray-900">${presentacion.nombre_presentacion}</td>
            <td class="px-4 py-3 text-sm text-gray-600 text-center">${presentacion.unidades_por_presentacion}</td>
            <td class="px-4 py-3 text-sm text-center">
                <button type="button" class="btn-editar-presentacion text-blue-600 hover:text-blue-800 mr-2" data-id="${id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn-eliminar-presentacion text-red-600 hover:text-red-800" data-id="${id}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        
        // Guardar datos en un atributo data
        row.dataset.presentacionData = JSON.stringify({
            id: id,
            nombre_presentacion: presentacion.nombre_presentacion,
            unidades_por_presentacion: presentacion.unidades_por_presentacion
        });
        
        presentacionesTableBody.appendChild(row);
    }

    // Abrir modal para agregar presentación
    if (btnAgregarPresentacion) {
        btnAgregarPresentacion.addEventListener('click', () => {
            editingPresentacionId = null;
            formPresentacion.reset();
            document.getElementById('modal-presentacion-title').textContent = 'Agregar Presentación';
            modalPresentacion.classList.remove('hidden');
            modalPresentacion.classList.add('flex');
        });
    }

    // Cerrar modal
    function cerrarModal() {
        modalPresentacion.classList.add('hidden');
        modalPresentacion.classList.remove('flex');
        formPresentacion.reset();
        editingPresentacionId = null;
    }

    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', cerrarModal);
    }

    if (btnCancelarModal) {
        btnCancelarModal.addEventListener('click', cerrarModal);
    }

    // Guardar presentación desde modal
    if (formPresentacion) {
        formPresentacion.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const nombre = document.getElementById('nombre_presentacion_modal').value.trim();
            const unidades = parseInt(document.getElementById('unidades_presentacion_modal').value);
            
            if (!nombre || unidades < 1) {
                Swal.fire('Error', 'Por favor completa todos los campos correctamente.', 'error');
                return;
            }
            
            if (editingPresentacionId) {
                // Editar presentación existente
                const row = document.querySelector(`tr[data-presentacion-id="${editingPresentacionId}"]`);
                if (row) {
                    row.querySelector('td:nth-child(1)').textContent = nombre;
                    row.querySelector('td:nth-child(2)').textContent = unidades;
                    
                    row.dataset.presentacionData = JSON.stringify({
                        id: editingPresentacionId,
                        nombre_presentacion: nombre,
                        unidades_por_presentacion: unidades
                    });
                }
            } else {
                // Agregar nueva presentación
                agregarPresentacionATabla({
                    id: `new_${presentacionCounter++}`,
                    nombre_presentacion: nombre,
                    unidades_por_presentacion: unidades
                });
            }
            
            cerrarModal();
            Swal.fire('Éxito', 'Presentación guardada correctamente.', 'success');
        });
    }

    // Editar presentación
    if (presentacionesTableBody) {
        presentacionesTableBody.addEventListener('click', (e) => {
            const btnEditar = e.target.closest('.btn-editar-presentacion');
            const btnEliminar = e.target.closest('.btn-eliminar-presentacion');
            
            if (btnEditar) {
                const row = btnEditar.closest('tr');
                const data = JSON.parse(row.dataset.presentacionData);
                
                editingPresentacionId = data.id;
                document.getElementById('nombre_presentacion_modal').value = data.nombre_presentacion;
                document.getElementById('unidades_presentacion_modal').value = data.unidades_por_presentacion;
                document.getElementById('modal-presentacion-title').textContent = 'Editar Presentación';
                
                modalPresentacion.classList.remove('hidden');
                modalPresentacion.classList.add('flex');
            }
            
            if (btnEliminar) {
                const rows = presentacionesTableBody.querySelectorAll('tr');
                
                if (rows.length === 1) {
                    Swal.fire('Advertencia', 'Debe haber al menos una presentación para el producto.', 'warning');
                    return;
                }
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción eliminará la presentación.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        btnEliminar.closest('tr').remove();
                        Swal.fire('Eliminado', 'La presentación ha sido eliminada.', 'success');
                    }
                });
            }
        });
    }

    // Función para obtener presentaciones antes de enviar el formulario
    window.getPresentacionesData = function() {
        const presentaciones = {};
        const rows = presentacionesTableBody.querySelectorAll('tr');
        
        rows.forEach((row, index) => {
            const data = JSON.parse(row.dataset.presentacionData);
            presentaciones[data.id] = {
                id: data.id.toString().startsWith('new_') ? '' : data.id,
                nombre_presentacion: data.nombre_presentacion,
                unidades_por_presentacion: data.unidades_por_presentacion
            };
        });
        
        return presentaciones;
    };

    // Cargar presentaciones al iniciar
    if (presentacionesTableBody && btnAgregarPresentacion) {
        loadPresentaciones();
    }
});
