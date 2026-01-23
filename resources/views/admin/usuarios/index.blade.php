@extends('layout.layout')
@php
    $title='Gestión de Usuarios';
    $subTitle = 'Lista de Usuarios';
    $v = '?v=' . time();
    $script='
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
        <script>

            window.APP_BASE_URL = "'.url('/').'";
            window.APP_BASE_PATH = "'.parse_url(url('/'), PHP_URL_PATH).'";
            window.USERS_API_URL = window.APP_BASE_URL + "/admin/usuarios/api";
            window.USERS_BASE_URL = window.APP_BASE_URL + "/admin/usuarios";
        </script>
        <script src="' . asset('assets/js/admin/usuarios.js') . $v . '"></script>
    ';
@endphp

@push('head')
    <title>Gestión de Usuarios</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.USERS_API_URL = "{{ route('admin.usuarios.api') }}";
        window.USERS_BASE_URL = "{{ url('/admin/usuarios') }}";
    </script>
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/crud.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/usuarios.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/roles.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

@section('content')
@include('components.loading-overlay', ['id' => 'loadingOverlay', 'label' => 'Cargando datos...'])

<div class="reportes-metrics-grid-4">
    
    <div class="reportes-metric-card gold">
        <div class="reportes-metric-icon-small">
            <iconify-icon icon="solar:users-group-two-rounded-bold-duotone"></iconify-icon>
        </div>
        <div class="reportes-metric-content">
            <div class="reportes-metric-label">Total Usuarios</div>
            <div class="reportes-metric-value-medium">{{ $usuarios->count() }}</div>
            <div class="reportes-metric-comparison">
                <iconify-icon icon="heroicons:users" class="text-xs"></iconify-icon> 
                <span>Registrados</span>
            </div>
        </div>
    </div>

    
    <div class="reportes-metric-card teal">
        <div class="reportes-metric-icon-small">
            <iconify-icon icon="solar:user-check-rounded-bold-duotone"></iconify-icon>
        </div>
        <div class="reportes-metric-content">
            <div class="reportes-metric-label">Usuarios Activos</div>
            <div class="reportes-metric-value-medium">{{ $usuarios->where('is_active', true)->count() }}</div>
            <div class="reportes-metric-comparison">
                <iconify-icon icon="solar:check-circle-bold" class="text-xs"></iconify-icon> 
                <span>Acceso habilitado</span>
            </div>
        </div>
    </div>

    
    <div class="reportes-metric-card red">
        <div class="reportes-metric-icon-small">
            <iconify-icon icon="solar:user-cross-rounded-bold-duotone"></iconify-icon>
        </div>
        <div class="reportes-metric-content">
            <div class="reportes-metric-label">Usuarios Inactivos</div>
            <div class="reportes-metric-value-medium">{{ $usuarios->where('is_active', false)->count() }}</div>
            <div class="reportes-metric-comparison">
                <iconify-icon icon="heroicons:pause" class="text-xs"></iconify-icon> 
                <span>Acceso suspendido</span>
            </div>
        </div>
    </div>

    
    <div class="reportes-metric-card purple">
        <div class="reportes-metric-icon-small">
            <iconify-icon icon="solar:shield-user-bold-duotone"></iconify-icon>
        </div>
        <div class="reportes-metric-content">
            <div class="reportes-metric-label">Total Roles</div>
            <div class="reportes-metric-value-medium">{{ $roles->count() }}</div>
            <div class="reportes-metric-comparison">
                <iconify-icon icon="heroicons:shield-check" class="text-xs"></iconify-icon> 
                <span>Configurados</span>
            </div>
        </div>
    </div>
</div>

