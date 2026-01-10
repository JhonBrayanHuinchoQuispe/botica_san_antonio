@extends('layout.layout')
@php
    $title = 'Boleta de Venta';
    $subTitle = 'Parámetros de salida para boleta y tickets';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/admin/configuracion.js') . '?v=' . time() . '"></script>
    ';
@endphp

@section('content')
<style>
.printer-card { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border: none; border-radius: 20px; overflow: hidden; }
.printer-body { background: #ffffff; padding: 2.5rem; margin: 0; }
.printer-body .form-control { width: 100%; padding: 1rem 1.25rem; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc; transition: border-color .3s ease, box-shadow .3s ease; }
.printer-body .form-control:focus { outline: none; border-color: #7c3aed; background: #ffffff; box-shadow: 0 0 0 3px rgba(124,58,237,.12); }
.printer-body select.form-control { appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e"); background-position: right .75rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; }
.btn-modern { padding: .875rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: .95rem; border: none; display: inline-flex; align-items: center; gap: .65rem; }
.btn-primary-modern { background: #ef4444; color: #fff; box-shadow: 0 4px 12px rgba(239,68,68,.25); }
.btn-primary-modern:hover { background: #dc2626; box-shadow: 0 6px 16px rgba(220,38,38,.28); }
.btn-outline-modern { background: #fff; border: 2px solid #e2e8f0; color: #374151; border-radius: 10px; padding: .75rem 1.25rem; }
.btn-outline-modern:hover { background: #f9fafb; color: #1f2937; }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm printer-card">
                <div class="card-header" style="background:#ffffff; border-bottom:1px solid #e2e8f0; padding:1rem;">
                    <div class="flex items-center gap-2">
                        <iconify-icon icon="solar:printer-bold-duotone" class="text-2xl"></iconify-icon>
                        <h3 class="text-lg font-semibold">Boleta de Venta</h3>
                    </div>
                </div>
                <div class="printer-body">
                    <form id="formConfiguracionImpresoras" onsubmit="guardarConfiguracionImpresoras(event)">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Impresora Principal -->
                            <div>
                                <label class="form-label">Boleta (A4) - Dispositivo principal</label>
                                <input type="text" name="impresora_principal" id="impresora_principal" 
                                       class="form-control" 
                                       value="{{ $configuracion->impresora_principal ?? '' }}"
                                       placeholder="Nombre del dispositivo de impresión para boleta">
                                <small class="text-muted">Dispositivo predeterminado para imprimir boletas (A4)</small>
                            </div>

                            <!-- Impresora de Tickets -->
                            <div>
                                <label class="form-label">Tickets - Dispositivo de impresión</label>
                                <input type="text" name="impresora_tickets" id="impresora_tickets" 
                                       class="form-control" 
                                       value="{{ $configuracion->impresora_tickets ?? '' }}"
                                       placeholder="Nombre de la impresora de tickets">
                                <small class="text-muted">Dispositivo específico para tickets térmicos</small>
                            </div>

                            <!-- Impresora de Reportes -->
                            <div>
                                <label class="form-label">Reportes - Dispositivo de impresión</label>
                                <input type="text" name="impresora_reportes" id="impresora_reportes" 
                                       class="form-control" 
                                       value="{{ $configuracion->impresora_reportes ?? '' }}"
                                       placeholder="Nombre de la impresora de reportes">
                                <small class="text-muted">Dispositivo para reportes y documentos largos</small>
                            </div>

                            <!-- Impresión Automática -->
                            <div>
                                <label class="form-label">Impresión automática</label>
                                <select name="imprimir_automatico" id="imprimir_automatico" class="form-control">
                                    <option value="1" {{ $configuracion->imprimir_automatico ? 'selected' : '' }}>Habilitado</option>
                                    <option value="0" {{ !$configuracion->imprimir_automatico ? 'selected' : '' }}>Deshabilitado</option>
                                </select>
                                <small class="text-muted">Imprimir automáticamente después de cada venta</small>
                            </div>

                            <!-- Copias de Ticket -->
                            <div>
                                <label class="form-label">Copias de ticket</label>
                                <input type="number" name="copias_ticket" id="copias_ticket" 
                                       class="form-control" 
                                       value="{{ $configuracion->copias_ticket ?? 1 }}"
                                       min="1" max="5">
                                <small class="text-muted">Número de copias a imprimir por ticket</small>
                            </div>

                            <!-- Ancho del Papel -->
                            <div>
                                <label class="form-label">Ancho del Papel (mm)</label>
                                <select name="papel_ticket_ancho" id="papel_ticket_ancho" class="form-control">
                                    <option value="58" {{ ($configuracion->papel_ticket_ancho ?? 80) == 58 ? 'selected' : '' }}>58mm</option>
                                    <option value="80" {{ ($configuracion->papel_ticket_ancho ?? 80) == 80 ? 'selected' : '' }}>80mm</option>
                                </select>
                                <small class="text-muted">Ancho del papel para tickets</small>
                            </div>
                        </div>

                        <!-- Configuración tipo Tickets para Boleta -->
                        <div class="mt-8">
                            <h4 class="text-md font-semibold mb-4">Configuración de Boleta electrónica (estilo Tickets)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="form-label">Mostrar Logo en Boleta</label>
                                    <select name="boleta_mostrar_logo" id="boleta_mostrar_logo" class="form-control">
                                        <option value="1">Sí, mostrar logo</option>
                                        <option value="0">No mostrar logo</option>
                                    </select>
                                    <small class="text-muted">Incluir el logo de la empresa en la boleta</small>
                                </div>

                                <div>
                                    <label class="form-label">Mostrar Dirección</label>
                                    <select name="boleta_mostrar_direccion" id="boleta_mostrar_direccion" class="form-control">
                                        <option value="1">Sí, mostrar dirección</option>
                                        <option value="0">No mostrar dirección</option>
                                    </select>
                                    <small class="text-muted">Incluir la dirección de la empresa</small>
                                </div>

                                <div>
                                    <label class="form-label">Mostrar Teléfono</label>
                                    <select name="boleta_mostrar_telefono" id="boleta_mostrar_telefono" class="form-control">
                                        <option value="1">Sí, mostrar teléfono</option>
                                        <option value="0">No mostrar teléfono</option>
                                    </select>
                                    <small class="text-muted">Incluir el teléfono de contacto</small>
                                </div>

                                <div>
                                    <label class="form-label">Mostrar Desglose IGV</label>
                                    <select name="boleta_mostrar_igv" id="boleta_mostrar_igv" class="form-control">
                                        <option value="1">Sí, mostrar IGV</option>
                                        <option value="0">No mostrar IGV</option>
                                    </select>
                                    <small class="text-muted">Mostrar el desglose del IGV en la boleta</small>
                                </div>

                                <div>
                                    <label class="form-label">Ancho del Papel (mm)</label>
                                    <select name="boleta_ancho_papel" id="boleta_ancho_papel" class="form-control">
                                        <option value="58">58mm</option>
                                        <option value="80" selected>80mm</option>
                                    </select>
                                    <small class="text-muted">Ancho del papel para la boleta/ticket</small>
                                </div>

                                <div>
                                    <label class="form-label">Margen Superior (mm)</label>
                                    <input type="number" name="boleta_margen_superior" id="boleta_margen_superior" 
                                           class="form-control" value="5" min="0" max="20">
                                    <small class="text-muted">Espacio en la parte superior</small>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="form-label">Mensaje del Pie de Página</label>
                                <textarea name="boleta_mensaje_pie" id="boleta_mensaje_pie" class="form-control" rows="3" placeholder="Mensaje personalizado para el pie de la boleta"></textarea>
                                <small class="text-muted">Mensaje que aparecerá al final de la boleta (máximo 500 caracteres)</small>
                            </div>
                        </div>

                        <!-- Sección de Pruebas -->
                            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-md font-semibold mb-3">Pruebas de salida</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <button type="button" class="btn-outline-modern" onclick="probarImpresora('principal')">Probar Principal</button>
                                <button type="button" class="btn-outline-modern" onclick="probarImpresora('tickets')">Probar Tickets</button>
                                <button type="button" class="btn-outline-modern" onclick="probarImpresora('reportes')">Probar Reportes</button>
                            </div>
                        </div>

                        <!-- Información de Estado -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                            <h4 class="text-md font-semibold mb-3">Estado de dispositivos</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl mb-2">
                                        <iconify-icon icon="solar:printer-bold-duotone" class="text-blue-600"></iconify-icon>
                                    </div>
                                    <p class="text-sm font-medium">Principal</p>
                                    <span class="badge badge-success" id="estado-principal">Conectada</span>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl mb-2">
                                        <iconify-icon icon="solar:ticket-bold-duotone" class="text-purple-600"></iconify-icon>
                                    </div>
                                    <p class="text-sm font-medium">Tickets</p>
                                    <span class="badge badge-success" id="estado-tickets">Conectada</span>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl mb-2">
                                        <iconify-icon icon="solar:document-bold-duotone" class="text-green-600"></iconify-icon>
                                    </div>
                                    <p class="text-sm font-medium">Reportes</p>
                                    <span class="badge badge-success" id="estado-reportes">Conectada</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <a href="{{ route('admin.configuracion') }}" class="btn-outline-modern">Volver</a>
                            <button type="submit" class="btn-modern btn-primary-modern">Guardar Configuración</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function guardarConfiguracionImpresoras(event) {
    event.preventDefault();
    
    const form = document.getElementById('formConfiguracionImpresoras');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('{{ route("admin.configuracion.impresoras.actualizar") }}', {
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

async function probarImpresora(tipo) {
    let impresora = '';
    
    switch(tipo) {
        case 'principal':
            impresora = document.getElementById('impresora_principal').value;
            break;
        case 'tickets':
            impresora = document.getElementById('impresora_tickets').value;
            break;
        case 'reportes':
            impresora = document.getElementById('impresora_reportes').value;
            break;
    }
    
    if (!impresora) {
        Swal.fire({
            icon: 'warning',
            title: 'Advertencia',
            text: 'Por favor, configure primero el nombre de la impresora'
        });
        return;
    }
    
    try {
        const response = await fetch('{{ route("admin.configuracion.impresoras.probar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ impresora: impresora })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Prueba Exitosa!',
                text: result.message,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            throw new Error(result.message || 'Error al probar la impresora');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al probar la impresora'
        });
    }
}
</script>
@endsection