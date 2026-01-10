@extends('layouts.app')

@section('title', 'Gestión de Lotes - ' . $producto->nombre)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-boxes text-primary"></i>
                        Gestión de Lotes - {{ $producto->nombre }}
                    </h4>
                    <a href="{{ route('inventario.productos.botica') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Inventario
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- Información del Producto -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Información del Producto</h6>
                                    <p><strong>Código:</strong> {{ $producto->codigo }}</p>
                                    <p><strong>Categoría:</strong> {{ $producto->categoria->nombre ?? 'Sin categoría' }}</p>
                                    <p><strong>Presentación:</strong> {{ $producto->presentacion->nombre ?? 'Sin presentación' }}</p>
                                    <p><strong>Stock Total:</strong> 
                                        <span class="badge badge-{{ $producto->stock_actual > $producto->stock_minimo ? 'success' : 'warning' }}">
                                            {{ $producto->stock_actual }} unidades
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Resumen de Lotes</h6>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h4 class="text-primary">{{ $resumenLotes['total_lotes'] }}</h4>
                                                <small>Total Lotes</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h4 class="text-success">{{ $resumenLotes['lotes_activos'] }}</h4>
                                                <small>Lotes Activos</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h4 class="text-warning">{{ $resumenLotes['proximos_vencer'] }}</h4>
                                                <small>Próximos a Vencer</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center">
                                                <h4 class="text-danger">{{ $resumenLotes['vencidos'] }}</h4>
                                                <small>Vencidos</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Lotes -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaLotes">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Lote</th>
                                    <th>Ubicación</th>
                                    <th>Fecha Ingreso</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Estado</th>
                                    <th>Cantidad Inicial</th>
                                    <th>Cantidad Actual</th>
                                    <th>Cantidad Vendida</th>
                                    <th>Precio Compra</th>
                                    <th>Precio Venta</th>
                                    <th>Proveedor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lotes as $lote)
                                <tr class="{{ $lote->estado_lote === 'vencido' ? 'table-danger' : ($lote->dias_para_vencer <= 30 ? 'table-warning' : '') }}">
                                    <td>
                                        <strong>{{ $lote->lote ?? 'Sin lote' }}</strong>
                                    </td>
                                    <td>{{ $lote->ubicacion->nombre ?? 'Sin ubicación' }}</td>
                                    <td>{{ $lote->fecha_ingreso ? $lote->fecha_ingreso->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        @if($lote->fecha_vencimiento)
                                            {{ $lote->fecha_vencimiento->format('d/m/Y') }}
                                            @if($lote->dias_para_vencer <= 30)
                                                <br><small class="text-{{ $lote->dias_para_vencer <= 0 ? 'danger' : 'warning' }}">
                                                    {{ $lote->dias_para_vencer <= 0 ? 'Vencido' : $lote->dias_para_vencer . ' días' }}
                                                </small>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $lote->estado_lote === 'activo' ? 'success' : ($lote->estado_lote === 'vencido' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($lote->estado_lote) }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($lote->cantidad_inicial ?? 0) }}</td>
                                    <td>
                                        <strong class="text-{{ $lote->cantidad > 0 ? 'success' : 'danger' }}">
                                            {{ number_format($lote->cantidad) }}
                                        </strong>
                                    </td>
                                    <td>{{ number_format($lote->cantidad_vendida ?? 0) }}</td>
                                    <td>S/ {{ number_format($lote->precio_compra_lote ?? 0, 2) }}</td>
                                    <td>S/ {{ number_format($lote->precio_venta_lote ?? 0, 2) }}</td>
                                    <td>{{ $lote->proveedor->nombre ?? 'Sin proveedor' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-info" onclick="verMovimientos({{ $lote->id }})">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            @if($lote->cantidad > 0 && $lote->estado_lote === 'activo')
                                                <button type="button" class="btn btn-sm btn-warning" onclick="ajustarStock({{ $lote->id }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center">
                                        <div class="py-4">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay lotes registrados para este producto</h5>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver movimientos -->
<div class="modal fade" id="modalMovimientos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Movimientos del Lote</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="contenidoMovimientos">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para ajustar stock -->
<div class="modal fade" id="modalAjustarStock" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajustar Stock del Lote</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formAjustarStock">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Cantidad Actual</label>
                        <input type="number" class="form-control" id="cantidadActual" readonly>
                    </div>
                    <div class="form-group">
                        <label>Nueva Cantidad</label>
                        <input type="number" class="form-control" id="nuevaCantidad" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Motivo del Ajuste</label>
                        <select class="form-control" id="motivoAjuste" required>
                            <option value="">Seleccionar motivo</option>
                            <option value="merma">Merma</option>
                            <option value="rotura">Rotura</option>
                            <option value="vencimiento">Vencimiento</option>
                            <option value="inventario">Ajuste de inventario</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea class="form-control" id="observacionesAjuste" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Ajuste</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#tablaLotes').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
        },
        order: [[2, 'desc']], // Ordenar por fecha de ingreso descendente
        pageLength: 25,
        responsive: true
    });
});

