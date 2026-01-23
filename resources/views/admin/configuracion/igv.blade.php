@extends('layout.layout')
@php
    $title = 'Configuración IGV';
    $subTitle = 'Gestión de parámetros del IGV';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/admin/configuracion.js') . '?v=' . time() . '">
    ';
@endphp

@section('content')
<style>
.igv-card {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    border-radius: 20px;
    overflow: hidden;
}

.igv-header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: none;
    padding: 2rem;
}

.igv-body {
    background: #ffffff;
    padding: 2.5rem;
    margin: 0;
}

.form-group-modern {
    position: relative;
    margin-bottom: 2rem;
}

.form-control-modern {
    width: 100%;
    padding: 1rem 1.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    background: #f8fafc;
}

select.form-control-modern {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    background-attachment: local;
}

select.form-control-modern:focus {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    background-attachment: local;
}

select.form-control-modern:active {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    background-attachment: local;
}

.form-control-modern:focus {
    outline: none;
    border-color: #10b981;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-label-modern {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: #2d3748;
    font-size: 0.95rem;
}

.form-help-text {
    font-size: 0.85rem;
    color: #64748b;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-modern {
    padding: 0.875rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
}
</style>

<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 xl:grid-cols-1 gap-8">
        <div class="xl:col-span-1">
            <div class="card igv-card">
                <div class="card-header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 1rem;">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-50 rounded-full">
                            <iconify-icon icon="solar:calculator-bold-duotone" class="text-2xl text-green-600"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 mb-1">Configuración del IGV</h3>
                            <p class="text-gray-600 mb-0">Gestiona los parámetros del Impuesto General a las Ventas</p>
                        </div>
                    </div>
                </div>
                <div class="igv-body">
                    <form id="formConfiguracionIgv" onsubmit="guardarConfiguracionIgv(event)">
                        @csrf
                        <div class="space-y-8 max-w-6xl">
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <iconify-icon icon="solar:power-bold-duotone" class="text-lg mr-2"></iconify-icon>
                                        Estado del IGV *
                                    </label>
                                    <select name="igv_habilitado" id="igv_habilitado" class="form-control-modern">
                                        <option value="1" {{ $configuracion->igv_habilitado ? 'selected' : '' }}>Habilitado</option>
                                        <option value="0" {{ !$configuracion->igv_habilitado ? 'selected' : '' }}>Deshabilitado</option>
                                    </select>
                                    <div class="form-help-text">
                                        <iconify-icon icon="solar:info-circle-linear"></iconify-icon>
                                        Activar o desactivar el cálculo automático del IGV
                                    </div>
                                </div>

                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <iconify-icon icon="solar:percent-bold-duotone" class="text-lg mr-2"></iconify-icon>
                                        Porcentaje del IGV (%) *
                                    </label>
                                    <input type="number" name="igv_porcentaje" id="igv_porcentaje" 
                                           class="form-control-modern" 
                                           value="{{ $configuracion->igv_porcentaje }}"
                                           min="0" max="100" step="0.01" 
                                           placeholder="18.00" required>
                                    <div class="form-help-text">
                                        <iconify-icon icon="solar:calculator-linear"></iconify-icon>
                                        Porcentaje estándar del IGV (ejemplo: 18.00)
                                    </div>
                                </div>
                            </div>

                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <iconify-icon icon="solar:tag-bold-duotone" class="text-lg mr-2"></iconify-icon>
                                        Nombre del Impuesto *
                                    </label>
                                    <input type="text" name="igv_nombre" id="igv_nombre" 
                                           class="form-control-modern" 
                                           value="{{ $configuracion->igv_nombre }}"
                                           maxlength="50" 
                                           placeholder="IGV" required>
                                    <div class="form-help-text">
                                        <iconify-icon icon="solar:document-text-linear"></iconify-icon>
                                        Nombre que aparecerá en facturas y comprobantes
                                    </div>
                                </div>

                                
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <iconify-icon icon="solar:ticket-bold-duotone" class="text-lg mr-2"></iconify-icon>
                                        Mostrar IGV en Tickets
                                    </label>
                                    <select name="mostrar_igv_tickets" id="mostrar_igv_tickets" class="form-control-modern">
                                        <option value="1" {{ $configuracion->mostrar_igv_tickets ? 'selected' : '' }}>Sí, mostrar desglose</option>
                                        <option value="0" {{ !$configuracion->mostrar_igv_tickets ? 'selected' : '' }}>No mostrar desglose</option>
                                    </select>
                                    <div class="form-help-text">
                                        <iconify-icon icon="solar:receipt-linear"></iconify-icon>
                                        Mostrar el desglose detallado del IGV en tickets
                                    </div>
                                </div>
                            </div>

                            
                        </div>

                        <div class="flex justify-end mt-8">
                            <button type="submit" class="btn-modern btn-primary-modern">
                                <iconify-icon icon="solar:diskette-bold" class="text-lg"></iconify-icon>
                                Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

async function guardarConfiguracionIgv(event) {
    event.preventDefault();
    
    const form = document.getElementById('formConfiguracionIgv');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('{{ route("admin.configuracion.igv.actualizar") }}', {
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

document.addEventListener('DOMContentLoaded', function() {

});
</script>
@endsection