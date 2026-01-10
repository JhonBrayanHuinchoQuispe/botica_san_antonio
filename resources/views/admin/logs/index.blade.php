@extends('layout.layout')
@php
    $title = 'Auditoría del Sistema';
    $subTitle = 'Registro completo de cambios en Productos, Categorías y Presentaciones';
@endphp

<head>
    <title>Auditoría del Sistema</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

@push('head')
<style>
/* Contenedor principal */
.audit-container {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    margin: 20px 0;
}

/* Header de auditoría - Color rojo suave */
.audit-header {
    background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
    padding: 28px 32px;
    color: white;
}

.audit-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
}

/* Filtros modernos */
.audit-filters {
    padding: 24px 32px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 6px;
}

.filter-select, .filter-input {
    padding: 10px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.9375rem;
    color: #334155;
    background: white;
    transition: all 0.2s ease;
    cursor: pointer;
}

.filter-select:hover, .filter-input:hover {
    border-color: #cbd5e1;
}

.filter-select:focus, .filter-input:focus {
    outline: none;
    border-color: #f87171;
    box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.1);
}

/* Tabla de auditoría */
.audit-table-wrapper {
    padding: 0;
    position: relative;
}

.audit-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.audit-table thead {
    background: #f1f5f9;
}

.audit-table thead th {
    padding: 16px 20px;
    text-align: left;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #e2e8f0;
}

.audit-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
}

.audit-table tbody td {
    padding: 16px 20px;
    font-size: 0.9375rem;
    color: #334155;
}

/* Badges modernos */
.audit-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
}

/* Colores de eventos actualizados */
.badge-created {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border: 1px solid #93c5fd;
}