function verMovimientos(loteId) {
    $('#contenidoMovimientos').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
    $('#modalMovimientos').modal('show');
    
    // Aquí se cargarían los movimientos del lote
    // Por ahora mostramos un mensaje placeholder
    setTimeout(() => {
        $('#contenidoMovimientos').html(`
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Funcionalidad de movimientos en desarrollo.
                <br>Se mostrará el historial completo de movimientos del lote.
            </div>
        `);
    }, 1000);
}

function ajustarStock(loteId) {
    // Aquí se cargarían los datos del lote
    $('#modalAjustarStock').modal('show');
    
    // Placeholder - en implementación real se cargarían los datos del lote
    $('#cantidadActual').val('100');
    $('#nuevaCantidad').val('');
    $('#motivoAjuste').val('');
    $('#observacionesAjuste').val('');
}

$('#formAjustarStock').on('submit', function(e) {
    e.preventDefault();
    
    // Aquí se procesaría el ajuste de stock
    alert('Funcionalidad de ajuste de stock en desarrollo');
    $('#modalAjustarStock').modal('hide');
});

function cambiarEstado(loteId, estadoActual) {
    $('#loteIdEstado').val(loteId);
    $('#estadoActual').val(estadoActual.charAt(0).toUpperCase() + estadoActual.slice(1));
    $('#nuevoEstado').val('');
    $('#motivoCambio').val('');
    $('#modalCambiarEstado').modal('show');
}

$('#formCambiarEstado').on('submit', function(e) {
    e.preventDefault();
    
    const loteId = $('#loteIdEstado').val();
    const nuevoEstado = $('#nuevoEstado').val();
    const motivo = $('#motivoCambio').val();
    
    if(!nuevoEstado || !motivo) {
        alert('Por favor complete todos los campos');
        return;
    }

    // Deshabilitar botón para prevenir doble envío
    const btnSubmit = $(this).find('button[type="submit"]');
    const originalText = btnSubmit.text();
    btnSubmit.prop('disabled', true).text('Guardando...');

    $.ajax({
        url: `/inventario/lote/${loteId}/cambiar-estado`,
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            nuevo_estado: nuevoEstado,
            motivo: motivo
        },
        success: function(response) {
            if(response.success) {
                alert('Estado actualizado correctamente');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            let msg = 'Ocurrió un error al actualizar el estado';
            if(xhr.responseJSON && xhr.responseJSON.message) {
                msg += ': ' + xhr.responseJSON.message;
            }
            alert(msg);
        },
        complete: function() {
            btnSubmit.prop('disabled', false).text(originalText);
            $('#modalCambiarEstado').modal('hide');
        }
    });
});
</script>
@endsection