<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Recuperar Contraseña - Botica San Antonio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/login.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('imagen/logo.png') }}">
</head>
<body>
    <div class="background-container">
        <div class="blur-overlay"></div>
        <div class="container h-100 d-flex align-items-center justify-content-center">
            <div class="login-card" style="max-width: 400px; width: 95%; margin: 1rem auto; padding: 2rem 1.5rem;">
                <div class="text-center mb-3">
                    <img src="{{ asset('imagen/logo.png') }}" alt="Logo Botica" class="logo mb-2">
                    <h2 class="fw-bold text-danger">Botica San Antonio</h2>
                    <p class="text-muted">Sistema de Administración</p>
                </div>
                
                <div class="text-center mb-4">
                    <i class="fas fa-key text-danger mb-3" style="font-size: 3rem;"></i>
                    <h3>Recuperar Contraseña</h3>
                    <p class="text-muted">
                        ¿Olvidaste tu contraseña? No te preocupes. Ingresa tu correo electrónico 
                        y te enviaremos un enlace para restablecer tu contraseña.
                    </p>
                </div>
                
                <!-- Mensaje de éxito -->
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('status') }}
                    </div>
                @endif
                
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    
                    <div class="input-container">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control input-field @error('email') is-invalid @enderror" 
                               name="email" value="{{ old('email') }}" placeholder="Correo electrónico" required autofocus>
                    </div>
                    
                    @error('email')
                        <div class="alert alert-danger" role="alert" style="margin-top: -1rem; margin-bottom: 1.5rem; font-size: 0.9rem; padding: 0.75rem; border-radius: 8px;">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>{{ $message }}</strong>
                        </div>
                    @enderror
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="login-btn" id="submitBtn">
                            <span id="btnText">
                                Enviar Enlace de Recuperación
                                <i class="fas fa-paper-plane ms-2"></i>
                            </span>
                            <span id="btnLoading" style="display: none;">
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                Enviando...
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
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            
            // Deshabilitar botón y mostrar spinner
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
            
            // Cambiar cursor
            submitBtn.style.cursor = 'wait';
        });
    </script>
    
    <style>
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
            }
            
            .input-container {
                margin-bottom: 1.5rem !important;
            }
            
            .form-control {
                height: 46px !important;
                font-size: 0.9rem !important;
            }
            
            .login-btn {
                height: 50px !important;
                font-size: 0.95rem !important;
                padding: 12px !important;
            }
            
            .container {
                padding: 0 0.5rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .login-card {
                max-width: 95% !important;
                width: 95% !important;
                padding: 1.2rem 0.8rem !important;
                margin: 0.5rem auto !important;
            }
            
            h2 {
                font-size: 1.1rem !important;
            }
            
            h3 {
                font-size: 1.2rem !important;
            }
            
            .logo {
                max-width: 50px !important;
            }
            
            .text-muted {
                font-size: 0.8rem !important;
            }
            
            .alert {
                font-size: 0.85rem !important;
                padding: 0.75rem !important;
                margin-bottom: 1rem !important;
            }
        }
    </style>
</body>
</html>
