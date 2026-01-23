@extends('layout.layout')
@php
    $title = 'Configuración de Tickets';
    $subTitle = 'Personalización de tickets de venta';
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
                <div class="card-header bg-gradient-to-r from-green-600 to-green-700 text-white rounded-t-lg p-4">
                    <div class="flex items-center gap-2">
                        <iconify-icon icon="solar:ticket-bold-duotone" class="text-2xl"></iconify-icon>
                        <h3 class="text-lg font-semibold">Configuración de Tickets</h3>
                    </div>
                </div>
                <div class="card-body p-6">
                    <form id="formConfiguracionTickets" onsubmit="guardarConfiguracionTickets(event)">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div>
                                <label class="form-label">Mostrar Logo en Ticket</label>
                                <select name="ticket_mostrar_logo" id="ticket_mostrar_logo" class="form-control" onchange="actualizarVistaPrevia()">
                                    <option value="1" {{ ($configuracion->ticket_mostrar_logo ?? true) ? 'selected' : '' }}>Sí, mostrar logo</option>
                                    <option value="0" {{ !($configuracion->ticket_mostrar_logo ?? true) ? 'selected' : '' }}>No mostrar logo</option>
                                </select>
                                <small class="text-muted">Incluir el logo de la empresa en el ticket</small>
                            </div>

                            
                            <div>
                                <label class="form-label">Mostrar Dirección</label>
                                <select name="ticket_mostrar_direccion" id="ticket_mostrar_direccion" class="form-control" onchange="actualizarVistaPrevia()">
                                    <option value="1" {{ ($configuracion->ticket_mostrar_direccion ?? true) ? 'selected' : '' }}>Sí, mostrar dirección</option>
                                    <option value="0" {{ !($configuracion->ticket_mostrar_direccion ?? true) ? 'selected' : '' }}>No mostrar dirección</option>
                                </select>
                                <small class="text-muted">Incluir la dirección de la empresa</small>
                            </div>

                            
                            <div>
                                <label class="form-label">Mostrar Teléfono</label>
                                <select name="ticket_mostrar_telefono" id="ticket_mostrar_telefono" class="form-control" onchange="actualizarVistaPrevia()">
                                    <option value="1" {{ ($configuracion->ticket_mostrar_telefono ?? true) ? 'selected' : '' }}>Sí, mostrar teléfono</option>
                                    <option value="0" {{ !($configuracion->ticket_mostrar_telefono ?? true) ? 'selected' : '' }}>No mostrar teléfono</option>
                                </select>
                                <small class="text-muted">Incluir el teléfono de contacto</small>
                            </div>

                            
                            <div>
                                <label class="form-label">Mostrar Desglose IGV</label>
                                <select name="ticket_mostrar_igv" id="ticket_mostrar_igv" class="form-control" onchange="actualizarVistaPrevia()">
                                    <option value="1" {{ ($configuracion->ticket_mostrar_igv ?? true) ? 'selected' : '' }}>Sí, mostrar IGV</option>
                                    <option value="0" {{ !($configuracion->ticket_mostrar_igv ?? true) ? 'selected' : '' }}>No mostrar IGV</option>
                                </select>
                                <small class="text-muted">Mostrar el desglose del IGV en el ticket</small>
                            </div>

                            
                            <div>
                                <label class="form-label">Ancho del Papel (mm)</label>
                                <select name="ticket_ancho_papel" id="ticket_ancho_papel" class="form-control" onchange="actualizarVistaPrevia()">
                                    <option value="58" {{ ($configuracion->ticket_ancho_papel ?? 80) == 58 ? 'selected' : '' }}>58mm</option>
                                    <option value="80" {{ ($configuracion->ticket_ancho_papel ?? 80) == 80 ? 'selected' : '' }}>80mm</option>
                                </select>
                                <small class="text-muted">Ancho del papel para el ticket</small>
                            </div>

                            
                            <div>
                                <label class="form-label">Margen Superior (mm)</label>
                                <input type="number" name="ticket_margen_superior" id="ticket_margen_superior" 
                                       class="form-control" 
                                       value="{{ $configuracion->ticket_margen_superior ?? 5 }}"
                                       min="0" max="20" onchange="actualizarVistaPrevia()">
                                <small class="text-muted">Espacio en la parte superior del ticket</small>
                            </div>

                            
                            <div>
                                <label class="form-label">Margen Inferior (mm)</label>
                                <input type="number" name="ticket_margen_inferior" id="ticket_margen_inferior" 
                                       class="form-control" 
                                       value="{{ $configuracion->ticket_margen_inferior ?? 5 }}"
                                       min="0" max="20" onchange="actualizarVistaPrevia()">
                                <small class="text-muted">Espacio en la parte inferior del ticket</small>
                            </div>
                        </div>

                        
                        <div class="mt-6">
                            <label class="form-label">Mensaje del Pie de Página</label>
                            <textarea name="ticket_mensaje_pie" id="ticket_mensaje_pie" 
                                      class="form-control" rows="3" 
                                      placeholder="Mensaje personalizado para el pie del ticket"
                                      onchange="actualizarVistaPrevia()">{{ $configuracion->ticket_mensaje_pie ?? '' }}</textarea>
                            <small class="text-muted">Mensaje que aparecerá al final del ticket (máximo 500 caracteres)</small>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <a href="{{ route('admin.configuracion') }}" class="btn btn-secondary">
                                <iconify-icon icon="solar:arrow-left-bold" class="mr-2"></iconify-icon>
                                Volver
                            </a>
                            <button type="button" class="btn btn-info" onclick="mostrarVistaPrevia()">
                                <iconify-icon icon="solar:eye-bold" class="mr-2"></iconify-icon>
                                Vista Previa
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="solar:diskette-bold" class="mr-2"></iconify-icon>
                                Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-gray-100 p-4">
                    <h4 class="text-md font-semibold">Vista Previa del Ticket</h4>
                </div>
                <div class="card-body p-4">
                    <div id="ticket-preview" class="ticket-preview bg-white border rounded p-3" style="font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.2;">
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ticket-preview {
    max-width: 300px;
    margin: 0 auto;
    background: white;
    border: 1px solid #ddd;
    padding: 10px;
    font-family: 'Courier New', monospace;
    font-size: 11px;
    line-height: 1.3;
}