<div class="filter-section bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
    <div class="flex flex-wrap items-center gap-4 justify-between">
        
        <div class="search-container-modern w-full sm:w-auto sm:min-w-[320px] flex-1">
            <div class="relative">
                <input type="text" 
                       id="searchInput" 
                       placeholder="Buscar por nombre o email..." 
                       class="block w-full px-4 py-3 border border-gray-300 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm transition-all duration-200 hover:shadow-md">
            </div>
        </div>

        
        <div class="flex items-center gap-3">
            <div class="filter-group">
                <select id="roleFilter" class="filter-select-elegant">
                    <option value="">Todos los Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ ucfirst(str_replace('-', '/', $role->name)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <select id="statusFilter" class="filter-select-elegant">
                    <option value="">Estados</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
            </div>
        </div>

        
        <div class="flex items-center gap-2">
            <button type="button" 
                    class="btn-action-elegant btn-export" 
                    onclick="exportUsers()"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);"
                    title="Exportar a Excel">
                <iconify-icon icon="solar:download-bold-duotone" style="font-size: 1.25rem;"></iconify-icon>
                <span>Exportar</span>
            </button>

            <button type="button" 
                    class="btn-action-elegant btn-primary" 
                    onclick="openCreateUserModal()"
                    style="background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);"
                    title="Agregar Nuevo Usuario">
                <iconify-icon icon="solar:user-plus-bold-duotone" style="font-size: 1.25rem;"></iconify-icon>
                <span>Agregar</span>
            </button>
        </div>
    </div>
</div>