.badge-updated {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.badge-deleted {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.badge-disabled {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    border: 1px solid #fcd34d;
}

/* Badge de módulo con colores diferentes */
.badge-producto {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    border: 1px solid #fcd34d;
    font-weight: 700;
}

.badge-categoria {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    color: #3730a3;
    border: 1px solid #a5b4fc;
    font-weight: 700;
}

.badge-presentacion {
    background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
    color: #831843;
    border: 1px solid #f9a8d4;
    font-weight: 700;
}

/* Información de usuario sin avatar */
.user-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.user-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9375rem;
}

.user-email {
    font-size: 0.8125rem;
    color: #64748b;
}

/* Fecha y hora */
.datetime-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.date-text {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9375rem;
}

.time-text {
    font-size: 0.8125rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Detalles del cambio */
.change-details {
    max-width: 350px;
    font-size: 0.875rem;
}

.change-item {
    margin-bottom: 8px;
    padding: 8px;
    background: #f8fafc;
    border-radius: 6px;
    border-left: 3px solid #cbd5e1;
}

.change-field {
    font-weight: 700;
    color: #475569;
    margin-bottom: 4px;
}

.change-values {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.value-old {
    color: #dc2626;
    background: #fee2e2;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.8125rem;
}

.value-new {
    color: #16a34a;
    background: #dcfce7;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.8125rem;
    font-weight: 600;
}

.value-arrow {
    color: #94a3b8;
}

/* Botón de acción */
.btn-view {
    padding: 8px 16px;
    background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-view:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(248, 113, 113, 0.3);
}

/* Estado vacío */
.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 16px;
}

.empty-text {
    font-size: 1.125rem;
    color: #64748b;
    font-weight: 500;
}

/* Paginación */
.pagination-wrapper {
    padding: 20px 0;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}

.pagination-wrapper nav {
    display: flex;
    gap: 4px;
}

.pagination-wrapper .pagination {
    display: flex;
    gap: 4px;
    list-style: none;
    margin: 0;
    padding: 0;
}

.pagination-wrapper .page-item {
    display: inline-block;
}

.pagination-wrapper .page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    color: #374151;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination-wrapper .page-link:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.pagination-wrapper .page-item.active .page-link {
    background: #ef4444;
    border-color: #ef4444;
    color: white;
}

.pagination-wrapper .page-item.disabled .page-link {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

/* Información del módulo con nombre */
.module-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.module-name {
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 500;
}

/* Modal personalizado */
.modal-audit-detail {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal-audit-detail.show {
    display: flex;
}

.modal-audit-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.modal-audit-header {
    background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
    color: white;
    padding: 20px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-audit-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: all 0.2s ease;
}

.modal-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.modal-audit-body {
    padding: 28px;
    max-height: calc(90vh - 100px);
    overflow-y: auto;
}

.modal-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.modal-info-item {
    background: white;
    padding: 16px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.modal-info-label {
    font-size: 0.6875rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 6px;
}

.modal-info-value {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1e293b;
}

.modal-item-name {
    margin-bottom: 20px;
    padding: 16px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 10px;
    border-left: 4px solid #f59e0b;
}

.modal-item-name strong {
    color: #92400e;
    font-weight: 700;
}

.modal-item-name span {
    color: #78350f;
    font-weight: 500;
}

.modal-changes-section {
    margin-top: 20px;
}

.modal-changes-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.modal-changes-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    background: white;
}

.modal-changes-table thead {
    background: #f8fafc;
}

.modal-changes-table thead th {
    padding: 12px 16px;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #e2e8f0;
}

.modal-changes-table thead th:nth-child(1) {
    width: 30%;
}

.modal-changes-table thead th:nth-child(2) {
    width: 35%;
}

.modal-changes-table thead th:nth-child(3) {
    width: 35%;
}

.modal-changes-table tbody td {
    padding: 12px 16px;
    font-size: 0.875rem;
    border-top: 1px solid #f1f5f9;
    vertical-align: middle;
}

.modal-changes-table tbody tr:hover {
    background: #fafbfc;
}

.modal-changes-table .field-name {
    font-weight: 600;
    color: #475569;
}

.modal-changes-table .value-old {
    color: #dc2626;
    background: #fee2e2;
    padding: 6px 12px;
    border-radius: 6px;
    display: inline-block;
    font-weight: 500;
}

.modal-changes-table .value-new {
    color: #16a34a;
    background: #dcfce7;
    padding: 6px 12px;
    border-radius: 6px;
    display: inline-block;
    font-weight: 600;
}

.modal-changes-table tr.highlight-change {
    background: #fffbeb;
}

.modal-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #64748b;
    font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
    .audit-header {
        padding: 20px;
    }
    
    .audit-filters {
        padding: 20px;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .audit-table thead {
        display: none;
    }
    
    .audit-table tbody tr {
        display: block;
        margin-bottom: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
    }
    
    .audit-table tbody td {
        display: block;
        padding: 8px 0;
        border: none;
    }
    
    .audit-table tbody td::before {
        content: attr(data-label);
        font-weight: 700;
        color: #475569;
        display: block;
        margin-bottom: 4px;
        font-size: 0.8125rem;
    }
    
    .modal-info-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-audit-content {
        width: 95%;
        margin: 20px;
    }
}
</style>
@endpush

@section('content')
<div class="audit-container">
    <!-- Header -->
    <div class="audit-header">
        <h2>
            <i class="fas fa-clipboard-list"></i>
            Registro completo de cambios en Productos, Categorías y Presentaciones
        </h2>
    </div>

    <!-- Filtros -->
    <div class="audit-filters">
        <form method="GET" action="{{ route('admin.logs') }}" id="filtrosForm">
            <div class="filters-grid">
                <div class="filter-group" style="position: relative;">
                    <label class="filter-label">
                        <i class="fas fa-search"></i>
                        Buscar Producto
                    </label>
                    <div style="position: relative;">
                        <input type="text" class="filter-input" name="search" id="filtroSearch" 
                               placeholder="Buscar por nombre de producto..." 
                               value="{{ request('search') }}"
                               style="cursor: text; padding-right: 35px;">
                        @if(request('search'))
                            <button type="button" 
                                    id="clearSearch" 
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 20px; padding: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;"
                                    title="Limpiar búsqueda">
                                ×
                            </button>
                        @else
                            <button type="button" 
                                    id="clearSearch" 
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 20px; padding: 0; width: 20px; height: 20px; display: none; align-items: center; justify-content: center;"
                                    title="Limpiar búsqueda">
                                ×
                            </button>
                        @endif
                    </div>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-filter"></i>
                        Tipo de Evento
                    </label>
                    <select class="filter-select auto-submit" name="event" id="filtroEvento">
                        <option value="">Todos los eventos</option>
                        <option value="created" {{ request('event') == 'created' ? 'selected' : '' }}>Creación</option>
                        <option value="updated" {{ request('event') == 'updated' ? 'selected' : '' }}>Actualización</option>
                        <option value="deleted" {{ request('event') == 'deleted' ? 'selected' : '' }}>Eliminación</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-cube"></i>
                        Módulo
                    </label>
                    <select class="filter-select auto-submit" name="module" id="filtroModulo">
                        <option value="">Todos los módulos</option>
                        <option value="App\Models\Producto" {{ request('module') == 'App\Models\Producto' ? 'selected' : '' }}>Productos</option>
                        <option value="App\Models\Categoria" {{ request('module') == 'App\Models\Categoria' ? 'selected' : '' }}>Categorías</option>
                        <option value="App\Models\Presentacion" {{ request('module') == 'App\Models\Presentacion' ? 'selected' : '' }}>Presentaciones</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-user"></i>
                        Usuario
                    </label>
                    <select class="filter-select auto-submit" name="user_id" id="filtroUsuario">
                        <option value="">Todos los usuarios</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" {{ request('user_id') == $usuario->id ? 'selected' : '' }}>
                                {{ $usuario->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="fas fa-calendar"></i>
                        Fecha
                    </label>
                    <input type="date" class="filter-input auto-submit" name="fecha" value="{{ request('fecha') }}">
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla -->
    <div class="audit-table-wrapper">
        <table class="audit-table">
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Evento</th>
                    <th>Módulo</th>
                    <th>Usuario</th>
                    <th>Detalles del Cambio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($audits as $audit)
                    <tr>
                        <td data-label="Fecha y Hora">
                            <div class="datetime-info">
                                <span class="date-text">{{ $audit->created_at->format('d/m/Y') }}</span>
                                <span class="time-text">
                                    <i class="far fa-clock"></i>
                                    {{ $audit->created_at->format('g:i A') }}
                                </span>
                            </div>
                        </td>
                        <td data-label="Evento">
                            @php
                                $changes = $audit->getModified();
                                $isStatusChange = isset($changes['estado']) || isset($changes['activo']);
                            @endphp
                            
                            @if($audit->event == 'created')
                                <span class="audit-badge badge-created">
                                    <i class="fas fa-plus-circle"></i>
                                    Creado
                                </span>
                            @elseif($audit->event == 'updated')
                                @if($isStatusChange)
                                    <span class="audit-badge badge-disabled">
                                        <i class="fas fa-toggle-on"></i>
                                        Estado
                                    </span>
                                @else
                                    <span class="audit-badge badge-updated">
                                        <i class="fas fa-edit"></i>
                                        Actualizado
                                    </span>
                                @endif
                            @elseif($audit->event == 'deleted')
                                <span class="audit-badge badge-deleted">
                                    <i class="fas fa-trash-alt"></i>
                                    Eliminado
                                </span>
                            @endif
                        </td>
                        <td data-label="Módulo">
                            @php
                                $moduleName = class_basename($audit->auditable_type);
                                $moduleIcon = 'fas fa-box';
                                $badgeClass = 'badge-producto';
                                
                                if($moduleName == 'Producto') {
                                    $moduleIcon = 'fas fa-pills';
                                    $badgeClass = 'badge-producto';
                                } elseif($moduleName == 'Categoria') {
                                    $moduleIcon = 'fas fa-tags';
                                    $badgeClass = 'badge-categoria';
                                } elseif($moduleName == 'Presentacion') {
                                    $moduleIcon = 'fas fa-cube';
                                    $badgeClass = 'badge-presentacion';
                                }
                                
                                // Obtener nombre del item
                                $itemName = '';
                                if($audit->auditable) {
                                    if($moduleName == 'Producto' && isset($audit->auditable->nombre)) {
                                        $itemName = $audit->auditable->nombre;
                                        if(isset($audit->auditable->concentracion)) {
                                            $itemName .= ' ' . $audit->auditable->concentracion;
                                        }
                                    } elseif(isset($audit->auditable->nombre)) {
                                        $itemName = $audit->auditable->nombre;
                                    }
                                }
                            @endphp
                            <div class="module-info">
                                <span class="audit-badge {{ $badgeClass }}">
                                    <i class="{{ $moduleIcon }}"></i>
                                    {{ $moduleName }}
                                </span>
                                @if($itemName)
                                    <span class="module-name">{{ Str::limit($itemName, 40) }}</span>
                                @endif
                            </div>
                        </td>
                        <td data-label="Usuario">
                            @if($audit->user)
                                <div class="user-info">
                                    <span class="user-name">{{ $audit->user->name }}</span>
                                    <span class="user-email">{{ $audit->user->email }}</span>
                                </div>
                            @else
                                <span class="audit-badge" style="background: #f1f5f9; color: #64748b;">
                                    <i class="fas fa-robot"></i>
                                    Sistema
                                </span>
                            @endif
                        </td>
                        <td data-label="Detalles">
                            <div class="change-details">
                                @php
                                    $changes = $audit->getModified();
                                    $displayCount = min(count($changes), 2);
                                @endphp
                                 
                                @if(count($changes) > 0)
                                    @foreach(array_slice($changes, 0, $displayCount, true) as $field => $values)
                                        <div class="change-item">
                                            <div class="change-field">
                                                @if($field == 'estado' || $field == 'activo')
                                                    Estado
                                                @else
                                                    {{ ucfirst($field) }}
                                                @endif
                                            </div>
                                            <div class="change-values">
                                                @if($field == 'estado' || $field == 'activo')
                                                    <span class="value-old">{{ $values['old'] == 1 ? 'Activo' : 'Desactivado' }}</span>
                                                    <span class="value-arrow">→</span>
                                                    <span class="value-new">{{ $values['new'] == 1 ? 'Activo' : 'Desactivado' }}</span>
                                                @else
                                                    <span class="value-old">{{ Str::limit($values['old'] ?? '-', 20) }}</span>
                                                    <span class="value-arrow">→</span>
                                                    <span class="value-new">{{ Str::limit($values['new'] ?? '-', 20) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    @if(count($changes) > 2)
                                        <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">
                                            +{{ count($changes) - 2 }} más
                                        </div>
                                    @endif
                                @else
                                    <span style="color: #94a3b8; font-style: italic; font-size: 0.875rem;">Sin cambios</span>
                                @endif
                            </div>
                        </td>
                        <td data-label="Acciones">
                            <button type="button" 
                                    class="btn-view btn-ver-detalles"
                                    data-old="{{ json_encode($audit->old_values) }}"
                                    data-new="{{ json_encode($audit->new_values) }}"
                                    data-event="{{ $audit->event }}"
                                    data-model="{{ class_basename($audit->auditable_type) }}"
                                    data-id="{{ $audit->auditable_id }}"
                                    data-user="{{ $audit->user ? $audit->user->name : 'Sistema' }}"
                                    data-date="{{ $audit->created_at->format('d/m/Y H:i:s') }}"
                                    data-item-name="{{ $itemName ?? '' }}">
                                <i class="fas fa-eye"></i>
                                Ver Detalles
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <p class="empty-text">No hay registros de auditoría disponibles</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($audits->hasPages())
        <div class="pagination-wrapper">
            {{ $audits->links() }}
        </div>
    @endif
</div>

<!-- Modal de Detalles de Auditoría -->
<div class="modal-audit-detail" id="modalAuditDetail">
    <div class="modal-audit-content">
        <div class="modal-audit-header">
            <h3 style="color: white;">
                <i class="fas fa-file-alt"></i>
                Detalle Completo de Auditoría
            </h3>
            <button class="modal-close-btn" onclick="closeAuditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-audit-body" id="modalAuditBody">
            <!-- El contenido se cargará dinámicamente -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Función para cerrar el modal
function closeAuditModal() {
    const modal = document.getElementById('modalAuditDetail');
    modal.classList.remove('show');
}

// Cerrar modal al hacer clic fuera de él
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modalAuditDetail');
    if (e.target === modal) {
        closeAuditModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Filtros en tiempo real
    const autoSubmitElements = document.querySelectorAll('.auto-submit');
    autoSubmitElements.forEach(element => {
        element.addEventListener('change', function() {
            document.getElementById('filtrosForm').submit();
        });
    });
    
    // Buscador con Enter y botón limpiar
    const searchInput = document.getElementById('filtroSearch');
    const clearSearchBtn = document.getElementById('clearSearch');
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('filtrosForm').submit();
            }
        });
        
        // Mostrar/ocultar botón X según contenido
        searchInput.addEventListener('input', function() {
            if (clearSearchBtn) {
                clearSearchBtn.style.display = this.value ? 'flex' : 'none';
            }
        });
    }
    
    // Limpiar búsqueda
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            document.getElementById('filtrosForm').submit();
        });
    }
    
    // Modal de detalles
    const botonesVer = document.querySelectorAll('.btn-ver-detalles');
    
    botonesVer.forEach(btn => {
        btn.addEventListener('click', function() {
            const oldValues = JSON.parse(this.getAttribute('data-old') || '{}');
            const newValues = JSON.parse(this.getAttribute('data-new') || '{}');
            const event = this.getAttribute('data-event');
            const model = this.getAttribute('data-model');
            const id = this.getAttribute('data-id');
            const user = this.getAttribute('data-user');
            const date = this.getAttribute('data-date');
            const itemName = this.getAttribute('data-item-name');
            
            // Traducir evento
            const eventText = {
                'created': 'Creación',
                'updated': 'Actualización',
                'deleted': 'Eliminación'
            }[event] || event;
            
            // Icono del módulo
            let moduleIcon = 'fas fa-box';
            if(model == 'Producto') moduleIcon = 'fas fa-pills';
            else if(model == 'Categoria') moduleIcon = 'fas fa-tags';
            else if(model == 'Presentacion') moduleIcon = 'fas fa-cube';
            
            // Construir contenido del modal
            let contentHtml = `
                <div class="modal-info-grid">
                    <div class="modal-info-item">
                        <span class="modal-info-label"><i class="fas fa-user"></i> Usuario</span>
                        <span class="modal-info-value">${user}</span>
                    </div>
                    <div class="modal-info-item">
                        <span class="modal-info-label"><i class="fas fa-clock"></i> Fecha y Hora</span>
                        <span class="modal-info-value">${date}</span>
                    </div>
                    <div class="modal-info-item">
                        <span class="modal-info-label"><i class="fas fa-cube"></i> Módulo</span>
                        <span class="modal-info-value"><i class="${moduleIcon}"></i> ${model}</span>
                    </div>
                    <div class="modal-info-item">
                        <span class="modal-info-label"><i class="fas fa-bolt"></i> Tipo de Evento</span>
                        <span class="modal-info-value">${eventText}</span>
                    </div>
                </div>
            `;
            
            if (itemName) {
                contentHtml += `
                    <div class="modal-item-name">
                        <strong>Nombre:</strong> <span>${itemName}</span>
                    </div>
                `;
            }
            
            contentHtml += `
                <div class="modal-changes-section">
                    <div class="modal-changes-title">
                        <i class="fas fa-list-ul"></i> Detalles de los Cambios
                    </div>
                    <table class="modal-changes-table">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Valor Anterior</th>
                                <th>Valor Nuevo</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            const allKeys = new Set([...Object.keys(oldValues), ...Object.keys(newValues)]);
            
            if (allKeys.size === 0) {
                contentHtml += `
                    <tr>
                        <td colspan="3" class="modal-empty-state">
                            No hay cambios registrados para mostrar
                        </td>
                    </tr>
                `;
            } else {
                allKeys.forEach(key => {
                let oldVal = oldValues[key] !== undefined ? String(oldValues[key]) : '-';
                let newVal = newValues[key] !== undefined ? String(newValues[key]) : '-';
                
                // Traducir valores de estado/activo
                if (key === 'estado' || key === 'activo') {
                    oldVal = oldVal === '1' || oldVal === 'true' ? 'Activo' : (oldVal === '0' || oldVal === 'false' ? 'Desactivado' : oldVal);
                    newVal = newVal === '1' || newVal === 'true' ? 'Activo' : (newVal === '0' || newVal === 'false' ? 'Desactivado' : newVal);
                }
                
                const isChanged = (oldVal !== newVal && event === 'updated');
                const rowClass = isChanged ? 'highlight-change' : '';
                
                // Traducir nombre del campo
                let fieldLabel = key;
                if (key === 'estado' || key === 'activo') {
                    fieldLabel = 'Estado';
                } else {
                    fieldLabel = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
                }
                
                contentHtml += `
                    <tr class="${rowClass}">
                        <td class="field-name">${fieldLabel}</td>
                        <td class="value-old">${oldVal}</td>
                        <td class="value-new">${newVal}</td>
                    </tr>
                `;
            });    }
            
            contentHtml += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // Mostrar el modal
            const modalBody = document.getElementById('modalAuditBody');
            modalBody.innerHTML = contentHtml;
            
            const modal = document.getElementById('modalAuditDetail');
            modal.classList.add('show');
        });
    });
});
</script>
@endpush
