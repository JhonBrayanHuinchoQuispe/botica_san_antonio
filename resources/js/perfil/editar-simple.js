// Archivo JavaScript simple para perfil - SIN ERRORES
console.log('Iniciando perfil simple...');

// Variables globales
var avatarFile = null;

// Función simple para actualizar preview
function updatePreview(inputId, previewId, defaultText) {
    var input = document.getElementById(inputId);
    var preview = document.getElementById(previewId);
    if (input && preview) {
        var value = input.value.trim();
        preview.textContent = value || defaultText || '';
    }
}

// Función simple para actualizar nombre completo
function updateFullName() {
    var nombres = document.getElementById('nombres');
    var apellidos = document.getElementById('apellidos');
    var nameField = document.getElementById('name');
    if (nombres && apellidos && nameField) {
        var fullName = (nombres.value.trim() + ' ' + apellidos.value.trim()).trim();
        nameField.value = fullName;
        
        // TAMBIÉN ACTUALIZAR las iniciales del avatar si no hay imagen
        var avatarPreview = document.getElementById('avatarPreview');
        if (avatarPreview && avatarPreview.className.indexOf('avatar-placeholder') !== -1) {
            var names = fullName.split(' ');
            var initials = '';
            for (var k = 0; k < names.length && k < 2; k++) {
                if (names[k] && names[k].length > 0) {
                    initials += names[k].charAt(0).toUpperCase();
                }
            }
            if (initials.length === 0) initials = 'U';
            
            avatarPreview.innerHTML = initials;
            console.log('✅ Iniciales actualizadas en tiempo real:', initials);
        }
    }
}

// Función simple para mostrar alertas
function showAlert(type, message) {
    if (typeof Swal !== 'undefined') {
        // Traducir mensajes comunes
        var msg = message || '';
        if (typeof msg === 'string' && msg.toLowerCase().includes('password is incorrect')) {
            msg = 'La contraseña actual es incorrecta.';
        }
        var config = {
            icon: type,
            title: type === 'success' ? '¡Éxito!' : (type === 'warning' ? 'Aviso' : 'Error'),
            text: msg,
            confirmButtonColor: '#e53e3e'
        };
        if (type === 'success') {
            config.showConfirmButton = false;
            config.timer = 1500; // más corto y sin botón
        } else if (type === 'warning') {
            config.showConfirmButton = true;
            config.confirmButtonText = 'Entendido';
        } else {
            config.showConfirmButton = true;
            config.confirmButtonText = 'Entendido';
        }
        config.didOpen = function() {
            var btn = Swal.getConfirmButton();
            if (btn) {
                btn.style.backgroundColor = '#e53e3e';
                btn.style.color = '#fff';
                btn.style.border = 'none';
                btn.style.boxShadow = 'none';
            }
        };
        Swal.fire(config);
    } else {
        alert(message);
    }
}

