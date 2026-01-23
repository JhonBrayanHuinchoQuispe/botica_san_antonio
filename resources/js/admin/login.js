function togglePassword() {
    const passwordInput = document.querySelector('input[name="password"]');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.querySelector('input[name="email"]');
    const passwordInput = document.querySelector('input[name="password"]');
    let formInteracted = false;
    let formSubmitted = false;
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function showError(input, message, isRequiredError = false) {
        if (isRequiredError && !formSubmitted) return;
        
        if (!isRequiredError && !formInteracted) return;
        
        input.classList.add('is-invalid');
        
        let feedbackElement = input.nextElementSibling;
        if (!feedbackElement || !feedbackElement.classList.contains('invalid-feedback')) {
            feedbackElement = document.createElement('div');
            feedbackElement.className = 'invalid-feedback';
            input.parentNode.insertBefore(feedbackElement, input.nextSibling);
        }
        
        feedbackElement.textContent = message;
        feedbackElement.style.display = 'block';
    }
    
    function hideError(input) {
        input.classList.remove('is-invalid');
        const feedbackElement = input.nextElementSibling;
        if (feedbackElement && feedbackElement.classList.contains('invalid-feedback')) {
            feedbackElement.style.display = 'none';
        }
    }
    
    const formInputs = document.querySelectorAll('input');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            formInteracted = true;
        });
    });
    
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            if (this.value.length === 0 && formSubmitted) {
                showError(this, 'El campo email es requerido', true);
            } else if (this.value.length > 0 && !isValidEmail(this.value)) {
                showError(this, 'Ingrese un email válido', false);
                formInteracted = true;
            } else {
                hideError(this);
            }
        });
        
        emailInput.addEventListener('blur', function() {
            if (this.value.length === 0 && formSubmitted) {
                showError(this, 'El campo email es requerido', true);
            } else if (this.value.length > 0 && !isValidEmail(this.value)) {
                showError(this, 'Ingrese un email válido', false);
                formInteracted = true;
            } else {
                hideError(this);
            }
        });
    }
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            if (this.value.length === 0 && formSubmitted) {
                showError(this, 'El campo contraseña es requerido', true);
            } else if (this.value.length > 0 && this.value.length < 6) {
                showError(this, 'La contraseña debe tener mínimo 6 caracteres', false);
                formInteracted = true;
            } else {
                hideError(this);
            }
        });
        
        passwordInput.addEventListener('blur', function() {
            if (this.value.length === 0 && formSubmitted) {
                showError(this, 'El campo contraseña es requerido', true);
            } else if (this.value.length > 0 && this.value.length < 6) {
                showError(this, 'La contraseña debe tener mínimo 6 caracteres', false);
                formInteracted = true;
            } else {
                hideError(this);
            }
        });
    }
    
    const loginForm = document.querySelector('form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            formInteracted = true;
            formSubmitted = true;
            let hasErrors = false;
            
            if (emailInput) {
                if (emailInput.value.length === 0) {
                    showError(emailInput, 'El campo email es requerido', true);
                    hasErrors = true;
                } else if (!isValidEmail(emailInput.value)) {
                    showError(emailInput, 'Ingrese un email válido', false);
                    hasErrors = true;
                }
            }
            
            if (passwordInput) {
                if (passwordInput.value.length === 0) {
                    showError(passwordInput, 'El campo contraseña es requerido', true);
                    hasErrors = true;
                } else if (passwordInput.value.length < 6) {
                    showError(passwordInput, 'La contraseña debe tener mínimo 6 caracteres', false);
                    hasErrors = true;
                }
            }
            
            if (hasErrors) {
                event.preventDefault();
                return;
            }
            
            const loginBtn = document.querySelector('.login-btn');
            
            loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Iniciando sesión...';
            loginBtn.disabled = true;
        });
    }
    
    const flashError = document.querySelector('.alert-danger:not(.d-none)');
    if (flashError) {
        setTimeout(() => {
            flashError.classList.add('d-none');
        }, 3000);
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('login_error')) {
        let errorContainer = document.getElementById('loginError');
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.id = 'loginError';
            errorContainer.className = 'alert alert-danger mt-3';
            errorContainer.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Usuario o contraseña incorrectos';
            loginForm.appendChild(errorContainer);
        } else {
            errorContainer.classList.remove('d-none');
        }
        
        setTimeout(() => {
            errorContainer.classList.add('d-none');
        }, 3000);
    }
});

