/**
 * PUNTO DE VENTA - Funcionalidad Original
 */

// Variables globales
let carritoProductos = [];
let metodoPagoSeleccionado = 'efectivo';
let searchTimeout;

// Configuración
const CONFIG = {
    API_BASE_URL: '/punto-venta',
    SEARCH_DELAY: 300,
    IGV_RATE: 0.18,
    CURRENCY: 'S/.',
    DNI_LENGTH: 8
};

/**
 * INICIALIZACIÓN
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Iniciando Punto de Venta...');
    initializeEventListeners();
    updateCarritoDisplay();
    updateTotales();
    checkBotonProcesarVenta();
    cargarEstadisticasHoy();
    // Removido el enfoque automático del campo de búsqueda
    console.log('✅ Punto de Venta iniciado correctamente');
});

function initializeEventListeners() {
    // Búsqueda con mejoras
    const buscarInput = document.getElementById('buscarProductos');
    const clearBtn = document.querySelector('.pos-search-clear');
    
    buscarInput.addEventListener('input', function(event) {
        handleProductSearch(event);
        
        // Mostrar/ocultar botón limpiar
        if (this.value.trim().length > 0) {
            clearBtn.style.display = 'block';
        } else {
            clearBtn.style.display = 'none';
            document.getElementById('resultadosCount').textContent = '0 productos encontrados';
        }
    });
    
    // Radio buttons de comprobante
    document.querySelectorAll('input[name="comprobante"]').forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('💡 Comprobante seleccionado:', this.value);
            checkBotonProcesarVenta();
        });
    });
    

    
    // Métodos de pago
    document.querySelectorAll('.pos-metodo-btn').forEach(btn => {
        btn.addEventListener('click', () => handleMetodoPagoChange(btn.dataset.metodo));
    });
    
    // Efectivo recibido
    document.getElementById('efectivoRecibido').addEventListener('input', calcularVuelto);
    document.getElementById('efectivoRecibido').addEventListener('keyup', calcularVuelto);
    document.getElementById('efectivoRecibido').addEventListener('change', calcularVuelto);
}

/**
 * BÚSQUEDA DE PRODUCTOS
 */
function handleProductSearch(event) {
    const query = event.target.value.trim();
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        document.getElementById('productosEncontrados').innerHTML = '';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        buscarProductos(query);
    }, CONFIG.SEARCH_DELAY);
}

