<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/login.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('imagen/logo.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('assets/js/admin/login.js') }}" defer></script>
</head>
<body>
    <div class="background-container">
        <div class="blur-overlay"></div>
        <div class="container h-100 d-flex align-items-center justify-content-center">
            <div class="login-card">
                <div class="text-center mb-3">
                    <img src="{{ asset('imagen/logo.png') }}" alt="Logo Botica" class="logo mb-2">
                    <h2 class="fw-bold text-danger">Botica San Antonio</h2>
                    <p class="text-muted">Sistema de Administración</p>
                </div>
                
                <h3 class="text-center">Iniciar Sesión</h3>
                
                
                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div class="input-container">
                        <i class="fas fa-user input-icon" aria-hidden="true"></i>
                        <input type="email" class="form-control input-field @error('email') is-invalid @enderror" 
                               name="email" value="{{ old('email') }}" placeholder="Correo electrónico" required autofocus aria-label="Correo electrónico" autocomplete="username">
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control input-field @error('password') is-invalid @enderror" 
                               name="password" placeholder="Contraseña" required>
                        <span class="password-toggle" onclick="togglePassword()" role="button" aria-label="Mostrar u ocultar contraseña">
                            <i class="fa fa-eye-slash" id="toggleIcon"></i>
                        </span>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                                   {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                Recordar sesión
                            </label>
                        </div>
                        <a href="{{ route('password.request') }}" class="forgot-link" style="color: #dc3545 !important; font-weight: 500; text-decoration: none; font-size: 14px;">¿Olvidó su contraseña?</a>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="login-btn">
                            Iniciar Sesión
                            <i class="fas fa-sign-in-alt ms-2"></i>
                        </button>
                    </div>
                    
                    @if(session('error'))
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/admin/login.js') }}"></script>
    
    @if(session('password_reset_success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Contraseña Restablecida!',
            text: '{{ session('password_reset_success') }}',
            confirmButtonText: 'Continuar',
            confirmButtonColor: '#dc3545',
            background: '#fff',
            color: '#333',
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        });
    </script>
    @endif
</body>
</html>