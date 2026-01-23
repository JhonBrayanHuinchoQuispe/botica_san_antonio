@extends('layout.layout')
@php
    $title = 'Configuración de Comprobantes';
    $subTitle = 'Numeración y configuración de comprobantes electrónicos';
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
                <div class="card-header bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-t-lg p-4">
                    <div class="flex items-center gap-2">
                        <iconify-icon icon="solar:document-add-bold-duotone" class="text-2xl"></iconify-icon>
                        <h3 class="text-lg font-semibold">Configuración de Comprobantes</h3>
                    </div>
                </div>
                <div class="card-body p-6">
                    <form id="formConfiguracionComprobantes" onsubmit="guardarConfiguracionComprobantes(event)">
                        @csrf
                        
                        
                        <div class="mb-6">
                            <h5 class="font-semibold mb-4">
                                <iconify-icon icon="solar:hashtag-bold-duotone" class="mr-2"></iconify-icon>
                                Numeración de Comprobantes
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Serie de Boletas</label>
                                    <input type="text" name="serie_boleta" id="serie_boleta" 
                                           class="form-control" 
                                           value="{{ $configuracion->serie_boleta ?? 'B001' }}"
                                           placeholder="B001" 
                                           pattern="[A-Z][0-9]{3}" 
                                           maxlength="4">
                                    <small class="text-muted">Formato: B001</small>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Numeración Actual - Boletas</label>
                                    <input type="number" name="numeracion_boleta" id="numeracion_boleta" 
                                           class="form-control" 
                                           value="{{ $configuracion->numeracion_boleta ?? 1 }}"
                                           min="1" max="99999999">
                                    <small class="text-muted">Próximo número a usar</small>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" 
                                               name="boleta_electronica" id="boleta_electronica" 
                                               value="1" 
                                               {{ ($configuracion->boleta_electronica ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="boleta_electronica">
                                            Boleta electrónica
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <label class="form-label">Serie de Facturas</label>
                                    <input type="text" name="serie_factura" id="serie_factura" 
                                           class="form-control" 
                                           value="{{ $configuracion->serie_factura ?? 'F001' }}"
                                           placeholder="F001" 
                                           pattern="[A-Z][0-9]{3}" 
                                           maxlength="4">
                                    <small class="text-muted">Formato: F001</small>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Numeración Actual - Facturas</label>
                                    <input type="number" name="numeracion_factura" id="numeracion_factura" 
                                           class="form-control" 
                                           value="{{ $configuracion->numeracion_factura ?? 1 }}"
                                           min="1" max="99999999">
                                    <small class="text-muted">Próximo número a usar</small>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" 
                                               name="factura_electronica" id="factura_electronica" 
                                               value="1" 
                                               {{ ($configuracion->factura_electronica ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="factura_electronica">
                                            Factura electrónica
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <label class="form-label">Serie de Tickets</label>
                                    <input type="text" name="serie_ticket" id="serie_ticket" 
                                           class="form-control" 
                                           value="{{ $configuracion->serie_ticket ?? 'T001' }}"
                                           placeholder="T001" 
                                           pattern="[A-Z][0-9]{3}" 
                                           maxlength="4">
                                    <small class="text-muted">Formato: T001</small>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Numeración Actual - Tickets</label>
                                    <input type="number" name="numeracion_ticket" id="numeracion_ticket" 
                                           class="form-control" 
                                           value="{{ $configuracion->numeracion_ticket ?? 1 }}"
                                           min="1" max="99999999">
                                    <small class="text-muted">Próximo número a usar</small>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="alert alert-info mt-2">
                                        <small>Los tickets son para uso interno</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="mb-6">
                            <h5 class="font-semibold mb-4">
                                <iconify-icon icon="solar:cloud-upload-bold-duotone" class="mr-2"></iconify-icon>
                                Comprobantes Electrónicos
                            </h5>
                            
                            <div class="row">

                                
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               name="generar_pdf_automatico" id="generar_pdf_automatico" 
                                               value="1" 
                                               {{ ($configuracion->generar_pdf_automatico ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="generar_pdf_automatico">
                                            <strong>Generar PDF automáticamente</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Crear archivo PDF del comprobante automáticamente</small>
                                </div>
                            </div>
                        </div>

                        
                        <div class="mb-6">
                            <h5 class="font-semibold mb-4">
                                <iconify-icon icon="solar:document-text-bold-duotone" class="mr-2"></iconify-icon>
                                Formato de Comprobantes
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Tamaño de papel</label>
                                    <select name="tamano_papel_comprobante" id="tamano_papel_comprobante" class="form-control">
                                        <option value="A4" {{ ($configuracion->tamano_papel_comprobante ?? 'A4') == 'A4' ? 'selected' : '' }}>A4</option>
                                        <option value="A5" {{ ($configuracion->tamano_papel_comprobante ?? 'A4') == 'A5' ? 'selected' : '' }}>A5</option>
                                        <option value="ticket" {{ ($configuracion->tamano_papel_comprobante ?? 'A4') == 'ticket' ? 'selected' : '' }}>Ticket (80mm)</option>
                                    </select>
                                    <small class="text-muted">Tamaño del papel para imprimir comprobantes</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Orientación</label>
                                    <select name="orientacion_comprobante" id="orientacion_comprobante" class="form-control">
                                        <option value="portrait" {{ ($configuracion->orientacion_comprobante ?? 'portrait') == 'portrait' ? 'selected' : '' }}>Vertical</option>
                                        <option value="landscape" {{ ($configuracion->orientacion_comprobante ?? 'portrait') == 'landscape' ? 'selected' : '' }}>Horizontal</option>
                                    </select>
                                    <small class="text-muted">Orientación del papel</small>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               name="mostrar_codigo_qr" id="mostrar_codigo_qr" 
                                               value="1" 
                                               {{ ($configuracion->mostrar_codigo_qr ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="mostrar_codigo_qr">
                                            Mostrar código QR
                                        </label>
                                    </div>
                                    <small class="text-muted">Incluir código QR en comprobantes electrónicos</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               name="mostrar_hash" id="mostrar_hash" 
                                               value="1" 
                                               {{ ($configuracion->mostrar_hash ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="mostrar_hash">
                                            Mostrar hash de validación
                                        </label>
                                    </div>
                                    <small class="text-muted">Incluir hash de validación SUNAT</small>
                                </div>
                            </div>
                        </div>

                        
                        <div class="mb-6">
                            <h5 class="font-semibold mb-4">
                                <iconify-icon icon="solar:copy-bold-duotone" class="mr-2"></iconify-icon>
                                Configuración de Copias
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Copias de Boletas</label>
                                    <input type="number" name="copias_boleta" id="copias_boleta" 
                                           class="form-control" 
                                           value="{{ $configuracion->copias_boleta ?? 1 }}"
                                           min="1" max="5">
                                    <small class="text-muted">Número de copias a imprimir</small>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Copias de Facturas</label>
                                    <input type="number" name="copias_factura" id="copias_factura" 
                                           class="form-control" 
                                           value="{{ $configuracion->copias_factura ?? 2 }}"
                                           min="1" max="5">
                                    <small class="text-muted">Número de copias a imprimir</small>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Copias de Tickets</label>
                                    <input type="number" name="copias_ticket" id="copias_ticket" 
                                           class="form-control" 
                                           value="{{ $configuracion->copias_ticket ?? 1 }}"
                                           min="1" max="3">
                                    <small class="text-muted">Número de copias a imprimir</small>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center gap-3 mt-6">
                            <div>
                                <button type="button" class="btn btn-info" onclick="previsualizarComprobante()">
                                    <iconify-icon icon="solar:eye-bold" class="mr-2"></iconify-icon>
                                    Vista Previa
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

        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-gray-100 p-4">
                    <h4 class="text-md font-semibold">Próximos Números</h4>
                </div>
                <div class="card-body p-4">
                    <div class="space-y-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Próxima Boleta:</span>
                            <span class="badge bg-primary" id="proximo-boleta">
                                {{ ($configuracion->serie_boleta ?? 'B001') }}-{{ str_pad($configuracion->numeracion_boleta ?? 1, 8, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Próxima Factura:</span>
                            <span class="badge bg-success" id="proximo-factura">
                                {{ ($configuracion->serie_factura ?? 'F001') }}-{{ str_pad($configuracion->numeracion_factura ?? 1, 8, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Próximo Ticket:</span>
                            <span class="badge bg-info" id="proximo-ticket">
                                {{ ($configuracion->serie_ticket ?? 'T001') }}-{{ str_pad($configuracion->numeracion_ticket ?? 1, 8, '0', STR_PAD_LEFT) }}
                            </span>
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
                            <strong>Series:</strong> Deben seguir el formato establecido por el sistema.
                        </div>
                        
                        <div class="alert alert-warning">
                            <iconify-icon icon="solar:danger-triangle-bold" class="mr-2"></iconify-icon>
                            <strong>Numeración:</strong> No se puede retroceder una vez utilizada.
                        </div>
                        
                        <div class="alert alert-success">
                            <iconify-icon icon="solar:check-circle-bold" class="mr-2"></iconify-icon>
                            <strong>Backup:</strong> Se recomienda hacer respaldo antes de cambios importantes.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function actualizarProximosNumeros() {
    const serieBoleta = document.getElementById('serie_boleta').value;
    const numeracionBoleta = document.getElementById('numeracion_boleta').value;
    const serieFactura = document.getElementById('serie_factura').value;
    const numeracionFactura = document.getElementById('numeracion_factura').value;
    const serieTicket = document.getElementById('serie_ticket').value;
    const numeracionTicket = document.getElementById('numeracion_ticket').value;
    
    document.getElementById('proximo-boleta').textContent = 
        `${serieBoleta}-${numeracionBoleta.toString().padStart(8, '0')}`;
    document.getElementById('proximo-factura').textContent = 
        `${serieFactura}-${numeracionFactura.toString().padStart(8, '0')}`;
    document.getElementById('proximo-ticket').textContent = 
        `${serieTicket}-${numeracionTicket.toString().padStart(8, '0')}`;
}

function previsualizarComprobante() {
    window.open('{{ route("admin.configuracion.comprobantes.vista-previa") }}', '_blank', 'width=800,height=600');
}

async function guardarConfiguracionComprobantes(event) {
    event.preventDefault();
    
    const form = document.getElementById('formConfiguracionComprobantes');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('{{ route("admin.configuracion.comprobantes.actualizar") }}', {
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

            actualizarProximosNumeros();
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

document.getElementById('serie_boleta').addEventListener('input', function(e) {
    let value = e.target.value.toUpperCase();
    if (value.length > 4) value = value.substring(0, 4);
    e.target.value = value;
    actualizarProximosNumeros();
});

document.getElementById('serie_factura').addEventListener('input', function(e) {
    let value = e.target.value.toUpperCase();
    if (value.length > 4) value = value.substring(0, 4);
    e.target.value = value;
    actualizarProximosNumeros();
});

document.getElementById('serie_ticket').addEventListener('input', function(e) {
    let value = e.target.value.toUpperCase();
    if (value.length > 4) value = value.substring(0, 4);
    e.target.value = value;
    actualizarProximosNumeros();
});

document.getElementById('numeracion_boleta').addEventListener('input', actualizarProximosNumeros);
document.getElementById('numeracion_factura').addEventListener('input', actualizarProximosNumeros);
document.getElementById('numeracion_ticket').addEventListener('input', actualizarProximosNumeros);

document.addEventListener('DOMContentLoaded', function() {
    actualizarProximosNumeros();
});
</script>
@endsection