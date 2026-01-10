@extends('layout.layout')
@php
    $title='Gestión de Roles';
    $subTitle = 'Roles y Permisos';
    $v = '?v=' . time();
    $script='
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx-js-style.min.js"></script>
        <script src="' . asset('assets/js/admin/roles.js') . $v . '"></script>
    ';
@endphp

<head>
    <title>Gestión de Roles y Permisos</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // URLs absolutas para evitar 404 por diferencias de base path/host
        window.ROLES_API_URL = "{{ route('admin.roles.api') }}";
        window.ROLES_BASE_URL = "{{ url('/admin/roles') }}";
    </script>
    <link rel="stylesheet" href="{{ asset('assets/css/inventario/crud.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin/roles.css') }}?v={{ time() }}">
    <!-- FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

@section('content')
@include('components.loading-overlay', ['id' => 'loadingOverlay', 'label' => 'Cargando datos...'])

<!-- Header con estadísticas elegantes -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <!-- Total Roles -->
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-purple-600/10 to-bg-white">
        <div class="card-body h-full p-5">
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
                <span class="inline-flex items-center gap-1 text-success-600">
                    <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> 
                    {{ $roles->where('is_active', true)->count() }}
                </span>
                Roles activos en el sistema
            </p>
        </div>
    </div>

    <!-- Total Permisos -->
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-blue-600/10 to-bg-white">
        <div class="card-body p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-neutral-900 mb-1">Total Permisos</p>
                    <h6 class="mb-0 text-2xl font-bold">{{ $permisos->flatten()->count() }}</h6>
                </div>
                <div class="w-[50px] h-[50px] bg-blue-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="solar:shield-keyhole-bold-duotone" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
            </div>
            <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-info-600">
                    <iconify-icon icon="heroicons:shield-check" class="text-xs"></iconify-icon> 
                    Activos
                </span>
                Permisos disponibles del sistema
            </p>
        </div>
    </div>

    <!-- Módulos del Sistema -->
    <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-success-600/10 to-bg-white">
        <div class="card-body p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-medium text-neutral-900 mb-1">Módulos del Sistema</p>
                    <h6 class="mb-0 text-2xl font-bold">{{ $permisos->count() }}</h6>
                </div>
                <div class="w-[50px] h-[50px] bg-success-600 rounded-full flex justify-center items-center">
                    <iconify-icon icon="solar:layers-bold-duotone" class="text-white text-2xl mb-0"></iconify-icon>
                </div>
            </div>
            <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-success-600">
                    <iconify-icon icon="heroicons:cog-6-tooth" class="text-xs"></iconify-icon> 
                    Configurados
                </span>
                Módulos organizados del sistema
            </p>
        </div>
    </div>
</div>

<!-- Controles Elegantes -->
<div class="filter-section bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-4">
    <div class="flex flex-wrap items-center gap-4 justify-between">
        <!-- Búsqueda Elegante -->
        <div class="search-container-modern w-full sm:w-auto sm:min-w-[320px] flex-1">
            <div class="relative">
                <input type="text" 
                       id="searchInput" 
                       placeholder="Buscar roles por nombre o descripción..." 
                       class="block w-full px-4 py-3 border border-gray-300 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 bg-white shadow-sm transition-all duration-200 hover:shadow-md">
            </div>
        </div>

        <!-- Acciones Elegantes -->
        <div class="flex items-center gap-2">
            <button type="button" 
                    class="btn-action-elegant btn-export" 
                    onclick="exportRoles()"
                    title="Exportar Roles">
                <iconify-icon icon="solar:download-bold-duotone"></iconify-icon>
                <span>Exportar</span>
            </button>

            <button type="button" 
                    class="btn-action-elegant btn-primary" 
                    onclick="openCreateRoleModal()"
                    title="Crear Nuevo Rol">
                <iconify-icon icon="solar:shield-plus-bold-duotone"></iconify-icon>
                <span>Agregar</span>
            </button>
        </div>
    </div>
</div>

