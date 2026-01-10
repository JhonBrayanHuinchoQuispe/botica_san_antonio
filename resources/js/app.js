import './bootstrap';
import '@iconify/iconify';

// Importar Turbo para navegación sin recargas (Turbo 8 syntax)
import * as Turbo from '@hotwired/turbo';

// Hacer Turbo disponible globalmente
window.Turbo = Turbo;

// Configurar Alpine.js
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';

// Registrar plugins de Alpine
Alpine.plugin(persist);
Alpine.plugin(focus);

// Componente de búsqueda instantánea
Alpine.data('busquedaInstantanea', () => ({
    termino: '',
    resultados: [],
    cargando: false,
    mostrarResultados: false,
    timeoutId: null,

    init() {
        this.$watch('termino', (value) => {
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
            }
            
            if (value.length >= 2) {
                this.timeoutId = setTimeout(() => {
                    this.buscar();
                }, 300); // Debounce de 300ms
            } else {
                this.resultados = [];
                this.mostrarResultados = false;
            }
        });
    },

    async buscar() {
        if (this.termino.length < 2) return;
        
        this.cargando = true;
        
        try {
            const response = await fetch(`/api/buscar-productos?q=${encodeURIComponent(this.termino)}&limit=20`, {
                headers: {
                    'Authorization': `Bearer ${document.querySelector('meta[name="api-token"]')?.content || ''}`,
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.resultados = data.data || [];
                this.mostrarResultados = true;
            }
        } catch (error) {
            console.error('Error en búsqueda:', error);
        } finally {
            this.cargando = false;
        }
    },

    seleccionarProducto(producto) {
        // Emitir evento personalizado para que otros componentes puedan escuchar
        this.$dispatch('producto-seleccionado', producto);
        this.limpiar();
    },

    limpiar() {
        this.termino = '';
        this.resultados = [];
        this.mostrarResultados = false;
    }
}));

// Componente para modales
Alpine.data('modal', (initialOpen = false) => ({
    open: initialOpen,
    
    toggle() {
        this.open = !this.open;
    },
    
    close() {
        this.open = false;
    }
}));

// Componente para notificaciones
Alpine.data('notificacion', () => ({
    mostrar: false,
    mensaje: '',
    tipo: 'success', // success, error, warning, info
    
    show(mensaje, tipo = 'success', duracion = 3000) {
        this.mensaje = mensaje;
        this.tipo = tipo;
        this.mostrar = true;
        
        setTimeout(() => {
            this.hide();
        }, duracion);
    },
    
    hide() {
        this.mostrar = false;
    }
}));

// Inicializar Alpine
window.Alpine = Alpine;
Alpine.start();

// Eventos de Turbo para mantener Alpine funcionando
document.addEventListener('turbo:load', () => {
    // Re-inicializar Alpine si es necesario
    if (window.Alpine && !window.Alpine.version) {
        Alpine.start();
    }
});

// Preservar el scroll en navegación
document.addEventListener('turbo:before-visit', () => {
    // Guardar posición del scroll si es necesario
});

// Mostrar indicador de carga opcional
document.addEventListener('turbo:visit', () => {
    // Opcional: mostrar spinner de carga
});

document.addEventListener('turbo:render', () => {
    // Página renderizada, ocultar spinner si existe
});