// Función mejorada para preview INMEDIATO de avatar
function previewAvatar(input) {
    console.log('🖼️ Preview avatar iniciado - TIEMPO REAL');
    
    if (input && input.files && input.files[0]) {
        var file = input.files[0];
        console.log('📁 Archivo seleccionado:', file.name, 'Tamaño:', (file.size/1024/1024).toFixed(2) + 'MB', 'Tipo:', file.type);
        
        // Validar tipo de archivo INMEDIATAMENTE
        if (!file.type.match('image.*')) {
            showAlert('error', 'Por favor selecciona una imagen válida (JPG, PNG, GIF).');
            input.value = ''; // Limpiar input
            return;
        }
        
        // Validar tamaño (máximo 5MB) INMEDIATAMENTE
        if (file.size > 5 * 1024 * 1024) {
            showAlert('error', 'La imagen debe ser menor a 5MB. Tu imagen es de ' + (file.size/1024/1024).toFixed(2) + 'MB');
            input.value = ''; // Limpiar input
            return;
        }
        
        // MOSTRAR LOADING INMEDIATAMENTE (sin modal)
        var avatarPreview = document.getElementById('avatarPreview');
        if (avatarPreview) {
            // Mantener el contenedor en su tamaño de 130x130 (CSS .avatar-preview)
            avatarPreview.className = 'avatar-preview';
            avatarPreview.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: #999; background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius: 50%;"><i class="ri-loader-4-line animate-spin" style="font-size: 2.5rem;"></i></div>';
            console.log('⏳ Loading mostrado en contenedor 130x130');
        }
        
        // Guardar archivo para envío posterior
        avatarFile = file;
        console.log('✅ Avatar file guardado correctamente');
        
        // Leer archivo INMEDIATAMENTE
        var reader = new FileReader();
        reader.onload = function(e) {
            console.log('📷 Imagen cargada, mostrando VISTA PREVIA INMEDIATA...');
            
            // MOSTRAR LA IMAGEN INMEDIATAMENTE como vista previa
            if (avatarPreview) {
                // Mantener tamaño del contenedor (CSS .avatar-preview)
                avatarPreview.className = 'avatar-preview';
                // Crear imagen con estilos apropiados para vista previa
                var imgElement = '<img src="' + e.target.result + '" alt="Vista Previa Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; opacity: 0; transition: opacity 0.3s ease;">';
                avatarPreview.innerHTML = imgElement;
                
                // Animar entrada de la imagen
                setTimeout(function() {
                    var img = avatarPreview.querySelector('img');
                    if (img) {
                        img.style.opacity = '1';
                        console.log('✅ VISTA PREVIA mostrada - Imagen visible');
                    }
                }, 50);
                
                console.log('✅ Avatar PRINCIPAL actualizado con VISTA PREVIA');
                
                // Mostrar botón remover INMEDIATAMENTE
                var removeBtn = document.querySelector('.btn-remover-avatar');
                if (removeBtn) {
                    removeBtn.style.display = 'flex';
                    removeBtn.style.opacity = '0';
                    removeBtn.style.transition = 'opacity 0.3s ease';
                    setTimeout(function() {
                        removeBtn.style.opacity = '1';
                    }, 100);
                    console.log('✅ Botón remover mostrado');
                } else {
                    console.log('⚠️ No se encontró botón remover');
                }
                
                // TAMBIÉN ACTUALIZAR avatares del NAVBAR inmediatamente
                var navbarAvatars = document.querySelectorAll('img[alt*="Avatar de"], img[src*="avatar"], .navbar img[src*="storage"]');
                for (var i = 0; i < navbarAvatars.length; i++) {
                    if (navbarAvatars[i].src && navbarAvatars[i].src.indexOf('storage') !== -1) {
                        navbarAvatars[i].src = e.target.result;
                        navbarAvatars[i].style.transition = 'opacity 0.3s ease';
                        navbarAvatars[i].style.opacity = '0';
                        setTimeout(function(img) {
                            return function() {
                                img.style.opacity = '1';
                            };
                        }(navbarAvatars[i]), 100);
                        console.log('✅ Avatar del NAVBAR actualizado:', i);
                    }
                }
                
                // TAMBIÉN ACTUALIZAR cualquier otro avatar con clase específica
                var otherAvatars = document.querySelectorAll('.avatar-preview:not(#avatarPreview), .user-avatar-img');
                for (var j = 0; j < otherAvatars.length; j++) {
                    if (otherAvatars[j].tagName.toLowerCase() === 'img') {
                        otherAvatars[j].src = e.target.result;
                    } else {
                        otherAvatars[j].innerHTML = imgElement;
                    }
                    console.log('✅ Avatar adicional actualizado:', j);
                }
                
                // NO mostrar modal - solo log silencioso
                console.log('✅ VISTA PREVIA completa - Imagen lista para guardar');
                
            } else {
                console.error('❌ No se encontró elemento avatarPreview');
            }
        };
        
        reader.onerror = function(error) {
            console.error('❌ Error al leer archivo:', error);
            showAlert('error', 'Error al procesar la imagen');
            input.value = ''; // Limpiar input en caso de error
            
            // Restaurar preview en caso de error
            if (avatarPreview) {
                avatarPreview.innerHTML = '<i class="ri-user-3-line" style="font-size: 3rem; color: #ccc;"></i>';
                avatarPreview.className = 'avatar-placeholder';
            }
        };
        
        // INICIAR LECTURA INMEDIATAMENTE
        reader.readAsDataURL(file);
        
    } else {
        console.log('❌ No se seleccionó archivo o input inválido');
    }
}

