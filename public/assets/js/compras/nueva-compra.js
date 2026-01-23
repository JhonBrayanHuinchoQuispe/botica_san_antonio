document.addEventListener('DOMContentLoaded', function() {
    // Elementos del formulario
    const form = document.getElementById('formEntradaMercaderia');
    const buscarProductoInput = document.getElementById('buscar-producto');
    const productoIdInput = document.getElementById('producto-id');
    const resultadosContainer = document.getElementById('resultados-busqueda');
    const proveedorSelect = document.getElementById('proveedor-select');
    const btnRegistrar = document.getElementById('btn-registrar-entrada');
    const btnLimpiarTodo = document.getElementById('btn-limpiar-todo');
    const btnLimpiarProducto = document.getElementById('btn-limpiar-producto');

    // Elementos de precios y stock
    const inputCantidad = document.getElementById('input-cantidad');
    const inputPrecioCompra = document.getElementById('input-precio-compra');
    const inputPrecioVenta = document.getElementById('input-precio-venta');
    const stockActualEl = document.getElementById('preview-stock-actual');
    const stockNuevoEl = document.getElementById('preview-stock-nuevo');

    // Elementos de Lote
    const checkLoteExistente = document.getElementById('check-lote-existente');
    const inputLoteTexto = document.getElementById('input-lote-texto');
    const containerLoteNuevo = document.getElementById('container-lote-nuevo');
    const containerLoteSelect = document.getElementById('container-lote-select');
    const selectLote = document.getElementById('select-lote');
    const inputFechaVencimiento = document.getElementById('input-fecha-vencimiento');
    const existingLoteIdInput = document.getElementById('existing-lote-id');

    // Elementos de Presentaciones y Footer
    const containerInferior = document.getElementById('container-inferior');
    const sectionPresentaciones = document.getElementById('section-presentaciones');
    const listaPresentaciones = document.getElementById('lista-presentaciones-precios');

    // Estado global
    let timeoutBusqueda = null;
    let productoSeleccionado = null;
    let lotesActivos = [];
    let presentacionesCache = [];
    let productosBusqueda = [];

    // --- INICIALIZACIÓN ---

    if (buscarProductoInput) {
        buscarProductoInput.addEventListener('input', manejarBusquedaProducto);
        buscarProductoInput.addEventListener('focus', () => {
            if (resultadosContainer && resultadosContainer.innerHTML.trim()) {
                resultadosContainer.style.display = 'block';
            }
        });
    }

    if (btnLimpiarProducto) {
        btnLimpiarProducto.addEventListener('click', limpiarProductoSeleccionado);
    }

    if (btnLimpiarTodo) {
        btnLimpiarTodo.addEventListener('click', () => {
            Swal.fire({
                title: '¿Limpiar todo?',
                text: "Se borrarán todos los datos ingresados en el formulario",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e53e3e',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Sí, limpiar',
                cancelButtonText: 'No, cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.reset();
                    limpiarProductoSeleccionado();
                }
            });
        });
    }

    if (form) {
        form.addEventListener('submit', procesarEntrada);
        form.addEventListener('input', validarFormulario);
        form.addEventListener('change', validarFormulario);
        if (inputCantidad) inputCantidad.addEventListener('input', actualizarPreviewStock);
    }

    if (inputPrecioVenta) {
        inputPrecioVenta.addEventListener('input', actualizarPrecioUnidadTabla);
    }

    // --- LÓGICA DE BÚSQUEDA (DISEÑO BONITO RESTAURADO) ---

    function manejarBusquedaProducto(e) {
        const termino = e.target.value.trim();
        if (termino.length > 0) btnLimpiarProducto.style.display = 'flex';
        else btnLimpiarProducto.style.display = 'none';
        
        if (timeoutBusqueda) clearTimeout(timeoutBusqueda);

        if (termino.length < 2) {
            resultadosContainer.style.display = 'none';
            return;
        }

        timeoutBusqueda = setTimeout(() => buscarProductos(termino), 300);
    }

    async function buscarProductos(termino) {
        try {
            const response = await fetch(`/api/compras/buscar-productos?q=${encodeURIComponent(termino)}`);
            const data = await response.json();
            if (data.success) {
                productosBusqueda = data.productos || [];
                renderizarResultadosBusqueda(productosBusqueda);
            }
        } catch (error) {
            console.error('Error al buscar productos:', error);
        }
    }

    function renderizarResultadosBusqueda(productos) {
        if (!resultadosContainer) return;
        if (productos.length === 0) {
            resultadosContainer.innerHTML = '<div class="compras-no-resultados">No se encontraron productos</div>';
            resultadosContainer.style.display = 'block';
            return;
        }

        resultadosContainer.innerHTML = productos.map(producto => {
            let claseEstado = '';
            let iconoEstado = '';
            let textoEstado = '';

            if (producto.estados_aplicables && Array.isArray(producto.estados_aplicables)) {
                if (producto.estados_aplicables.some(e => e.toLowerCase().includes('agotado'))) {
                    claseEstado = 'stock-agotado'; iconoEstado = '⚠️'; textoEstado = 'Agotado';
                } else if (producto.estados_aplicables.some(e => e.toLowerCase().includes('bajo'))) {
                    claseEstado = 'stock-bajo'; iconoEstado = '⚡'; textoEstado = 'Bajo Stock';
                } else if (producto.estados_aplicables.some(e => e.toLowerCase().includes('vencer'))) {
                    claseEstado = 'proximo-vencimiento'; iconoEstado = '⏰'; textoEstado = 'Por Vencer';
                }
            }

            const esSugerido = producto.sugerido ? 'sugerido' : '';
            const iconoSugerido = producto.sugerido ? '⭐' : '';

            return `
                <div class="compras-resultado-item ${claseEstado} ${esSugerido}" onclick="window.seleccionarProductoDeLista(${producto.id})">
                    <div class="compras-resultado-info">
                        <div class="compras-resultado-nombre">
                            ${iconoSugerido} ${producto.nombre}
                            ${iconoEstado ? `<span class="estado-badge ${claseEstado}">${iconoEstado} ${textoEstado}</span>` : ''}
                        </div>
                        <div class="compras-resultado-detalles">
                            <span class="concentracion-text">${producto.concentracion || ''}</span>
                            <span class="compras-stock ${claseEstado}">Stock: ${producto.stock_actual}</span>
                            ${producto.lote ? `<span class="compras-lote">Lote: ${producto.lote}</span>` : ''}
                        </div>
                        <div class="compras-historial">Última entrada: ${producto.texto_ultima_entrada}</div>
                    </div>
                    <div class="compras-resultado-precio">S/. ${parseFloat(producto.precio_venta || 0).toFixed(2)}</div>
                </div>
            `;
        }).join('');
        resultadosContainer.style.display = 'block';
    }

    window.seleccionarProductoDeLista = function(id) {
        const producto = productosBusqueda.find(p => p.id == id);
        if (!producto) return;

        productoSeleccionado = producto;
        buscarProductoInput.value = producto.nombre;
        productoIdInput.value = producto.id;
        resultadosContainer.style.display = 'none';
        btnLimpiarProducto.style.display = 'flex';

        // Placeholders de precios
        if (inputPrecioCompra) inputPrecioCompra.placeholder = `Actual: S/. ${parseFloat(producto.precio_compra || 0).toFixed(2)}`;
        if (inputPrecioVenta) inputPrecioVenta.placeholder = `Actual: S/. ${parseFloat(producto.precio_venta || 0).toFixed(2)}`;

        actualizarPreviewStock();
        if (containerInferior) containerInferior.style.display = 'block';
        cargarPresentaciones(id);
        obtenerLotesActivos(id);
        validarFormulario();
    };

    function limpiarProductoSeleccionado() {
        buscarProductoInput.value = '';
        productoIdInput.value = '';
        productoSeleccionado = null;
        btnLimpiarProducto.style.display = 'none';
        resultadosContainer.innerHTML = '';
        resultadosContainer.style.display = 'none';

        // Limpiar Lotes
        lotesActivos = [];
        selectLote.innerHTML = '<option value="">Seleccionar lote...</option>';
        checkLoteExistente.checked = false;
        checkLoteExistente.disabled = true;
        toggleModoLote();

        // Limpiar Presentaciones
        presentacionesCache = [];
        if (containerInferior) containerInferior.style.display = 'none';
        listaPresentaciones.innerHTML = '';

        actualizarPreviewStock();
        validarFormulario();
    }

    // --- LÓGICA DE LOTES ---

    if (checkLoteExistente) checkLoteExistente.addEventListener('change', toggleModoLote);
    if (selectLote) selectLote.addEventListener('change', alSeleccionarLote);

    function toggleModoLote() {
        const esExistente = checkLoteExistente.checked;
        containerLoteNuevo.style.display = esExistente ? 'none' : 'block';
        containerLoteSelect.style.display = esExistente ? 'block' : 'none';
        inputLoteTexto.disabled = esExistente;
        selectLote.disabled = !esExistente;
        existingLoteIdInput.disabled = !esExistente;
        if (inputFechaVencimiento) inputFechaVencimiento.readOnly = esExistente;
        if (proveedorSelect) proveedorSelect.style.pointerEvents = esExistente ? 'none' : 'auto';

        if (!esExistente) {
            inputLoteTexto.value = '';
            inputFechaVencimiento.value = '';
            proveedorSelect.value = '';
            existingLoteIdInput.value = '';
            if (inputPrecioCompra) { inputPrecioCompra.value = ''; inputPrecioCompra.readOnly = false; }
            if (inputPrecioVenta) { inputPrecioVenta.value = ''; inputPrecioVenta.readOnly = false; }
            // Re-renderizar presentaciones como editables
            if (presentacionesCache.length > 0) renderizarPresentaciones(presentacionesCache, false);
        } else {
            alSeleccionarLote();
        }
        validarFormulario();
    }

    function alSeleccionarLote() {
        const loteCodigo = selectLote.value;
        if (!loteCodigo) {
            inputFechaVencimiento.value = '';
            proveedorSelect.value = '';
            existingLoteIdInput.value = '';
            return;
        }

        const lote = lotesActivos.find(l => l.lote === loteCodigo);
        if (lote) {
            existingLoteIdInput.value = lote.id;
            inputFechaVencimiento.value = lote.fecha_vencimiento ? lote.fecha_vencimiento.split('T')[0] : '';
            proveedorSelect.value = lote.proveedor_id || '';
            if (inputPrecioCompra) { inputPrecioCompra.value = lote.precio_compra_lote || ''; inputPrecioCompra.readOnly = true; }
            if (inputPrecioVenta) { inputPrecioVenta.value = lote.precio_venta_lote || ''; inputPrecioVenta.readOnly = true; }

            // Re-renderizar presentaciones como solo lectura para lotes existentes
            if (presentacionesCache.length > 0) renderizarPresentaciones(presentacionesCache, true);

            if (lote.presentaciones_precios) {
                lote.presentaciones_precios.forEach(pp => {
                    const tr = document.querySelector(`tr[data-pres-id="${pp.producto_presentacion_id}"]`);
                    if (tr) {
                        const inputPrecio = tr.querySelector('.input-precio-table');
                        if (inputPrecio) inputPrecio.value = pp.precio_venta;
                        
                        const inputUnidades = tr.querySelector('.input-pres-unidades');
                        if (inputUnidades && pp.unidades_por_presentacion) {
                            inputUnidades.value = pp.unidades_por_presentacion;
                        }
                    }
                });
            }
            actualizarPrecioUnidadTabla();
        }
        validarFormulario();
    }

    async function obtenerLotesActivos(productoId) {
        try {
            const response = await fetch(`/api/compras/productos/${productoId}/lotes-activos`);
            const data = await response.json();
            if (data.success && data.lotes && data.lotes.length > 0) {
                lotesActivos = data.lotes;
                checkLoteExistente.disabled = false;
                selectLote.innerHTML = '<option value="">Seleccionar lote...</option>' + 
                    data.lotes.map(l => `<option value="${l.lote}">${l.lote} (Stock: ${l.cantidad})</option>`).join('');
            } else {
                checkLoteExistente.disabled = true;
                checkLoteExistente.checked = false;
                toggleModoLote();
            }
        } catch (error) { console.error('Error al obtener lotes:', error); }
    }

    // --- LÓGICA DE PRESENTACIONES ---

    async function cargarPresentaciones(productoId) {
        try {
            console.log('Cargando presentaciones para producto:', productoId);
            
            // Si el contenedor inferior no está visible, lo mostramos
            if (containerInferior) containerInferior.style.display = 'block';

            listaPresentaciones.innerHTML = `
                <tr>
                    <td colspan="3" class="py-8 text-center">
                        <div class="flex items-center justify-center gap-2 text-slate-400">
                            <iconify-icon icon="line-md:loading-twotone-loop" style="font-size: 24px;"></iconify-icon>
                            <span class="text-sm font-medium">Buscando presentaciones...</span>
                        </div>
                    </td>
                </tr>
            `;
            
            const response = await fetch(`/inventario/producto/presentaciones/api/${productoId}`);
            const data = await response.json();
            
            console.log('Respuesta API Presentaciones:', data);
            
            // EXTRAER PRESENTACIONES CORRECTAMENTE
            let presentaciones = [];
            if (data.data) presentaciones = data.data;
            else if (data.presentaciones) presentaciones = data.presentaciones;
            else if (Array.isArray(data)) presentaciones = data;

            if (presentaciones && presentaciones.length > 0) {
                presentacionesCache = presentaciones;
                const esReadOnly = checkLoteExistente && checkLoteExistente.checked;
                renderizarPresentaciones(presentaciones, esReadOnly);
                if (sectionPresentaciones) sectionPresentaciones.style.display = 'block';
            } else {
                console.log('No se encontraron presentaciones para este producto');
                presentacionesCache = [];
                if (sectionPresentaciones) sectionPresentaciones.style.display = 'block';
                listaPresentaciones.innerHTML = `
                    <tr>
                        <td colspan="3" class="py-8 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <iconify-icon icon="solar:info-circle-bold-duotone" style="font-size: 32px;"></iconify-icon>
                                <span class="text-sm font-medium">Este producto no tiene presentaciones configuradas.</span>
                                <span class="text-[11px]">Solo se registrará el stock en unidades base.</span>
                            </div>
                        </td>
                    </tr>
                `;
            }
        } catch (error) { 
            console.error('Error al cargar presentaciones:', error);
            if (sectionPresentaciones) sectionPresentaciones.style.display = 'none';
        }
    }

    function renderizarPresentaciones(presentaciones, isReadOnly = false) {
        // Toggle badge obligatorio
        const badgeObligatorio = document.querySelector('.badge-obligatorio');
        if (badgeObligatorio) {
            if (isReadOnly) {
                badgeObligatorio.style.display = 'none';
            } else {
                badgeObligatorio.style.display = 'inline-block';
            }
        }

        listaPresentaciones.innerHTML = presentaciones.map(pres => {
            const nombre = pres.nombre_presentacion || pres.nombre || 'Sin nombre';
            const unidades = pres.unidades_por_presentacion || pres.cantidad_unidades || 1;
            const precio = pres.precio_venta_presentacion || pres.precio || 0;
            
            const isUnidad = nombre.toLowerCase() === 'unidad';
            const disableField = isReadOnly || isUnidad;
            
            return `
                <tr data-pres-id="${pres.id}" data-unidades-base="${unidades}">
                    <td class="py-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg ${isUnidad ? 'bg-blue-50 text-blue-500' : 'bg-slate-50 text-slate-400'}">
                                <iconify-icon icon="${isUnidad ? 'solar:box-bold' : 'solar:box-minimalistic-bold'}" 
                                              class="icon-presentation-blue" style="font-size: 20px;"></iconify-icon>
                            </div>
                            <div class="font-bold text-slate-700 text-[15px]">${nombre}</div>
                        </div>
                    </td>
                    <td class="py-4">
                        <div class="flex items-center justify-center gap-2">
                            ${!disableField ? `
                                <button type="button" class="unit-control-btn" onclick="window.cambiarUnidadesPres(${pres.id}, -1)">
                                    <iconify-icon icon="solar:minus-circle-bold"></iconify-icon>
                                </button>
                            ` : ''}
                            <input type="number" name="presentaciones_unidades[${pres.id}]" 
                                   id="pres_unidades_${pres.id}"
                                   class="input-unit-blue" 
                                   value="${unidades}" 
                                   ${disableField ? 'readonly tabindex="-1"' : ''} 
                                   min="1" required
                                   oninput="window.recalcularPreciosPres()">
                            ${!disableField ? `
                                <button type="button" class="unit-control-btn" onclick="window.cambiarUnidadesPres(${pres.id}, 1)">
                                    <iconify-icon icon="solar:add-circle-bold"></iconify-icon>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                    <td class="py-4">
                        <div class="flex flex-col items-end">
                            <div class="flex items-center justify-end gap-1 text-emerald-500 font-bold bg-emerald-50 px-3 py-1 rounded-lg">
                                <span class="text-sm">S/</span>
                                <input type="number" step="0.01" name="presentaciones[${pres.id}]" 
                                       id="pres_precio_${pres.id}"
                                       class="input-minimalist price-text w-24 text-right bg-transparent border-none font-bold text-emerald-600" 
                                       value="${parseFloat(precio).toFixed(2)}" 
                                       placeholder="0.00" 
                                       ${disableField ? 'readonly tabindex="-1"' : ''} 
                                       oninput="window.actualizarGananciaManual(${pres.id})"
                                       required>
                            </div>
                            <span class="ganancia-tag" id="ganancia_pres_${pres.id}">Ganancia: S/ 0.00</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
        
        // Exponer funciones globalmente
        window.cambiarUnidadesPres = function(id, delta) {
            const input = document.getElementById(`pres_unidades_${id}`);
            if (input && !input.readOnly) {
                const newVal = Math.max(1, parseInt(input.value || 1) + delta);
                input.value = newVal;
                window.recalcularPreciosPres();
            }
        };

        window.recalcularPreciosPres = function() {
            const precioVentaBase = parseFloat(inputPrecioVenta.value || 0);
            const precioCompraBase = parseFloat(inputPrecioCompra.value || 0);

            document.querySelectorAll('#lista-presentaciones-precios tr').forEach(row => {
                const presId = row.dataset.presId;
                const unidades = parseInt(document.getElementById(`pres_unidades_${presId}`).value || 1);
                const inputPrecio = document.getElementById(`pres_precio_${presId}`);
                const tagGanancia = document.getElementById(`ganancia_pres_${presId}`);
                const isUnidad = row.querySelector('.font-bold').innerText.toLowerCase() === 'unidad';

                if (isUnidad) {
                    inputPrecio.value = precioVentaBase.toFixed(2);
                } else if (!inputPrecio.readOnly && precioVentaBase > 0) {
                    // Autocalcular sugerido basado en unidades
                    inputPrecio.value = (precioVentaBase * unidades).toFixed(2);
                }

                // Calcular ganancia
                const precioVentaPres = parseFloat(inputPrecio.value || 0);
                const costoTotalPres = precioCompraBase * unidades;
                const ganancia = (precioVentaPres - costoTotalPres).toFixed(2);
                
                if (tagGanancia) {
                    tagGanancia.innerText = `Ganancia: S/ ${ganancia}`;
                    tagGanancia.style.color = ganancia > 0 ? '#10b981' : '#ef4444';
                }
            });
        };

        window.actualizarGananciaManual = function(id) {
            const precioCompraBase = parseFloat(inputPrecioCompra.value || 0);
            const unidades = parseInt(document.getElementById(`pres_unidades_${id}`).value || 1);
            const inputPrecio = document.getElementById(`pres_precio_${id}`);
            const tagGanancia = document.getElementById(`ganancia_pres_${id}`);
            
            const precioVentaPres = parseFloat(inputPrecio.value || 0);
            const costoTotalPres = precioCompraBase * unidades;
            const ganancia = (precioVentaPres - costoTotalPres).toFixed(2);
            
            if (tagGanancia) {
                tagGanancia.innerText = `Ganancia: S/ ${ganancia}`;
                tagGanancia.style.color = ganancia > 0 ? '#10b981' : '#ef4444';
            }
        };

        // Escuchar cambios en precios base
        inputPrecioVenta.removeEventListener('input', window.recalcularPreciosPres);
        inputPrecioVenta.addEventListener('input', window.recalcularPreciosPres);
        
        inputPrecioCompra.removeEventListener('input', window.recalcularPreciosPres);
        inputPrecioCompra.addEventListener('input', window.recalcularPreciosPres);

        actualizarPrecioUnidadTabla();
        window.recalcularPreciosPres();
    }

    function actualizarPrecioUnidadTabla() {
        const precio = inputPrecioVenta.value || (productoSeleccionado ? productoSeleccionado.precio_venta : '');
        const trs = Array.from(document.querySelectorAll('#lista-presentaciones-precios tr'));
        const trUnidad = trs.find(tr => {
            const span = tr.querySelector('td .font-bold');
            return span && span.textContent.toLowerCase().includes('unidad');
        });
        if (trUnidad) {
            const input = trUnidad.querySelector('.price-text');
            if (input) input.value = precio;
        }
    }

    function actualizarPreviewStock() {
        if (!stockActualEl || !stockNuevoEl) return;
        const stockActual = productoSeleccionado ? parseInt(productoSeleccionado.stock_actual || 0) : 0;
        const cantidad = parseInt(inputCantidad.value) || 0;
        stockActualEl.textContent = productoSeleccionado ? stockActual : '—';
        stockNuevoEl.textContent = productoSeleccionado && cantidad > 0 ? stockActual + cantidad : '—';
    }

    function validarFormulario() {
        if (!productoIdInput || !proveedorSelect || !inputCantidad || !btnRegistrar) return;

        const pId = productoIdInput.value;
        const provId = proveedorSelect.value;
        const cant = inputCantidad.value;
        const esExistente = checkLoteExistente ? checkLoteExistente.checked : false;
        
        let loteValido = false;
        if (esExistente) {
            loteValido = selectLote && selectLote.value !== '';
        } else {
            const inputLote = document.getElementById('input-lote-texto');
            loteValido = inputLote && inputLote.value.trim() !== '';
        }

        const venc = inputFechaVencimiento ? inputFechaVencimiento.value : '';
        
        const esValido = (pId && provId && cant > 0 && loteValido && venc);
        if (btnRegistrar) btnRegistrar.disabled = !esValido;
    }

    async function procesarEntrada(e) {
        e.preventDefault();
        const formData = new FormData(form);
        try {
            const response = await fetch('/compras/procesar', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: '¡Entrada procesada!', timer: 1500, showConfirmButton: false });
                form.reset(); limpiarProductoSeleccionado();
            } else { Swal.fire({ icon: 'error', title: 'Error', text: data.message }); }
        } catch (error) { console.error('Error:', error); }
    }

    document.addEventListener('click', (e) => { if (!e.target.closest('.compras-busqueda-container')) resultadosContainer.style.display = 'none'; });
});
