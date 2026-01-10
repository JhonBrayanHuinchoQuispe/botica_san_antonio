// ===============================================
// MODAL AGREGAR ESTANTE - FUNCIONALIDAD COMPLETA
// ===============================================

document.addEventListener("DOMContentLoaded", function() {
    console.log("🚀 Inicializando Modal Agregar Estante...");
    
    // Verificar que SweetAlert2 esté disponible
    if (typeof Swal === "undefined") {
        console.error("❌ SweetAlert2 no está disponible");
        return;
    }
    
    // Agregar estilos mejorados para validación
    agregarEstilosValidacion();
    
    initModalAgregarEstante();
});

function agregarEstilosValidacion() {
    // Crear estilos CSS para mejorar la apariencia de validación
    const style = document.createElement('style');
    style.textContent = `
        /* Estilos para campos de formulario */
        .form-group input:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            outline: none;
        }
        
        .form-group input {
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        /* Animación suave para mensajes de error */
        .error-message {
            animation: slideInError 0.3s ease-out;
        }
        
        @keyframes slideInError {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Mejoras visuales para el estado de error */
        .form-group.error label {
            color: #ef4444 !important;
            font-weight: 600;
        }
    `;
    
    // Agregar estilos al head si no existen
    if (!document.head.querySelector('style[data-validation-styles]')) {
        style.setAttribute('data-validation-styles', 'true');
        document.head.appendChild(style);
    }
}

function initModalAgregarEstante() {
    const btnNuevoEstante = document.getElementById("btnNuevoEstante");
    const modalNuevoEstante = document.getElementById("modalNuevoEstante");
    const formNuevoEstante = document.getElementById("formNuevoEstante");
    const btnGuardarNuevoEstante = document.getElementById("btnGuardarNuevoEstante");
    const btnCancelarNuevoEstante = document.getElementById("btnCancelarNuevoEstante");
    const btnCerrarModal = modalNuevoEstante?.querySelector(".modal-close-btn");

    console.log("📋 Elementos encontrados:", {
        btnNuevoEstante: !!btnNuevoEstante,
        modalNuevoEstante: !!modalNuevoEstante,
        formNuevoEstante: !!formNuevoEstante,
        btnGuardarNuevoEstante: !!btnGuardarNuevoEstante
    });

    // Event listener para abrir modal
    if (btnNuevoEstante) {
        btnNuevoEstante.addEventListener("click", function(e) {
            e.preventDefault();
            abrirModalAgregarEstante();
        });
    }

    // Event listener para cerrar modal con X
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener("click", function(e) {
            e.preventDefault();
            cerrarModalAgregarEstante();
        });
    }

    // Event listener para cancelar
    if (btnCancelarNuevoEstante) {
        btnCancelarNuevoEstante.addEventListener("click", function(e) {
            e.preventDefault();
            cerrarModalAgregarEstante();
        });
    }

    // Event listener para envío del formulario
    if (formNuevoEstante) {
        formNuevoEstante.addEventListener("submit", function(e) {
            e.preventDefault();
            guardarNuevoEstante();
        });
    }

    // Event listener para guardar con botón
    if (btnGuardarNuevoEstante) {
        btnGuardarNuevoEstante.addEventListener("click", function(e) {
            e.preventDefault();
            guardarNuevoEstante();
        });
    }

    // Cerrar modal al hacer click fuera
    if (modalNuevoEstante) {
        modalNuevoEstante.addEventListener("click", function(e) {
            if (e.target === modalNuevoEstante) {
                cerrarModalAgregarEstante();
            }
        });
    }

    // Cerrar modal con ESC
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape" && modalNuevoEstante && !modalNuevoEstante.classList.contains("hidden")) {
            cerrarModalAgregarEstante();
        }
    });

    // Validación mejorada - solo cuando corresponde
    initValidacionMejorada();

    console.log("✅ Modal Agregar Estante inicializado correctamente");
}

// Hacer la función globalmente accesible
window.abrirModalAgregarEstante = abrirModalAgregarEstante;

