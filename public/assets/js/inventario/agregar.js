document.addEventListener('DOMContentLoaded', function() {
    // Obtener elementos
    const filterEstado = document.getElementById('filterEstado');
    const table = document.getElementById('selection-table');
    const formAgregarProducto = document.getElementById('formAgregarProducto');
    
    // Inicializar filtrado
    if (filterEstado) {
        filterEstado.addEventListener('change', function() {
            const selectedEstado = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const estadoCell = row.querySelector('td:nth-child(5) span');
                if (!estadoCell) return;

                const estadoText = estadoCell.textContent.toLowerCase().trim();
                let showRow = false;

                if (selectedEstado === 'todos') {
                    showRow = true;
                } else {
                    // Manejar casos especiales
                    switch(selectedEstado) {
                        case 'bajo_stock':
                            showRow = estadoText === 'bajo stock';
                            break;
                        case 'por_vencer':
                            showRow = estadoText === 'por vencer';
                            break;
                        default:
                            showRow = estadoText === selectedEstado;
                            break;
                    }
                }

                row.style.display = showRow ? '' : 'none';
                if (showRow) visibleCount++;
            });

            // Actualizar contador
            updateFilterCounter(visibleCount, rows.length, selectedEstado);
        });
    }

    // --- VALIDACIONES BÁSICAS EN TIEMPO REAL ---
    if (formAgregarProducto) {
        // Nombre: permitir letras, números, espacios y caracteres especiales comunes
        const nombreInput = formAgregarProducto.querySelector('input[name="nombre"]');
        // Definir input de concentración aquí para que esté disponible en todo el ámbito
        const concentracionInput = formAgregarProducto.querySelector('input[name="concentracion"]');

        if (nombreInput) {
            nombreInput.addEventListener('keypress', function(e) {
                // Permitir letras, números, espacios y caracteres especiales comunes para medicamentos
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.\(\)\+\/]$/.test(e.key)) {
                    e.preventDefault();
                }
            });
            nombreInput.addEventListener('input', function(e) {
                // Limpiar caracteres no permitidos pero permitir escritura
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.\(\)\+\/]/g, '');
            });
        }

        // Marca: solo letras, números y espacios
        const marcaInput = formAgregarProducto.querySelector('input[name="marca"]');
        if (marcaInput) {
            marcaInput.addEventListener('keypress', function(e) {
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]$/.test(e.key)) {
                    e.preventDefault();
                }
            });
            marcaInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]/g, '');
            });
        }

        // Función para verificar duplicados (Nombre + Concentración)
        window.verificarDuplicado = async (silent = false) => {
            const nombre = nombreInput.value.trim();
            // Usar 'N/A' si no hay concentración para normalizar búsqueda
            const concentracion = concentracionInput ? concentracionInput.value.trim() : '';
            
            if (nombre.length > 2) {
                try {
                    // Buscar si existe - Usar endpoint de búsqueda global
                    const params = new URLSearchParams({ search: nombre });
                    params.append('per_page', 50); 
                    
                    const res = await fetch(`/inventario/productos/ajax?${params}`);
                    const data = await res.json();
                    
                    if (data && data.data && Array.isArray(data.data)) {
                        // Filtrar coincidencia exacta de nombre y concentración
                        const norm = (s) => (s || '').toLowerCase().trim().replace(/\s+/g, ' ');
                        
                        const duplicado = data.data.find(p => {
                            const pNombre = norm(p.nombre);
                            const pConc = norm(p.concentracion);
                            const inNombre = norm(nombre);
                            const inConc = norm(concentracion);
                            
                            // Coincidencia exacta de nombre Y concentración
                            return pNombre === inNombre && pConc === inConc;
                        });
                        
                        const parent = nombreInput.parentElement;
                        let alert = parent.querySelector('.duplicado-alert');
                        
                        if (duplicado) {
                            if (!alert) {
                                alert = document.createElement('div');
                                alert.className = 'duplicado-alert text-xs text-amber-600 mt-1 font-medium bg-amber-50 p-2 rounded border border-amber-200 flex items-center gap-1';
                                alert.innerHTML = '<iconify-icon icon="solar:danger-circle-bold"></iconify-icon> Este producto (Nombre + Concentración) ya existe.';
                                nombreInput.insertAdjacentElement('afterend', alert);
                            }
                            nombreInput.classList.add('border-amber-500');
                            if(concentracionInput) concentracionInput.classList.add('border-amber-500');
                            return true; // Duplicado encontrado
                        } else {
                            if (alert) alert.remove();
                            nombreInput.classList.remove('border-amber-500');
                            if(concentracionInput) concentracionInput.classList.remove('border-amber-500');
                            return false; // No hay duplicado
                        }
                    }
                } catch (e) { console.error('Error verificando duplicados', e); }
            }
            return false;
        };

        // Concentración: permitir letras, números y caracteres comunes para concentraciones
        // const concentracionInput = formAgregarProducto.querySelector('input[name="concentracion"]'); // Ya definido arriba
        if (concentracionInput) {
            concentracionInput.addEventListener('blur', () => window.verificarDuplicado()); // Verificar también al cambiar concentración
            
            concentracionInput.addEventListener('keypress', function(e) {
                // Permitir letras, números, espacios y caracteres para concentraciones (mg, ml, %, etc.)
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.\%\/]$/.test(e.key)) {
                    e.preventDefault();
                }
            });
            concentracionInput.addEventListener('input', function(e) {
                // Limpiar caracteres no permitidos pero permitir escritura
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.\%\/]/g, '');
            });
        }

        // Lote: solo letras, números, espacios, guiones y puntos
        const loteInput = formAgregarProducto.querySelector('input[name="lote"]');
        if (loteInput) {
            loteInput.addEventListener('keypress', function(e) {
                if (!/^[a-zA-Z0-9\s\-.]$/.test(e.key)) {
                    e.preventDefault();
                }
            });
            loteInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^a-zA-Z0-9\s\-.]/g, '');
            });
        }

        // Código de barras: solo números, máximo 13 dígitos
        const codBarrasInput = formAgregarProducto.querySelector('input[name="codigo_barras"]');
        if (codBarrasInput) {
            codBarrasInput.addEventListener('keypress', function(e) {
                if (!/^[0-9]$/.test(e.key) || this.value.length >= 13) {
                    e.preventDefault();
                }
            });
            codBarrasInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 13);
            });
        }

        // Stock: solo números enteros
        ['stock_actual', 'stock_minimo'].forEach(name => {
            const stockInput = formAgregarProducto.querySelector(`input[name="${name}"]`);
            if (stockInput) {
                stockInput.addEventListener('keypress', function(e) {
                    // Solo permitir números
                    if (!/^[0-9]$/.test(e.key)) {
                        e.preventDefault();
                    }
                    // Limitar longitud
                    if (this.value.length >= 6) {
                        e.preventDefault();
                    }
                });
                stockInput.addEventListener('input', function(e) {
                    // Solo números, sin límite de ceros iniciales (permitir 0)
                    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                });
            }
        });

        // Precios: solo números decimales, máximo dos decimales
        ['precio_compra', 'precio_venta'].forEach(name => {
            const precioInput = formAgregarProducto.querySelector(`input[name="${name}"]`);
            if (precioInput) {
                precioInput.addEventListener('keypress', function(e) {
                    // Permitir números, punto decimal y teclas de control
                    if (!/^[0-9\.]$/.test(e.key)) {
                        e.preventDefault();
                    }
                    // Solo permitir un punto decimal
                    if (e.key === '.' && this.value.includes('.')) {
                        e.preventDefault();
                    }
                    // Limitar longitud total
                    if (this.value.length >= 10 && this.selectionStart === this.value.length) {
                        e.preventDefault();
                    }
                });
                precioInput.addEventListener('input', function(e) {
                    let val = this.value.replace(/[^0-9.]/g, '');
                    
                    // Manejar múltiples puntos decimales
                    const parts = val.split('.');
                    if (parts.length > 2) {
                        val = parts[0] + '.' + parts.slice(1).join('');
                    }
                    
                    // Limitar decimales a 2 dígitos
                    if (parts[1] && parts[1].length > 2) {
                        parts[1] = parts[1].slice(0, 2);
                        val = parts[0] + '.' + parts[1];
                    }
                    
                    // Limitar longitud total
                    val = val.slice(0, 10);
                    
                    // No permitir que empiece con punto
                    if (val.startsWith('.')) {
                        val = '0' + val;
                    }
                    
                    this.value = val;
                });
            }
        });

        // Fechas: configurar fecha mínima para vencimiento
        const fechaFabInput = formAgregarProducto.querySelector('input[name="fecha_fabricacion"]');
        const fechaVenInput = formAgregarProducto.querySelector('input[name="fecha_vencimiento"]');
        
        if (fechaFabInput && fechaVenInput) {
            fechaFabInput.addEventListener('input', function() {
                if (this.value) {
                    fechaVenInput.min = this.value;
                } else {
                    fechaVenInput.removeAttribute('min');
                }
            });
        }
    }

    // --- VALIDACIONES BÁSICAS EN TIEMPO REAL PARA MODAL DE EDICIÓN ---
    const formEditarProducto = document.getElementById('formEditarProducto');
    if (formEditarProducto) {
        // Nombre: permitir edición con validaciones básicas
        const nombreEditInput = formEditarProducto.querySelector('input[name="nombre"]');
        if (nombreEditInput) {
            nombreEditInput.addEventListener('keypress', function(e) {
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.\(\)\+\/]$/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }

        // Concentración: permitir edición
        const concentracionEditInput = formEditarProducto.querySelector('#edit-concentracion');
        if (concentracionEditInput) {
            concentracionEditInput.addEventListener('keypress', function(e) {
                if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.\%\/]$/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }

        // Lote: solo letras, números, espacios, guiones y puntos
        const loteEditInput = formEditarProducto.querySelector('input[name="lote"]');
        if (loteEditInput) {
            loteEditInput.addEventListener('keypress', function(e) {
                if (!/^[a-zA-Z0-9\s\-.]$/.test(e.key)) {
                    e.preventDefault();
                }
            });
            loteEditInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^a-zA-Z0-9\s\-.]/g, '');
            });
        }
    }

    // Cargar categorías dinámicamente en el modal de agregar producto
    function cargarCategoriasEnSelect() {
        fetch('/inventario/categoria/api/all')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const select = document.querySelector('#modalAgregar select[name="categoria"]');
                    if (select) {
                        select.innerHTML = '<option value="">Seleccionar</option>';
                        data.data.forEach(cat => {
                            select.innerHTML += `<option value="${cat.nombre}">${cat.nombre}</option>`;
                        });
                    }
                }
            });
    }

    // Mostrar el modal y cargar categorías
    const btnAgregarProducto = document.getElementById('btnAgregarProducto');
    const modalAgregar = document.getElementById('modalAgregar');
    if (btnAgregarProducto && modalAgregar) {
        btnAgregarProducto.addEventListener('click', function() {
            cargarCategoriasEnSelect();
            modalAgregar.classList.remove('hidden');
            modalAgregar.classList.add('flex');
            modalAgregar.style.display = 'flex';
            document.body.classList.add('modal-open');
        });
        // Cerrar modal agregar correctamente
        const btnCloseAdd = document.getElementById('closeAgregar');
        const btnCancelAdd = document.getElementById('btnCancelarAgregar');
        const closeAdd = ()=>{ modalAgregar.classList.add('hidden'); modalAgregar.classList.remove('flex'); modalAgregar.style.display = 'none'; document.body.classList.remove('modal-open'); document.documentElement.style.overflow=''; document.body.style.overflow=''; };
        if (btnCloseAdd) btnCloseAdd.addEventListener('click', closeAdd);
        if (btnCancelAdd) btnCancelAdd.addEventListener('click', closeAdd);
    }

    // Cargar categorías dinámicamente en el modal de editar producto
    function cargarCategoriasEnSelectEditar(valorSeleccionado = '') {
        fetch('/inventario/categoria/api/all')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const select = document.querySelector('#modalEditar select[name="categoria"]');
                    if (select) {
                        select.innerHTML = '<option value="">Seleccionar</option>';
                        data.data.forEach(cat => {
                            select.innerHTML += `<option value="${cat.nombre}"${cat.nombre === valorSeleccionado ? ' selected' : ''}>${cat.nombre}</option>`;
                        });
                    }
                }
            });
    }

    // Helpers para editar: cargar selects
    async function cargarPresentacionesEnSelectEditar(valorSeleccionado = '') {
        try {
            const res = await fetch('/inventario/presentacion/api');
            const data = await res.json();
            if (data.success) {
                const select = document.querySelector('#modalEditar select[name="presentacion"]');
                if (select) {
                    select.innerHTML = '<option value="">Seleccionar</option>';
                    data.data.forEach(p => {
                        select.innerHTML += `<option value="${p.nombre}"${p.nombre === valorSeleccionado ? ' selected' : ''}>${p.nombre}</option>`;
                    });
                }
            }
        } catch(e) { console.error(e); }
    }

    async function cargarProveedoresEditarSeguro(valorSeleccionado = '', nombreProveedor = '') {
        try {
            // Usar el mismo endpoint que Agregar (nombre = razon_social)
            const res = await fetch('/compras/proveedores/api');
            const data = await res.json();
            const select = document.getElementById('edit-proveedor');
            if (!select) return;
            // Preparar select
            select.innerHTML = '<option value="">Seleccionar</option>';
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                // Ordenar por razón social
                data.data.sort((a,b)=>((a.razon_social||'').localeCompare(b.razon_social||'')));
                data.data.forEach(pr => {
                    const display = pr.razon_social || pr.ruc || pr.nombre_comercial || `Proveedor #${pr.id}`;
                    const opt = document.createElement('option');
                    opt.value = pr.id;
                    opt.textContent = display;
                    if (String(pr.id) === String(valorSeleccionado)) opt.selected = true;
                    select.appendChild(opt);
                });
                // Si el proveedor del producto no está en la lista, cargarlo directo y seleccionarlo
                if (valorSeleccionado && !Array.from(select.options).some(o => String(o.value) === String(valorSeleccionado))) {
                    try {
                        const r = await fetch(`/api/compras/proveedor/${valorSeleccionado}`);
                        const d = await r.json();
                        if (d.success && d.data) {
                            const p = d.data;
                            const display = p.razon_social || p.ruc || p.nombre_comercial || `Proveedor #${p.id}`;
                            const opt = document.createElement('option');
                            opt.value = p.id;
                            opt.textContent = display;
                            opt.selected = true;
                            select.appendChild(opt);
                        }
                    } catch (_) {}
                }
                // Si NO hay id pero sí nombre (razón social), intentar seleccionar por texto
                if (!valorSeleccionado && nombreProveedor) {
                    const match = Array.from(select.options).find(o => (o.textContent||'').trim().toLowerCase() === nombreProveedor.trim().toLowerCase());
                    if (match) {
                        select.value = match.value;
                    }
                }
                // Forzar selección por si el browser no tomó el 'selected'
                if (valorSeleccionado || nombreProveedor) {
                    if (valorSeleccionado) select.value = String(valorSeleccionado);
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
            } else {
                select.innerHTML = '<option value="">No hay proveedores activos</option>';
            }
            select.disabled = false;
        } catch(e) {
            console.error(e);
            const select = document.getElementById('edit-proveedor');
            if (select) {
                select.innerHTML = '<option value="">Error al cargar proveedores</option>';
                select.disabled = false;
            }
        }
    }

    // Reintento seguro para fijar selección cuando otros scripts/tiempos de carga interfieren
    function forceSelectProveedorEditar(provId, provName, tries = 20) {
        const sel = document.getElementById('edit-proveedor');
        if (!sel) return;
        const trySet = () => {
            if (!sel.options || sel.options.length <= 1) {
                if (tries-- > 0) return setTimeout(trySet, 150);
                return;
            }
            let targetValue = null;
            if (provId) {
                const opt = Array.from(sel.options).find(o => String(o.value) === String(provId));
                if (opt) targetValue = opt.value;
            }
            if (!targetValue && provName) {
                const optByName = Array.from(sel.options).find(o => (o.textContent||'').trim().toLowerCase() === provName.trim().toLowerCase());
                if (optByName) targetValue = optByName.value;
            }
            if (targetValue) {
                sel.value = targetValue;
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            } else if (tries-- > 0) {
                setTimeout(trySet, 150);
            }
        };
        trySet();
    }

    // Mostrar el modal de editar y cargar categorías
    const modalEditar = document.getElementById('modalEditar');
    if (modalEditar) {
        window.abrirModalEditarProducto = async function(producto) {
            // ID oculto
            const campoId = document.getElementById('edit-producto-id');
            if (campoId) campoId.value = producto.id;

            // Inputs
            const campos = {
                'edit-nombre': producto.nombre,
                'edit-concentracion': producto.concentracion,
                'edit-marca': producto.marca,
                'edit-lote': producto.lote,
                'edit-codigo_barras': producto.codigo_barras,
                'edit-stock_actual': producto.stock_actual,
                'edit-stock_minimo': producto.stock_minimo,
                'edit-precio_compra': producto.precio_compra,
                'edit-precio_venta': producto.precio_venta,
                'edit-fecha_fabricacion': producto.fecha_fabricacion || '',
                'edit-fecha_vencimiento': producto.fecha_vencimiento || ''
            };
            Object.entries(campos).forEach(([id, val]) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; });

            // Selects - await all async operations
            await Promise.all([
                cargarCategoriasEnSelectEditar(producto.categoria),
                cargarPresentacionesEnSelectEditar(producto.presentacion)
            ]);
            await cargarProveedoresEditarSeguro(producto.proveedor_id, producto.proveedor);
            forceSelectProveedorEditar(producto.proveedor_id, producto.proveedor);

            // Imagen
            const prev = document.getElementById('edit-preview-container');
            const img = document.getElementById('edit-preview-image');
            if (prev && img) {
                prev.style.display = 'block';
                img.src = producto.imagen_url || '/assets/images/default-product.svg';
                img.onerror = function(){ this.src = '/assets/images/default-product.svg'; };
            }

            modalEditar.classList.remove('hidden');
            modalEditar.classList.add('flex');
            modalEditar.style.display = 'flex';
            document.body.classList.add('modal-open');

            // Asegurar selección del proveedor después de abrir (por si otros scripts rehacen el select)
            setTimeout(() => {
                const sel = document.getElementById('edit-proveedor');
                if (sel) {
                    if (producto.proveedor_id) {
                        sel.value = String(producto.proveedor_id);
                    } else if (producto.proveedor) {
                        const match = Array.from(sel.options).find(o => (o.textContent||'').trim().toLowerCase() === producto.proveedor.trim().toLowerCase());
                        if (match) sel.value = match.value;
                    }
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }, 50);
        };
        // Cerrar modal editar correctamente
        const btnCloseEdit = document.getElementById('closeEditar');
        const btnCancelEdit = document.getElementById('btnCancelarEditar');
        const closeEdit = ()=>{ modalEditar.classList.add('hidden'); modalEditar.classList.remove('flex'); modalEditar.style.display = 'none'; document.body.classList.remove('modal-open'); };
        if (btnCloseEdit) btnCloseEdit.addEventListener('click', closeEdit);
        if (btnCancelEdit) btnCancelEdit.addEventListener('click', closeEdit);
    }
});

function updateFilterCounter(visible, total, estado) {
    const estadoLabels = {
        'todos': 'Todos',
        'normal': 'Normal',
        'bajo_stock': 'Bajo stock',
        'por_vencer': 'Por vencer',
        'vencido': 'Vencido'
    };

    // Crear o actualizar el contador
    let counter = document.getElementById('filter-counter');
    if (!counter) {
        counter = document.createElement('div');
        counter.id = 'filter-counter';
        counter.className = 'text-sm text-gray-600 mt-2';
        const filterContainer = document.getElementById('filterEstado').parentNode;
        filterContainer.appendChild(counter);
    }
}