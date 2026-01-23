/**
 * SCRIPT DE DEPURACIÓN PARA EL PUNTO DE VENTA
 * Verifica que todos los elementos y funciones estén funcionando
 */

console.log('🔍 INICIANDO DEBUG DEL PUNTO DE VENTA');

// Función para verificar elementos DOM
function verificarElementosDOM() {
    console.log('📋 Verificando elementos DOM...');
    
    const elementos = {
        searchInput: document.getElementById('buscarProductos'),
        searchLoader: document.getElementById('searchLoader'),
        productosEncontrados: document.getElementById('productosEncontrados'),
        carritoProductos: document.getElementById('carritoProductos'),
        contadorProductos: document.getElementById('contadorProductos'),
        subtotalVenta: document.getElementById('subtotalVenta'),
        igvVenta: document.getElementById('igvVenta'),
        totalVenta: document.getElementById('totalVenta'),
        boletaElectronica: document.getElementById('boletaElectronica'),
        datosCliente: document.getElementById('datosCliente'),
        dniCliente: document.getElementById('dniCliente'),
        btnProcesarVenta: document.getElementById('btnProcesarVenta')
    };
    
    let todosEncontrados = true;
    
    for (const [nombre, elemento] of Object.entries(elementos)) {
        if (elemento) {
            console.log(`✅ ${nombre}: encontrado`);
        } else {
            console.error(`❌ ${nombre}: NO encontrado`);
            todosEncontrados = false;
        }
    }
    
    return todosEncontrados;
}

// Función para probar la búsqueda con datos reales
function probarBusquedaReal() {
    console.log('🔍 Probando función de búsqueda con datos reales...');
    
    const searchInput = document.getElementById('buscarProductos');
    if (!searchInput) {
        console.error('❌ Campo de búsqueda no encontrado');
        return;
    }
    
    // Probar con "para" que debería encontrar productos con Paracetamol
    console.log('🧪 Probando búsqueda de "para" (debería encontrar productos)...');
    searchInput.value = 'para';
    
    // Disparar evento de input
    const inputEvent = new Event('input', { bubbles: true });
    searchInput.dispatchEvent(inputEvent);
    
    console.log('✅ Evento de búsqueda disparado para "para"');
    
    // Probar después de un momento con otra búsqueda
    setTimeout(() => {
        console.log('🧪 Probando búsqueda de "ib" (debería encontrar ibuprofeno)...');
        searchInput.value = 'ib';
        searchInput.dispatchEvent(inputEvent);
    }, 2000);
    
    // Limpiar después
    setTimeout(() => {
        console.log('🧹 Limpiando campo de búsqueda...');
        searchInput.value = '';
        document.getElementById('productosEncontrados').innerHTML = '';
    }, 4000);
}

// Función para monitorear cambios en el DOM
function monitorearCambiosDOM() {
    console.log('👀 Iniciando monitoreo de cambios DOM...');
    
    const productosContainer = document.getElementById('productosEncontrados');
    if (!productosContainer) {
        console.error('❌ Contenedor de productos no encontrado');
        return;
    }
    
    // Observer para cambios en el contenedor de productos
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                console.log('🔄 Cambio detectado en productos encontrados');
                console.log('📊 Contenido actual:', productosContainer.innerHTML.substring(0, 200) + '...');
                
                const productos = productosContainer.querySelectorAll('.pos-producto-item');
                console.log(`📦 Productos renderizados: ${productos.length}`);
                
                if (productos.length > 0) {
                    console.log('✅ ¡Productos encontrados y renderizados!');
                    productos.forEach((producto, index) => {
                        const nombre = producto.querySelector('.pos-producto-nombre')?.textContent;
                        console.log(`  ${index + 1}. ${nombre}`);
                    });
                } else if (productosContainer.innerHTML.includes('pos-no-productos')) {
                    console.log('ℹ️ Mensaje de "no productos" mostrado');
                } else if (productosContainer.innerHTML.trim() === '') {
                    console.log('🔄 Contenedor limpiado');
                }
            }
        });
    });
    
    // Configurar el observer
    observer.observe(productosContainer, {
        childList: true,
        subtree: true
    });
    
    console.log('👁️ Observer configurado exitosamente');
}

// Verificar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('📦 DOM cargado, iniciando verificaciones...');
    
    setTimeout(() => {
        const elementosOK = verificarElementosDOM();
        
        if (elementosOK) {
            console.log('🎉 Todos los elementos encontrados');
            
            // Configurar monitoreo
            monitorearCambiosDOM();
            
            // Probar búsqueda después de un momento
            setTimeout(() => {
                probarBusquedaReal();
            }, 1000);
        } else {
            console.error('❌ Faltan algunos elementos');
        }
    }, 500);
});

// Función global para probar búsqueda manual
window.probarBusquedaManual = function(termino = 'para') {
    console.log('🧪 Prueba manual de búsqueda:', termino);
    
    fetch(`/punto-venta/buscar-productos?q=${termino}`)
        .then(response => {
            console.log('📡 Respuesta recibida:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Datos recibidos:', data);
            if (data.success && data.productos) {
                console.log(`✅ ${data.productos.length} productos encontrados`);
                data.productos.forEach((producto, index) => {
                    console.log(`  ${index + 1}. ${producto.nombre} - S/ ${producto.precio}`);
                });
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
        });
};

// Función para simular click en producto
window.simularClickProducto = function() {
    const productos = document.querySelectorAll('.pos-producto-item');
    if (productos.length > 0) {
        console.log('🖱️ Simulando click en primer producto...');
        productos[0].click();
    } else {
        console.error('❌ No hay productos para hacer click');
    }
}; 