// Función mejorada para remover avatar
function removeAvatar() {
    console.log('🗑️ Removiendo avatar...');
    
    // Remover directamente sin confirmación para mejor UX
    // Limpiar variable global
    avatarFile = null;
    console.log('✅ Variable avatarFile limpiada');
    
    // Restaurar TODOS los avatares al estado inicial
    var avatarPreview = document.getElementById('avatarPreview');
    if (avatarPreview) {
        // Obtener iniciales del usuario desde el nombre
        var nameField = document.getElementById('name') || document.querySelector('input[name="name"]');
        var userName = nameField ? nameField.value : 'José Antonio Enrique Navarr';
        var names = userName.split(' ');
        var initials = '';
        for (var k = 0; k < names.length && k < 2; k++) {
            if (names[k] && names[k].length > 0) {
                initials += names[k].charAt(0).toUpperCase();
            }
        }
        if (initials.length === 0) initials = 'JA';
        
        // FORZAR las iniciales con estilos directos
        avatarPreview.innerHTML = initials;
        avatarPreview.className = 'avatar-placeholder';
        
        // APLICAR ESTILOS DIRECTAMENTE para asegurar que se vean
        avatarPreview.style.width = '100%';
        avatarPreview.style.height = '100%';
        avatarPreview.style.display = 'flex';
        avatarPreview.style.alignItems = 'center';
        avatarPreview.style.justifyContent = 'center';
        avatarPreview.style.fontSize = '3.2rem';
        avatarPreview.style.fontWeight = '700';
        avatarPreview.style.color = 'white';
        avatarPreview.style.background = 'linear-gradient(135deg, #e53e3e, #feb2b2)';
        avatarPreview.style.textAlign = 'center';
        avatarPreview.style.lineHeight = '1';
        avatarPreview.style.fontFamily = 'Arial, sans-serif';
        avatarPreview.style.textShadow = '0 1px 3px rgba(0, 0, 0, 0.3)';
        avatarPreview.style.letterSpacing = '1px';
        
        console.log('✅ Avatar PRINCIPAL restaurado con iniciales FORZADAS:', initials);
        
        // TAMBIÉN RESTAURAR avatares del NAVBAR al placeholder con iniciales
        var navbarAvatars = document.querySelectorAll('img[alt*="Avatar de"], img[src*="avatar"], .navbar img[src*="storage"]');
        for (var i = 0; i < navbarAvatars.length; i++) {
            if (navbarAvatars[i].src && navbarAvatars[i].src.indexOf('storage') !== -1) {
                // Crear un placeholder con iniciales para el navbar
                var canvas = document.createElement('canvas');
                canvas.width = 100;
                canvas.height = 100;
                var ctx = canvas.getContext('2d');
                
                // Fondo con gradiente
                var gradient = ctx.createLinearGradient(0, 0, 100, 100);
                gradient.addColorStop(0, '#e53e3e');
                gradient.addColorStop(1, '#feb2b2');
                ctx.fillStyle = gradient;
                ctx.fillRect(0, 0, 100, 100);
                
                // Texto con iniciales
                ctx.fillStyle = 'white';
                ctx.font = 'bold 35px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(initials, 50, 50);
                
                navbarAvatars[i].src = canvas.toDataURL();
                console.log('✅ Avatar del NAVBAR restaurado con iniciales:', initials);
            }
        }
        
        // TAMBIÉN RESTAURAR cualquier otro avatar con iniciales
        var otherAvatars = document.querySelectorAll('.avatar-preview:not(#avatarPreview), .user-avatar-img');
        for (var j = 0; j < otherAvatars.length; j++) {
            if (otherAvatars[j].tagName.toLowerCase() === 'img') {
                var canvas2 = document.createElement('canvas');
                canvas2.width = 100;
                canvas2.height = 100;
                var ctx2 = canvas2.getContext('2d');
                
                // Fondo con gradiente
                var gradient2 = ctx2.createLinearGradient(0, 0, 100, 100);
                gradient2.addColorStop(0, '#e53e3e');
                gradient2.addColorStop(1, '#feb2b2');
                ctx2.fillStyle = gradient2;
                ctx2.fillRect(0, 0, 100, 100);
                
                // Texto con iniciales
                ctx2.fillStyle = 'white';
                ctx2.font = 'bold 35px Arial';
                ctx2.textAlign = 'center';
                ctx2.textBaseline = 'middle';
                ctx2.fillText(initials, 50, 50);
                
                otherAvatars[j].src = canvas2.toDataURL();
            } else {
                otherAvatars[j].innerHTML = placeholderHtml;
                otherAvatars[j].className = otherAvatars[j].className.replace('avatar-preview', 'avatar-placeholder');
            }
            console.log('✅ Avatar adicional restaurado con iniciales:', initials);
        }
        
    } else {
        console.error('❌ No se encontró elemento avatarPreview');
    }
    
    // Limpiar todos los posibles inputs de avatar
    var avatarInputs = ['avatarInput', 'avatar'];
    for (var i = 0; i < avatarInputs.length; i++) {
        var input = document.getElementById(avatarInputs[i]);
        if (input) {
            input.value = '';
            console.log('✅ Input ' + avatarInputs[i] + ' limpiado');
        }
    }
    
    // Ocultar botón remover
    var removeBtn = document.querySelector('.btn-remover-avatar');
    if (removeBtn) {
        removeBtn.style.display = 'none';
        console.log('✅ Botón remover ocultado');
    } else {
        console.log('⚠️ No se encontró botón remover');
    }
    
    console.log('✅ Avatar removido completamente - SIN MODAL');
}