<div class="table-container bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="table-responsive">
        <table class="users-table" id="usersTable">
            <thead>
                <tr>
                    <th>
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="solar:user-bold-duotone" style="font-size: 1rem;"></iconify-icon>
                            Usuario
                        </div>
                    </th>
                    <th>
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="solar:letter-bold-duotone" style="font-size: 1rem;"></iconify-icon>
                            Contacto
                        </div>
                    </th>
                    <th>
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="solar:shield-user-bold-duotone" style="font-size: 1rem;"></iconify-icon>
                            Roles
                        </div>
                    </th>
                    <th style="text-align: center !important;">
                        <div class="flex items-center justify-center gap-2">
                            <iconify-icon icon="solar:check-circle-bold-duotone" style="font-size: 1rem;"></iconify-icon>
                            Estado
                        </div>
                    </th>
                    <th>
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="solar:clock-circle-bold-duotone" style="font-size: 1rem;"></iconify-icon>
                            Último Acceso
                        </div>
                    </th>
                    <th style="text-align: center !important;">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody id="usuariosTableBody">
                @forelse($usuarios as $usuario)
                <tr data-user-id="{{ $usuario->id }}" class="user-row" data-telefono="{{ $usuario->telefono }}" data-direccion="{{ $usuario->direccion }}">
                    
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar" style="border: 2px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                @if($usuario->avatar)
                                    <img src="{{ $usuario->avatar_url }}" 
                                         alt="Avatar de {{ $usuario->full_name }}">
                                @else
                                    <div class="avatar-placeholder" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                                        {{ $usuario->initials }}
                                    </div>
                                @endif
                            </div>
                            <div class="user-info">
                                <div class="user-name" style="font-weight: 700; color: #1e293b; font-size: 0.95rem;">
                                    @if($usuario->nombres && $usuario->apellidos)
                                        {{ $usuario->nombres }} {{ $usuario->apellidos }}
                                    @else
                                        {{ $usuario->name }}
                                    @endif
                                </div>
                                <div class="user-phone" style="font-size: 0.75rem; color: #64748b; font-weight: 600;">
                                    <iconify-icon icon="solar:phone-bold" style="font-size: 0.8rem; margin-right: 2px;"></iconify-icon>
                                    {{ $usuario->telefono ?? 'Sin teléfono' }}
                                </div>
                            </div>
                        </div>
                    </td>

                    
                    <td>
                        <div class="contact-cell">
                            <div class="email-cell" style="font-weight: 600; color: #475569; font-size: 0.875rem; display: flex; align-items: center; gap: 4px;">
                                <iconify-icon icon="solar:letter-bold" style="color: #64748b; font-size: 0.9rem;"></iconify-icon>
                                {{ $usuario->email }}
                            </div>
                            <div class="address-cell" style="font-size: 0.75rem; color: #64748b; font-weight: 500; margin-top: 2px; display: flex; align-items: center; gap: 4px;">
                                <iconify-icon icon="solar:map-point-bold" style="color: #94a3b8; font-size: 0.85rem;"></iconify-icon>
                                {{ $usuario->direccion ?? '-' }}
                            </div>
                        </div>
                    </td>

                    
                    <td>
                        <div class="roles-container">
                            @forelse($usuario->roles as $role)
                                <span class="role-badge" style="background-color: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 0.25rem 0.6rem; border-radius: 8px;">
                                    {{ ucfirst(str_replace('-', '/', $role->name)) }}
                                </span>
                            @empty
                                <span class="no-role-badge">Sin roles</span>
                            @endforelse
                        </div>
                    </td>

                    
                    <td style="text-align: center;">
                        <span class="status-badge {{ $usuario->is_active ? 'status-active' : 'status-inactive' }}" style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">
                            <iconify-icon icon="{{ $usuario->is_active ? 'solar:check-circle-bold' : 'solar:close-circle-bold' }}"></iconify-icon>
                            {{ $usuario->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>

                    
                    <td>
                        <div class="last-login-cell" style="font-size: 0.8rem; font-weight: 600; color: #64748b;">
                            @if($usuario->last_login_at)
                                <span style="color: #1e293b;">{{ $usuario->last_login_at->locale('es')->diffForHumans() }}</span>
                            @else
                                <span class="no-login" style="font-style: italic; color: #94a3b8;">Nunca</span>
                            @endif
                        </div>
                    </td>

                    
                    <td>
                        <div class="action-buttons" style="justify-content: center;">

                            @php
                                $isDueno = $usuario->roles->contains('name', 'dueño');
                            @endphp

                            @if(!$isDueno)
                                
                                <button type="button" 
                                        class="action-btn btn-edit" 
                                        onclick="editUser({{ $usuario->id }})"
                                        title="Editar Usuario">
                                    <iconify-icon icon="heroicons:pencil"></iconify-icon>
                                </button>

                                
                                @if($usuario->id !== auth()->id())
                                <label class="toggle-switch user-toggle" title="Activar/Desactivar">
                                    <input type="checkbox" class="user-status-toggle" data-user-id="{{ $usuario->id }}" {{ $usuario->is_active ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                @endif

                                
                                @if($usuario->id !== auth()->id())
                                <button type="button" 
                                        class="action-btn btn-delete" 
                                        onclick="deleteUser({{ $usuario->id }})"
                                        title="Eliminar Usuario">
                                    <iconify-icon icon="heroicons:trash"></iconify-icon>
                                </button>
                                @endif
                            @else
                                
                                <button type="button" 
                                        class="action-btn btn-protected" 
                                        title="Usuario Protegido - No se puede editar">
                                    <iconify-icon icon="heroicons:shield-check"></iconify-icon>
                                </button>
                                
                                <button type="button" 
                                        class="action-btn btn-protected" 
                                        title="Usuario Protegido - No se puede eliminar">
                                    <iconify-icon icon="heroicons:shield-exclamation"></iconify-icon>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-state">
                        <div class="empty-content">
                            <iconify-icon icon="heroicons:users" class="empty-icon"></iconify-icon>
                            <h3>No hay usuarios registrados</h3>
                            <p>Crea el primer usuario para comenzar</p>
                            <button type="button" class="btn-primary" onclick="openCreateUserModal()">
                                <iconify-icon icon="heroicons:plus"></iconify-icon>
                                Crear Usuario
                            </button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
            
            
            <tbody id="usuariosSkeletonBody" style="display:none;">
                @for ($i = 0; $i < 6; $i++)
                <tr class="skeleton-row">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="skeleton-bar skeleton-loader" style="width: 40px; height: 40px; border-radius: 50%;"></div>
                            <div class="flex flex-col gap-2">
                                <div class="skeleton-bar skeleton-loader" style="width: 140px; height: 16px;"></div>
                                <div class="skeleton-bar skeleton-loader" style="width: 80px; height: 12px;"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="flex flex-col gap-2">
                            <div class="skeleton-bar skeleton-loader" style="width: 180px; height: 16px;"></div>
                            <div class="skeleton-bar skeleton-loader" style="width: 120px; height: 12px;"></div>
                        </div>
                    </td>
                    <td><div class="skeleton-bar skeleton-loader" style="width: 100px; height: 24px; border-radius: 8px;"></div></td>
                    <td><div class="skeleton-bar skeleton-loader" style="width: 80px; height: 24px; border-radius: 20px; margin: 0 auto;"></div></td>
                    <td><div class="skeleton-bar skeleton-loader" style="width: 120px; height: 16px;"></div></td>
                    <td>
                        <div class="flex justify-center gap-2">
                            <div class="skeleton-bar skeleton-loader" style="width: 32px; height: 32px; border-radius: 8px;"></div>
                            <div class="skeleton-bar skeleton-loader" style="width: 32px; height: 32px; border-radius: 8px;"></div>
                            <div class="skeleton-bar skeleton-loader" style="width: 32px; height: 32px; border-radius: 8px;"></div>
                        </div>
                    </td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>

<div id="userModal" class="modal-profesional hidden">
    <div class="modal-profesional-container">
        
        <div class="header-profesional">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <iconify-icon icon="solar:user-plus-bold-duotone" id="modalIcon"></iconify-icon>
                    </div>
                    <div class="header-text">
                        <h3 id="modalTitle">Crear Nuevo Usuario</h3>
                        <p>Complete todos los campos obligatorios</p>
                    </div>
                </div>
                <button type="button" class="btn-close" onclick="closeUserModal()">
                    <iconify-icon icon="heroicons:x-mark"></iconify-icon>
                </button>
            </div>
        </div>

        
        <div class="modal-content-profesional">
            <form id="userForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" id="userId" name="user_id">

                
                <div class="seccion-form seccion-azul">
                    
                    <div class="avatar-upload-modern">
                        <div class="avatar-preview-modern" id="avatarPreview">
                            <img id="avatarImage" src="" alt="Avatar" style="display: none; width: 96px; height: 96px; object-fit: cover; border-radius: 50%;">
                            <div id="avatarPlaceholder" class="avatar-placeholder-modern">
                                <iconify-icon icon="solar:user-bold-duotone"></iconify-icon>
                            </div>
                        </div>
                        <div class="avatar-controls-modern">
                            <input type="file" name="avatar" accept="image/*" id="avatarInput" style="display: none;">
                            
                            
                            <div class="avatar-buttons-container" id="avatarButtonsContainer">
                                <button type="button" class="btn-upload-moderno" onclick="document.getElementById('avatarInput').click()">
                                    <iconify-icon icon="solar:upload-bold-duotone"></iconify-icon>
                                    Seleccionar Perfil
                                </button>
                                <button type="button" class="btn-remove-moderno" id="removeAvatarBtn" onclick="removeAvatar()" style="display: none;">
                                    <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone"></iconify-icon>
                                    Quitar Imagen
                                </button>
                            </div>
                            
                            
                            <div class="avatar-info">
                                <span class="avatar-formats">Formatos: JPG, PNG, GIF (Máx. 2MB)</span>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="seccion-form seccion-verde">
                    <div class="seccion-header">
                        <div class="seccion-icon icon-verde">
                            <iconify-icon icon="solar:user-id-bold-duotone"></iconify-icon>
                        </div>
                        <div class="seccion-titulo">
                            <h3>Información Personal</h3>
                            <p>Datos básicos del usuario</p>
                        </div>
                    </div>
                    
                    <div class="grid-campos columnas-2">
                        <div class="campo-grupo">
                            <label class="campo-label">
                                <iconify-icon icon="solar:user-bold-duotone" class="label-icon"></iconify-icon>
                                Nombres *
                            </label>
                            <input type="text" name="nombres" id="nombres" class="campo-input" placeholder="Ingrese los nombres" required>
                        </div>
                        
                        <div class="campo-grupo">
                            <label class="campo-label">
                                <iconify-icon icon="solar:user-bold-duotone" class="label-icon"></iconify-icon>
                                Apellidos *
                            </label>
                            <input type="text" name="apellidos" id="apellidos" class="campo-input" placeholder="Ingrese los apellidos" required>
                        </div>
                        
                        <div class="campo-grupo">
                            <label class="campo-label">
                                <iconify-icon icon="solar:phone-bold-duotone" class="label-icon"></iconify-icon>
                                Teléfono
                            </label>
                            <input type="tel" name="telefono" id="telefono" class="campo-input" placeholder="Número de teléfono">
                        </div>
                        
                        <div class="campo-grupo">
                            <label class="campo-label">
                                <iconify-icon icon="solar:map-point-bold-duotone" class="label-icon"></iconify-icon>
                                Dirección
                            </label>
                            <input type="text" name="direccion" id="direccion" class="campo-input" placeholder="Dirección completa del usuario">
                        </div>
                    </div>
                </div>

                
                <div class="seccion-form seccion-amarillo">
                    <div class="seccion-header">
                        <div class="seccion-icon icon-amarillo">
                            <iconify-icon icon="solar:shield-keyhole-bold-duotone"></iconify-icon>
                        </div>
                        <div class="seccion-titulo">
                            <h3>Credenciales de Acceso</h3>
                        </div>
                    </div>
                    
                    <div class="grid-campos columnas-2">
                        <div class="campo-grupo campo-completo">
                            <label class="campo-label">
                                <iconify-icon icon="solar:letter-bold-duotone" class="label-icon"></iconify-icon>
                                Correo Electrónico *
                            </label>
                            <input type="email" name="email" id="email" class="campo-input" placeholder="usuario@boticasanantonio.com" required>
                        </div>
                        
                        <div class="campo-grupo">
                            <label class="campo-label">
                                <iconify-icon icon="solar:key-bold-duotone" class="label-icon"></iconify-icon>
                                Contraseña <span id="passwordRequired" style="color: #ef4444;">*</span>
                            </label>
                            <div class="password-wrapper">
                                <input type="password" name="password" id="password" class="campo-input" placeholder="Mínimo 6 caracteres" style="padding-right: 36px;">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
                                    <iconify-icon icon="solar:eye-bold-duotone" id="password-toggle-icon"></iconify-icon>
                                </button>
                            </div>
                        </div>
                        
                        <div class="campo-grupo">
                            <label class="campo-label">
                                <iconify-icon icon="solar:lock-keyhole-bold-duotone" class="label-icon"></iconify-icon>
                                Confirmar Contraseña <span id="confirmPasswordRequired" style="color: #ef4444;">*</span>
                            </label>
                            <div class="password-wrapper">
                                <input type="password" name="password_confirmation" id="password_confirmation" class="campo-input" placeholder="Repita la contraseña" style="padding-right: 36px;">
                                <button type="button" class="password-toggle-btn" onclick="togglePassword('password_confirmation')">
                                    <iconify-icon icon="solar:eye-bold-duotone" id="password-confirmation-toggle-icon"></iconify-icon>
                                </button>
                            </div>
                            <div id="password-match-indicator" style="display: none; margin-top: 8px;">
                                <div style="display: flex; align-items: center; font-size: 14px;">
                                    <iconify-icon icon="solar:check-circle-bold-duotone" style="color: #10b981; margin-right: 8px;"></iconify-icon>
                                    <span style="color: #10b981; font-weight: 600;">Las contraseñas coinciden</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="campo-grupo campo-completo">
                            <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; padding: 10px; margin-top: 8px; font-size: 13px;">
                                <span style="color: #92400e; font-weight: 600;">💡 Requisitos:</span>
                                <span style="color: #b45309;"> Mínimo 6 caracteres</span>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="seccion-form seccion-morado">
                    <div class="seccion-header">
                        <div class="seccion-icon icon-morado">
                            <iconify-icon icon="solar:shield-user-bold-duotone"></iconify-icon>
                        </div>
                        <div class="seccion-titulo">
                            <h3>Asignación de Roles</h3>
                            <p>Selecciona los roles que tendrá este usuario</p>
                        </div>
                    </div>
                    
                    <div class="roles-grid-moderno">
                        @foreach($roles as $role)
                        @php
                            $roleKey = strtolower($role->name);
                            $roleDisplay = strtolower($role->display_name ?? '');
                        @endphp
                        @if(in_array($roleKey, ['dueño','dueno','owner']) || in_array($roleDisplay, ['dueño','dueno','owner']))
                            @continue
                        @endif
                        @if($role->name === 'Gerente')
                            <div class="role-card-moderno role-disabled" title="Este rol está reservado y no puede ser asignado">
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" id="role-{{ $role->id }}" class="role-checkbox-hidden" disabled>
                                
                                <div class="role-header-moderno">
                                    <div class="role-info-moderno">
                                        <div class="role-icon-moderno {{ $role->name }}">
                                            <iconify-icon icon="solar:crown-bold-duotone"></iconify-icon>
                                        </div>
                                        <div class="role-details-moderno">
                                            <h4 class="role-name-moderno">
                                                {{ ucfirst(str_replace('-', '/', $role->display_name ?? $role->name)) }}
                                                <iconify-icon icon="solar:lock-bold-duotone" style="color: #ef4444; margin-left: 8px;"></iconify-icon>
                                            </h4>
                                        <p class="role-description-moderno">
                                            Rol reservado - Solo puede haber un gerente
                                        </p>
                                        <div class="role-permissions-moderno">
                                            <iconify-icon icon="solar:shield-check-bold-duotone"></iconify-icon>
                                            {{ $role->permissions->count() }} permisos
                                        </div>
                                    </div>
                                </div>
                                <div class="selection-indicator">
                                    <iconify-icon icon="solar:shield-exclamation-bold-duotone"></iconify-icon>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="role-card-moderno" onclick="toggleRoleSelection({{ $role->id }})">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" id="role-{{ $role->id }}" class="role-checkbox-hidden">
                            
                            <div class="role-header-moderno">
                                <div class="role-info-moderno">
                                    <div class="role-icon-moderno {{ $role->name }}">
                                        @if($role->name === 'administrador')
                                            <iconify-icon icon="solar:shield-keyhole-bold-duotone"></iconify-icon>
                                        @elseif($role->name === 'vendedor')
                                            <iconify-icon icon="solar:cart-bold-duotone"></iconify-icon>
                                        @elseif($role->name === 'almacenero')
                                            <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                                        @elseif($role->name === 'vendedor-almacenero' || $role->name === 'vendedor/almacenero')
                                            <iconify-icon icon="solar:cart-bold-duotone"></iconify-icon>
                                        @else
                                            <iconify-icon icon="solar:user-bold-duotone"></iconify-icon>
                                        @endif
                                    </div>
                                    <div class="role-details-moderno">
                                        <h4 class="role-name-moderno">
                                            {{ ucfirst(str_replace('-', '/', $role->display_name ?? $role->name)) }}
                                        </h4>
                                        <p class="role-description-moderno">
                                            {{ $role->description ?? 'Rol del sistema con permisos específicos' }}
                                        </p>
                                        <div class="role-permissions-moderno">
                                            <iconify-icon icon="solar:shield-check-bold-duotone"></iconify-icon>
                                            {{ $role->permissions->count() }} permisos
                                        </div>
                                    </div>
                                </div>
                                <div class="selection-indicator">
                                    <iconify-icon icon="solar:check-bold"></iconify-icon>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    
                    
                    <div class="roles-info-compacta">
                        <iconify-icon icon="solar:info-circle-bold-duotone"></iconify-icon>
                        <span><strong>Tip:</strong> Puedes asignar múltiples roles. Los permisos se combinan automáticamente.</span>
                    </div>
                </div>

            </form>
        </div>
        
        
        <div class="acciones-footer">
            <button type="button" class="btn-accion btn-cancelar" onclick="closeUserModal()">
                <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                Cancelar
            </button>
            <button type="button" class="btn-accion btn-guardar" onclick="submitUserForm()">
                <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                <span id="submitButtonText">Crear Usuario</span>
            </button>
        </div>
    </div>
</div>
@endsection