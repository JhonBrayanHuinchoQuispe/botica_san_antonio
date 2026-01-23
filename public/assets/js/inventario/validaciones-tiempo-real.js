/**
 * Validaciones en Tiempo Real para Productos
 * Maneja la validación instantánea de campos en los modales de agregar y editar productos
 */

class ValidacionesTiempoReal {
    constructor() {
        this.debounceTimers = {};
        this.validationCache = {};
        this.init();
    }

    init() {
        console.log('🔍 Inicializando validaciones en tiempo real...');
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Validaciones para modal de agregar
        this.setupModalValidations('modalAgregar', 'formAgregarProducto', {
            'nombre': this.validateNombre.bind(this),
            'categoria': this.validateCategoria.bind(this),
            'marca': this.validateMarca.bind(this),
            'proveedor_id': this.validateProveedor.bind(this),
            'presentacion': this.validatePresentacion.bind(this),
            'concentracion': this.validateConcentracion.bind(this),
            'lote': this.validateLote.bind(this),
            'codigo_barras': this.validateCodigoBarras.bind(this),
            'stock_actual': this.validateStock.bind(this),
            'stock_minimo': this.validateStock.bind(this),
            'precio_compra': this.validatePrecios.bind(this),
            'precio_venta': this.validatePrecios.bind(this),
            'fecha_fabricacion': this.validateFechas.bind(this),
            'fecha_vencimiento': this.validateFechas.bind(this)
        });

        // Validaciones para modal de editar
        this.setupModalValidations('modalEditar', 'formEditarProducto', {
            'edit-nombre': this.validateNombre.bind(this),
            'edit-categoria': this.validateCategoria.bind(this),
            'edit-marca': this.validateMarca.bind(this),
            'edit-proveedor': this.validateProveedor.bind(this),
            'edit-concentracion': this.validateConcentracion.bind(this),
            'edit-lote': this.validateLote.bind(this),
            'edit-codigo_barras': this.validateCodigoBarras.bind(this),
            'edit-stock_actual': this.validateStock.bind(this),
            'edit-stock_minimo': this.validateStock.bind(this),
            'precio_compra_base_edit': this.validatePrecios.bind(this),
            'precio_venta_base_edit': this.validatePrecios.bind(this),
            'edit-fecha_vencimiento': this.validateFechas.bind(this)
        });
    }