// Función simple para toggle password
function togglePassword(inputId) {
    var input = document.getElementById(inputId);
    if (input) {
        var button = input.parentNode.querySelector('.toggle-password');
        var icon = button ? button.querySelector('iconify-icon') : null;
        
        if (input.type === 'password') {
            input.type = 'text';
            if (icon) icon.setAttribute('icon', 'heroicons:eye-slash-solid');
        } else {
            input.type = 'password';
            if (icon) icon.setAttribute('icon', 'heroicons:eye-solid');
        }
    }
}

// Función simple para resetear formulario de contraseña
function resetPasswordForm() {
    var form = document.getElementById('formCambiarPassword');
    if (form) {
        form.reset();
    }
}

// Función simple para reenviar verificación
function reenviarVerificacion() {
    showAlert('info', 'Función de verificación no implementada');
}

// Función para actualizar navbar (vacía por ahora)
function updateNavbarAvatar(url, user) {
    console.log('Actualizando navbar avatar:', url);
}

// Función para actualizar navbar placeholder (vacía por ahora)
function updateNavbarAvatarToPlaceholder() {
    console.log('Actualizando navbar placeholder');
}

// Función mejorada para enviar formulario
function submitPersonalInfo() {
    console.log('📤 Enviando información personal...');
    var form = document.getElementById('formInformacionPersonal');
    if (!form) {
        console.error('❌ No se encontró el formulario');
        return;
    }
    
    // Detectar si no hay cambios
    var inputs = Array.from(form.querySelectorAll('input, textarea'));
    var noChanges = inputs.every(function(el){
        if (el.name === '_token' || el.name === '_method') return true;
        return (el.value || '') === (el.getAttribute('data-initial') || el.defaultValue || '');
    }) && !avatarFile;
    if (noChanges) {
        showAlert('warning', 'No se realizó ningún cambio');
        return;
    }
    var formData = new FormData(form);
    
    // Agregar avatar si existe
    if (avatarFile) {
        formData.append('avatar', avatarFile);
        console.log('📎 Avatar agregado al FormData:', avatarFile.name, 'Tamaño:', avatarFile.size);
    } else {
        console.log('ℹ️ No hay avatar para enviar');
    }
    
    // Deshabilitar botón de envío
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Procesando...';
        console.log('🔒 Botón deshabilitado');
    }
    
    // Enviar formulario
    fetch('/perfil/actualizar', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(function(response) {
        console.log('📥 Respuesta recibida, status:', response.status);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        console.log('📊 Datos procesados:', data);
        
        // Restaurar botón
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<iconify-icon icon="heroicons:check-circle-solid"></iconify-icon> Guardar Cambios';
            console.log('🔓 Botón habilitado');
        }
        
        if (data.success) {
            console.log('✅ Actualización exitosa');
            
            // Mostrar mensaje de éxito breve, sin botón
            showAlert('success', 'Información actualizada correctamente');
            
            console.log('🔄 Recargando página en 2 segundos para mostrar cambios...');
            
            // Recargar página después de 1.6 segundos
            setTimeout(function() {
                console.log('🔄 Recargando página ahora...');
                window.location.reload();
            }, 1600);
            
        } else {
            console.error('❌ Error en la respuesta:', data);
            if (data.errors) {
                var firstError = Object.values(data.errors)[0][0];
                showAlert('error', firstError);
                console.error('❌ Error de validación:', firstError);
            } else {
                showAlert('error', data.message || 'Error al actualizar');
                console.error('❌ Error general:', data.message);
            }
        }
    })
    .catch(function(error) {
        console.error('❌ Error en fetch:', error);
        
        // Restaurar botón en caso de error
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<iconify-icon icon="heroicons:check-circle-solid"></iconify-icon> Guardar Cambios';
        }
        
        showAlert('error', 'Error de conexión al actualizar la información');
    });
}

