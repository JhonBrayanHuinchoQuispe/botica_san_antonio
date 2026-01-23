@extends('layout.layout')
@php
    $title = 'Optimización de Imágenes';
    $subTitle = 'Administración / Optimización de Imágenes';
@endphp

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title flex items-center gap-2">
                        <iconify-icon icon="mdi:image-multiple" class="text-xl"></iconify-icon>
                        <span>Optimización de Imágenes de Productos</span>
                    </h3>
                    <button class="btn btn-info" onclick="cargarEstadisticas()">
                        <iconify-icon icon="mdi:refresh" class="mr-1"></iconify-icon> Actualizar
                    </button>
                </div>
                <div class="card-body">
                    
                    <div class="row mb-4" id="estadisticas-container">
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-box"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Productos</span>
                                    <span class="info-box-number" id="total-productos">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-image"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Con Imagen</span>
                                    <span class="info-box-number" id="con-imagen">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Sin Imagen</span>
                                    <span class="info-box-number" id="sin-imagen">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-primary">
                                <span class="info-box-icon"><i class="fas fa-hdd"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Espacio Total</span>
                                    <span class="info-box-number" id="espacio-total">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Acciones de Optimización</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <button class="btn btn-primary btn-block btn-lg" onclick="optimizarTodasLasImagenes()" id="btn-optimize-all">
                                                <iconify-icon icon="mdi:compress" class="mr-2"></iconify-icon>
                                                Optimizar Todas las Imágenes
                                            </button>
                                            <small class="text-muted">Reduce el tamaño de todas las imágenes existentes</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <button class="btn btn-success btn-block btn-lg" onclick="generarPlaceholders()">
                                                <iconify-icon icon="mdi:image-plus" class="mr-2"></iconify-icon>
                                                Generar Placeholders
                                            </button>
                                            <small class="text-muted">Crea imágenes placeholder para productos sin imagen</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <button class="btn btn-warning btn-block btn-lg" onclick="limpiarImagenesNoUtilizadas()">
                                                <iconify-icon icon="mdi:trash-can" class="mr-2"></iconify-icon>
                                                Limpiar Imágenes No Utilizadas
                                            </button>
                                            <small class="text-muted">Elimina archivos de imagen que no están en uso</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <button class="btn btn-info btn-block btn-lg" onclick="mostrarImagenesPesadas()">
                                                <iconify-icon icon="mdi:weight" class="mr-2"></iconify-icon>
                                                Ver Imágenes Pesadas
                                            </button>
                                            <small class="text-muted">Muestra las imágenes que más espacio ocupan</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="row mb-4" id="progress-container" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Progreso de Operación</h5>
                                </div>
                                <div class="card-body">
                                    <div class="progress mb-3">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" id="progress-bar" style="width: 0%"></div>
                                    </div>
                                    <p id="progress-text" class="text-center mb-0">Iniciando...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="row" id="results-container" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Resultados de la Operación</h5>
                                </div>
                                <div class="card-body" id="results-content">
                                    
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="row" id="heavy-images-container" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Imágenes Más Pesadas (Top 10)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped" id="heavy-images-table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Producto</th>
                                                    <th>Imagen</th>
                                                    <th>Tamaño</th>
                                                    <th>Vista Previa</th>
                                                </tr>
                                            </thead>
                                            <tbody id="heavy-images-tbody">
                                                
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="row" id="recommendations-container" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-lightbulb mr-2"></i>
                                        Recomendaciones
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <ul id="recommendations-list" class="list-unstyled">
                                        
                                    </ul>
                                </div>
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
<script>
class ImageOptimizationManager {
    constructor() {
        this.baseUrl = '/api/image-optimization';
        this.init();
    }

    init() {
        this.cargarEstadisticas();
        this.setupEventListeners();
    }

    setupEventListeners() {

    }