function abrirModalAgregarEstante() {
    console.log("📂 Abriendo modal agregar estante...");
    
    const modal = document.getElementById("modalNuevoEstante");
    if (!modal) {
        console.error("❌ Modal no encontrado");
        return;
    }

    // Limpiar formulario primero
    limpiarFormularioEstante();
    
    // Remover errores previos
    limpiarErroresValidacion();
    
    // Asegurar que no hay valores persistentes en el navegador - TODOS los campos vacíos
    setTimeout(() => {
        const campos = ['nombre_estante', 'capacidad_total', 'numero_niveles', 'ubicacion_local'];
        campos.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                campo.value = ''; // Dejar TODOS los campos vacíos
            }
        });
        
        console.log('🔍 Valores después de limpieza (todos vacíos):', {
            nombre: document.getElementById("nombre_estante")?.value,
            capacidad: document.getElementById("capacidad_total")?.value,
            niveles: document.getElementById("numero_niveles")?.value,
            ubicacion: document.getElementById("ubicacion_local")?.value
        });
    }, 50);
    
    // Mostrar modal
    modal.classList.remove("hidden");
    modal.style.display = "flex";
    // Bloquear scroll del fondo mientras el modal está abierto
    document.body.style.overflow = 'hidden';
    
    // Enfocar primer campo
    const primerCampo = document.getElementById("nombre_estante");
    if (primerCampo) {
        setTimeout(() => {
            primerCampo.focus();
            primerCampo.select(); // Seleccionar el texto si hay alguno
        }, 150);
    }
    
    console.log("✅ Modal abierto exitosamente");
}

function cerrarModalAgregarEstante() {
    console.log("❌ Cerrando modal agregar estante...");
    
    const modal = document.getElementById("modalNuevoEstante");
    if (!modal) return;

    // Limpiar formulario ANTES de cerrar
    limpiarFormularioEstante();
    limpiarErroresValidacion();
    
    // Cerrar modal
    modal.classList.add("hidden");
    modal.style.display = "none";
    // Restaurar scroll del fondo
    document.body.style.overflow = '';
    
    // Asegurar que el formulario está realmente limpio - TODOS los campos vacíos
    setTimeout(() => {
        const form = document.getElementById("formNuevoEstante");
        if (form) {
            form.reset();
            
            // Limpiar explícitamente TODOS los campos
            const campos = ['nombre_estante', 'capacidad_total', 'numero_niveles', 'ubicacion_local'];
            campos.forEach(campoId => {
                const campo = document.getElementById(campoId);
                if (campo) {
                    campo.value = ''; // Dejar TODOS vacíos
                }
            });
        }
    }, 100);
    
    console.log("✅ Modal cerrado y formulario limpiado");
}

function limpiarFormularioEstante() {
    const form = document.getElementById("formNuevoEstante");
    if (form) {
        form.reset();
        
        // Limpiar explícitamente todos los campos
        const campos = [
            'nombre_estante',
            'capacidad_total', 
            'numero_niveles',
            'ubicacion_local'
        ];
        
        campos.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                campo.value = '';
            }
        });
        
        console.log('🧹 Formulario limpiado - todos los campos vacíos');
    }
}

function limpiarErroresValidacion() {
    // Remover clases de error
    document.querySelectorAll(".form-group.error").forEach(group => {
        group.classList.remove("error");
    });
    
    // Remover mensajes de error
    document.querySelectorAll(".error-message").forEach(msg => {
        msg.remove();
    });
    
    // Remover bordes rojos y estilos inline
    document.querySelectorAll(".input-error").forEach(input => {
        input.classList.remove("input-error");
        // Remover estilos inline de error
        input.style.borderColor = "";
        input.style.borderWidth = "";
        input.style.boxShadow = "";
    });
}

