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
            // URLs absolutas basadas en el origen actual para evitar 404
            window.APP_BASE_URL = "'.url('/').'";
            window.APP_BASE_PATH = "'.parse_url(url('/'), PHP_URL_PATH).'";
            window.USERS_API_URL = window.APP_BASE_URL + "/admin/usuarios/api";
            window.USERS_BASE_URL = window.APP_BASE_URL + "/admin/usuarios";
        </script>
        <script src="' . asset('assets/js/admin/usuarios.js') . $v . '"></script>
    ';
@endphp

<head>
    <title>Gestión de Usuarios</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // URLs absolutas para evitar 404 al recargar por AJAX
        window.USERS_API_URL = "{{ route('admin.usuarios.api') }}";
        window.USERS_BASE_URL = "{{ url('/admin/usuarios') }}";
    </script>
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/crud.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/usuarios.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/roles.css') }}?v={{ time() }}">
    <!-- FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

@section('content')
@include('components.loading-overlay', ['id' => 'loadingOverlay', 'label' => 'Cargando datos...'])

<!-- Header con estadísticas elegantes -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Usuarios -->
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-blue-600/10 to-bg-white">
        <div class="card-body h-full p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-neutral-900 mb-1">Total Usuarios</p>
                    <h6 class="mb-0 text-2xl font-bold">{{ $usuarios->count() }}</h6>
                </div>
                <div class="w-[50px] h-[50px] bg-blue-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="solar:users-group-two-rounded-bold-duotone" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
            </div>
            <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-info-600">
                    <iconify-icon icon="heroicons:users" class="text-xs"></iconify-icon> 
                    Registrados
                </span>
                Usuarios en el sistema
            </p>
        </div>
    </div>

    <!-- Usuarios Activos -->
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-success-600/10 to-bg-white">
        <div class="card-body p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-neutral-900 mb-1">Usuarios Activos</p>
                    <h6 class="mb-0 text-2xl font-bold">{{ $usuarios->where('is_active', true)->count() }}</h6>
                </div>
                <div class="w-[50px] h-[50px] bg-success-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="solar:user-check-rounded-bold-duotone" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
            </div>
            <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-success-600">
                    <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> 
                    Activos
                </span>
                Usuarios con acceso habilitado
            </p>
        </div>
    </div>

    <!-- Usuarios Inactivos -->
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-red-600/10 to-bg-white">
        <div class="card-body p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-neutral-900 mb-1">Usuarios Inactivos</p>
                    <h6 class="mb-0 text-2xl font-bold">{{ $usuarios->where('is_active', false)->count() }}</h6>
                </div>
                <div class="w-[50px] h-[50px] bg-red-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="solar:user-cross-rounded-bold-duotone" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
            </div>
            <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-danger-600">
                    <iconify-icon icon="heroicons:pause" class="text-xs"></iconify-icon> 
                    Inactivos
                </span>
                Usuarios con acceso suspendido
            </p>
        </div>
    </div>

    <!-- Total Roles -->
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-purple-600/10 to-bg-white">
        <div class="card-body p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-neutral-900 mb-1">Total Roles</p>
                    <h6 class="mb-0 text-2xl font-bold">{{ $roles->count() }}</h6>
                </div>
                <div class="w-[50px] h-[50px] bg-purple-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="solar:shield-user-bold-duotone" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
            </div>
            <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-purple-600">
                    <iconify-icon icon="heroicons:shield-check" class="text-xs"></iconify-icon> 
                    Configurados
                </span>
                Roles disponibles del sistema
            </p>
        </div>
    </div>
</div>

<!-- Filtros y Controles Elegantes -->
<div class="filter-section bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
    <div class="flex flex-wrap items-center gap-4 justify-between">
        <!-- Búsqueda Elegante -->
        <div class="search-container-modern w-full sm:w-auto sm:min-w-[320px] flex-1">
            <div class="relative">
                <input type="text" 
                       id="searchInput" 
                       placeholder="Buscar por nombre o email..." 
                       class="block w-full px-4 py-3 border border-gray-300 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm transition-all duration-200 hover:shadow-md">
            </div>
        </div>

        <!-- Filtros Elegantes -->
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

        <!-- Acciones Elegantes -->
        <div class="flex items-center gap-2">
            <button type="button" 
                    class="btn-action-elegant btn-export" 
                    onclick="exportUsers()"
                    title="Exportar a Excel">
                <iconify-icon icon="solar:download-bold-duotone"></iconify-icon>
                <span>Exportar</span>
            </button>

            <button type="button" 
                    class="btn-action-elegant btn-primary" 
                    onclick="openCreateUserModal()"
                    title="Agregar Nuevo Usuario">
                <iconify-icon icon="solar:user-plus-bold-duotone"></iconify-icon>
                <span>Agregar</span>
            </button>
        </div>
    </div>
</div>