// --- Indicador de fuerza de contraseña ---
function checkPasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    return strength;
}

function updateStrengthIndicator() {
    // Indicador removido
    return;
}

// --- Validación de confirmación de contraseña ---
function validatePasswordConfirmation() {
    var password = document.getElementById('new_password').value;
    var confirm = document.getElementById('password_confirmation').value;
    var matchDiv = document.getElementById('passwordMatch');
    var errorDiv = document.getElementById('error-password_confirmation');
    if (password && confirm) {
        if (password !== confirm) {
            if (matchDiv) matchDiv.innerHTML = '<span style="color:#e53e3e;font-size:13px;">Las contraseñas no coinciden</span>';
            if (errorDiv) errorDiv.textContent = 'Las contraseñas no coinciden';
            return false;
        } else {
            if (matchDiv) matchDiv.innerHTML = '<span style="color:#38a169;font-size:13px;">✔ Coinciden</span>';
            if (errorDiv) errorDiv.textContent = '';
            return true;
        }
    } else {
        if (matchDiv) matchDiv.innerHTML = '';
        if (errorDiv) errorDiv.textContent = '';
        return null;
    }
}

// --- Hook de eventos para fuerza y confirmación ---
document.addEventListener('DOMContentLoaded', function() {
    var newPassword = document.getElementById('new_password');
    var confirmPassword = document.getElementById('password_confirmation');
    if (newPassword) {
        newPassword.addEventListener('input', function() {
            validatePasswordConfirmation();
        });
    }
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            validatePasswordConfirmation();
        });
    }
});

// --- Validación antes de enviar el formulario ---
function submitPasswordChange() {
    console.log('📤 Enviando cambio de contraseña...');
    var form = document.getElementById('formCambiarPassword');
    if (!form) {
        console.error('❌ No se encontró el formulario de contraseña');
        return;
    }
    // Validar longitud mínima y confirmación antes de enviar
    var newPwd = document.getElementById('new_password').value || '';
    if (newPwd.length < 6) {
        var err = document.getElementById('error-password');
        if (err) err.textContent = 'La contraseña debe tener mínimo 6 caracteres';
        showAlert('error', 'La contraseña debe tener mínimo 6 caracteres');
        return;
    }
    if (validatePasswordConfirmation() === false) {
        showAlert('error', 'Las contraseñas no coinciden');
        return;
    }
    var formData = new FormData(form);
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Procesando...';
    }
    fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(function(response) {
        return response.json().then(function(data){
            return { ok: response.ok, data: data };
        });
    })
    .then(function(result) {
        var data = result.data;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<iconify-icon icon="heroicons:shield-check-solid"></iconify-icon> Actualizar Contraseña';
        }
        if (result.ok && data.success) {
            showAlert('success', data.message || 'Contraseña actualizada correctamente');
            resetPasswordForm();
            document.getElementById('passwordMatch').innerHTML = '';
        } else {
            // Validar errores específicos
            if (data.errors && data.errors.current_password) {
                var errorDiv = document.getElementById('error-current_password');
                if (errorDiv) errorDiv.textContent = data.errors.current_password[0];
                // Forzar español si backend devuelve inglés
                var msg = data.errors.current_password[0];
                if (typeof msg === 'string' && msg.toLowerCase().includes('password')) {
                    msg = 'La contraseña actual es incorrecta.';
                }
                showAlert('error', msg);
            } else if (data.errors && data.errors.password_confirmation) {
                var errorDiv = document.getElementById('error-password_confirmation');
                if (errorDiv) errorDiv.textContent = data.errors.password_confirmation[0];
                showAlert('error', data.errors.password_confirmation[0]);
            } else {
                showAlert('error', data.message || 'Error al cambiar la contraseña');
            }
        }
    })
    .catch(async function(error) {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<iconify-icon icon="heroicons:shield-check-solid"></iconify-icon> Actualizar Contraseña';
        }
        let errorMsg = 'Error al cambiar la contraseña';
        if (error && error.json) {
            try {
                const errData = await error.json();
                if (errData && errData.errors) {
                    if (errData.errors.current_password) {
                        var errorDiv = document.getElementById('error-current_password');
                        if (errorDiv) errorDiv.textContent = errData.errors.current_password[0];
                    errorMsg = errData.errors.current_password[0];
                } else if (errData.errors.password_confirmation) {
                        var errorDiv = document.getElementById('error-password_confirmation');
                        if (errorDiv) errorDiv.textContent = errData.errors.password_confirmation[0];
                        errorMsg = errData.errors.password_confirmation[0];
                    } else {
                        errorMsg = Object.values(errData.errors)[0][0];
                    }
                } else if (errData && errData.message) {
                    errorMsg = errData.message;
                }
            } catch (e) {}
        }
        if (typeof errorMsg === 'string' && errorMsg.toLowerCase().includes('password is incorrect')) {
            errorMsg = 'La contraseña actual es incorrecta.';
        }
        showAlert('error', errorMsg);
    });
}

