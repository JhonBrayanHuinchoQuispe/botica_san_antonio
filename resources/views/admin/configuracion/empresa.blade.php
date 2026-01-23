@extends('layout.layout')
@php
    $title = 'Configuración de Empresa';
    $subTitle = 'Datos generales de la empresa';
    $script = '
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="' . asset('assets/js/admin/configuracion.js') . '?v=' . time() . '"></script>
    ';
@endphp

@section('content')
<style>
.empresa-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(102, 126, 234, 0.15);
    overflow: hidden;
}

.empresa-header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: none;
    padding: 2rem;
}

.empresa-body {
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
    transition: all 0.3s ease;
    background: #f8fafc;
}

.form-control-modern:focus {
    outline: none;
    border-color: #667eea;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

.logo-upload-area {
    border: 2px dashed #cbd5e0;
    border-radius: 12px;
    padding: 2.5rem;
    text-align: center;
    background: #f8fafc;
    transition: all 0.3s ease;
}

.logo-upload-area:hover {
    border-color: #667eea;
    background: #f0f4ff;
}

.logo-preview-container {
    background: #ffffff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem 2rem;
    text-align: center;
    min-height: 220px; 
    display: flex;
    align-items: center;
    justify-content: center;
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
    gap: 0.75rem; 
}

.btn-modern iconify-icon {
    display: inline-block;
    line-height: 1;
    vertical-align: middle; 
    font-size: 1.1rem; 
}

.btn-primary-modern {
    background: #ef4444; 
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
}

.btn-primary-modern:hover {
    background: #dc2626; 
    box-shadow: 0 6px 16px rgba(220, 38, 38, 0.28);
}

.btn-secondary-modern {
    background: #f1f5f9;
    color: #64748b;
    border: 2px solid #e2e8f0;
}

.btn-secondary-modern:hover {
    background: #e2e8f0;
    color: #475569;
}

.info-card-modern {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    overflow: hidden;
}

.info-header-modern {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.alert-modern {
    padding: 1rem 1.25rem;
    border-radius: 10px;
    border: none;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.alert-info-modern {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
}

.alert-warning-modern {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
}

.alert-success-modern {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.current-data-card {
    background: #f8fafc;
    border-radius: 10px;
    padding: 1.25rem;
    border: 1px solid #e2e8f0;
}

.data-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.data-item:last-child {
    border-bottom: none;
}

.data-label {
    font-weight: 600;
    color: #475569;
}

.data-value {
    color: #1e293b;
    font-weight: 500;
}
</style>

<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
        <div class="xl:col-span-3">
            <div class="card empresa-card">
                <div class="card-header" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 1rem;">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-50 rounded-full">
                            <iconify-icon icon="solar:buildings-2-bold-duotone" class="text-2xl text-blue-600"></iconify-icon>
                        </div>
                        <p class="text-gray-600 mb-0">Gestiona la información básica de tu empresa</p>
                    </div>
                </div>
                <div class="empresa-body">
                    <form id="formConfiguracionEmpresa" onsubmit="guardarConfiguracionEmpresa(event)">
                        @csrf
                        
                        <div class="space-y-8 max-w-6xl">
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <iconify-icon icon="solar:buildings-bold-duotone" class="text-lg mr-2"></iconify-icon>
                                        Razón Social *
                                    </label>
                                    <input type="text" name="empresa_nombre" id="empresa_nombre" 
                                           class="form-control-modern w-full" 
                                           value="{{ $configuracion->empresa_nombre ?? '' }}"
                                           placeholder="Ingrese la razón social" required>
                                    <div class="form-help-text">
                                        <iconify-icon icon="solar:info-circle-linear"></iconify-icon>
                                        Este nombre aparecerá en todos los documentos oficiales
                                    </div>
                                </div>
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <iconify-icon icon="solar:shop-bold-duotone" class="text-lg mr-2"></iconify-icon>
                                        Nombre Comercial
                                    </label>
                                    <input type="text" name="empresa_nombre_comercial" id="empresa_nombre_comercial"
                                           class="form-control-modern w-full"
                                           value="{{ $configuracion->empresa_nombre_comercial ?? '' }}"
                                           placeholder="Ingrese el nombre comercial (si aplica)">
                                    <div class="form-help-text">
                                        <iconify-icon icon="solar:info-circle-linear"></iconify-icon>
                                        Nombre visible para clientes
                                    </div>
                                </div>
                            </div>

                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <iconify-icon icon="solar:document-text-bold-duotone" class="text-lg mr-2"></iconify-icon>
                                        RUC *
                                    </label>
                                    <input type="text" name="empresa_ruc" id="empresa_ruc" 
                                           class="form-control-modern w-full" 
                                           value="{{ $configuracion->empresa_ruc ?? '' }}"
                                           placeholder="20123456789" 
                                           pattern="[0-9]{11}" 
                                           maxlength="11" required>
                                    <div class="form-help-text">
                                        <iconify-icon icon="solar:shield-check-linear"></iconify-icon>
                                        Debe contener exactamente 11 dígitos
                                    </div>
                                </div>
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <iconify-icon icon="solar:letter-bold-duotone" class="text-lg mr-2"></iconify-icon>
                                        Correo Electrónico
                                    </label>
                                    <input type="email" name="empresa_email" id="empresa_email" 
                                           class="form-control-modern w-full" 
                                           value="{{ $configuracion->empresa_email ?? '' }}"
                                           placeholder="contacto@miempresa.com">
                                    <div class="form-help-text">
                                        <iconify-icon icon="solar:mailbox-linear"></iconify-icon>
                                        Email corporativo para comunicaciones oficiales
                                    </div>
                                </div>
                            </div>

                            
                            <div class="form-group-modern">
                                <label class="form-label-modern">
                                    <iconify-icon icon="solar:map-point-bold-duotone" class="text-lg mr-2"></iconify-icon>
                                    Dirección Completa *
                                </label>
                                <textarea name="empresa_direccion" id="empresa_direccion" 
                                          class="form-control-modern w-full" rows="2" 
                                          placeholder="Av. Principal 123, Distrito, Provincia, Departamento" required>{{ $configuracion->empresa_direccion ?? '' }}</textarea>
                                <div class="form-help-text">
                                    <iconify-icon icon="solar:map-linear"></iconify-icon>
                                    Dirección fiscal completa de la empresa
                                </div>
                            </div>
                        </div>

                        
                        <div class="form-group-modern">
                            <label class="form-label-modern">
                                <iconify-icon icon="solar:gallery-bold-duotone" class="text-lg mr-2"></iconify-icon>
                                Logo de la Empresa
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="logo-upload-area" style="padding: 2rem; min-height: 160px;">
                                    <div class="flex flex-col items-center justify-center h-full">
                                        <iconify-icon icon="solar:cloud-upload-bold-duotone" class="text-4xl text-gray-400 mb-3"></iconify-icon>
                                        <input type="file" name="empresa_logo" id="empresa_logo" class="hidden" accept="image/*" onchange="previsualizarLogo(this)">
                                        <label for="empresa_logo" class="cursor-pointer inline-block px-4 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Haz clic para subir una imagen</label>
                                        <div class="form-help-text text-center mt-2">
                                            PNG, JPG o GIF (Max. 2MB)
                                        </div>
                                    </div>
                                </div>
                                <div class="logo-preview-container" style="min-height: 220px;">
                                    <div id="logo-preview" class="h-full flex items-center justify-center">
                                        @if($configuracion->empresa_logo ?? false)
                                            <div class="text-center">
                                                <img src="{{ asset('storage/' . $configuracion->empresa_logo) }}" 
                                                     alt="Logo actual" 
                                                     class="max-w-full h-auto rounded" 
                                                     style="max-width: 180px; max-height: 120px;">
                                                <div class="mt-2">
                                                    <span class="inline-block px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">Logo Actual</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-center text-gray-500">
                                                <iconify-icon icon="solar:gallery-linear" class="text-3xl mb-2"></iconify-icon>
                                                <p class="mb-0 text-sm">Sin logo configurado</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-200">
                            <a href="{{ route('admin.configuracion') }}" class="btn btn-secondary-modern">Volver</a>
                            <button type="submit" class="btn btn-primary-modern">Guardar Configuración</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="xl:col-span-1">
            <div class="info-card-modern" style="background: #f8fafc;">
                <div class="info-header-modern" style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);">
                    <div class="flex items-center gap-2">
                        <iconify-icon icon="solar:database-bold-duotone" class="text-lg text-green-700"></iconify-icon>
                        <h5 class="mb-0 font-semibold text-green-800 text-sm">Datos Actuales</h5>
                    </div>
                </div>
                <div class="p-4 space-y-4">
                    <div class="data-item">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-blue-100 rounded-full">
                                <iconify-icon icon="solar:buildings-bold" class="text-blue-600"></iconify-icon>
                            </div>
                            <span class="data-label">Razón Social:</span>
                        </div>
                        <span class="data-value {{ $configuracion->empresa_nombre ? '' : 'text-red-500 italic' }}">{{ $configuracion->empresa_nombre ?? 'No configurado' }}</span>
                    </div>
                    <div class="data-item">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-blue-100 rounded-full">
                                <iconify-icon icon="solar:shop-bold" class="text-blue-600"></iconify-icon>
                            </div>
                            <span class="data-label">Nombre Comercial:</span>
                        </div>
                        <span class="data-value {{ $configuracion->empresa_nombre_comercial ? '' : 'text-red-500 italic' }}">{{ $configuracion->empresa_nombre_comercial ?? 'No configurado' }}</span>
                    </div>
                    <div class="data-item">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-green-100 rounded-full">
                                <iconify-icon icon="solar:document-text-bold" class="text-green-600"></iconify-icon>
                            </div>
                            <span class="data-label">RUC:</span>
                        </div>
                        <span class="data-value {{ $configuracion->empresa_ruc ? '' : 'text-red-500 italic' }}">{{ $configuracion->empresa_ruc ?? 'No configurado' }}</span>
                    </div>
                    
                    <div class="data-item">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-red-100 rounded-full">
                                <iconify-icon icon="solar:letter-bold" class="text-red-600"></iconify-icon>
                            </div>
                            <span class="data-label">Email:</span>
                        </div>
                        <span class="data-value {{ $configuracion->empresa_email ? '' : 'text-red-500 italic' }}">{{ $configuracion->empresa_email ?? 'No configurado' }}</span>
                    </div>
                    <div class="data-item">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-yellow-100 rounded-full">
                                <iconify-icon icon="solar:map-point-bold" class="text-yellow-600"></iconify-icon>
                            </div>
                            <span class="data-label">Dirección:</span>
                        </div>
                        <span class="data-value {{ $configuracion->empresa_direccion ? '' : 'text-red-500 italic' }}">{{ $configuracion->empresa_direccion ? Str::limit($configuracion->empresa_direccion, 30) : 'No configurado' }}</span>
                    </div>
                    <div class="data-item">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-indigo-100 rounded-full">
                                <iconify-icon icon="solar:gallery-bold" class="text-indigo-600"></iconify-icon>
                            </div>
                            <span class="data-label">Logo:</span>
                        </div>
                        @if($configuracion->empresa_logo)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $configuracion->empresa_logo) }}" alt="Logo actual" class="w-16 h-16 object-contain rounded border">
                                <span class="data-value text-green-600 block mt-1">Configurado</span>
                            </div>
                        @else
                            <span class="data-value text-red-500 italic">No configurado</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previsualizarLogo(input) {
    const preview = document.getElementById('logo-preview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="text-center">
                    <img src="${e.target.result}" 
                         alt="Vista previa del logo" 
                         class="img-fluid rounded" 
                         style="max-width: 200px; max-height: 140px;">
                    <div class="mt-2">
                        <span class="badge bg-info">Vista Previa</span>
                    </div>
                </div>
            `;
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

async function guardarConfiguracionEmpresa(event) {
    event.preventDefault();
    
    const form = document.getElementById('formConfiguracionEmpresa');
    const formData = new FormData(form);

    const ruc = document.getElementById('empresa_ruc').value;
    if (ruc && !/^[0-9]{11}$/.test(ruc)) {
        Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: 'El RUC debe tener exactamente 11 dígitos numéricos'
        });
        return;
    }
    
    try {
        const response = await fetch('{{ route("admin.configuracion.empresa.actualizar") }}', {
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

                Turbo.visit(window.location.href, { action: 'replace' });
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

document.getElementById('empresa_ruc').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');