@extends('layout.layout')
@php
    $title = 'Devoluciones';
    $subTitle = 'Gestión de devoluciones de productos';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/ventas/devoluciones.js') . '?v=' . time() . '"></script>
    ';
@endphp

@push('head')
    <title>Devoluciones</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ventas/ventas.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<style>

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

.reportes-metric-content {
    flex: 1;
}

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
    color: #94a3b8;
    margin-top: 0.15rem;
    font-weight: 600;
}

.reportes-metric-card.gold { border-left: 3px solid #eab308; }
.reportes-metric-card.gold .reportes-metric-icon-small { background: rgba(234, 179, 8, 0.1); color: #a16207; }

.reportes-metric-card.teal { border-left: 3px solid #14b8a6; }
.reportes-metric-card.teal .reportes-metric-icon-small { background: rgba(20, 184, 166, 0.1); color: #0f766e; }

.reportes-metric-card.purple { border-left: 3px solid #8b5cf6; }
.reportes-metric-card.purple .reportes-metric-icon-small { background: rgba(139, 92, 246, 0.1); color: #6d28d9; }

.reportes-metric-card.red { border-left: 3px solid #ef4444; }
.reportes-metric-card.red .reportes-metric-icon-small { background: rgba(239, 68, 68, 0.1); color: #b91c1c; }

.devoluciones-search-box {
    background: white;
    padding: 1.25rem 1.5rem;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    margin-bottom: 0.25rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.btn-buscar-premium {
    background: #3b82f6 !important;
    color: white !important;
    border: none !important;
    padding: 0 1.5rem !important;
    height: 48px !important;
    border-radius: 12px !important;
    font-weight: 700 !important;
    font-size: 0.85rem !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.2s ease !important;
}

.btn-buscar-premium:hover {
    background: #2563eb !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.devoluciones-table thead th {
    background: rgba(59, 130, 246, 0.05) !important;
    color: #2563eb !important;
    font-size: 0.72rem !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 1.25rem 1rem !important;
    border-bottom: 2px solid rgba(59, 130, 246, 0.1) !important;
}

.producto-row td {
    padding: 1rem !important;
    vertical-align: middle !important;
}

.producto-row:hover {
    background: #f8fafc !important;
}

.receipt-card-premium {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    margin: 1rem 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.receipt-header-premium {
    background: #f8fafc;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.receipt-body-premium {
    padding: 2rem;
}

.info-grid-premium {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.info-label-premium {
    font-size: 0.65rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    margin-bottom: 0.25rem;
}

.info-value-premium {
    font-size: 0.9rem;
    font-weight: 700;
    color: #475569;
}

.custom-checkbox {
    width: 18px;
    height: 18px;
    border-radius: 6px;
    border: 2px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.2s;
}

.custom-checkbox:checked {
    background-color: #4f46e5;
    border-color: #4f46e5;
}
</style>
@endpush

@section('content')

<div class="grid grid-cols-12 gap-6">
    
    <div class="col-span-12 mb-2">
        <div class="flex flex-col gap-0.5">
            <h1 class="text-xl font-bold text-slate-800">{{ $title }}</h1>
            <p class="text-xs text-slate-500">{{ $subTitle }}</p>
        </div>
    </div>

    
    <div class="col-span-12">
        <div class="reportes-metrics-grid-4">
            <div class="reportes-metric-card gold">
                <div class="reportes-metric-icon-small">
                    <iconify-icon icon="solar:refresh-square-bold-duotone"></iconify-icon>
                </div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Devoluciones Hoy</p>
                    <h3 class="reportes-metric-value-medium">{{ $estadisticas['devoluciones_hoy'] ?? 0 }}</h3>
                    <p class="reportes-metric-comparison">Operaciones del día</p>
                </div>
            </div>
            
            <div class="reportes-metric-card teal">
                <div class="reportes-metric-icon-small">
                    <iconify-icon icon="solar:calendar-mark-bold-duotone"></iconify-icon>
                </div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Este Mes</p>
                    <h3 class="reportes-metric-value-medium">{{ $estadisticas['devoluciones_mes'] ?? 0 }}</h3>
                    <p class="reportes-metric-comparison">Acumulado mensual</p>
                </div>
            </div>
            
            <div class="reportes-metric-card purple">
                <div class="reportes-metric-icon-small">
                    <iconify-icon icon="solar:wallet-money-bold-duotone"></iconify-icon>
                </div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Monto Devuelto Hoy</p>
                    <h3 class="reportes-metric-value-medium">S/ {{ number_format($estadisticas['monto_devuelto_hoy'] ?? 0, 2) }}</h3>
                    <p class="reportes-metric-comparison">Efectivo retornado</p>
                </div>
            </div>
            
            <div class="reportes-metric-card red">
                <div class="reportes-metric-icon-small">
                    <iconify-icon icon="solar:chart-square-bold-duotone"></iconify-icon>
                </div>
                <div class="reportes-metric-content">
                    <p class="reportes-metric-label">Monto Total Mes</p>
                    <h3 class="reportes-metric-value-medium">S/ {{ number_format($estadisticas['monto_devuelto_mes'] ?? 0, 2) }}</h3>
                    <p class="reportes-metric-comparison">Reembolsos del periodo</p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-span-12">
        <div class="devoluciones-search-box">
            <div style="display: flex; gap: 1rem; align-items: center;">
                <div style="position: relative; flex: 1;">
                    <iconify-icon icon="solar:magnifer-bold-duotone" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #3b82f6; font-size: 1.25rem;"></iconify-icon>
                    <input type="text" 
                           id="numeroVenta"
                           name="numero_venta" 
                           value="{{ request('numero_venta') }}"
                           class="form-control" 
                           placeholder="Buscar por número de venta (Ej: BBB100000034)..."
                           onkeypress="if(event.key === 'Enter') buscarVenta()"
                           style="padding-left: 3rem; padding-right: 3rem; border-radius: 12px; height: 48px; border: 1px solid #e2e8f0; font-size: 0.9rem;">
                    <button type="button" 
                            onclick="document.getElementById('numeroVenta').value = ''; document.getElementById('numeroVenta').focus();"
                            style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <iconify-icon icon="solar:close-circle-bold-duotone" style="font-size: 1.25rem;"></iconify-icon>
                    </button>
                </div>
                <button type="button" 
                        id="buscarVenta"
                        onclick="buscarVenta()"
                        class="btn-buscar-premium">
                    <iconify-icon icon="solar:minimalistic-magnifer-bold" style="font-size: 1.1rem; margin-right: 6px;"></iconify-icon>
                    Buscar Venta
                </button>
                <a href="{{ route('ventas.historial') }}" class="historial-btn-limpiar" style="height: 48px; padding: 0 1.5rem; display: inline-flex; align-items: center; gap: 8px;">
                    <iconify-icon icon="solar:history-bold-duotone" style="font-size: 1.1rem;"></iconify-icon>
                    Ver Historial
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-span-12" id="devolucionContentArea">
        @if($venta)
            <div class="receipt-card-premium animate__animated animate__fadeIn">
                <div class="receipt-header-premium">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <iconify-icon icon="solar:bill-list-bold-duotone" style="font-size: 1.5rem; color: #4f46e5;"></iconify-icon>
                        <span style="font-weight: 800; color: #475569; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">Información de la Venta</span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <span class="status-badge-premium status-paid">Venta Pagada</span>
                    </div>
                </div>
                <div class="receipt-body-premium">
                    <div class="info-grid-premium">
                        <div>
                            <p class="info-label-premium">N° de Operación</p>
                            <p class="info-value-premium">#{{ $venta->numero_venta }}</p>
                        </div>
                        <div>
                            <p class="info-label-premium">Fecha y Hora</p>
                            <p class="info-value-premium">{{ optional($venta->fecha_venta ?? $venta->created_at)->format('d/m/Y g:i A') }}</p>
                        </div>
                        <div>
                            <p class="info-label-premium">Comprobante</p>
                            <p class="info-value-premium">
                                @if($venta->tipo_comprobante === 'boleta') Boleta de Venta @elseif($venta->tipo_comprobante === 'ticket') Ticket de Venta @else Sin comprobante @endif
                            </p>
                        </div>
                        <div>
                            <p class="info-label-premium">Cliente</p>
                            <p class="info-value-premium">{{ optional($venta->cliente)->nombre_completo ?? 'Cliente General' }}</p>
                        </div>
                        <div>
                            <p class="info-label-premium">Vendedor</p>
                            <p class="info-value-premium">{{ $venta->usuario->name ?? 'Sistema' }}</p>
                        </div>
                        <div>
                            <p class="info-label-premium">Método de Pago</p>
                            <p class="info-value-premium" style="text-transform: capitalize;">{{ $venta->metodo_pago }}</p>
                        </div>
                    </div>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed #e2e8f0; display: flex; justify-content: flex-end; gap: 2rem;">
                        <div style="text-align: right;">
                            <p class="info-label-premium">Subtotal</p>
                            <p style="font-weight: 700; color: #64748b;">S/ {{ number_format($venta->subtotal, 2) }}</p>
                        </div>
                        <div style="text-align: right;">
                            <p class="info-label-premium">IGV (18%)</p>
                            <p style="font-weight: 700; color: #64748b;">S/ {{ number_format($venta->iva, 2) }}</p>
                        </div>
                        <div style="text-align: right;">
                            <p class="info-label-premium">Total Pagado</p>
                            <p style="font-size: 1.25rem; font-weight: 900; color: #10b981;">S/ {{ number_format($venta->total, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="historial-table-container-improved">
                <form id="devolucionForm" style="padding: 0;">
                    <input type="hidden" name="venta_id" value="{{ $venta->id }}">
                    
                    <div class="historial-table-wrapper-improved">
                        <table class="historial-table devoluciones-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px; text-align: center;">
                                        <input type="checkbox" id="selectAll" class="custom-checkbox">
                                    </th>
                                    <th style="text-align: left;">Descripción del Producto</th>
                                    <th>Cant. Vendida</th>
                                    <th>P. Unitario</th>
                                    <th>Subtotal</th>
                                    <th>Cant. a Devolver</th>
                                    <th>Motivo de Devolución</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($venta->detalles as $detalle)
                                <tr class="producto-row" data-detalle-id="{{ $detalle->id }}">
                                    <td style="text-align: center;">
                                        <input type="checkbox" 
                                               class="producto-checkbox custom-checkbox" 
                                               name="productos[{{ $loop->index }}][selected]"
                                               value="1">
                                    </td>
                                    <td style="text-align: left;">
                                        <div class="flex flex-col">
                                            <span style="font-weight: 700; color: #475569; font-size: 0.85rem;">{{ $detalle->producto->nombre }}</span>
                                            <span style="font-size: 0.75rem; color: #94a3b8;">{{ $detalle->producto->concentracion }}</span>
                                        </div>
                                    </td>
                                    <td>
                                            @php
                                                $cantidadDevuelta = $venta->devoluciones()
                                                    ->where('venta_detalle_id', $detalle->id)
                                                    ->sum('cantidad_devuelta');
                                                $cantidadDisponible = $detalle->cantidad - $cantidadDevuelta;
                                                $presentacionNombre = $detalle->presentacion_nombre ?: 'Unidad';
                                            @endphp
                                            
                                            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                                <span style="font-weight: 700; color: #64748b;">{{ $detalle->cantidad }} {{ $presentacionNombre }}</span>
                                                @if($cantidadDevuelta > 0)
                                                    <span style="font-size: 0.65rem; padding: 2px 6px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 4px; font-weight: 700;">Devueltas: {{ $cantidadDevuelta }}</span>
                                                    <span style="font-size: 0.65rem; padding: 2px 6px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 4px; font-weight: 700;">Disponibles: {{ $cantidadDisponible }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    <td><span style="font-weight: 600; color: #64748b;">S/ {{ number_format($detalle->precio_unitario, 2) }}</span></td>
                                    <td><span style="font-weight: 700; color: #475569;">S/ {{ number_format($detalle->subtotal, 2) }}</span></td>
                                    <td>
                                        <input type="hidden" name="productos[{{ $loop->index }}][detalle_id]" value="{{ $detalle->id }}">
                                        @if($cantidadDisponible > 0)
                                            <input type="number" 
                                                   name="productos[{{ $loop->index }}][cantidad_devolver]"
                                                   class="cantidad-devolver"
                                                   min="1" 
                                                   max="{{ $cantidadDisponible }}"
                                                   disabled
                                                   style="width: 80px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 10px; text-align: center; font-weight: 700; color: #4f46e5;">
                                        @else
                                            <span style="color: #94a3b8; font-style: italic; font-size: 0.75rem; font-weight: 600;">Completado</span>
                                            <input type="hidden" name="productos[{{ $loop->index }}][cantidad_devolver]" value="0">
                                        @endif
                                    </td>
                                    <td>
                                        <select name="productos[{{ $loop->index }}][motivo]" 
                                                class="motivo-select"
                                                disabled
                                                style="width: 180px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 0.8rem; color: #475569;">
                                            <option value="">Motivo...</option>
                                            <option value="defectuoso">Producto defectuoso</option>
                                            <option value="vencido">Producto vencido</option>
                                            <option value="equivocacion">Error en la venta</option>
                                            <option value="cliente_insatisfecho">Cliente insatisfecho</option>
                                            <option value="cambio_opinion">Cambio de opinión</option>
                                            <option value="otro">Otro motivo</option>
                                        </select>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="padding: 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 8px; color: #94a3b8;">
                                        <iconify-icon icon="solar:info-circle-bold-duotone" style="font-size: 1.25rem; color: #3b82f6;"></iconify-icon>
                                        <span style="font-size: 0.8rem; font-weight: 600;">Selecciona los productos y completa la información para procesar.</span>
                                    </div>
                                    <div style="display: flex; gap: 0.75rem;">
                                        <button type="submit" 
                                                class="btn-buscar-premium"
                                                id="procesarDevolucionBtn"
                                                style="background: #ef4444 !important; min-width: 200px;"
                                                disabled>
                                            <iconify-icon icon="solar:check-circle-bold-duotone" style="font-size: 1.1rem; margin-right: 6px;"></iconify-icon>
                                            Procesar Devolución
                                        </button>
                                    </div>
                                </div>
                            </div>
                </form>
            </div>
        @else
            
            <div class="historial-table-container-improved">
                <div class="historial-empty-improved">
                    @if(request('numero_venta'))
                        <div style="padding: 4rem 2rem; text-align: center;">
                            <div style="width: 80px; height: 80px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                                <iconify-icon icon="solar:magnifer-zoom-out-bold-duotone" style="font-size: 3rem;"></iconify-icon>
                            </div>
                            <h3 style="font-weight: 800; color: #1e293b; font-size: 1.5rem; margin-bottom: 0.5rem;">Venta no encontrada</h3>
                            <p style="color: #64748b; font-size: 1rem;">No se encontró la venta con número <strong style="color: #1e293b;">{{ request('numero_venta') }}</strong></p>
                        </div>
                    @else
                        <div style="padding: 4rem 2rem; text-align: center;">
                            <div style="width: 80px; height: 80px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                                <iconify-icon icon="solar:bill-list-bold-duotone" style="font-size: 3rem;"></iconify-icon>
                            </div>
                            <h3 style="font-weight: 800; color: #1e293b; font-size: 1.5rem; margin-bottom: 0.5rem;">Buscar venta para devolución</h3>
                            <p style="color: #64748b; font-size: 1rem;">Ingresa el número de venta en el campo de búsqueda para comenzar el proceso.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

<style>

.navbar-header .sidebar-mobile-toggle { display: none !important; }

.producto-row.selected {
    background: #f0f9ff !important;
    border-left: 4px solid #3b82f6;
}

.cantidad-devolver:enabled {
    border-color: #3b82f6 !important;
}

.motivo-select:enabled {
    border-color: #3b82f6 !important;
}

.producto-checkbox:checked {
    accent-color: #3b82f6;
}

#selectAll:checked {
    accent-color: #dc2626;
}

@media (max-width: 768px) {
    .historial-table th:nth-child(4),
    .historial-table td:nth-child(4),
    .historial-table th:nth-child(5),
    .historial-table td:nth-child(5) {
        display: none;
    }
    
    .cantidad-devolver {
        width: 60px !important;
    }
    
    .motivo-select {
        width: 120px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.producto-checkbox');
    const procesarBtn = document.getElementById('procesarDevolucionBtn');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                toggleProductoInputs(checkbox);
            });
            updateProcessarBtn();
        });
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            toggleProductoInputs(this);
            updateSelectAll();
            updateProcessarBtn();
        });
    });
    
    function toggleProductoInputs(checkbox) {
        const row = checkbox.closest('.producto-row');
        const cantidadInput = row.querySelector('.cantidad-devolver');
        const motivoSelect = row.querySelector('.motivo-select');
        
        if (checkbox.checked) {
            row.classList.add('selected');
            cantidadInput.disabled = false;
            cantidadInput.required = true;
            motivoSelect.disabled = false;
            motivoSelect.required = true;

            if (!cantidadInput.value) {
                cantidadInput.value = cantidadInput.max;
            }
        } else {
            row.classList.remove('selected');
            cantidadInput.disabled = true;
            cantidadInput.required = false;
            cantidadInput.value = '';
            motivoSelect.disabled = true;
            motivoSelect.required = false;
            motivoSelect.value = '';
        }
    }
    
    function updateSelectAll() {
        const checkedCount = document.querySelectorAll('.producto-checkbox:checked').length;
        const totalCount = checkboxes.length;
        
        if (selectAll) {
            selectAll.checked = checkedCount === totalCount;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < totalCount;
        }
    }
    
    function updateProcessarBtn() {
        const checkedCount = document.querySelectorAll('.producto-checkbox:checked').length;
        if (procesarBtn) {
            procesarBtn.disabled = checkedCount === 0;
        }
    }
});

function limpiarSeleccion() {
    document.querySelectorAll('.producto-checkbox').forEach(checkbox => {
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change'));
    });
}
</script>

{{-- Overlay de carga reutilizable (como en Presentación/Categoría) --}}
@include('components.loading-overlay', [
    'id' => 'loadingOverlay',
    'size' => 36,
    'inner' => 14,
    'label' => 'Cargando datos...'
])