    setupModalValidations(modalId, formId, validations) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Observer para detectar cuando el modal se abre
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isVisible = !modal.classList.contains('hidden');
                    if (isVisible) {
                        this.attachValidationsToForm(formId, validations);
                    }
                }
            });
        });

        observer.observe(modal, { attributes: true });

        // También configurar inmediatamente si el modal ya está visible
        if (!modal.classList.contains('hidden')) {
            this.attachValidationsToForm(formId, validations);
        }
    }

    attachValidationsToForm(formId, validations) {
        const form = document.getElementById(formId);
        if (!form) return;

        Object.entries(validations).forEach(([fieldName, validator]) => {
            const field = form.querySelector(`[name="${fieldName}"], #${fieldName}`);
            if (field) {
                // Remover listeners anteriores
                field.removeEventListener('input', field._validationHandler);
                field.removeEventListener('blur', field._validationHandler);

                // Crear nuevo handler que incluye actualización del botón
                field._validationHandler = (e) => {
                    this.debounceValidation(field, validator, 300);
                    // Actualizar estado del botón después de la validación
                    setTimeout(() => this.updateButtonState(formId), 100);
                };
                
                // Agregar listeners
                field.addEventListener('input', field._validationHandler);
                field.addEventListener('blur', field._validationHandler);

                // Validación inicial para mostrar mensajes en valores por defecto (ej. 0)
                setTimeout(() => {
                    validator(field);
                }, 10);
            }
        });

        // Configurar estado inicial del botón
        this.updateButtonState(formId);
    }

    debounceValidation(field, validator, delay) {
        const fieldId = field.id || field.name;
        
        // Limpiar timer anterior
        if (this.debounceTimers[fieldId]) {
            clearTimeout(this.debounceTimers[fieldId]);
        }

        // Crear nuevo timer
        this.debounceTimers[fieldId] = setTimeout(() => {
            validator(field);
        }, delay);
    }

    async validateNombre(field) {
        const value = field.value.trim();
        const isEdit = field.id.startsWith('edit-');
        const form = field.closest('form');
        
        this.clearFieldError(field);

        if (!value) {
            this.showFieldError(field, 'El nombre del producto es obligatorio');
            return false;
        }

        // Validación de formato
        const nameRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]+$/;
        if (!nameRegex.test(value)) {
            this.showFieldError(field, 'El nombre solo puede contener letras, números, espacios, guiones y puntos');
            return false;
        }

        // Validación de duplicados (nombre + concentración)
        const concentracionField = form.querySelector(isEdit ? '#edit-concentracion' : '[name="concentracion"]');
        const concentracion = concentracionField ? concentracionField.value.trim() : '';
        
        if (concentracion) {
            const isDuplicate = await this.checkDuplicateProduct(value, concentracion, isEdit ? this.getProductId(form) : null);
            if (isDuplicate) {
                this.showFieldError(field, `Ya existe un producto "${value}" con concentración "${concentracion}"`);
                return false;
            }
        }

        this.showFieldSuccess(field);
        return true;
    }

    validateCategoria(field) {
        const value = field.value.trim();
        
        this.clearFieldError(field);

        if (!value) {
            this.showFieldError(field, 'Este campo es obligatorio');
            return false;
        }

        this.showFieldSuccess(field);
        return true;
    }

    validateMarca(field) {
        const value = field.value.trim();
        
        this.clearFieldError(field);

        if (!value) {
            this.showFieldError(field, 'Este campo es obligatorio');
            return false;
        }

        // Validación de formato
        const marcaRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-\.]+$/;
        if (!marcaRegex.test(value)) {
            this.showFieldError(field, 'La marca solo puede contener letras, números, espacios, guiones y puntos');
            return false;
        }

        this.showFieldSuccess(field);
        return true;
    }

    validateProveedor(field) {
        const value = field.value.trim();
        
        this.clearFieldError(field);

        if (!value) {
            this.showFieldError(field, 'Este campo es obligatorio');
            return false;
        }

        this.showFieldSuccess(field);
        return true;
    }

    async validateConcentracion(field) {
        const value = field.value.trim();
        const isEdit = field.id.startsWith('edit-');
        const form = field.closest('form');
        
        this.clearFieldError(field);

        if (!value) {
            this.showFieldError(field, 'La concentración es obligatoria');
            return false;
        }

        // Validación de formato (número + unidad)
        const concentracionRegex = /^\d+(\.\d+)?\s*(mg|ml|g|l|%|mcg|ui|iu)$/i;
        if (!concentracionRegex.test(value)) {
            this.showFieldError(field, 'Formato inválido. Ejemplos: 500mg, 2.5ml, 10%');
            return false;
        }

        // Validación de duplicados (nombre + concentración)
        const nombreField = form.querySelector(isEdit ? '#edit-nombre' : '[name="nombre"]');
        const nombre = nombreField ? nombreField.value.trim() : '';
        
        if (nombre) {
            const isDuplicate = await this.checkDuplicateProduct(nombre, value, isEdit ? this.getProductId(form) : null);
            if (isDuplicate) {
                this.showFieldError(field, `Ya existe un producto "${nombre}" con esta concentración`);
                return false;
            }
        }

        this.showFieldSuccess(field);
        return true;
    }

    async validateCodigoBarras(field) {
        const value = field.value.trim();
        const isEdit = field.id.startsWith('edit-');
        const form = field.closest('form');
        
        this.clearFieldError(field);

        if (!value) {
            this.showFieldError(field, 'El código de barras es obligatorio');
            return false;
        }

        // Validación de longitud exacta de 13 dígitos
        if (value.length !== 13) {
            this.showFieldError(field, 'El código de barras debe tener exactamente 13 dígitos');
            return false;
        }

        // Validación de formato (solo números)
        const codigoRegex = /^[0-9]+$/;
        if (!codigoRegex.test(value)) {
            this.showFieldError(field, 'El código de barras solo puede contener números');
            return false;
        }

        // Validación de duplicados
        const isDuplicate = await this.checkDuplicateBarcode(value, isEdit ? this.getProductId(form) : null);
        if (isDuplicate) {
            this.showFieldError(field, 'Este código de barras ya está en uso');
            return false;
        }

        this.showFieldSuccess(field);
        return true;
    }

    validatePrecios(field) {
        const value = parseFloat(field.value);
        const isEdit = field.id.startsWith('edit-');
        const form = field.closest('form');
        const fieldName = field.name || field.id.replace('edit-', '');
        
        this.clearFieldError(field);

        if (isNaN(value) || value <= 0) {
            this.showFieldError(field, 'Ingrese un precio válido mayor a 0');
            return false;
        }

        // Validar margen de ganancia si ambos precios están presentes
        const precioCompraField = form.querySelector(isEdit ? '#edit-precio_compra' : '[name="precio_compra"]');
        const precioVentaField = form.querySelector(isEdit ? '#edit-precio_venta' : '[name="precio_venta"]');
        
        const precioCompra = parseFloat(precioCompraField?.value || 0);
        const precioVenta = parseFloat(precioVentaField?.value || 0);

        if (precioCompra > 0 && precioVenta > 0) {
            const margen = ((precioVenta - precioCompra) / precioCompra) * 100;
            
            if (margen < 5) {
                this.showFieldWarning(field, `Margen muy bajo: ${margen.toFixed(1)}%. Recomendado: mínimo 5%`);
            } else if (margen > 500) {
                this.showFieldWarning(field, `Margen muy alto: ${margen.toFixed(1)}%. Verifique los precios`);
            } else {
                this.showFieldSuccess(field, `Margen: ${margen.toFixed(1)}%`);
            }
        } else {
            this.showFieldSuccess(field);
        }

        return true;
    }

    validateStock(field) {
        const value = parseInt(field.value);
        const isEdit = field.id.startsWith('edit-');
        const form = field.closest('form');
        const fieldName = field.name || field.id.replace('edit-', '');
        
        this.clearFieldError(field);

        if (!field.value.trim()) {
            this.showFieldError(field, 'Este campo es obligatorio');
            return false;
        }

        if (isNaN(value) || value < 0) {
            this.showFieldError(field, 'Ingrese un número válido mayor o igual a 0');
            return false;
        }

        if (value > 99999) {
            this.showFieldError(field, 'El stock no puede ser mayor a 99,999');
            return false;
        }

        // Validación específica para stock mínimo
        if (fieldName === 'stock_minimo' && value === 0) {
            this.showFieldWarning(field, 'Se recomienda un stock mínimo mayor a 0');
        }

        // Validar relación entre stock actual y mínimo
        const stockActualField = form.querySelector(isEdit ? '#edit-stock_actual' : '[name="stock_actual"]');
        const stockMinimoField = form.querySelector(isEdit ? '#edit-stock_minimo' : '[name="stock_minimo"]');
        
        const stockActual = parseInt(stockActualField?.value || 0);
        const stockMinimo = parseInt(stockMinimoField?.value || 0);

        if (stockActual > 0 && stockMinimo > 0) {
            if (stockActual <= stockMinimo) {
                if (fieldName === 'stock_actual') {
                    this.showFieldWarning(field, 'Stock actual está por debajo del mínimo');
                } else if (fieldName === 'stock_minimo') {
                    this.showFieldWarning(field, 'Stock mínimo es mayor al stock actual');
                }
            } else {
                this.showFieldSuccess(field);
            }
        } else {
            this.showFieldSuccess(field);
        }

        return true;
    }

    validatePresentacion(field) {
        const value = field.value.trim();
        
        this.clearFieldError(field);

        if (!value) {
            this.showFieldError(field, 'Este campo es obligatorio');
            return false;
        }

        this.showFieldSuccess(field);
        return true;
    }

    validateLote(field) {
        const value = field.value.trim();
        
        this.clearFieldError(field);

        if (!value) {
            this.showFieldError(field, 'Este campo es obligatorio');
            return false;
        }

        // Validación de formato del lote
        const loteRegex = /^[a-zA-Z0-9\-_]+$/;
        if (!loteRegex.test(value)) {
            this.showFieldError(field, 'El lote solo puede contener letras, números, guiones y guiones bajos');
            return false;
        }

        if (value.length < 3) {
            this.showFieldError(field, 'El lote debe tener al menos 3 caracteres');
            return false;
        }

        if (value.length > 20) {
            this.showFieldError(field, 'El lote no puede tener más de 20 caracteres');
            return false;
        }

        this.showFieldSuccess(field);
        return true;
    }

    validateFechas(field) {
        const value = field.value;
        const isEdit = field.id.startsWith('edit-');
        const form = field.closest('form');
        const fieldName = field.name || field.id.replace('edit-', '');
        
        this.clearFieldError(field);

        if (!value) {
            this.showFieldError(field, 'La fecha es obligatoria');
            return false;
        }

        const fecha = new Date(value);
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        if (fieldName === 'fecha_fabricacion') {
            if (fecha > hoy) {
                this.showFieldError(field, 'La fecha de fabricación no puede ser futura');
                return false;
            }
        }

        if (fieldName === 'fecha_vencimiento') {
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            
            // En edición permitimos cualquier fecha futura o de hoy
            // En nuevo producto pedimos al menos 7 días
            const diasMinimos = isEdit ? 0 : 7;
            const fechaMinima = new Date();
            fechaMinima.setDate(fechaMinima.getDate() + diasMinimos);
            fechaMinima.setHours(0, 0, 0, 0);
            
            if (fecha < fechaMinima) {
                this.showFieldError(field, isEdit ? 'La fecha no puede ser pasada' : 'La fecha debe ser al menos 7 días desde hoy');
                return false;
            }

            // Validar que sea al menos 30 días después de la fabricación
            const fechaFabField = form.querySelector(isEdit ? '#edit-fecha_fabricacion' : '[name="fecha_fabricacion"]');
            if (fechaFabField?.value) {
                const fechaFab = new Date(fechaFabField.value);
                const diasDiferencia = (fecha - fechaFab) / (1000 * 60 * 60 * 24);
                
                if (diasDiferencia < 30) {
                    this.showFieldError(field, 'Debe haber al menos 30 días entre fabricación y vencimiento');
                    return false;
                }
            }
        }

        this.showFieldSuccess(field);
        return true;
    }

    // Métodos de API
    async checkDuplicateProduct(nombre, concentracion, excludeId = null) {
        try {
            const response = await fetch('/api/productos/validar-duplicado', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    nombre: nombre,
                    concentracion: concentracion,
                    exclude_id: excludeId
                })
            });

            const data = await response.json();
            return data.exists || false;
        } catch (error) {
            console.error('Error validando duplicado:', error);
            return false;
        }
    }

    async checkDuplicateBarcode(codigo, excludeId = null) {
        try {
            const response = await fetch('/api/productos/validar-codigo-barras', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    codigo_barras: codigo,
                    exclude_id: excludeId
                })
            });

            const data = await response.json();
            return data.exists || false;
        } catch (error) {
            console.error('Error validando código de barras:', error);
            return false;
        }
    }

    // Métodos de UI
    showFieldError(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('border-red-500', 'bg-red-50');
        field.classList.remove('border-green-500', 'bg-green-50', 'border-yellow-500', 'bg-yellow-50');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-message error-message text-red-600 text-sm mt-1 flex items-center';
        errorDiv.innerHTML = `
            <iconify-icon icon="heroicons:exclamation-circle" class="mr-1"></iconify-icon>
            ${message}
        `;
        
        field.parentNode.appendChild(errorDiv);
    }

    showFieldSuccess(field, message = '') {
        this.clearFieldError(field);
        
        field.classList.add('border-green-500', 'bg-green-50');
        field.classList.remove('border-red-500', 'bg-red-50', 'border-yellow-500', 'bg-yellow-50');
        
        if (message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'validation-message success-message text-green-600 text-sm mt-1 flex items-center';
            successDiv.innerHTML = `
                <iconify-icon icon="heroicons:check-circle" class="mr-1"></iconify-icon>
                ${message}
            `;
            
            field.parentNode.appendChild(successDiv);
        }
    }

    showFieldWarning(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('border-yellow-500', 'bg-yellow-50');
        field.classList.remove('border-red-500', 'bg-red-50', 'border-green-500', 'bg-green-50');
        
        const warningDiv = document.createElement('div');
        warningDiv.className = 'validation-message warning-message text-yellow-600 text-sm mt-1 flex items-center';
        warningDiv.innerHTML = `
            <iconify-icon icon="heroicons:exclamation-triangle" class="mr-1"></iconify-icon>
            ${message}
        `;
        
        field.parentNode.appendChild(warningDiv);
    }

    clearFieldError(field) {
        field.classList.remove('border-red-500', 'bg-red-50', 'border-green-500', 'bg-green-50', 'border-yellow-500', 'bg-yellow-50');
        
        const existingMessages = field.parentNode.querySelectorAll('.validation-message');
        existingMessages.forEach(msg => msg.remove());
    }

    getProductId(form) {
        const idField = form.querySelector('#edit-producto-id');
        return idField ? idField.value : null;
    }

    // Método para actualizar el estado del botón de guardar
    updateButtonState(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const isEdit = formId === 'formEditarProducto';
        const button = isEdit ? 
            document.querySelector('.btn-save-edit') : 
            document.querySelector('.btn-save');

        if (!button) return;

        // Verificar si hay errores en el formulario
        const hasErrors = form.querySelectorAll('.error-message').length > 0;
        
        // Verificar si todos los campos requeridos tienen valor
        const requiredFields = form.querySelectorAll('[required]');
        const hasEmptyRequired = Array.from(requiredFields).some(field => !field.value.trim());

        // Habilitar/deshabilitar botón
        const shouldDisable = hasErrors || hasEmptyRequired;
        
        if (shouldDisable) {
            button.disabled = true;
            button.classList.add('opacity-50', 'cursor-not-allowed');
            button.classList.remove('hover:bg-indigo-700');
        } else {
            button.disabled = false;
            button.classList.remove('opacity-50', 'cursor-not-allowed');
            button.classList.add('hover:bg-indigo-700');
        }
    }

    // Método público para validar todo el formulario
    async validateForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        const isEdit = formId === 'formEditarProducto';
        let isValid = true;

        // Validar campos requeridos
        const validations = isEdit ? [
            { field: form.querySelector('#edit-nombre'), validator: this.validateNombre.bind(this) },
            { field: form.querySelector('#edit-categoria'), validator: this.validateCategoria.bind(this) },
            { field: form.querySelector('#edit-marca'), validator: this.validateMarca.bind(this) },
            { field: form.querySelector('#edit-proveedor'), validator: this.validateProveedor.bind(this) },
            { field: form.querySelector('#edit-concentracion'), validator: this.validateConcentracion.bind(this) },
            { field: form.querySelector('#edit-lote'), validator: this.validateLote.bind(this) },
            { field: form.querySelector('#edit-codigo_barras'), validator: this.validateCodigoBarras.bind(this) },
            { field: form.querySelector('#edit-stock_actual'), validator: this.validateStock.bind(this) },
            { field: form.querySelector('#edit-stock_minimo'), validator: this.validateStock.bind(this) },
            { field: form.querySelector('#precio_compra_base_edit'), validator: this.validatePrecios.bind(this) },
            { field: form.querySelector('#precio_venta_base_edit'), validator: this.validatePrecios.bind(this) }
            // { field: form.querySelector('#edit-fecha_vencimiento'), validator: this.validateFechas.bind(this) }
        ] : [
            { field: form.querySelector('[name="nombre"]'), validator: this.validateNombre.bind(this) },
            { field: form.querySelector('[name="categoria"]'), validator: this.validateCategoria.bind(this) },
            { field: form.querySelector('[name="marca"]'), validator: this.validateMarca.bind(this) },
            { field: form.querySelector('[name="proveedor_id"]'), validator: this.validateProveedor.bind(this) },
            { field: form.querySelector('[name="presentacion"]'), validator: this.validatePresentacion.bind(this) },
            { field: form.querySelector('[name="concentracion"]'), validator: this.validateConcentracion.bind(this) },
            { field: form.querySelector('[name="lote"]'), validator: this.validateLote.bind(this) },
            { field: form.querySelector('[name="codigo_barras"]'), validator: this.validateCodigoBarras.bind(this) },
            { field: form.querySelector('[name="stock_actual"]'), validator: this.validateStock.bind(this) },
            { field: form.querySelector('[name="stock_minimo"]'), validator: this.validateStock.bind(this) },
            { field: form.querySelector('[name="precio_compra"]'), validator: this.validatePrecios.bind(this) },
            { field: form.querySelector('[name="precio_venta"]'), validator: this.validatePrecios.bind(this) },
            { field: form.querySelector('[name="fecha_fabricacion"]'), validator: this.validateFechas.bind(this) },
            { field: form.querySelector('[name="fecha_vencimiento"]'), validator: this.validateFechas.bind(this) }
        ];

        for (const { field, validator } of validations) {
            if (field) {
                const fieldValid = await validator(field);
                if (!fieldValid) {
                    isValid = false;
                }
            }
        }

        return isValid;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.validacionesTiempoReal = new ValidacionesTiempoReal();
});

// Exportar para uso global
window.ValidacionesTiempoReal = ValidacionesTiempoReal;