<!-- Tabla de Roles -->
<div class="table-container">
    <div class="table-responsive">
        <table class="roles-table">
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Descripción</th>
                    <th>Permisos</th>
                    <th>Usuarios</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr class="role-row" data-role-id="{{ $role->id }}">
                    <!-- Celda de Rol -->
                    <td class="role-cell">
                        <div class="role-info">
                            <span class="role-color-indicator" style="background: {{ $role->color ?? '#e5e7eb' }}"></span>
                            <div class="role-details">
                                @if(in_array($role->name, ['dueño', 'gerente']))
                                    <div class="protected-badge-container">
                                        <span class="protected-badge">
                                            <iconify-icon icon="solar:crown-bold-duotone"></iconify-icon>
                                            Protegido
                                        </span>
                                    </div>
                                @endif
                                <div class="role-name">
                                    {{ $role->display_name }}
                                </div>
                                @if($role->display_name !== $role->name)
                                    <div class="role-system-name">{{ $role->name }}</div>
                                @endif
                            </div>
                        </div>
                    </td>

                    <!-- Descripción -->
                    <td class="description-cell">
                        @if($role->description)
                            <span class="description-text">{{ Str::limit($role->description, 60) }}</span>
                        @else
                            <span class="no-description">Sin descripción</span>
                        @endif
                    </td>

                    <!-- Permisos -->
                    <td class="permissions-cell">
                        <div class="permissions-info">
                            <div class="permissions-count">
                                <iconify-icon icon="solar:shield-check-bold-duotone" class="permission-icon"></iconify-icon>
                                @if(in_array($role->name, ['dueño', 'gerente']))
                                    <span class="count">Acceso</span>
                                    <span class="label">completo</span>
                                @else
                                    <span class="count">{{ $role->permissions->count() }}</span>
                                    <span class="label">permisos</span>
                                @endif
                            </div>
                        </div>
                    </td>

                    <!-- Usuarios -->
                    <td class="users-cell">
                        <div class="users-count">
                            <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="users-icon"></iconify-icon>
                            <span class="count">{{ $role->users->count() }}</span>
                            <span class="label">{{ $role->users->count() === 1 ? 'usuario' : 'usuarios' }}</span>
                        </div>
                    </td>

                    <!-- Estado -->
                    <td class="status-cell">
                        @if($role->is_active)
                        <span class="status-badge status-active">
                            <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                            Activo
                        </span>
                        @else
                        <span class="status-badge status-inactive" style="background: linear-gradient(135deg, rgba(239,68,68,0.12) 0%, rgba(239,68,68,0.08) 100%); color: #dc2626; border-color: rgba(239,68,68,0.3); box-shadow: 0 2px 4px rgba(239,68,68,0.1);">
                            <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                            Inactivo
                        </span>
                        @endif
                    </td>

                    <!-- Acciones -->
                    <td class="actions-cell">
                        <div class="action-buttons">
                            <button class="action-btn btn-view" 
                                    onclick="viewRole({{ $role->id }})"
                                    title="Ver Detalles">
                                <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                            </button>
                            @if(!in_array($role->name, ['dueño', 'gerente']))
                                <button class="action-btn btn-edit" 
                                        onclick="editRole({{ $role->id }})"
                                        title="Editar Rol">
                                    <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                                </button>
                                <!-- Estado (toggle) -->
                                <label class="toggle-switch role-toggle" title="Activar/Desactivar">
                                    <input type="checkbox" class="role-status-toggle" data-role-id="{{ $role->id }}" {{ $role->is_active ? 'checked' : '' }}>
                                    <span class="toggle-slider"></span>
                                </label>
                                <button class="action-btn btn-delete" 
                                        onclick="deleteRole({{ $role->id }})"
                                        title="Eliminar Rol">
                                    <iconify-icon icon="solar:trash-bin-trash-bold-duotone"></iconify-icon>
                                </button>
                            @else
                                <button class="action-btn btn-protected" 
                                        title="Rol Protegido - No se puede editar">
                                    <iconify-icon icon="solar:shield-keyhole-bold-duotone"></iconify-icon>
                                </button>
                                <button class="action-btn btn-protected" 
                                        title="Rol Protegido - No se puede eliminar">
                                    <iconify-icon icon="solar:shield-warning-bold-duotone"></iconify-icon>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-state">
                        <div class="empty-content">
                            <iconify-icon icon="solar:shield-user-bold-duotone" class="empty-icon"></iconify-icon>
                            <h3>No hay roles configurados</h3>
                            <p>Crea el primer rol para comenzar a gestionar permisos</p>
                            <button type="button" class="btn-action-elegant btn-primary" onclick="openCreateRoleModal()">
                                <iconify-icon icon="solar:shield-plus-bold-duotone"></iconify-icon>
                                <span>Crear Primer Rol</span>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>

            <!-- Skeleton Loading para Roles -->
            <tbody id="rolesSkeletonBody" style="display: none;">
                @for ($i = 0; $i < 5; $i++)
                <tr class="skeleton-row-table">
                    <td><div class="skeleton-bar medium"></div></td>
                    <td><div class="skeleton-bar long"></div></td>
                    <td><div class="skeleton-bar short"></div></td>
                    <td><div class="skeleton-bar short"></div></td>
                    <td><div class="skeleton-bar short"></div></td>
                    <td><div class="skeleton-bar actions"></div></td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PERMISOS DEL SISTEMA - COMPLETAMENTE NUEVO -->
