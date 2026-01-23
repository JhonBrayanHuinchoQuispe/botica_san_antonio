/**
 * Inicialización de botones de predicciones para IA
 * Botica San Antonio - Sistema de Predicciones ML
 */

// Variable global para evitar múltiples inicializaciones
window.predictionsInitialized = window.predictionsInitialized || false;

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Inicializando sistema de predicciones...');
    
    // Si ya está inicializado, no hacer nada
    if (window.predictionsInitialized) {
        console.log('⚠️ Sistema de predicciones ya inicializado, saltando...');
        return;
    }
    
    const initPredictionButtons = () => {
        // Buscar botones de predicciones
        const predictionButtons = document.querySelectorAll('.ai-prediction-btn');
        
        if (predictionButtons.length > 0 && window.aiChat) {
            console.log(`✅ Encontrados ${predictionButtons.length} botones de predicciones`);
            
            // Limpiar todos los listeners existentes primero
            predictionButtons.forEach(button => {
                // Clonar el botón para eliminar todos los event listeners
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
            });
            
            // Volver a obtener los botones después del clonado
            const cleanButtons = document.querySelectorAll('.ai-prediction-btn');
            
            cleanButtons.forEach(button => {
                // Agregar listener al botón limpio
                button.addEventListener('click', handlePredictionClick);
                button.setAttribute('data-listener-added', 'true');
                console.log(`🔗 Configurado botón: ${button.textContent.trim()}`);
            });
            
            // Marcar como inicializado
            window.predictionsInitialized = true;
            console.log('✅ Botones de predicciones configurados correctamente');
        } else {
            console.log('⏳ Esperando botones de predicciones y sistema de chat...');
            setTimeout(initPredictionButtons, 500);
        }
    };
    
    // Función para manejar clics en botones de predicción
    const handlePredictionClick = async (event) => {
        event.preventDefault();
        event.stopPropagation();
        
        const button = event.currentTarget;
        const predictionType = button.getAttribute('data-prediction');
        
        // Evitar múltiples clics
        if (button.disabled || button.hasAttribute('data-processing')) {
            console.log('🚫 Predicción ya en proceso, ignorando clic');
            return;
        }
        
        console.log(`🎯 Predicción solicitada: ${predictionType}`);
        
        // Marcar como procesando
        button.setAttribute('data-processing', 'true');
        button.disabled = true;
        const originalText = button.textContent;
        button.textContent = 'Generando...';
        
        try {
            // Llamar directamente a las funciones de predicción del chat
            if (window.aiChat) {
                switch(predictionType) {
                    case 'sales':
                        await window.aiChat.showSalesPrediction();
                        break;
                    case 'stock':
                        await window.aiChat.showStockAnalysis();
                        break;
                    case 'trends':
                        await window.aiChat.showTrendsAnalysis();
                        break;
                    default:
                        console.error('❌ Tipo de predicción no reconocido:', predictionType);
                }
            } else {
                console.error('❌ Sistema de chat no disponible');
                alert('Error: Sistema de chat no disponible');
            }
            
        } catch (error) {
            console.error('❌ Error en predicción:', error);
            alert('Error al generar predicción. Inténtalo de nuevo.');
        } finally {
            // Restaurar botón después de un delay
            setTimeout(() => {
                button.removeAttribute('data-processing');
                button.disabled = false;
                button.textContent = originalText;
            }, 2000); // 2 segundos de delay para evitar clics rápidos
        }
    };
    
    // Inicializar
    initPredictionButtons();
    
    // Hacer disponible globalmente
    window.handlePredictionClick = handlePredictionClick;
});