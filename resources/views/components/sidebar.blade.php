{{-- Iconify ya está importado globalmente via Vite/app.js. Evitar <head> incrustado que rompe navegación con Turbo. --}}
<aside class="sidebar {{ request()->cookie('sidebar_collapsed') === '1' ? 'active' : '' }}">
    <button type="button" class="sidebar-close-btn !mt-4">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="{{ route('dashboard.analisis') }}" class="sidebar-logo">
            <img src="{{ asset('assets/images/logotipo.png') }}" alt="site logo" class="light-logo">
            <img src="{{ asset('assets/images/logotipo.png') }}" alt="site logo" class="dark-logo">
            <img src="{{ asset('assets/images/logotipo.png') }}" alt="site logo" class="logo-icon">
            <span class="sidebar-brand-text">San Antonio</span>
        </a>
    </div>
    
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">
            @can('dashboard.view')
            <li>
                <a href="{{ route('dashboard.analisis') }}" class="{{ request()->routeIs('dashboard.analisis') || request()->is('dashboard/analisis') ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:chart-square-bold" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
            </li>
            @endcan

            <li>
                <a href="{{ route('ai.index') }}" class="{{ request()->routeIs('ai.*') ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:magic-stick-3-bold-duotone" class="menu-icon" style="color: #8b5cf6;"></iconify-icon>
                    <span>Asistente IA</span>
                    <span class="badge bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-xs px-2 py-0.5 rounded-full ml-2">Nuevo</span>
                </a>
            </li>

            @if(auth()->check() && (auth()->user()->can('ventas.view') || auth()->user()->can('ventas.create')))
            <li class="sidebar-menu-group-title">Punto de Venta</li>
            
            @can('ventas.create')
            <li>
                <a href="{{ route('punto-venta.index') }}" data-turbo="false" class="{{ request()->routeIs('punto-venta.index') ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:shop-2-bold-duotone" class="menu-icon"></iconify-icon>
                    <span>Nueva Venta (POS)</span>
                </a>
            </li>
            @endcan
            
            <li class="dropdown {{ request()->routeIs('ventas.*') ? 'dropdown-open open' : '' }}">
                <a href="javascript:void(0)" class="{{ request()->routeIs('ventas.*') ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:chart-square-bold-duotone" class="menu-icon"></iconify-icon>
                    <span>Gestión de Ventas</span>
                </a>
                <ul class="sidebar-submenu">
                    @can('ventas.view')
                    <li>
                        <a href="{{ route('ventas.historial') }}" class="{{ request()->routeIs('ventas.historial') ? 'active-page' : '' }}">
                            <i class="ri-circle-fill circle-icon text-primary-600"></i> Historial de Ventas
                        </a>
                    </li>
                    @endcan
                    @can('ventas.create')
                    <li>
                        <a href="{{ route('ventas.devoluciones') }}" class="{{ request()->routeIs('ventas.devoluciones') ? 'active-page' : '' }}">
                            <i class="ri-circle-fill circle-icon text-danger-600"></i> Devoluciones
                        </a>
                    </li>
                    @endcan
                    @can('ventas.reports')
                    <li>
                        <a href="{{ route('ventas.reportes') }}" class="{{ request()->routeIs('ventas.reportes') ? 'active-page' : '' }}">
                            <i class="ri-circle-fill circle-icon text-info-600"></i> Reportes
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endif

            @if(auth()->check() && (auth()->user()->can('inventario.view') || auth()->user()->can('ubicaciones.view') || auth()->user()->can('compras.view')))
            <li class="sidebar-menu-group-title">Gestión de Inventario</li>
            
            @can('inventario.view')
            <li class="dropdown {{ request()->routeIs('inventario.productos.botica') || request()->routeIs('inventario.categorias') ? 'dropdown-open open' : '' }}">
                <a href="javascript:void(0)" class="{{ request()->routeIs('inventario.productos.botica') || request()->routeIs('inventario.categorias') ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:box-bold" class="menu-icon"></iconify-icon>
                    <span>Productos</span>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="{{ route('inventario.productos.botica') }}" class="{{ request()->routeIs('inventario.productos.botica') ? 'active-page' : '' }}">
                            <i class="ri-circle-fill circle-icon text-danger-600"></i> Lista de Productos
                        </a>
                    </li>
                    
                    @can('inventario.categories')
                    <li>
                        <a href="{{ route('inventario.categorias') }}" class="{{ request()->routeIs('inventario.categorias') ? 'active-page' : '' }}">
                            <i class="ri-circle-fill circle-icon text-success-600"></i> Categorías
                        </a>
                    </li>
                    @endcan
                    <li>
                        <a href="{{ route('admin.reportes.inventario.salud') }}" class="{{ request()->routeIs('admin.reportes.inventario.salud') ? 'active-page' : '' }}">
                            <i class="ri-circle-fill circle-icon text-info-600"></i> Salud del Inventario
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            @can('compras.view')
            <li class="dropdown {{ request()->routeIs('compras.nueva') || request()->routeIs('compras.historial') ? 'dropdown-open open' : '' }}">
                <a href="javascript:void(0)" class="{{ request()->routeIs('compras.nueva') || request()->routeIs('compras.historial') ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:box-minimalistic-bold-duotone" class="menu-icon"></iconify-icon>
                    <span>Entrada de Mercadería</span>
                </a>
                <ul class="sidebar-submenu">
                    @can('compras.create')
                    <li>
                        <a href="{{ route('compras.nueva') }}" class="{{ request()->routeIs('compras.nueva') ? 'active-page' : '' }}">
                            <i class="ri-circle-fill circle-icon text-primary-600"></i> Nueva Entrada
                        </a>
                    </li>
                    @endcan
                    <li>
                        <a href="{{ route('compras.historial') }}" class="{{ request()->routeIs('compras.historial') ? 'active-page' : '' }}">
                            <i class="ri-circle-fill circle-icon text-info-600"></i> Historial de Entradas
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            <li>
                <a href="{{ route('admin.logs') }}" class="{{ request()->routeIs('admin.logs') ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:clipboard-list-bold-duotone" class="menu-icon"></iconify-icon>
                    <span>Auditoría</span>
                </a>
            </li>
            @endif

            @if(auth()->check() && (auth()->user()->can('usuarios.view') || auth()->user()->can('usuarios.roles')))
            <li class="sidebar-menu-group-title">Control de Usuarios</li>
            <li class="dropdown {{ request()->routeIs('admin.usuarios.*') || request()->routeIs('admin.roles.*') ? 'dropdown-open open' : '' }}">
                <a href="javascript:void(0)" class="{{ request()->routeIs('admin.usuarios.*') || request()->routeIs('admin.roles.*') ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:users-group-two-rounded-bold" class="menu-icon"></iconify-icon>
                    <span>Usuarios y Roles</span>
                </a>
                <ul class="sidebar-submenu">
                    @can('usuarios.view')
                    <li>
                        <a href="{{ route('admin.usuarios.index') }}" class="{{ request()->routeIs('admin.usuarios.index') ? 'active-page' : '' }}">
                            <i class="ri-circle-fill circle-icon text-primary-600"></i> Gestión de Usuarios
                        </a>
                    </li>
                    @endcan
                    @can('usuarios.roles')
                    <li>
                        <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.index') ? 'active-page' : '' }}">
                            <i class="ri-circle-fill circle-icon text-warning-600"></i> Roles y Permisos
                        </a>
                    </li>
                    @endcan
                </ul>
            </li>
            @endif

            @can('compras.view')
            <li>
                <a href="{{ route('compras.proveedores') }}" class="{{ request()->routeIs('compras.proveedores') ? 'active-page' : '' }}">
                    <iconify-icon icon="solar:users-group-rounded-bold" class="menu-icon"></iconify-icon>
                    <span>Proveedores</span>
                </a>
            </li>
            @endcan
        </ul>
    </div>
</aside>