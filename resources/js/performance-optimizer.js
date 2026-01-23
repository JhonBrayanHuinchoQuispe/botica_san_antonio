// 🚀 OPTIMIZADOR DE RENDIMIENTO GLOBAL - Botica San Antonio
// Aplicable a todas las páginas para máxima velocidad

(function() {
    'use strict';
    
    const PerformanceOptimizer = {
        
        // 🧠 Detectar páginas pesadas que necesitan preloader inteligente
        isHeavyPage() {
            return false;
        },
        
        // ⚡ Optimización SÚPER AGRESIVA de imágenes
        optimizeImages() {
            const images = document.querySelectorAll('img:not([loading])');
            const isAnalysisOrAlerts = window.location.pathname.includes('/analisis') || 
                                     window.location.pathname.includes('/alertas');
            
            images.forEach((img, index) => {
                if (isAnalysisOrAlerts) {
                    // Análisis/Alertas: TODAS las imágenes eager para máxima velocidad
                    img.loading = 'eager';
                    img.decoding = 'sync';
                    img.style.willChange = 'transform';
                    img.style.transform = 'translateZ(0)';
                } else {
                    // Otras páginas: comportamiento normal
                    if (index < 5) {
                        img.loading = 'eager';
                        img.decoding = 'sync';
                    } else {
                        img.loading = 'lazy';
                        img.decoding = 'async';
                    }
                }
                
                // Transiciones súper rápidas
                img.style.transition = 'opacity 0.15s ease';
                
                // Placeholder optimizado
                if (!img.complete) {
                    img.style.opacity = '0.8';
                    img.addEventListener('load', () => {
                        img.style.opacity = '1';
                    }, { once: true });
                }
            });
        },
        
        // 🏠 Detectar si es cualquier Dashboard (todas las páginas de dashboard son pesadas)
        isDashboardPage() {
            return window.location.pathname === '/' || 
                   window.location.pathname.includes('/dashboard') ||
                   window.location.pathname.endsWith('/dashboard') ||
                   window.location.pathname.includes('/analisis') ||
                   window.location.pathname.includes('/alertas');
        },
        
        // 🚀 Preloader inteligente específico para páginas pesadas
        initSmartPreloader() {
            const preloader = document.getElementById('preloader');
            if (!preloader || !this.isHeavyPage()) return;
            
            const startTime = performance.now();
            let isShown = false;
            let isHidden = false;
            
            // Configuración específica para todas las páginas de Dashboard
            const isDashboard = this.isDashboardPage();
            const HEAVY_PAGE_THRESHOLD = isDashboard ? 150 : 500; // Dashboard SÚPER rápido (150ms)
            const MIN_SHOW_TIME = isDashboard ? 1000 : 600; // Dashboard más tiempo visible (1 segundo)
            
            // Función para mostrar preloader
            const showPreloader = () => {
                if (isShown || isHidden) return;
                isShown = true;
                
                preloader.style.transition = 'all 0.25s ease-out';
                preloader.style.opacity = '1';
                preloader.style.visibility = 'visible';
                preloader.style.transform = 'scale(1)';
                
                console.log(isDashboard ? '🏠 Dashboard detectado - Mostrando preloader (página pesada)' : '🐌 Página pesada detectada - Mostrando preloader');
            };
            
            // Función para ocultar preloader
            const hidePreloader = () => {
                if (isHidden) return;
                isHidden = true;
                
                const elapsedTime = performance.now() - startTime;
                
                // Si se mostró, esperar tiempo mínimo
                if (isShown) {
                    const timeShown = elapsedTime - HEAVY_PAGE_THRESHOLD;
                    const remainingTime = Math.max(0, MIN_SHOW_TIME - timeShown);
                    
                    setTimeout(() => {
                        preloader.style.transition = 'all 0.2s ease-out';
                        preloader.style.opacity = '0';
                        preloader.style.visibility = 'hidden';
                        preloader.style.transform = 'scale(0.95)';
                        preloader.style.pointerEvents = 'none';
                        
                        setTimeout(() => {
                            if (preloader.parentNode) {
                                preloader.remove();
                            }
                        }, 200);
                    }, remainingTime);
                } else {
                    // Ocultar inmediatamente si nunca se mostró
                    preloader.style.opacity = '0';
                    preloader.style.visibility = 'hidden';
                    preloader.remove();
                }
            };
            
            // Mostrar solo si tarda (incluye dashboard)
            setTimeout(() => {
                if (!isHidden && document.readyState !== 'complete') {
                    showPreloader();
                }
            }, HEAVY_PAGE_THRESHOLD);
            
            // Listeners para ocultar
            const checkAndHide = () => {
                // Verificar si las imágenes críticas están cargadas
                const criticalImages = document.querySelectorAll('img[src*="user"], img[src*="logo"]');
                let loadedCount = 0;
                
                criticalImages.forEach(img => {
                    if (img.complete) loadedCount++;
                });
                
                // Si todas las imágenes críticas están cargadas o es muy rápido
                if (loadedCount === criticalImages.length || performance.now() - startTime < HEAVY_PAGE_THRESHOLD) {
                    hidePreloader();
                }
            };
            
            // Múltiples triggers
            if (document.readyState === 'complete') {
                checkAndHide();
            } else if (document.readyState === 'interactive') {
                setTimeout(checkAndHide, 100);
            } else {
                document.addEventListener('DOMContentLoaded', checkAndHide, { once: true });
            }
            
            // Fallback de seguridad (más tiempo para dashboard)
            const timeoutDuration = 4000;
            setTimeout(() => {
                if (!isHidden) {
                    console.log(isDashboard ? '🏠 Dashboard timeout (6s) - Ocultando preloader' : '🚨 Timeout de seguridad - Ocultando preloader');
                    hidePreloader();
                }
            }, timeoutDuration);

            // Ocultar en cualquier interacción del usuario
            const hideOnInteract = () => { hidePreloader(); };
            window.addEventListener('click', hideOnInteract, { once: true, capture: true });
            window.addEventListener('pointerdown', hideOnInteract, { once: true, capture: true });
            window.addEventListener('keydown', hideOnInteract, { once: true, capture: true });
            window.addEventListener('wheel', hideOnInteract, { once: true, capture: true });
        },
        
        // 🔧 Optimización de recursos críticos
        optimizeResources() {
            // Precargar recursos críticos si estamos en página pesada
            if (this.isHeavyPage()) {
                const criticalResources = [
                    '/assets/images/users/user1.png',
                    '/assets/images/users/user2.png',
                    '/assets/images/users/user3.png'
                ];
                
                criticalResources.forEach(resource => {
                    const link = document.createElement('link');
                    link.rel = 'preload';
                    link.as = 'image';
                    link.href = resource;
                    document.head.appendChild(link);
                });
            }
        },
        
        // 🚀 Inicialización completa
        init() {
            // Ejecutar optimizaciones inmediatamente
            this.optimizeResources();
            this.initSmartPreloader();
            
            // Optimizar imágenes cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    this.optimizeImages();
                });
            } else {
                this.optimizeImages();
            }
            
            console.log('⚡ Performance Optimizer iniciado para:', this.isDashboardPage() ? 'Dashboard (Página Pesada)' : 'Página regular');
        }
    };
    
    // 🚀 Inicializar inmediatamente
    PerformanceOptimizer.init();
    
})();