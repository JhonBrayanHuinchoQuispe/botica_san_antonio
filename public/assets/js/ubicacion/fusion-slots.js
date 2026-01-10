// ===============================================
// FUSIÓN DE SLOTS - SISTEMA DE SELECCIÓN DIRECTA
// ===============================================

class FusionSlotsDirecta {
    constructor() {
        this.modoFusion = false;
        this.slotsSeleccionados = [];
        this.panelControl = null;
        this.contadorSlots = null;
        this.btnConfirmar = null;
        this.textoConfirmar = null;
        
        this.init();
    }

    init() {
        console.log('🔧 Inicializando sistema de fusión directa...');
        
        this.panelControl = document.getElementById('panelControlFusion');
        this.contadorSlots = document.getElementById('slotsSeleccionados');
        this.btnConfirmar = document.getElementById('btnConfirmarFusionDirecta');
        this.textoConfirmar = document.getElementById('textoConfirmarFusion');
        
        // Debug: verificar elementos
        console.log('🔍 Elementos encontrados:');
        console.log('- panelControl:', this.panelControl);
        console.log('- contadorSlots:', this.contadorSlots);
        console.log('- btnConfirmar:', this.btnConfirmar);
        console.log('- textoConfirmar:', this.textoConfirmar);
        
        this.configurarEventos();
        this.configurarEventosSeparacion();
        
        console.log('✅ Sistema de fusión directa listo');
    }

    configurarEventos() {
        // Botón principal de fusión
        const btnFusion = document.getElementById('btnIniciarFusion');
        
        if (btnFusion) {
            // Event listener principal
            btnFusion.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                if (this.modoFusion) {
                    this.cancelarFusion();
                } else {
                    this.iniciarModoFusion();
                }
            });
            
