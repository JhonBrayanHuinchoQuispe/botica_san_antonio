@extends('layout.layout')
@php
    $title = 'Configuración del Sistema';
    $subTitle = 'Administrar todas las configuraciones del sistema';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/admin/configuracion.js') . '?v=' . time() . '"></script>
    ';
@endphp

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="text-xl font-bold mb-2">Configuración del Sistema</h2>
                            <p class="text-muted">Administre todas las configuraciones de su sistema de botica</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-success" onclick="exportarConfiguracion()">
                                <iconify-icon icon="solar:export-bold" class="mr-2"></iconify-icon>
                                Exportar Config
                            </button>
                            <button type="button" class="btn btn-warning" onclick="importarConfiguracion()">
                                <iconify-icon icon="solar:import-bold" class="mr-2"></iconify-icon>
                                Importar Config
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navegación por pestañas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <nav class="nav nav-tabs nav-fill" id="configTabs" role="tablist">
                        <button class="nav-link active" id="empresa-tab" data-bs-toggle="tab" data-bs-target="#empresa" type="button" role="tab">
                            <iconify-icon icon="solar:buildings-2-bold-duotone" class="mr-2"></iconify-icon>
                            Empresa
                        </button>
                        <button class="nav-link" id="igv-tab" data-bs-toggle="tab" data-bs-target="#igv" type="button" role="tab">
                            <iconify-icon icon="solar:calculator-bold-duotone" class="mr-2"></iconify-icon>
                            IGV
                        </button>
                        <button class="nav-link" id="comprobantes-tab" data-bs-toggle="tab" data-bs-target="#comprobantes" type="button" role="tab">
                            <iconify-icon icon="solar:document-add-bold-duotone" class="mr-2"></iconify-icon>
                            Comprobantes
                        </button>

                        <button class="nav-link" id="impresoras-tab" data-bs-toggle="tab" data-bs-target="#impresoras" type="button" role="tab">
                            <iconify-icon icon="solar:printer-bold-duotone" class="mr-2"></iconify-icon>
                            Impresoras
                        </button>
                        <button class="nav-link" id="tickets-tab" data-bs-toggle="tab" data-bs-target="#tickets" type="button" role="tab">
                            <iconify-icon icon="solar:ticket-bold-duotone" class="mr-2"></iconify-icon>
                            Tickets
                        </button>
                        <button class="nav-link" id="alertas-tab" data-bs-toggle="tab" data-bs-target="#alertas" type="button" role="tab">
                            <iconify-icon icon="solar:bell-bing-bold-duotone" class="mr-2"></iconify-icon>
                            Alertas
                        </button>
                        <button class="nav-link" id="cache-tab" data-bs-toggle="tab" data-bs-target="#cache" type="button" role="tab">
                            <iconify-icon icon="solar:server-bold-duotone" class="mr-2"></iconify-icon>
                            Cache
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido de las pestañas -->
    <div class="tab-content" id="configTabsContent">
        <!-- Empresa -->
        <div class="tab-pane fade show active" id="empresa" role="tabpanel" aria-labelledby="empresa-tab">
            @include('admin.configuracion.empresa')
        </div>

        <!-- IGV -->
        <div class="tab-pane fade" id="igv" role="tabpanel" aria-labelledby="igv-tab">
            @include('admin.configuracion.igv')
        </div>

        <!-- Comprobantes -->
        <div class="tab-pane fade" id="comprobantes" role="tabpanel" aria-labelledby="comprobantes-tab">
            @include('admin.configuracion.comprobantes')
        </div>



        <!-- Impresoras -->
        <div class="tab-pane fade" id="impresoras" role="tabpanel" aria-labelledby="impresoras-tab">
            @include('admin.configuracion.impresoras')
        </div>

        <!-- Tickets -->
        <div class="tab-pane fade" id="tickets" role="tabpanel" aria-labelledby="tickets-tab">
            @include('admin.configuracion.tickets')
        </div>

        <!-- Alertas -->
        <div class="tab-pane fade" id="alertas" role="tabpanel" aria-labelledby="alertas-tab">
            @include('admin.configuracion.alertas')
        </div>

        <!-- Cache -->
        <div class="tab-pane fade" id="cache" role="tabpanel" aria-labelledby="cache-tab">
            @include('admin.configuracion.cache')
        </div>
    </div>

    <!-- Panel de estado del sistema -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                    <h4 class="text-lg font-semibold">
                        <iconify-icon icon="solar:monitor-bold-duotone" class="mr-2"></iconify-icon>
                        Estado del Sistema
                    </h4>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="mb-2">
                                    <iconify-icon icon="solar:database-bold-duotone" class="text-3xl text-primary"></iconify-icon>
                                </div>
                                <h6 class="font-semibold">Base de Datos</h6>
                                <span class="badge bg-success" id="db-status">Conectado</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="mb-2">
                                    <iconify-icon icon="solar:cloud-check-bold-duotone" class="text-3xl text-success"></iconify-icon>
                                </div>
                                <h6 class="font-semibold">Sistema</h6>
                                <span class="badge bg-success" id="system-status">
                                    Operativo
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="mb-2">
                                    <iconify-icon icon="solar:printer-2-bold-duotone" class="text-3xl text-info"></iconify-icon>
                                </div>
                                <h6 class="font-semibold">Impresoras</h6>
                                <span class="badge bg-info" id="printer-status">Configurado</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="mb-2">
                                    <iconify-icon icon="solar:server-2-bold-duotone" class="text-3xl text-warning"></iconify-icon>
                                </div>
                                <h6 class="font-semibold">Cache</h6>
                                <span class="badge bg-warning" id="cache-status">Activo</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para importar configuración -->
