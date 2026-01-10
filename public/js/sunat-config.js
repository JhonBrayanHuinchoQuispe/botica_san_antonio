/**
 * JavaScript para la configuración de SUNAT
 * Maneja las funcionalidades de la interfaz de configuración
 */

class SunatConfig {
    constructor() {
        this.initializeEventListeners();
        this.initializeTooltips();
        this.loadInitialData();
    }

    /**
     * Inicializa los event listeners
     */
    initializeEventListeners() {
        // Botón guardar configuración
        document.getElementById('btn-guardar')?.addEventListener('click', () => {
            this.guardarConfiguracion();
        });

        // Botón subir certificado
        document.getElementById('btn-subir-certificado')?.addEventListener('click', () => {
            this.subirCertificado();
        });

        // Botón probar conexión
        document.getElementById('btn-probar-conexion')?.addEventListener('click', () => {
            this.probarConexion();
        });

        // Input de archivo de certificado
        document.getElementById('certificado')?.addEventListener('change', (e) => {
            this.validarCertificado(e.target.files[0]);
        });

        // Checkbox de producción
        document.getElementById('produccion')?.addEventListener('change', (e) => {
            this.toggleAmbiente(e.target.checked);
        });

        // Validación en tiempo real de RUC
        document.getElementById('ruc')?.addEventListener('input', (e) => {
            this.validarRuc(e.target.value);
        });

        // Tabs
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.cambiarTab(e.target.getAttribute('data-bs-target'));
            });
        });
    }

    /**
     * Inicializa los tooltips de Bootstrap
     */
    initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    /**
     * Carga los datos iniciales
     */
    loadInitialData() {
        this.actualizarEstado();
        this.cargarConfiguracionActual();
    }

    /**
     * Guarda la configuración SUNAT
     */
    async guardarConfiguracion() {
        const btn = document.getElementById('btn-guardar');
        const originalText = btn.innerHTML;
        
        try {
            // Mostrar loading
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            btn.disabled = true;

            // Recopilar datos del formulario
            const formData = new FormData();
            const form = document.getElementById('form-configuracion');
            
            // Agregar todos los campos del formulario
            new FormData(form).forEach((value, key) => {
                formData.append(key, value);
            });

            // Enviar petición
            const response = await fetch('/admin/sunat/configuracion', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const result = await response.json();

            if (result.success) {
                this.mostrarAlerta('success', 'Configuración guardada exitosamente');
                this.actualizarEstado();
            } else {
                this.mostrarAlerta('error', result.message || 'Error al guardar la configuración');
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta('error', 'Error de conexión al guardar la configuración');
        } finally {
            // Restaurar botón
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    /**
     * Sube el certificado digital
     */
    async subirCertificado() {
        const fileInput = document.getElementById('certificado');
        const passwordInput = document.getElementById('certificado_password');
        const btn = document.getElementById('btn-subir-certificado');
        
        if (!fileInput.files[0]) {
            this.mostrarAlerta('warning', 'Por favor seleccione un archivo de certificado');
            return;
        }

        if (!passwordInput.value) {
            this.mostrarAlerta('warning', 'Por favor ingrese la contraseña del certificado');
            return;
        }

        const originalText = btn.innerHTML;
        
        try {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('certificado', fileInput.files[0]);
            formData.append('password', passwordInput.value);

            const response = await fetch('/admin/sunat/certificado/subir', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const result = await response.json();

            if (result.success) {
                this.mostrarAlerta('success', 'Certificado subido y validado exitosamente');
                this.actualizarEstadoCertificado(true);
            } else {
                this.mostrarAlerta('error', result.message || 'Error al subir el certificado');
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta('error', 'Error de conexión al subir el certificado');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    /**
     * Prueba la conexión con SUNAT
     */
    async probarConexion() {
        const btn = document.getElementById('btn-probar-conexion');
        const originalText = btn.innerHTML;
        
        try {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';
            btn.disabled = true;

            const response = await fetch('/admin/sunat/conexion/probar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const result = await response.json();

            if (result.success) {
                this.mostrarAlerta('success', 'Conexión exitosa con SUNAT');
                this.actualizarEstadoConexion(true);
            } else {
                this.mostrarAlerta('error', result.message || 'Error de conexión con SUNAT');
                this.actualizarEstadoConexion(false);
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarAlerta('error', 'Error al probar la conexión');
            this.actualizarEstadoConexion(false);
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    /**
     * Valida el archivo de certificado
     */
    validarCertificado(file) {
        if (!file) return;

        const allowedTypes = ['.p12', '.pfx'];
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!allowedTypes.includes(fileExtension)) {
            this.mostrarAlerta('error', 'Tipo de archivo no válido. Solo se permiten archivos .p12 o .pfx');
            document.getElementById('certificado').value = '';
            return false;
        }

        if (file.size > maxSize) {
            this.mostrarAlerta('error', 'El archivo es demasiado grande. Máximo 5MB');
            document.getElementById('certificado').value = '';
            return false;
        }

        this.mostrarAlerta('info', `Archivo seleccionado: ${file.name}`);
        return true;
    }

    /**
     * Valida el RUC en tiempo real
     */
    validarRuc(ruc) {
        const rucPattern = /^\d{11}$/;
        const input = document.getElementById('ruc');
        const feedback = document.getElementById('ruc-feedback');

        if (ruc.length === 0) {
            input.classList.remove('is-valid', 'is-invalid');
            feedback.textContent = '';
            return;
        }

        if (rucPattern.test(ruc)) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            feedback.textContent = 'RUC válido';
            feedback.className = 'valid-feedback';
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            feedback.textContent = 'RUC debe tener 11 dígitos';
            feedback.className = 'invalid-feedback';
        }
    }

    /**
     * Cambia entre ambiente de prueba y producción
     */
    toggleAmbiente(esProduccion) {
        const badge = document.getElementById('ambiente-badge');
        const alert = document.getElementById('ambiente-alert');
        
        if (esProduccion) {
            badge.textContent = 'PRODUCCIÓN';
            badge.className = 'badge bg-danger';
            alert.className = 'alert alert-danger';
            alert.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>¡ATENCIÓN!</strong> Está en modo PRODUCCIÓN. Los comprobantes enviados serán reales.';
        } else {
            badge.textContent = 'PRUEBAS';
            badge.className = 'badge bg-warning';
            alert.className = 'alert alert-warning';
            alert.innerHTML = '<i class="fas fa-info-circle"></i> Está en modo de PRUEBAS. Los comprobantes no tienen validez fiscal.';
        }
    }

    /**
     * Cambia de tab
     */
    cambiarTab(target) {
        // Remover active de todos los tabs
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.classList.remove('active');
        });
        
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });

        // Activar el tab seleccionado
        document.querySelector(`[data-bs-target="${target}"]`).classList.add('active');
        document.querySelector(target).classList.add('show', 'active');
    }

    /**
     * Actualiza el estado general del sistema
     */
    async actualizarEstado() {
        try {
            const response = await fetch('/admin/sunat/estado');
            const result = await response.json();

            if (result.success) {
                this.actualizarIndicadores(result.data);
            }
        } catch (error) {
            console.error('Error al actualizar estado:', error);
        }
    }

    /**
     * Actualiza los indicadores visuales
     */
    actualizarIndicadores(data) {
        // Estado de configuración
        const configStatus = document.getElementById('config-status');
        if (configStatus) {
            configStatus.className = data.configurado ? 'badge bg-success' : 'badge bg-danger';
            configStatus.textContent = data.configurado ? 'Configurado' : 'No Configurado';
        }

        // Estado del certificado
        this.actualizarEstadoCertificado(data.certificado_valido);

        // Estado de la conexión
        this.actualizarEstadoConexion(data.conexion_ok);

        // Último comprobante
        const ultimoComprobante = document.getElementById('ultimo-comprobante');
        if (ultimoComprobante && data.ultimo_comprobante) {
            ultimoComprobante.textContent = data.ultimo_comprobante;
        }
    }

    /**
     * Actualiza el estado del certificado
     */
    actualizarEstadoCertificado(valido) {
        const certStatus = document.getElementById('cert-status');
        if (certStatus) {
            certStatus.className = valido ? 'badge bg-success' : 'badge bg-danger';
            certStatus.textContent = valido ? 'Válido' : 'No Válido';
        }
    }

    /**
     * Actualiza el estado de la conexión
     */
    actualizarEstadoConexion(conectado) {
        const connStatus = document.getElementById('conn-status');
        if (connStatus) {
            connStatus.className = conectado ? 'badge bg-success' : 'badge bg-danger';
            connStatus.textContent = conectado ? 'Conectado' : 'Desconectado';
        }
    }

    /**
     * Carga la configuración actual
     */
    async cargarConfiguracionActual() {
        try {
            const response = await fetch('/admin/sunat/configuracion/actual');
            const result = await response.json();

            if (result.success && result.data) {
                this.llenarFormulario(result.data);
            }
        } catch (error) {
            console.error('Error al cargar configuración:', error);
        }
    }

    /**
     * Llena el formulario con los datos actuales
     */
    llenarFormulario(data) {
        Object.keys(data).forEach(key => {
            const input = document.getElementById(key);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = data[key];
                    if (key === 'produccion') {
                        this.toggleAmbiente(data[key]);
                    }
                } else {
                    input.value = data[key] || '';
                }
            }
        });
    }

    /**
     * Muestra alertas al usuario
     */
    mostrarAlerta(tipo, mensaje) {
        // Crear el elemento de alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-${this.getIconoAlerta(tipo)}"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insertar en el contenedor de alertas
        const container = document.getElementById('alertas-container') || document.querySelector('.container-fluid');
        container.insertBefore(alertDiv, container.firstChild);

        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    /**
     * Obtiene el icono apropiado para cada tipo de alerta
     */
    getIconoAlerta(tipo) {
        const iconos = {
            'success': 'check-circle',
            'error': 'exclamation-triangle',
            'warning': 'exclamation-circle',
            'info': 'info-circle'
        };
        return iconos[tipo] || 'info-circle';
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    new SunatConfig();
});

// Función global para refrescar el estado (puede ser llamada desde otros scripts)
window.refreshSunatStatus = function() {
    if (window.sunatConfig) {
        window.sunatConfig.actualizarEstado();
    }
};