<div id="permissionsModal" class="permisos-modal-overlay hidden">
    <div class="permisos-modal-container">
        <!-- Header -->
        <div class="permisos-modal-header">
            <div class="permisos-header-content">
                <div class="permisos-header-icon">
                    <iconify-icon icon="solar:shield-check-bold-duotone"></iconify-icon>
                </div>
                <div class="permisos-header-text">
                    <h2>Permisos del Sistema</h2>
                    <p>Vista completa de todos los permisos disponibles</p>
                </div>
            </div>
            <button type="button" class="permisos-close-btn" onclick="closePermissionsModal()">
                <iconify-icon icon="heroicons:x-mark"></iconify-icon>
            </button>
        </div>

        <!-- Body -->
        <div class="permisos-modal-body">
            <!-- Permisos por Módulo -->
            <div class="permisos-modules-section">
                <div class="permisos-modules-header">
                    <iconify-icon icon="solar:layers-bold-duotone"></iconify-icon>
                    <h3>Permisos por Módulo</h3>
                </div>
                <div class="permisos-modules-list">
                    @foreach($permisos as $modulo => $permisosPorModulo)
                    <div class="permisos-module-card">
                        <div class="permisos-module-header">
                            <div class="permisos-module-title">
                                <iconify-icon icon="solar:layers-bold-duotone"></iconify-icon>
                                <span>{{ ucfirst($modulo) }}</span>
                                <span class="permisos-module-count">{{ $permisosPorModulo->count() }} permisos</span>
                            </div>
                        </div>
                        <div class="permisos-items-list">
                            @foreach($permisosPorModulo as $permiso)
                            <div class="permisos-item">
                                <div class="permisos-item-icon">
                                    <iconify-icon icon="solar:shield-check-bold-duotone"></iconify-icon>
                                </div>
                                <div class="permisos-item-content">
                                    <div class="permisos-item-name">{{ $permiso->display_name }}</div>
                                    <div class="permisos-item-code">{{ $permiso->name }}</div>
                                    @if($permiso->description)
                                    <div class="permisos-item-desc">{{ $permiso->description }}</div>
                                    @endif
                                </div>
                                <div class="permisos-item-roles">
                                    <span class="roles-count">{{ $permiso->roles->count() }}</span>
                                    <span class="roles-label">{{ $permiso->roles->count() === 1 ? 'rol' : 'roles' }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="permisos-modal-footer">
            <button type="button" class="permisos-btn-close" onclick="closePermissionsModal()">
                <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Rol -->
<div id="roleModal" class="modal-profesional hidden">
    <div class="modal-profesional-container">
        <!-- Header Profesional -->
        <div class="header-profesional">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-icon">
                        <iconify-icon icon="solar:shield-plus-bold-duotone" id="modalIcon"></iconify-icon>
                    </div>
                    <div class="header-text">
                        <h3 id="modalTitle">Crear Nuevo Rol</h3>
                        <p>Configure los permisos y características del rol</p>
                    </div>
                </div>
                <button type="button" class="btn-close" onclick="closeRoleModal()">
                    <iconify-icon icon="heroicons:x-mark"></iconify-icon>
                </button>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-bar" id="roleProgressBar"></div>
        </div>

        <!-- Content -->
        <div class="modal-content-profesional">
            <form id="roleForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" id="roleId" name="role_id">

                <!-- Sección 1: Información del Rol -->
                <div class="seccion-form seccion-azul">
                    <div class="seccion-header">
                        <div class="seccion-icon icon-azul">
                            <iconify-icon icon="solar:shield-user-bold-duotone"></iconify-icon>
                        </div>
                        <div class="seccion-titulo">
                            <h3>Información del Rol</h3>
                            <p>Datos básicos y configuración del rol</p>
                        </div>
                    </div>
                    
                    <div class="grid-campos columnas-2">
                        <div class="campo-grupo campo-completo">
                            <label class="campo-label">
                                <iconify-icon icon="solar:eye-bold-duotone" class="label-icon" style="color: #3b82f6;"></iconify-icon>
                                Nombre del Rol *
                            </label>
                            <input type="text" name="display_name" id="display_name" class="campo-input" placeholder="ej: Administrador, Vendedor" required>
                        </div>
                        
                        <div class="campo-grupo campo-completo">
                            <label class="campo-label">
                                <iconify-icon icon="solar:document-text-bold-duotone" class="label-icon" style="color: #3b82f6;"></iconify-icon>
                                Descripción
                            </label>
                            <input type="text" name="description" id="description" class="campo-input" placeholder="Describe las responsabilidades de este rol...">
                        </div>
                        
                        <div class="campo-grupo">
                            <label class="campo-label">
                                <iconify-icon icon="solar:pallete-bold-duotone" class="label-icon" style="color: #3b82f6;"></iconify-icon>
                                Color del Rol *
                            </label>
                            <div class="color-picker-rol">
                                <input type="color" name="color" id="color" class="color-input-rol" value="#e53e3e" required>
                                <div class="color-presets-rol">
                                    <button type="button" class="color-preset-rol" data-color="#e53e3e" style="background: #e53e3e;" title="Rojo Principal"></button>
                                    <button type="button" class="color-preset-rol" data-color="#3b82f6" style="background: #3b82f6;" title="Azul"></button>
                                    <button type="button" class="color-preset-rol" data-color="#10b981" style="background: #10b981;" title="Verde"></button>
                                    <button type="button" class="color-preset-rol" data-color="#f59e0b" style="background: #f59e0b;" title="Amarillo"></button>
                                    <button type="button" class="color-preset-rol" data-color="#8b5cf6" style="background: #8b5cf6;" title="Púrpura"></button>
                                    <button type="button" class="color-preset-rol" data-color="#ef4444" style="background: #ef4444;" title="Rojo"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Asignación de Permisos -->
                <div class="seccion-form seccion-purpura">
                    <div class="seccion-header">
                        <div class="seccion-icon icon-purpura">
                            <iconify-icon icon="solar:shield-keyhole-bold-duotone"></iconify-icon>
                        </div>
                        <div class="seccion-titulo">
                            <h3>Asignación de Permisos</h3>
                            <p>Selecciona los permisos que tendrá este rol</p>
                        </div>
                        <div class="contador-permisos">
                            <div class="contador-numero">
                                <span id="selectedPermissionsCount">0</span>/<span>{{ $permisos->flatten()->count() }}</span>
                            </div>
                            <div class="contador-label">permisos seleccionados</div>
                        </div>
                    </div>

                    <!-- Controles de selección -->
                    <div class="controles-permisos">
                        <button type="button" class="btn-control-permisos btn-seleccionar" onclick="selectAllPermissions()">
                            <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                            Seleccionar Todo
                        </button>
                        <button type="button" class="btn-control-permisos btn-deseleccionar oculto" onclick="deselectAllPermissions()">
                            <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                            Deseleccionar Todo
                        </button>
                    </div>

                    <!-- 💡 Tip compacto -->
                    <div style="display: flex; align-items: center; gap: 8px; background: #f3f4f6; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; color: #4b5563;">
                        <iconify-icon icon="solar:lightbulb-bold-duotone" style="color: #f59e0b; font-size: 16px;"></iconify-icon>
                        <span><strong>Tip:</strong> Puedes seleccionar módulos completos marcando el checkbox del encabezado.</span>
                    </div>

                    <!-- Permisos por módulo -->
                    <div class="modulos-permisos">
                        @php
                            // Diccionario de etiquetas amigables en español
                            $permLabels = [
                                // Dashboard
                                'dashboard.access' => 'Dashboard',

                                // Ventas
                                'ventas.view' => 'Ver ventas',
                                'ventas.create' => 'Crear venta',
                                'ventas.edit' => 'Editar venta',
                                'ventas.delete' => 'Eliminar venta',
                                'ventas.reports' => 'Reportes de ventas',
                                'ventas.devoluciones' => 'Devoluciones de ventas',
                                'ventas.clientes' => 'Clientes de ventas',

                                // Inventario
                                'inventario.view' => 'Ver inventario',
                                'inventario.create' => 'Crear inventario',
                                'inventario.edit' => 'Editar inventario',
                                'inventario.delete' => 'Eliminar inventario',

                                // Productos
                                'productos.view' => 'Ver productos',
                                'productos.create' => 'Crear producto',
                                'productos.edit' => 'Editar producto',
                                'productos.delete' => 'Eliminar producto',

                                // Usuarios
                                'usuarios.view' => 'Ver usuarios',
                                'usuarios.create' => 'Crear usuario',
                                'usuarios.edit' => 'Editar usuario',
                                'usuarios.delete' => 'Eliminar usuario',
                                'usuarios.activate' => 'Activar usuario',
                                'usuarios.roles' => 'Roles de usuario',

                                // Compras
                                'compras.view' => 'Ver compras',
                                'compras.create' => 'Crear compra',
                                'compras.edit' => 'Editar compra',
                                'compras.delete' => 'Eliminar compra',
                            ];
                        @endphp
                        @foreach($permisos as $modulo => $permisosPorModulo)
                        <div class="modulo-permiso-card">
                            <!-- Header del módulo con checkbox principal -->
                            <div class="modulo-header">
                                <div class="modulo-titulo">
                                    <iconify-icon icon="solar:layers-bold-duotone" class="modulo-icon"></iconify-icon>
                                    <span class="modulo-nombre">{{ ucfirst($modulo) }}</span>
                                </div>
                                <div class="modulo-contador">
                                    <span class="contador-total">{{ $permisosPorModulo->count() }}</span>
                                    <span class="contador-texto">permisos</span>
                                </div>
                            </div>
                            
                            <!-- Permisos del módulo -->
                            <div class="permisos-grid">
                                @php $dashboardShown = false; @endphp
                                @foreach($permisosPorModulo as $permiso)
                                @php
                                    $name = $permiso->name;
                                    // Etiqueta amigable por diccionario o fallback
                                    $label = $permLabels[$name] ?? ($permiso->display_name ?? ucfirst(str_replace('.', ' ', $name)));
                                    // Colapsar dashboard a una sola entrada visible
                                    if (str_starts_with($name, 'dashboard.')) {
                                        $label = 'Dashboard';
                                    }
                                    $isDashboard = str_starts_with($name, 'dashboard.');
                                    $hiddenDashboard = $isDashboard && $dashboardShown;
                                    if ($isDashboard && !$dashboardShown) { $dashboardShown = true; }
                                @endphp
                                <div class="permiso-card" @if($hiddenDashboard) style="display:none" @endif>
                                    <input type="checkbox" 
                                           name="permisos[]" 
                                           value="{{ $permiso->id }}" 
                                           id="permission_{{ $permiso->id }}"
                                           class="checkbox-permiso permission-checkbox" 
                                           data-module="{{ $modulo }}"
                                           data-name="{{ $name }}"
                                           @if($hiddenDashboard) data-dashboard-hidden="true" @endif
                                           onchange="updatePermissionCount()">
                                    <label for="permission_{{ $permiso->id }}" class="permiso-label">
                                        <div class="permiso-info">
                                            <div class="permiso-nombre">{{ $label }}</div>
                                            <div class="permiso-codigo">{{ $name }}</div>
                                            @if($permiso->description)
                                            <div class="permiso-descripcion">{{ $permiso->description }}</div>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>

                        </div>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer Profesional -->
        <div class="footer-profesional">
            <div class="footer-botones">
                <button type="button" class="btn-cancelar" onclick="closeRoleModal()">
                    <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                    Cancelar
                </button>
                <button type="submit" form="roleForm" class="btn-guardar">
                    <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                    <span id="saveButtonText">Crear Rol</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection