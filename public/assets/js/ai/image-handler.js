/**
 * MANEJADOR DE IMÁGENES PARA PREDICCIONES
 * Botica San Antonio - Procesamiento de gráficos base64
 */

class ImageHandler {
    static processResponse(text) {
        console.log('🔍 Procesando respuesta para imágenes...');
        
        // Buscar imágenes base64 en el texto
        const base64Pattern = /<img[^>]*src="(data:image\/[^"]*)"[^>]*>/g;
        let match;
        const images = [];
        
        while ((match = base64Pattern.exec(text)) !== null) {
            images.push({
                fullMatch: match[0],
                src: match[1]
            });
        }
        
        // También buscar divs con imágenes
        const divPattern = /<div[^>]*>[\s\S]*?<img[^>]*src="(data:image\/[^"]*)"[^>]*>[\s\S]*?<\/div>/g;
        let divMatch;
        
        while ((divMatch = divPattern.exec(text)) !== null) {
            console.log('📦 Encontrado div con imagen');
        }
        
        if (images.length > 0) {
            console.log(`✅ Encontradas ${images.length} imágenes base64`);
            
            // Procesar cada imagen para asegurar que se muestre correctamente
            images.forEach((img, index) => {
                console.log(`📊 Procesando imagen ${index + 1}:`, img.src.substring(0, 50) + '...');
                
                // Verificar que la imagen sea válida
                if (this.isValidBase64Image(img.src)) {
                    console.log(`✅ Imagen ${index + 1} válida`);
                } else {
                    console.warn(`⚠️ Imagen ${index + 1} puede estar corrupta`);
                }
            });
        } else {
            console.log('ℹ️ No se encontraron imágenes base64 en la respuesta');
        }
        
        return text;
    }
    
    static isValidBase64Image(src) {
        try {
            // Verificar formato básico
            if (!src.startsWith('data:image/')) {
                return false;
            }
            
            // Verificar que tenga contenido base64
            const base64Part = src.split(',')[1];
            if (!base64Part || base64Part.length < 100) {
                return false;
            }
            
            // Verificar caracteres válidos de base64
            const base64Regex = /^[A-Za-z0-9+/]*={0,2}$/;
            return base64Regex.test(base64Part);
            
        } catch (error) {
            console.error('Error validando imagen base64:', error);
            return false;
        }
    }
    
    static enhanceImageDisplay(container) {
        const images = container.querySelectorAll('img[src^="data:image/"]');
        
        images.forEach((img, index) => {
            console.log(`🖼️ Mejorando visualización de imagen ${index + 1}`);
            
            // Agregar estilos para mejor visualización
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
            img.style.borderRadius = '8px';
            img.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            img.style.border = '2px solid #fff';
            img.style.display = 'block';
            img.style.margin = '10px auto';
            
            // Agregar evento de carga
            img.onload = () => {
                console.log(`✅ Imagen ${index + 1} cargada correctamente`);
                img.style.opacity = '1';
            };
            
            // Agregar evento de error
            img.onerror = () => {
                console.error(`❌ Error cargando imagen ${index + 1}`);
                img.style.display = 'none';
                
                // Mostrar mensaje de error
                const errorDiv = document.createElement('div');
                errorDiv.innerHTML = `
                    <div style="padding: 20px; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; color: #dc2626; text-align: center;">
                        <iconify-icon icon="solar:danger-triangle-bold" style="font-size: 24px; margin-bottom: 8px;"></iconify-icon><br>
                        <strong>Error al cargar el gráfico</strong><br>
                        <small>El gráfico no se pudo mostrar correctamente</small>
                    </div>
                `;
                img.parentNode.insertBefore(errorDiv, img.nextSibling);
            };
            
            // Inicializar con opacidad 0 para transición suave
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease';
        });
    }
    
    static createImagePlaceholder(width = 400, height = 300) {
        return `
            <div style="
                width: ${width}px; 
                height: ${height}px; 
                background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
                border: 2px dashed #d1d5db;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 20px auto;
                color: #6b7280;
                font-family: system-ui, -apple-system, sans-serif;
            ">
                <div style="text-align: center;">
                    <iconify-icon icon="solar:chart-2-bold-duotone" style="font-size: 48px; margin-bottom: 12px; opacity: 0.5;"></iconify-icon><br>
                    <strong>Generando gráfico...</strong><br>
                    <small>Por favor espera</small>
                </div>
            </div>
        `;
    }
}

// Exportar para uso global
window.ImageHandler = ImageHandler;