    async cargarEstadisticas() {
        try {
            this.mostrarCargando('Cargando estadísticas...');
            
            const response = await fetch(`${this.baseUrl}/statistics`);
            const data = await response.json();
            
            if (data.success) {
                this.mostrarEstadisticas(data.data);
                this.mostrarImagenesPesadasData(data.data.imagenes_pesadas);
                this.mostrarRecomendaciones(data.data.recomendaciones);
            } else {
                this.mostrarError('Error cargando estadísticas: ' + data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión: ' + error.message);
        } finally {
            this.ocultarCargando();
        }
    }

    mostrarEstadisticas(data) {
        const resumen = data.resumen;
        
        document.getElementById('total-productos').textContent = resumen.total_productos.toLocaleString();
        document.getElementById('con-imagen').textContent = resumen.productos_con_imagen.toLocaleString();
        document.getElementById('sin-imagen').textContent = resumen.productos_sin_imagen.toLocaleString();
        document.getElementById('espacio-total').textContent = resumen.espacio_total;
    }

    mostrarImagenesPesadasData(imagenes) {
        const tbody = document.getElementById('heavy-images-tbody');
        tbody.innerHTML = '';
        
        if (imagenes && imagenes.length > 0) {
            imagenes.forEach(imagen => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${imagen.id}</td>
                    <td>${imagen.nombre}</td>
                    <td><small>${imagen.imagen}</small></td>
                    <td><span class="badge badge-warning">${imagen.tamano}</span></td>
                    <td>
                        <img src="/storage/${imagen.imagen}" 
                             alt="${imagen.nombre}" 
                             class="img-thumbnail" 
                             style="max-width: 50px; max-height: 50px;"
                             onerror="this.src='/images/no-image.png'">
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            document.getElementById('heavy-images-container').style.display = 'block';
        }
    }

    mostrarRecomendaciones(recomendaciones) {
        const list = document.getElementById('recommendations-list');
        list.innerHTML = '';
        
        if (recomendaciones && recomendaciones.length > 0) {
            recomendaciones.forEach(recomendacion => {
                const li = document.createElement('li');
                li.innerHTML = `<i class="fas fa-check-circle text-success mr-2"></i>${recomendacion}`;
                li.className = 'mb-2';
                list.appendChild(li);
            });
            
            document.getElementById('recommendations-container').style.display = 'block';
        }
    }

    async optimizarTodasLasImagenes() {
        const confirmed = window.confirm('¿Optimizar todas las imágenes?\nPuede tomar varios minutos.');
        
        if (!confirmed) return;

        try {
            this.mostrarProgreso('Optimizando imágenes...', 0);
            
            const response = await fetch(`${this.baseUrl}/optimize-all`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarResultados('Optimización Completada', data.data, 'success');
                this.cargarEstadisticas();
                this.mostrarError('Error en la optimización: ' + data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión: ' + error.message);
        } finally {
            this.ocultarProgreso();
        }
    }

    async generarPlaceholders() {
        const confirmed = window.confirm('¿Generar placeholders para productos sin imagen?');
        
        if (!confirmed) return;

        try {
            this.mostrarProgreso('Generando placeholders...', 0);
            
            const response = await fetch(`${this.baseUrl}/generate-placeholders`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarResultados('Placeholders Generados', data.data, 'success');
                this.cargarEstadisticas();
            } else {
                this.mostrarError('Error generando placeholders: ' + data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión: ' + error.message);
        } finally {
            this.ocultarProgreso();
        }
    }

    async limpiarImagenesNoUtilizadas() {
        const confirmed = window.confirm('¿Eliminar imágenes no utilizadas? Esta acción no se puede deshacer.');
        
        if (!confirmed) return;

        try {
            this.mostrarProgreso('Limpiando imágenes no utilizadas...', 0);
            
            const response = await fetch(`${this.baseUrl}/clean-unused`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarResultados('Limpieza Completada', data.data, 'success');
                this.cargarEstadisticas();
            } else {
                this.mostrarError('Error en la limpieza: ' + data.message);
            }
        } catch (error) {
            this.mostrarError('Error de conexión: ' + error.message);
        } finally {
            this.ocultarProgreso();
        }
    }

    mostrarImagenesPesadas() {
        const container = document.getElementById('heavy-images-container');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            container.scrollIntoView({ behavior: 'smooth' });
        } else {
            container.style.display = 'none';
        }
    }

    mostrarProgreso(mensaje, porcentaje) {
        const container = document.getElementById('progress-container');
        const bar = document.getElementById('progress-bar');
        const text = document.getElementById('progress-text');
        
        container.style.display = 'block';
        bar.style.width = porcentaje + '%';
        text.textContent = mensaje;
        
        container.scrollIntoView({ behavior: 'smooth' });
    }

    ocultarProgreso() {
        document.getElementById('progress-container').style.display = 'none';
    }

    mostrarResultados(titulo, data, tipo = 'info') {
        const container = document.getElementById('results-container');
        const content = document.getElementById('results-content');
        
        let html = `<h5 class="text-${tipo === 'success' ? 'success' : 'info'}">${titulo}</h5>`;
        
        if (typeof data === 'object') {
            html += '<div class="row">';
            for (const [key, value] of Object.entries(data)) {
                const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                html += `
                    <div class="col-md-6 mb-2">
                        <strong>${label}:</strong> ${value}
                    </div>
                `;
            }
            html += '</div>';
        } else {
            html += `<p>${data}</p>`;
        }
        
        content.innerHTML = html;
        container.style.display = 'block';
        container.scrollIntoView({ behavior: 'smooth' });
    }

    mostrarCargando(mensaje) {

    }

    ocultarCargando() {

    }

    mostrarError(mensaje) {
        alert('Error: ' + mensaje);
    }

}

let imageOptimizer;

document.addEventListener('DOMContentLoaded', function() {
    imageOptimizer = new ImageOptimizationManager();
});

function cargarEstadisticas() {
    imageOptimizer.cargarEstadisticas();
}

function optimizarTodasLasImagenes() {
    imageOptimizer.optimizarTodasLasImagenes();
}

function generarPlaceholders() {
    imageOptimizer.generarPlaceholders();
}

function limpiarImagenesNoUtilizadas() {
    imageOptimizer.limpiarImagenesNoUtilizadas();
}

function mostrarImagenesPesadas() {
    imageOptimizer.mostrarImagenesPesadas();
}
</script>
@endpush