function guardarNuevoEstante() {
    console.log("💾 Guardando nuevo estante...");

    if (!validarFormularioEstante()) {
        console.log("❌ Validación fallida");
        return;
    }

    const formData = obtenerDatosFormulario();
    console.log("📊 Datos del formulario:", formData);

    // Mostrar loading
    Swal.fire({
        title: 'Guardando estante...',
        text: 'Por favor, espere...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Obtener token CSRF
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        console.error("❌ Token CSRF no encontrado");
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Token de seguridad no encontrado. Recargue la página.',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    // Construir URL correcta para la API
    const apiUrl = window.location.origin + '/api/ubicaciones/crear-estante';
    
    console.log('🌐 Enviando datos a:', apiUrl);

    // Calcular numero_posiciones desde capacidad_total y numero_niveles
    const numeroNiveles = formData.numero_niveles;
    const capacidadTotal = formData.capacidad_total;
    const numeroPosiciones = Math.ceil(capacidadTotal / numeroNiveles);

    // Preparar datos para envío (JSON en lugar de FormData)
    const dataToSend = {
        nombre: formData.nombre,
        numero_niveles: numeroNiveles,
        numero_posiciones: numeroPosiciones,
        capacidad_total: capacidadTotal,
        ubicacion_fisica: formData.ubicacion_fisica || '',
        tipo: 'venta', // Tipo por defecto
        activo: true
    };

    console.log('📦 Datos a enviar:', dataToSend);

    // Enviar datos al servidor
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dataToSend)
    })
    .then(response => {
        console.log("📡 Respuesta del servidor:", response.status, response.statusText);
        console.log("📡 Headers de respuesta:", Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log("📦 Respuesta completa del servidor:", data);
        
        if (data.success) {
            console.log('✅ Estante creado exitosamente:', data.data);
            console.log('📊 Ubicaciones creadas:', data.data.ubicaciones_creadas);
            
            Swal.fire({
                icon: 'success',
                title: '¡Estante creado exitosamente!',
                text: `El estante "${formData.nombre}" ha sido creado con ${formData.capacidad_total} slots distribuidos en ${formData.numero_niveles} niveles.`,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                cerrarModalAgregarEstante();
                
                // Actualizar la vista de estantes si está disponible
                if (window.mapaAlmacen && typeof window.mapaAlmacen.actualizarResumenEstantes === 'function') {
                    console.log('🔄 Actualizando vista de estantes...');
                    window.mapaAlmacen.actualizarResumenEstantes();
                } else {
                    console.log('🔄 Recargando página...');
                    window.location.reload();
                }
            });
        } else {
            // Error desde el servidor pero con respuesta JSON válida
            console.error('❌ Error reportado por el servidor:', data);
            
            let errorMessage = data.message || 'Error desconocido al crear el estante';
            
            // Si hay errores de validación, mostrarlos
            if (data.errors) {
                console.error('❌ Errores de validación:', data.errors);
                errorMessage += '\n\nDetalles:\n';
                Object.entries(data.errors).forEach(([field, errors]) => {
                    errorMessage += `• ${field}: ${Array.isArray(errors) ? errors.join(', ') : errors}\n`;
                });
            }
            
            // Si hay detalles de debug, mostrarlos en consola
            if (data.debug_data) {
                console.error('🐛 Datos de debug:', data.debug_data);
            }
            
            if (data.error_details) {
                console.error('🔍 Detalles del error:', data.error_details);
            }
            
            throw new Error(errorMessage);
        }
    })
    .catch(error => {
        console.error("❌ Error detallado al guardar:", error);
        console.error("❌ Stack trace:", error.stack);
        
        let mensajeError = 'Hubo un problema al crear el estante.';
        
        if (error.message.includes('validation')) {
            mensajeError = 'Por favor, verifique que todos los campos estén llenos correctamente.';
        } else if (error.message.includes('exists') || error.message.includes('duplicate')) {
            mensajeError = 'Ya existe un estante con este nombre. Por favor, elija otro nombre.';
        } else if (error.message.includes('network') || error.message.includes('fetch')) {
            mensajeError = 'Error de conexión. Verifique su conexión a internet e intente nuevamente.';
        } else if (error.message.includes('404')) {
            mensajeError = 'La ruta para crear estantes no está disponible. Contacte al administrador.';
        } else if (error.message.includes('500')) {
            mensajeError = 'Error interno del servidor. Intente nuevamente en unos momentos.';
        } else if (error.message) {
            mensajeError = error.message;
        }
        
        Swal.fire({
            icon: 'error',
            title: 'Error al crear estante',
            text: mensajeError,
            confirmButtonText: 'Entendido',
            footer: `<small>Error técnico: ${error.message}</small>`
        });
    });
}

