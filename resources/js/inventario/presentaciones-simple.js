// public/assets/js/inventario/presentaciones-simple.js
document.addEventListener('DOMContentLoaded', function() {
    const presentacionesContainer = document.getElementById('presentaciones-container');
    const addPresentacionBtn = document.getElementById('add-presentacion-btn');
    const productoIdHidden = document.getElementById('producto_id_hidden');
    const productoId = productoIdHidden?.value; // Obtener el ID del producto si existe

    let presentacionCounter = 0;

    // Función para cargar presentaciones existentes
    async function loadPresentaciones() {
        if (!productoId) {
            // Si es un nuevo producto, agregar una presentación por defecto (Unidad)
            if (presentacionesContainer.children.length === 0) {
                addPresentacionRow({
                    nombre_presentacion: 'Unidad',
                    unidades_por_presentacion: 1,
                    precio_compra_presentacion: '',
                    precio_venta_presentacion: ''
                });
            }
            return;
        }

        try {
            const response = await fetch(`/inventario/producto/presentaciones/api/${productoId}`);
            const result = await response.json();

            if (result.success && result.data.length > 0) {
                result.data.forEach(presentacion => {
                    addPresentacionRow(presentacion);
                });
            } else {
                // Si no hay presentaciones, agregar una por defecto (Unidad)
                if (presentacionesContainer.children.length === 0) {
                    addPresentacionRow({
                        nombre_presentacion: 'Unidad',
                        unidades_por_presentacion: 1,
                        precio_compra_presentacion: '',
                        precio_venta_presentacion: ''
                    });
                }
            }
        } catch (error) {
            console.error('Error loading presentaciones:', error);
            Swal.fire('Error', 'No se pudieron cargar las presentaciones existentes.', 'error');
        }
    }

    // Función para agregar una fila de presentación
    function addPresentacionRow(data = {}) {
        const id = data.id || `new_${presentacionCounter++}`;

        const row = document.createElement('div');
        row.classList.add('presentacion-row', 'grid', 'grid-cols-12', 'gap-4', 'mb-4', 'p-4', 'border', 'rounded-lg', 'bg-gray-50');
        row.setAttribute('data-id', id);

        row.innerHTML = `
            <input type="hidden" name="presentaciones[${id}][id]" value="${data.id || ''}">
            <div class="col-span-12 md:col-span-3">
                <label for="nombre_presentacion_${id}" class="block text-sm font-medium text-gray-700">Nombre Presentación</label>
                <input type="text" name="presentaciones[${id}][nombre_presentacion]" id="nombre_presentacion_${id}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    value="${data.nombre_presentacion || ''}" required placeholder="Ej: Unidad, Blíster, Caja">
            </div>
            <div class="col-span-12 md:col-span-3">
                <label for="unidades_por_presentacion_${id}" class="block text-sm font-medium text-gray-700">Unidades x Presentación</label>
                <input type="number" name="presentaciones[${id}][unidades_por_presentacion]" id="unidades_por_presentacion_${id}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    value="${data.unidades_por_presentacion || 1}" min="1" required placeholder="Ej: 1, 10, 20">
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="precio_compra_presentacion_${id}" class="block text-sm font-medium text-gray-700">Precio Compra</label>
                <input type="number" name="presentaciones[${id}][precio_compra_presentacion]" id="precio_compra_presentacion_${id}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    value="${data.precio_compra_presentacion || ''}" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="col-span-12 md:col-span-2">
                <label for="precio_venta_presentacion_${id}" class="block text-sm font-medium text-gray-700">Precio Venta</label>
                <input type="number" name="presentaciones[${id}][precio_venta_presentacion]" id="precio_venta_presentacion_${id}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    value="${data.precio_venta_presentacion || ''}" step="0.01" min="0" required placeholder="0.00">
            </div>
            <div class="col-span-12 md:col-span-2 flex items-end">
                <button type="button" class="remove-presentacion-btn inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all">
                    <i class="fas fa-trash-alt mr-2"></i> Eliminar
                </button>
            </div>
        `;
        presentacionesContainer.appendChild(row);

        // Deshabilitar el botón de eliminar si es la única fila
        updateRemoveButtons();
    }

    function updateRemoveButtons() {
        const removeButtons = presentacionesContainer.querySelectorAll('.remove-presentacion-btn');
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

    // Evento para agregar nueva presentación
    if (addPresentacionBtn) {
        addPresentacionBtn.addEventListener('click', () => {
            addPresentacionRow();
        });
    }

    // Evento para eliminar presentación
    if (presentacionesContainer) {
        presentacionesContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-presentacion-btn') || event.target.closest('.remove-presentacion-btn')) {
                const button = event.target.closest('.remove-presentacion-btn');
                const row = button.closest('.presentacion-row');

                if (presentacionesContainer.children.length === 1) {
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
                        updateRemoveButtons();
                        Swal.fire('Eliminado', 'La presentación ha sido eliminada.', 'success');
                    }
                });
            }
        });
    }

    // Cargar presentaciones al iniciar
    if (presentacionesContainer && addPresentacionBtn) {
        loadPresentaciones();
    }
});
