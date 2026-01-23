@extends('layouts.app')

@section('title', 'Respaldos del Sistema')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-database me-2"></i>
                        Respaldos del Sistema
                    </h3>
                    <button type="button" class="btn btn-primary" onclick="realizarBackup()">
                        <i class="fas fa-plus me-1"></i>
                        Crear Respaldo
                    </button>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Información:</strong> Los respaldos incluyen la base de datos completa y las imágenes de productos. 
                        Se recomienda crear respaldos periódicamente para proteger la información del sistema.
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-database fa-3x text-primary mb-3"></i>
                                    <h5>Base de Datos</h5>
                                    <p class="text-muted">Incluye todos los datos del sistema: productos, ventas, usuarios, etc.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-images fa-3x text-success mb-3"></i>
                                    <h5>Imágenes</h5>
                                    <p class="text-muted">Todas las imágenes de productos almacenadas en el sistema.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-file-archive fa-3x text-warning mb-3"></i>
                                    <h5>Archivo ZIP</h5>
                                    <p class="text-muted">Todo el respaldo se comprime en un archivo ZIP para fácil descarga.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <h5><i class="fas fa-history me-2"></i>Historial de Respaldos</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Archivo</th>
                                            <th>Tamaño</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historial-respaldos">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                <i class="fas fa-folder-open fa-2x mb-2"></i><br>
                                                No hay respaldos disponibles. Crea tu primer respaldo.
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
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/inventario/backup.js') }}"></script>
<script>

function realizarBackup() {
    Swal.fire({
        title: 'Crear Respaldo',
        text: '¿Desea crear un respaldo completo del sistema?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, crear respaldo',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {

            Swal.fire({
                title: 'Creando respaldo...',
                text: 'Por favor espere mientras se genera el respaldo.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('{{ route("admin.respaldos.crear") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Respaldo creado!',
                        text: `Respaldo generado exitosamente: ${data.filename}`,
                        confirmButtonText: 'Entendido'
                    }).then(() => {

                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: `Error al crear el respaldo: ${error.message}`,
                    confirmButtonText: 'Entendido'
                });
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    cargarHistorialRespaldos();
});

function cargarHistorialRespaldos() {

}
</script>
@endpush

@push('styles')
<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.bg-light {
    background-color: #f8f9fa !important;
}

.fa-3x {
    font-size: 3em;
}

.table th {
    border-top: none;
    font-weight: 600;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}
</style>
@endpush