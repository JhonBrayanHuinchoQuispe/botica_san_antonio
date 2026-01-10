@extends('layout.layout')

@php
    $title = 'Detalle del Estante';
    $subTitle = $estante->nombre ?? 'Detalle';
@endphp

<head>
    <title>{{ $estante->nombre ?? 'Detalle del Estante' }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/estante-detalle.css') }}">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
</head>

@section('content')
<div class="card border-0 overflow-hidden">
    <div class="card-body">
        <div class="detalle-estante-container">

            <!-- Encabezado -->
            <div class="detalle-header">
                <a href="{{ route('ubicaciones.mapa') }}" class="btn-volver">
                    <iconify-icon icon="solar:arrow-left-linear"></iconify-icon>
                    <span>Volver al Mapa</span>
                </a>
                <div>
                    <h1 class="main-title">{{ $estante->nombre }} - Distribución de Productos</h1>
                    <p class="subtitle">{{ $estante->descripcion }}</p>
                </div>
            </div>

            <!-- Foto del Estante -->
            <div class="foto-container" style="background-image: url('{{ $estante->foto_url }}');">
                <div class="foto-overlay">
                    <button class="btn-action-foto">
                        <iconify-icon icon="solar:camera-add-bold-duotone"></iconify-icon>
                        <span>Actualizar Foto</span>
                    </button>
                </div>
            </div>

            <!-- Barra de acciones de niveles -->
            <div class="niveles-actions-header">
                <button class="btn-action">
                    <iconify-icon icon="solar:pen-new-square-bold-duotone"></iconify-icon>
                    <span>Editar Ubicaciones</span>
                </button>
            </div>

            <!-- Niveles del Estante -->
            <div class="niveles-container">
                @if(isset($estante->niveles) && count($estante->niveles) > 0)
                    @foreach($estante->niveles as $nivel)
                        <div class="nivel-card">
                            <div class="nivel-header">
                                <div class="nivel-title">
                                    <iconify-icon icon="solar:layers-bold-duotone"></iconify-icon>
                                    <h3>{{ $nivel->nombre }}</h3>
                                </div>
                                <span class="nivel-product-count">{{ $nivel->total_productos }} productos</span>
                            </div>
                            <div class="productos-grid">
                                @foreach($nivel->productos as $producto)
                                    @if($producto->nombre)
                                        <!-- Card de Producto -->
                                        <div class="producto-card">
                                            <div class="producto-info">
                                                <h4 class="producto-nombre">{{ $producto->nombre }}</h4>
                                                <p class="producto-unidades">{{ $producto->unidades }} unidades</p>
                                            </div>
                                            <div class="producto-actions">
                                                <span class="producto-codigo">{{ $producto->codigo_ubicacion }}</span>
                                                <button class="action-btn" title="Ver Producto">
                                                    <iconify-icon icon="solar:eye-bold"></iconify-icon>
                                                </button>
                                                <button class="action-btn" title="Añadir Stock">
                                                    <iconify-icon icon="solar:add-circle-bold"></iconify-icon>
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        <!-- Card de Espacio Vacío -->
                                        <div class="producto-card empty-slot">
                                            <iconify-icon icon="solar:archive-minimalistic-linear"></iconify-icon>
                                            <p>Espacio Vacío</p>
                                            <span class="producto-codigo">{{ $producto->codigo_ubicacion }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <p>Este estante no tiene niveles definidos.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 