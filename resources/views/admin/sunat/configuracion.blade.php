@extends('layouts.app')

@section('title', 'Configuración SUNAT - Facturación Electrónica')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice text-primary me-2"></i>
                        Configuración SUNAT - Facturación Electrónica
                    </h4>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" id="btnProbarConexion">
                            <i class="fas fa-wifi me-1"></i> Probar Conexión
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnEstadoSistema">
                            <i class="fas fa-info-circle me-1"></i> Estado del Sistema
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    
                    <div id="alertas-container"></div>
                    
                    
                    <ul class="nav nav-tabs" id="configTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="empresa-tab" data-bs-toggle="tab" data-bs-target="#empresa" type="button" role="tab">
                                <i class="fas fa-building me-1"></i> Datos de Empresa
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sunat-tab" data-bs-toggle="tab" data-bs-target="#sunat" type="button" role="tab">
                                <i class="fas fa-university me-1"></i> Credenciales SUNAT
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="certificado-tab" data-bs-toggle="tab" data-bs-target="#certificado" type="button" role="tab">
                                <i class="fas fa-certificate me-1"></i> Certificado Digital
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="monitoreo-tab" data-bs-toggle="tab" data-bs-target="#monitoreo" type="button" role="tab">
                                <i class="fas fa-chart-line me-1"></i> Monitoreo
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3" id="configTabsContent">
                        
                        <div class="tab-pane fade show active" id="empresa" role="tabpanel">
                            <form id="formEmpresa">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="ruc" class="form-label">RUC <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="ruc" name="ruc" value="{{ $config['ruc'] }}" maxlength="11" required>
                                            <div class="form-text">Número de RUC de 11 dígitos</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="ubigeo" class="form-label">Ubigeo <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="ubigeo" name="ubigeo" value="{{ $config['ubigeo'] }}" maxlength="6" required>
                                            <div class="form-text">Código de ubigeo de 6 dígitos</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="razon_social" class="form-label">Razón Social <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="razon_social" name="razon_social" value="{{ $config['razon_social'] }}" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nombre_comercial" class="form-label">Nombre Comercial <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre_comercial" name="nombre_comercial" value="{{ $config['nombre_comercial'] }}" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="departamento" class="form-label">Departamento <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="departamento" name="departamento" value="{{ $config['departamento'] }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="provincia" class="form-label">Provincia <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="provincia" name="provincia" value="{{ $config['provincia'] }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="distrito" class="form-label">Distrito <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="distrito" name="distrito" value="{{ $config['distrito'] }}" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="direccion" name="direccion" value="{{ $config['direccion'] }}" required>
                                </div>
                            </form>
                        </div>
                        
                        
                        <div class="tab-pane fade" id="sunat" role="tabpanel">
                            <form id="formSunat">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="usuario_sol" class="form-label">Usuario SOL <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="usuario_sol" name="usuario_sol" value="{{ $config['usuario_sol'] }}" required>
                                            <div class="form-text">Usuario proporcionado por SUNAT</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="clave_sol" class="form-label">Clave SOL <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="clave_sol" name="clave_sol" required>
                                            <div class="form-text">Clave proporcionada por SUNAT</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="produccion" name="produccion" {{ $config['produccion'] ? 'checked' : '' }}>
                                        <label class="form-check-label" for="produccion">
                                            <strong>Modo Producción</strong>
                                        </label>
                                        <div class="form-text">
                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                            Desactivar para usar el entorno de pruebas (BETA). Activar solo cuando tengas certificado válido y credenciales de producción.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Importante:</strong> Las credenciales SOL son proporcionadas por SUNAT al registrarte para facturación electrónica.
                                    Para obtenerlas, debes ingresar a <a href="https://www.sunat.gob.pe" target="_blank">www.sunat.gob.pe</a> y seguir el proceso de registro.
                                </div>
                            </form>
                        </div>
                        
                        
                        <div class="tab-pane fade" id="certificado" role="tabpanel">
                            <div class="row">
                                <div class="col-md-8">
                                    <form id="formCertificado" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="certificado" class="form-label">Certificado Digital (.pfx) <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control" id="certificado" name="certificado" accept=".pfx" required>
                                            <div class="form-text">Selecciona tu archivo de certificado digital en formato .pfx</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Contraseña del Certificado <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <div class="form-text">Contraseña que protege tu certificado digital</div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload me-1"></i> Subir Certificado
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i> Estado del Certificado</h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="certificado-status">
                                                @if($config['certificado_existe'])
                                                    <div class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        Certificado cargado
                                                    </div>
                                                @else
                                                    <div class="text-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        Sin certificado
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <i class="fas fa-shield-alt me-2"></i>
                                        <strong>Seguridad:</strong> El certificado digital es necesario para firmar los comprobantes electrónicos. Manténlo seguro y no lo compartas.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="tab-pane fade" id="monitoreo" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-tachometer-alt me-1"></i> Estado del Sistema</h6>
                                        </div>
                                        <div class="card-body" id="sistema-status">
                                            <div class="d-flex justify-content-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-history me-1"></i> Últimas Boletas</h6>
                                        </div>
                                        <div class="card-body" id="ultimas-boletas">
                                            <div class="d-flex justify-content-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </button>
                        <button type="button" class="btn btn-success" id="btnGuardarConfiguracion">
                            <i class="fas fa-save me-1"></i> Guardar Configuración
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEstadoSistema" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Estado del Sistema SUNAT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalEstadoContent">
                
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.nav-tabs .nav-link {
    border-radius: 0.375rem 0.375rem 0 0;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}

.status-success { background-color: #28a745; }
.status-warning { background-color: #ffc107; }
.status-danger { background-color: #dc3545; }
.status-info { background-color: #17a2b8; }

.card-header h6 {
    color: #495057;
    font-weight: 600;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
}

.alert {
    border-radius: 0.5rem;
}

.btn {
    border-radius: 0.375rem;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {

    $('#monitoreo-tab').on('click', function() {
        cargarEstadoSistema();
    });

    $('#btnGuardarConfiguracion').on('click', function() {
        guardarConfiguracion();
    });

    $('#formCertificado').on('submit', function(e) {
        e.preventDefault();
        subirCertificado();
    });

    $('#btnProbarConexion').on('click', function() {
        probarConexion();
    });

    $('#btnEstadoSistema').on('click', function() {
        mostrarEstadoSistema();
    });

    $('#ruc').on('input', function() {
        let ruc = $(this).val().replace(/\D/g, '');
        $(this).val(ruc);
        
        if (ruc.length === 11) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });

    $('#ubigeo').on('input', function() {
        let ubigeo = $(this).val().replace(/\D/g, '');
        $(this).val(ubigeo);
        
        if (ubigeo.length === 6) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });
});

function guardarConfiguracion() {
    const btn = $('#btnGuardarConfiguracion');
    const originalText = btn.html();

    const formData = new FormData();

    $('#formEmpresa').serializeArray().forEach(function(item) {
        formData.append(item.name, item.value);
    });

    $('#formSunat').serializeArray().forEach(function(item) {
        formData.append(item.name, item.value);
    });

    formData.append('produccion', $('#produccion').is(':checked') ? '1' : '0');
    
    btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...').prop('disabled', true);
    
    $.ajax({
        url: '{{ route("admin.sunat.configuracion.guardar") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                mostrarAlerta('success', response.message);
            } else {
                mostrarAlerta('danger', response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            if (response && response.errors) {
                let errorMsg = 'Errores de validación:\n';
                Object.keys(response.errors).forEach(function(key) {
                    errorMsg += '- ' + response.errors[key][0] + '\n';
                });
                mostrarAlerta('danger', errorMsg);
            } else {
                mostrarAlerta('danger', 'Error guardando configuración');
            }
        },
        complete: function() {
            btn.html(originalText).prop('disabled', false);
        }
    });
}

function subirCertificado() {
    const form = $('#formCertificado')[0];
    const formData = new FormData(form);
    const btn = $('#formCertificado button[type="submit"]');
    const originalText = btn.html();
    
    btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Subiendo...').prop('disabled', true);
    
    $.ajax({
        url: '{{ route("admin.sunat.certificado.subir") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                mostrarAlerta('success', response.message);
                actualizarEstadoCertificado(true);
                $('#formCertificado')[0].reset();
            } else {
                mostrarAlerta('danger', response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            mostrarAlerta('danger', response.message || 'Error subiendo certificado');
        },
        complete: function() {
            btn.html(originalText).prop('disabled', false);
        }
    });
}

function probarConexion() {
    const btn = $('#btnProbarConexion');
    const originalText = btn.html();
    
    btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Probando...').prop('disabled', true);
    
    $.ajax({
        url: '{{ route("admin.sunat.conexion.probar") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                mostrarAlerta('success', response.message + ' (Modo: ' + response.modo + ')');
            } else {
                mostrarAlerta('warning', response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            mostrarAlerta('danger', response.message || 'Error probando conexión');
        },
        complete: function() {
            btn.html(originalText).prop('disabled', false);
        }
    });
}

function cargarEstadoSistema() {
    $.ajax({
        url: '{{ route("admin.sunat.estado") }}',
        method: 'GET',
        success: function(response) {
            actualizarEstadoSistema(response);
        },
        error: function() {
            $('#sistema-status').html('<div class="text-danger">Error cargando estado</div>');
            $('#ultimas-boletas').html('<div class="text-danger">Error cargando datos</div>');
        }
    });
}

function mostrarEstadoSistema() {
    $.ajax({
        url: '{{ route("admin.sunat.estado") }}',
        method: 'GET',
        success: function(response) {
            let content = '<div class="row">';

            content += '<div class="col-md-6">';
            content += '<h6><i class="fas fa-cog me-1"></i> Configuración</h6>';
            content += '<ul class="list-unstyled">';
            content += '<li><span class="status-indicator ' + (response.configuracion_completa ? 'status-success' : 'status-danger') + '"></span>' + (response.configuracion_completa ? 'Completa' : 'Incompleta') + '</li>';
            content += '<li><span class="status-indicator ' + (response.certificado_valido ? 'status-success' : 'status-danger') + '"></span>Certificado ' + (response.certificado_valido ? 'Válido' : 'Inválido') + '</li>';
            content += '<li><span class="status-indicator ' + (response.conexion_sunat ? 'status-success' : 'status-warning') + '"></span>Conexión SUNAT ' + (response.conexion_sunat ? 'OK' : 'Pendiente') + '</li>';
            content += '</ul>';
            content += '</div>';

            content += '<div class="col-md-6">';
            content += '<h6><i class="fas fa-chart-bar me-1"></i> Estadísticas</h6>';
            content += '<p><strong>Modo:</strong> ' + response.modo + '</p>';
            content += '<p><strong>Boletas generadas:</strong> ' + response.ultimas_boletas.length + '</p>';
            content += '</div>';
            
            content += '</div>';
            
            $('#modalEstadoContent').html(content);
            $('#modalEstadoSistema').modal('show');
        }
    });
}

function actualizarEstadoSistema(data) {

    let statusHtml = '<div class="list-group list-group-flush">';
    statusHtml += '<div class="list-group-item d-flex justify-content-between align-items-center">';
    statusHtml += 'Configuración <span class="badge ' + (data.configuracion_completa ? 'bg-success' : 'bg-danger') + '">' + (data.configuracion_completa ? 'Completa' : 'Incompleta') + '</span>';
    statusHtml += '</div>';
    statusHtml += '<div class="list-group-item d-flex justify-content-between align-items-center">';
    statusHtml += 'Certificado <span class="badge ' + (data.certificado_valido ? 'bg-success' : 'bg-danger') + '">' + (data.certificado_valido ? 'Válido' : 'Inválido') + '</span>';
    statusHtml += '</div>';
    statusHtml += '<div class="list-group-item d-flex justify-content-between align-items-center">';
    statusHtml += 'Conexión SUNAT <span class="badge ' + (data.conexion_sunat ? 'bg-success' : 'bg-warning') + '">' + (data.conexion_sunat ? 'OK' : 'Pendiente') + '</span>';
    statusHtml += '</div>';
    statusHtml += '<div class="list-group-item d-flex justify-content-between align-items-center">';
    statusHtml += 'Modo <span class="badge bg-info">' + data.modo + '</span>';
    statusHtml += '</div>';
    statusHtml += '</div>';
    
    $('#sistema-status').html(statusHtml);

    let boletasHtml = '';
    if (data.ultimas_boletas && data.ultimas_boletas.length > 0) {
        boletasHtml = '<div class="list-group list-group-flush">';
        data.ultimas_boletas.forEach(function(boleta) {
            boletasHtml += '<div class="list-group-item">';
            boletasHtml += '<div class="d-flex w-100 justify-content-between">';
            boletasHtml += '<h6 class="mb-1">' + boleta.serie_electronica + '-' + boleta.numero_electronico + '</h6>';
            boletasHtml += '<small>' + new Date(boleta.fecha_envio_sunat).toLocaleDateString() + '</small>';
            boletasHtml += '</div>';
            boletasHtml += '<p class="mb-1">S/ ' + parseFloat(boleta.total).toFixed(2) + '</p>';
            boletasHtml += '<small class="text-' + (boleta.estado_sunat === 'ACEPTADO' ? 'success' : 'warning') + '">' + boleta.estado_sunat + '</small>';
            boletasHtml += '</div>';
        });
        boletasHtml += '</div>';
    } else {
        boletasHtml = '<div class="text-center text-muted"><i class="fas fa-inbox fa-2x mb-2"></i><br>No hay boletas generadas</div>';
    }
    
    $('#ultimas-boletas').html(boletasHtml);
}

function actualizarEstadoCertificado(existe) {
    const statusDiv = $('#certificado-status');
    if (existe) {
        statusDiv.html('<div class="text-success"><i class="fas fa-check-circle me-1"></i>Certificado cargado</div>');
    } else {
        statusDiv.html('<div class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Sin certificado</div>');
    }
}

function mostrarAlerta(tipo, mensaje) {
    const alertaHtml = `
        <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('#alertas-container').html(alertaHtml);

    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endpush