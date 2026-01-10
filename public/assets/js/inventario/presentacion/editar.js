function initEditarPresentacion() {
    const modal = document.getElementById('modalEditarPresentacion');
    if (!modal) return;

    const form = document.getElementById('formEditarPresentacion');
    const btnCancelar = document.getElementById('btnCancelarEditarPresentacion');

    function cerrarModal() {
        modal.style.display = 'none';
        form.reset();
    }

    document.getElementById('presentaciones-tbody').addEventListener('click', async function(e) {
        const btn = e.target.closest('.edit');
        if (!btn) return;
        
        const id = btn.dataset.id;
        try {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'flex';
            const res = await fetch(`/inventario/presentacion/api/${id}`);
            if (!res.ok) throw new Error('No se pudo cargar la presentación para editar.');
            const data = await res.json();
            
            if (data.success) {
                document.getElementById('editarPresentacionId').value = data.data.id;
                document.getElementById('editarPresentacionNombre').value = data.data.nombre;
                document.getElementById('editarPresentacionDescripcion').value = data.data.descripcion || '';
                // Presentación simplificada: solo nombre y descripción
                modal.style.display = 'flex';
                document.getElementById('editarPresentacionNombre').focus();
            } else {
                Swal.fire('Error', data.message || 'No se encontró la presentación', 'error');
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

    // Presentación simplificada: sin cálculo automático ni campos extra

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const id = document.getElementById('editarPresentacionId').value;
        const nombre = document.getElementById('editarPresentacionNombre').value;
        const descripcion = document.getElementById('editarPresentacionDescripcion').value;
        const token = document.querySelector('meta[name="csrf-token"]').content;
        
        const btnGuardar = document.getElementById('btnGuardarEditarPresentacion');
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<iconify-icon icon="line-md:loading-loop"></iconify-icon> Actualizando...';

        try {
            const res = await fetch(`/inventario/presentacion/api/${id}`, {
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
                    text: 'Presentación actualizada correctamente.',
                    showConfirmButton: false,
                    timer: 1500
                });
                cerrarModal();
                cargarPresentaciones();
            } else {
                let msg = data.message || 'No se pudo actualizar la presentación.';
                 if (data.errors && data.errors.nombre) {
                    msg = data.errors.nombre[0];
                }
                Swal.fire('Error', msg, 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Ocurrió un error de red.', 'error');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<iconify-icon icon="ic:round-check-circle"></iconify-icon> Guardar';
        }
    });
}
