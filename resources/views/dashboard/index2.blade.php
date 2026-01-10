@extends('layout.layout')

@php
    $title='Dashboard';
    $subTitle = 'Dashboard Principal';
    $script= '<script src="' . asset('assets/js/homeOneChart.js') . '"></script>';
@endphp

<head>
    <title>Dashboard - Botica San Antonio</title>
    
    <!-- ⚡ PRELOAD RECURSOS CRÍTICOS PARA DASHBOARD -->
    <link rel="preload" href="{{ asset('assets/js/homeOneChart.js') }}" as="script">
    <link rel="preload" href="{{ asset('assets/images/users/user1.png') }}" as="image">
    <link rel="preload" href="{{ asset('assets/images/users/user2.png') }}" as="image">
    <link rel="preload" href="{{ asset('assets/images/users/user3.png') }}" as="image">
    
    <!-- ⚡ SCRIPT ESPECÍFICO PARA DASHBOARD PRINCIPAL -->
    <script>
        // Optimización específica para dashboard principal (página más pesada)
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🏠 Dashboard Principal - Iniciando optimizaciones...');
            
            // Precargar imágenes críticas del dashboard
            const criticalImages = [
                '{{ asset("assets/images/users/user4.png") }}',
                '{{ asset("assets/images/users/user5.png") }}'
            ];
            
            criticalImages.forEach(src => {
                const img = new Image();
                img.src = src;
            });
            
            // Optimizar tablas con lazy loading
            const tables = document.querySelectorAll('table');
            tables.forEach(table => {
                table.style.opacity = '0';
                table.style.transition = 'opacity 0.3s ease';
                
                // Mostrar tabla cuando esté lista
                setTimeout(() => {
                    table.style.opacity = '1';
                }, 100);
            });
            
            // Observer para detectar cuando el gráfico está listo
            const chartContainer = document.getElementById('chart');
            const chartLoading = document.getElementById('chart-loading');
            
            if (chartContainer && chartLoading) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length > 0) {
                            const hasChart = chartContainer.querySelector('.apexcharts-canvas');
                            if (hasChart && chartLoading) {
                                // Ocultar loading con animación suave
                                chartLoading.style.transition = 'opacity 0.3s ease';
                                chartLoading.style.opacity = '0';
                                setTimeout(() => {
                                    chartLoading.style.display = 'none';
                                }, 300);
                                
                                console.log('📊 Gráfico del Dashboard cargado');
                                observer.disconnect();
                            }
                        }
                    });
                });
                
                observer.observe(chartContainer, { childList: true, subtree: true });
                
                // Fallback: ocultar loading después de 4 segundos
                setTimeout(() => {
                    if (chartLoading && chartLoading.style.display !== 'none') {
                        chartLoading.style.transition = 'opacity 0.3s ease';
                        chartLoading.style.opacity = '0';
                        setTimeout(() => {
                            chartLoading.style.display = 'none';
                        }, 300);
                    }
                    observer.disconnect();
                }, 4000);
            }
            
            console.log('🏠 Dashboard Principal optimizado completamente');
        });
    </script>
</head>

