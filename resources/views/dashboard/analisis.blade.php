@extends('layout.layout')

@php
    $title='Dashboard';
    $subTitle = 'Analisis';
@endphp

@push('styles')
    <!-- ⚡ OPTIMIZACIÓN SÚPER RÁPIDA PARA ANÁLISIS -->
    <link rel="preload" href="{{ asset('assets/images/users/user1.png') }}" as="image">
    <link rel="preload" href="{{ asset('assets/images/users/user2.png') }}" as="image">
    <link rel="preload" href="{{ asset('assets/images/users/user3.png') }}" as="image">
    <link rel="preload" href="{{ asset('assets/images/users/user4.png') }}" as="image">
    <link rel="preload" href="{{ asset('assets/images/users/user5.png') }}" as="image">
    <!-- ⚡ DNS Prefetch para recursos externos -->
    <link rel="dns-prefetch" href="//code.iconify.design">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <!-- ⚡ CSS específico para Dashboard de Análisis -->
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard/analisis.css') }}">
@endpush

@push('scripts')
    <script>
        window.chartData = {
            ventas: @json($ventasPorDia ?? []),
            totalIngresos: {{ $ingresosMes ?? 0 }},
            cambioVentas: {{ $cambioVentas ?? 0 }}
        };
        window.categoriasMasVendidas = @json($categoriasMasVendidas ?? []);
        window.cambiosComparativos = @json($cambiosComparativos ?? []);
    </script>
@endpush

