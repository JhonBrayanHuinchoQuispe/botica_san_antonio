<div class="navbar-header border-b border-red-200 dark:bg-gray-800 dark:border-gray-600" style="background-color: #ef6060 !important;">
    <div class="flex items-center justify-between">
        <div class="col-auto">
            <div class="flex flex-wrap items-center gap-[16px]">
                <button type="button" class="sidebar-toggle">
                    <iconify-icon icon="heroicons:bars-3-solid" class="icon non-active text-white text-2xl" style="color: white !important;"></iconify-icon>
                    <iconify-icon icon="iconoir:arrow-right" class="icon active text-white text-2xl" style="color: white !important;"></iconify-icon>
                </button>
                <button type="button" class="sidebar-mobile-toggle d-flex !leading-[0]">
                    <iconify-icon icon="heroicons:bars-3-solid" class="icon text-white !text-[30px]" style="color: white !important;"></iconify-icon>
                </button>
                <form class="navbar-search relative">
                    <input type="search" name="search" id="navbar-search-input" placeholder="Buscar por sección..." autocomplete="off">
                    <iconify-icon icon="ion:search-outline" class="icon text-gray-500 text-lg"></iconify-icon>
                    
                    <div id="search-results" class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-600 z-50 max-h-96 overflow-y-auto hidden">
                        <div id="search-results-header" class="px-4 py-3 border-b border-gray-200 dark:border-gray-600 bg-blue-50 dark:bg-gray-700">
                            <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-0">Resultados de navegación</h6>
                        </div>
                        <div id="search-results-content" class="py-2">
                            <!-- Los resultados  -->
                        </div>
                        <div id="search-no-results" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 hidden">
                            <iconify-icon icon="solar:magnifer-zoom-out-linear" class="text-4xl mb-2 opacity-50"></iconify-icon>
                            <p class="text-sm">No se encontraron opciones de menú</p>
                        </div>
                    </div>
                </form>

            </div>
        </div>
        <div class="col-auto">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Theme toggle removed to enforce light mode -->

                <!-- Botón de instalar PWA retirado por solicitud -->

                <!-- Botón de Pantalla Completa retirado por solicitud -->

                <button type="button" id="internet-status" class="w-10 h-10 bg-white/20 hover:bg-white/30 border border-white/40 rounded-full flex justify-center items-center transition-all duration-300 backdrop-blur-sm" title="Estado de Internet">
                    <span id="internet-connected" class="hidden">
                        <iconify-icon icon="material-symbols:wifi" class="text-xl" style="color: #00e676 !important;"></iconify-icon>
                    </span>
                    <span id="internet-slow" class="hidden">
                        <iconify-icon icon="material-symbols:wifi-2-bar" class="text-xl" style="color: #ffea00 !important;"></iconify-icon>
                    </span>
                    <span id="internet-disconnected" class="hidden">
                        <iconify-icon icon="material-symbols:wifi-off" class="text-xl" style="color: #ff1744 !important;"></iconify-icon>
                    </span>
                    <span id="internet-checking" class="hidden">
                        <iconify-icon icon="material-symbols:wifi-find" class="text-xl animate-pulse" style="color: white !important;"></iconify-icon>
                    </span>
                </button>

                <button data-dropdown-toggle="dropdownNotification" id="notification-button" class="relative w-10 h-10 bg-white/20 hover:bg-white/30 border-2 border-white/40 rounded-full flex justify-center items-center transition-all duration-300 backdrop-blur-sm" type="button">
                    <iconify-icon id="notification-bell" icon="iconoir:bell" class="text-xl transition-all duration-300" style="color: white !important;"></iconify-icon>
                    <span id="notification-counter" class="absolute bg-gradient-to-br from-red-500 to-red-600 text-white font-black rounded-full flex items-center justify-center border-2 border-white dark:border-gray-700 shadow-xl transform scale-100 transition-all duration-300 hover:scale-110" style="top: -6px !important; right: -6px !important; font-size: 11px !important; line-height: 1 !important; z-index: 99999 !important; width: 22px !important; height: 22px !important; min-width: 22px !important; min-height: 22px !important; display: none;">0</span>
                </button>
                <div id="dropdownNotification" class="z-10 hidden bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg max-w-[394px] w-full">
                    <div class="py-3 px-4 rounded-lg bg-blue-100 border-2 border-blue-300 dark:bg-gray-700 dark:border-gray-500 m-4 flex items-center justify-between gap-2 shadow-sm" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-color: #93c5fd;">
                        <style>
                            .dark div[style*="linear-gradient"] {
                                background: #374151 !important;
                                border-color: #6b7280 !important;
                            }
                            
                            /* Forzar colores de iconos de notificaciones */
                            .notification-icon-red {
                                color: #dc2626 !important;
                            }
                            .notification-icon-amber {
                                color: #d97706 !important;
                            }
                            .notification-icon-emerald {
                                color: #059669 !important;
                            }
                            
                            .dark .notification-icon-red {
                                color: #f87171 !important;
                            }
                            .dark .notification-icon-amber {
                                color: #fbbf24 !important;
                            }
                            .dark .notification-icon-emerald {
                                color: #34d399 !important;
                            }
                            
                            /* Estilos del contador de notificaciones - FORZADO */
                            #notification-counter {
                                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
                                box-shadow: 
                                    0 4px 15px rgba(239, 68, 68, 0.6),
                                    0 2px 8px rgba(0, 0, 0, 0.4),
                                    inset 0 1px 0 rgba(255, 255, 255, 0.3) !important;
                                font-size: 11px !important;
                                font-weight: 900 !important;
                                letter-spacing: 0.02em !important;
                                z-index: 99999 !important;
                                aspect-ratio: 1 !important;
                                display: flex !important;
                                align-items: center !important;
                                justify-content: center !important;
                                position: absolute !important;
                                top: -6px !important;
                                right: -6px !important;
                                width: 22px !important;
                                height: 22px !important;
                                min-width: 22px !important;
                                min-height: 22px !important;
                                border-radius: 50% !important;
                                color: white !important;
                                border: 2px solid white !important;
                                text-align: center !important;
                                line-height: 1 !important;
                            }
                            
                            #notification-counter:hover {
                                background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
                                box-shadow: 
                                    0 4px 15px rgba(239, 68, 68, 0.6),
                                    0 2px 6px rgba(0, 0, 0, 0.3),
                                    inset 0 1px 0 rgba(255, 255, 255, 0.3);
                                transform: scale(1.1);
                            }
                            
                            /* Animaciones para el contador */
                            .notification-counter-hidden {
                                transform: scale(0) rotate(180deg);
                                opacity: 0;
                            }
                            
                            .notification-counter-visible {
                                transform: scale(1) rotate(0deg);
                                opacity: 1;
                            }
                            
                            /* Pulso sutil para llamar la atención */
                            @keyframes notification-pulse {
                                0%, 100% { transform: scale(1); }
                                50% { transform: scale(1.05); }
                            }
                            
                            .notification-pulse {
                                animation: notification-pulse 2s ease-in-out infinite;
                            }
                            
                            /* Estilos para el icono de campana */
                            .bell-normal {
                                color: #2563eb !important; /* Azul normal */
                            }
                            
                            /* Botón en estado normal (sin notificaciones) */
                            .notification-button-normal {
                                border: 2px solid rgba(59, 130, 246, 0.2) !important;
                            }
                            
                            .dark .notification-button-normal {
                                border: 2px solid rgba(75, 85, 99, 0.8) !important;
                                background: rgba(55, 65, 81, 1) !important;
                            }
                            
                            .dark .notification-button-normal:hover {
                                border: 2px solid rgba(75, 85, 99, 1) !important;
                                background: rgba(75, 85, 99, 1) !important;
                            }
                            
                            .bell-active {
                                color: #dc2626 !important; /* Rojo cuando hay notificaciones */
                                animation: bell-swing 1.5s ease-in-out infinite;
                                filter: drop-shadow(0 0 8px rgba(220, 38, 38, 0.6));
                                transform-origin: top center;
                            }
                            
                            .dark .bell-normal {
                                color: #e5e7eb !important; /* Gris claro en modo oscuro */
                            }
                            
                            .dark .bell-active {
                                color: #f87171 !important; /* Rojo claro en modo oscuro */
                                filter: drop-shadow(0 0 10px rgba(248, 113, 113, 0.8));
                            }
                            
                            /* Animación de campanita - MUY NOTORIA */
                            @keyframes bell-swing {
                                0%, 100% { 
                                    transform: rotate(0deg) scale(1); 
                                }
                                10% { 
                                    transform: rotate(15deg) scale(1.1); 
                                }
                                20% { 
                                    transform: rotate(-12deg) scale(1.05); 
                                }
                                30% { 
                                    transform: rotate(10deg) scale(1.08); 
                                }
                                40% { 
                                    transform: rotate(-6deg) scale(1.03); 
                                }
                                50% { 
                                    transform: rotate(4deg) scale(1.06); 
                                }
                                60% { 
                                    transform: rotate(-2deg) scale(1.02); 
                                }
                                70% { 
                                    transform: rotate(1deg) scale(1.01); 
                                }
                                80%, 90% { 
                                    transform: rotate(0deg) scale(1); 
                                }
                            }
                            
                            /* Botón cuando hay notificaciones - SÚPER NOTORIO */
                            .notification-button-active {
                                background: linear-gradient(135deg, rgba(220, 38, 38, 0.2), rgba(239, 68, 68, 0.3)) !important;
                                border: 2px solid white !important;
                                border-width: 2px !important;
                                border-style: solid !important;
                                border-color: white !important;
                                box-shadow: 
                                    0 0 25px rgba(220, 38, 38, 0.5),
                                    0 0 50px rgba(220, 38, 38, 0.3),
                                    inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
                                animation: notification-glow 2s ease-in-out infinite !important;
                                transform: scale(1.08) !important;
                            }
                            
                            .notification-button-active:hover {
                                background: linear-gradient(135deg, rgba(220, 38, 38, 0.3), rgba(239, 68, 68, 0.4)) !important;
                                box-shadow: 
                                    0 0 25px rgba(220, 38, 38, 0.6),
                                    0 0 50px rgba(220, 38, 38, 0.3),
                                    inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
                                transform: scale(1.08);
                            }
                            
                            .dark .notification-button-active {
                                background: linear-gradient(135deg, rgba(248, 113, 113, 0.3), rgba(252, 165, 165, 0.4)) !important;
                                border: 2px solid rgba(255, 255, 255, 0.9) !important;
                                border-width: 2px !important;
                                border-style: solid !important;
                                border-color: rgba(255, 255, 255, 0.9) !important;
                                box-shadow: 
                                    0 0 25px rgba(248, 113, 113, 0.7),
                                    0 0 50px rgba(248, 113, 113, 0.5),
                                    inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
                            }
                            
                            .dark .notification-button-active:hover {
                                background: linear-gradient(135deg, rgba(248, 113, 113, 0.4), rgba(252, 165, 165, 0.5)) !important;
                                border: 2px solid rgba(255, 255, 255, 1) !important;
                                box-shadow: 
                                    0 0 30px rgba(248, 113, 113, 0.8),
                                    0 0 60px rgba(248, 113, 113, 0.5),
                                    inset 0 1px 0 rgba(255, 255, 255, 0.25) !important;
                                transform: scale(1.1) !important;
                            }
                            
                            /* Animación de resplandor para el botón */
                            @keyframes notification-glow {
                                0%, 100% { 
                                    box-shadow: 
                                        0 0 20px rgba(220, 38, 38, 0.4),
                                        0 0 40px rgba(220, 38, 38, 0.2),
                                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
                                }
                                                                 50% { 
                                     box-shadow: 
                                         0 0 30px rgba(220, 38, 38, 0.6),
                                         0 0 60px rgba(220, 38, 38, 0.4),
                                         inset 0 1px 0 rgba(255, 255, 255, 0.2);
                                 }
                            }
                            
                            /* Animación de resplandor para modo oscuro */
                            .dark .notification-button-active {
                                animation: notification-glow-dark 2s ease-in-out infinite;
                            }
                            
                            @keyframes notification-glow-dark {
                                0%, 100% { 
                                    box-shadow: 
                                        0 0 20px rgba(248, 113, 113, 0.5),
                                        0 0 40px rgba(248, 113, 113, 0.3),
                                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
                                }
                                50% { 
                                    box-shadow: 
                                        0 0 30px rgba(248, 113, 113, 0.7),
                                        0 0 60px rgba(248, 113, 113, 0.5),
                                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
                                }
                            }
                            
                            /* OVERRIDE FINAL - FORZADO MÁXIMO */
                            #notification-button.notification-button-active {
                                border: 2px solid white !important;
                                border-width: 2px !important;
                                border-style: solid !important;
                                border-color: white !important;
                                transform: scale(1.08) !important;
                                box-shadow: 0 0 25px rgba(220, 38, 38, 0.5), 0 0 50px rgba(220, 38, 38, 0.3) !important;
                            }
                            
                            #notification-button.notification-button-normal {
                                border: 2px solid rgba(59, 130, 246, 0.2) !important;
                                border-width: 2px !important;
                                border-style: solid !important;
                                border-color: rgba(59, 130, 246, 0.2) !important;
                                transform: scale(1) !important;
                                box-shadow: none !important;
                            }
                            
                            #notification-counter {
                                font-size: 11px !important;
                                width: 22px !important;
                                height: 22px !important;
                                min-width: 22px !important;
                                min-height: 22px !important;
                                top: -6px !important;
                                right: -6px !important;
                                border: 2px solid white !important;
                            }
                            
                            .dark #notification-counter {
                                border: 2px solid rgba(55, 65, 81, 1) !important;
                                box-shadow: 
                                    0 4px 15px rgba(239, 68, 68, 0.8),
                                    0 2px 8px rgba(0, 0, 0, 0.6),
                                    inset 0 1px 0 rgba(255, 255, 255, 0.4) !important;
                            }
                            
                                                        /* ESTILOS PANTALLA COMPLETA - IGUAL QUE TU MODO OSCURO */
                            #fullscreen-toggle {
                                background-color: rgba(255, 255, 255, 0.15) !important;
                                border: 1px solid rgba(255, 255, 255, 0.2) !important;
                                backdrop-filter: blur(10px) !important;
                                color: white !important;
                            }

                            #fullscreen-toggle:hover {
                                background-color: rgba(255, 255, 255, 0.25) !important;
                                border-color: rgba(255, 255, 255, 0.3) !important;
                            }

                            /* Iconos del botón de pantalla completa */
                            #fullscreen-expand-icon iconify-icon,
                            #fullscreen-compress-icon iconify-icon {
                                color: white !important;
                            }

                            /* Modo oscuro */
                            .dark #fullscreen-toggle {
                                background-color: rgba(255, 255, 255, 0.1) !important;
                                border-color: rgba(255, 255, 255, 0.15) !important;
                                backdrop-filter: blur(10px) !important;
                            }

                            .dark #fullscreen-toggle:hover {
                                background-color: rgba(255, 255, 255, 0.2) !important;
                                border-color: rgba(255, 255, 255, 0.25) !important;
                            }
                        </style>
                        <h6 class="text-lg text-neutral-900 dark:text-white font-semibold mb-0">Notificaciones</h6>
                        <span id="notification-header-counter" class="w-10 h-10 bg-blue-600 dark:bg-blue-500 text-white font-bold flex justify-center items-center rounded-full shadow-lg border-2 border-white dark:border-gray-300 hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors">00</span>
                    </div>
                    <div class="scroll-sm !border-t-0">
                        <div class="max-h-[400px] overflow-y-auto" id="notifications-container">
                            <!-- Las notificaciones se cargan dinámicamente desde notifications.js -->
                            <div class="flex flex-col items-center justify-center py-12 px-4" id="notifications-loading">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400 mb-3"></div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Cargando notificaciones...</p>
                            </div>
                        </div>


                        
                        <style>
                            /* FORZADO FINAL MODO OSCURO */
                            .dark #alert-view-button {
                                background-color: #374151 !important;
                                background: #374151 !important;
                                border-color: #4b5563 !important;
                                color: #60a5fa !important;
                            }
                            
                            .dark #alert-view-button iconify-icon {
                                color: #60a5fa !important;
                            }
                            
                            .dark #alert-view-button span {
                                color: #60a5fa !important;
                            }
                            
                            .dark #alert-view-button:hover {
                                background-color: #4b5563 !important;
                                background: #4b5563 !important;
                            }
                            
                            #alert-view-button:hover {
                                background-color: #eff6ff !important;
                                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
                            }
                        </style>
                        
                        <script>
                            // FORZAR con JavaScript también
                            document.addEventListener('DOMContentLoaded', function() {
                                const alertButton = document.getElementById('alert-view-button');
                                
                                function updateAlertButtonTheme() {
                                    const isDark = document.documentElement.classList.contains('dark');
                                    
                                    if (alertButton) {
                                        if (isDark) {
                                            alertButton.style.backgroundColor = '#374151';
                                            alertButton.style.background = '#374151';
                                            alertButton.style.border = '1px solid #4b5563';
                                            alertButton.style.color = '#60a5fa';
                                            
                                            // Forzar elementos internos
                                            const icon = alertButton.querySelector('iconify-icon');
                                            const span = alertButton.querySelector('span');
                                            if (icon) icon.style.color = '#60a5fa';
                                            if (span) span.style.color = '#60a5fa';
                                        } else {
                                            alertButton.style.backgroundColor = 'white';
                                            alertButton.style.background = 'white';
                                            alertButton.style.border = '1px solid #dbeafe';
                                            alertButton.style.color = '#2563eb';
                                            
                                            // Forzar elementos internos
                                            const icon = alertButton.querySelector('iconify-icon');
                                            const span = alertButton.querySelector('span');
                                            if (icon) icon.style.color = '#2563eb';
                                            if (span) span.style.color = '#2563eb';
                                        }
                                    }
                                }
                                
                                // Ejecutar al cargar
                                updateAlertButtonTheme();
                                
                                // Observar cambios de tema
                                const alertThemeObserver = new MutationObserver(function(mutations) {
                                    mutations.forEach(function(mutation) {
                                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                            setTimeout(() => {
                                                updateAlertButtonTheme();
                                            }, 50);
                                        }
                                    });
                                });
                                
                                alertThemeObserver.observe(document.documentElement, { 
                                    attributes: true, 
                                    attributeFilter: ['class'] 
                                });
                            });
                        </script>
                    </div>
                </div>
                <!-- Notification End  -->
                
                <!-- Script para contador dinámico de notificaciones -->
                <script>
                    function updateNotificationCounter() {
                        // Contar elementos con clase 'notification-item'
                        const notificationItems = document.querySelectorAll('.notification-item');
                        const counter = document.getElementById('notification-counter');
                        const bellIcon = document.getElementById('notification-bell');
                        const bellButton = document.getElementById('notification-button');
                        const count = notificationItems.length;
                        
                        if (counter && bellIcon && bellButton) {
                            if (count > 0) {
                                // Mostrar contador con el número correcto
                                counter.textContent = count;
                                counter.classList.remove('notification-counter-hidden');
                                counter.classList.add('notification-counter-visible');
                                counter.style.display = 'flex';
                                
                                // Activar icono de campana (rojo con animación)
                                bellIcon.classList.remove('bell-normal');
                                bellIcon.classList.add('bell-active');
                                bellButton.classList.remove('notification-button-normal');
                                bellButton.classList.add('notification-button-active');
                                
                                // FORZAR estilos inline para asegurar que se apliquen
                                const isDark = document.documentElement.classList.contains('dark');
                                if (isDark) {
                                    bellButton.style.border = '2px solid rgba(255, 255, 255, 0.9)';
                                    bellButton.style.borderColor = 'rgba(255, 255, 255, 0.9)';
                                    bellButton.style.background = 'linear-gradient(135deg, rgba(248, 113, 113, 0.3), rgba(252, 165, 165, 0.4))';
                                    bellButton.style.boxShadow = '0 0 25px rgba(248, 113, 113, 0.7), 0 0 50px rgba(248, 113, 113, 0.5)';
                                } else {
                                    bellButton.style.border = '2px solid white';
                                    bellButton.style.borderColor = 'white';
                                    bellButton.style.background = 'linear-gradient(135deg, rgba(220, 38, 38, 0.2), rgba(239, 68, 68, 0.3))';
                                    bellButton.style.boxShadow = '0 0 25px rgba(220, 38, 38, 0.5), 0 0 50px rgba(220, 38, 38, 0.3)';
                                }
                                bellButton.style.borderWidth = '2px';
                                bellButton.style.borderStyle = 'solid';
                                bellButton.style.transform = 'scale(1.08)';
                                
                                // Efecto de entrada dramático
                                bellButton.style.transition = 'all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                                setTimeout(() => {
                                    bellButton.style.transition = 'all 0.3s ease-in-out';
                                }, 600);
                                
                                // Agregar pulso si hay notificaciones críticas (stock bajo)
                                const criticalNotifications = document.querySelectorAll('.notification-item[href*="stock_bajo"]');
                                if (criticalNotifications.length > 0) {
                                    counter.classList.add('notification-pulse');
                                    // Cambiar icono a campana sonando para críticas
                                    bellIcon.setAttribute('icon', 'iconoir:bell-notification');
                                } else {
                                    counter.classList.remove('notification-pulse');
                                    // Mantener campana normal pero activa
                                    bellIcon.setAttribute('icon', 'iconoir:bell');
                                }
                            } else {
                                // Ocultar contador si no hay notificaciones
                                counter.classList.remove('notification-counter-visible', 'notification-pulse');
                                counter.classList.add('notification-counter-hidden');
                                
                                // Desactivar icono de campana (azul normal)
                                bellIcon.classList.remove('bell-active');
                                bellIcon.classList.add('bell-normal');
                                bellButton.classList.remove('notification-button-active');
                                bellButton.classList.add('notification-button-normal');
                                bellIcon.setAttribute('icon', 'iconoir:bell');
                                
                                // FORZAR vuelta al estado normal
                                const isDarkMode = document.documentElement.classList.contains('dark');
                                if (isDarkMode) {
                                    bellButton.style.border = '2px solid rgba(75, 85, 99, 0.8)';
                                    bellButton.style.borderColor = 'rgba(75, 85, 99, 0.8)';
                                    bellButton.style.background = 'rgba(55, 65, 81, 1)';
                                } else {
                                    bellButton.style.border = '2px solid rgba(59, 130, 246, 0.2)';
                                    bellButton.style.borderColor = 'rgba(59, 130, 246, 0.2)';
                                    bellButton.style.background = 'white';
                                }
                                bellButton.style.borderWidth = '2px';
                                bellButton.style.borderStyle = 'solid';
                                bellButton.style.transform = 'scale(1)';
                                bellButton.style.boxShadow = 'none';
                                
                                // Efecto de salida suave
                                bellButton.style.transition = 'all 0.8s ease-out';
                                setTimeout(() => {
                                    bellButton.style.transition = 'all 0.3s ease-in-out';
                                }, 800);
                                
                                setTimeout(() => {
                                    counter.style.display = 'none';
                                }, 300);
                            }
                        }
                    }
                    
                    // Ejecutar cuando se carga la página
                    document.addEventListener('DOMContentLoaded', function() {
                        // Inicializar el icono con estado normal
                        const bellIcon = document.getElementById('notification-bell');
                        const bellButton = document.getElementById('notification-button');
                        
                        if (bellIcon) {
                            bellIcon.classList.add('bell-normal');
                        }
                        
                        if (bellButton) {
                            bellButton.classList.add('notification-button-normal');
                            // FORZAR estado inicial detectando modo oscuro
                            const isInitialDark = document.documentElement.classList.contains('dark');
                            if (isInitialDark) {
                                bellButton.style.border = '2px solid rgba(75, 85, 99, 0.8)';
                                bellButton.style.borderColor = 'rgba(75, 85, 99, 0.8)';
                                bellButton.style.background = 'rgba(55, 65, 81, 1)';
                            } else {
                                bellButton.style.border = '2px solid rgba(59, 130, 246, 0.2)';
                                bellButton.style.borderColor = 'rgba(59, 130, 246, 0.2)';
                                bellButton.style.background = 'white';
                            }
                            bellButton.style.borderWidth = '2px';
                            bellButton.style.borderStyle = 'solid';
                        }
                        
                        // Actualizar contador y estado del icono
                        updateNotificationCounter();
                    });
                    
                    // También ejecutar cuando se modifica el DOM (para actualizaciones dinámicas)
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'childList') {
                                updateNotificationCounter();
                            }
                        });
                    });
                    
                    // Observar cambios en el dropdown de notificaciones
                    const dropdownNotification = document.getElementById('dropdownNotification');
                    if (dropdownNotification) {
                        observer.observe(dropdownNotification, { 
                            childList: true, 
                            subtree: true 
                        });
                    }
                    
                    // Observar cambios en el modo oscuro
                    const themeObserver = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                // Actualizar estilos cuando cambie el modo oscuro
                                setTimeout(() => {
                                    updateNotificationCounter();
                                }, 100);
                            }
                        });
                    });
                    
                    // Observar cambios en la clase del documento
                    themeObserver.observe(document.documentElement, { 
                        attributes: true, 
                        attributeFilter: ['class'] 
                    });
                </script>


                <button data-dropdown-toggle="dropdownProfile" class="flex justify-center items-center rounded-full border-2 border-white/40 hover:border-white/80 transition-colors" type="button">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar_url }}" alt="Avatar de {{ auth()->user()->name }}" class="w-10 h-10 object-cover rounded-full">
                    @else
                        <div class="w-10 h-10 bg-white text-[#ef6060] rounded-full flex items-center justify-center font-bold text-sm shadow-sm">
                            @if(auth()->user()->nombres && auth()->user()->apellidos)
                                {{ strtoupper(substr(auth()->user()->nombres, 0, 1) . substr(auth()->user()->apellidos, 0, 1)) }}
                            @else
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            @endif
                        </div>
                    @endif
                </button>
                <div id="dropdownProfile" class="z-10 hidden bg-white dark:bg-gray-800 rounded-lg shadow-lg dropdown-menu-sm p-3">
                    <div class="py-3 px-4 rounded-lg bg-red-50 border-2 border-red-200 dark:bg-red-900/20 dark:border-red-700/50 mb-4 flex items-center justify-between gap-2 shadow-sm" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-color: #fca5a5;">
                        <div class="flex items-center gap-3">
                            @if(auth()->user()->avatar)
                                <img src="{{ auth()->user()->avatar_url }}" alt="Avatar de {{ auth()->user()->name }}" class="w-12 h-12 object-cover rounded-full border-2 border-white shadow-md">
                            @else
                                <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-full flex items-center justify-center text-white font-bold border-2 border-white shadow-md">
                                    @if(auth()->user()->nombres && auth()->user()->apellidos)
                                        {{ strtoupper(substr(auth()->user()->nombres, 0, 1) . substr(auth()->user()->apellidos, 0, 1)) }}
                                    @else
                                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                    @endif
                                </div>
                            @endif
                            <div>
                                <h6 class="text-lg text-neutral-900 dark:text-white font-semibold mb-0">
                                    @if(auth()->user()->nombres && auth()->user()->apellidos)
                                        {{ auth()->user()->nombres }} {{ auth()->user()->apellidos }}
                                    @else
                                        {{ auth()->user()->name }}
                                    @endif
                                </h6>
                                <span class="text-neutral-500 dark:text-gray-300 text-sm">
                                    @php
                                        $user = auth()->user();
                                        $roleLabel = optional($user->roles->first())->display_name ?? ($user->getRoleNames()->first() ?? 'Usuario');
                                    @endphp
                                    {{ $roleLabel }}
                                </span>
                            </div>
                        </div>
                        <button type="button" class="hover:text-danger-600 dark:hover:text-red-400 text-gray-500 dark:text-gray-400 transition-colors duration-200" onclick="closeProfileDropdown()">
                            <iconify-icon icon="radix-icons:cross-1" class="icon text-xl"></iconify-icon>
                        </button>
                    </div>

                    <div class="max-h-[400px] overflow-y-auto scroll-sm pe-2">
                        <ul class="flex flex-col">
                            {{-- <li>
                                <a class="text-black px-0 py-2 hover:text-primary-600 flex items-center gap-4" href="{{ route('profile.edit') }}">
                                    <iconify-icon icon="solar:user-linear" class="icon text-xl"></iconify-icon>  Mi Perfil
                                </a>
                            </li> --}}
                            <li>
                                <a class="text-black dark:text-white px-0 py-2 hover:text-red-500 dark:hover:text-red-300 flex items-center gap-4 transition-colors duration-200" href="{{ route('perfil.editar') }}">
                                    <iconify-icon icon="solar:user-circle-linear" class="icon text-xl"></iconify-icon>  Mi Perfil
                                </a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                                    @csrf
                                    <button type="submit" class="text-black dark:text-white px-0 py-2 hover:text-danger-600 dark:hover:text-red-400 flex items-center gap-4 w-full transition-colors duration-200">
                                        <iconify-icon icon="lucide:power" class="icon text-xl"></iconify-icon> Cerrar sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Función para cerrar el dropdown del perfil