function validarFormularioEstante() {
    console.log("🔍 Validando formulario...");
    
    limpiarErroresValidacion();
    let esValido = true;

    // Validar nombre del estante
    const nombre = document.getElementById("nombre_estante");
    if (!nombre || !nombre.value.trim()) {
        mostrarErrorCampo(nombre, "Por favor, ingresa un nombre para el estante");
        esValido = false;
    } else if (nombre.value.trim().length < 2) {
        mostrarErrorCampo(nombre, "El nombre debe tener al menos 2 caracteres");
        esValido = false;
    }

    // Validar capacidad total
    const capacidad = document.getElementById("capacidad_total");
    if (!capacidad || !capacidad.value) {
        mostrarErrorCampo(capacidad, "Especifica cuántos slots tendrá el estante");
        esValido = false;
    } else {
        const capacidadNum = parseInt(capacidad.value);
        if (capacidadNum < 1) {
            mostrarErrorCampo(capacidad, "Debe tener al menos 1 slot");
            esValido = false;
        } else if (capacidadNum > 100) {
            mostrarErrorCampo(capacidad, "Máximo permitido: 100 slots");
            esValido = false;
        }
    }

    // Validar número de niveles
    const niveles = document.getElementById("numero_niveles");
    if (!niveles || !niveles.value) {
        mostrarErrorCampo(niveles, "Indica cuántos niveles tendrá el estante");
        esValido = false;
    } else {
        const nivelesNum = parseInt(niveles.value);
        if (nivelesNum < 1) {
            mostrarErrorCampo(niveles, "Debe tener al menos 1 nivel");
            esValido = false;
        } else if (nivelesNum > 10) {
            mostrarErrorCampo(niveles, "Máximo permitido: 10 niveles");
            esValido = false;
        }
        
        // Validar coherencia entre capacidad y niveles
        const capacidadNum = parseInt(capacidad?.value || 0);
        if (capacidadNum > 0 && nivelesNum > 0) {
            const posicionesPorNivel = Math.ceil(capacidadNum / nivelesNum);
            if (posicionesPorNivel < 1) {
                mostrarErrorCampo(niveles, "La combinación de capacidad y niveles no es válida");
                esValido = false;
            } else if (posicionesPorNivel > 20) {
                mostrarErrorCampo(capacidad, "Demasiadas posiciones por nivel. Máximo 20 posiciones por nivel.");
                esValido = false;
            }
            
            // Informar al usuario sobre la distribución
            console.log(`📊 Distribución calculada: ${nivelesNum} niveles con ${posicionesPorNivel} posiciones cada uno`);
        }
    }

    console.log("✅ Validación completada:", esValido ? "EXITOSA" : "FALLIDA");
    return esValido;
}

function mostrarErrorCampo(input, mensaje) {
    if (!input) return;
    
    const formGroup = input.closest(".form-group");
    if (formGroup) {
        formGroup.classList.add("error");
        
        // Agregar clase de error al input con estilo mejorado
        input.classList.add("input-error");
        input.style.borderColor = "#ef4444";
        input.style.borderWidth = "2px";
        input.style.boxShadow = "0 0 0 3px rgba(239, 68, 68, 0.1)";
        
        // Crear mensaje de error con mejor estilo
        let errorMsg = formGroup.querySelector(".error-message");
        if (!errorMsg) {
            errorMsg = document.createElement("div");
            errorMsg.className = "error-message";
            formGroup.appendChild(errorMsg);
        }
        
        // Estilos mejorados para el mensaje de error
        errorMsg.textContent = mensaje;
        errorMsg.style.cssText = `
            color: #ef4444;
            font-size: 13px;
            font-weight: 500;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(239, 68, 68, 0.05);
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid #ef4444;
        `;
        
        // Agregar icono de error
        if (!errorMsg.querySelector('.error-icon')) {
            const icon = document.createElement('span');
            icon.className = 'error-icon';
            icon.innerHTML = '⚠️';
            icon.style.fontSize = '14px';
            errorMsg.insertBefore(icon, errorMsg.firstChild);
        }
    }
}

