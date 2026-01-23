function initEditarCategoria() {
    const modal = document.getElementById('modalEditarCategoria');
    if (!modal) return;

    const form = document.getElementById('formEditarCategoria');
    const btnCancelar = document.getElementById('btnCancelarEditarCategoria');

    function cerrarModal() {
        modal.style.display = 'none';
        form.reset();
    }

    // Event listener para abrir el modal
    document.getElementById('categorias-tbody').addEventListener('click', async function(e) {
        const btn = e.target.closest('.edit');
        if (!btn) return;
        
        const id = btn.dataset.id;
        try {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'flex';
            const res = await fetch(`/inventario/categoria/api/${id}`);
            if (!res.ok) throw new Error('No se pudo cargar la categoría para editar.');
            const data = await res.json();
            
            if (data.success) {
                document.getElementById('editarCategoriaId').value = data.data.id;
                document.getElementById('editarCategoriaNombre').value = data.data.nombre;
                document.getElementById('editarCategoriaDescripcion').value = data.data.descripcion || '';
                modal.style.display = 'flex';
                document.getElementById('editarCategoriaNombre').focus();
            } else {
                Swal.fire('Error', data.message || 'No se encontró la categoría', 'error');
            }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        } finally {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'none';
        }
    });

    btnCancelar.addEventListener('click', cerrarModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) cerrarModal();
    });
    window.addEventListener('keydown', (e) => {
        if (modal.style.display === 'flex' && e.key === 'Escape') cerrarModal();
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const id = document.getElementById('editarCategoriaId').value;
        const nombre = document.getElementById('editarCategoriaNombre').value;
        const descripcion = document.getElementById('editarCategoriaDescripcion').value;
        const token = document.querySelector('meta[name="csrf-token"]').content;
        
        const btnGuardar = document.getElementById('btnGuardarEditarCategoria');
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<iconify-icon icon="line-md:loading-loop"></iconify-icon> Actualizando...';

        try {
            const res = await fetch(`/inventario/categoria/api/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ nombre, descripcion })
            });

            const data = await res.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Categoría actualizada correctamente.',
                    showConfirmButton: false,
                    timer: 1500
                });
                cerrarModal();
                cargarCategorias();
            } else {
                let msg = data.message || 'No se pudo actualizar la categoría.';
                 if (data.errors && data.errors.nombre) {
                    msg = data.errors.nombre[0];
                }
                Swal.fire('Error', msg, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Ocurrió un error de red.', 'error');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<iconify-icon icon="ic:round-check-circle" class="text-lg"></iconify-icon> Guardar';
        }
    });
}