function closeProfileDropdown() {
    const dropdown = document.getElementById('dropdownProfile');
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
}

// También cerrar con Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeProfileDropdown();
    }
});

// Interceptor de clics en notificaciones para evitar rutas legacy
document.addEventListener('click', function(e){
    const a = e.target.closest('.notification-item');
    if (!a) return;
    const href = a.getAttribute('href') || '';
    if (href.includes('/inventario/productos?filter=')) {
        e.preventDefault();
        try {
            const u = new URL(href, window.location.origin);
            const filter = u.searchParams.get('filter');
            const map = {
                'stock_bajo': 'Bajo stock',
                'agotados': 'Agotado',
                'por_vencer': 'Por Vencer',
                'vencidos': 'Vencido',
                'normal': 'Normal'
            };
            const estado = map[filter] || 'todos';
            const qs = estado !== 'todos' ? ('?estado=' + encodeURIComponent(estado)) : '';
            window.location.href = '/inventario/productos-botica' + qs;
        } catch(_) {
            window.location.href = '/inventario/productos-botica';
        }
    }
});

// Pantalla completa deshabilitada y botón retirado por solicitud

// ==================== FUNCIONALIDAD DE ESTADO DE INTERNET ====================
document.addEventListener('DOMContentLoaded', function() {
    const internetStatus = document.getElementById('internet-status');
    const connectedIcon = document.getElementById('internet-connected');
    const slowIcon = document.getElementById('internet-slow');
    const disconnectedIcon = document.getElementById('internet-disconnected');
    const checkingIcon = document.getElementById('internet-checking');
    
    let isOnline = navigator.onLine;
    let checkInterval;
    let connectionSpeed = 'unknown';
    
    // Función para actualizar el estado visual
    function updateInternetStatus(status, speed = null) {
        // Ocultar todos los iconos
        connectedIcon.classList.add('hidden');
        slowIcon.classList.add('hidden');
        disconnectedIcon.classList.add('hidden');
        checkingIcon.classList.add('hidden');
        
        switch(status) {
            case 'connected':
                connectedIcon.classList.remove('hidden');
                internetStatus.setAttribute('title', `Internet Conectado - Velocidad: ${speed || 'Buena'}`);
                break;
            case 'slow':
                slowIcon.classList.remove('hidden');
                internetStatus.setAttribute('title', `Internet Lento - Velocidad: ${speed || 'Limitada'}`);
                break;
            case 'disconnected':
                disconnectedIcon.classList.remove('hidden');
                internetStatus.setAttribute('title', 'Sin Conexión a Internet - Haz clic para verificar');
                break;
            case 'checking':
                checkingIcon.classList.remove('hidden');
                internetStatus.setAttribute('title', 'Verificando Conexión... Por favor espera');
                break;
        }
    }
    
    // Función para verificar la conexión real y velocidad
    async function checkInternetConnection() {
        try {
            updateInternetStatus('checking');

            const startTime = performance.now();

            // Usar un recurso local confiable para evitar problemas CORS
            const url = `${window.location.origin}/favicon.ico`;
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 4000); // timeout 4s

            const response = await fetch(url, {
                method: 'HEAD',
                cache: 'no-store',
                signal: controller.signal,
            });

            clearTimeout(timeoutId);

            const endTime = performance.now();
            const responseTime = endTime - startTime;

            // Si llegamos aquí, hay conexión
            isOnline = true;

            // Determinar velocidad basada en tiempo de respuesta
            if (responseTime < 500) {
                connectionSpeed = 'Excelente';
                updateInternetStatus('connected', connectionSpeed);
            } else if (responseTime < 1500) {
                connectionSpeed = 'Buena';
                updateInternetStatus('connected', connectionSpeed);
            } else if (responseTime < 3000) {
                connectionSpeed = 'Regular';
                updateInternetStatus('slow', connectionSpeed);
            } else {
                connectionSpeed = 'Lenta';
                updateInternetStatus('slow', connectionSpeed);
            }

        } catch (error) {
            // Si hay error/timeout, considerar desconectado
            isOnline = false;
            connectionSpeed = 'Sin conexión';
            updateInternetStatus('disconnected');
        }
    }
    
    // Función para iniciar verificación periódica
    function startPeriodicCheck() {
        // Verificar cada 30 segundos
        checkInterval = setInterval(checkInternetConnection, 30000);
    }
    
    // Función para detener verificación periódica
    function stopPeriodicCheck() {
        if (checkInterval) {
            clearInterval(checkInterval);
            checkInterval = null;
        }
    }
    
    // Event listeners para cambios de conexión del navegador
    window.addEventListener('online', function() {
        isOnline = true;
        // Actualizar inmediatamente para evitar estado en rojo
        updateInternetStatus('connected', 'Recuperando');
        // Verificar la velocidad real cuando se detecta conexión
        checkInternetConnection();
        stopPeriodicCheck();
        startPeriodicCheck();
    });
    
    window.addEventListener('offline', function() {
        isOnline = false;
        connectionSpeed = 'Sin conexión';
        updateInternetStatus('disconnected');
        stopPeriodicCheck();
        // Verificar más frecuentemente cuando está desconectado
        checkInterval = setInterval(checkInternetConnection, 10000);
    });
    
    // Click en el botón para verificación manual
    internetStatus.addEventListener('click', function() {
        checkInternetConnection();
    });
    
    // Inicialización
    if (navigator.onLine) {
        checkInternetConnection();
        startPeriodicCheck();
    } else {
        updateInternetStatus('disconnected');
        // Verificar más frecuentemente cuando está desconectado
        checkInterval = setInterval(checkInternetConnection, 10000);
    }
    
    // Limpiar interval cuando se cierra la página
    window.addEventListener('beforeunload', function() {
        stopPeriodicCheck();
    });
    
    // 🔔 El sistema de notificaciones se inicializa automáticamente en notifications.js
});
</script>
