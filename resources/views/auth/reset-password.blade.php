<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Restablecer Contraseña - Botica San Antonio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/login.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('imagen/logo.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="background-container">
        <div class="blur-overlay"></div>
        <div class="container h-100 d-flex align-items-center justify-content-center">
            <div class="login-card" style="max-width: 420px; width: 95%; margin: 1rem auto; padding: 2rem 1.5rem;">
                <div class="text-center mb-3">
                    <img src="{{ asset('imagen/logo.png') }}" alt="Logo Botica" class="logo mb-2">
                    <h2 class="fw-bold text-danger">Botica San Antonio</h2>
                    <p class="text-muted">Sistema de Administración</p>
                </div>
                
                <div class="text-center mb-4">
                    <i class="fas fa-shield-alt text-danger mb-3" style="font-size: 3rem;"></i>
                    <h3>Restablecer Contraseña</h3>
                    <p class="text-muted">
                        Ingresa tu nueva contraseña. Asegúrate de que sea segura y fácil de recordar.
                    </p>
                </div>
                
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

                    <!-- Token de restablecimiento -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <!-- Email -->
                    <div class="input-container">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control input-field @error('email') is-invalid @enderror" 
                               name="email" value="{{ old('email', $request->email) }}" placeholder="Correo electrónico" required readonly>
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
        </div>

                    <!-- Nueva Contraseña -->
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control input-field @error('password') is-invalid @enderror" 
                               name="password" placeholder="Nueva contraseña" required id="password">
                        <span class="password-toggle" onclick="togglePassword('password')">
                            <i class="fa fa-eye-slash" id="toggleIconPassword"></i>
                        </span>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
        </div>

                    <!-- Confirmar Contraseña -->
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control input-field @error('password_confirmation') is-invalid @enderror" 
                               name="password_confirmation" placeholder="Confirmar nueva contraseña" required id="password_confirmation">
                        <span class="password-toggle" onclick="togglePassword('password_confirmation')">
                            <i class="fa fa-eye-slash" id="toggleIconPasswordConfirmation"></i>
                        </span>
                        @error('password_confirmation')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    <!-- Indicador de fuerza de contraseña -->
                    <div class="password-strength mb-3" id="passwordStrength" style="display: none;">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <small class="strength-text" id="strengthText"></small>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="login-btn" id="resetBtn" style="background: linear-gradient(45deg, #dc3545, #e74c3c); color: white; font-weight: 600; border: none; padding: 15px; border-radius: 10px; font-size: 16px; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);">
                            <span id="resetBtnText">
                                <i class="fas fa-shield-check me-2"></i>
                                Restablecer Contraseña
                            </span>
                            <span id="resetBtnLoading" style="display: none;">
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                Restableciendo...
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
        // Spinner para el formulario
        document.querySelector('form').addEventListener('submit', function() {
            const resetBtn = document.getElementById('resetBtn');
            const resetBtnText = document.getElementById('resetBtnText');
            const resetBtnLoading = document.getElementById('resetBtnLoading');
            
            // Deshabilitar botón y mostrar spinner
            resetBtn.disabled = true;
            resetBtnText.style.display = 'none';
            resetBtnLoading.style.display = 'inline';
            
            // Cambiar cursor
            resetBtn.style.cursor = 'wait';
        });
    </script>
    <script>
        // Función para mostrar/ocultar contraseña
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById('toggleIcon' + fieldId.charAt(0).toUpperCase() + fieldId.slice(1));
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fa fa-eye';
            } else {
                field.type = 'password';
                icon.className = 'fa fa-eye-slash';
            }
        }
        
        // Indicador de fuerza de contraseña
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return;
            }
            
            strengthDiv.style.display = 'block';
            
            let strength = 0;
            let text = '';
            let color = '';
            
            // Calcular fuerza
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            switch (strength) {
                case 0:
                case 1:
                    text = 'Muy débil';
                    color = '#dc3545';
                    break;
                case 2:
                    text = 'Débil';
                    color = '#fd7e14';
                    break;
                case 3:
                    text = 'Regular';
                    color = '#ffc107';
                    break;
                case 4:
                    text = 'Fuerte';
                    color = '#20c997';
                    break;
                case 5:
                    text = 'Muy fuerte';
                    color = '#198754';
                    break;
            }
            
            strengthFill.style.width = (strength * 20) + '%';
            strengthFill.style.backgroundColor = color;
            strengthText.textContent = text;
            strengthText.style.color = color;
        });
    </script>
    
    <style>
        .password-strength {
            margin-top: 0.5rem;
        }
        
        .strength-bar {
            height: 4px;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }
        
        .strength-fill {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
            border-radius: 2px;
        }
        
        .strength-text {
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* CSS Responsivo */
        @media (max-width: 767.98px) {
            body {
                overflow-y: auto;
                background-color: #f8f9fa;
            }
            
            .background-container {
                position: fixed;
                padding: 0.5rem;
                min-height: 100vh;
                display: flex !important;
                align-items: center !important;
            }
            
            .container {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 0.5rem !important;
                margin: 0 !important;
            }
            
            .login-card {
                max-width: 90% !important;
                width: 90% !important;
                padding: 1.5rem 1rem !important;
                margin: 1rem auto !important;
                border-radius: 12px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
            
            .logo {
                max-width: 65px !important;
            }
            
            h2, h3 {
                font-size: 1.3rem !important;
                margin-bottom: 1rem !important;
            }
            
            .text-muted {
                font-size: 0.85rem !important;
            }
            
            .input-container {
                margin-bottom: 1.5rem !important;
            }
            
            .form-control {
                height: 46px !important;
                font-size: 0.9rem !important;
                padding-left: 40px !important;
            }
            
            .input-icon {
                font-size: 1rem !important;
                left: 12px !important;
            }
            
            .password-toggle {
                right: 12px !important;
            }
            
            .login-btn {
                height: 50px !important;
                font-size: 0.95rem !important;
                padding: 12px !important;
            }
            
            .container {
                padding: 0 0.5rem !important;
            }
            
            .password-strength {
                margin-top: 0.3rem !important;
            }
            
            .strength-text {
                font-size: 0.7rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .login-card {
                max-width: 98% !important;
                padding: 1.2rem 0.8rem !important;
            }
            
            h2 {
                font-size: 1.2rem !important;
            }
            
            .logo {
                max-width: 55px !important;
            }
            
            .form-control {
                height: 44px !important;
                font-size: 0.85rem !important;
                padding-left: 35px !important;
            }
            
            .input-icon {
                left: 10px !important;
                font-size: 0.9rem !important;
            }
            
            .password-toggle {
                right: 10px !important;
            }
            
            .login-btn {
                height: 48px !important;
                font-size: 0.9rem !important;
            }
        }
        
        @media (max-height: 600px) {
            .login-card {
                padding: 1rem !important;
                margin-top: 0.5rem !important;
                margin-bottom: 0.5rem !important;
            }
            
            .input-container {
                margin-bottom: 1rem !important;
            }
            
            .form-control {
                height: 42px !important;
            }
            
            .login-btn {
                height: 42px !important;
                padding: 8px !important;
            }
        }
    </style>
</body>
</html>
