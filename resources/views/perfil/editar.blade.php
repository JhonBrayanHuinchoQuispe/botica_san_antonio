@extends('layout.layout')

@php
    $title = 'Perfil de Usuario';
    $subTitle = 'Editar Información del Perfil';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/perfil/editar-simple.js') . '?v=' . time() . '"></script>
    ';
@endphp

<head>
    <title>Editar Perfil - {{ $user->name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/perfil/editar.css') }}?v={{ time() }}">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="{{ asset('assets/js/perfil/debug-avatar.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('assets/js/perfil/fix-avatar.js') }}?v={{ time() }}"></script>
</head>

@section('content')

<div class="grid grid-cols-12">
    <div class="col-span-12">
<div class="editar-perfil-container">
            <!-- Layout Principal Moderno: Información + Formulario -->
            <div class="editar-perfil-layout">
                <!-- Panel Izquierdo - Vista Previa del Perfil -->
                <div class="perfil-preview-panel">
                    <!-- Header de Usuario -->
                    <div class="usuario-preview-header">
                        <div class="gradient-background"></div>
                        <div class="avatar-section">
                            <div class="avatar-preview" id="avatarPreview">
                                @php
                                    $names = explode(' ', $user->name);
                                    $initials = '';
                                    foreach($names as $name) {
                                        $initials .= strtoupper(substr($name, 0, 1));
                                        if(strlen($initials) >= 2) break;
                                    }
                                    if(empty($initials)) $initials = 'JA';
                                @endphp
                                @if($user->avatar)
                                    <img src="{{ $user->avatar_url }}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;">
                                @else
                                    <span style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: white; font-size: 3.2rem; font-weight: 700; background: linear-gradient(135deg, #e53e3e, #feb2b2); text-align: center; line-height: 1; font-family: Arial, sans-serif; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3); letter-spacing: 1px; border-radius: 50%;">{{ $initials }}</span>
                                @endif
                                <div class="avatar-status online"></div>
                            </div>
                            
                            <div class="avatar-controls">
                                <button type="button" class="btn-cambiar-avatar">
                                    <iconify-icon icon="heroicons:camera-solid"></iconify-icon>
                                    Cambiar Foto
                                </button>
                                @if($user->avatar)
                                    <button type="button" class="btn-remover-avatar" style="display: flex;">
                                        <iconify-icon icon="heroicons:trash-solid"></iconify-icon>
                                        Remover
                                    </button>
                                @else
                                    <button type="button" class="btn-remover-avatar" style="display: none;">
                                        <iconify-icon icon="heroicons:trash-solid"></iconify-icon>
                                        Remover
                                    </button>
                                @endif
                                <input type="file" id="avatarInput" name="avatar" accept="image/*,image/jpeg,image/png,image/jpg,image/gif" style="display: none;">
                            </div>
                        </div>
                        
                        <div class="usuario-info-preview">
                            <h2 class="nombre-preview" id="previewNombre">{{ $user->name }}</h2>
                            <p class="email-preview" id="previewEmail">{{ $user->email }}</p>
                            <div class="badges-preview">
                                @php
                                    $roleLabel = optional($user->roles->first())->display_name ?? ($user->getRoleNames()->first() ?? 'Usuario');
                                @endphp
                                <span class="badge-role">
                                    <iconify-icon icon="heroicons:user-solid"></iconify-icon>
                                    {{ $roleLabel }}
                                </span>
                                <span class="badge-status activo">
                                    <iconify-icon icon="heroicons:check-circle-solid"></iconify-icon>
                                    Activo
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Información Personal Actual -->
                    <div class="info-personal-actual">
                        <h3>
                            <iconify-icon icon="heroicons:identification-solid"></iconify-icon>
                            Información Personal
                        </h3>
                        <div class="info-list">
                            <div class="info-item">
                                <span class="info-label">Nombre:</span>
                                <span class="info-value" id="previewNombreCompleto">{{ $user->name }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value" id="previewEmailCompleto">{{ $user->email }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Teléfono:</span>
                                <span class="info-value" id="previewTelefono">{{ $user->telefono ?? 'No especificado' }}</span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Dirección:</span>
                                <span class="info-value" id="previewDireccion">{{ $user->direccion ?? 'No especificada' }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Miembro desde:</span>
            <span class="info-value">{{ optional($user->created_at)->format('d/m/Y') ?? '-' }}</span>
                            </div>
                        </div>
                    </div>


                </div>

                <!-- Panel Derecho - Formularios de Edición -->
                <div class="formularios-panel">
                    <!-- Tabs Simplificados -->
                    <div class="tabs-navigation-simple">
            <button class="tab-btn active" data-tab="informacion-personal">
                <iconify-icon icon="heroicons:user-solid"></iconify-icon>
                <span>Información Personal</span>
            </button>
            <button class="tab-btn" data-tab="cambiar-password">
                <iconify-icon icon="heroicons:lock-closed-solid"></iconify-icon>
                <span>Cambiar Contraseña</span>
            </button>
    </div>

                    <!-- Contenido de los Tabs -->
    <div class="tabs-content">
        <!-- Tab: Información Personal -->
        <div id="informacion-personal" class="tab-content active">
                            <div class="form-header">
                                <h3>
                        <iconify-icon icon="heroicons:identification-solid"></iconify-icon>
                        Información Personal
                                </h3>
                    <p>Actualiza tu información personal y datos de contacto</p>
                </div>

                <form id="formInformacionPersonal" class="modern-form">
                    @csrf
                    @method('PATCH')
                    
                                <div class="form-group">
                                    <label for="nombres">
                                    <iconify-icon icon="heroicons:user-solid"></iconify-icon>
                                        Nombres *
                                    </label>
                                    <input type="text" id="nombres" name="nombres" value="{{ old('nombres', $user->nombres) }}" required 
                                           onkeyup="updateFullName(); updatePreview('name', 'previewNombre'); updatePreview('name', 'previewNombreCompleto')" 
                                           placeholder="Ingresa tus nombres">
                                    <div class="field-error" id="error-nombres"></div>
                                </div>

                                <div class="form-group">
                                    <label for="apellidos">
                                        <iconify-icon icon="heroicons:user-solid"></iconify-icon>
                                        Apellidos *
                            </label>
                                    <input type="text" id="apellidos" name="apellidos" value="{{ old('apellidos', $user->apellidos) }}" required 
                                           onkeyup="updateFullName(); updatePreview('name', 'previewNombre'); updatePreview('name', 'previewNombreCompleto')" 
                                           placeholder="Ingresa tus apellidos">
                                    <div class="field-error" id="error-apellidos"></div>
                    </div>

                        <div class="form-group">
                            <label for="name">
                                        <iconify-icon icon="heroicons:identification-solid"></iconify-icon>
                                        Nombre Completo (automático)
                            </label>
                                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" readonly 
                                           style="background-color: #f8fafc; cursor: not-allowed;">
                            <div class="field-error" id="error-name"></div>
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <iconify-icon icon="heroicons:envelope-solid"></iconify-icon>
                                Correo Electrónico *
                            </label>
                                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                                           onkeyup="updatePreview('email', 'previewEmail'); updatePreview('email', 'previewEmailCompleto')">
                            <div class="field-error" id="error-email"></div>
                            
                            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                <div class="verification-notice">
                                    <iconify-icon icon="heroicons:exclamation-triangle-solid"></iconify-icon>
                                    <span>Tu correo electrónico no está verificado.</span>
                                    <button type="button" class="btn-link" onclick="reenviarVerificacion()">
                                        Reenviar enlace de verificación
                                    </button>
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="telefono">
                                <iconify-icon icon="heroicons:phone-solid"></iconify-icon>
                                Teléfono
                            </label>
                                    <input type="tel" id="telefono" name="telefono" value="{{ old('telefono', $user->telefono) }}"
                                           onkeyup="updatePreview('telefono', 'previewTelefono', 'No especificado')">
                            <div class="field-error" id="error-telefono"></div>
                        </div>

                        

                                <div class="form-group">
                            <label for="direccion">
                                <iconify-icon icon="heroicons:map-pin-solid"></iconify-icon>
                                Dirección
                            </label>
                                    <textarea id="direccion" name="direccion" rows="3" placeholder="Ingresa tu dirección completa"
                                              onkeyup="updatePreview('direccion', 'previewDireccion', 'No especificada')">{{ old('direccion', $user->direccion) }}</textarea>
                            <div class="field-error" id="error-direccion"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <iconify-icon icon="heroicons:check-circle-solid"></iconify-icon>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
        </div>

        <!-- Tab: Cambiar Contraseña -->
        <div id="cambiar-password" class="tab-content">
                            <div class="form-header">
                                <h3>
                        <iconify-icon icon="heroicons:shield-check-solid"></iconify-icon>
                        Cambiar Contraseña
                                </h3>
                    <p>Asegúrate de usar una contraseña larga y segura para proteger tu cuenta</p>
                </div>

                <form id="formCambiarPassword" class="modern-form" action="{{ route('perfil.cambiar-password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="password-strength-info">
                        <h4>Requisitos de seguridad:</h4>
                        <p style="color:#6b7280; font-size: 14px;">Mínimo 6 caracteres</p>
                    </div>

                        <div class="form-group">
                            <label for="current_password">
                                <iconify-icon icon="heroicons:lock-open-solid"></iconify-icon>
                                Contraseña Actual *
                            </label>
                            <div class="password-input-container">
                                <input type="password" id="current_password" name="current_password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                                    <iconify-icon icon="heroicons:eye-solid"></iconify-icon>
                                </button>
                            </div>
                            <div class="field-error" id="error-current_password"></div>
                        </div>

                        <div class="form-group">
                            <label for="new_password">
                                <iconify-icon icon="heroicons:lock-closed-solid"></iconify-icon>
                                Nueva Contraseña *
                            </label>
                            <div class="password-input-container">
                                <input type="password" id="new_password" name="password" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                    <iconify-icon icon="heroicons:eye-solid"></iconify-icon>
                                </button>
                            </div>
                            
                            <div class="field-error" id="error-password"></div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">
                                <iconify-icon icon="heroicons:shield-check-solid"></iconify-icon>
                                Confirmar Contraseña *
                            </label>
                            <div class="password-input-container">
                                <input type="password" id="password_confirmation" name="password_confirmation" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password_confirmation')">
                                    <iconify-icon icon="heroicons:eye-solid"></iconify-icon>
                                </button>
                            </div>
                            <div class="password-match-indicator" id="passwordMatch"></div>
                            <div class="field-error" id="error-password_confirmation"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <iconify-icon icon="heroicons:shield-check-solid"></iconify-icon>
                            Actualizar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Debug adicional para verificar que todo esté cargado
    console.log('🔍 Verificando elementos del DOM...');
    
    document.addEventListener('DOMContentLoaded', function() {
        // Verificar elementos críticos
        var avatarPreview = document.getElementById('avatarPreview');
        var avatarInput = document.getElementById('avatarInput');
        var btnCambiar = document.querySelector('.btn-cambiar-avatar');
        var btnRemover = document.querySelector('.btn-remover-avatar');
        var form = document.getElementById('formInformacionPersonal');
        
        console.log('🖼️ Avatar Preview:', avatarPreview ? '✅ Encontrado' : '❌ No encontrado');
        console.log('📁 Avatar Input:', avatarInput ? '✅ Encontrado (' + avatarInput.id + ')' : '❌ No encontrado');
        console.log('🔘 Botón Cambiar:', btnCambiar ? '✅ Encontrado' : '❌ No encontrado');
        console.log('🗑️ Botón Remover:', btnRemover ? '✅ Encontrado' : '❌ No encontrado');
        console.log('📋 Formulario:', form ? '✅ Encontrado' : '❌ No encontrado');
        
        // Verificar funciones exportadas
        console.log('🔧 Funciones disponibles:');
        console.log('  - previewAvatar:', typeof window.previewAvatar);
        console.log('  - removeAvatar:', typeof window.removeAvatar);
        console.log('  - submitPersonalInfo:', typeof window.submitPersonalInfo);
        
        // Test manual del input de avatar
        if (avatarInput && btnCambiar) {
            console.log('🧪 Configurando test manual...');
            window.testAvatarInput = function() {
                console.log('🧪 Test: Activando input de avatar...');
                avatarInput.click();
            };
            console.log('✅ Usa window.testAvatarInput() para probar manualmente');
        }
        
        // FORZAR INICIALES INMEDIATAMENTE
        setTimeout(function() {
            if (typeof window.forceAvatarInitials === 'function') {
                window.forceAvatarInitials();
            }
        }, 100);
    });
    
    // Script adicional para forzar iniciales
    window.addEventListener('load', function() {
        console.log('🔧 Página completamente cargada, forzando iniciales...');
        if (typeof window.forceAvatarInitials === 'function') {
            window.forceAvatarInitials();
        }
    });
</script>
@endpush