<!-- Tabla de Usuarios -->
<div class="table-container bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="table-responsive">
        <table class="users-table" id="usersTable">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Estado</th>
                    <th>Último Acceso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                <tr data-user-id="{{ $usuario->id }}" class="user-row" data-telefono="{{ $usuario->telefono }}" data-direccion="{{ $usuario->direccion }}">
                    <!-- Usuario -->
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar">
                                @if($usuario->avatar)
                                    <img src="{{ $usuario->avatar_url }}" 
                                         alt="Avatar de {{ $usuario->full_name }}">
                                @else
                                    <div class="avatar-placeholder">
                                        {{ $usuario->initials }}
                                    </div>
                                @endif
                            </div>
                            <div class="user-info">
                                <div class="user-name">
                                    @if($usuario->nombres && $usuario->apellidos)
                                        {{ $usuario->nombres }} {{ $usuario->apellidos }}
                                    @else
                                        {{ $usuario->name }}
                                    @endif
                                </div>
                                <div class="user-phone">
                                    {{ $usuario->telefono ?? 'Sin teléfono' }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Email -->
                    <td>
                        <div class="email-cell">
                            <iconify-icon icon="heroicons:envelope" class="email-icon"></iconify-icon>
                            {{ $usuario->email }}
                        </div>
                    </td>

                    <!-- Roles -->
                    <td>
                        <div class="roles-container">
                            @forelse($usuario->roles as $role)
                                <span class="role-badge" style="background-color: #e53e3e20; color: #e53e3e; border-color: #e53e3e40;">
                                    {{ ucfirst(str_replace('-', '/', $role->name)) }}
                                </span>
                            @empty
                                <span class="no-role-badge">Sin roles</span>
                            @endforelse
                        </div>
                    </td>

                    <!-- Estado -->
                    <td>
                        <span class="status-badge {{ $usuario->is_active ? 'status-active' : 'status-inactive' }}">
                            <iconify-icon icon="{{ $usuario->is_active ? 'heroicons:check-circle' : 'heroicons:x-circle' }}"></iconify-icon>
                            {{ $usuario->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>

                    <!-- Último Acceso -->
                    <td>
                        <div class="last-login-cell">
                            @if($usuario->last_login_at)
                                <iconify-icon icon="heroicons:clock" class="time-icon"></iconify-icon>
                                <span>{{ $usuario->last_login_at->locale('es')->diffForHumans() }}</span>
                            @else
                                <span class="no-login">Nunca</span>
                            @endif
                        </div>
                    </td>

                    <!-- Acciones -->
                    <td>
                        <div class="action-buttons">
                            <!-- Ver -->
                            <button type="button" 
                                    class="action-btn btn-view" 
                                    onclick="viewUser({{ $usuario->id }})"
                                    title="Ver Detalles">
                                <iconify-icon icon="heroicons:eye"></iconify-icon>
                            </button>

                            @php
                                $isDueno = $usuario->roles->contains('name', 'dueño');
                            @endphp

                            @if(!$isDueno)
                                <!-- Editar -->
                                <button type="button" 
                                        class="action-btn btn-edit" 
                                        onclick="editUser({{ $usuario->id }})"
                                        title="Editar Usuario">
                                    <iconify-icon icon="heroicons:pencil"></iconify-icon>
                                </button>

                                <!-- Estado (toggle) -->
                                @if($usuario->id !== auth()->id())
                                <label class="toggle-switch user-toggle" title="Activar/Desactivar">
                                    <input type="checkbox" class="user-status-toggle" data-user-id="{{ $usuario->id }}" {{ $usuario->is_active ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                @endif

                                <!-- Eliminar -->
                                @if($usuario->id !== auth()->id())
                                <button type="button" 
                                        class="action-btn btn-delete" 
                                        onclick="deleteUser({{ $usuario->id }})"
                                        title="Eliminar Usuario">
                                    <iconify-icon icon="heroicons:trash"></iconify-icon>
                                </button>
                                @endif
                            @else
                                <!-- Botones protegidos para dueño -->
                                <button type="button" 
                                        class="action-btn btn-protected" 
                                        title="Usuario Protegido - No se puede editar">
                                    <iconify-icon icon="heroicons:shield-check"></iconify-icon>
                                </button>
                                <!-- Sin control de estado para Dueño -->
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
            
            <!-- Skeleton loading for Usuarios -->
            <tbody id="usuariosSkeletonBody" style="display:none;">
                @for ($i = 0; $i < 5; $i++)
                <tr class="skeleton-row-table">
                    <!-- Usuario -->
                    <td>
                        <div class="user-cell">
                            <div class="skeleton-avatar"></div>
                            <div class="user-info" style="width: 100%;">
                                <div class="skeleton-bar medium" style="margin-bottom: 4px;"></div>
                                <div class="skeleton-bar short"></div>
                            </div>
                        </div>
                    </td>
                    <!-- Email -->
                    <td><div class="skeleton-bar medium"></div></td>
                    <!-- Roles -->
                    <td><div class="skeleton-bar short"></div></td>
                    <!-- Estado -->
                    <td><div class="skeleton-bar short"></div></td>
                    <!-- Ultimo Acceso -->
                    <td><div class="skeleton-bar medium"></div></td>
                    <!-- Acciones -->
                    <td><div class="skeleton-bar actions"></div></td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear/Editar Usuario -->
<div id="userModal" class="modal-profesional hidden">
    <div class="modal-profesional-container">
        <!-- Header Profesional -->
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

        <!-- Content -->
        <div class="modal-content-profesional">
            <form id="userForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" id="userId" name="user_id">

                <!-- Sección 1: Foto de Perfil (compacta) -->
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
                            
                            <!-- Botones en fila cuando hay imagen -->
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
                            
                            <!-- Información de formatos compacta -->
                            <div class="avatar-info">
                                <span class="avatar-formats">Formatos: JPG, PNG, GIF (Máx. 2MB)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Información Personal -->
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

                <!-- Sección 3: Credenciales de Acceso -->
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

                <!-- Sección 4: Asignación de Roles -->
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
                    
                    <!-- Información compacta de roles -->
                    <div class="roles-info-compacta">
                        <iconify-icon icon="solar:info-circle-bold-duotone"></iconify-icon>
                        <span><strong>Tip:</strong> Puedes asignar múltiples roles. Los permisos se combinan automáticamente.</span>
                    </div>
                </div>

            </form>
        </div>
        
        <!-- Botones de Acción -->
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