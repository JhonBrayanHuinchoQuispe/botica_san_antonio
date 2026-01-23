@extends('layout.layout')
@php
    $title = 'Gestión de Caché';
    $subTitle = 'Administración del caché del sistema';
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
                <div class="card-header bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-t-lg p-4">
                    <div class="flex items-center gap-2">
                        <iconify-icon icon="solar:database-bold-duotone" class="text-2xl"></iconify-icon>
                        <h3 class="text-lg font-semibold">Gestión de Caché</h3>
                    </div>
                </div>
                <div class="card-body p-6">
                    
                    <div class="row mb-6">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <iconify-icon icon="solar:server-bold-duotone" class="text-3xl text-primary mb-2"></iconify-icon>
                                    <h6 class="font-semibold">Caché de Aplicación</h6>
                                    <p class="text-sm text-muted mb-0">Configuraciones y datos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <iconify-icon icon="solar:routing-2-bold-duotone" class="text-3xl text-success mb-2"></iconify-icon>
                                    <h6 class="font-semibold">Caché de Rutas</h6>
                                    <p class="text-sm text-muted mb-0">Rutas del sistema</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <iconify-icon icon="solar:eye-bold-duotone" class="text-3xl text-warning mb-2"></iconify-icon>
                                    <h6 class="font-semibold">Caché de Vistas</h6>
                                    <p class="text-sm text-muted mb-0">Plantillas compiladas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <iconify-icon icon="solar:settings-bold-duotone" class="text-3xl text-info mb-2"></iconify-icon>
                                    <h6 class="font-semibold">Caché de Config</h6>
                                    <p class="text-sm text-muted mb-0">Configuraciones</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <iconify-icon icon="solar:trash-bin-minimalistic-bold" class="mr-2"></iconify-icon>
                                        Limpiar Caché
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">Elimina todos los archivos de caché almacenados para mejorar el rendimiento.</p>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-primary" onclick="limpiarCache('application')">
                                            <iconify-icon icon="solar:server-bold" class="mr-2"></iconify-icon>
                                            Limpiar Caché de Aplicación
                                        </button>
                                        
                                        <button type="button" class="btn btn-outline-success" onclick="limpiarCache('route')">
                                            <iconify-icon icon="solar:routing-2-bold" class="mr-2"></iconify-icon>
                                            Limpiar Caché de Rutas
                                        </button>
                                        
                                        <button type="button" class="btn btn-outline-warning" onclick="limpiarCache('view')">
                                            <iconify-icon icon="solar:eye-bold" class="mr-2"></iconify-icon>
                                            Limpiar Caché de Vistas
                                        </button>
                                        
                                        <button type="button" class="btn btn-outline-info" onclick="limpiarCache('config')">
                                            <iconify-icon icon="solar:settings-bold" class="mr-2"></iconify-icon>
                                            Limpiar Caché de Configuración
                                        </button>
                                        
                                        <hr>
                                        
                                        <button type="button" class="btn btn-danger" onclick="limpiarCache('all')">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-bold" class="mr-2"></iconify-icon>
                                            Limpiar Todo el Caché
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <iconify-icon icon="solar:refresh-bold" class="mr-2"></iconify-icon>
                                        Optimizar Sistema
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">Optimiza el rendimiento del sistema regenerando los cachés necesarios.</p>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-success" onclick="optimizarSistema('config')">
                                            <iconify-icon icon="solar:settings-bold" class="mr-2"></iconify-icon>
                                            Optimizar Configuración
                                        </button>
                                        
                                        <button type="button" class="btn btn-outline-success" onclick="optimizarSistema('route')">
                                            <iconify-icon icon="solar:routing-2-bold" class="mr-2"></iconify-icon>
                                            Optimizar Rutas
                                        </button>
                                        
                                        <button type="button" class="btn btn-outline-success" onclick="optimizarSistema('autoload')">
                                            <iconify-icon icon="solar:code-bold" class="mr-2"></iconify-icon>
                                            Optimizar Autoload
                                        </button>
                                        
                                        <hr>
                                        
                                        <button type="button" class="btn btn-success" onclick="optimizarSistema('all')">
                                            <iconify-icon icon="solar:rocket-bold" class="mr-2"></iconify-icon>
                                            Optimización Completa
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <iconify-icon icon="solar:clock-circle-bold" class="mr-2"></iconify-icon>
                                Configuración Automática
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="formConfiguracionCache" onsubmit="guardarConfiguracionCache(event)">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="cache_automatico" id="cache_automatico" 
                                                   value="1" 
                                                   {{ ($configuracion->cache_automatico ?? false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="cache_automatico">
                                                <strong>Limpieza automática de caché</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted">Limpiar caché automáticamente cada cierto tiempo</small>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">Frecuencia de limpieza</label>
                                        <select name="frecuencia_limpieza_cache" id="frecuencia_limpieza_cache" class="form-control">
                                            <option value="diario" {{ ($configuracion->frecuencia_limpieza_cache ?? 'semanal') == 'diario' ? 'selected' : '' }}>Diario</option>
                                            <option value="semanal" {{ ($configuracion->frecuencia_limpieza_cache ?? 'semanal') == 'semanal' ? 'selected' : '' }}>Semanal</option>
                                            <option value="mensual" {{ ($configuracion->frecuencia_limpieza_cache ?? 'semanal') == 'mensual' ? 'selected' : '' }}>Mensual</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <iconify-icon icon="solar:diskette-bold" class="mr-2"></iconify-icon>
                                        Guardar Configuración
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('admin.configuracion') }}" class="btn btn-secondary">
                            <iconify-icon icon="solar:arrow-left-bold" class="mr-2"></iconify-icon>
                            Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-gray-100 p-4">
                    <h4 class="text-md font-semibold">Estado del Sistema</h4>
                </div>
                <div class="card-body p-4">
                    <div id="estado-sistema" class="space-y-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Estado del caché:</span>
                            <span class="badge bg-success">Activo</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Última limpieza:</span>
                            <span class="text-muted">No registrada</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Tamaño del caché:</span>
                            <span class="text-muted">Calculando...</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Rendimiento:</span>
                            <span class="badge bg-info">Óptimo</span>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-gray-100 p-4">
                    <h4 class="text-md font-semibold">Información</h4>
                </div>
                <div class="card-body p-4">
                    <div class="space-y-3 text-sm">
                        <div class="alert alert-info">
                            <iconify-icon icon="solar:info-circle-bold" class="mr-2"></iconify-icon>
                            <strong>Caché de Aplicación:</strong> Almacena datos temporales para mejorar el rendimiento.
                        </div>
                        
                        <div class="alert alert-warning">
                            <iconify-icon icon="solar:danger-triangle-bold" class="mr-2"></iconify-icon>
                            <strong>Precaución:</strong> Limpiar el caché puede afectar temporalmente el rendimiento.
                        </div>
                        
                        <div class="alert alert-success">
                            <iconify-icon icon="solar:check-circle-bold" class="mr-2"></iconify-icon>
                            <strong>Recomendación:</strong> Optimice el sistema después de limpiar el caché.
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-gray-100 p-4">
                    <h4 class="text-md font-semibold">Últimas Actividades</h4>
                </div>
                <div class="card-body p-4">
                    <div id="log-actividades" class="space-y-2 text-sm">
                        <div class="text-muted">No hay actividades registradas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function limpiarCache(tipo) {
    const tipoTexto = {
        'application': 'Caché de Aplicación',
        'route': 'Caché de Rutas',
        'view': 'Caché de Vistas',
        'config': 'Caché de Configuración',
        'all': 'Todo el Caché'
    };
    
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `Se limpiará: ${tipoTexto[tipo]}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        try {
            const response = await fetch('{{ route("admin.configuracion.cache.limpiar") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ tipo: tipo })
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                actualizarLogActividades(`Limpieza de ${tipoTexto[tipo]} completada`);
            } else {
                throw new Error(data.message || 'Error al limpiar el caché');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error al limpiar el caché'
            });
        }
    }
}

async function optimizarSistema(tipo) {
    const tipoTexto = {
        'config': 'Configuración',
        'route': 'Rutas',
        'autoload': 'Autoload',
        'all': 'Sistema Completo'
    };
    
    try {
        const response = await fetch('{{ route("admin.configuracion.cache.optimizar") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ tipo: tipo })
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Optimización completada!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });

            actualizarLogActividades(`Optimización de ${tipoTexto[tipo]} completada`);
        } else {
            throw new Error(data.message || 'Error al optimizar el sistema');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al optimizar el sistema'
        });
    }
}

async function guardarConfiguracionCache(event) {
    event.preventDefault();
    
    const form = document.getElementById('formConfiguracionCache');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('{{ route("admin.configuracion.cache.actualizar") }}', {
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

function actualizarLogActividades(mensaje) {
    const log = document.getElementById('log-actividades');
    const fecha = new Date().toLocaleString();
    
    const nuevaActividad = document.createElement('div');
    nuevaActividad.className = 'd-flex justify-content-between';
    nuevaActividad.innerHTML = `
        <span>${mensaje}</span>
        <small class="text-muted">${fecha}</small>
    `;

    if (log.children.length === 1 && log.children[0].textContent.includes('No hay actividades')) {
        log.innerHTML = '';
    }
    
    log.insertBefore(nuevaActividad, log.firstChild);

    while (log.children.length > 5) {
        log.removeChild(log.lastChild);
    }
}

document.addEventListener('DOMContentLoaded', function() {

});
</script>
@endsection