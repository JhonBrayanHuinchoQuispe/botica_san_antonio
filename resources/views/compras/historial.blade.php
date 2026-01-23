@extends('layout.layout')
@php
    $title = 'Historial de Entradas';
    $subTitle = 'Registro completo de entradas de mercadería';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/compras/historial.js') . '?v=' . time() . '"></script>
    ';
@endphp

@push('head')
    <title>Historial de Entradas de Mercadería</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/compras/compras.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos Premium - Réplica Exacta de Ventas */
        .reportes-metrics-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .reportes-metric-card {
            background: white;
            padding: 1rem;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .reportes-metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
        }

        .reportes-metric-icon-small {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .reportes-metric-content { flex: 1; }

        .reportes-metric-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 0.15rem;
        }

        .reportes-metric-value-medium {
            font-size: 1.15rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
        }

        .reportes-metric-comparison {
            font-size: 0.65rem;
            margin-top: 0.15rem;
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 600;
        }

        /* Colores de Tarjetas */
        .reportes-metric-card.red { border-left: 3px solid #ef4444; }
        .reportes-metric-card.red .reportes-metric-icon-small { background: rgba(239, 68, 68, 0.1); color: #b91c1c; }
        .reportes-metric-card.red .reportes-metric-comparison { color: #b91c1c; }

        .reportes-metric-card.orange { border-left: 3px solid #f59e0b; }
        .reportes-metric-card.orange .reportes-metric-icon-small { background: rgba(245, 158, 11, 0.1); color: #b45309; }
        .reportes-metric-card.orange .reportes-metric-comparison { color: #b45309; }

        .reportes-metric-card.blue { border-left: 3px solid #3b82f6; }
        .reportes-metric-card.blue .reportes-metric-icon-small { background: rgba(59, 130, 246, 0.1); color: #1d4ed8; }
        .reportes-metric-card.blue .reportes-metric-comparison { color: #1d4ed8; }

        .reportes-metric-card.green { border-left: 3px solid #10b981; }
        .reportes-metric-card.green .reportes-metric-icon-small { background: rgba(16, 185, 129, 0.1); color: #047857; }
        .reportes-metric-card.green .reportes-metric-comparison { color: #047857; }

        /* Estilos de Tabla */
        .historial-table-container-improved {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .historial-table-header-improved {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .historial-table thead th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            border-bottom: 2px solid #e2e8f0 !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 0.72rem !important;
            letter-spacing: 0.05em !important;
            padding: 1rem 0.75rem !important;
            text-align: center !important;
        }

        .historial-table thead th:first-child {
            text-align: left !important;
            padding-left: 1.5rem !important;
        }

        .historial-row td {
            padding: 1.25rem 1rem !important;
            border-bottom: 1px solid #f1f5f9 !important;
            vertical-align: middle !important;
            text-align: center;
            color: #64748b;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .historial-row td:first-child {
            text-align: left;
            padding-left: 1.5rem !important;
        }

        .historial-row:hover {
            background-color: #f8fafc !important;
        }

        /* UI Elements */
        .historial-input-clean {
            padding: 0.5rem 0.75rem !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px !important;
            background: white !important;
            font-size: 0.85rem !important;
            transition: all 0.2s ease !important;
            color: #1e293b !important;
        }

        .historial-input-clean:focus {
            outline: none !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        .historial-select-clean {
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            appearance: none;
            cursor: pointer;
        }

        .historial-btn-limpiar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.5rem 1rem;
            background: #f1f5f9;
            color: #475569;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            white-space: nowrap;
        }

        .historial-btn-limpiar:hover { background: #e2e8f0; color: #1e293b; }

        .historial-inline-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: capitalize;
            margin-right: 0.5rem;
        }

        /* Badges Suaves */
        .historial-badge-soft {
            padding: 0.4rem 0.8rem;
            border-radius: 99px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid transparent;
        }

        .badge-green-soft { background: rgba(16, 185, 129, 0.08); color: #10b981; border-color: rgba(16, 185, 129, 0.15); }
        .badge-blue-soft { background: rgba(59, 130, 246, 0.08); color: #3b82f6; border-color: rgba(59, 130, 246, 0.15); }

        /* Paginación */
        .pagination-btn {
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.25rem;
        }

        .pagination-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
        .pagination-btn-current { background: #ef4444 !important; border-color: #ef4444 !important; color: white !important; }
        .pagination-btn-disabled { color: #cbd5e1; background: #f8fafc; border: 1px solid #f1f5f9; cursor: not-allowed; }

        /* Estilo Tachado */
        .total-tachado {
            text-decoration: line-through;
            color: #94a3b8;
            font-size: 0.75rem;
            margin-right: 4px;
        }

        /* Skeleton Loading */
        @keyframes shimmer {
            0% { background-position: -468px 0; }
            100% { background-position: 468px 0; }
        }
        .skeleton-loading {
            background: #f6f7f8;
            background-image: linear-gradient(to right, #f6f7f8 0%, #edeef1 20%, #f6f7f8 40%, #f6f7f8 100%);
            background-repeat: no-repeat;
            background-size: 800px 104px;
            display: inline-block;
            position: relative;
            animation: shimmer 1.2s linear infinite forwards;
            border-radius: 4px;
            height: 12px;
            width: 100%;
        }
    </style>
@endpush

@section('content')

<div class="grid grid-cols-12 gap-4">
    
    <div class="col-span-12 mb-1">
        <div class="flex flex-col gap-0.5">
            <h1 class="text-xl font-bold text-slate-800">{{ $title }}</h1>
            <p class="text-xs text-slate-500">{{ $subTitle }}</p>
        </div>
    </div>

    
    <div class="col-span-12">
        <div class="reportes-metrics-grid-4">
            <div class="reportes-metric-card red">
                <div class="reportes-metric-icon-small"><iconify-icon icon="solar:calendar-add-bold-duotone"></iconify-icon></div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Entradas Hoy</p>
                    <h3 class="reportes-metric-value-medium">{{ $estadisticas['entradas_hoy'] }}</h3>
                    <p class="reportes-metric-comparison"><iconify-icon icon="solar:trend-up-bold"></iconify-icon> Hoy vs Ayer</p>
                </div>
            </div>
            <div class="reportes-metric-card orange">
                <div class="reportes-metric-icon-small"><iconify-icon icon="solar:calendar-mark-bold-duotone"></iconify-icon></div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Este Mes</p>
                    <h3 class="reportes-metric-value-medium">{{ $estadisticas['entradas_mes'] }}</h3>
                    <p class="reportes-metric-comparison">Total del mes</p>
                </div>
            </div>
            <div class="reportes-metric-card blue">
                <div class="reportes-metric-icon-small"><iconify-icon icon="solar:box-bold-duotone"></iconify-icon></div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Productos</p>
                    <h3 class="reportes-metric-value-medium">{{ $estadisticas['productos_ingresados_mes'] }}</h3>
                    <p class="reportes-metric-comparison">Unidades ingresadas</p>
                </div>
            </div>
            <div class="reportes-metric-card green">
                <div class="reportes-metric-icon-small"><iconify-icon icon="solar:wallet-money-bold-duotone"></iconify-icon></div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Valor Total</p>
                    <h3 class="reportes-metric-value-medium">S/ {{ number_format($estadisticas['valor_total_mes'], 2) }}</h3>
                    <p class="reportes-metric-comparison">Inversión mensual</p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-span-12">
        <div class="historial-table-container-improved">
            <div class="historial-table-header-improved">
                <div class="flex flex-wrap items-center gap-4">
                    <form id="filtrosForm" class="flex flex-wrap items-center gap-4 flex-1">
                        <input type="search" name="search" value="{{ request('search') }}" class="historial-input-clean" placeholder="Buscar entradas..." style="min-width: 220px; flex: 1.5;">
                        
                        <div class="flex items-center">
                            <span class="historial-inline-label">Desde</span>
                            <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="historial-input-clean" id="fechaDesde" style="width: 130px; padding: 0.4rem !important;">
                        </div>

                        <div class="flex items-center">
                            <span class="historial-inline-label">Hasta</span>
                            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="historial-input-clean" id="fechaHasta" style="width: 130px; padding: 0.4rem !important;">
                        </div>

                        <div class="flex items-center">
                            <span class="historial-inline-label">Usuario</span>
                            <div class="relative">
                                <select class="historial-select-clean" name="usuario_id" id="filtroUsuario" style="width: 140px; padding-right: 2rem;">
                                    <option value="">Todos</option>
                                    @foreach($usuarios as $usuario)
                                        <option value="{{ $usuario->id }}" {{ request('usuario_id') == $usuario->id ? 'selected' : '' }}>{{ $usuario->name }}</option>
                                    @endforeach
                                </select>
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"><iconify-icon icon="solar:alt-arrow-down-bold"></iconify-icon></div>
                            </div>
                        </div>

                        <button type="button" onclick="limpiarFiltrosEntradas()" class="historial-btn-limpiar">
                            <iconify-icon icon="solar:eraser-bold-duotone"></iconify-icon>
                            Limpiar Filtros
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="overflow-x-auto" id="tablaContenedor">
                @include('compras.partials.tabla_historial')
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function limpiarFiltrosEntradas() {
        const form = document.getElementById('filtrosForm');
        form.querySelectorAll('input').forEach(i => i.value = '');
        form.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
        actualizarTabla();
    }

    function actualizarTabla(url = null) {
        const form = document.getElementById('filtrosForm');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        const fetchUrl = url || `{{ route('compras.historial') }}?${params.toString()}`;
        
        // Mostrar Skeleton
        document.getElementById('tablaContenedor').innerHTML = `
            <div class="p-8">
                ${Array(5).fill(0).map(() => `<div class="skeleton-loading mb-4" style="height: 60px;"></div>`).join('')}
            </div>
        `;

        fetch(fetchUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            document.getElementById('tablaContenedor').innerHTML = html;
            // Actualizar URL del navegador sin recargar
            window.history.pushState({}, '', fetchUrl);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const filtrosForm = document.getElementById('filtrosForm');
        const searchInput = filtrosForm.querySelector('input[name="search"]');
        
        // Búsqueda en tiempo real con debounce
        let timeout = null;
        searchInput.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(actualizarTabla, 500);
        });

        // Cambios en fechas y usuario
        filtrosForm.querySelectorAll('input[type="date"], select').forEach(el => {
            el.addEventListener('change', () => actualizarTabla());
        });

        // Manejar clics en paginación
        document.getElementById('tablaContenedor').addEventListener('click', (e) => {
            const link = e.target.closest('.pagination-btn');
            if (link && link.href) {
                e.preventDefault();
                actualizarTabla(link.href);
            }
        });

        // Validaciones de fecha
        const fDesde = document.getElementById('fechaDesde');
        const fHasta = document.getElementById('fechaHasta');
        if (fDesde && fHasta) {
            fDesde.addEventListener('change', () => {
                if (fHasta.value && fDesde.value > fHasta.value) {
                    Swal.fire({ icon: 'warning', title: 'Fecha inválida', text: 'Desde no puede ser mayor que Hasta', confirmButtonColor: '#ef4444' });
                    fDesde.value = '';
                }
            });
            fHasta.addEventListener('change', () => {
                if (fDesde.value && fHasta.value < fDesde.value) {
                    Swal.fire({ icon: 'warning', title: 'Fecha inválida', text: 'Hasta no puede ser menor que Desde', confirmButtonColor: '#ef4444' });
                    fHasta.value = '';
                }
            });
        }
    });
</script>
@endpush
