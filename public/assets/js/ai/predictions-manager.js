/**
 * SISTEMA PROFESIONAL DE GESTIÓN DE PREDICCIONES
 * Botica San Antonio - Sin dependencia de F5
 */

class PredictionsManager {
    constructor() {
        this.initialized = false;
        this.processing = new Set(); // Track de botones en proceso
        this.eventHandlers = new Map(); // Mapa de handlers
        
        console.log('🚀 PredictionsManager inicializado');
        
        // Inicializar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }
    
    init() {
        if (this.initialized) {
            console.log('⚠️ PredictionsManager ya inicializado');
            return;
        }
        
        console.log('🔧 Inicializando PredictionsManager...');
        
        // Esperar a que el chat esté disponible
        this.waitForChatSystem();
    }
    
    waitForChatSystem() {
        const checkChat = () => {
            if (window.aiChat && typeof window.aiChat.showSalesPrediction === 'function') {
                console.log('✅ Sistema de chat detectado');
                this.setupPredictionButtons();
            } else {
                console.log('⏳ Esperando sistema de chat...');
                setTimeout(checkChat, 300);
            }
        };
        
        checkChat();
    }
    
    setupPredictionButtons() {
        const buttons = document.querySelectorAll('.ai-prediction-btn');
        
        if (buttons.length === 0) {
            console.log('⏳ No se encontraron botones, reintentando...');
            setTimeout(() => this.setupPredictionButtons(), 500);
            return;
        }
        
        console.log(`🎯 Configurando ${buttons.length} botones de predicciones`);
        
        // Limpiar handlers existentes
        this.cleanupHandlers();
        
        // Configurar cada botón
        buttons.forEach(button => this.setupButton(button));
        
        this.initialized = true;
        console.log('✅ PredictionsManager completamente inicializado');
    }
    
    cleanupHandlers() {
        // Remover todos los event listeners existentes
        this.eventHandlers.forEach((handler, button) => {
            button.removeEventListener('click', handler);
        });
        this.eventHandlers.clear();
        console.log('🧹 Handlers limpiados');
    }
    
    setupButton(button) {
        const predictionType = button.getAttribute('data-prediction');
        
        if (!predictionType) {
            console.warn('⚠️ Botón sin data-prediction:', button);
            return;
        }
        
        // Crear handler específico para este botón
        const handler = (event) => this.handlePredictionClick(event, predictionType);
        
        // Agregar event listener
        button.addEventListener('click', handler);
        
        // Guardar referencia para limpieza posterior
        this.eventHandlers.set(button, handler);
        
        console.log(`🔗 Botón configurado: ${predictionType}`);
    }
    
    async handlePredictionClick(event, predictionType) {
        event.preventDefault();
        event.stopPropagation();
        
        const button = event.currentTarget;
        const buttonId = button.id || `btn-${predictionType}`;
        
        // Verificar si ya está procesando
        if (this.processing.has(buttonId)) {
            console.log(`🚫 ${predictionType} ya en proceso, ignorando`);
            return;
        }
        
        console.log(`🎯 Ejecutando predicción: ${predictionType}`);
        
        // Marcar como procesando
        this.processing.add(buttonId);
        this.setButtonState(button, true, 'Generando...');
        
        try {
            // Ejecutar predicción según el tipo
            switch (predictionType) {
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
                    throw new Error(`Tipo de predicción desconocido: ${predictionType}`);
            }
            
            console.log(`✅ Predicción ${predictionType} completada`);
            
        } catch (error) {
            console.error(`❌ Error en predicción ${predictionType}:`, error);
            
            // Mostrar error al usuario
            if (window.aiChat && window.aiChat.addMessage) {
                window.aiChat.addMessage(
                    `❌ Error al generar ${predictionType}. Inténtalo de nuevo.`, 
                    false, 
                    true
                );
            }
        } finally {
            // Restaurar botón después de un delay
            setTimeout(() => {
                this.processing.delete(buttonId);
                this.setButtonState(button, false, button.dataset.originalText || 'Predicción');
            }, 1500);
        }
    }
    
    setButtonState(button, disabled, text) {
        if (!button.dataset.originalText) {
            button.dataset.originalText = button.textContent.trim();
        }
        
        button.disabled = disabled;
        
        // Cambiar solo el texto, mantener iconos
        const textSpan = button.querySelector('span');
        if (textSpan) {
            textSpan.textContent = text;
        } else {
            button.textContent = text;
        }
        
        // Agregar clase visual
        if (disabled) {
            button.classList.add('processing');
        } else {
            button.classList.remove('processing');
        }
    }
    
    // Método público para reinicializar si es necesario
    reinitialize() {
        console.log('🔄 Reinicializando PredictionsManager...');
        this.initialized = false;
        this.processing.clear();
        this.cleanupHandlers();
        this.init();
    }
}

// Crear instancia global única
if (!window.predictionsManager) {
    window.predictionsManager = new PredictionsManager();
} else {
    console.log('♻️ PredictionsManager ya existe, reinicializando...');
    window.predictionsManager.reinitialize();
}

// Exportar para uso externo
window.PredictionsManager = PredictionsManager;