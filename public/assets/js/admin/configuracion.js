console.log('✅ Configuración del Sistema - JavaScript cargado');

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando Configuración del Sistema');
    
    // Configurar eventos
    configurarFormulario();
    configurarEventosAutorizacion();
    
    console.log('✅ Configuración del Sistema inicializada correctamente');
});

// Configurar el formulario principal
function configurarFormulario() {
    const form = document.getElementById('formConfiguracion');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarConfiguracion();
        });
    }
}

// Configurar eventos de autorización de descuento
function configurarEventosAutorizacion() {
    const selectAutorizacion = document.getElementById('requiere_autorizacion_descuento');
    const inputMaxSinAutorizacion = document.getElementById('descuento_sin_autorizacion_max');
    
    if (selectAutorizacion && inputMaxSinAutorizacion) {
        selectAutorizacion.addEventListener('change', function() {
            inputMaxSinAutorizacion.disabled = this.value === '0';
            if (this.value === '0') {
                inputMaxSinAutorizacion.value = '0';
            }
        });
    }
}

// Guardar configuración
async function guardarConfiguracion() {
    const form = document.getElementById('formConfiguracion');
    const formData = new FormData(form);
    
    // Convertir valores booleanos
    const booleanFields = [
        'igv_habilitado', 
        'descuentos_habilitados', 
        'requiere_autorizacion_descuento', 
        'promociones_habilitadas',
        'imprimir_automatico'
    ];
    
    booleanFields.forEach(field => {
        formData.set(field, formData.get(field) === '1' ? true : false);
    });
    
    // Mostrar loading
    Swal.fire({
        title: 'Guardando Configuración',
        html: `
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 3em; margin-bottom: 16px;">⚙️</div>
                <p style="color: #6b7280;">Actualizando configuración del sistema...</p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        const response = await fetch('/admin/configuracion/actualizar', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(Object.fromEntries(formData))
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Configuración Actualizada!',
                text: data.message,
                confirmButtonColor: '#059669',
                timer: 2000,
                timerProgressBar: true
            }).then(() => {
                // Actualizar dinámicamente sin recargar la página
                actualizarConfiguracionEnPantalla();
            });
        } else {
            throw new Error(data.message || 'Error al guardar configuración');
        }
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al guardar la configuración',
            confirmButtonColor: '#dc2626'
        });
    }
}

// Función para restablecer valores predeterminados
function restablecerConfiguracion() {
    Swal.fire({
        title: '¿Restablecer Configuración?',
        text: "Se restablecerán todos los valores a su configuración predeterminada",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, restablecer',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            restablecerValoresPredeterminados();
        }
    });
} 

/* ==============================================
   FUNCIONES AUXILIARES PARA ACTUALIZACIÓN DINÁMICA
   ============================================== */

/**
 * Actualizar configuración en pantalla sin recargar
 */
function actualizarConfiguracionEnPantalla() {
    // Mostrar mensaje de éxito visual
    const form = document.getElementById('configuracion-form');
    if (form) {
        // Agregar clase de éxito temporal
        form.classList.add('config-updated');
        setTimeout(() => {
            form.classList.remove('config-updated');
        }, 3000);
    }
    
    // Actualizar indicadores visuales si existen
    const saveButton = document.querySelector('button[type="submit"]');
    if (saveButton) {
        const originalText = saveButton.innerHTML;
        saveButton.innerHTML = '<i class="fas fa-check"></i> Guardado';
        saveButton.disabled = true;
        
        setTimeout(() => {
            saveButton.innerHTML = originalText;
            saveButton.disabled = false;
        }, 2000);
    }
    
    console.log('✅ Configuración actualizada dinámicamente');
}

/**
 * Restablecer valores predeterminados sin recargar
 */
function restablecerValoresPredeterminados() {
    // Valores predeterminados del sistema
    const valoresPredeterminados = {
        'nombre_empresa': 'Mi Empresa',
        'direccion_empresa': '',
        'telefono_empresa': '',
        'email_empresa': '',
        'moneda_predeterminada': 'PEN',
        'zona_horaria': 'America/Lima',
        'idioma_sistema': 'es',
        'formato_fecha': 'dd/mm/yyyy',
        'decimales_precios': '2',
        'iva_predeterminado': '18',
        'backup_automatico': false,
        'notificaciones_email': true,
        'modo_mantenimiento': false
    };
    
    // Actualizar campos del formulario
    Object.keys(valoresPredeterminados).forEach(campo => {
        const elemento = document.getElementById(campo) || document.querySelector(`[name="${campo}"]`);
        if (elemento) {
            if (elemento.type === 'checkbox') {
                elemento.checked = valoresPredeterminados[campo];
            } else {
                elemento.value = valoresPredeterminados[campo];
            }
        }
    });
    
    // Mostrar mensaje de confirmación
    Swal.fire({
        icon: 'success',
        title: '¡Configuración Restablecida!',
        text: 'Se han restaurado los valores predeterminados',
        confirmButtonColor: '#059669',
        timer: 2000,
        timerProgressBar: true
    });
    
    console.log('✅ Configuración restablecida a valores predeterminados');
}