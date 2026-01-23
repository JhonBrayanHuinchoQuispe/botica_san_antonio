function initEliminarPresentacion() {
    document.getElementById('presentaciones-tbody').addEventListener('click', function(e) {
        const btn = e.target.closest('.delete');
        if (!btn) return;

        const id = btn.dataset.id;
        const fila = btn.closest('tr');
        const nombre = fila.querySelector('td:nth-child(2)').textContent;
        const productos = fila.querySelector('td:nth-child(4)').textContent;

        Swal.fire({
            title: `¿Eliminar "${nombre}"?`,
            html: `
                <div style="text-align:left; padding: 0 1em;">
                <p>Esta acción es irreversible.</p>
                <p class="swal-warning-text">Actualmente hay <b>${productos}</b> producto(s) con esta presentación.</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'swal2-popup-custom',
                confirmButton: 'swal2-confirm-custom-rojo',
                cancelButton: 'swal2-cancel-custom'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                eliminar(id);
            }
        });
    });
}

async function eliminar(id) {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    try {
        const res = await fetch(`/inventario/presentacion/api/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            }
        });

        const data = await res.json();
        
        if (data.success) {
            if (data.needsRefresh) {
                Swal.fire({
                    title: '¡Eliminada!',
                    text: 'La presentación ha sido eliminada y la lista se ha actualizado.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    timerProgressBar: true
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    title: '¡Eliminada!',
                    text: 'La presentación ha sido eliminada.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                cargarPresentaciones();
            }
        } else {
            Swal.fire('Error', data.message || 'No se pudo eliminar la presentación.', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Ocurrió un error de red.', 'error');
    }
}
