@extends('layouts.app')

@section('title', 'Monitoreo SUNAT')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Monitoreo de Comprobantes Electrónicos</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" id="actualizarEstados">
                            <i class="fas fa-sync-alt"></i> Actualizar Estados
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="total-enviados">0</h3>
                                    <p>Total Enviados</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="total-aceptados">0</h3>
                                    <p>Aceptados</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="total-pendientes">0</h3>
                                    <p>Pendientes</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3 id="total-rechazados">0</h3>
                                    <p>Rechazados</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="filtro-fecha-desde">Desde:</label>
                            <input type="date" class="form-control" id="filtro-fecha-desde" value="{{ date('Y-m-d', strtotime('-7 days')) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="filtro-fecha-hasta">Hasta:</label>
                            <input type="date" class="form-control" id="filtro-fecha-hasta" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="filtro-estado">Estado:</label>
                            <select class="form-control" id="filtro-estado">
                                <option value="">Todos</option>
                                <option value="enviado">Enviado</option>
                                <option value="aceptado">Aceptado</option>
                                <option value="rechazado">Rechazado</option>
                                <option value="pendiente">Pendiente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" id="aplicarFiltros">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de comprobantes -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tabla-comprobantes">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Venta</th>
                                    <th>Serie-Número</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado SUNAT</th>
                                    <th>Hash</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargan via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles -->
<div class="modal fade" id="modalDetalles" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detalles del Comprobante</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="contenido-detalles">
                <!-- Contenido cargado dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="descargarXML">
                    <i class="fas fa-download"></i> Descargar XML
                </button>
                <button type="button" class="btn btn-info" id="descargarPDF">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/sunat-config.css') }}">
<style>
.estado-badge {
    font-size: 0.8em;
    padding: 0.25em 0.6em;
}
.estado-enviado { background-color: #17a2b8; }
.estado-aceptado { background-color: #28a745; }
.estado-rechazado { background-color: #dc3545; }
.estado-pendiente { background-color: #ffc107; color: #212529; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let tabla = $('#tabla-comprobantes').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.sunat.monitoreo.data") }}',
            data: function(d) {
                d.fecha_desde = $('#filtro-fecha-desde').val();
                d.fecha_hasta = $('#filtro-fecha-hasta').val();
                d.estado = $('#filtro-estado').val();
            }
        },
        columns: [
            { data: 'fecha', name: 'fecha' },
            { data: 'numero_venta', name: 'numero_venta' },
            { data: 'serie_numero', name: 'serie_numero' },
            { data: 'cliente', name: 'cliente' },
            { data: 'total', name: 'total' },
            { data: 'estado_sunat', name: 'estado_sunat', orderable: false },
            { data: 'hash', name: 'hash', orderable: false },
            { data: 'acciones', name: 'acciones', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
        }
    });

    // Aplicar filtros
    $('#aplicarFiltros').click(function() {
        tabla.ajax.reload();
        cargarEstadisticas();
    });

    // Actualizar estados
    $('#actualizarEstados').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');
        
        $.post('{{ route("admin.sunat.actualizar-estados") }}', {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if (response.success) {
                tabla.ajax.reload();
                cargarEstadisticas();
                toastr.success('Estados actualizados correctamente');
            } else {
                toastr.error(response.message || 'Error al actualizar estados');
            }
        })
        .fail(function() {
            toastr.error('Error de conexión');
        })
        .always(function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Actualizar Estados');
        });
    });

    // Ver detalles
    $(document).on('click', '.btn-detalles', function() {
        let ventaId = $(this).data('venta-id');
        
        $.get('{{ route("admin.sunat.detalles", "") }}/' + ventaId)
        .done(function(response) {
            $('#contenido-detalles').html(response);
            $('#modalDetalles').modal('show');
        })
        .fail(function() {
            toastr.error('Error al cargar detalles');
        });
    });

    // Verificar estado individual
    $(document).on('click', '.btn-verificar', function() {
        let btn = $(this);
        let ventaId = btn.data('venta-id');
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post('{{ route("admin.punto-venta.verificar-estado") }}', {
            _token: '{{ csrf_token() }}',
            venta_id: ventaId
        })
        .done(function(response) {
            if (response.success) {
                tabla.ajax.reload();
                toastr.success('Estado verificado');
            } else {
                toastr.error(response.message);
            }
        })
        .fail(function() {
            toastr.error('Error al verificar estado');
        })
        .always(function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i>');
        });
    });

    // Regenerar comprobante
    $(document).on('click', '.btn-regenerar', function() {
        if (!confirm('¿Está seguro de regenerar este comprobante?')) return;
        
        let btn = $(this);
        let ventaId = btn.data('venta-id');
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post('{{ route("admin.punto-venta.regenerar-comprobante") }}', {
            _token: '{{ csrf_token() }}',
            venta_id: ventaId
        })
        .done(function(response) {
            if (response.success) {
                tabla.ajax.reload();
                toastr.success('Comprobante regenerado exitosamente');
            } else {
                toastr.error(response.message);
            }
        })
        .fail(function() {
            toastr.error('Error al regenerar comprobante');
        })
        .always(function() {
            btn.prop('disabled', false).html('<i class="fas fa-redo"></i>');
        });
    });

    // Cargar estadísticas iniciales
    cargarEstadisticas();

    function cargarEstadisticas() {
        $.get('{{ route("admin.sunat.estadisticas") }}', {
            fecha_desde: $('#filtro-fecha-desde').val(),
            fecha_hasta: $('#filtro-fecha-hasta').val()
        })
        .done(function(response) {
            $('#total-enviados').text(response.enviados || 0);
            $('#total-aceptados').text(response.aceptados || 0);
            $('#total-pendientes').text(response.pendientes || 0);
            $('#total-rechazados').text(response.rechazados || 0);
        });
    }
});
</script>
@endpush