.ticket-58mm {
    max-width: 200px;
}

.ticket-80mm {
    max-width: 280px;
}

.ticket-header {
    text-align: center;
    border-bottom: 1px dashed #000;
    padding-bottom: 5px;
    margin-bottom: 5px;
}

.ticket-footer {
    text-align: center;
    border-top: 1px dashed #000;
    padding-top: 5px;
    margin-top: 5px;
}
</style>

<script>
function actualizarVistaPrevia() {
    const preview = document.getElementById('ticket-preview');
    const ancho = document.getElementById('ticket_ancho_papel').value;
    const mostrarLogo = document.getElementById('ticket_mostrar_logo').value === '1';
    const mostrarDireccion = document.getElementById('ticket_mostrar_direccion').value === '1';
    const mostrarTelefono = document.getElementById('ticket_mostrar_telefono').value === '1';
    const mostrarIgv = document.getElementById('ticket_mostrar_igv').value === '1';
    const mensajePie = document.getElementById('ticket_mensaje_pie').value;

    preview.className = `ticket-preview bg-white border rounded p-3 ticket-${ancho}mm`;
    
    let html = `
        <div class="ticket-header">
            ${mostrarLogo ? '<div style="margin-bottom: 5px;">[LOGO EMPRESA]</div>' : ''}
            <div style="font-weight: bold;">FARMACIA SAN ANTONIO</div>
            <div>RUC: 20123456789</div>
            ${mostrarDireccion ? '<div>Av. Principal 123, Lima</div>' : ''}
            ${mostrarTelefono ? '<div>Tel: (01) 234-5678</div>' : ''}
        </div>
        
        <div style="margin: 10px 0;">
            <div>Ticket: T001-00001</div>
            <div>Fecha: ${new Date().toLocaleDateString()}</div>
            <div>Hora: ${new Date().toLocaleTimeString()}</div>
        </div>
        
        <div style="border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px;">
            <div>Paracetamol 500mg x2    S/ 10.00</div>
            <div>Ibuprofeno 400mg x1     S/ 15.50</div>
        </div>
        
        <div style="text-align: right;">
            ${mostrarIgv ? '<div>Subtotal: S/ 21.61</div><div>IGV (18%): S/ 3.89</div>' : ''}
            <div style="font-weight: bold;">TOTAL: S/ 25.50</div>
        </div>
        
        ${mensajePie ? `<div class="ticket-footer">${mensajePie}</div>` : ''}
        
        <div class="ticket-footer">
            <div>¡Gracias por su compra!</div>
        </div>
    `;
    
    preview.innerHTML = html;
}

async function guardarConfiguracionTickets(event) {
    event.preventDefault();
    
    const form = document.getElementById('formConfiguracionTickets');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('{{ route("admin.configuracion.tickets.actualizar") }}', {
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

function mostrarVistaPrevia() {
    window.open('{{ route("admin.configuracion.tickets.vista-previa") }}', '_blank', 'width=400,height=600');
}

document.addEventListener('DOMContentLoaded', function() {
    actualizarVistaPrevia();
});
</script>
@endsection