async function buscarProductos(query) {
    try {
        const response = await fetch(`${CONFIG.API_BASE_URL}/buscar-productos?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success) {
            displayProductosEncontrados(data.productos);
        } else {
            showError('Error al buscar productos');
        }
    } catch (error) {
        console.error('Error de búsqueda:', error);
        showError('Error de conexión al buscar productos');
    }
}

function displayProductosEncontrados(productos) {
    const container = document.getElementById('productosEncontrados');
    
    if (productos.length === 0) {
        container.innerHTML = '<div class="pos-no-productos"><p>No se encontraron productos</p></div>';
        return;
    }
    
    const html = productos.map(producto => {
        // Estado simple
        let estadoClass = 'disponible';
        let estadoTexto = 'Disponible';
        
        if (producto.dias_para_vencer !== null && producto.dias_para_vencer < 0) {
            estadoClass = 'vencido';
            estadoTexto = 'Vencido';
        } else if (producto.dias_para_vencer !== null && producto.dias_para_vencer <= 30) {
            estadoClass = 'por-vencer';
            estadoTexto = 'Por vencer';
        } else if (producto.stock_actual <= producto.stock_minimo) {
            estadoClass = 'stock-bajo';
            estadoTexto = 'Stock bajo';
        }
        
        return `
            <div class="pos-producto-compacto-real" onclick="agregarProductoAlCarrito(${producto.id})" data-producto='${JSON.stringify(producto)}'>
                <div class="pos-producto-imagen-mini">
                    <img src="${normalizarImagenBusqueda(producto.imagen)}" alt="${producto.nombre}">
                    <span class="pos-estado-${estadoClass}">${estadoTexto.charAt(0)}</span>
                </div>
                <div class="pos-producto-info-mini">
                    <div class="pos-nombre-precio">
                        <span class="pos-nombre">${combinarNombreConcentracion(producto.nombre, producto.concentracion)}</span>
                        <span class="pos-precio">${CONFIG.CURRENCY} ${formatearPrecio(producto.precio_venta)}</span>
                    </div>
                    <div class="pos-detalles-mini">
                        <span class="pos-marca">${producto.marca}</span> | 
                        <span class="pos-ubicacion">${producto.ubicacion_almacen}</span> | 
                        <span class="pos-stock">Stock: ${producto.stock_actual}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = html;
}

// Normaliza rutas de imagen en búsqueda
function normalizarImagenBusqueda(ruta) {
    if (!ruta) return '/assets/images/default-product.png';
    return (!ruta.startsWith('http') && !ruta.startsWith('/')) ? ('/' + ruta) : ruta;
}

// Evita duplicar concentración cuando ya está en el nombre
function combinarNombreConcentracion(nombre, concentracion) {
    let n = nombre || '';
    const conc = concentracion || '';
    return (conc && !n.toLowerCase().includes(conc.toLowerCase())) ? `${n} ${conc}`.trim() : n;
}

/**
 * GESTIÓN DEL CARRITO
 */
function agregarProductoAlCarrito(productoId) {
    console.log('➕ Agregando producto al carrito:', productoId);
    
    // Buscar el elemento que contiene toda la información del producto
    const productoElement = document.querySelector(`[onclick="agregarProductoAlCarrito(${productoId})"]`);
    if (!productoElement) {
        console.error('❌ Elemento de producto no encontrado');
        return;
    }
    
    // Obtener datos completos del producto desde el atributo data
    let producto;
    try {
        producto = JSON.parse(productoElement.getAttribute('data-producto'));
    } catch (e) {
        console.error('❌ Error parsing datos del producto:', e);
        return;
    }
    
    // Verificar si el producto ya existe en el carrito
    const existingIndex = carritoProductos.findIndex(item => item.id === productoId);
    
    if (existingIndex >= 0) {
        // Verificar stock antes de aumentar cantidad
        const nuevaCantidad = carritoProductos[existingIndex].cantidad + 1;
        if (nuevaCantidad > producto.stock_actual) {
            showError(`Stock insuficiente. Solo hay ${producto.stock_actual} unidades disponibles.`);
            return;
        }
        
        carritoProductos[existingIndex].cantidad++;
        console.log('🔄 Producto existente, aumentando cantidad');
    } else {
        // Agregar nuevo producto con información completa
        carritoProductos.push({
            id: producto.id,
            nombre: producto.nombre,
            marca: producto.marca,
            concentracion: producto.concentracion,
            categoria: producto.categoria,
            presentacion: producto.presentacion,
            codigo_barras: producto.codigo_barras,
            precio_compra: producto.precio_compra,
            precio_venta: producto.precio_venta,
            stock_actual: producto.stock_actual,
            stock_minimo: producto.stock_minimo,
            ubicacion_almacen: producto.ubicacion_almacen,
            fecha_vencimiento: producto.fecha_vencimiento,
            dias_para_vencer: producto.dias_para_vencer,
            estado: producto.estado,
            imagen: producto.imagen,
            cantidad: 1,
            // Información adicional
            info_stock: producto.info_stock,
            info_vencimiento: producto.info_vencimiento
        });
        console.log('🆕 Nuevo producto agregado con información completa');
    }
    
    updateCarritoDisplay();
    updateTotales();
    checkBotonProcesarVenta();
    
    // Limpiar búsqueda
    document.getElementById('buscarProductos').value = '';
    document.getElementById('productosEncontrados').innerHTML = '';
    // Removido el enfoque automático del campo de búsqueda
    
    showSuccess(`${producto.nombre} agregado al carrito`);
}

function updateCarritoDisplay() {
    console.log('🔄 Actualizando display del carrito...');
    console.log('📦 Productos en carrito:', carritoProductos.length);
    console.log('📊 Carrito completo:', carritoProductos);
    
    const container = document.getElementById('carritoProductos');
    const contador = document.getElementById('contadorProductos');
    
    if (!container) {
        console.error('❌ Contenedor carritoProductos no encontrado');
        return;
    }
    
    if (!contador) {
        console.error('❌ Elemento contadorProductos no encontrado');
        return;
    }
    
    const totalItems = carritoProductos.reduce((sum, item) => sum + item.cantidad, 0);
    contador.textContent = `(${totalItems} producto${totalItems !== 1 ? 's' : ''})`;
    console.log('🔢 Total items en contador:', totalItems);
    
    if (carritoProductos.length === 0) {
        container.innerHTML = `
            <div class="pos-carrito-vacio">
                <p>No hay productos en el carrito</p>
                <span>Busca y agrega productos para comenzar la venta</span>
            </div>
        `;
        console.log('📭 Carrito vacío mostrado');
        return;
    }
    
    console.log('🏗️ Generando HTML para', carritoProductos.length, 'productos...');
    
    const html = carritoProductos.map((item, index) => {
        console.log('🎯 Procesando producto:', item.nombre, 'Index:', index);
        
        const subtotal = item.precio_venta * item.cantidad;
        
        // Estado simple
        let estadoClass = 'disponible';
        let estadoTexto = 'D';
        
        if (item.dias_para_vencer !== null && item.dias_para_vencer < 0) {
            estadoClass = 'vencido';
            estadoTexto = 'V';
        } else if (item.dias_para_vencer !== null && item.dias_para_vencer <= 30) {
            estadoClass = 'por-vencer';
            estadoTexto = 'P';
        } else if (item.stock_actual <= item.stock_minimo) {
            estadoClass = 'stock-bajo';
            estadoTexto = 'S';
        }
        
        const stockWarning = item.cantidad > item.stock_actual ? 
            '<div class="pos-warning">⚠️ Sin stock</div>' : '';
        
        const itemHtml = `
            <div class="pos-carrito-item-mejorado" data-index="${index}">
                <button class="pos-eliminar-esquina" onclick="eliminarProducto(${index})" title="Eliminar producto">×</button>
                
                <div class="pos-carrito-contenido-mejorado">
                    <div class="pos-imagen-estado-container">
                        <img src="${item.imagen || '/assets/images/default-product.png'}" alt="${item.nombre}" class="pos-imagen-carrito-mejorada">
                        <span class="pos-estado-${estadoClass}">${estadoTexto}</span>
                    </div>
                    
                    <div class="pos-info-producto-carrito">
                        <div class="pos-nombre-carrito-mejorado">${item.nombre || 'Producto sin nombre'}</div>
                        <div class="pos-detalles-carrito-mejorado">
                            <span class="pos-marca-carrito">${item.marca || 'Sin marca'}</span> • 
                            <span class="pos-ubicacion-carrito">${item.ubicacion_almacen || 'Sin ubicación'}</span> • 
                            <span class="pos-stock-carrito">Stock: ${item.stock_actual || 0}</span>
                        </div>
                        <div class="pos-precio-unitario-carrito">Precio: S/. ${formatearPrecio(item.precio_venta || 0)}</div>
                        ${stockWarning}
                    </div>
                    
                    <div class="pos-controles-cantidad-mejorados">
                        <div class="pos-cantidad-wrapper">
                            <button class="pos-btn-menos" onclick="cambiarCantidad(${index}, -1)">−</button>
                            <input type="number" class="pos-input-cantidad-mejorado" value="${item.cantidad}" min="1" max="${item.stock_actual}" onchange="setCantidad(${index}, this.value)">
                            <button class="pos-btn-mas" onclick="cambiarCantidad(${index}, 1)">+</button>
                        </div>
                        <div class="pos-subtotal-completo">
                            <span class="pos-subtotal-etiqueta">Subtotal:</span>
                            <span class="pos-subtotal-monto">S/. ${formatearPrecio(subtotal)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        console.log('📝 HTML generado para producto:', item.nombre);
        return itemHtml;
    }).join('');
    
    console.log('✨ HTML final generado, longitud:', html.length);
    console.log('🖼️ Insertando HTML en container...');
    
    container.innerHTML = html;
    
    // Verificar que se insertó correctamente
    const itemsInsertados = container.querySelectorAll('.pos-carrito-item-mejorado');
    console.log('✅ Items insertados en DOM:', itemsInsertados.length);
    
    if (itemsInsertados.length !== carritoProductos.length) {
        console.error('❌ Error: No coincide la cantidad de items insertados');
        console.error('Esperados:', carritoProductos.length, 'Insertados:', itemsInsertados.length);
    }
    
    console.log('✅ Carrito actualizado con', carritoProductos.length, 'productos');
}

function cambiarCantidad(index, delta) {
    console.log('🔢 Cambiando cantidad:', index, delta);
    
    if (carritoProductos[index]) {
        const nuevaCantidad = Math.max(1, carritoProductos[index].cantidad + delta);
        
        // Verificar stock antes de cambiar cantidad
        if (nuevaCantidad > carritoProductos[index].stock_actual) {
            showError(`Stock insuficiente. Solo hay ${carritoProductos[index].stock_actual} unidades disponibles de ${carritoProductos[index].nombre}.`);
            return;
        }
        
        carritoProductos[index].cantidad = nuevaCantidad;
        console.log('Nueva cantidad:', nuevaCantidad);
        
        updateCarritoDisplay();
        updateTotales();
        checkBotonProcesarVenta();
    }
}

function setCantidad(index, cantidad) {
    console.log('🔢 Estableciendo cantidad:', index, cantidad);
    
    const nuevaCantidad = parseInt(cantidad) || 1;
    
    if (carritoProductos[index]) {
        // Verificar stock antes de establecer cantidad
        if (nuevaCantidad > carritoProductos[index].stock_actual) {
            showError(`Stock insuficiente. Solo hay ${carritoProductos[index].stock_actual} unidades disponibles de ${carritoProductos[index].nombre}.`);
            // Restaurar cantidad anterior
            updateCarritoDisplay();
            return;
        }
        
        carritoProductos[index].cantidad = Math.max(1, nuevaCantidad);
        
        updateCarritoDisplay();
        updateTotales();
        checkBotonProcesarVenta();
    }
}

function eliminarProducto(index) {
    console.log('🗑️ Eliminando producto:', index);
    
    if (carritoProductos[index]) {
        const producto = carritoProductos[index];
        carritoProductos.splice(index, 1);
        
        updateCarritoDisplay();
        updateTotales();
        checkBotonProcesarVenta();
        
        showInfo(`${producto.nombre} eliminado del carrito`);
        console.log('✅ Producto eliminado correctamente');
    }
}

function limpiarCarrito() {
    console.log('🧹 Limpiando carrito...');
    
    if (carritoProductos.length === 0) {
        showInfo('El carrito ya está vacío');
        return;
    }
    
    if (confirm('¿Estás seguro de que quieres limpiar el carrito?')) {
        carritoProductos = [];
        updateCarritoDisplay();
        updateTotales();
        checkBotonProcesarVenta();
        showSuccess('Carrito limpiado exitosamente');
        console.log('✅ Carrito limpiado');
    }
}

/**
 * CÁLCULOS Y TOTALES
 */
function updateTotales() {
    console.log('💰 Actualizando totales...');
    
    const subtotal = carritoProductos.reduce((sum, item) => sum + (item.precio_venta * item.cantidad), 0);
    const igv = subtotal * CONFIG.IGV_RATE;
    const total = subtotal + igv;
    
    console.log('Subtotal:', subtotal, 'IGV:', igv, 'Total:', total);
    
    // Verificar que los elementos existan
    const subtotalElement = document.getElementById('subtotalVenta');
    const igvElement = document.getElementById('igvVenta');
    const totalElement = document.getElementById('totalVenta');
    
    if (subtotalElement) subtotalElement.textContent = `${CONFIG.CURRENCY} ${formatearPrecio(subtotal)}`;
    if (igvElement) igvElement.textContent = `${CONFIG.CURRENCY} ${formatearPrecio(igv)}`;
    if (totalElement) totalElement.textContent = `${CONFIG.CURRENCY} ${formatearPrecio(total)}`;
    
    calcularVuelto();
    console.log('✅ Totales actualizados');
}

function calcularVuelto() {
    console.log('💵 Calculando vuelto...');
    
    const vueltoElement = document.getElementById('vueltoCalculado');
    if (!vueltoElement) return;
    
    if (metodoPagoSeleccionado !== 'efectivo') {
        vueltoElement.textContent = `${CONFIG.CURRENCY} 0.00`;
        vueltoElement.style.color = '#6c757d';
        return;
    }
    
    const efectivoRecibido = parseFloat(document.getElementById('efectivoRecibido').value) || 0;
    const total = getTotalConIgv();
    const vuelto = Math.max(0, efectivoRecibido - total);
    
    vueltoElement.textContent = `${CONFIG.CURRENCY} ${formatearPrecio(vuelto)}`;
    
    // Cambiar color según el vuelto
    if (efectivoRecibido < total && efectivoRecibido > 0) {
        vueltoElement.style.color = '#dc3545'; // Rojo
    } else if (vuelto > 0) {
        vueltoElement.style.color = '#28a745'; // Verde
    } else {
        vueltoElement.style.color = '#6c757d'; // Gris
    }
    
    checkBotonProcesarVenta();
    console.log('✅ Vuelto calculado:', vuelto);
}

/**
 * GESTIÓN DEL CLIENTE
 */
async function consultarDni() {
    console.log('🔍 Consultando DNI...');
    
    const dni = document.getElementById('dniCliente').value.trim();
    
    if (dni.length !== CONFIG.DNI_LENGTH) {
        showError('El DNI debe tener 8 dígitos');
        return;
    }
    
    try {
        const response = await fetch(`${CONFIG.API_BASE_URL}/consultar-dni`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ dni: dni })
        });
        
        const data = await response.json();
        
        if (data.success && data.cliente) {
            clienteSeleccionado = data.cliente;
            document.getElementById('nombreCompleto').textContent = data.cliente.nombre_completo;
            document.getElementById('dniCompleto').textContent = `DNI: ${data.cliente.dni}`;
            
            // Ocultar fuente de la consulta (ya no se muestra)
            const fuenteElement = document.getElementById('fuenteConsulta');
            if (fuenteElement) { fuenteElement.textContent = ''; fuenteElement.style.display = 'none'; }
            
            document.getElementById('infoCliente').style.display = 'block';
            
            // Mostrar mensaje específico según el origen
            const mensaje = data.message || 'Cliente encontrado exitosamente';
            showSuccess(mensaje);
            
            console.log('✅ Cliente encontrado:', {
                id: data.cliente.id,
                dni: data.cliente.dni,
                nombre_completo: data.cliente.nombre_completo,
                nombres: data.cliente.nombres,
                apellido_paterno: data.cliente.apellido_paterno,
                apellido_materno: data.cliente.apellido_materno,
                fuente: data.message
            });
        } else {
            // Generar datos ficticios
            const nombres = ['Carlos', 'María', 'José', 'Ana', 'Luis', 'Carmen'];
            const apellidos = ['García', 'González', 'Rodríguez', 'Fernández'];
            
            const nombreFicticio = `${nombres[Math.floor(Math.random() * nombres.length)]} ${apellidos[Math.floor(Math.random() * apellidos.length)]} ${apellidos[Math.floor(Math.random() * apellidos.length)]}`;
            
            clienteSeleccionado = {
                id: null,
                dni: dni,
                nombre_completo: nombreFicticio
            };
            
            document.getElementById('nombreCompleto').textContent = nombreFicticio;
            document.getElementById('dniCompleto').textContent = `DNI: ${dni}`;
            document.getElementById('infoCliente').style.display = 'block';
            
            showSuccess('Cliente generado para pruebas');
        }
    } catch (error) {
        console.error('Error consultando DNI:', error);
        showError('Error consultando DNI');
    } finally {
        checkBotonProcesarVenta();
    }
}

// Restringir entrada del DNI a números
document.addEventListener('DOMContentLoaded', function() {
    const dniInput = document.getElementById('dniCliente');
    if (dniInput) {
        dniInput.addEventListener('input', function() {
            this.value = (this.value || '').replace(/\D/g, '').slice(0, CONFIG.DNI_LENGTH);
        });
    }
});

/**
 * MÉTODOS DE PAGO
 */
function handleMetodoPagoChange(metodo) {
    console.log('💳 Cambiando método de pago a:', metodo);
    
    metodoPagoSeleccionado = metodo;
    
    document.querySelectorAll('.pos-metodo-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.metodo === metodo);
    });
    
    document.getElementById('pagoEfectivo').style.display = metodo === 'efectivo' ? 'block' : 'none';
    
    if (metodo === 'efectivo') {
        document.getElementById('efectivoRecibido').focus();
    } else {
        document.getElementById('efectivoRecibido').value = '';
        calcularVuelto();
    }
    
    checkBotonProcesarVenta();
}

/**
 * PROCESAMIENTO DE VENTA
 */
function checkBotonProcesarVenta() {
    const tieneProductos = carritoProductos.length > 0;
    
    // Validar efectivo solo si el método es efectivo
    const efectivoValido = metodoPagoSeleccionado !== 'efectivo' || 
        parseFloat(document.getElementById('efectivoRecibido').value) >= getTotalConIgv();
    
    const habilitado = tieneProductos && efectivoValido;
    
    const btnProcesar = document.getElementById('btnProcesarVenta');
    if (btnProcesar) {
        btnProcesar.disabled = !habilitado;
        btnProcesar.style.opacity = habilitado ? '1' : '0.6';
        btnProcesar.style.cursor = habilitado ? 'pointer' : 'not-allowed';
        
        // Texto estándar para procesar venta
        btnProcesar.innerHTML = '<i class="ri-check-line"></i> Procesar Venta';
    }
    
    // Controlar visibilidad del botón Vista Previa
    checkBotonVistaPrevia();
}

function checkBotonVistaPrevia() {
    const btnVistaPrevia = document.getElementById('btnVistaPrevia');
    if (!btnVistaPrevia) return;
    
    const tieneProductos = carritoProductos.length > 0;
    const esBoletaElectronica = getComprobanteSeleccionado();
    
    const mostrarVistaPrevia = tieneProductos && esBoletaElectronica;
    
    btnVistaPrevia.style.display = mostrarVistaPrevia ? 'block' : 'none';
    
    console.log('Vista previa button:', {
        tieneProductos,
        esBoletaElectronica,
        mostrarVistaPrevia
    });
}

function mostrarVistaPrevia() {
    console.log('👁️ Mostrando vista previa de boleta...');
    
    if (carritoProductos.length === 0) {
        showError('No hay productos en el carrito');
        return;
    }
    
    if (!getComprobanteSeleccionado()) {
        showError('La vista previa solo está disponible para boletas electrónicas');
        return;
    }
    
    // Generar datos para la vista previa
    const subtotal = carritoProductos.reduce((sum, item) => sum + (item.precio_venta * item.cantidad), 0);
    const igv = subtotal * CONFIG.IGV_RATE;
    const total = subtotal + igv;
    
    // Crear ventana de vista previa
    const vistaPrevia = window.open('', '_blank', 'width=800,height=900,scrollbars=yes,resizable=yes');
    
    if (!vistaPrevia) {
        showError('No se pudo abrir la vista previa. Verifique que no esté bloqueando ventanas emergentes.');
        return;
    }
    
    // Generar HTML de la boleta
    const htmlBoleta = `
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Vista Previa - Boleta Electrónica</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    padding: 20px;
                }
                
                .boleta-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                
                .boleta-header {
                    background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                
                .empresa-logo {
                    font-size: 36px;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                
                .empresa-info {
                    font-size: 14px;
                    opacity: 0.9;
                    line-height: 1.5;
                }
                
                .boleta-numero {
                    background: rgba(255,255,255,0.1);
                    display: inline-block;
                    padding: 10px 20px;
                    border-radius: 25px;
                    margin-top: 15px;
                    font-weight: bold;
                }
                
                .boleta-body {
                    padding: 30px;
                }
                
                .seccion {
                    margin-bottom: 25px;
                }
                
                .seccion-titulo {
                    font-size: 16px;
                    font-weight: bold;
                    color: #2d3748;
                    margin-bottom: 12px;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #e2e8f0;
                }
                
                .cliente-info {
                    background: #f7fafc;
                    padding: 15px;
                    border-radius: 8px;
                    border-left: 4px solid #38a169;
                }
                
                .fecha-info {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    margin-bottom: 15px;
                }
                
                .fecha-item {
                    background: #edf2f7;
                    padding: 10px;
                    border-radius: 6px;
                    text-align: center;
                }
                
                .fecha-label {
                    font-size: 12px;
                    color: #718096;
                    margin-bottom: 5px;
                }
                
                .fecha-valor {
                    font-weight: bold;
                    color: #2d3748;
                }
                
                .productos-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                
                .productos-table th {
                    background: #2d3748;
                    color: white;
                    padding: 12px;
                    font-weight: 600;
                    text-align: left;
                }
                
                .productos-table td {
                    padding: 12px;
                    border-bottom: 1px solid #e2e8f0;
                }
                
                .productos-table tr:hover {
                    background: #f7fafc;
                }
                
                .totales-container {
                    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
                    padding: 20px;
                    border-radius: 8px;
                    border: 1px solid #e2e8f0;
                }
                
                .total-item {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                    font-size: 14px;
                }
                
                .total-final {
                    font-size: 18px;
                    font-weight: bold;
                    color: #e53e3e;
                    padding-top: 10px;
                    border-top: 2px solid #e2e8f0;
                    margin-top: 10px;
                }
                
                .metodo-pago {
                    background: #e6fffa;
                    color: #234e52;
                    padding: 15px;
                    border-radius: 8px;
                    border-left: 4px solid #38b2ac;
                    text-align: center;
                    font-weight: 600;
                }
                
                .boleta-footer {
                    background: #f7fafc;
                    padding: 20px;
                    text-align: center;
                    border-top: 1px solid #e2e8f0;
                    color: #718096;
                    font-size: 12px;
                }
                
                .acciones-buttons {
                    padding: 20px;
                    text-align: center;
                    background: #f7fafc;
                    border-top: 1px solid #e2e8f0;
                }
                
                .btn {
                    background: #e53e3e;
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 600;
                    margin: 0 5px;
                    transition: background 0.3s;
                }
                
                .btn:hover {
                    background: #c53030;
                }
                
                .btn-secondary {
                    background: #718096;
                }
                
                .btn-secondary:hover {
                    background: #4a5568;
                }
                
                @media print {
                    body { 
                        background: white; 
                        padding: 0; 
                    }
                    .acciones-buttons { 
                        display: none; 
                    }
                    .boleta-container { 
                        box-shadow: none; 
                        border-radius: 0; 
                    }
                }
                
                .preview-badge {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #f56565;
                    color: white;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: bold;
                    z-index: 1000;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                }
            </style>
        </head>
        <body>
            <div class="preview-badge">VISTA PREVIA</div>
            
            <div class="boleta-container">
                <div class="boleta-header">
                    <div class="empresa-logo">🏥 Botica San Antonio</div>
                    <div class="empresa-info">
                        RUC: 20123456789<br>
                        Av. Principal 123, Lima - Perú<br>
                        Teléfono: (01) 234-5678
                    </div>
                    <div class="boleta-numero">BOLETA ELECTRÓNICA<br>B001-00000001</div>
                </div>
                
                <div class="boleta-body">
                    <div class="fecha-info">
                        <div class="fecha-item">
                            <div class="fecha-label">Fecha de Emisión</div>
                            <div class="fecha-valor">${new Date().toLocaleDateString('es-PE')}</div>
                        </div>
                        <div class="fecha-item">
                            <div class="fecha-label">Hora de Emisión</div>
                            <div class="fecha-valor">${new Date().toLocaleTimeString('es-PE')}</div>
                        </div>
                    </div>
                    
                    <div class="seccion">
                        <div class="seccion-titulo">📋 Comprobante de Pago</div>
                        <div class="cliente-info">
                            <strong>Boleta Electrónica</strong><br>
                            Se generará con API SUNAT
                        </div>
                    </div>
                    
                    <div class="seccion">
                        <div class="seccion-titulo">🛒 Detalle de Productos</div>
                        <table class="productos-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th style="text-align: center;">Cant.</th>
                                    <th style="text-align: right;">P. Unit.</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${carritoProductos.map(item => `
                                    <tr>
                                        <td>${item.nombre}</td>
                                        <td style="text-align: center;">${item.cantidad}</td>
                                        <td style="text-align: right;">S/. ${formatearPrecio(item.precio_venta)}</td>
                                        <td style="text-align: right;">S/. ${formatearPrecio(item.precio_venta * item.cantidad)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="seccion">
                        <div class="seccion-titulo">💰 Resumen de Pago</div>
                        <div class="totales-container">
                            <div class="total-item">
                                <span>Subtotal:</span>
                                <span>S/. ${formatearPrecio(subtotal)}</span>
                            </div>
                            <div class="total-item">
                                <span>IGV (18%):</span>
                                <span>S/. ${formatearPrecio(igv)}</span>
                            </div>
                            <div class="total-item total-final">
                                <span>TOTAL A PAGAR:</span>
                                <span>S/. ${formatearPrecio(total)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="seccion">
                        <div class="metodo-pago">
                            💳 Método de Pago: ${metodoPagoSeleccionado.charAt(0).toUpperCase() + metodoPagoSeleccionado.slice(1)}
                        </div>
                    </div>
                </div>
                
                <div class="acciones-buttons">
                    <button class="btn" onclick="window.print()">🖨️ Imprimir</button>
                    <button class="btn btn-secondary" onclick="window.close()">❌ Cerrar</button>
                </div>
                
                <div class="boleta-footer">
                    <p>Esta es una vista previa de la boleta electrónica</p>
                    <p>Representación impresa de la Boleta Electrónica</p>
                    <p>Generado el ${new Date().toLocaleString('es-PE')}</p>
                </div>
            </div>
        </body>
        </html>
    `;
    
    vistaPrevia.document.write(htmlBoleta);
    vistaPrevia.document.close();
    
    console.log('✅ Vista previa generada exitosamente');
    showSuccess('Vista previa generada exitosamente');
}

function getTotalConIgv() {
    const subtotal = carritoProductos.reduce((sum, item) => sum + (item.precio_venta * item.cantidad), 0);
    return subtotal * (1 + CONFIG.IGV_RATE);
}

async function procesarVenta() {
    console.log('🛒 Procesando venta...');
    
    if (carritoProductos.length === 0) {
        showError('No hay productos en el carrito');
        return;
    }
    
    if (metodoPagoSeleccionado === 'efectivo') {
        const efectivoRecibido = parseFloat(document.getElementById('efectivoRecibido').value) || 0;
        const total = getTotalConIgv();
        
        if (efectivoRecibido < total) {
            showError('El efectivo recibido es insuficiente');
            return;
        }
    }
    
    if (!confirm(`¿Procesar venta por ${CONFIG.CURRENCY} ${formatearPrecio(getTotalConIgv())}?`)) {
        return;
    }
    
    const btnProcesar = document.getElementById('btnProcesarVenta');
    btnProcesar.disabled = true;
    btnProcesar.innerHTML = 'Procesando...';
    
    try {
        const esBoletaElectronica = getComprobanteSeleccionado();
        
        const ventaData = {
            productos: carritoProductos.map(item => ({
                id: item.id,
                cantidad: item.cantidad,
                precio: item.precio_venta
            })),
            metodo_pago: metodoPagoSeleccionado,
            tipo_comprobante: esBoletaElectronica ? 'boleta' : 'ticket',
            efectivo_recibido: metodoPagoSeleccionado === 'efectivo' ? parseFloat(document.getElementById('efectivoRecibido').value) : null
        };
        
        console.log('📋 Datos de venta a enviar:', ventaData);
        
        const response = await fetch(`${CONFIG.API_BASE_URL}/procesar-venta`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(ventaData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ Venta procesada exitosamente:', data.venta);
            
            // Mostrar información de la venta
            let mensaje = `Venta procesada exitosamente\n`;
            mensaje += `Número: ${data.venta.numero_venta}\n`;
            mensaje += `Total: S/. ${formatearPrecio(data.venta.total)}`;
            
            if (data.venta.vuelto > 0) {
                mensaje += `\nVuelto: S/. ${formatearPrecio(data.venta.vuelto)}`;
            }
            
            if (data.venta.numero_sunat) {
                mensaje += `\nBoleta: ${data.venta.numero_sunat}`;
            }
            
            alert(mensaje);
            
            // Limpiar todo el formulario
            carritoProductos = [];
            metodoPagoSeleccionado = 'efectivo';
            
            // Resetear formulario
            document.getElementById('comprobanteNo').checked = true;
            document.getElementById('comprobanteSi').checked = false;
            document.getElementById('efectivoRecibido').value = '';
            document.getElementById('buscarProductos').value = '';
            document.getElementById('productosEncontrados').innerHTML = '';
            
            // Resetear métodos de pago
            document.querySelectorAll('.pos-metodo-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector('[data-metodo="efectivo"]').classList.add('active');
            document.getElementById('pagoEfectivo').style.display = 'block';
            
            // Actualizar displays
            updateCarritoDisplay();
            updateTotales();
            checkBotonProcesarVenta();
            
            // Removido el enfoque automático del campo de búsqueda
            
            showSuccess('Venta registrada exitosamente');
            
        } else {
            showError(data.message || 'Error al procesar la venta');
        }
    } catch (error) {
        console.error('Error procesando venta:', error);
        showError('Error de conexión al procesar la venta');
    } finally {
        btnProcesar.disabled = false;
        btnProcesar.innerHTML = '<i class="ri-check-line"></i> Procesar Venta';
        checkBotonProcesarVenta();
    }
}

/**
 * UTILIDADES
 */
function formatearPrecio(precio) {
    return parseFloat(precio).toFixed(2);
}

function showSuccess(message) {
    const notification = document.createElement('div');
    notification.innerHTML = `
        <div style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 8px; z-index: 9999; max-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <strong>✅ ${message}</strong>
        </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            document.body.removeChild(notification);
        }
    }, 3000);
}

function showError(message) {
    const notification = document.createElement('div');
    notification.innerHTML = `
        <div style="position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 15px 20px; border-radius: 8px; z-index: 9999; max-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <strong>❌ ${message}</strong>
        </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            document.body.removeChild(notification);
        }
    }, 3000);
}

function showInfo(message) {
    const notification = document.createElement('div');
    notification.innerHTML = `
        <div style="position: fixed; top: 20px; right: 20px; background: #17a2b8; color: white; padding: 15px 20px; border-radius: 8px; z-index: 9999; max-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <strong>ℹ️ ${message}</strong>
        </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            document.body.removeChild(notification);
        }
    }, 3000);
}

/**
 * EXPONER FUNCIONES GLOBALES PARA ONCLICK
 * Estas funciones deben estar disponibles globalmente para los eventos onclick del HTML
 */
// Nuevas funciones mejoradas
async function cargarEstadisticasHoy() {
    try {
        const response = await fetch(`${CONFIG.API_BASE_URL}/estadisticas-hoy`);
        if (response.ok) {
            const data = await response.json();
            document.getElementById('ventasHoy').textContent = data.ventas || 0;
            document.getElementById('totalHoy').textContent = `${CONFIG.CURRENCY} ${formatearPrecio(data.total || 0)}`;
        }
    } catch (error) {
        console.log('No se pudieron cargar las estadísticas del día');
    }
}

function limpiarBusqueda() {
    document.getElementById('buscarProductos').value = '';
    document.getElementById('productosEncontrados').innerHTML = '';
    document.getElementById('resultadosCount').textContent = '0 productos encontrados';
    document.querySelector('.pos-search-clear').style.display = 'none';
    // Removido el enfoque automático del campo de búsqueda
}

// Función removida - ya no se usa sistema de clientes

function validarDni(dni) {
    if (!/^\d{8}$/.test(dni)) {
        return false;
    }
    
    const dnisFalsos = ['00000000', '11111111', '22222222', '33333333', '44444444', '55555555', '66666666', '77777777', '88888888', '99999999'];
    if (dnisFalsos.includes(dni)) {
        return false;
    }
    
    return true;
}

// Tecla F1 para enfocar búsqueda
document.addEventListener('keydown', function(event) {
    if (event.key === 'F1') {
        event.preventDefault();
        document.getElementById('buscarProductos').focus();
    }
});

// Exponer funciones globales
window.agregarProductoAlCarrito = agregarProductoAlCarrito;
window.cambiarCantidad = cambiarCantidad;
window.setCantidad = setCantidad;
window.eliminarProducto = eliminarProducto;
window.limpiarCarrito = limpiarCarrito;
window.limpiarBusqueda = limpiarBusqueda;
window.procesarVenta = procesarVenta;
window.mostrarVistaPrevia = mostrarVistaPrevia;

console.log('📦 JavaScript del Punto de Venta cargado completamente');

/**
 * OBTENER ESTADO DEL COMPROBANTE
 */
function getComprobanteSeleccionado() {
    const radioSeleccionado = document.querySelector('input[name="comprobante"]:checked');
    return radioSeleccionado && radioSeleccionado.value === 'si';
}