            console.log('✅ Event listener configurado para botón de fusión');
        } else {
            console.error('❌ No se encontró el botón de fusión');
        }

        // Event delegation para selección de slots
        document.addEventListener('click', (e) => {
            const slot = e.target.closest('.slot-container.vacio');
            if (slot && this.modoFusion) {
                // Verificar si el slot ya está fusionado
                if (slot.classList.contains('fusionado')) {
                    this.mostrarNotificacion('warning', 'No puedes seleccionar un slot que ya está fusionado');
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                this.toggleSeleccionSlot(slot);
            }
        });

        // Botón cancelar fusión
        const btnCancelar = document.getElementById('btnCancelarFusion');
        if (btnCancelar) {
            btnCancelar.addEventListener('click', () => {
                this.cancelarFusion();
            });
        }

        // Botón confirmar fusión
        if (this.btnConfirmar) {
            this.btnConfirmar.addEventListener('click', () => {
                this.confirmarFusion();
            });
        }

        // Cerrar con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modoFusion) {
                this.cancelarFusion();
            }
        });
    }

    iniciarModoFusion() {
        this.modoFusion = true;
        this.slotsSeleccionados = [];
        
        // Cambiar estado del botón
        const btnFusion = document.getElementById('btnIniciarFusion');
        if (btnFusion) {
            btnFusion.classList.add('fusion-activa');
            btnFusion.innerHTML = `
                <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                <span>Cancelar Fusión</span>
            `;
        }
        
        // Mostrar panel de control
        if (this.panelControl) {
            this.panelControl.classList.remove('hidden');
        }
        
        // Agregar clase modo-fusion a todos los slots vacíos
        const slotsVacios = document.querySelectorAll('.slot-container.vacio');
        slotsVacios.forEach(slot => {
            slot.classList.add('modo-fusion');
        });
        
        // Actualizar interfaz
        this.actualizarContador();
        this.actualizarBotonConfirmar();
    }

    toggleSeleccionSlot(slot) {
        const slotId = slot.dataset.slot;
        
        if (this.estaSeleccionado(slotId)) {
            // Deseleccionar
            this.deseleccionarSlot(slot, slotId);
        } else {
            // Seleccionar
            this.seleccionarSlot(slot, slotId);
        }
        
        this.actualizarContador();
        this.actualizarBotonConfirmar();
    }

    seleccionarSlot(slot, slotId) {
        console.log('➕ Seleccionando slot:', slotId);
        
        slot.classList.add('fusion-seleccionado');
        this.slotsSeleccionados.push(slotId);
    }

    deseleccionarSlot(slot, slotId) {
        console.log('➖ Deseleccionando slot:', slotId);
        
        slot.classList.remove('fusion-seleccionado');
        this.slotsSeleccionados = this.slotsSeleccionados.filter(id => id !== slotId);
    }

    estaSeleccionado(slotId) {
        return this.slotsSeleccionados.includes(slotId);
    }



    actualizarContador() {
        if (this.contadorSlots) {
            this.contadorSlots.textContent = this.slotsSeleccionados.length;
        }
    }

    actualizarBotonConfirmar() {
        if (!this.btnConfirmar || !this.textoConfirmar) return;

        const cantidad = this.slotsSeleccionados.length;
        
        if (cantidad >= 2) {
            this.btnConfirmar.disabled = false;
            
            let tipoFusion = this.determinarTipoFusion();
            let descripcion = this.obtenerDescripcionTipoFusion(tipoFusion);
            
            this.textoConfirmar.textContent = `Fusionar ${cantidad} Slots (${descripcion})`;
        } else {
            this.btnConfirmar.disabled = true;
            this.textoConfirmar.textContent = `Fusionar Slots (mínimo 2)`;
        }
    }

    determinarTipoFusion() {
        const cantidad = this.slotsSeleccionados.length;
        
        if (cantidad === 2) {
            return this.esFusionVertical() ? 'vertical-2' : 'horizontal-2';
        } else if (cantidad === 3) {
            return this.esLineaHorizontal() ? 'horizontal-3' : 'personalizada';
        } else if (cantidad === 4) {
            if (this.esFusionCuadrada()) {
                return 'cuadrado-2x2';
            } else if (this.esLineaHorizontal()) {
                return 'horizontal-4';
            } else {
                return 'personalizada';
            }
        }
        
        return 'personalizada';
    }

    esFusionVertical() {
        if (this.slotsSeleccionados.length !== 2) return false;
        
        const [slot1, slot2] = this.slotsSeleccionados.map(id => {
            const [nivel, posicion] = id.split('-').map(Number);
            return { nivel, posicion };
        });
        
        // Vertical: misma posición, niveles consecutivos
        return slot1.posicion === slot2.posicion && 
               Math.abs(slot1.nivel - slot2.nivel) === 1;
    }

    esFusionCuadrada() {
        if (this.slotsSeleccionados.length !== 4) return false;
        
        const slots = this.slotsSeleccionados.map(id => {
            const [nivel, posicion] = id.split('-').map(Number);
            return { nivel, posicion, id };
        });
        
        // Ordenar por nivel y posición
        slots.sort((a, b) => a.nivel - b.nivel || a.posicion - b.posicion);
        
        // Verificar si forman un cuadrado 2x2
        const [s1, s2, s3, s4] = slots;
        
        return s1.nivel === s2.nivel && 
               s3.nivel === s4.nivel && 
               s1.nivel + 1 === s3.nivel &&
               s1.posicion + 1 === s2.posicion &&
               s3.posicion + 1 === s4.posicion &&
               s1.posicion === s3.posicion &&
               s2.posicion === s4.posicion;
    }

    esLineaHorizontal() {
        if (this.slotsSeleccionados.length < 2) return false;
        
        const slots = this.slotsSeleccionados.map(id => {
            const [nivel, posicion] = id.split('-').map(Number);
            return { nivel, posicion };
        });
        
        // Verificar que todos estén en el mismo nivel
        const primerNivel = slots[0].nivel;
        if (!slots.every(slot => slot.nivel === primerNivel)) return false;
        
        // Ordenar por posición
        slots.sort((a, b) => a.posicion - b.posicion);
        
        // Verificar que las posiciones sean consecutivas
        for (let i = 1; i < slots.length; i++) {
            if (slots[i].posicion !== slots[i-1].posicion + 1) {
                return false;
            }
        }
        
        return true;
    }

    obtenerDescripcionTipoFusion(tipo) {
        const descripciones = {
            'horizontal-2': '2H',
            'horizontal-3': '3H',
            'vertical-2': '2V',
            'cuadrado-2x2': '2×2',
            'horizontal-4': '4H',
            'personalizada': 'Custom'
        };
        
        return descripciones[tipo] || 'Custom';
    }

    cancelarFusion() {
        console.log('❌ Cancelando fusión...');
        
        this.modoFusion = false;
        this.slotsSeleccionados = [];
        
        // Restaurar estado del botón
        const btnFusion = document.getElementById('btnIniciarFusion');
        if (btnFusion) {
            btnFusion.classList.remove('fusion-activa');
            btnFusion.innerHTML = `
                <iconify-icon icon="solar:widget-2-bold-duotone"></iconify-icon>
                <span>Fusionar Slots</span>
            `;
        }
        
        // Ocultar panel
        if (this.panelControl) {
            this.panelControl.classList.add('hidden');
        }
        
        // Limpiar selecciones visuales y modo fusion
        document.querySelectorAll('.slot-container.fusion-seleccionado').forEach(slot => {
            slot.classList.remove('fusion-seleccionado');
        });
        
        document.querySelectorAll('.slot-container.modo-fusion').forEach(slot => {
            slot.classList.remove('modo-fusion');
        });
    }

    async confirmarFusion() {
        if (this.slotsSeleccionados.length < 2) {
            this.mostrarNotificacion('warning', 'Debes seleccionar al menos 2 slots');
            return;
        }

        console.log('✅ Confirmando fusión de slots:', this.slotsSeleccionados);

        try {
            // Mostrar loading
            this.btnConfirmar.disabled = true;
            this.btnConfirmar.innerHTML = `
                <iconify-icon icon="solar:loading-bold" style="animation: spin 1s linear infinite;"></iconify-icon>
                Fusionando...
            `;

            // Determinar el slot principal (el primero seleccionado)
            const slotPrincipal = this.slotsSeleccionados[0];
            const tipoFusion = this.determinarTipoFusion();

            // Llamar a la API
            const resultado = await this.enviarFusionAPI(slotPrincipal, tipoFusion);
            
            if (resultado.success) {
                this.mostrarNotificacion('success', 'Slots fusionados correctamente');
                
                // Recargar la página después de un breve delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                
            } else {
                throw new Error(resultado.message || 'Error al fusionar slots');
            }

        } catch (error) {
            console.error('❌ Error al fusionar slots:', error);
            this.mostrarNotificacion('error', 'Error al fusionar slots: ' + error.message);
            
            // Restaurar botón
            this.actualizarBotonConfirmar();
        }
    }

    async enviarFusionAPI(slotPrincipal, tipoFusion) {
        const url = `/api/ubicaciones/fusionar-slots`;
        
        // Obtener estante_id del primer slot seleccionado
        const primerSlot = document.querySelector(`[data-slot="${slotPrincipal}"]`);
        const estanteId = primerSlot ? primerSlot.dataset.estanteId : null;
        
        if (!estanteId) {
            throw new Error('No se pudo determinar el ID del estante');
        }
        
        const datos = {
            estante_id: parseInt(estanteId),
            slot_origen: slotPrincipal,
            tipo_fusion: tipoFusion,
            slots_seleccionados: this.slotsSeleccionados
        };

        console.log('📤 Enviando datos de fusión:', datos);

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(datos)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    mostrarNotificacion(tipo, mensaje) {
        if (typeof Swal !== 'undefined') {
            const config = {
                title: tipo === 'success' ? '¡Éxito!' : tipo === 'warning' ? 'Atención' : tipo === 'info' ? 'Información' : 'Error',
                text: mensaje,
                icon: tipo,
                confirmButtonText: 'Entendido',
                timer: tipo === 'success' || tipo === 'info' ? 3000 : undefined,
                toast: tipo === 'info',
                position: tipo === 'info' ? 'top-end' : 'center'
            };

            Swal.fire(config);
        } else {
            alert(mensaje);
        }
    }

    configurarEventosSeparacion() {
        // Event delegation para botones de separar fusión
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-separar-fusionado')) {
                e.preventDefault();
                e.stopPropagation();
                
                const button = e.target.closest('.btn-separar-fusionado');
                const ubicacionId = button.dataset.ubicacionId;
                const slot = button.dataset.slot;
                
                this.confirmarSeparacion(ubicacionId, slot);
            }
        });
    }

    async confirmarSeparacion(ubicacionId, slot) {
        try {
            const result = await Swal.fire({
                title: '¿Separar fusión?',
                text: `¿Estás seguro de que quieres separar la fusión del slot ${slot}? Los slots volverán a ser independientes.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, separar',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                await this.separarFusion(ubicacionId);
            }
        } catch (error) {
            console.error('Error en confirmación de separación:', error);
        }
    }

    async separarFusion(ubicacionId) {
        try {
            console.log('🔄 Separando fusión de ubicación:', ubicacionId);

            const response = await fetch('/api/ubicaciones/separar-slots', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    ubicacion_id: parseInt(ubicacionId)
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();

            if (result.success) {
                this.mostrarNotificacion('success', result.message || 'Fusión separada correctamente');
                
                // Recargar la página después de un breve delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                
            } else {
                throw new Error(result.message || 'Error al separar la fusión');
            }

        } catch (error) {
            console.error('❌ Error al separar fusión:', error);
            this.mostrarNotificacion('error', 'Error al separar fusión: ' + error.message);
        }
    }
}

// Función de prueba global
window.probarFusion = function() {
    console.log('🧪 Probando sistema de fusión...');
    
    if (window.fusionSlotsDirecta) {
        console.log('✅ Sistema de fusión disponible');
        console.log('Estado actual:', {
            modoFusion: window.fusionSlotsDirecta.modoFusion,
            slotsSeleccionados: window.fusionSlotsDirecta.slotsSeleccionados,
            panelControl: !!window.fusionSlotsDirecta.panelControl,
            btnConfirmar: !!window.fusionSlotsDirecta.btnConfirmar
        });
        
        // Intentar iniciar modo fusión
        if (!window.fusionSlotsDirecta.modoFusion) {
            console.log('🎯 Iniciando modo fusión de prueba...');
            window.fusionSlotsDirecta.iniciarModoFusion();
        } else {
            console.log('🔄 Cancelando modo fusión...');
            window.fusionSlotsDirecta.cancelarFusion();
        }
    } else {
        console.error('❌ Sistema de fusión no disponible');
    }
};

// Función para verificar elementos del DOM
window.verificarElementosFusion = function() {
    console.log('🔍 Verificando elementos del DOM...');
    
    const elementos = {
        btnIniciarFusion: document.getElementById('btnIniciarFusion'),
        panelControlFusion: document.getElementById('panelControlFusion'),
        slotsSeleccionados: document.getElementById('slotsSeleccionados'),
        btnConfirmarFusionDirecta: document.getElementById('btnConfirmarFusionDirecta'),
        textoConfirmarFusion: document.getElementById('textoConfirmarFusion'),
        btnCancelarFusion: document.getElementById('btnCancelarFusion')
    };
    
    console.log('📋 Elementos encontrados:');
    Object.entries(elementos).forEach(([nombre, elemento]) => {
        if (elemento) {
            console.log(`✅ ${nombre}:`, elemento);
            if (elemento.classList.contains('hidden')) {
                console.log(`   ⚠️ Elemento tiene clase 'hidden'`);
            }
            if (elemento.style.display === 'none') {
                console.log(`   ⚠️ Elemento tiene display: none`);
            }
        } else {
            console.error(`❌ ${nombre}: NO ENCONTRADO`);
        }
    });
    
    // Verificar slots vacíos
    const slotsVacios = document.querySelectorAll('.slot-container.vacio');
    console.log(`🎯 Slots vacíos encontrados: ${slotsVacios.length}`);
    
    return elementos;
};

// Función para simular click en el botón
window.simularClickFusion = function() {
    console.log('🖱️ Simulando click en botón de fusión...');
    
    const btnFusion = document.getElementById('btnIniciarFusion');
    if (btnFusion) {
        console.log('✅ Botón encontrado, simulando click...');
        
        // Simular diferentes tipos de eventos
        btnFusion.click();
        
        // También disparar evento personalizado
        const evento = new MouseEvent('click', {
            bubbles: true,
            cancelable: true,
            view: window
        });
        btnFusion.dispatchEvent(evento);
        
        console.log('🎯 Eventos de click disparados');
    } else {
        console.error('❌ Botón no encontrado');
    }
};

// Función para activar fusión directamente
window.activarFusionDirecta = function() {
    console.log('🚀 Activando fusión directamente...');
    
    if (window.fusionSlotsDirecta) {
        if (!window.fusionSlotsDirecta.modoFusion) {
            window.fusionSlotsDirecta.iniciarModoFusion();
            console.log('✅ Fusión activada directamente');
        } else {
            console.log('⚠️ La fusión ya está activa');
        }
    } else {
        console.error('❌ Sistema de fusión no disponible');
    }
};

// Función de inicialización robusta
function inicializarSistemaFusion() {
    try {
        if (!window.fusionSlotsDirecta) {
            console.log('🚀 Inicializando sistema de fusión...');
            window.fusionSlotsDirecta = new FusionSlotsDirecta();
            console.log('✅ Sistema de fusión inicializado correctamente');
        }
    } catch (error) {
        console.error('❌ Error al inicializar sistema de fusión:', error);
    }
}

// Múltiples puntos de inicialización para asegurar que funcione
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarSistemaFusion);
} else {
    inicializarSistemaFusion();
}

// Backup adicional
window.addEventListener('load', inicializarSistemaFusion);

// CSS de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);