// Exportar funciones a window INMEDIATAMENTE
window.updatePreview = updatePreview;
window.updateFullName = updateFullName;
window.previewAvatar = previewAvatar;
window.removeAvatar = removeAvatar;
window.togglePassword = togglePassword;
window.resetPasswordForm = resetPasswordForm;
window.reenviarVerificacion = reenviarVerificacion;
window.updateNavbarAvatar = updateNavbarAvatar;
window.updateNavbarAvatarToPlaceholder = updateNavbarAvatarToPlaceholder;

console.log('Funciones exportadas correctamente');

// Función para asegurar que el avatar muestre las iniciales correctamente
function ensureAvatarInitials() {
    var avatarPreview = document.getElementById('avatarPreview');
    if (avatarPreview) {
        console.log('🔍 Verificando avatar:', avatarPreview.className, avatarPreview.innerHTML);
        
        // Si es placeholder o no tiene imagen, asegurar que muestre iniciales
        if (avatarPreview.className.indexOf('avatar-placeholder') !== -1 || 
            !avatarPreview.querySelector('img')) {
            
            // Obtener iniciales del usuario
            var nameField = document.getElementById('name') || document.querySelector('input[name="name"]');
            var userName = nameField ? nameField.value : 'José Antonio Enrique Navarr';
            var names = userName.split(' ');
            var initials = '';
            for (var k = 0; k < names.length && k < 2; k++) {
                if (names[k] && names[k].length > 0) {
                    initials += names[k].charAt(0).toUpperCase();
                }
            }
            if (initials.length === 0) initials = 'JA';
            
            // FORZAR las iniciales con estilos directos
            avatarPreview.innerHTML = initials;
            avatarPreview.className = 'avatar-placeholder';
            
            // APLICAR ESTILOS DIRECTAMENTE
            avatarPreview.style.width = '100%';
            avatarPreview.style.height = '100%';
            avatarPreview.style.display = 'flex';
            avatarPreview.style.alignItems = 'center';
            avatarPreview.style.justifyContent = 'center';
            avatarPreview.style.fontSize = '3.2rem';
            avatarPreview.style.fontWeight = '700';
            avatarPreview.style.color = 'white';
            avatarPreview.style.background = 'linear-gradient(135deg, #e53e3e, #feb2b2)';
            avatarPreview.style.textAlign = 'center';
            avatarPreview.style.lineHeight = '1';
            avatarPreview.style.fontFamily = 'Arial, sans-serif';
            avatarPreview.style.textShadow = '0 1px 3px rgba(0, 0, 0, 0.3)';
            avatarPreview.style.letterSpacing = '1px';
            avatarPreview.style.borderRadius = '50%';
            
            console.log('✅ Iniciales FORZADAS en avatar:', initials);
        }
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM listo, inicializando perfil...');
    
    // Asegurar que el avatar muestre iniciales si es necesario
    ensureAvatarInitials();
    
    // Configurar tabs
    var tabButtons = document.querySelectorAll('.tab-btn');
    var tabContents = document.querySelectorAll('.tab-content');
    
    for (var i = 0; i < tabButtons.length; i++) {
        tabButtons[i].addEventListener('click', function() {
            var targetTab = this.getAttribute('data-tab');
            
            for (var j = 0; j < tabButtons.length; j++) {
                tabButtons[j].classList.remove('active');
            }
            for (var k = 0; k < tabContents.length; k++) {
                tabContents[k].classList.remove('active');
            }
            
            this.classList.add('active');
            var targetContent = document.getElementById(targetTab);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    }
    
    // Configurar formulario
    var formPersonal = document.getElementById('formInformacionPersonal');
    if (formPersonal) {
        formPersonal.addEventListener('submit', function(e) {
            e.preventDefault();
            submitPersonalInfo();
        });
    }
    
    // Configurar avatar con ÚNICO event listener (sin duplicados)
    console.log('🔧 Configurando avatar - SIN DUPLICADOS...');
    
    var avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        // LIMPIAR cualquier event listener previo
        avatarInput.removeEventListener('change', previewAvatar);
        
        // Agregar ÚNICO event listener
        avatarInput.addEventListener('change', function(e) {
            console.log('📁 Cambio detectado en input de avatar - ÚNICO LISTENER');
            previewAvatar(this);
        });
        console.log('✅ Event listener ÚNICO agregado a avatarInput');
    } else {
        console.error('❌ No se encontró input avatarInput');
    }
    
    // Configurar botón cambiar avatar con ÚNICO event listener
    var btnCambiarAvatar = document.querySelector('.btn-cambiar-avatar');
    if (btnCambiarAvatar) {
        // LIMPIAR cualquier event listener previo
        var newBtn = btnCambiarAvatar.cloneNode(true);
        btnCambiarAvatar.parentNode.replaceChild(newBtn, btnCambiarAvatar);
        btnCambiarAvatar = newBtn;
        
        // Agregar ÚNICO event listener
        btnCambiarAvatar.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🖱️ Click en botón cambiar avatar - ÚNICO LISTENER');
            
            if (avatarInput) {
                console.log('🎯 Activando selector de archivos...');
                avatarInput.click();
            } else {
                console.error('❌ No hay input de avatar disponible');
                showAlert('error', 'Error: No se encontró el selector de archivos');
            }
        });
        console.log('✅ Event listener ÚNICO agregado al botón cambiar avatar');
    } else {
        console.error('❌ No se encontró botón cambiar avatar');
    }
    
    // Configurar botón remover avatar con ÚNICO event listener
    var btnRemoverAvatar = document.querySelector('.btn-remover-avatar');
    if (btnRemoverAvatar) {
        // LIMPIAR cualquier event listener previo
        var newRemoveBtn = btnRemoverAvatar.cloneNode(true);
        btnRemoverAvatar.parentNode.replaceChild(newRemoveBtn, btnRemoverAvatar);
        btnRemoverAvatar = newRemoveBtn;
        
        // Agregar ÚNICO event listener
        btnRemoverAvatar.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🖱️ Click en botón remover avatar - ÚNICO LISTENER');
            removeAvatar();
        });
        console.log('✅ Event listener ÚNICO agregado al botón remover avatar');
    } else {
        console.log('ℹ️ No se encontró botón remover avatar (normal si no hay imagen)');
    }
    
    console.log('Perfil inicializado correctamente');
    
    // Funciones de test para debugging
    window.testAvatarPreview = function() {
        console.log('🧪 Test: Probando vista previa...');
        var avatarPreview = document.getElementById('avatarPreview');
        if (avatarPreview) {
            avatarPreview.removeAttribute('style');
            avatarPreview.className = 'avatar-preview';
            avatarPreview.innerHTML = '<img src="https://via.placeholder.com/130x130/e53e3e/ffffff?text=JA" alt="Test" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;">';
            console.log('✅ Vista previa de test aplicada');
        }
    };
    
    window.testAvatarInitials = function() {
        console.log('🧪 Test: Probando iniciales...');
        if (typeof window.forceAvatarInitials === 'function') {
            window.forceAvatarInitials();
        }
    };
    
    console.log('✅ Funciones de test disponibles:');
    console.log('  - window.testAvatarPreview() - Probar vista previa');
    console.log('  - window.testAvatarInitials() - Probar iniciales');
    
    // Configurar formulario de cambio de contraseña
    var formPassword = document.getElementById('formCambiarPassword');
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            e.preventDefault();
            submitPasswordChange();
        });
    }
});