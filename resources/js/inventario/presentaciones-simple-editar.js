// public/assets/js/inventario/presentaciones-simple-editar.js
document.addEventListener('DOMContentLoaded', function() {
    const presentacionesContainerEdit = document.getElementById('presentaciones-container-edit');
    const addPresentacionBtnEdit = document.getElementById('add-presentacion-btn-edit');
    const productoIdHidden = document.getElementById('producto_id_hidden');

    let presentacionCounterEdit = 0;

    // Función para cargar presentaciones existentes (EDITAR)
    async function loadPresentacionesEdit() {
        const productoId = productoIdHidden?.value;
        
        if (!productoId) {
            return;
        }

        // Limpiar contenedor
        presentacionesContainerEdit.innerHTML = '';

        try {
            const response = await fetch(`/inventario/producto/presentaciones/api/${productoId}`);
            const result = await response.json();

            if (result.success && result.data.length > 0) {
                result.data.forEach(presentacion => {
                    addPresentacionRowEdit(presentacion);
                });
            } else {
                // Si no hay presentaciones, agregar una por defecto (Unidad)
                if (presentacionesContainerEdit.children.length === 0) {
                    addPresentacionRowEdit({
                        nombre_presentacion: 'Unidad',
                        unidades_por_presentacion: 1,
                        precio_compra_presentacion: '',
                        precio_venta_presentacion: ''
                    });
                }
            }
        } catch (error) {
            console.error('Error loading presentaciones (edit):', error);
            Swal.fire('Error', 'No se pudieron cargar las presentaciones existentes.', 'error');
        }
    }

    // Función para agregar una fila de presentación (EDITAR)
    function addPresentacionRowEdit(data = {}) {
        const id = data.id || `new_${presentacionCounterEdit++}`;

        const row = document.createElement('div');
        row.classList.add('presentacion-row', 'grid', 'grid-cols-12', 'gap-4', 'mb-4', 'p-4', 'border', 'rounded-lg', 'bg-gray-50');
        row.setAttribute('data-id', id);

        row.innerHTML = `
            <input type="hidden" name="presentaciones[${id}][id]" value="${data.id || ''}">
            <div class="col-span-12 md:col-span-3">
                <label for="nombre_presentacion_edit_${id}" class="block text-sm font-medium text-gray-700">Nombre Presentación</label>
                <input type="text" name="presentaciones[${id}][nombre_presentacion]" id="nombre_presentacion_edit_${id}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    value="${data.nombre_presentacion || ''}" required placeholder="Ej: Unidad, Blíster, Caja">
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="unidades_por_presentacion_edit_${id}" class="block text-sm font-medium text-gray-700">Unidades x Presentación</label>
                <input type="number" name="presentaciones[${id}][unidades_por_presentacion]" id="unidades_por_presentacion_edit_${id}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    value="${data.unidades_por_presentacion || 1}" min="1" required placeholder="Ej: 1, 10, 20">
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="precio_compra_presentacion_edit_${id}" class="block text-sm font-medium text-gray-700">Precio Compra</label>
                <input type="number" name="presentaciones[${id}][precio_compra_presentacion]" id="precio_compra_presentacion_edit_${id}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    value="${data.precio_compra_presentacion || ''}" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="precio_venta_presentacion_edit_${id}" class="block text-sm font-medium text-gray-700">Precio Venta</label>
                <input type="number" name="presentaciones[${id}][precio_venta_presentacion]" id="precio_venta_presentacion_edit_${id}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    value="${data.precio_venta_presentacion || ''}" step="0.01" min="0" required placeholder="0.00">
            </div>
            <div class="col-span-12 md:col-span-2 flex items-end">
                <button type="button" class="remove-presentacion-btn-edit inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all">
                    <i class="fas fa-trash-alt mr-2"></i> Eliminar
                </button>
            </div>
        `;
        presentacionesContainerEdit.appendChild(row);

        // Deshabilitar el botón de eliminar si es la única fila
        updateRemoveButtonsEdit();
    }

    function updateRemoveButtonsEdit() {
        const removeButtons = presentacionesContainerEdit.querySelectorAll('.remove-presentacion-btn-edit');
        if (removeButtons.length === 1) {
            removeButtons[0].disabled = true;
            removeButtons[0].classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            removeButtons.forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }
    }

    // Evento para agregar nueva presentación (EDITAR)
    if (addPresentacionBtnEdit) {
        addPresentacionBtnEdit.addEventListener('click', () => {
            addPresentacionRowEdit();
        });
    }

    // Evento para eliminar presentación (EDITAR)
    if (presentacionesContainerEdit) {
        presentacionesContainerEdit.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-presentacion-btn-edit') || event.target.closest('.remove-presentacion-btn-edit')) {
                const button = event.target.closest('.remove-presentacion-btn-edit');
                const row = button.closest('.presentacion-row');

                if (presentacionesContainerEdit.children.length === 1) {
                    Swal.fire('Advertencia', 'Debe haber al menos una presentación para el producto.', 'warning');
                    return;
                }

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción no se puede revertir.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        row.remove();
                        updateRemoveButtonsEdit();
                        Swal.fire('Eliminado', 'La presentación ha sido eliminada.', 'success');
                    }
                });
            }
        });
    }

    // Exponer función para cargar presentaciones cuando se abre el modal de edición
    window.loadPresentacionesEdit = loadPresentacionesEdit;
});