<div class="modal fade" id="modalImportarConfig" tabindex="-1" aria-labelledby="modalImportarConfigLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalImportarConfigLabel">Importar Configuración</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formImportarConfig">
                    <div class="mb-3">
                        <label for="archivoConfig" class="form-label">Archivo de configuración</label>
                        <input type="file" class="form-control" id="archivoConfig" accept=".json" required>
                        <div class="form-text">Seleccione un archivo JSON con la configuración exportada</div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="sobrescribirConfig" checked>
                        <label class="form-check-label" for="sobrescribirConfig">
                            Sobrescribir configuración existente
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="procesarImportacion()">Importar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Funciones para exportar/importar configuración
async function exportarConfiguracion() {
    try {
        const response = await fetch('{{ route("admin.configuracion.exportar") }}', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `configuracion_sistema_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Configuración exportada correctamente',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            throw new Error('Error al exportar la configuración');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    }
}

function importarConfiguracion() {
    const modal = new bootstrap.Modal(document.getElementById('modalImportarConfig'));
    modal.show();
}

async function procesarImportacion() {
    const archivo = document.getElementById('archivoConfig').files[0];
    const sobrescribir = document.getElementById('sobrescribirConfig').checked;
    
    if (!archivo) {
        Swal.fire({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Por favor seleccione un archivo'
        });
        return;
    }
    
    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('sobrescribir', sobrescribir ? '1' : '0');
    
    try {
        const response = await fetch('{{ route("admin.configuracion.importar") }}', {
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
                location.reload();
            });
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalImportarConfig'));
            modal.hide();
        } else {
            throw new Error(result.message || 'Error al importar la configuración');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    }
}

// Verificar estado del sistema
async function verificarEstadoSistema() {
    try {
        const response = await fetch('{{ route("admin.configuracion.estado") }}', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const estado = await response.json();
        
        // Actualizar indicadores
        document.getElementById('db-status').textContent = estado.database ? 'Conectado' : 'Desconectado';
        document.getElementById('db-status').className = `badge ${estado.database ? 'bg-success' : 'bg-danger'}`;
        

        
        document.getElementById('printer-status').textContent = estado.printer ? 'Configurado' : 'Sin configurar';
        document.getElementById('printer-status').className = `badge ${estado.printer ? 'bg-info' : 'bg-secondary'}`;
        
        document.getElementById('cache-status').textContent = estado.cache ? 'Activo' : 'Inactivo';
        document.getElementById('cache-status').className = `badge ${estado.cache ? 'bg-warning' : 'bg-secondary'}`;
        
    } catch (error) {
        console.error('Error al verificar estado del sistema:', error);
    }
}

// Guardar pestaña activa en localStorage
document.addEventListener('DOMContentLoaded', function() {
    // Restaurar pestaña activa
    const activeTab = localStorage.getItem('activeConfigTab');
    if (activeTab) {
        const tabElement = document.querySelector(`#${activeTab}-tab`);
        if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
        }
    }
    
    // Guardar pestaña activa cuando cambie
    document.querySelectorAll('#configTabs button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const tabId = e.target.id.replace('-tab', '');
            localStorage.setItem('activeConfigTab', tabId);
        });
    });
    
    // Verificar estado del sistema cada 30 segundos
    verificarEstadoSistema();
    setInterval(verificarEstadoSistema, 30000);
});

// Función global para mostrar notificaciones
function mostrarNotificacion(tipo, titulo, mensaje) {
    Swal.fire({
        icon: tipo,
        title: titulo,
        text: mensaje,
        timer: tipo === 'success' ? 2000 : 0,
        showConfirmButton: tipo !== 'success'
    });
}
</script>
@endsection