@section('content')

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 4xl:grid-cols-5 gap-6">

        <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-purple-600/10 to-bg-white">
            <div class="card-body h-full p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 mb-1">Inventario</p>
                        <h6 class="mb-0">15,000</h6>
                    </div>
                    <div class="w-[50px] h-[50px] bg-purple-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="fa-solid:award" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-danger-600"><iconify-icon icon="bxs:down-arrow" class="text-xs"></iconify-icon> -800</span>
                    Stock de los últimos 30 días
                </p>
            </div>
        </div><!-- card end -->
        <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-blue-600/10 to-bg-white">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 mb-1">Ventas</p>
                        <h6 class="mb-0">5,000</h6>
                    </div>
                    <div class="w-[50px] h-[50px] bg-blue-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="fluent:people-20-filled" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-success-600"><iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> +200</span>
                    Ventas de los últimos 30 días
                </p>
            </div>
        </div><!-- card end -->
        <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-success-600/10 to-bg-white">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 mb-1">Gastos </p>
                        <h6 class="mb-0">S/42,000</h6>
                    </div>
                    <div class="w-[50px] h-[50px] bg-success-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="solar:wallet-bold" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-success-600"><iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> +$20,000</span>
                    Last 30 days income
                </p>
            </div>
        </div><!-- card end -->
        <div class="card shadow-none border border-gray-200 rounded-lg h-full bg-gradient-to-r from-red-600/10 to-bg-white">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 mb-1">Rendimiento</p>
                        <h6 class="mb-0">S/30,000</h6>
                    </div>
                    <div class="w-[50px] h-[50px] bg-red-600 rounded-full flex justify-center items-center">
                        <iconify-icon icon="fa6-solid:file-invoice-dollar" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
                <p class="font-medium text-sm text-neutral-600 mt-3 mb-0 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-success-600"><iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon> +$5,000</span>
                    Last 30 days expense
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xxl:grid-cols-12 gap-6 mt-6">
        <div class="xl:col-span-12 4xl:col-span-6">
            <div class="card h-full rounded-lg border-0">
                <div class="card-body">
                    <div class="flex flex-wrap items-center justify-between">
                        <h6 class="text-lg mb-0">Estadística de ventas</h6>
                        <select class="form-select bg-white form-select-sm w-auto">
                            <option>Anual</option>
                            <option>Mensual</option>
                            <option>Semanal</option>
                            <option>Hoy</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <h6 class="mb-0">S/27,200</h6>
                        <span class="text-sm font-semibold rounded-full bg-success-100 text-success-600 border border-success-200 px-2 py-1.5 line-height-1 flex items-center gap-1">
                            10% <iconify-icon icon="bxs:up-arrow" class="text-xs"></iconify-icon>
                        </span>
                        <span class="text-xs font-medium">+ S/1400 Por día</span>
                    </div>
                    <div id="chart" class="pt-[28px] apexcharts-tooltip-style-1">
                        <!-- ⚡ Placeholder mientras carga el gráfico del dashboard -->
                        <div id="chart-loading" class="flex items-center justify-center h-64 text-gray-500 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                                <span class="text-sm font-medium">Cargando estadísticas del dashboard...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:col-span-12 2xl:col-span-9">
            <div class="card h-full border-0">
                <div class="card-body p-6">

                    <div class="mb-4">
                        <ul class="tab-style-gradient flex flex-wrap -mb-px text-sm font-medium text-center" id="default-tab" data-tabs-toggle="#default-tab-content" role="tablist">
                            <li class="" role="presentation">
                                <button class="py-2.5 px-4 border-t-2 font-semibold text-lg inline-flex items-center gap-3 text-neutral-600" id="registered-tab" data-tabs-target="#registered" type="button" role="tab" aria-controls="registered" aria-selected="false">
                                    Latest Registered
                                    <span class="text-white px-2 py-0.5 bg-neutral-600 rounded-full text-sm">20</span>
                                </button>
                            </li>
                            <li class="" role="presentation">
                                <button class="py-2.5 px-4 border-t-2 font-semibold text-lg inline-flex items-center gap-3 text-neutral-600 hover:text-gray-600 hover:border-gray-300" id="subscribe-tab" data-tabs-target="#subscribe" type="button" role="tab" aria-controls="subscribe" aria-selected="false">
                                    Latest Subscribe
                                    <span class="text-white px-2 py-0.5 bg-neutral-600 rounded-full text-sm">20</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div id="default-tab-content">
                        <div class="hidden" id="registered" role="tabpanel" aria-labelledby="registered-tab">
                            <div class="overflow-x-auto">
                                <table class="table bordered-table sm-table mb-0 table-auto">
                                    <thead>
                                        <tr>
                                            <th scope="col">Users </th>
                                            <th scope="col">Registered On</th>
                                            <th scope="col">Plan</th>
                                            <th scope="col" class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <img src="{{ asset('assets/images/users/user1.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 me-2 overflow-hidden">
                                                    <div class="grow">
                                                        <h6 class="text-base mb-0 font-medium">Dianne Russell</h6>
                                                        <span class="text-sm text-secondary-light font-medium">redaniel@gmail.com</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 Mar 2025</td>
                                            <td>Free</td>
                                            <td class="text-center">
                                                <span class="bg-success-100 text-success-600 px-6 py-1.5 rounded-full font-medium text-sm">Active</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <img src="{{ asset('assets/images/users/user2.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 me-2 overflow-hidden">
                                                    <div class="grow">
                                                        <h6 class="text-base mb-0 font-medium">Wade Warren</h6>
                                                        <span class="text-sm text-secondary-light font-medium">xterris@gmail.com</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 Mar 2025</td>
                                            <td>Basic</td>
                                            <td class="text-center">
                                                <span class="bg-success-100 text-success-600 px-6 py-1.5 rounded-full font-medium text-sm">Active</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <img src="{{ asset('assets/images/users/user3.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 me-2 overflow-hidden">
                                                    <div class="grow">
                                                        <h6 class="text-base mb-0 font-medium">Albert Flores</h6>
                                                        <span class="text-sm text-secondary-light font-medium">seannand@mail.ru</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 Mar 2025</td>
                                            <td>Standard</td>
                                            <td class="text-center">
                                                <span class="bg-success-100 text-success-600 px-6 py-1.5 rounded-full font-medium text-sm">Active</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <img src="{{ asset('assets/images/users/user4.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 me-2 overflow-hidden">
                                                    <div class="grow">
                                                        <h6 class="text-base mb-0 font-medium">Bessie Cooper </h6>
                                                        <span class="text-sm text-secondary-light font-medium">igerrin@gmail.com</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 Mar 2025</td>
                                            <td>Business</td>
                                            <td class="text-center">
                                                <span class="bg-success-100 text-success-600 px-6 py-1.5 rounded-full font-medium text-sm">Active</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <img src="{{ asset('assets/images/users/user5.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 me-2 overflow-hidden">
                                                    <div class="grow">
                                                        <h6 class="text-base mb-0 font-medium">Arlene McCoy</h6>
                                                        <span class="text-sm text-secondary-light font-medium">fellora@mail.ru</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 Mar 2025</td>
                                            <td>Enterprise </td>
                                            <td class="text-center">
                                                <span class="bg-success-100 text-success-600 px-6 py-1.5 rounded-full font-medium text-sm">Active</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="hidden" id="subscribe" role="tabpanel" aria-labelledby="subscribe-tab">
                            <div class="overflow-x-auto">
                                <table class="table bordered-table sm-table mb-0 table-auto">
                                    <thead>
                                        <tr>
                                            <th scope="col">Users Name </th>
                                            <th scope="col">Registered On</th>
                                            <th scope="col">Plan</th>
                                            <th scope="col" class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <img src="{{ asset('assets/images/users/user1.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 me-2 overflow-hidden">
                                                    <div class="grow">
                                                        <h6 class="text-base mb-0 font-medium">Dianne Russell</h6>
                                                        <span class="text-sm text-secondary-light font-medium">redaniel@gmail.com</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 Mar 2025</td>
                                            <td>Free</td>
                                            <td class="text-center">
                                                <span class="bg-success-100 text-success-600 px-6 py-1.5 rounded-full font-medium text-sm">Active</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <img src="{{ asset('assets/images/users/user2.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 me-2 overflow-hidden">
                                                    <div class="grow">
                                                        <h6 class="text-base mb-0 font-medium">Wade Warren</h6>
                                                        <span class="text-sm text-secondary-light font-medium">xterris@gmail.com</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 Mar 2025</td>
                                            <td>Basic</td>
                                            <td class="text-center">
                                                <span class="bg-success-100 text-success-600 px-6 py-1.5 rounded-full font-medium text-sm">Active</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <img src="{{ asset('assets/images/users/user3.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 me-2 overflow-hidden">
                                                    <div class="grow">
                                                        <h6 class="text-base mb-0 font-medium">Albert Flores</h6>
                                                        <span class="text-sm text-secondary-light font-medium">seannand@mail.ru</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 Mar 2025</td>
                                            <td>Standard</td>
                                            <td class="text-center">
                                                <span class="bg-success-100 text-success-600 px-6 py-1.5 rounded-full font-medium text-sm">Active</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <img src="{{ asset('assets/images/users/user4.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 me-2 overflow-hidden">
                                                    <div class="grow">
                                                        <h6 class="text-base mb-0 font-medium">Bessie Cooper </h6>
                                                        <span class="text-sm text-secondary-light font-medium">igerrin@gmail.com</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 Mar 2025</td>
                                            <td>Business</td>
                                            <td class="text-center">
                                                <span class="bg-success-100 text-success-600 px-6 py-1.5 rounded-full font-medium text-sm">Active</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <img src="{{ asset('assets/images/users/user5.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 me-2 overflow-hidden">
                                                    <div class="grow">
                                                        <h6 class="text-base mb-0 font-medium">Arlene McCoy</h6>
                                                        <span class="text-sm text-secondary-light font-medium">fellora@mail.ru</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>27 Mar 2025</td>
                                            <td>Enterprise </td>
                                            <td class="text-center">
                                                <span class="bg-success-100 text-success-600 px-6 py-1.5 rounded-full font-medium text-sm">Active</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="xl:col-span-6 2xl:col-span-3">
            <div class="card h-full border-0">
                <div class="card-body">
                    <div class="flex items-center flex-wrap gap-2 justify-between">
                        <h6 class="font-bold text-lg mb-0">Top Performer</h6>
                        <a href="javascript:void(0)" class="text-primary-600 hover-text-primary flex items-center gap-1">
                            View All
                            <iconify-icon icon="solar:alt-arrow-right-linear" class="icon"></iconify-icon>
                        </a>
                    </div>

                    <div class="mt-8">

                        <div class="flex items-center justify-between gap-2 mb-6">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('assets/images/users/user1.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 overflow-hidden">
                                <div class="grow">
                                    <h6 class="text-base mb-0 font-medium">Dianne Russell</h6>
                                    <span class="text-sm text-secondary-light font-medium">Agent ID: 36254</span>
                                </div>
                            </div>
                            <span class="text-neutral-600 text-base font-medium">$20</span>
                        </div>

                        <div class="flex items-center justify-between gap-2 mb-6">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('assets/images/users/user2.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 overflow-hidden">
                                <div class="grow">
                                    <h6 class="text-base mb-0 font-medium">Wade Warren</h6>
                                    <span class="text-sm text-secondary-light font-medium">Agent ID: 36254</span>
                                </div>
                            </div>
                            <span class="text-neutral-600 text-base font-medium">$20</span>
                        </div>

                        <div class="flex items-center justify-between gap-2 mb-6">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('assets/images/users/user3.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 overflow-hidden">
                                <div class="grow">
                                    <h6 class="text-base mb-0 font-medium">Albert Flores</h6>
                                    <span class="text-sm text-secondary-light font-medium">Agent ID: 36254</span>
                                </div>
                            </div>
                            <span class="text-neutral-600 text-base font-medium">$30</span>
                        </div>

                        <div class="flex items-center justify-between gap-2 mb-6">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('assets/images/users/user4.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 overflow-hidden">
                                <div class="grow">
                                    <h6 class="text-base mb-0 font-medium">Bessie Cooper</h6>
                                    <span class="text-sm text-secondary-light font-medium">Agent ID: 36254</span>
                                </div>
                            </div>
                            <span class="text-neutral-600 text-base font-medium">$40</span>
                        </div>

                        <div class="flex items-center justify-between gap-2 mb-6">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('assets/images/users/user5.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 overflow-hidden">
                                <div class="grow">
                                    <h6 class="text-base mb-0 font-medium">Arlene McCoy</h6>
                                    <span class="text-sm text-secondary-light font-medium">Agent ID: 36254</span>
                                </div>
                            </div>
                            <span class="text-neutral-600 text-base font-medium">$10</span>
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('assets/images/users/user1.png') }}" alt="" class="w-10 h-10 rounded-full shrink-0 overflow-hidden">
                                <div class="grow">
                                    <h6 class="text-base mb-0 font-medium">Arlene McCoy</h6>
                                    <span class="text-sm text-secondary-light font-medium">Agent ID: 36254</span>
                                </div>
                            </div>
                            <span class="text-neutral-600 text-base font-medium">$10</span>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

@endsection