function obtenerDatosFormulario() {
    const datos = {
        nombre: document.getElementById("nombre_estante")?.value?.trim() || '',
        capacidad_total: parseInt(document.getElementById("capacidad_total")?.value || 0),
        numero_niveles: parseInt(document.getElementById("numero_niveles")?.value || 0),
        ubicacion_fisica: document.getElementById("ubicacion_local")?.value?.trim() || ''
    };
    
    console.log('📋 Datos del formulario obtenidos:', {
        nombre: `"${datos.nombre}"`,
        capacidad_total: datos.capacidad_total,
        numero_niveles: datos.numero_niveles,
        ubicacion_fisica: `"${datos.ubicacion_fisica}"`
    });
    
    return datos;
}

function initValidacionMejorada() {
    // Validación mejorada - solo cuando el usuario interactúa
    const campos = [
        'nombre_estante',
        'capacidad_total', 
        'numero_niveles'
    ];

    campos.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo) {
            // Marcar que el campo ha sido tocado
            let campoPensado = false;
            
            // Validar solo DESPUÉS de que el usuario haya escrito algo
            campo.addEventListener('input', function() {
                campoPensado = true;
                
                // Limpiar error previo cuando el usuario empieza a escribir
                const formGroup = this.closest('.form-group');
                if (formGroup) {
                    formGroup.classList.remove('error');
                    this.classList.remove('input-error');
                    const errorMsg = formGroup.querySelector('.error-message');
                    if (errorMsg) errorMsg.remove();
                }
                
                // Para campos numéricos, evitar valores negativos
                if (campoId.includes('capacidad') || campoId.includes('numero')) {
                    const valor = parseInt(this.value);
                    if (valor < 0) {
                        this.value = '';
                    }
                }
            });

            // Validar cuando pierde el foco SOLO si ya ha sido tocado
            campo.addEventListener('blur', function() {
                if (campoPensado && this.value.trim() !== '') {
                    // Limpiar error previo de este campo
                    const formGroup = this.closest('.form-group');
                    if (formGroup) {
                        formGroup.classList.remove('error');
                        this.classList.remove('input-error');
                        const errorMsg = formGroup.querySelector('.error-message');
                        if (errorMsg) errorMsg.remove();
                    }
                    
                    // Validar este campo específico
                    validarCampoIndividual(this);
                }
            });
        }
    });
}

function validarCampoIndividual(campo) {
    const id = campo.id;
    let mensaje = '';

    switch(id) {
        case 'nombre_estante':
            if (!campo.value.trim()) {
                mensaje = 'Por favor, ingresa un nombre para el estante';
            } else if (campo.value.trim().length < 2) {
                mensaje = 'El nombre debe tener al menos 2 caracteres';
            }
            break;

        case 'capacidad_total':
            const capacidad = parseInt(campo.value);
            if (!campo.value) {
                mensaje = 'Especifica cuántos slots tendrá el estante';
            } else if (capacidad < 1) {
                mensaje = 'Debe tener al menos 1 slot';
            } else if (capacidad > 100) {
                mensaje = 'Máximo permitido: 100 slots';
            }
            break;

        case 'numero_niveles':
            const niveles = parseInt(campo.value);
            if (!campo.value) {
                mensaje = 'Indica cuántos niveles tendrá el estante';
            } else if (niveles < 1) {
                mensaje = 'Debe tener al menos 1 nivel';
            } else if (niveles > 10) {
                mensaje = 'Máximo permitido: 10 niveles';
            }
            break;
    }

    if (mensaje) {
        mostrarErrorCampo(campo, mensaje);
        return false;
    }
    return true;
}

// Función global para compatibilidad
window.abrirModalAgregarEstante = abrirModalAgregarEstante;
window.cerrarModalAgregarEstante = cerrarModalAgregarEstante;

console.log("✅ Modal Agregar Estante - Script cargado exitosamente");
