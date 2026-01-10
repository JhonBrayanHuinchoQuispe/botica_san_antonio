document.addEventListener("DOMContentLoaded", function() {
    console.log("=== Inicializando lista de productos...");
    
    // Verificar que SweetAlert2 está disponible
    if (typeof Swal === "undefined") {
        console.error("SweetAlert2 no está disponible");
        return;
    }
    
    // Verificar si FontAwesome está disponible
    function checkFontAwesome() {
        const testElement = document.createElement('i');
        testElement.className = 'fas fa-test';
        testElement.style.display = 'none';
        document.body.appendChild(testElement);
        
        const computed = window.getComputedStyle(testElement);
        const fontFamily = computed.getPropertyValue('font-family');
        
        document.body.removeChild(testElement);
        
        return fontFamily.toLowerCase().includes('font awesome');
    }
    
    const fontAwesomeAvailable = checkFontAwesome();
    console.log('FontAwesome disponible:', fontAwesomeAvailable);
    
    // ===============================================================
    // MODAL DE AGREGAR PRODUCTO
    // ===============================================================
    
    // Función para abrir modal de agregar producto
    function abrirModalAgregar() {
        console.log("Abriendo modal de agregar producto");
        const modal = document.getElementById("modalAgregar");
        if (modal) {
            modal.classList.remove("hidden");
            modal.style.display = "flex";
            
            // Limpiar formulario
            const form = document.getElementById("formAgregarProducto");
            if (form) {
                form.reset();
                
                // Limpiar todas las clases de validación
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.classList.remove('campo-invalido', 'campo-valido', 'border-red-500', 'bg-red-50', 'border-green-500', 'bg-green-50', 'border-yellow-500', 'bg-yellow-50');
                });
                
                // Limpiar mensajes de validación
                const validationMessages = form.querySelectorAll('.validation-message, .mensaje-error');
                validationMessages.forEach(msg => msg.remove());
                
                // Limpiar preview de imagen
                const previewContainer = document.getElementById("preview-container");
                const previewImage = document.getElementById("preview-image");
                if (previewContainer) {
                    previewContainer.classList.add("hidden");
                }
                if (previewImage) {
                    previewImage.src = "";
                }
                
                // Limpiar input de imagen
                const imagenInput = document.getElementById("imagen-input");
                if (imagenInput) {
                    imagenInput.value = "";
                }
                
                // Deshabilitar botón de guardar
                const btnGuardar = document.getElementById("btnGuardarProducto");
                if (btnGuardar) {
                    btnGuardar.disabled = true;
                    btnGuardar.classList.add('opacity-50', 'cursor-not-allowed');
                    btnGuardar.classList.remove('hover:bg-blue-700');
                }
            }
        }
    }
    
    // Event listener para botón agregar producto
    const btnAgregar = document.getElementById("btnAgregarProducto");
    if (btnAgregar) {
        btnAgregar.addEventListener("click", function(e) {
            e.preventDefault();
            abrirModalAgregar();
        });
    }
    
    // Event listeners para cerrar modal agregar
    const modalAgregar = document.getElementById("modalAgregar");
    if (modalAgregar) {
        // Botón cerrar (X)
        const btnClose = modalAgregar.querySelector(".modal-close");
        if (btnClose) {
            btnClose.addEventListener("click", function() {
                modalAgregar.classList.add("hidden");
                modalAgregar.style.display = "none";
            });
        }
        
        // Botón cancelar
        const btnCancel = modalAgregar.querySelector(".btn-cancel");
        if (btnCancel) {
            btnCancel.addEventListener("click", function() {
                modalAgregar.classList.add("hidden");
                modalAgregar.style.display = "none";
            });
        }
        
        // Click fuera del modal
        modalAgregar.addEventListener("click", function(e) {
            if (e.target === this) {
                modalAgregar.classList.add("hidden");
                modalAgregar.style.display = "none";
            }
        });
    }

    // ===============================================================
    // MODAL DE EDITAR PRODUCTO
    // ===============================================================
    
    // Función para abrir modal de edición
    function abrirModalEdicion(productId) {
        console.log('Abriendo modal de edición para producto:', productId);
        
        // Obtener datos del producto vía AJAX
        fetch(`/inventario/producto/${productId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    mostrarModalEdicion(data.data);
                } else {
                    throw new Error(data.message || 'Error al cargar los datos del producto');
                }
            })
            .catch(error => {
                console.error('Error al cargar producto:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar la información del producto para editar.',
                    confirmButtonText: 'Entendido'
                });
            });
    }
    
    // Función para mostrar el modal de edición con los datos usando el modal original
    function mostrarModalEdicion(producto) {
        console.log('=== Mostrando modal de edición con datos:', producto);
        
        const modalEditar = document.getElementById('modalEditar');
        if (!modalEditar) {
            console.error('Modal de editar no encontrado');
            return;
        }
        
        // Llenar el campo ID oculto
        const campoId = document.getElementById('edit-producto-id');
        if (campoId) {
            campoId.value = producto.id;
        }
        
        // Llenar los campos del formulario
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
        
        // Llenar campos
        for (const [fieldId, value] of Object.entries(campos)) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = value || '';
            }
        }
        
        // Llenar selects
        cargarCategoriasYPresentaciones(producto.categoria, producto.presentacion);
        
        // Cargar y seleccionar proveedor
        cargarProveedores(producto.proveedor_id);
        
        // Mostrar imagen actual si existe
        const previewContainer = document.getElementById('edit-preview-container');
        const previewImage = document.getElementById('edit-preview-image');
        if (previewContainer && previewImage) {
            if (producto.imagen_url) {
                // Usar la URL completa que viene del accessor del modelo
                previewImage.src = producto.imagen_url;
                previewContainer.style.display = 'block';
                previewContainer.classList.remove('hidden');
            } else {
                previewImage.src = '/assets/images/default-product.svg';
                previewContainer.style.display = 'block';
                previewContainer.classList.remove('hidden');
            }
            
            // Agregar fallback para errores de carga
            previewImage.onerror = function() {
                this.src = '/assets/images/default-product.svg';
            };
        }
        
        // Guardar el ID del producto para la actualización
        modalEditar.setAttribute('data-product-id', producto.id);
        
        // Mostrar el modal
        modalEditar.classList.remove('hidden');
        modalEditar.style.display = 'flex';
    }
    
    // Event listeners para cerrar modal editar
    const modalEditar = document.getElementById('modalEditar');
    if (modalEditar) {
        // Botón cerrar (X)
        const btnClose = modalEditar.querySelector('.modal-close');
        if (btnClose) {
            btnClose.addEventListener('click', function() {
                modalEditar.classList.add('hidden');
                modalEditar.style.display = 'none';
            });
        }
        
        // Botón cancelar
        const btnCancel = modalEditar.querySelector('.btn-cancel-edit');
        if (btnCancel) {
            btnCancel.addEventListener('click', function() {
                modalEditar.classList.add('hidden');
                modalEditar.style.display = 'none';
            });
        }
        
        // Click fuera del modal
        modalEditar.addEventListener('click', function(e) {
            if (e.target === this) {
                modalEditar.classList.add('hidden');
                modalEditar.style.display = 'none';
            }
        });
    }
    
    // ===============================================================
    // MODAL DE VER DETALLES
    // ===============================================================
    
    // Función para mostrar modal de detalles
    function abrirModalDetalles(productId) {
        console.log('=== Abriendo modal de detalles para producto:', productId);
        
        // Obtener datos del producto vía AJAX
        fetch(`/inventario/producto/${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    mostrarModalDetalles(data.data);
                } else {
                    throw new Error(data.message || 'Error al cargar los datos del producto');
                }
            })
            .catch(error => {
                console.error('Error al cargar producto:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cargar la información del producto.',
                    confirmButtonText: 'Entendido'
                });
            });
    }

    // Función para mostrar el modal con los datos usando el modal HTML existente
    function mostrarModalDetalles(producto) {
        console.log('=== Mostrando modal de detalles con datos:', producto);
        
        const modal = document.getElementById('modalDetalles');
        if (!modal) {
            console.error('Modal de detalles no encontrado');
            return;
        }
        
        // Llenar los datos en el modal - mapeo correcto según los IDs del HTML
        const elementos = {
            'modal-id': producto.id || 'N/A',
            'modal-nombre': producto.nombre || 'N/A',
            'modal-concentracion': producto.concentracion || 'N/A',
            'modal-marca': producto.marca || 'N/A',
            'modal-lote': producto.lote || 'N/A',
            'modal-codigo-barras': producto.codigo_barras || 'N/A',
            'modal-stock': producto.stock_actual || '0',
            'modal-stock-min': producto.stock_minimo || '0',
            'modal-precio-compra': `S/ ${parseFloat(producto.precio_compra || 0).toFixed(2)}`,
            'modal-precio-venta': `S/ ${parseFloat(producto.precio_venta || 0).toFixed(2)}`,
            'modal-fecha-fab': producto.fecha_fabricacion || 'N/A',
            'modal-fecha-ven': producto.fecha_vencimiento || 'N/A',
            'modal-categoria': producto.categoria || 'N/A',
            'modal-presentacion': producto.presentacion || 'N/A',
            'modal-proveedor': producto.proveedor || 'N/A',
            'modal-ubicacion': producto.ubicacion || 'Sin ubicación'
        };
        
        // Llenar cada elemento
        for (const [elementId, value] of Object.entries(elementos)) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = value;
            }
        }
        
        // Mostrar imagen del producto
        const imagenProducto = document.getElementById('modal-imagen');
        if (imagenProducto) {
            if (producto.imagen_url) {
                imagenProducto.src = producto.imagen_url;
                imagenProducto.onerror = function() {
                    this.src = '/assets/images/default-product.svg';
                };
            } else {
                imagenProducto.src = '/assets/images/default-product.svg';
            }
        }
        
        // Mostrar estado del producto con colores
        const estadoElement = document.getElementById('modal-estado');
        if (estadoElement) {
            const estado = determinarEstadoProducto(producto);
            estadoElement.textContent = estado.texto;
            estadoElement.className = `estado-badge ${estado.clase}`;
        }
        
        // Mostrar el modal
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        try { document.documentElement.style.overflow='hidden'; document.body.style.overflow='hidden'; document.body.classList.add('modal-open'); } catch(e){}
    }
    
    // Event listeners para cerrar modal detalles
    const modalDetalles = document.getElementById('modalDetalles');
    if (modalDetalles) {
        // Botón cerrar (X)
        const btnClose = modalDetalles.querySelector('.modal-close');
        if (btnClose) {
            btnClose.addEventListener('click', function() {
                modalDetalles.classList.add('hidden');
                modalDetalles.style.display = 'none';
                try { document.documentElement.style.overflow=''; document.body.style.overflow=''; document.body.classList.remove('modal-open'); } catch(e){}
            });
        }
        
        // Click fuera del modal
        modalDetalles.addEventListener('click', function(e) {
            if (e.target === this) {
                modalDetalles.classList.add('hidden');
                modalDetalles.style.display = 'none';
                try { document.documentElement.style.overflow=''; document.body.style.overflow=''; document.body.classList.remove('modal-open'); } catch(e){}
            }
        });
    }
    
    // ===============================================================
    // FUNCIONES DE UTILIDAD
    // ===============================================================
    
    // Función para formatear fechas
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toISOString().split('T')[0];
    }
    
    // Función para determinar el estado del producto
    function determinarEstadoProducto(producto) {
        const hoy = new Date();
        const fechaVencimiento = new Date(producto.fecha_vencimiento);
        const diasParaVencer = Math.ceil((fechaVencimiento - hoy) / (1000 * 60 * 60 * 24));
        
        if (fechaVencimiento < hoy) {
            return { texto: 'Vencido', clase: 'estado-vencido' };
        } else if (diasParaVencer <= 30) {
            return { texto: 'Por vencer', clase: 'estado-por-vencer' };
        } else if (producto.stock_actual <= producto.stock_minimo) {
            return { texto: 'Bajo stock', clase: 'estado-bajo-stock' };
        } else {
            return { texto: 'Normal', clase: 'estado-normal' };
        }
    }
    
    // Función para cargar categorías y presentaciones
    function cargarCategoriasYPresentaciones(categoriaSeleccionada = null, presentacionSeleccionada = null) {
        // Cargar categorías
        fetch('/inventario/categoria/api/all')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const selectCategoria = document.getElementById('edit-categoria');
                    if (selectCategoria) {
                        selectCategoria.innerHTML = '<option value="">Seleccionar</option>';
                        data.data.forEach(categoria => {
                            const option = document.createElement('option');
                            option.value = categoria.nombre;
                            option.textContent = categoria.nombre;
                            if (categoriaSeleccionada && categoria.nombre === categoriaSeleccionada) {
                                option.selected = true;
                            }
                            selectCategoria.appendChild(option);
                        });
                    }
                }
            })
            .catch(error => console.error('Error al cargar categorías:', error));
        
        // Cargar presentaciones
        fetch('/inventario/presentacion/api')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const selectPresentacion = document.getElementById('edit-presentacion');
                    if (selectPresentacion) {
                        selectPresentacion.innerHTML = '<option value="">Seleccionar</option>';
                        data.data.forEach(presentacion => {
                            const option = document.createElement('option');
                            option.value = presentacion.nombre;
                            option.textContent = presentacion.nombre;
                            if (presentacionSeleccionada && presentacion.nombre === presentacionSeleccionada) {
                                option.selected = true;
                            }
                            selectPresentacion.appendChild(option);
                        });
                    }
                }
            })
            .catch(error => console.error('Error al cargar presentaciones:', error));
    }

    // Función para cargar proveedores
    function cargarProveedores(proveedorSeleccionado = null) {
        fetch('/api/compras/buscar-proveedores')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const selectProveedor = document.getElementById('edit-proveedor');
                    if (selectProveedor) {
                        selectProveedor.innerHTML = '<option value="">Seleccionar</option>';
                        data.data.forEach(proveedor => {
                            const option = document.createElement('option');
                            option.value = proveedor.id;
                            // La API devuelve 'razon_social as nombre', usar nombre o razon_social como fallback
                            const nombreProveedor = proveedor.nombre || proveedor.razon_social || proveedor.nombre_comercial || `Proveedor #${proveedor.id}`;
                            option.textContent = nombreProveedor;
                            if (proveedorSeleccionado && proveedor.id == proveedorSeleccionado) {
                                option.selected = true;
                            }
                            selectProveedor.appendChild(option);
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error al cargar proveedores:', error);
                // Mostrar mensaje de error al usuario
                const selectProveedor = document.getElementById('edit-proveedor');
                if (selectProveedor) {
                    selectProveedor.innerHTML = '<option value="">Error al cargar proveedores</option>';
                }
            });
    }
    
    // ===============================================================
    // MANEJO DEL FORMULARIO DE AGREGAR PRODUCTO
    // ===============================================================
    
    // Event listener para el formulario de agregar producto
    const formAgregarProducto = document.getElementById('formAgregarProducto');
    if (formAgregarProducto) {
        formAgregarProducto.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarNuevoProducto();
        });
    }
    
    // ===============================================================
    // MANEJO DEL FORMULARIO DE EDICIÓN
    // ===============================================================
    
    // Event listener para el formulario de edición
    const formEditarProducto = document.getElementById('formEditarProducto');
    if (formEditarProducto) {
        formEditarProducto.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarEdicionProducto();
        });
    }
    
    // Event listener para el input de imagen de edición
    const editImagenInput = document.getElementById('edit-imagen-input');
    if (editImagenInput) {
        editImagenInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewImage = document.getElementById('edit-preview-image');
                    if (previewImage) {
                        previewImage.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Event listener para el input de imagen de agregar producto
    const imagenInput = document.getElementById('imagen-input');
    if (imagenInput) {
        imagenInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.getElementById('preview-container');
            const previewImage = document.getElementById('preview-image');
            
            if (file && previewContainer && previewImage) {
                // Validar que sea una imagen
                if (!file.type.match('image.*')) {
                    alert('Por favor selecciona una imagen válida (JPG, PNG, GIF)');
                    this.value = '';
                    previewContainer.classList.add('hidden');
                    return;
                }
                
                // Validar tamaño (máximo 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('La imagen debe ser menor a 2MB');
                    this.value = '';
                    previewContainer.classList.add('hidden');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else if (previewContainer) {
                // Si no hay archivo, ocultar preview
                previewContainer.classList.add('hidden');
            }
        });
    }
    
    // Variable para prevenir envíos múltiples
    let isSubmitting = false;
    
    // Función para guardar un nuevo producto
    async function guardarNuevoProducto() {
        // Prevenir envíos múltiples
        if (isSubmitting) {
            console.log('⚠️ Ya se está procesando una solicitud...');
            return;
        }
        
        isSubmitting = true;
        console.log('💾 Guardando nuevo producto...');
        
        try {
            // Validar formulario antes de enviar
            if (window.validacionesTiempoReal) {
            console.log('🔍 Validando formulario...');
            const isValid = await window.validacionesTiempoReal.validateForm('formAgregarProducto');
            if (!isValid) {
                console.log('❌ Validación fallida');
                Swal.fire({
                    icon: 'warning',
                    title: 'Errores de validación',
                    text: 'Por favor corrige los errores marcados en el formulario antes de continuar.',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            console.log('✅ Validación exitosa');
            }
            
            console.log('📝 Creando FormData...');
            const formData = new FormData(formAgregarProducto);
            
            // Log de los datos que se van a enviar
            console.log('📋 Datos del formulario:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }
            
            // Verificar token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            console.log('🔐 Token CSRF element:', csrfToken);
            console.log('🔐 Token CSRF value:', csrfToken ? csrfToken.content : 'NO ENCONTRADO');
            
            if (!csrfToken || !csrfToken.content) {
                throw new Error('Token CSRF no encontrado. Recarga la página e intenta nuevamente.');
            }
            
            console.log('🌐 Enviando petición al servidor...');
            const response = await fetch('/inventario/producto/guardar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.content,
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            
            console.log('📡 Respuesta recibida:', response.status, response.statusText);
            
            // Verificar si la respuesta es exitosa
            if (!response.ok) {
                // Intentar obtener el error del servidor
                let errorMessage = `Error HTTP: ${response.status} - ${response.statusText}`;
                try {
                    const errorData = await response.json();
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                    if (errorData.errors) {
                        // Errores de validación de Laravel
                        const validationErrors = Object.values(errorData.errors).flat();
                        errorMessage = validationErrors.join(', ');
                    }
                } catch (e) {
                    // Si no se puede parsear como JSON, usar el mensaje HTTP
                    console.error('No se pudo parsear el error como JSON:', e);
                }
                throw new Error(errorMessage);
            }
            
            // Verificar si la respuesta es JSON
            console.log('🔍 Verificando tipo de contenido...');
            const contentType = response.headers.get('content-type');
            console.log('📄 Content-Type:', contentType);
            
            if (!contentType || !contentType.includes('application/json')) {
                console.log('❌ Respuesta no es JSON, obteniendo texto...');
                const textResponse = await response.text();
                console.error('Respuesta no es JSON:', textResponse);
                throw new Error('El servidor devolvió una respuesta no válida. Verifica que estés autenticado.');
            }
            
            
            console.log('📥 Parseando respuesta JSON...');
            const data = await response.json();
            console.log('📊 Datos recibidos:', data);
            
            if (data.success) {
                console.log('✅ Producto guardado exitosamente');
                // Disparar evento de creación de producto
                const evento = new CustomEvent('productoActualizado', {
                    detail: {
                        tipo: 'nuevo_producto',
                        producto_id: data.producto_id || null,
                        timestamp: Date.now()
                    }
                });
                window.dispatchEvent(evento);
                
                // También usar localStorage para comunicación entre ventanas/pestañas
                localStorage.setItem('producto_actualizado', JSON.stringify({
                    tipo: 'nuevo_producto',
                    producto_id: data.producto_id || null,
                    timestamp: Date.now()
                }));
                
                // Cerrar modal
                const modalAgregar = document.getElementById('modalAgregar');
                if (modalAgregar) {
                    modalAgregar.classList.add('hidden');
                    modalAgregar.style.display = 'none';
                }
                
                
                // Mostrar SweetAlert de éxito
                Swal.fire({
                    icon: 'success',
                    title: '¡Producto creado!',
                    text: 'El producto se ha guardado correctamente.',
                    confirmButtonText: 'Entendido',
                    timer: 3000,
                    timerProgressBar: true
                });
                
                // Actualizar dinámicamente la tabla de productos
                if (typeof loadProducts === 'function') {
                    loadProducts();
                } else {
                    actualizarTablaProductosDespuesCreacion(data.producto);
                }
            } else {
                throw new Error(data.message || 'Error al crear el producto');
            }
        } catch (error) {
            console.error('Error al guardar producto:', error);
            
            // Intentar obtener más detalles del error
            let errorMessage = 'No se pudo crear el producto. Inténtalo de nuevo.';
            let errorDetails = '';
            
            if (error.message) {
                errorMessage = error.message;
            }
            
            // Si el error contiene información de validación
            if (error.message && error.message.includes('validation')) {
                errorMessage = 'Error de validación en los datos del formulario';
                errorDetails = 'Verifica que todos los campos estén correctamente completados.';
            }
            
            // Si es un error HTTP específico
            if (error.message && error.message.includes('HTTP: 500')) {
                errorMessage = 'Error interno del servidor';
                errorDetails = 'Revisa los logs del servidor para más información.';
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error al guardar',
                text: errorMessage,
                footer: errorDetails,
                confirmButtonText: 'Entendido',
                showCancelButton: true,
                cancelButtonText: 'Ver detalles en consola'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    console.log('Detalles completos del error:', error);
                }
            });
        } finally {
            // Resetear la variable para permitir nuevos envíos
            isSubmitting = false;
            console.log('🔄 Reseteo de estado de envío completado');
        }
    }
    
    // Función para guardar la edición del producto
    async function guardarEdicionProducto() {
        console.log('💾 Guardando edición de producto...');
        
        // Validar formulario antes de enviar
        if (window.validacionesTiempoReal) {
            const isValid = await window.validacionesTiempoReal.validateForm('formEditarProducto');
            if (!isValid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Errores de validación',
                    text: 'Por favor corrige los errores marcados en el formulario antes de continuar.',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
        }
        
        const formData = new FormData(formEditarProducto);
        const productId = document.getElementById('edit-producto-id').value;
        
        try {
            const response = await fetch(`/inventario/producto/actualizar/${productId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-HTTP-Method-Override': 'PUT',
                    'Accept': 'application/json'
                },
                body: formData
            });
            
            // Verificar si la respuesta es exitosa
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
            }
            
            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                console.error('Respuesta no es JSON:', textResponse);
                throw new Error('El servidor devolvió una respuesta no válida. Verifica que estés autenticado.');
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Disparar evento de actualización de producto
                const evento = new CustomEvent('productoActualizado', {
                    detail: {
                        tipo: 'edicion_producto',
                        producto_id: productId,
                        timestamp: Date.now()
                    }
                });
                window.dispatchEvent(evento);
                
                // También usar localStorage para comunicación entre ventanas/pestañas
                localStorage.setItem('producto_actualizado', JSON.stringify({
                    tipo: 'edicion_producto',
                    producto_id: productId,
                    timestamp: Date.now()
                }));
                
                // Cerrar modal
                const modalEditar = document.getElementById('modalEditar');
                if (modalEditar) {
                    modalEditar.classList.add('hidden');
                    modalEditar.style.display = 'none';
                }
                
                // Mostrar SweetAlert de éxito
                Swal.fire({
                    icon: 'success',
                    title: '¡Producto actualizado!',
                    text: 'Los cambios se han guardado correctamente.',
                    confirmButtonText: 'Entendido',
                    timer: 3000,
                    timerProgressBar: true
                });
                
                // Actualizar dinámicamente la tabla de productos
                if (typeof loadProducts === 'function') {
                    loadProducts();
                } else {
                    actualizarTablaProductosDespuesEdicion(data.producto);
                }
            } else {
                throw new Error(data.message || 'Error al actualizar el producto');
            }
        } catch (error) {
            console.error('Error al guardar producto:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error al guardar',
                text: error.message || 'No se pudo actualizar el producto. Inténtalo de nuevo.',
                confirmButtonText: 'Entendido'
            });
        }
    }
    
    // ===============================================================
    // EVENT LISTENERS GLOBALES
    // ===============================================================
    
    // Hacer las funciones globales para que puedan ser llamadas desde HTML
    window.abrirModalEdicion = abrirModalEdicion;
    window.abrirModalDetalles = abrirModalDetalles;
    window.abrirModalAgregar = abrirModalAgregar;
    
    console.log("=== Lista de productos inicializada correctamente");
});

/* ==============================================
   FUNCIONES AUXILIARES PARA ACTUALIZACIÓN DINÁMICA
   ============================================== */

/**
 * Actualizar tabla después de crear un producto
 */
function actualizarTablaProductosDespuesCreacion(producto) {
    const tabla = document.querySelector('#productos-table tbody');
    if (!tabla) return;
    
    // Crear nueva fila
    const nuevaFila = document.createElement('tr');
    nuevaFila.innerHTML = `
        <td>${producto.id}</td>
        <td>
            <div class="d-flex align-items-center">
                <img src="${producto.imagen || '/assets/images/default-product.png'}" 
                     alt="${producto.nombre}" class="product-thumb me-2">
                <div>
                    <strong>${producto.nombre}</strong>
                    <br><small class="text-muted">${producto.codigo_barras || 'Sin código'}</small>
                </div>
            </div>
        </td>
        <td>${producto.categoria?.nombre || 'Sin categoría'}</td>
        <td>${producto.presentacion?.nombre || 'Sin presentación'}</td>
        <td>S/ ${parseFloat(producto.precio_venta || 0).toFixed(2)}</td>
        <td>
            <span class="badge ${producto.stock > 10 ? 'bg-success' : producto.stock > 0 ? 'bg-warning' : 'bg-danger'}">
                ${producto.stock || 0}
            </span>
        </td>
        <td>
            <span class="badge ${producto.estado === 'activo' ? 'bg-success' : 'bg-secondary'}">
                ${producto.estado === 'activo' ? 'Activo' : 'Inactivo'}
            </span>
        </td>
        <td>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-info" onclick="abrirModalDetalles(${producto.id})" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="abrirModalEdicion(${producto.id})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        </td>
    `;
    
    // Agregar animación de entrada
    nuevaFila.style.opacity = '0';
    nuevaFila.style.transform = 'translateY(-10px)';
    tabla.insertBefore(nuevaFila, tabla.firstChild);
    
    // Animar entrada
    setTimeout(() => {
        nuevaFila.style.transition = 'all 0.3s ease';
        nuevaFila.style.opacity = '1';
        nuevaFila.style.transform = 'translateY(0)';
    }, 100);
    
    // Actualizar contadores si existen
    actualizarContadoresProductos();
    
    console.log('✅ Producto agregado dinámicamente a la tabla');
}

/**
 * Actualizar tabla después de editar un producto
 */
function actualizarTablaProductosDespuesEdicion(producto) {
    const fila = document.querySelector(`#productos-table tbody tr[data-id="${producto.id}"]`);
    if (!fila) {
        // Si no encontramos la fila por data-id, buscar por el ID en la primera celda
        const filas = document.querySelectorAll('#productos-table tbody tr');
        for (let f of filas) {
            if (f.cells[0] && f.cells[0].textContent.trim() === producto.id.toString()) {
                actualizarFilaProducto(f, producto);
                return;
            }
        }
        console.warn('No se encontró la fila del producto para actualizar');
        return;
    }
    
    actualizarFilaProducto(fila, producto);
}

/**
 * Actualizar una fila específica de producto
 */
function actualizarFilaProducto(fila, producto) {
    // Actualizar contenido de las celdas
    fila.cells[1].innerHTML = `
        <div class="d-flex align-items-center">
            <img src="${producto.imagen || '/assets/images/default-product.png'}" 
                 alt="${producto.nombre}" class="product-thumb me-2">
            <div>
                <strong>${producto.nombre}</strong>
                <br><small class="text-muted">${producto.codigo_barras || 'Sin código'}</small>
            </div>
        </div>
    `;
    fila.cells[2].textContent = producto.categoria?.nombre || 'Sin categoría';
    fila.cells[3].textContent = producto.presentacion?.nombre || 'Sin presentación';
    fila.cells[4].textContent = `S/ ${parseFloat(producto.precio_venta || 0).toFixed(2)}`;
    fila.cells[5].innerHTML = `
        <span class="badge ${producto.stock > 10 ? 'bg-success' : producto.stock > 0 ? 'bg-warning' : 'bg-danger'}">
            ${producto.stock || 0}
        </span>
    `;
    fila.cells[6].innerHTML = `
        <span class="badge ${producto.estado === 'activo' ? 'bg-success' : 'bg-secondary'}">
            ${producto.estado === 'activo' ? 'Activo' : 'Inactivo'}
        </span>
    `;
    
    // Agregar animación de actualización
    fila.style.backgroundColor = '#e3f2fd';
    setTimeout(() => {
        fila.style.transition = 'background-color 0.5s ease';
        fila.style.backgroundColor = '';
    }, 100);
    
    console.log('✅ Producto actualizado dinámicamente en la tabla');
}

/**
 * Actualizar contadores de productos
 */
function actualizarContadoresProductos() {
    const totalProductos = document.querySelectorAll('#productos-table tbody tr').length;
    
    // Actualizar contador en el título si existe
    const tituloContador = document.querySelector('.productos-count');
    if (tituloContador) {
        tituloContador.textContent = totalProductos;
    }
    
    // Actualizar estadísticas si existen
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        if (card.textContent.includes('Total Productos') || card.textContent.includes('Productos')) {
            const valueElement = card.querySelector('.stat-value');
            if (valueElement) {
                valueElement.textContent = totalProductos;
            }
        }
    });
}