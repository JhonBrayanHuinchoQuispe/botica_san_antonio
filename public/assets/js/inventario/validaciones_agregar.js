document.addEventListener('DOMContentLoaded', function() {
    const formAgregarProducto = document.getElementById('formAgregarProducto');
    const btnGuardarProducto = document.getElementById('btnGuardarProducto');
    
    if (!formAgregarProducto || !btnGuardarProducto) return;

    // Helpers de mensajes
    function clearFieldMessages(input) {
        input.classList.remove('border-red-500', 'bg-red-50', 'border-green-500', 'bg-green-50', 'border-yellow-500', 'bg-yellow-50');
        const msgs = input.parentNode.querySelectorAll('.validation-message');
        msgs.forEach(m => m.remove());
    }

    function showFieldError(input, message) {
        clearFieldMessages(input);
        input.classList.add('border-red-500', 'bg-red-50');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-message error-message text-red-600 text-sm mt-1 flex items-center';
        errorDiv.innerHTML = `<iconify-icon icon="heroicons:exclamation-circle" class="mr-1"></iconify-icon>${message}`;
        input.parentNode.appendChild(errorDiv);
    }

    function showFieldWarning(input, message) {
        clearFieldMessages(input);
        input.classList.add('border-yellow-500', 'bg-yellow-50');
        const warnDiv = document.createElement('div');
        warnDiv.className = 'validation-message warn-message text-yellow-600 text-sm mt-1 flex items-center';
        warnDiv.innerHTML = `<iconify-icon icon="heroicons:exclamation-triangle" class="mr-1"></iconify-icon>${message}`;
        input.parentNode.appendChild(warnDiv);
    }

    function showFieldSuccess(input, message = '') {
        clearFieldMessages(input);
        input.classList.add('border-green-500', 'bg-green-50');
        if (message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'validation-message success-message text-green-600 text-sm mt-1 flex items-center';
            successDiv.innerHTML = `<iconify-icon icon="heroicons:check-circle" class="mr-1"></iconify-icon>${message}`;
            input.parentNode.appendChild(successDiv);
        }
    }

    // Función para validar un campo específico
    function validarCampo(input) {
        const valor = input.value.trim();
        const nombre = input.name;
        let esValido = true;

        // Validaciones específicas por campo
        switch(nombre) {
            case 'nombre':
            case 'marca':
                esValido = valor.length > 0;
                break;
            case 'categoria':
            case 'proveedor':
                esValido = valor !== '';
                break;
            case 'presentacion':
            case 'concentracion':
            case 'lote':
                esValido = valor.length > 0;
                break;
            case 'codigo_barras':
                esValido = valor.length === 13;
                break;
            case 'stock_actual': {
                if (!valor.length) { esValido = false; showFieldError(input, 'Este campo es obligatorio'); break; }
                const n = parseInt(valor);
                if (isNaN(n) || n < 0) { esValido = false; showFieldError(input, 'Ingrese un número válido mayor o igual a 0'); break; }
                if (n === 0) { showFieldWarning(input, 'El stock actual es 0 (agotado)'); } else { showFieldSuccess(input); }
                break;
            }
            case 'stock_minimo': {
                if (!valor.length) { esValido = false; showFieldError(input, 'Este campo es obligatorio'); break; }
                const n = parseInt(valor);
                if (isNaN(n) || n < 0) { esValido = false; showFieldError(input, 'Ingrese un número válido mayor o igual a 0'); break; }
                if (n === 0) { showFieldWarning(input, 'Se recomienda un stock mínimo mayor a 0'); } else { showFieldSuccess(input); }
                break;
            }
            break;
            case 'precio_compra':
            case 'precio_venta':
                if (!valor.length) { esValido = false; showFieldError(input, 'Este campo es obligatorio'); break; }
                const p = parseFloat(valor);
                if (isNaN(p) || p <= 0) { esValido = false; showFieldError(input, 'Ingrese un precio válido mayor a 0'); break; }
                showFieldSuccess(input);
                break;
            case 'fecha_fabricacion':
            case 'fecha_vencimiento':
                if (!valor.length) { esValido = false; showFieldError(input, 'La fecha es obligatoria'); break; }
                esValido = true;
                break;
            default:
                if (input.hasAttribute('required') && !valor.length) { esValido = false; showFieldError(input, 'Este campo es obligatorio'); } else { showFieldSuccess(input); }
        }

        // Validación adicional para fechas
        if (nombre === 'fecha_vencimiento' && esValido) {
            const fechaFab = formAgregarProducto.querySelector('input[name="fecha_fabricacion"]').value;
            const hoy = new Date(); hoy.setHours(0,0,0,0);
            const ven = new Date(valor);
            const fab = fechaFab ? new Date(fechaFab) : null;
            const sieteDias = new Date(); sieteDias.setDate(sieteDias.getDate() + 7);
            if (ven <= hoy) { esValido = false; showFieldError(input, 'La fecha de vencimiento debe ser posterior a hoy'); }
            else if (fab && ven <= fab) { esValido = false; showFieldError(input, 'La fecha de vencimiento debe ser posterior a la fabricación'); }
            else if (ven < sieteDias) { esValido = false; showFieldError(input, 'La fecha de vencimiento debe ser al menos 7 días desde hoy'); }
            else if (fab) {
                const dif = (ven - fab) / (1000*60*60*24);
                if (dif < 30) { esValido = false; showFieldError(input, 'Debe haber al menos 30 días entre fabricación y vencimiento'); }
                else { showFieldSuccess(input); }
            } else {
                showFieldSuccess(input);
            }
        }

        // Aplicar clases CSS compatibles
        if (esValido) { input.classList.remove('campo-invalido'); input.classList.add('campo-valido'); }
        else { input.classList.remove('campo-valido'); input.classList.add('campo-invalido'); }

        return esValido;
    }

    // Función para validar todo el formulario
    function validarFormulario() {
        const inputs = formAgregarProducto.querySelectorAll('input[required], select[required]');
        let formularioValido = true;

        // Validar cada campo individualmente
        inputs.forEach(input => {
            if (!validarCampo(input)) {
                formularioValido = false;
            }
        });

        // Verificar si hay mensajes de error visibles de las validaciones en tiempo real
        const mensajesError = formAgregarProducto.querySelectorAll('.error-message');
        if (mensajesError.length > 0) {
            formularioValido = false;
        }

        // Verificar si hay campos con clases de error
        const camposConError = formAgregarProducto.querySelectorAll('.border-red-500, .campo-invalido');
        if (camposConError.length > 0) {
            formularioValido = false;
        }

        // Validaciones adicionales específicas
        const nombre = formAgregarProducto.querySelector('input[name="nombre"]').value.trim();
        const concentracion = formAgregarProducto.querySelector('input[name="concentracion"]').value.trim();
        const lote = formAgregarProducto.querySelector('input[name="lote"]').value.trim();
        const codigoBarras = formAgregarProducto.querySelector('input[name="codigo_barras"]').value.trim();
        const stockActual = formAgregarProducto.querySelector('input[name="stock_actual"]').value.trim();
        const stockMinimo = formAgregarProducto.querySelector('input[name="stock_minimo"]').value.trim();
        const precioCompra = formAgregarProducto.querySelector('input[name="precio_compra"]').value.trim();
        const precioVenta = formAgregarProducto.querySelector('input[name="precio_venta"]').value.trim();
        const fechaFab = formAgregarProducto.querySelector('input[name="fecha_fabricacion"]').value;
        const fechaVen = formAgregarProducto.querySelector('input[name="fecha_vencimiento"]').value;

        // Validar que todos los campos requeridos tengan contenido
        if (!nombre || !concentracion || !lote || !codigoBarras || !stockActual || 
            !stockMinimo || !precioCompra || !precioVenta || !fechaFab || !fechaVen) {
            formularioValido = false;
        }

        // Validar formato de código de barras (debe tener exactamente 13 dígitos)
        if (codigoBarras && codigoBarras.length !== 13) {
            formularioValido = false;
        }

        // Validar que los precios sean números válidos y mayores a 0
        if (precioCompra && (isNaN(parseFloat(precioCompra)) || parseFloat(precioCompra) <= 0)) { formularioValido = false; }
        if (precioVenta && (isNaN(parseFloat(precioVenta)) || parseFloat(precioVenta) <= 0)) { formularioValido = false; }

        // Validar que los stocks sean números válidos y mayores a 0
        if (stockActual && (isNaN(parseInt(stockActual)) || parseInt(stockActual) < 0)) { formularioValido = false; }
        if (stockMinimo && (isNaN(parseInt(stockMinimo)) || parseInt(stockMinimo) < 0)) { formularioValido = false; }

        // Validar fechas
        if (fechaFab && fechaVen) {
            const fechaFabricacion = new Date(fechaFab);
            const fechaVencimiento = new Date(fechaVen);
            const hoy = new Date(); hoy.setHours(0,0,0,0);
            const sieteDias = new Date(); sieteDias.setDate(sieteDias.getDate() + 7);
            if (fechaVencimiento <= fechaFabricacion) formularioValido = false;
            if (fechaVencimiento <= hoy) formularioValido = false;
            if (fechaVencimiento < sieteDias) formularioValido = false;
            const dif = (fechaVencimiento - fechaFabricacion) / (1000*60*60*24);
            if (dif < 30) formularioValido = false;
        }

        // Habilitar/deshabilitar botón de guardar
        btnGuardarProducto.disabled = !formularioValido;
        
        // Cambiar el estilo del botón según el estado
        if (formularioValido) {
            btnGuardarProducto.classList.remove('opacity-50', 'cursor-not-allowed');
            btnGuardarProducto.classList.add('hover:bg-blue-700');
        } else {
            btnGuardarProducto.classList.add('opacity-50', 'cursor-not-allowed');
            btnGuardarProducto.classList.remove('hover:bg-blue-700');
        }
        
        return formularioValido;
    }

    // Deshabilitar botón por defecto
    btnGuardarProducto.disabled = true;
    btnGuardarProducto.classList.add('opacity-50', 'cursor-not-allowed');

    // Agregar eventos de validación en tiempo real
    const inputs = formAgregarProducto.querySelectorAll('input, select');
    inputs.forEach(input => {
        // Aplicar clase inicial para campos requeridos
        if (input.hasAttribute('required')) {
            input.classList.add('campo-invalido');
        }

        // Eventos de validación
        input.addEventListener('input', function() {
            validarCampo(this);
            // Usar setTimeout para asegurar que las validaciones en tiempo real se ejecuten primero
            setTimeout(() => validarFormulario(), 50);
        });

        input.addEventListener('change', function() {
            validarCampo(this);
            setTimeout(() => validarFormulario(), 50);
        });

        input.addEventListener('blur', function() {
            validarCampo(this);
            setTimeout(() => validarFormulario(), 50);
        });

        input.addEventListener('keyup', function() {
            setTimeout(() => validarFormulario(), 50);
        });
    });

    // Observar cambios en el DOM para detectar mensajes de error de validaciones en tiempo real
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                // Si se agregaron o quitaron elementos, revalidar
                setTimeout(() => validarFormulario(), 50);
            }
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                // Si cambiaron las clases de algún elemento, revalidar
                setTimeout(() => validarFormulario(), 50);
            }
        });
    });

    // Observar el formulario completo
    observer.observe(formAgregarProducto, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class']
    });

    // Validación inicial
    setTimeout(() => validarFormulario(), 100);

    // Interceptar envío del formulario
    formAgregarProducto.addEventListener('submit', function(e) {
        if (!validarFormulario()) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Formulario incompleto',
                text: 'Por favor, complete todos los campos requeridos correctamente.',
                confirmButtonText: 'Entendido'
            });
        }
    });
});
