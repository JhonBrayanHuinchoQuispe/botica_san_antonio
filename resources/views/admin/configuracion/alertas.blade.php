@extends('layout.layout')
@php
    $title = 'Configuración de Alertas';
    $subTitle = 'Notificaciones y alertas del sistema';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/admin/configuracion.js') . '?v=' . time() . '"></script>
    ';
@endphp

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-to-r from-orange-600 to-orange-700 text-white rounded-t-lg p-4">
                    <div class="flex items-center gap-2">
                        <iconify-icon icon="solar:bell-bing-bold-duotone" class="text-2xl"></iconify-icon>
                        <h3 class="text-lg font-semibold">Configuración de Alertas</h3>
                    </div>
                </div>
                <div class="card-body p-6">
                    <form id="formConfiguracionAlertas" onsubmit="guardarConfiguracionAlertas(event)">
                        @csrf
                        
                        <!-- Alertas de Stock -->
                        <div class="mb-6">
                            <h5 class="font-semibold mb-4">
                                <iconify-icon icon="solar:box-bold-duotone" class="mr-2"></iconify-icon>
                                Alertas de Stock
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" 
                                       name="alertas_stock_minimo" id="alertas_stock_minimo" 
                                       value="1" 
                                       {{ ($configuracion->alertas_stock_minimo ?? true) ? 'checked' : '' }}
                                       onchange="toggleStockFields()">
                                <label class="form-check-label" for="alertas_stock_minimo">
                                    <strong>Habilitar alertas de stock mínimo</strong>
                                </label>
                            </div>
                            
                            <div id="stock-fields" style="display: {{ ($configuracion->alertas_stock_minimo ?? true) ? 'block' : 'none' }};">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Stock mínimo global</label>
                                        <input type="number" name="stock_minimo_global" id="stock_minimo_global" 
                                               class="form-control" 
                                               value="{{ $configuracion->stock_minimo_global ?? 10 }}"
                                               min="1" max="1000">
                                        <small class="text-muted">Cantidad mínima por defecto para todos los productos</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Días de anticipación</label>
                                        <input type="number" name="dias_anticipacion_stock" id="dias_anticipacion_stock" 
                                               class="form-control" 
                                               value="{{ $configuracion->dias_anticipacion_stock ?? 7 }}"
                                               min="1" max="30">
                                        <small class="text-muted">Días antes de llegar al stock mínimo</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alertas de Vencimiento -->
                        <div class="mb-6">
                            <h5 class="font-semibold mb-4">
                                <iconify-icon icon="solar:calendar-mark-bold-duotone" class="mr-2"></iconify-icon>
                                Alertas de Vencimiento
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" 
                                       name="alertas_vencimiento" id="alertas_vencimiento" 
                                       value="1" 
                                       {{ ($configuracion->alertas_vencimiento ?? true) ? 'checked' : '' }}
                                       onchange="toggleVencimientoFields()">
                                <label class="form-check-label" for="alertas_vencimiento">
                                    <strong>Habilitar alertas de vencimiento</strong>
                                </label>
                            </div>
                            
                            <div id="vencimiento-fields" style="display: {{ ($configuracion->alertas_vencimiento ?? true) ? 'block' : 'none' }};">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Días de anticipación</label>
                                        <input type="number" name="dias_anticipacion_vencimiento" id="dias_anticipacion_vencimiento" 
                                               class="form-control" 
                                               value="{{ $configuracion->dias_anticipacion_vencimiento ?? 30 }}"
                                               min="1" max="365">
                                        <small class="text-muted">Días antes del vencimiento para alertar</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nivel de criticidad</label>
                                        <select name="nivel_criticidad_vencimiento" id="nivel_criticidad_vencimiento" class="form-control">
                                            <option value="baja" {{ ($configuracion->nivel_criticidad_vencimiento ?? 'media') == 'baja' ? 'selected' : '' }}>Baja</option>
                                            <option value="media" {{ ($configuracion->nivel_criticidad_vencimiento ?? 'media') == 'media' ? 'selected' : '' }}>Media</option>
                                            <option value="alta" {{ ($configuracion->nivel_criticidad_vencimiento ?? 'media') == 'alta' ? 'selected' : '' }}>Alta</option>
                                        </select>
                                        <small class="text-muted">Nivel de importancia de las alertas</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alertas por Email -->
                        <div class="mb-6">
                            <h5 class="font-semibold mb-4">
                                <iconify-icon icon="solar:letter-bold-duotone" class="mr-2"></iconify-icon>
                                Notificaciones por Email
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" 
                                       name="alertas_email" id="alertas_email" 
                                       value="1" 
                                       {{ ($configuracion->alertas_email ?? false) ? 'checked' : '' }}
                                       onchange="toggleEmailFields()">
                                <label class="form-check-label" for="alertas_email">
                                    <strong>Enviar alertas por email</strong>
                                </label>
                            </div>
                            
                            <div id="email-fields" style="display: {{ ($configuracion->alertas_email ?? false) ? 'block' : 'none' }};">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Email de notificaciones</label>
                                        <input type="email" name="email_alertas" id="email_alertas" 
                                               class="form-control" 
                                               value="{{ $configuracion->email_alertas ?? '' }}"
                                               placeholder="alertas@empresa.com">
                                        <small class="text-muted">Email donde se enviarán las alertas</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Frecuencia de envío</label>
                                        <select name="frecuencia_email_alertas" id="frecuencia_email_alertas" class="form-control">
                                            <option value="diario" {{ ($configuracion->frecuencia_email_alertas ?? 'diario') == 'diario' ? 'selected' : '' }}>Diario</option>
                                            <option value="semanal" {{ ($configuracion->frecuencia_email_alertas ?? 'diario') == 'semanal' ? 'selected' : '' }}>Semanal</option>
                                            <option value="inmediato" {{ ($configuracion->frecuencia_email_alertas ?? 'diario') == 'inmediato' ? 'selected' : '' }}>Inmediato</option>
                                        </select>
                                        <small class="text-muted">Con qué frecuencia enviar las alertas</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alertas del Sistema -->
                        <div class="mb-6">
                            <h5 class="font-semibold mb-4">
                                <iconify-icon icon="solar:settings-bold-duotone" class="mr-2"></iconify-icon>
                                Alertas del Sistema
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" 
                                       name="alertas_sistema" id="alertas_sistema" 
                                       value="1" 
                                       {{ ($configuracion->alertas_sistema ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="alertas_sistema">
                                    <strong>Mostrar alertas en el sistema</strong>
                                </label>
                            </div>
                            <small class="text-muted">Mostrar notificaciones en la interfaz del sistema</small>
                        </div>

                        <!-- Configuraciones Adicionales -->
                        <div class="mb-6">
                            <h5 class="font-semibold mb-4">
                                <iconify-icon icon="solar:widget-add-bold-duotone" class="mr-2"></iconify-icon>
                                Configuraciones Adicionales
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               name="alertas_ventas_bajas" id="alertas_ventas_bajas" 
                                               value="1" 
                                               {{ ($configuracion->alertas_ventas_bajas ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="alertas_ventas_bajas">
                                            Alertas de ventas bajas
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               name="alertas_productos_sin_movimiento" id="alertas_productos_sin_movimiento" 
                                               value="1" 
                                               {{ ($configuracion->alertas_productos_sin_movimiento ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="alertas_productos_sin_movimiento">
                                            Productos sin movimiento
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               name="alertas_backup" id="alertas_backup" 
                                               value="1" 
                                               {{ ($configuracion->alertas_backup ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="alertas_backup">
                                            Recordatorios de backup
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               name="alertas_actualizaciones" id="alertas_actualizaciones" 
                                               value="1" 
                                               {{ ($configuracion->alertas_actualizaciones ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="alertas_actualizaciones">
                                            Notificaciones de actualizaciones
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center gap-3 mt-6">
                            <div>
                                <button type="button" class="btn btn-info" onclick="probarAlertas()">
                                    <iconify-icon icon="solar:bell-bold" class="mr-2"></iconify-icon>
                                    Probar Alertas
                                </button>
                            </div>
                            <div class="flex gap-3">
                                <a href="{{ route('admin.configuracion') }}" class="btn btn-secondary">
                                    <iconify-icon icon="solar:arrow-left-bold" class="mr-2"></iconify-icon>
                                    Volver
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <iconify-icon icon="solar:diskette-bold" class="mr-2"></iconify-icon>
                                    Guardar Configuración
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel de Estado -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-gray-100 p-4">
                    <h4 class="text-md font-semibold">Estado de Alertas</h4>
                </div>
                <div class="card-body p-4">
                    <div class="space-y-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Stock bajo:</span>
                            <span class="badge bg-warning">5 productos</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Próximos a vencer:</span>
                            <span class="badge bg-danger">3 productos</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Sin movimiento:</span>
                            <span class="badge bg-info">12 productos</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Último backup:</span>
                            <span class="text-muted">Hace 2 días</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración Actual -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-gray-100 p-4">
                    <h4 class="text-md font-semibold">Configuración Actual</h4>
                </div>
                <div class="card-body p-4">
                    <div class="space-y-2 text-sm">
                        <div class="d-flex justify-content-between">
                            <span>Stock mínimo:</span>
                            <span class="{{ ($configuracion->alertas_stock_minimo ?? true) ? 'text-success' : 'text-muted' }}">
                                {{ ($configuracion->alertas_stock_minimo ?? true) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Vencimientos:</span>
                            <span class="{{ ($configuracion->alertas_vencimiento ?? true) ? 'text-success' : 'text-muted' }}">
                                {{ ($configuracion->alertas_vencimiento ?? true) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Email:</span>
                            <span class="{{ ($configuracion->alertas_email ?? false) ? 'text-success' : 'text-muted' }}">
                                {{ ($configuracion->alertas_email ?? false) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Sistema:</span>
                            <span class="{{ ($configuracion->alertas_sistema ?? true) ? 'text-success' : 'text-muted' }}">
                                {{ ($configuracion->alertas_sistema ?? true) ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleStockFields() {
    const habilitado = document.getElementById('alertas_stock_minimo').checked;
    const fields = document.getElementById('stock-fields');
    fields.style.display = habilitado ? 'block' : 'none';
}

function toggleVencimientoFields() {
    const habilitado = document.getElementById('alertas_vencimiento').checked;
    const fields = document.getElementById('vencimiento-fields');
    fields.style.display = habilitado ? 'block' : 'none';
}

function toggleEmailFields() {
    const habilitado = document.getElementById('alertas_email').checked;
    const fields = document.getElementById('email-fields');
    fields.style.display = habilitado ? 'block' : 'none';
}

async function probarAlertas() {
    try {
        const response = await fetch('{{ route("admin.configuracion.alertas.probar") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Prueba exitosa!',
                text: result.message,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            throw new Error(result.message || 'Error al probar las alertas');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al probar las alertas'
        });
    }
}

async function guardarConfiguracionAlertas(event) {
    event.preventDefault();
    
    const form = document.getElementById('formConfiguracionAlertas');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('{{ route("admin.configuracion.alertas.actualizar") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: result.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                Turbo.visit(window.location.href, { action: 'replace' });
            });
        } else {
            throw new Error(result.message || 'Error al guardar la configuración');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al guardar la configuración'
        });
    }
}
</script>
@endsection