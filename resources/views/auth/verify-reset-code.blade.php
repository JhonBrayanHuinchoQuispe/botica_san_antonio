<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verificar Código - Botica San Antonio</title>
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
                </div>
                
                <div class="text-center mb-4">
                    <h3>Verificar Código</h3>
                </div>
                
                
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                
                <div class="alert alert-success" role="alert" id="codeMessage">
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="messageText">Te hemos enviado un código de verificación a tu email.</span>
                </div>
                
                <form method="POST" action="{{ route('password.verify-code') }}" id="verifyForm">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    
                    <div class="mb-4">
                        <label for="code" class="form-label text-center d-block mb-3">
                            <strong>Código de Verificación</strong>
                        </label>
                        <div class="code-input-container d-flex justify-content-center gap-2">
                            <input type="text" class="code-digit form-control text-center" maxlength="1" data-index="0">
                            <input type="text" class="code-digit form-control text-center" maxlength="1" data-index="1">
                            <input type="text" class="code-digit form-control text-center" maxlength="1" data-index="2">
                            <input type="text" class="code-digit form-control text-center" maxlength="1" data-index="3">
                            <input type="text" class="code-digit form-control text-center" maxlength="1" data-index="4">
                            <input type="text" class="code-digit form-control text-center" maxlength="1" data-index="5">
                        </div>
                        <input type="hidden" name="code" id="fullCode">
                        <div class="text-center mt-2">
                            <small class="text-muted">Ingresa los 6 dígitos del código</small>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="login-btn" id="submitBtn" disabled>
                            <span id="btnText">
                                Verificar Código
                                <i class="fas fa-check ms-2"></i>
                            </span>
                            <span id="btnLoading" style="display: none;">
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                Verificando...
                            </span>
                        </button>
                    </div>
                    
                    
                    <div class="text-center mb-3" id="resendSection">
                        <div id="countdownSection" style="display: block;">
                            <p class="text-muted mb-2">
                                <i class="fas fa-clock me-1"></i>
                                Puedes solicitar un nuevo código en: 
                                <span id="countdown" class="fw-bold text-primary">0:30</span>
                            </p>
                        </div>
                        
                        <div id="resendOption" style="display: none;">
                            <p class="text-muted mb-2">¿No recibiste el código?</p>
                            <button type="button" class="btn btn-link p-0 text-decoration-none" id="resend-btn" onclick="resendCode()">
                                <i class="fas fa-redo me-1"></i>
                                Reenviar código
                            </button>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="{{ route('password.request') }}" class="forgot-link">
                            <i class="fas fa-arrow-left me-2"></i>
                            Usar otro correo electrónico
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const codeInputs = document.querySelectorAll('.code-digit');
            const fullCodeInput = document.getElementById('fullCode');
            const submitBtn = document.getElementById('submitBtn');
            const verifyForm = document.getElementById('verifyForm');
            const resendForm = document.getElementById('resendForm');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            const countdownElement = document.getElementById('countdown');
            const countdownSection = document.getElementById('countdownSection');
            const resendOption = document.getElementById('resendOption');

            let timeLeft = 30;
            let countdownTimer;
            
            function startCountdown() {
                countdownTimer = setInterval(function() {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    
                    if (timeLeft <= 0) {
                        clearInterval(countdownTimer);
                        countdownSection.style.display = 'none';
                        resendOption.style.display = 'block';
                    }
                    
                    timeLeft--;
                }, 1000);
            }

            startCountdown();

            function updateFullCode() {
                let code = '';
                codeInputs.forEach(input => {
                    code += input.value;
                });
                fullCodeInput.value = code;

                submitBtn.disabled = code.length !== 6;
            }

            codeInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {

                    this.value = this.value.replace(/[^0-9]/g, '');

                    if (this.value.length === 1 && index < codeInputs.length - 1) {
                        codeInputs[index + 1].focus();
                    }
                    
                    updateFullCode();
                });

                input.addEventListener('keydown', function(e) {

                    if (e.key === 'Backspace' && this.value === '' && index > 0) {
                        codeInputs[index - 1].focus();
                    }

                    if (e.key === 'Enter' && fullCodeInput.value.length === 6) {
                        verifyForm.submit();
                    }
                });

                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
                    
                    if (pastedData.length === 6) {
                        pastedData.split('').forEach((digit, i) => {
                            if (codeInputs[i]) {
                                codeInputs[i].value = digit;
                            }
                        });
                        updateFullCode();
                        codeInputs[5].focus();
                    }
                });
            });

            codeInputs[0].focus();

            verifyForm.addEventListener('submit', function() {
                submitBtn.disabled = true;
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline';
                submitBtn.style.cursor = 'wait';
            });

            window.resendCode = function() {
                const resendBtn = document.getElementById('resend-btn');
                const originalText = resendBtn.innerHTML;
                const messageText = document.getElementById('messageText');
                const codeMessage = document.getElementById('codeMessage');

                resendBtn.disabled = true;
                resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Reenviando...';
                
                console.log('Iniciando reenvío de código...');

                fetch('{{ route("password.resend-code") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: '{{ $email }}'
                    })
                })
                .then(response => {
                    console.log('Respuesta recibida:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Datos de respuesta:', data);
                    
                    if (data.success) {

                        messageText.textContent = 'Te hemos reenviado un código de verificación a tu email.';

                        codeMessage.style.animation = 'pulse 0.5s ease-in-out';
                        setTimeout(() => {
                            codeMessage.style.animation = '';
                        }, 500);

                        timeLeft = 30;
                        countdownSection.style.display = 'block';
                        resendOption.style.display = 'none';
                        startCountdown();

                        codeInputs.forEach(input => {
                            input.value = '';
                        });
                        fullCodeInput.value = '';
                        codeInputs[0].focus();
                        
                    } else {

                        messageText.textContent = 'Error al reenviar el código. ' + (data.message || 'Inténtalo más tarde.');
                        codeMessage.className = 'alert alert-danger';
                        codeMessage.querySelector('i').className = 'fas fa-exclamation-circle me-2';
                    }
                })
                .catch(error => {
                    console.error('Error en la solicitud:', error);

                    messageText.textContent = 'Error de conexión. Por favor, intenta nuevamente.';
                    codeMessage.className = 'alert alert-danger';
                    codeMessage.querySelector('i').className = 'fas fa-exclamation-circle me-2';
                })
                .finally(() => {

                    resendBtn.disabled = false;
                    resendBtn.innerHTML = originalText;
                });
            };
        });
    </script>
    
    <style>
        .code-digit {
            width: 50px !important;
            height: 60px !important;
            font-size: 24px !important;
            font-weight: bold !important;
            border: 2px solid #dee2e6 !important;
            border-radius: 8px !important;
            transition: all 0.3s ease !important;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .code-digit:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            outline: none !important;
        }
        
        .code-digit:not(:placeholder-shown) {
            border-color: #28a745 !important;
            background-color: #f8fff9 !important;
        }
        
        @media (max-width: 767.98px) {
            .code-digit {
                width: 40px !important;
                height: 50px !important;
                font-size: 20px !important;
            }
            
            .login-card {
                max-width: 95% !important;
                padding: 1.5rem 1rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .code-digit {
                width: 35px !important;
                height: 45px !important;
                font-size: 18px !important;
            }
            
            .code-input-container {
                gap: 0.25rem !important;
            }
        }
    </style>
</body>
</html>