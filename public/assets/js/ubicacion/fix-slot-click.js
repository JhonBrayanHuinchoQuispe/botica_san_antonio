// ===============================================
// FIX PARA SLOTS VACÍOS - ASEGURAR QUE EL CLICK FUNCIONE
// ===============================================

console.log('🔧 Fix de slots vacíos cargado');

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Iniciando fix de slots vacíos...');
    
    // Función para agregar listeners a slots vacíos
    function agregarClickASlotsVacios() {
        const slotsVacios = document.querySelectorAll('.slot-container.vacio');
        console.log(`📦 Encontrados ${slotsVacios.length} slots vacíos`);
        
        slotsVacios.forEach((slot, index) => {
            // Remover listeners anteriores
            const oldHandler = slot._clickHandler;
            if (oldHandler) {
                slot.removeEventListener('click', oldHandler);
            }
            
            // Crear nuevo handler
            const newHandler = function(e) {
                // Verificar si estamos en modo fusión
                if (document.body.classList.contains('modo-fusion-activo')) {
                    console.log('⚠️ Modo fusión activo, no abrir modal');
                    return;
                }
                
                // Verificar que no sea un botón de acción
                if (e.target.closest('.btn-slot-accion')) {
                    console.log('⚠️ Click en botón de acción, ignorar');
                    return;
                }
                
                const slotId = slot.dataset.slot;
                console.log(`✅ Click en slot vacío: ${slotId}`);
                
                // Prevenir propagación
                e.stopPropagation();
                e.preventDefault();
                
                // Verificar si existe modalAgregar
                if (window.modalAgregar && typeof window.modalAgregar.abrirModoSlotEspecifico === 'function') {
                    console.log('🚀 Abriendo modal para slot:', slotId);
                    window.modalAgregar.abrirModoSlotEspecifico(slotId);
                } else {
                    console.error('❌ modalAgregar no está disponible');
                    console.log('Intentando inicializar modalAgregar...');
                    
                    // Intentar inicializar el modal
                    setTimeout(() => {
                        if (window.modalAgregar) {
                            window.modalAgregar.abrirModoSlotEspecifico(slotId);
                        } else {
                            alert('Error: El sistema de agregar productos no está disponible. Por favor, recarga la página.');
                        }
                    }, 100);
                }
            };
            
            // Guardar referencia al handler
            slot._clickHandler = newHandler;
            
            // Agregar el listener
            slot.addEventListener('click', newHandler);
            
            // Agregar estilo de cursor
            slot.style.cursor = 'pointer';
            
            // Agregar título para indicar que es clickeable
            slot.title = 'Click para agregar producto';
            
            console.log(`✅ Listener agregado al slot ${index + 1}: ${slot.dataset.slot}`);
        });
        
        console.log('✅ Todos los listeners agregados correctamente');
    }
    
    // Ejecutar inmediatamente
    agregarClickASlotsVacios();
    
    // Ejecutar después de un delay para asegurar que todo esté cargado
    setTimeout(agregarClickASlotsVacios, 500);
    setTimeout(agregarClickASlotsVacios, 1000);
    setTimeout(agregarClickASlotsVacios, 2000);
    
    // Observar cambios en el DOM para re-agregar listeners si se agregan nuevos slots
    const observer = new MutationObserver(function(mutations) {
        let shouldUpdate = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && (node.classList.contains('slot-container') || node.querySelector('.slot-container'))) {
                        shouldUpdate = true;
                    }
                });
            }
        });
        
        if (shouldUpdate) {
            console.log('🔄 Detectados cambios en el DOM, re-agregando listeners...');
            setTimeout(agregarClickASlotsVacios, 100);
        }
    });
    
    // Observar el contenedor de slots
    const estanteGrid = document.querySelector('.estante-grid');
    if (estanteGrid) {
        observer.observe(estanteGrid, {
            childList: true,
            subtree: true
        });
        console.log('👀 Observador de DOM activado');
    }
    
    console.log('✅ Fix de slots vacíos completado');
});

// Exportar función para uso manual si es necesario
window.fixSlotsVacios = function() {
    console.log('🔧 Ejecutando fix manual de slots vacíos...');
    const event = new Event('DOMContentLoaded');
    document.dispatchEvent(event);
};

console.log('💡 Usa window.fixSlotsVacios() para ejecutar el fix manualmente');
