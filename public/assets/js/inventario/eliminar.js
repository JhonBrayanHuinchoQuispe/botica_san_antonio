// Hacer la función global
window.eliminarProducto = function(id) {
    console.log('🗑️ Función eliminarProducto llamada con ID:', id);
    
    // Verificar que SweetAlert2 esté disponible
    if (typeof Swal === 'undefined') {
        console.error('❌ SweetAlert2 no está disponible');
        alert('Error: Sistema no inicializado correctamente');
        return;
    }
    // Obtener el nombre del producto y sus detalles
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (!row) {
        console.error('No se encontró la fila del producto con ID:', id);
        return;
    }
    
    // Obtener datos del producto de la fila (robusto y alineado al layout actual)
    const nombreProducto = (row.querySelector('h6')?.textContent || 'Producto').trim();
    const estado = (row.querySelector('.estado-badge')?.textContent || 'Normal').trim();
    // Precio venta y compra renderizados con clases .price-cell .pv / .pc
    const precioVentaText = row.querySelector('.price-cell .pv')?.textContent || '';
    const precioCompraText = row.querySelector('.price-cell .pc')?.textContent || '';
    const precioVenta = precioVentaText.replace(/.*P\.\s*Venta:\s*/i, '').trim();
    const precioCompra = precioCompraText.replace(/.*P\.\s*Compra:\s*/i, '').trim();
    // Stock mostrado como chip "XX uni"
    const stockText = row.querySelector('.chip')?.textContent || '';

    Swal.fire({
        title: '¿Eliminar producto?',
        html: `
            <div style="text-align:center;">
                <p style="margin:0 0 8px;color:#6b7280;">Estás a punto de eliminar el producto:</p>
                <div style="background:#f9fafb;border-radius:12px;padding:14px 16px;margin-bottom:12px;">
                    <p style="font-weight:600;font-size:1.05rem;color:#1f2937;margin:0 0 6px;">${nombreProducto}</p>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;font-size:.9rem;color:#374151;">
                        ${precioVenta ? `<span>Precio Venta: ${precioVenta}</span>` : ''}
                        ${precioCompra ? `<span>Precio Compra: ${precioCompra}</span>` : ''}
                        ${stockText ? `<span>Stock: ${stockText}</span>` : ''}
                        <span style="padding:.15rem .5rem;border-radius:9999px;background:${estado.toLowerCase()==='normal'?'#d1fae5':estado.toLowerCase()==='bajo stock'?'#fef3c7':'#fee2e2'};color:${estado.toLowerCase()==='normal'?'#065f46':estado.toLowerCase()==='bajo stock'?'#92400e':'#991b1b'};">${estado}</span>
                    </div>
                </div>
                <p style="font-size:.9rem;color:#6b7280;margin:0;">Esta acción eliminará permanentemente el producto del inventario y no podrá ser recuperado.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#F3F4F6',
        confirmButtonText: '<span class="text-white">Sí, eliminar producto</span>',
        cancelButtonText: '<span class="text-gray-700">Cancelar</span>',
        customClass: {
            popup: 'swal2-popup-custom',
            confirmButton: 'swal2-confirm-custom',
            cancelButton: 'swal2-cancel-custom',
            htmlContainer: 'swal2-html-custom'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const token = document.querySelector('meta[name="csrf-token"]').content;
            
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando producto...',
                text: 'Por favor, espere...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/inventario/producto/eliminar/${id}`, {  // Ruta correcta según web.php
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.needsRefresh) {
                        // Recargar la página si los IDs fueron reordenados
                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminado correctamente!',
                            text: `${nombreProducto} ha sido eliminado del inventario.`,
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        // Eliminar la fila con animación
                        const row = document.querySelector(`tr[data-id="${id}"]`);
                        if (row) {
                            row.style.transition = 'all 0.3s ease-out';
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(20px)';
                            setTimeout(() => {
                                row.remove();
                                actualizarContador();
                                reordenarIds(); // Nueva función para reordenar IDs visualmente
                            }, 300);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminado correctamente!',
                            text: `${nombreProducto} ha sido eliminado del inventario.`,
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                    }
                } else {
                    throw new Error(data.message || 'Error al eliminar el producto');
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error al eliminar',
                    text: error.message || 'Hubo un problema al intentar eliminar el producto.',
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#dc2626'
                });
            });
        }
    });
}

// Función para actualizar el contador de registros
function actualizarContador() {
    const totalRows = document.querySelectorAll('tbody tr').length;
    const counter = document.querySelector('.datatable-info');
    if (counter) {
        // Eliminar cualquier asignación a counter.textContent con mensajes de conteo de productos
    }
}

// Nueva función para reordenar IDs visualmente
function reordenarIds() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
        const idCell = row.querySelector('td:first-child');
        if (idCell) {
            idCell.textContent = (index + 1).toString();
        }
    });
}

// Confirmar que el script se cargó correctamente
console.log('✅ Script eliminar.js cargado correctamente');
console.log('🗑️ Función eliminarProducto disponible:', typeof window.eliminarProducto);