@section('content')

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 4xl:grid-cols-5 gap-6">

        <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-purple-600/10 to-white">
            <div class="card-body h-full p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 mb-1">Inventario Total</p>
                        <h6 class="mb-0">{{ number_format($totalProductos) }}</h6>
                    </div>
                    <div class="w-[50px] h-[50px] bg-purple-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="solar:box-bold" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                    @if($cambioStock < 0)
                        <span class="inline-flex items-center gap-1 text-danger-600">
                            <iconify-icon icon="bxs:down-arrow" class="text-xs"></iconify-icon> 
                            {{ $cambioStock }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-success-600">
                            <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> 
                            Stock estable
                        </span>
                    @endif
                    Stock crítico: {{ $productosStockBajo }}
                </p>
            </div>
        </div><!-- card end -->
        
        <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-blue-600/10 to-white">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 mb-1">Ventas</p>
                        <h6 class="mb-0">{{ number_format($ventasMes) }}</h6>
                    </div>
                    <div class="w-[50px] h-[50px] bg-blue-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="solar:cart-bold" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2 cambio-ventas">
                    @if(($cambiosComparativos['porcentaje_cambio'] ?? 0) >= 0)
                        <span class="inline-flex items-center gap-1 text-success-600">
                            <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> 
                            +{{ number_format($cambiosComparativos['porcentaje_cambio'] ?? 0, 1) }}%
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-danger-600">
                            <iconify-icon icon="bxs:down-arrow" class="text-xs"></iconify-icon> 
                            {{ number_format($cambiosComparativos['porcentaje_cambio'] ?? 0, 1) }}%
                        </span>
                    @endif
                    {{ $cambiosComparativos['etiqueta'] ?? 'vs anterior' }}
                </p>
            </div>
        </div><!-- card end -->
        
        <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-success-600/10 to-white">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 mb-1">Ingresos</p>
                        <h6 class="mb-0">S/. {{ number_format($ingresosMes, 2) }}</h6>
                    </div>
                    <div class="w-[50px] h-[50px] bg-success-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="solar:dollar-bold" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-success-600">
                        <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> 
                        +S/. {{ number_format($ingresosMes/30, 2) }}
                    </span>
                    Promedio diario
                </p>
            </div>
        </div><!-- card end -->
        
        <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-red-600/10 to-white">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 mb-1">Rendimiento</p>
                        <h6 class="mb-0">S/. {{ number_format($rendimiento, 2) }}</h6>
                    </div>
                    <div class="w-[50px] h-[50px] bg-red-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="solar:chart-2-bold" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                    @if($cambioRendimiento >= 0)
                        <span class="inline-flex items-center gap-1 text-success-600">
                            <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> 
                            +S/. {{ number_format($cambioRendimiento, 2) }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-danger-600">
                            <iconify-icon icon="bxs:down-arrow" class="text-xs"></iconify-icon> 
                            S/. {{ number_format($cambioRendimiento, 2) }}
                        </span>
                    @endif
                    Margen estimado
                </p>
            </div>
        </div>
    </div>

    <!-- Gráfico principal de ventas -->
    <div class="grid grid-cols-1 gap-6 mt-6">
        <div class="col-span-1">
            <div class="card h-full rounded-lg border-0">
                <div class="card-body">
                    <div class="flex flex-wrap items-center justify-between">
                        <h6 class="text-lg mb-0 dark:text-white">Estadística de ventas</h6>
                        <select id="periodo-selector" class="form-select bg-white dark:bg-gray-700 dark:text-white dark:border-gray-600 form-select-sm w-auto">
                            <option value="hoy" {{ ($periodo ?? '') == 'hoy' ? 'selected' : '' }}>Hoy</option>
                            <option value="ultimos7" {{ ($periodo ?? '') == 'ultimos7' ? 'selected' : '' }}>Últimos 7 días</option>
                            <option value="mes" {{ ($periodo ?? '') == 'mes' ? 'selected' : '' }}>Este mes</option>
                            <option value="anual" {{ ($periodo ?? '') == 'anual' ? 'selected' : '' }}>Este año</option>
                        </select>
                    </div>
                    
                    <!-- Título del período -->
                    @if(!empty($tituloPeriodo ?? ''))
                    <div class="text-center mt-2 mb-1">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300 titulo-periodo">{{ $tituloPeriodo }}</p>
                    </div>
                    @endif
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <h6 class="mb-0 estadisticas-total dark:text-white">S/. {{ number_format(collect($ventasPorDia)->sum('total'), 2) }}</h6>
                        <span class="text-sm font-semibold rounded-full bg-success-100 dark:bg-success-600/20 text-success-600 dark:text-success-400 border border-success-200 dark:border-success-600/30 px-2 py-1.5 line-height-1 flex items-center gap-1 estadisticas-ventas">
                            {{ collect($ventasPorDia)->sum('ventas') }} ventas <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon>
                        </span>
                        <span class="text-xs font-medium estadisticas-promedio dark:text-gray-300">+ S/. {{ number_format(collect($ventasPorDia)->avg('total'), 2) }} Por día</span>
                    </div>
                    <div class="pt-[28px] apexcharts-tooltip-style-1 relative">
                        <!-- ⚡ Contenedor único del gráfico con padding adecuado -->
                        <div class="overflow-visible">
                            <div id="ventas-chart-analisis" class="h-[320px]" style="margin-left: 5px; width: calc(100% - 5px);"></div>
                        </div>
                        
                        <!-- ⚡ Placeholder mientras carga el gráfico -->
                        <div id="chart-loading" class="absolute inset-0 flex items-center justify-center h-64 text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <span class="ml-2">Cargando gráfico...</span>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Sección de Categorías y Top Productos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Categorías más vendidas -->
        <div class="col-span-1">
            <div class="card h-full rounded-lg border-0 shadow-sm">
                <div class="card-body p-6">
                    <div class="flex flex-wrap items-center justify-between mb-6">
                        <h6 class="font-bold text-xl text-gray-800 dark:text-white mb-0">Categorías más vendidas</h6>
                        <span class="bg-blue-100 dark:bg-blue-600/20 text-blue-700 dark:text-blue-400 px-3 py-1 rounded-full text-sm font-semibold">Todas las ventas</span>
                    </div>

                    <div class="overflow-visible">
                        <div id="categorias-chart-analisis" class="h-[280px]"></div>
                        
                        <!-- Loading para gráfico de categorías -->
                        <div id="categorias-loading" class="absolute inset-0 flex items-center justify-center h-64 text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800" style="display: none;">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                            <span class="ml-2 text-sm font-medium">Cargando categorías...</span>
                        </div>
                                                    </div>
                                                </div>
                                                    </div>
                                                </div>

        <!-- Top Productos Vendidos -->
        <div class="col-span-1">
            <div class="card h-full border-0 shadow-sm">
                <div class="card-body p-6">
                    <div class="mb-6">
                        <h6 class="font-bold text-xl text-gray-800 dark:text-white mb-0 flex items-center gap-3">
                            Top Productos Vendidos
                            <span class="text-white px-3 py-1 bg-blue-600 rounded-full text-sm font-semibold">{{ $productosMasVendidos->count() }}</span>
                        </h6>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-600">
                                    <th scope="col" class="text-left py-3 px-2 font-semibold text-gray-700 dark:text-gray-300 text-sm">Producto</th>
                                    <th scope="col" class="text-left py-3 px-2 font-semibold text-gray-700 dark:text-gray-300 text-sm">Categoría</th>
                                    <th scope="col" class="text-left py-3 px-2 font-semibold text-gray-700 dark:text-gray-300 text-sm">Cantidad</th>
                                    <th scope="col" class="text-center py-3 px-2 font-semibold text-gray-700 dark:text-gray-300 text-sm">Ingresos</th>
                                        </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                                @forelse($productosMasVendidos as $index => $producto)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="py-4 px-2">
                                                <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full shrink-0 me-3 overflow-hidden bg-blue-100 dark:bg-blue-600/20 flex items-center justify-center">
                                                <span class="font-bold text-blue-600 dark:text-blue-400 text-sm">#{{ $index + 1 }}</span>
                                            </div>
                                                    <div class="grow">
                                                <h6 class="text-sm mb-1 font-semibold text-gray-800 dark:text-white">{{ Str::limit($producto->nombre, 20) }}</h6>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ $producto->marca ?? 'Sin marca' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                    <td class="py-4 px-2">
                                        <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ $producto->categoria }}</span>
                                            </td>
                                    <td class="py-4 px-2">
                                        <span class="text-blue-600 dark:text-blue-400 font-bold text-base">{{ $producto->total_vendido }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">unidades</span>
                                            </td>
                                    <td class="text-center py-4 px-2">
                                        <span class="bg-green-100 dark:bg-green-600/20 text-green-700 dark:text-green-400 px-3 py-1.5 rounded-full font-semibold text-sm">S/. {{ number_format($producto->ingresos_producto, 2) }}</span>
                                            </td>
                                        </tr>
                                @empty
                                        <tr>
                                    <td colspan="4" class="text-center py-16">
                                        <div class="flex flex-col items-center justify-center">
                                            <iconify-icon icon="solar:chart-square-bold-duotone" class="text-7xl text-blue-400 dark:text-blue-500 mb-4"></iconify-icon>
                                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No hay ventas registradas</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Aún no se han registrado ventas en el sistema</p>
                                                </div>
                                            </td>
                                        </tr>
                                @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
            </div>
        </div>
    </div>

    <!-- Sección inferior: Stock Crítico y Usuarios Activos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Stock Crítico -->
        <div class="col-span-1">
            <div class="card h-full border-0 shadow-sm">
                <div class="card-body p-6">
                    <div class="mb-6">
                        <h6 class="font-bold text-xl text-gray-800 dark:text-white mb-0 flex items-center gap-3">
                            Stock Crítico
                            <span class="text-white px-3 py-1 bg-red-600 rounded-full text-sm font-semibold">{{ $productosStockCritico->count() }}</span>
                        </h6>
                    </div>

                            <div class="overflow-x-auto">
                        <table class="table w-full">
                                    <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-600">
                                    <th scope="col" class="text-left py-3 px-2 font-semibold text-gray-700 dark:text-gray-300 text-sm">Producto</th>
                                    <th scope="col" class="text-left py-3 px-2 font-semibold text-gray-700 dark:text-gray-300 text-sm">Categoría</th>
                                    <th scope="col" class="text-left py-3 px-2 font-semibold text-gray-700 dark:text-gray-300 text-sm">Stock</th>
                                    <th scope="col" class="text-center py-3 px-2 font-semibold text-gray-700 dark:text-gray-300 text-sm">Estado</th>
                                        </tr>
                                    </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                                @forelse($productosStockCritico as $producto)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="py-4 px-2">
                                                <div class="flex items-center">
                                                    <div class="grow">
                                                <h6 class="text-sm mb-1 font-semibold text-gray-800 dark:text-white">{{ Str::limit($producto->nombre, 18) }}</h6>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ $producto->concentracion ?? 'Sin concentración' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                    <td class="py-4 px-2">
                                        <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ $producto->categoria }}</span>
                                            </td>
                                    <td class="py-4 px-2">
                                        <span class="text-red-600 dark:text-red-400 font-bold text-base">{{ $producto->stock_actual }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(Min: {{ $producto->stock_minimo }})</span>
                                            </td>
                                    <td class="text-center py-4 px-2">
                                        <span class="bg-red-100 dark:bg-red-600/20 text-red-700 dark:text-red-400 px-3 py-1.5 rounded-full font-semibold text-sm">Crítico</span>
                                            </td>
                                        </tr>
                                @empty
                                        <tr>
                                    <td colspan="4" class="text-center py-16">
                                        <div class="flex flex-col items-center justify-center">
                                            <iconify-icon icon="solar:shield-check-bold-duotone" class="text-7xl text-green-400 dark:text-green-500 mb-4"></iconify-icon>
                                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Stock en buen estado</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Todos los productos tienen stock adecuado</p>
                                                </div>
                                            </td>
                                        </tr>
                                @endforelse
                                    </tbody>
                                </table>
                    </div>
                </div>
            </div>


        </div>

        <!-- Usuarios Activos -->
        <div class="col-span-1">
            <div class="card h-full border-0 shadow-sm">
                <div class="card-body p-6">
                    <div class="mb-6">
                        <h6 class="font-bold text-xl text-gray-800 dark:text-white mb-0">Usuarios Activos</h6>
                    </div>

                    <div class="space-y-4">
                        @forelse($usuariosActivos as $index => $usuario)
                        <div class="flex items-center justify-between gap-3 p-4 bg-gradient-to-r from-gray-50 to-blue-50/30 dark:from-gray-700/50 dark:to-blue-800/20 rounded-xl border border-gray-100 dark:border-gray-600 hover:shadow-sm transition-all">
                            <div class="flex items-center gap-4">
                                @if($usuario->avatar)
                                    <img src="{{ $usuario->avatar_url }}" alt="" class="w-12 h-12 rounded-full shrink-0 overflow-hidden border-2 border-white dark:border-gray-600 shadow-sm">
                                @else
                                    <div class="w-12 h-12 rounded-full shrink-0 overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-sm">
                                        <span class="text-white font-bold text-base">{{ strtoupper(substr($usuario->name, 0, 1)) }}</span>
                                </div>
                                @endif
                                <div class="grow">
                                    <h6 class="text-base mb-1 font-semibold text-gray-800 dark:text-white">{{ Str::limit($usuario->name, 18) }}</h6>
                                    <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">{{ $usuario->email }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                @php
                                    // Usuario está "en línea" si hizo login en los últimos 5 minutos
                                    $isOnline = $usuario->last_login_at && $usuario->last_login_at->diffInMinutes(now()) <= 5;
                                @endphp
                                
                                @if($isOnline)
                                    <span class="text-sm font-bold flex items-center gap-1">
                                        <span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                        <span style="color: #16a34a;" class="font-semibold">En línea</span>
                                    </span>
                                @elseif($usuario->last_login_at)
                                    <span class="text-sm text-gray-500 dark:text-gray-400 font-medium flex items-center gap-1">
                                        <span class="inline-block w-2 h-2 bg-gray-400 rounded-full"></span>
                                        Desconectado
                                    </span>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        {{ $usuario->last_login_at->locale('es')->diffForHumans() }}
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500 font-medium flex items-center gap-1">
                                        <span class="inline-block w-2 h-2 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                                        Sin conexión
                                    </span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-16 text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center">
                                <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="text-7xl text-gray-400 dark:text-gray-500 mb-4"></iconify-icon>
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Sin usuarios activos</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">No hay usuarios que hayan iniciado sesión</p>
                            </div>
                        </div>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Alertas de Lotes por Vencer (Full Width) -->
    @if(isset($alertasLotes) && count($alertasLotes) > 0)
    <div class="grid grid-cols-1 gap-6 mt-6">
        <div class="col-span-1">
            <div class="card border-0 shadow-sm rounded-lg bg-white dark:bg-gray-800">
                <div class="card-header border-b border-gray-100 dark:border-gray-700 p-5 bg-white dark:bg-gray-800 rounded-t-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center">
                            <iconify-icon icon="solar:bell-bing-bold-duotone" class="text-warning-500 text-2xl"></iconify-icon>
                        </div>
                        <h5 class="text-lg font-bold text-gray-800 dark:text-white mb-0">
                            Lotes Próximos a Vencer
                            <span class="ml-2 px-2.5 py-0.5 text-xs rounded-full bg-warning-100 text-warning-700 border border-warning-200 font-bold">
                                {{ count($alertasLotes) }}
                            </span>
                        </h5>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-4 font-bold tracking-wider">Producto</th>
                                    <th class="px-6 py-4 font-bold tracking-wider">Vencimiento</th>
                                    <th class="px-6 py-4 font-bold tracking-wider text-right">Estado / Días</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($alertasLotes->take(10) as $lote)
                                <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-800 dark:text-white text-base">{{ Str::limit($lote->producto->nombre ?? 'Desconocido', 40) }}</span>
                                            <span class="text-xs text-gray-500 font-mono mt-0.5 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded w-fit">{{ $lote->lote }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <iconify-icon icon="solar:calendar-date-bold-duotone" class="text-gray-400 text-lg"></iconify-icon>
                                            <span class="text-gray-700 dark:text-gray-300 font-medium">{{ \Carbon\Carbon::parse($lote->fecha_vencimiento)->format('d/m/Y') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @php
                                            $dias = round(now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($lote->fecha_vencimiento)->startOfDay(), false));
                                        @endphp
                                        @if($dias < 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                                Vencido
                                            </span>
                                        @elseif($dias <= 30)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-50 text-red-600 border border-red-100">
                                                {{ $dias }} días
                                            </span>
                                        @elseif($dias <= 90)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-50 text-orange-600 border border-orange-100">
                                                {{ $dias }} días
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-50 text-green-600 border border-green-100">
                                                {{ $dias }} días
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('scripts')
<!-- ApexCharts para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
<!-- ⚡ JavaScript específico para Dashboard de Análisis -->
<script src="{{ asset('assets/js/dashboard/analisis.js') }}"></script>
@endpush