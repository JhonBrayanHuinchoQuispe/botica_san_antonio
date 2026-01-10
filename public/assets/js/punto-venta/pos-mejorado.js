/**
 * MEJORAS VISUALES PARA EL PUNTO DE VENTA
 * Script que aplica estilos mejorados y corrige problemas visuales
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎨 Aplicando mejoras visuales al POS...');
    
    // Aplicar mejoras al header
    mejorarHeader();
    
    // Aplicar mejoras generales
    mejorarEstilosGenerales();
    
    // Corregir problemas de layout
    corregirLayout();
    
    console.log('✅ Mejoras visuales aplicadas correctamente');
});

function mejorarHeader() {
    const header = document.querySelector('.pos-header');
    if (header) {
        header.style.cssText = `
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            padding: 24px 32px;
            border-bottom: 1px solid #e5e7eb;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            align-items: start;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            min-height: 100px;
        `;
    }
    
    const headerLeft = document.querySelector('.pos-header-left');
    if (headerLeft) {
        headerLeft.style.cssText = `
            display: flex;
            flex-direction: column;
            gap: 12px;
            flex: 1;
            max-width: calc(100% - 140px);
        `;
    }
    
    const headerRight = document.querySelector('.pos-header-right');
    if (headerRight) {
        headerRight.style.cssText = `
            display: flex;
            gap: 12px;
            align-items: center;
            flex-shrink: 0;
        `;
    }
    
    const title = document.querySelector('.pos-title');
    if (title) {
        title.style.cssText = `
            font-size: 28px;
            font-weight: 800;
            color: #dc2626;
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            line-height: 1.2;
        `;
    }
    
    const info = document.querySelector('.pos-info');
    if (info) {
        info.style.cssText = `
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 15px;
        `;
    }
    
    const dateUser = document.querySelectorAll('.pos-date, .pos-user');
    dateUser.forEach(element => {
        element.style.cssText = `
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            color: #6b7280;
            border: 1px solid #f3f4f6;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        `;
    });
}

function mejorarEstilosGenerales() {
    // Mejorar el contenedor principal
    const container = document.querySelector('.pos-container');
    if (container) {
        container.style.cssText = `
            width: 100%;
            height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        `;
    }
    
    // Mejorar paneles
    const panels = document.querySelectorAll('.pos-left-panel, .pos-right-panel');
    panels.forEach(panel => {
        panel.style.cssText = `
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        `;
    });
    
    // Mejorar botones
    const buttons = document.querySelectorAll('.pos-btn');
    buttons.forEach(button => {
        button.style.cssText += `
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 1px solid;
        `;
        
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-1px)';
            this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 1px 2px 0 rgba(0, 0, 0, 0.05)';
        });
    });
    
    // Mejorar inputs
    const inputs = document.querySelectorAll('.pos-input, .pos-search-input');
    inputs.forEach(input => {
        input.style.cssText += `
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: #ffffff;
        `;
        
        input.addEventListener('focus', function() {
            this.style.borderColor = '#dc2626';
            this.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
        });
        
        input.addEventListener('blur', function() {
            this.style.borderColor = '#e5e7eb';
            this.style.boxShadow = 'none';
        });
    });
}

function corregirLayout() {
    // Corregir el layout principal
    const main = document.querySelector('.pos-main');
    if (main) {
        main.style.cssText = `
            flex: 1;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            padding: 24px 32px;
            overflow: hidden;
        `;
    }
    
    // Mejorar secciones
    const sections = document.querySelectorAll('.pos-search-section, .pos-carrito-header, .pos-resumen-header, .pos-totales');
    sections.forEach(section => {
        section.style.cssText += `
            background: #f1f5f9;
            border-bottom: 1px solid #e5e7eb;
        `;
    });
    
    // Mejorar elementos del carrito
    const carritoItems = document.querySelectorAll('.pos-carrito-item');
    carritoItems.forEach(item => {
        item.style.cssText += `
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.2s ease;
        `;
        
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8fafc';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
        });
    });
    
    // Mejorar productos encontrados
    const productItems = document.querySelectorAll('.pos-producto-item');
    productItems.forEach(item => {
        item.style.cssText += `
            transition: all 0.2s ease;
            border-bottom: 1px solid #f3f4f6;
        `;
        
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8fafc';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'transparent';
        });
    });
}

// Aplicar mejoras responsivas
function aplicarResponsive() {
    const isMobile = window.innerWidth <= 768;
    const isTablet = window.innerWidth <= 1200;
    
    if (isMobile) {
        const main = document.querySelector('.pos-main');
        if (main) {
            main.style.gridTemplateColumns = '1fr';
            main.style.gap = '16px';
            main.style.padding = '16px 20px';
        }
        
        const header = document.querySelector('.pos-header');
        if (header) {
            header.style.gridTemplateColumns = '1fr';
            header.style.textAlign = 'center';
            header.style.gap = '16px';
        }
    } else if (isTablet) {
        const main = document.querySelector('.pos-main');
        if (main) {
            main.style.gridTemplateColumns = '1fr 380px';
        }
    }
}

// Escuchar cambios de tamaño de ventana
window.addEventListener('resize', aplicarResponsive);
aplicarResponsive();

// Mejorar la experiencia del usuario
function mejorarUX() {
    // Añadir efectos de loading más suaves
    const searchLoader = document.querySelector('.pos-search-loader');
    if (searchLoader) {
        searchLoader.style.cssText += `
            transition: opacity 0.3s ease;
        `;
    }
    
    // Mejorar tooltips y feedback visual
    const quantityBtns = document.querySelectorAll('.pos-quantity-btn, .pos-remove-btn');
    quantityBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });
}

// Aplicar mejoras UX
mejorarUX();

// Exportar funciones para uso global
window.mejorarAparienciaPOS = {
    header: mejorarHeader,
    general: mejorarEstilosGenerales,
    layout: corregirLayout,
    responsive: aplicarResponsive,
    ux: mejorarUX
}; 