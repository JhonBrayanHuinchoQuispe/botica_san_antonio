// ========================================
// FIX INMEDIATO DRAG AND DROP
// ========================================

console.log('%c🚀 FIX INMEDIATO CARGADO', 'background: #10b981; color: white; padding: 8px 12px; border-radius: 6px; font-weight: bold;');

// SOLUCIÓN INMEDIATA
document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
            console.log('🔧 Aplicando FIX inmediato...');
            
            // FORZAR DRAGGABLE EN TODOS LOS PRODUCTOS
            const productos = document.querySelectorAll('.slot-container.ocupado');
            console.log(`📦 Productos encontrados: ${productos.length}`);
            
            if (productos.length === 0) {
                console.log('⚠️  No se encontraron productos. Intentando nuevamente en 2 segundos...');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
                return;
            }
            
            productos.forEach((slot, index) => {
                // Forzar draggable
                slot.setAttribute('draggable', 'true');
                slot.style.cursor = 'grab';
                
                // Solo logear el primero y último para no spam
                if (index === 0 || index === productos.length - 1) {
                    console.log(`✅ Producto ${index + 1} (${slot.dataset.slot}) configurado para drag`);
                }
            });
        
        // VARIABLES GLOBALES
        let draggedElement = null;
        
        // EVENTOS DRAG AND DROP SIMPLIFICADOS
        
        // DRAG START
        productos.forEach(slot => {
            slot.addEventListener('dragstart', function(e) {
                // Evitar drag desde botones
                if (e.target.closest('.btn-slot-accion')) {
                    e.preventDefault();
                    return;
                }
                
                console.log('🎯 DRAG START:', this.dataset.slot);
                draggedElement = this;
                this.style.opacity = '0.5';
                
                // Marcar otros productos como válidos para drop
                productos.forEach(otherSlot => {
                    if (otherSlot !== this) {
                        otherSlot.classList.add('drop-zone');
                    }
                });
            });
            
            slot.addEventListener('dragend', function(e) {
                console.log('🏁 DRAG END');
                this.style.opacity = '1';
                draggedElement = null;
                
                // Limpiar clases
                productos.forEach(otherSlot => {
                    otherSlot.classList.remove('drop-zone', 'drag-over');
                });
            });
            
            slot.addEventListener('dragover', function(e) {
                e.preventDefault();
                if (this !== draggedElement && draggedElement) {
                    this.classList.add('drag-over');
                }
            });
            
            slot.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
                         slot.addEventListener('drop', async function(e) {
                 e.preventDefault();
                 
                 // VERIFICACIÓN ROBUSTA PARA EVITAR ERRORES
                 if (this === draggedElement || !draggedElement) {
                     console.log('❌ Drop inválido');
                     return;
                 }
                 
                 // CAPTURAR DATOS INMEDIATAMENTE ANTES DE QUE SE PIERDAN
                 const slot1Data = {
                     nombre: draggedElement?.dataset?.productoNombre || 'Producto desconocido',
                     slot: draggedElement?.dataset?.slot || '',
                     productoId: draggedElement?.dataset?.productoId || '',
                     ubicacionId: draggedElement?.dataset?.ubicacionId || ''
                 };
                 
                 const slot2Data = {
                     nombre: this?.dataset?.productoNombre || 'Producto desconocido',
                     slot: this?.dataset?.slot || '',
                     productoId: this?.dataset?.productoId || '',
                     ubicacionId: this?.dataset?.ubicacionId || ''
                 };
                 
                 console.log(`✅ DROP: ${slot1Data.slot} → ${slot2Data.slot}`);
                 
                 // Limpiar clases
                 this.classList.remove('drag-over');
                 productos.forEach(s => s.classList.remove('drop-zone'));
                 
                 // VALIDAR DATOS ANTES DE CONTINUAR
                 if (!slot1Data.productoId || !slot1Data.ubicacionId || !slot2Data.productoId || !slot2Data.ubicacionId) {
                     console.error('❌ Datos incompletos:', { slot1Data, slot2Data });
                     await Swal.fire({
                         title: 'Error de Datos',
                         text: 'No se pudieron obtener todos los datos necesarios. Recarga la página e inténtalo nuevamente.',
                         icon: 'error',
                         confirmButtonText: 'Recargar',
                         confirmButtonColor: '#ef4444'
                     }).then(() => {
                         window.location.reload();
                     });
                     return;
                 }
                 
                 // MOSTRAR MODAL PROFESIONAL Y SOBRIO
                 const confirmacion = await Swal.fire({
                     title: '¿Intercambiar Productos?',
                     html: `
                         <div style="padding: 25px 20px;">
                             <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin: 20px 0;">
                                 <!-- Producto 1 -->
                                 <div style="background: #f8fafc; border: 2px solid #e2e8f0; padding: 18px; border-radius: 12px; text-align: center; min-width: 130px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                                     <div style="font-size: 20px; margin-bottom: 8px; color: #64748b;">💊</div>
                                     <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px; color: #1e293b;">${slot1Data.nombre}</div>
                                     <div style="font-size: 12px; color: #64748b;">Ubicación: ${slot1Data.slot}</div>
                                 </div>
                                 
                                 <!-- Icono de intercambio -->
                                 <div style="background: #10b981; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                                     <div style="color: white; font-size: 24px; font-weight: bold;">⇄</div>
                                 </div>
                                 
                                 <!-- Producto 2 -->
                                 <div style="background: #f8fafc; border: 2px solid #e2e8f0; padding: 18px; border-radius: 12px; text-align: center; min-width: 130px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                                     <div style="font-size: 20px; margin-bottom: 8px; color: #64748b;">💊</div>
                                     <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px; color: #1e293b;">${slot2Data.nombre}</div>
                                     <div style="font-size: 12px; color: #64748b;">Ubicación: ${slot2Data.slot}</div>
                                 </div>
                             </div>
                             
                             <!-- Mensaje informativo sobrio -->
                             <div style="background: #e0f2fe; border: 1px solid #b3e5fc; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center;">
                                 <div style="color: #0277bd; font-weight: 600; font-size: 14px; margin-bottom: 4px;">
                                     <span style="font-size: 16px;">ℹ️</span> Confirmación de Intercambio
                                 </div>
                                 <div style="color: #01579b; font-size: 13px;">
                                     Los productos cambiarán de ubicación en el almacén
                                 </div>
                             </div>
                         </div>
                     `,
                     showCancelButton: true,
                     showDenyButton: false,
                     showCloseButton: false,
                     confirmButtonText: 'Sí, Intercambiar',
                     cancelButtonText: 'Cancelar',
                     denyButtonText: '',
                     allowOutsideClick: false,
                     allowEscapeKey: true,
                     reverseButtons: false,
                     focusConfirm: true,
                     customClass: {
                         popup: 'modal-intercambio-sobrio',
                         confirmButton: 'btn-confirmar-sobrio',
                         cancelButton: 'btn-cancelar-sobrio',
                         actions: 'swal2-actions-clean'
                     },
                     buttonsStyling: false,
                     width: '480px',
                     didOpen: () => {
                         // LIMPIAR BOTONES EXTRA DESPUÉS DE RENDERIZAR
                         setTimeout(() => {
                             const actions = document.querySelector('.swal2-actions');
                             if (actions) {
                                 // Eliminar solo botones específicos problemáticos
                                 const buttons = actions.querySelectorAll('button');
                                 buttons.forEach((btn) => {
                                     const text = btn.textContent?.trim().toLowerCase();
                                     // Eliminar solo botones "No" pero mantener "Cancelar" y "Sí, Intercambiar"
                                     if (text === 'no' || text === 'deny' || btn.classList.contains('swal2-deny')) {
                                         btn.remove();
                                     }
                                 });
                                 
                                 // Asegurar que tenemos exactamente 2 botones: Confirmar y Cancelar
                                 const remainingButtons = actions.querySelectorAll('button');
                                 if (remainingButtons.length > 2) {
                                     // Si hay más de 2, eliminar los extras que no sean confirm ni cancel
                                     remainingButtons.forEach((btn, index) => {
                                         if (!btn.classList.contains('swal2-confirm') && 
                                             !btn.classList.contains('swal2-cancel') && 
                                             index >= 2) {
                                             btn.remove();
                                         }
                                     });
                                 }
                             }
                         }, 10);
                     }
                 });
                 
                 if (!confirmacion.isConfirmed) {
                     console.log('❌ Intercambio cancelado');
                     return;
                 }
                 
                 // MOSTRAR LOADING
                 Swal.fire({
                     title: 'Intercambiando Productos...',
                     html: `
                         <div style="text-align: center; padding: 20px;">
                             <div style="color: #10b981; font-size: 48px; margin-bottom: 15px;">
                                 <iconify-icon icon="solar:refresh-bold-duotone" class="rotating"></iconify-icon>
                             </div>
                             <div style="font-weight: 500; margin-bottom: 10px;">Procesando intercambio...</div>
                             <div style="color: #6b7280; font-size: 0.9em;">Por favor espera un momento</div>
                         </div>
                     `,
                     allowOutsideClick: false,
                     allowEscapeKey: false,
                     showConfirmButton: false
                 });
                 
                 try {
                     // PREPARAR DATOS CON VALIDACIÓN EXTRA
                     const intercambioData = {
                         slot1_codigo: slot1Data.slot,
                         slot1_producto_id: parseInt(slot1Data.productoId),
                         slot1_ubicacion_id: parseInt(slot1Data.ubicacionId),
                         slot2_codigo: slot2Data.slot,
                         slot2_producto_id: parseInt(slot2Data.productoId),
                         slot2_ubicacion_id: parseInt(slot2Data.ubicacionId),
                         estante_id: parseInt(window.estanteActual) || 1
                     };
                     
                     console.log('📤 Enviando intercambio...');
                     
                     // VERIFICAR TOKEN CSRF
                     const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                     if (!csrfToken) {
                         throw new Error('Token CSRF no encontrado');
                     }
                     
                     // ENVIAR A LA API CON MEJOR MANEJO DE ERRORES
                     const response = await fetch('/api/ubicaciones/drag-drop-intercambio', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': csrfToken,
                             'Accept': 'application/json'
                         },
                         body: JSON.stringify(intercambioData)
                     });
                     
                     const result = await response.json();
                     console.log('📊 Respuesta recibida:', result.success ? 'Éxito' : 'Error');
                     
                     if (!response.ok || !result.success) {
                         throw new Error(result.message || `Error HTTP ${response.status}`);
                     }
                     
                     // MOSTRAR ÉXITO (SIN BOTONES)
                     await Swal.fire({
                         title: '¡Intercambio Exitoso!',
                         html: `
                             <div style="text-align: center; padding: 20px;">
                                 <div style="color: #10b981; font-size: 64px; margin-bottom: 20px;">
                                     <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                                 </div>
                                 <div style="font-weight: 600; color: #1f2937; margin-bottom: 15px; font-size: 1.1em;">
                                     Los productos se intercambiaron correctamente
                                 </div>
                                 <div style="background: #f0fdf4; padding: 15px; border-radius: 10px; border: 1px solid #bbf7d0;">
                                     <div style="font-weight: 500; color: #15803d;">
                                         <iconify-icon icon="solar:database-bold-duotone" style="margin-right: 8px;"></iconify-icon>
                                         Cambios guardados en la base de datos
                                     </div>
                                 </div>
                             </div>
                         `,
                         showConfirmButton: false,
                         showCancelButton: false,
                         showCloseButton: false,
                         allowOutsideClick: false,
                         allowEscapeKey: false,
                         customClass: {
                             popup: 'modal-intercambio-sobrio'
                         },
                         timer: 2500,
                         timerProgressBar: true
                     });
                     
                     // RECARGAR
                     window.location.reload();
                     
                 } catch (error) {
                     console.error('❌ Error:', error);
                     
                     await Swal.fire({
                         title: 'Error en el Intercambio',
                         text: `Error: ${error.message}. Verifica tu conexión e inténtalo nuevamente.`,
                         icon: 'error',
                         confirmButtonText: 'Entendido',
                         confirmButtonColor: '#ef4444'
                     });
                 }
             });
        });
        
                 // AGREGAR ESTILOS PROFESIONALES
         const style = document.createElement('style');
         style.textContent = `
             /* Estilos para drag and drop */
             .slot-container.ocupado[draggable="true"] {
                 cursor: grab !important;
             }
             .slot-container.ocupado[draggable="true"]:active {
                 cursor: grabbing !important;
             }
             .slot-container.drop-zone {
                 border: 2px dashed #10b981 !important;
                 background: rgba(16, 185, 129, 0.1) !important;
             }
             .slot-container.drag-over {
                 border: 2px solid #10b981 !important;
                 background: rgba(16, 185, 129, 0.2) !important;
                 transform: scale(1.02) !important;
             }
             .rotating {
                 animation: rotate 2s linear infinite;
             }
             @keyframes rotate {
                 from { transform: rotate(0deg); }
                 to { transform: rotate(360deg); }
             }
             
             /* Estilos para modal sobrio */
             .modal-intercambio-sobrio {
                 font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
                 border-radius: 16px !important;
                 border: none !important;
                 box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
             }
             
             .modal-intercambio-sobrio .swal2-title {
                 font-size: 22px !important;
                 font-weight: 600 !important;
                 color: #1e293b !important;
                 margin-bottom: 8px !important;
             }
             
             /* Botones sobrios y profesionales */
             .btn-confirmar-sobrio {
                 background: #10b981 !important;
                 border: none !important;
                 border-radius: 8px !important;
                 padding: 12px 24px !important;
                 font-size: 15px !important;
                 font-weight: 600 !important;
                 color: white !important;
                 box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2) !important;
                 transition: all 0.2s ease !important;
                 margin: 0 6px !important;
                 cursor: pointer !important;
                 opacity: 1 !important;
                 visibility: visible !important;
                 display: inline-block !important;
             }
             
             .btn-confirmar-sobrio:hover {
                 background: #059669 !important;
                 transform: translateY(-1px) !important;
                 box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3) !important;
             }
             
             .btn-cancelar-sobrio {
                 background: #ef4444 !important;
                 border: none !important;
                 border-radius: 8px !important;
                 padding: 12px 24px !important;
                 font-size: 15px !important;
                 font-weight: 600 !important;
                 color: white !important;
                 box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2) !important;
                 transition: all 0.2s ease !important;
                 margin: 0 6px !important;
                 cursor: pointer !important;
                 opacity: 1 !important;
                 visibility: visible !important;
                 display: inline-block !important;
             }
             
             .btn-cancelar-sobrio:hover {
                 background: #dc2626 !important;
                 transform: translateY(-1px) !important;
                 box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3) !important;
             }
             
             /* Asegurar que los botones se vean en el modal */
             .swal2-actions {
                 margin-top: 30px !important;
                 gap: 15px !important;
             }
             
             .swal2-actions button {
                 opacity: 1 !important;
                 visibility: visible !important;
                 display: inline-block !important;
             }
             
             /* Ocultar botones extra como "No" */
             .swal2-actions .swal2-deny {
                 display: none !important;
                 visibility: hidden !important;
                 opacity: 0 !important;
             }
             
             .swal2-actions .swal2-close {
                 display: none !important;
                 visibility: hidden !important;
                 opacity: 0 !important;
             }
             
             /* Solo mostrar confirm y cancel */
             .swal2-actions-clean {
                 justify-content: center !important;
             }
             
             .swal2-actions-clean .swal2-confirm,
             .swal2-actions-clean .swal2-cancel {
                 display: inline-block !important;
                 visibility: visible !important;
                 opacity: 1 !important;
             }
             
             /* Mejorar el contenedor del modal */
             .swal2-html-container {
                 margin: 0 !important;
                 padding: 0 !important;
             }
         `;
        document.head.appendChild(style);
        
                 console.log('✅ FIX INMEDIATO APLICADO - DRAG AND DROP FUNCIONANDO');
         console.log(`🎯 ${productos.length} productos listos para arrastrar`);
        
    }, 3000);
});

console.log('%c🎯 SOLUCIÓN INMEDIATA CARGADA - Espera 3 segundos', 'background: #059669; color: white; padding: 8px 12px; border-radius: 6px; font-weight: bold;'); 