@extends('layout.layout')
@php
    $title = 'Auditoría de cambios de inventario';
    $subTitle = 'Auditoría';
@endphp

@push('head')
    <title>Auditoría | Botica San Antonio</title>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        :root {
            --audit-primary: #4f46e5;
            --audit-success: #10b981;
            --audit-danger: #ef4444;
            --audit-warning: #f59e0b;
            --audit-bg: #f8fafc;
            --audit-card-bg: #ffffff;
            --audit-border: #e2e8f0;
            --audit-text-main: #1e293b;
            --audit-text-muted: #64748b;
        }

        .audit-page-container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            font-family: 'Inter', sans-serif;
        }

        
        .audit-filters-card {
            background: var(--audit-card-bg);
            border: 1px solid var(--audit-border);
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .filter-section-title {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chips-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
        }

        .chip-filter {
            padding: 0.4rem 1rem;
            border-radius: 10px;
            font-size: 0.8125rem;
            font-weight: 600;
            border: 1px solid var(--audit-border);
            background: white;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .chip-filter:hover {
            border-color: var(--audit-primary);
            color: var(--audit-primary);
            background: #f5f3ff;
        }

        .chip-filter.active {
            background: #eff6ff;
            color: #2563eb;
            border-color: #bfdbfe;
            box-shadow: none;
        }

        .search-input-wrapper {
            position: relative;
            flex: 1;
            min-width: 300px;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 0.65rem 1rem 0.65rem 2.5rem;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            font-size: 0.875rem;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .search-input-wrapper input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
        }

        
        .audit-table-card {
            background: white;
            border: 1px solid var(--audit-border);
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.05);
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .audit-table thead {
            background: #f1f5f9; 
            border-bottom: 2px solid #e2e8f0;
        }

        .audit-table th {
            padding: 1rem 1.5rem;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }

        .audit-table td {
            padding: 0.85rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        
        .event-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.4rem 0.8rem;
            border-radius: 10px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .event-created { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
        .event-updated { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
        .event-deleted { background: #fff1f2; color: #e11d48; border: 1px solid #ffe4e6; }

        
        .module-badge-producto {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
            padding: 0.35rem 0.75rem;
            border-radius: 10px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .module-badge-categoria {
            background: #f5f3ff;
            color: #6d28d9;
            border: 1px solid #ede9fe;
            padding: 0.35rem 0.75rem;
            border-radius: 10px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .module-item-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: #334155;
            margin-top: 0.25rem;
            display: block;
        }

        
        .comparison-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .mini-comparison {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #ffffff;
            padding: 0.3rem 0.75rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .mini-comparison .field {
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.65rem;
        }

        .mini-comparison .new-val {
            font-weight: 700;
            color: #2563eb;
        }

        .btn-details-circle {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            background: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }

        .btn-details-circle:hover {
            background: #2563eb;
            transform: scale(1.1);
            box-shadow: 0 6px 10px -1px rgba(59, 130, 246, 0.4);
        }

        
        .modal-premium-overlay {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(4px);
        }

        .modal-content-professional {
            background: white;
            border-radius: 24px;
            width: 100%;
            max-width: 900px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .modal-header-pro {
            padding: 1.5rem 2rem;
            background: white;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body-pro {
            padding: 2rem;
            max-height: 80vh;
            overflow-y: auto;
            background: #fcfdfe;
        }

        .audit-info-banner {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .banner-item {
            background: white;
            padding: 1.25rem;
            border-radius: 20px;
            border: 1px solid #eef2f6;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        .banner-label {
            font-size: 0.65rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .banner-value {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .comparison-grid-pro {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .comparison-card-pro {
            background: white;
            border-radius: 16px;
            border: 1px solid #f1f5f9;
            padding: 1.25rem;
            display: grid;
            grid-template-columns: 200px 1fr 1fr;
            gap: 1.5rem;
            align-items: center;
            transition: all 0.2s;
        }

        .comparison-card-pro:hover {
            border-color: #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .field-title-pro {
            font-size: 0.8rem;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
        }

        .value-pill-pro {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            border: 1px solid transparent;
        }

        .value-pill-old {
            background: #fff1f2;
            color: #be123c;
            border-color: #ffe4e6;
            text-decoration: line-through;
        }

        .value-pill-new {
            background: #f0fdf4;
            color: #15803d;
            border-color: #dcfce7;
            font-weight: 700;
        }

        .comparison-labels-pro {
            display: grid;
            grid-template-columns: 200px 1fr 1fr;
            gap: 1.5rem;
            padding: 0 1.25rem;
            margin-bottom: 0.75rem;
        }

        .comparison-labels-pro span {
            font-size: 0.7rem;
            font-weight: 800;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .pagination-container {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
        }

        .pagination-container nav > div:first-child {
            display: none !important; 
        }

        .pagination-container .relative.z-0 {
            display: flex;
            gap: 0.5rem;
            box-shadow: none !important;
            border: none !important;
        }

        .pagination-container .relative.z-0 > span,
        .pagination-container .relative.z-0 > a {
            border-radius: 12px !important;
            border: 1px solid #f0f0ff !important;
            background: #f5f3ff !important;
            color: #6366f1 !important;
            font-weight: 700;
            padding: 0.6rem 1rem !important;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
        }

        .pagination-container .relative.z-0 > span[aria-current="page"] > span {
            background: #6366f1 !important;
            color: white !important;
            border: none !important;
        }

        .pagination-container .relative.z-0 > span[aria-current="page"] {
            background: #6366f1 !important;
            color: white !important;
            border-color: #6366f1 !important;
        }

        .pagination-container .relative.z-0 > a:hover {
            background: #6366f1 !important;
            color: white !important;
            transform: translateY(-2px);
        }

        
        .change-pill-row {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .change-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #ffffff;
            border: 1px solid #f1f5f9;
            padding: 0.4rem 0.8rem;
            border-radius: 10px;
            font-size: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .change-field-label {
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.65rem;
            min-width: 80px;
        }

        .change-val-old {
            color: #ef4444;
            text-decoration: line-through;
            background: #fee2e2;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .change-val-new {
            color: #10b981;
            background: #dcfce7;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-weight: 700;
        }

        .change-val-only {
            color: #3b82f6;
            background: #eff6ff;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-weight: 700;
        }

        .arrow-divider {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        
        .skeleton-loader {
            background: linear-gradient(90deg, #f1f5f9 25%, #f8fafc 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 8px;
        }

        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .skeleton-row td {
            padding: 1rem 1.5rem;
        }

        .skeleton-bar {
            height: 14px;
            width: 100%;
            border-radius: 4px;
            background: #f1f5f9;
        }
    </style>
@endpush

@section('content')
<div class="audit-page-container">
    
    <div class="audit-filters-card">
        <form id="auditFiltersForm" method="GET" action="{{ route('admin.logs') }}">
            <input type="hidden" name="event" id="inputEvent" value="{{ request('event') }}">
            <input type="hidden" name="module" id="inputModule" value="{{ request('module') }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
                <div>
                    <div class="filter-section-title">
                        <iconify-icon icon="solar:filter-bold-duotone"></iconify-icon>
                        Filtrar por Evento
                    </div>
                    <div class="chips-container" id="eventChips">
                        <div class="chip-filter {{ !request('event') ? 'active' : '' }}" data-value="">
                            Todos
                        </div>
                        <div class="chip-filter {{ request('event') == 'created' ? 'active' : '' }}" data-value="created">
                            <iconify-icon icon="solar:add-circle-bold-duotone" style="color: #10b981;"></iconify-icon>
                            Creaciones
                        </div>
                        <div class="chip-filter {{ request('event') == 'updated' ? 'active' : '' }}" data-value="updated">
                            <iconify-icon icon="solar:pen-new-square-bold-duotone" style="color: #3b82f6;"></iconify-icon>
                            Actualizaciones
                        </div>
                        <div class="chip-filter {{ request('event') == 'deleted' ? 'active' : '' }}" data-value="deleted">
                            <iconify-icon icon="solar:trash-bin-trash-bold-duotone" style="color: #ef4444;"></iconify-icon>
                            Eliminaciones
                        </div>
                    </div>
                </div>

                <div>
                    <div class="filter-section-title">
                        <iconify-icon icon="solar:widget-bold-duotone"></iconify-icon>
                        Filtrar por Módulo
                    </div>
                    <div class="chips-container" id="moduleChips">
                        <div class="chip-filter {{ !request('module') ? 'active' : '' }}" data-value="">
                            Todos los módulos
                        </div>
                        <div class="chip-filter {{ request('module') == 'App\Models\Producto' ? 'active' : '' }}" data-value="App\Models\Producto">
                            <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                            Productos
                        </div>
                        <div class="chip-filter {{ request('module') == 'App\Models\Categoria' ? 'active' : '' }}" data-value="App\Models\Categoria">
                            <iconify-icon icon="solar:tag-bold-duotone"></iconify-icon>
                            Categorías
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 mt-4 pt-4 border-t border-slate-100">
                <div class="search-input-wrapper">
                    <iconify-icon icon="solar:magnifer-linear" class="search-icon"></iconify-icon>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre de producto o categoría...">
                </div>
                
                <div class="flex items-center gap-3">
                    <input type="date" name="fecha" value="{{ request('fecha') }}" 
                           max="{{ date('Y-m-d') }}"
                           class="chip-filter" style="height: 42px; padding: 0 1rem;">
                    
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 flex items-center gap-2">
                        <iconify-icon icon="solar:filter-bold" style="font-size: 1.1rem;"></iconify-icon>
                        Aplicar Filtros
                    </button>

                    @if(request()->anyFilled(['event', 'module', 'search', 'fecha']))
                        <a href="{{ route('admin.logs') }}" class="px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold text-sm hover:bg-slate-200 transition-all flex items-center gap-2">
                            <iconify-icon icon="solar:restart-bold"></iconify-icon>
                            Limpiar
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    
    <div class="audit-table-card">
        <table class="audit-table">
            <thead>
                <tr>
                    <th style="width: 180px;">Fecha y Hora</th>
                    <th style="width: 140px;">Evento</th>
                    <th style="width: 250px;">Módulo / Ítem</th>
                    <th style="width: 200px;">Usuario</th>
                    <th>Resumen Detallado de Cambios</th>
                </tr>
            </thead>
            <tbody id="auditSkeleton" style="display: none;">
                @for($i = 0; $i < 6; $i++)
                <tr class="skeleton-row">
                    <td><div class="skeleton-bar skeleton-loader" style="width: 120px; height: 18px;"></div></td>
                    <td><div class="skeleton-bar skeleton-loader" style="width: 100px; height: 24px; border-radius: 10px;"></div></td>
                    <td>
                        <div class="flex flex-col gap-2">
                            <div class="skeleton-bar skeleton-loader" style="width: 140px; height: 16px;"></div>
                            <div class="skeleton-bar skeleton-loader" style="width: 80px; height: 12px;"></div>
                        </div>
                    </td>
                    <td><div class="skeleton-bar skeleton-loader" style="width: 130px; height: 16px;"></div></td>
                    <td>
                        <div class="flex flex-col gap-2">
                            <div class="skeleton-bar skeleton-loader" style="height: 35px; border-radius: 10px;"></div>
                            <div class="skeleton-bar skeleton-loader" style="height: 35px; border-radius: 10px;"></div>
                        </div>
                    </td>
                </tr>
                @endfor
            </tbody>
            <tbody id="auditTableBody">
                @forelse($audits as $audit)
                    @php
                        $auditable = $audit->auditable;
                        $itemName = $auditable->nombre ?? ($auditable->razon_social ?? 'Elemento Eliminado');
                        $isProductCreation = ($audit->event === 'created' && $audit->auditable_type === 'App\Models\Producto');
                    @endphp
                    <tr>
                        <td>
                            <div class="flex flex-col">
                                <span class="text-slate-800 font-extrabold text-sm">{{ \Carbon\Carbon::parse($audit->created_at)->translatedFormat('d M, Y') }}</span>
                                <span class="text-[0.65rem] font-bold text-slate-400 uppercase tracking-tighter">{{ \Carbon\Carbon::parse($audit->created_at)->translatedFormat('h:i A') }}</span>
                            </div>
                        </td>
                        <td>
                            @php
                                $eventClasses = [
                                    'created' => 'event-created',
                                    'updated' => 'event-updated',
                                    'deleted' => 'event-deleted'
                                ];
                                $eventIcons = [
                                    'created' => 'solar:add-circle-bold-duotone',
                                    'updated' => 'solar:pen-new-square-bold-duotone',
                                    'deleted' => 'solar:trash-bin-trash-bold-duotone'
                                ];
                                $eventLabels = [
                                    'created' => 'Creación',
                                    'updated' => 'Actualización',
                                    'deleted' => 'Eliminación'
                                ];
                            @endphp
                            <div class="event-badge {{ $eventClasses[$audit->event] ?? 'bg-slate-100 text-slate-600' }}">
                                <iconify-icon icon="{{ $eventIcons[$audit->event] ?? 'solar:info-circle-bold' }}" style="font-size: 1rem;"></iconify-icon>
                                {{ $eventLabels[$audit->event] ?? $audit->event }}
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-col gap-1">
                                <div class="module-badge-{{ strtolower(class_basename($audit->auditable_type)) }}">
                                    <iconify-icon icon="{{ $audit->auditable_type === 'App\Models\Producto' ? 'solar:box-bold-duotone' : 'solar:tag-horizontal-bold-duotone' }}"></iconify-icon>
                                    {{ class_basename($audit->auditable_type) }}
                                </div>
                                <span class="text-xs font-bold text-slate-700 ml-1">{{ $itemName }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="text-xs font-bold text-slate-700">{{ $audit->user->name ?? 'Sistema' }}</span>
                        </td>
                        <td>
                            <div class="change-pill-row">
                                @php
                                    $changes = $audit->getModified();
                                    $displayCount = 0;
                                    $ignoreFieldsSummary = ['updated_at', 'id', 'user_id', 'auditable_id', 'auditable_type', 'created_at', 'deleted_at'];

                                    if ($isProductCreation) {
                                        $fieldsToProcess = array_filter(array_keys($changes), function($f) {
                                            return in_array($f, ['nombre', 'presentaciones']);
                                        });
                                    } else {
                                        $fieldsToProcess = array_keys($changes);
                                    }
                                @endphp
                                @forelse($fieldsToProcess as $field)
                                    @php $values = $changes[$field] ?? null; @endphp
                                    @if($values && !in_array($field, $ignoreFieldsSummary) && $displayCount < 3)
                                        <div class="change-item">
                                            <span class="change-field-label">{{ str_replace('_', ' ', $field) }}</span>
                                            
                                            @if($audit->event === 'updated')
                                                @php
                                                    $old = $values['old'] ?? '-';
                                                    $new = $values['new'] ?? '-';
                                                    $boolMap = ['1' => 'Activo', '0' => 'Inactivo', 'true' => 'Activo', 'false' => 'Inactivo'];
                                                    if(in_array($field, ['estado', 'activo', 'habilitado'])) {
                                                        $old = $boolMap[string_value($old)] ?? $old;
                                                        $new = $boolMap[string_value($new)] ?? $new;
                                                    }
                                                @endphp
                                                <span class="change-val-old">{{ Str::limit($old, 20) }}</span>
                                                <iconify-icon icon="solar:double-alt-arrow-right-bold-duotone" class="arrow-divider"></iconify-icon>
                                                <span class="change-val-new">{{ Str::limit($new, 20) }}</span>
                                            @else
                                                @php
                                                    $val = ($audit->event === 'created' ? ($values['new'] ?? '-') : ($values['old'] ?? '-'));
                                                    if(in_array($field, ['estado', 'activo', 'habilitado'])) {
                                                        $val = $boolMap[string_value($val)] ?? $val;
                                                    }
                                                @endphp
                                                <span class="change-val-only">{{ Str::limit($val, 40) }}</span>
                                            @endif
                                        </div>
                                        @php $displayCount++; @endphp
                                    @endif
                                @empty
                                    <span class="text-xs italic text-slate-400">Sin cambios relevantes</span>
                                @endforelse
                                
                                {{-- Solo mostrar el contador de cambios extras si NO es creación de producto --}}
                                @if(!$isProductCreation && count($changes) > $displayCount)
                                    <div class="px-3 py-1 bg-slate-50 border border-slate-100 rounded-lg text-[0.65rem] font-bold text-slate-500 inline-block w-max">
                                        +{{ count($changes) - $displayCount }} otros cambios realizados
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-20 text-center">
                            <iconify-icon icon="solar:document-text-bold-duotone" style="font-size: 4rem; color: #e2e8f0;"></iconify-icon>
                            <h3 class="text-lg font-bold text-slate-400 mt-4">No hay registros</h3>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    
    @if($audits->hasPages())
        <div class="pagination-container">
            {{ $audits->links() }}
        </div>
    @endif
</div>

@php
    function string_value($val) {
        if (is_bool($val)) return $val ? 'true' : 'false';
        return (string) $val;
    }
@endphp
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const eventChips = document.querySelectorAll('#eventChips .chip-filter');
        const moduleChips = document.querySelectorAll('#moduleChips .chip-filter');
        const inputEvent = document.getElementById('inputEvent');
        const inputModule = document.getElementById('inputModule');
        const filtersForm = document.getElementById('auditFiltersForm');
        const searchInput = document.querySelector('input[name="search"]');

        function handleChipClick(chips, hiddenInput) {
            chips.forEach(chip => {
                chip.addEventListener('click', function() {
                    chips.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    hiddenInput.value = this.dataset.value;
                    showSkeletonAndSubmit();
                });
            });
        }

        function showSkeletonAndSubmit() {
            const tbody = document.getElementById('auditTableBody');
            const skeleton = document.getElementById('auditSkeleton');
            if (tbody && skeleton) {
                tbody.style.display = 'none';
                skeleton.style.display = 'table-row-group';
            }
            filtersForm.submit();
        }

        handleChipClick(eventChips, inputEvent);
        handleChipClick(moduleChips, inputModule);

        let searchTimeout = null;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                showSkeletonAndSubmit();
            }, 600);
        });

        filtersForm.addEventListener('submit', function(e) {
            const tbody = document.getElementById('auditTableBody');
            const skeleton = document.getElementById('auditSkeleton');
            if (tbody && skeleton) {
                tbody.style.display = 'none';
                skeleton.style.display = 'table-row-group';
            }
        });

        const paginationInfo = document.querySelector('.pagination-container nav div p');
        if (paginationInfo) {
            let text = paginationInfo.innerHTML;
            text = text.replace('Showing', 'Mostrando')
                       .replace('to', 'al')
                       .replace('of', 'de')
                       .replace('results', 'registros');
            paginationInfo.innerHTML = text;
        }

        const prevButton = document.querySelector('.pagination-container nav a[rel="prev"]');
        if (prevButton) prevButton.innerHTML = 'Anterior';
        
        const nextButton = document.querySelector('.pagination-container nav a[rel="next"]');
        if (nextButton) nextButton.innerHTML = 'Siguiente';
    });

    function class_basename(path) {
        if (!path) return '';
        return path.split('\\').pop();
    }
</script>
@endpush