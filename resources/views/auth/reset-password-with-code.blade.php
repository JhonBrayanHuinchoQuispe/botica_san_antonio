<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nueva Contraseña - Botica San Antonio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/login.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('imagen/logo.png') }}">
</head>
<body>
    <div class="background-container">
        <div class="blur-overlay"></div>
        <div class="container h-100 d-flex align-items-center justify-content-center">
            <div class="login-card" style="max-width: 450px; width: 95%; margin: 1rem auto; padding: 2rem 1.5rem;">
                <div class="text-center mb-3">
                    <img src="{{ asset('imagen/logo.png') }}" alt="Logo Botica" class="logo mb-2">
                    <h2 class="fw-bold text-danger">Botica San Antonio</h2>
                    <p class="text-muted">Sistema de Administración</p>
                </div>
                
                <div class="text-center mb-4">
                    <i class="fas fa-key text-success mb-3" style="font-size: 3rem;"></i>
                    <h3>Nueva Contraseña</h3>
                    <p class="text-muted">
                        Código verificado correctamente para <strong>{{ $email }}</strong>. 
                        Ahora puedes establecer tu nueva contraseña.
                    </p>
                </div>
                
                <!-- Mensaje de error -->
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                
                <form method="POST" action="{{ route('password.update-with-code') }}" id="resetForm">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <input type="hidden" name="code" value="{{ $code }}">
                    
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control input-field @error('password') is-invalid @enderror" 
                               name="password" placeholder="Nueva contraseña" required id="password">
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                    
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control input-field @error('password_confirmation') is-invalid @enderror" 
                               name="password_confirmation" placeholder="Confirmar nueva contraseña" required id="password_confirmation">
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                            <i class="fas fa-eye" id="password_confirmation-icon"></i>
                        </button>
                    </div>
                    
                    <!-- Indicador de fortaleza de contraseña -->
                    <div class="password-strength mb-3" id="passwordStrength" style="display: none;">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>

                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="login-btn" id="submitBtn" disabled>
                            <span id="btnText">
                                Cambiar Contraseña
                                <i class="fas fa-check ms-2"></i>
                            </span>
                            <span id="btnLoading" style="display: none;">
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                Cambiando...
                            </span>
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <a href="{{ route('login') }}" class="forgot-link">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver al inicio de sesión
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            const confirmField = document.getElementById('password_confirmation');
            const strengthIndicator = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            const submitBtn = document.getElementById('submitBtn');
            const resetForm = document.getElementById('resetForm');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');

            // Función para verificar requisitos de contraseña (solo longitud mínima)
            function checkPasswordRequirements(password) {
                const requirements = {
                    length: password.length >= 6
                };

                // Actualizar indicador visual solo para longitud
                const lengthElement = document.getElementById('req-length');
                if (lengthElement) {
                    const icon = lengthElement.querySelector('i');
                    
                    if (requirements.length) {
                        icon.className = 'fas fa-check text-success';
                        lengthElement.classList.add('text-success');
                        lengthElement.classList.remove('text-danger');
                    } else {
                        icon.className = 'fas fa-times text-danger';
                        lengthElement.classList.add('text-danger');
                        lengthElement.classList.remove('text-success');
                    }
                }

                return requirements;
            }

            // Función para calcular fortaleza de contraseña
            function calculatePasswordStrength(password) {
                if (password.length === 0) return 0;
                
                let score = 0;
                
                // Puntuación basada en longitud
                if (password.length >= 6) score += 30;
                if (password.length >= 8) score += 20;
                if (password.length >= 12) score += 20;
                
                // Bonificaciones por complejidad (opcionales)
                if (/[A-Z]/.test(password)) score += 10; // Mayúscula
                if (/[a-z]/.test(password)) score += 10; // Minúscula
                if (/\d/.test(password)) score += 10; // Número
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score += 10; // Carácter especial
                
                return Math.min(100, score);
            }

            function updatePasswordStrength() {
                const password = passwordField.value;
                const strength = calculatePasswordStrength(password);
                
                // Actualizar barra de progreso
                const strengthFill = document.querySelector('.strength-fill');
                const strengthText = document.getElementById('strengthText');
                
                strengthFill.style.width = strength + '%';
                
                if (strength < 40) {
                    strengthFill.className = 'strength-fill weak';
                    strengthText.textContent = 'Débil';
                    strengthText.className = 'strength-text text-warning';
                } else if (strength < 70) {
                    strengthFill.className = 'strength-fill medium';
                    strengthText.textContent = 'Media';
                    strengthText.style.color = '#fd7e14';
                    strengthText.className = 'strength-text';
                } else {
                    strengthFill.className = 'strength-fill strong';
                    strengthText.textContent = 'Fuerte';
                    strengthText.className = 'strength-text text-success';
                }
                
                // Validar botón después de actualizar la fortaleza
                checkPasswordMatch();
            }

            // Evento para verificar fortaleza de contraseña
            passwordField.addEventListener('input', function() {
                const password = this.value;
                
                if (password.length > 0) {
                    strengthIndicator.style.display = 'block';
                    updatePasswordStrength();
                } else {
                    strengthIndicator.style.display = 'none';
                    checkPasswordMatch();
                }
            });

            // Verificar coincidencia de contraseñas
            function checkPasswordMatch() {
                const password = passwordField.value;
                const confirmPassword = confirmField.value;
                
                // Verificar que la contraseña tenga al menos 6 caracteres
                const hasMinLength = password.length >= 6;
                
                // Verificar que las contraseñas coincidan
                const passwordsMatch = password === confirmPassword;
                
                if (confirmPassword && !passwordsMatch) {
                    confirmField.setCustomValidity('Las contraseñas no coinciden');
                    confirmField.classList.add('is-invalid');
                } else {
                    confirmField.setCustomValidity('');
                    confirmField.classList.remove('is-invalid');
                }
                
                // Habilitar botón solo si tiene mínimo 6 caracteres y las contraseñas coinciden
                submitBtn.disabled = !hasMinLength || !passwordsMatch || !confirmPassword;
            }

            confirmField.addEventListener('input', checkPasswordMatch);
            passwordField.addEventListener('input', checkPasswordMatch);

            // Manejar envío del formulario
            resetForm.addEventListener('submit', function() {
                submitBtn.disabled = true;
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline';
                submitBtn.style.cursor = 'wait';
            });
        });
    </script>
    
    <style>
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #dc3545;
        }

        .input-container {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .password-strength {
            margin-top: -1rem;
            margin-bottom: 1rem;
        }

        .strength-bar {
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .strength-fill {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
            border-radius: 3px;
        }

        .strength-fill.weak {
            background-color: #dc3545;
        }

        .strength-fill.medium {
            background-color: #ffc107;
        }

        .strength-fill.strong {
            background-color: #28a745;
        }

        .login-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }

        .login-btn:disabled {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            cursor: not-allowed;
            opacity: 0.6;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 767.98px) {
            .login-card {
                max-width: 95% !important;
                padding: 1.5rem 1rem !important;
            }
        }
    </style>
</body>
</html>