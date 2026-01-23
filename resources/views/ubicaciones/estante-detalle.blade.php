@extends('layout.layout')

@php
    $title = 'Gestión de Almacén';
    $subTitle = 'Detalle del Estante';
@endphp

<head>
    <title>Detalle del Estante</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/estante-detalle.css') }}">
    
    
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/configuracion/estructura-modal.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/configuracion/estructura-form.css') }}">
    
    
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/modal_agregar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/modal_editar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/modal_mover.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ubicacion/modal_ver.css') }}">
    
    
    <script>
        window.estanteActual = {{ $estante->id }};
        window.estanteNombre = "{{ $estante->nombre }}";
    </script>
    
    
    <script src="{{ asset('assets/js/ubicacion/estante/estante.js') }}" defer></script>
    <script src="{{ asset('assets/js/ubicacion/estante-detalle.js') }}" defer></script>
    <script src="{{ asset('assets/js/ubicacion/modal_agregar.js') }}" defer></script>
    <script src="{{ asset('assets/js/ubicacion/modal_mover.js') }}" defer></script>
    <script src="{{ asset('assets/js/ubicacion/fusion-slots.js') }}" defer></script>
    <script src="{{ asset('assets/js/ubicacion/fix-slot-click.js') }}" defer></script>
    
    
    <script>

        window.emergenciaDragDrop = function() {
            console.log('%c🆘 ACTIVANDO DRAG AND DROP DE EMERGENCIA', 'background: #dc2626; color: white; padding: 10px; border-radius: 6px; font-weight: bold; font-size: 14px;');
            
            const productos = document.querySelectorAll('.slot-container.ocupado');
            console.log(`📦 Encontrados ${productos.length} productos`);
            
            productos.forEach((slot, index) => {
                slot.setAttribute('draggable', 'true');
                slot.style.cursor = 'grab';
                console.log(`✅ Producto ${index + 1} (${slot.dataset.slot}) FORZADO draggable`);
            });
            
            console.log('%c🎯 DRAG AND DROP EMERGENCIA ACTIVADO', 'background: #10b981; color: white; padding: 6px; border-radius: 4px; font-weight: bold;');
            console.log('¡Ahora puedes arrastrar los productos!');
        };

        window.mostrarProductosFueraDeRango = function() {
            const productosExtraRango = document.querySelectorAll('.slot-fuera-rango.ocupado');
            
            if (productosExtraRango.length === 0) {
                Swal.fire({
                    title: 'Sin productos fuera de rango',
                    text: 'Actualmente no hay productos ubicados fuera del rango configurado.',
                    icon: 'info',
                    confirmButtonText: 'Entendido',
                    customClass: {
                        popup: 'modal-intercambio-sobrio'
                    }
                });
                return;
            }

            let listaProductos = '<div style="text-align: left; max-height: 300px; overflow-y: auto;">';
            listaProductos += '<h4 style="margin-bottom: 15px; color: #1e293b;">Productos fuera del rango configurado:</h4>';
            
            productosExtraRango.forEach((slot, index) => {
                const nombre = slot.dataset.productoNombre || 'Producto desconocido';
                const stock = slot.dataset.productoStock || '0';
                const ubicacion = slot.dataset.slot || 'Sin ubicación';
                
                listaProductos += `
                    <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                        <div style="font-weight: 600; color: #92400e; margin-bottom: 4px;">
                            📦 ${nombre}
                        </div>
                        <div style="color: #92400e; font-size: 14px;">
                            📍 Ubicación: ${ubicacion}<br>
                            📊 Stock: ${stock} unidades
                        </div>
                    </div>
                `;
            });
            
            listaProductos += '</div>';

            productosExtraRango.forEach(slot => {
                slot.style.animation = 'fuera-rango-pulse 2s ease-in-out 3';
                slot.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
            
            Swal.fire({
                title: `${productosExtraRango.length} Productos fuera de rango`,
                html: listaProductos,
                icon: 'warning',
                confirmButtonText: 'Entendido',
                width: '600px',
                customClass: {
                    popup: 'modal-intercambio-sobrio'
                }
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            console.log('%c🎉 SISTEMA DRAG & DROP CARGADO', 'background: #10b981; color: white; padding: 8px; border-radius: 6px; font-weight: bold;');
            console.log('💡 Función disponible: emergenciaDragDrop()');
            console.log('💡 Función disponible: mostrarProductosFueraDeRango()');
        });
    </script>
    
    
    
    
    
    
    
    <script src="{{ asset('assets/js/ubicacion/configuracion/estructura-modal.js') }}" defer></script>
    
    
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    
    <style>
        
        .alert-productos-fuera-rango {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-shadow: 0 4px 6px rgba(245, 158, 11, 0.1);
        }

        .alert-icon {
            background: #f59e0b;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .alert-icon iconify-icon {
            font-size: 20px;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 600;
            color: #92400e;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .alert-description {
            color: #92400e;
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .alert-actions {
            display: flex;
            gap: 12px;
        }

        .btn-alert-action {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-alert-action:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        
        .nivel-fuera-rango {
            position: relative;
            border: 2px dashed #f59e0b;
            border-radius: 8px;
            background: rgba(254, 243, 199, 0.3);
        }

        .nivel-label-fuera-rango {
            background: #fef3c7;
            border: 1px solid #f59e0b;
        }

        .badge-fuera-rango {
            background: #f59e0b;
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 8px;
            font-weight: 500;
        }

        
        .slot-fuera-rango {
            position: relative;
            border: 2px solid #f59e0b !important;
            background: linear-gradient(135deg, rgba(254, 243, 199, 0.8) 0%, rgba(253, 230, 138, 0.8) 100%) !important;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
        }

        .slot-fuera-rango::before {
            content: '';
            position: absolute;
            top: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            background: #f59e0b;
            border-radius: 50%;
            border: 2px solid white;
            z-index: 10;
        }

        .slot-fuera-rango::after {
            content: '⚠';
            position: absolute;
            top: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            color: white;
            font-size: 8px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 11;
        }

        .badge-fuera-rango-slot {
            background: #dc2626;
            color: white;
            font-size: 9px;
            padding: 1px 4px;
            border-radius: 3px;
            margin-left: 4px;
            font-weight: 500;
            white-space: nowrap;
        }

        
        .slot-fuera-rango.ocupado {
            animation: fuera-rango-pulse 3s ease-in-out infinite;
        }

        @keyframes fuera-rango-pulse {
            0%, 100% {
                box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
            }
            50% {
                box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.4);
            }
        }

        
        .slot-fuera-rango .slot-content {
            position: relative;
        }

        .slot-fuera-rango .slot-content::before {
            content: 'Este producto está en una ubicación fuera del rango configurado del estante';
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            z-index: 1000;
        }

        .slot-fuera-rango:hover .slot-content::before {
            opacity: 1;
        }
    </style>
    
    
    <style>
        
        .modal-intercambio-sobrio {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
            border-radius: 16px !important;
            border: none !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
        }
        
        .modal-intercambio-sobrio .swal2-title {
            font-size: 22px !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            margin-bottom: 8px !important;
        }
        
        
        .btn-confirmar-sobrio {
            background: #10b981 !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 12px 24px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2) !important;
            transition: all 0.2s ease !important;
            margin: 0 6px !important;
            cursor: pointer !important;
            opacity: 1 !important;
            visibility: visible !important;
            display: inline-block !important;
        }
        
        .btn-confirmar-sobrio:hover {
            background: #059669 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3) !important;
        }
        
        .btn-cancelar-sobrio {
            background: #ef4444 !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 12px 24px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2) !important;
            transition: all 0.2s ease !important;
            margin: 0 6px !important;
            cursor: pointer !important;
            opacity: 1 !important;
            visibility: visible !important;
            display: inline-block !important;
        }
        
        .btn-cancelar-sobrio:hover {
            background: #dc2626 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3) !important;
        }
        
        
        .btn-cancelar-sobrio-rojo {
            background: #ef4444 !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 12px 24px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2) !important;
            transition: all 0.2s ease !important;
            margin: 0 6px !important;
            cursor: pointer !important;
            opacity: 1 !important;
            visibility: visible !important;
            display: inline-block !important;
        }
        
        .btn-cancelar-sobrio-rojo:hover {
            background: #dc2626 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35) !important;
        }
        
        
        .swal2-actions {
            margin-top: 30px !important;
            gap: 15px !important;
        }
        
        .swal2-actions button {
            opacity: 1 !important;
            visibility: visible !important;
            display: inline-block !important;
        }
        
        
        .swal2-actions .swal2-deny {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            width: 0 !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .swal2-actions .swal2-close {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        
        .swal2-actions button:contains("No") {
            display: none !important;
        }
        
        
        .swal2-actions-clean {
            justify-content: center !important;
        }
        
        .swal2-actions-clean .swal2-confirm,
        .swal2-actions-clean .swal2-cancel {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        
        .swal2-actions .swal2-confirm,
        .swal2-actions .swal2-cancel {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        
        .btn-confirmar-sobrio,
        .btn-cancelar-sobrio,
        .swal2-confirm {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 9999 !important;
        }
        
        
        .swal2-actions button:nth-child(n+3):not(.swal2-confirm):not(.swal2-cancel) {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        
        .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .success-intercambio .swal2-title {
            color: #065f46 !important;
        }
        
        .error-intercambio .swal2-title {
            color: #991b1b !important;
        }
        
        .swal2-icon.swal2-question {
            border-color: #3b82f6 !important;
            color: #3b82f6 !important;
        }
        
        .rotating {
            animation: rotate 2s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        
        .swal2-loading .swal2-title {
            color: #10b981 !important;
            font-weight: 600 !important;
        }
        
        
        .swal2-popup[class*="modal-intercambio-sobrio"] .swal2-actions {
            margin-top: 25px !important;
            padding: 0 !important;
            justify-content: center !important;
        }
        
        .swal2-popup[class*="modal-intercambio-sobrio"] .swal2-confirm {
            background: #3b82f6 !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 12px 28px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25) !important;
            transition: all 0.2s ease !important;
            margin: 0 !important;
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .swal2-popup[class*="modal-intercambio-sobrio"] .swal2-confirm:hover {
            background: #1d4ed8 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.35) !important;
        }
        
        
        .modal-sin-cambios .swal2-actions {
            gap: 0 !important;
            justify-content: center !important;
        }
        
        .modal-sin-cambios .swal2-actions .swal2-cancel,
        .modal-sin-cambios .swal2-actions .swal2-deny {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            width: 0 !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        
        .modal-guardar-cambios .swal2-actions {
            gap: 15px !important;
            justify-content: center !important;
            margin-top: 30px !important;
        }
        
        .modal-guardar-cambios .swal2-actions .swal2-confirm,
        .modal-guardar-cambios .swal2-actions .swal2-cancel {
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
    </style>
</head>

@section('content')
<div class="card border-0 overflow-hidden">
    <div class="card-body">
        
        
        <div class="estante-header-section">
            <div class="breadcrumb-modern">
                <a href="{{ route('ubicaciones.mapa') }}" class="breadcrumb-link">
                    <iconify-icon icon="solar:warehouse-bold-duotone"></iconify-icon>
                    <span>Mapa del Almacén</span>
                </a>
                <iconify-icon icon="solar:alt-arrow-right-bold"></iconify-icon>
                <span class="breadcrumb-current">{{ $estante->nombre }}</span>
            </div>
            
            <div class="estante-title-section">
                <div class="estante-main-info">
                    <div class="estante-icon-wrapper">
                        <iconify-icon icon="solar:server-square-bold-duotone"></iconify-icon>
                    </div>
                    <div class="estante-text-info">
                        <h1 class="estante-title">{{ $estante->nombre }}</h1>
                        <p class="estante-subtitle">{{ $estante->ubicacion_fisica ?? 'Ubicación no especificada' }}</p>
                    </div>
                </div>
                <div class="estante-actions">
                    <button class="btn-action-modern btn-edit">
                        <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                        <span>Editar</span>
                    </button>

                </div>
            </div>
        </div>

        
        @php

            $totalSlots = 0;
            $slotsOcupados = 0;
            $totalProductos = 0;
            $alertas = 0;
            
            foreach ($estante->niveles as $nivel) {
                $totalSlots += count($nivel['productos']);
                foreach ($nivel['productos'] as $ubicacion) {
                    if (!empty($ubicacion['nombre'])) {
                        $slotsOcupados++;
                        $totalProductos += $ubicacion['unidades'] ?? 0;

                        $stock = $ubicacion['unidades'] ?? 0;
                        if ($stock > 0 && $stock <= 10) {
                            $alertas++;
                        }
                    }
                }
            }
            
            $porcentajeOcupacion = $totalSlots > 0 ? round(($slotsOcupados / $totalSlots) * 100, 1) : 0;
        @endphp
        <div class="metricas-compactas">
            <div class="metrica-compacta ocupacion">
                <div class="metrica-compacta-icon">
                    <iconify-icon icon="solar:pie-chart-bold-duotone"></iconify-icon>
                </div>
                <div class="metrica-compacta-content">
                    <div class="metrica-compacta-header">
                        <span class="metrica-compacta-valor">{{ $porcentajeOcupacion }}%</span>
                        <span class="metrica-compacta-label">Ocupación</span>
                    </div>
                    <div class="metrica-compacta-detalle">{{ $slotsOcupados }} de {{ $totalSlots }} slots</div>
                    <div class="metrica-compacta-barra">
                        <div class="barra-progreso" style="width: {{ $porcentajeOcupacion }}%"></div>
                    </div>
                </div>
            </div>
            
            <div class="metrica-compacta productos">
                <div class="metrica-compacta-icon">
                    <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                </div>
                <div class="metrica-compacta-content">
                    <div class="metrica-compacta-header">
                        <span class="metrica-compacta-valor">{{ $totalProductos }}</span>
                        <span class="metrica-compacta-label">Productos</span>
                    </div>
                    <div class="metrica-compacta-detalle">Total almacenados</div>
                </div>
            </div>
            
            <div class="metrica-compacta alertas">
                <div class="metrica-compacta-icon">
                    <iconify-icon icon="solar:danger-triangle-bold-duotone"></iconify-icon>
                </div>
                <div class="metrica-compacta-content">
                    <div class="metrica-compacta-header">
                        <span class="metrica-compacta-valor">{{ $alertas }}</span>
                        <span class="metrica-compacta-label">Alertas</span>
                    </div>
                    <div class="metrica-compacta-detalle">Stock bajo</div>
                    @if($alertas > 0)
                    <div class="alertas-indicadores">
                        @for($i = 0; $i < min($alertas, 3); $i++)
                            <span class="alerta-punto {{ $i == 0 ? 'critico' : 'alerta' }}"></span>
                        @endfor
                    </div>
                    @endif
                </div>
            </div>
        </div>

        
        @if($estante->tiene_productos_fuera_de_rango)
        <div class="alert-productos-fuera-rango">
            <div class="alert-icon">
                <iconify-icon icon="solar:danger-triangle-bold-duotone"></iconify-icon>
            </div>
            <div class="alert-content">
                <div class="alert-title">
                    Productos fuera del rango configurado
                </div>
                <div class="alert-description">
                    Este estante tiene productos ubicados en posiciones que están fuera del rango actual configurado ({{ $estante->numero_niveles }}×{{ $estante->numero_posiciones }}). 
                    Los productos se conservan y aparecen en la vista extendida ({{ $estante->numero_niveles_real }}×{{ $estante->numero_posiciones_real }}).
                </div>
                <div class="alert-actions">
                    <button class="btn-alert-action" onclick="mostrarProductosFueraDeRango()">
                        <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                        Ver productos fuera de rango
                    </button>
                </div>
            </div>
        </div>
        @endif

        
        <div class="slots-main-content">
            <div class="slots-section">
                <div class="slots-header">
                    <div class="slots-title">
                        <h3>Distribución de Slots</h3>
                        <p>Vista detallada del estante con {{ count($estante->niveles) }} niveles y {{ $estante->capacidad_total }} posiciones totales</p>
                    </div>
                    <div class="slots-controles">
                        <button class="btn-control btn-secondary" id="btnConfigurarEstructura">
                            <iconify-icon icon="solar:settings-bold-duotone"></iconify-icon>
                            <span>Configurar Estructura</span>
                        </button>
                        <button class="btn-control btn-fusion" id="btnIniciarFusion">
                            <iconify-icon icon="solar:widget-2-bold-duotone"></iconify-icon>
                            <span>Fusionar Slots</span>
                        </button>
                    </div>
                </div>

                
                <div class="estante-grid">
                    @if(empty($estante->niveles))
                        <div class="no-ubicaciones-message">
                            <iconify-icon icon="solar:box-minimalistic-broken" style="font-size: 48px; color: #999;"></iconify-icon>
                            <h4 style="color: #666; margin-top: 10px;">No hay ubicaciones configuradas</h4>
                            <p style="color: #999;">Este estante no tiene ubicaciones creadas</p>
                        </div>
                    @else
                        @php

                            $nivelesOrdenados = collect($estante->niveles)->sortByDesc('numero');
                        @endphp
                        @foreach($nivelesOrdenados as $nivel)
                        <div class="nivel-container {{ isset($nivel['fuera_de_rango']) && $nivel['fuera_de_rango'] ? 'nivel-fuera-rango' : '' }}" data-nivel="{{ $nivel['numero'] }}">
                        <div class="nivel-label {{ isset($nivel['fuera_de_rango']) && $nivel['fuera_de_rango'] ? 'nivel-label-fuera-rango' : '' }}">
                                <span class="nivel-numero">{{ $nivel['nombre'] }}</span>
                                <span class="nivel-info">
                                    @if($nivel['numero'] == $nivelesOrdenados->max('numero'))
                                        Superior
                                    @elseif($nivel['numero'] == $nivelesOrdenados->min('numero'))
                                        Inferior
                                    @else
                                        Medio
                                    @endif
                                    @if(isset($nivel['fuera_de_rango']) && $nivel['fuera_de_rango'])
                                        <span class="badge-fuera-rango">Fuera de rango</span>
                                    @endif
                                </span>
                        </div>
                        <div class="slots-row" style="--slots-per-level: {{ $estante->numero_posiciones }}">
                                @foreach($nivel['productos'] as $index => $ubicacionData)
                                    @php
                                        $esProducto = !empty($ubicacionData['nombre']);
                                        $codigoUbicacion = $ubicacionData['codigo_ubicacion'] ?? ($nivel['numero'] . '-' . ($index + 1));
                                        $esFusionado = $ubicacionData['es_fusionado'] ?? false;
                                        $tipoFusion = $ubicacionData['tipo_fusion'] ?? null;
                                        $slotsOcupados = $ubicacionData['slots_ocupados'] ?? 1;

                                        $estado = 'vacio';
                                        if ($esProducto) {
                                            $stock = $ubicacionData['unidades'] ?? 0;
                                            if ($stock > 20) {
                                                $estado = 'ok';
                                            } elseif ($stock > 5) {
                                                $estado = 'alerta';
                                            } else {
                                                $estado = 'peligro';
                                            }
                                        }

                                        $clasesFusion = '';
                                        if ($esFusionado) {
                                            $clasesFusion = 'fusionado ' . $tipoFusion;
                                        }
                            @endphp
                            
                                    <div class="slot-container {{ $esProducto ? 'ocupado' : 'vacio' }} {{ (isset($ubicacionData['fuera_de_rango']) && $ubicacionData['fuera_de_rango']) ? 'slot-fuera-rango' : '' }} {{ $clasesFusion }}" 
                                         data-slot="{{ $codigoUbicacion }}" 
                                         data-estado="{{ $estado }}"
                                         data-ubicacion-id="{{ $ubicacionData['ubicacion_id'] ?? '' }}"
                                         data-producto-ubicacion-id="{{ $ubicacionData['producto_ubicacion_id'] ?? '' }}"
                                         {{ $esProducto ? 'draggable="true"' : '' }}
                                         data-producto-id="{{ $ubicacionData['id'] ?? '' }}"
                                         data-producto-nombre="{{ $ubicacionData['nombre'] ?? '' }}"
                                         data-producto-marca="{{ $ubicacionData['marca'] ?? '' }}"
                                         data-producto-concentracion="{{ $ubicacionData['concentracion'] ?? '' }}"
                                         data-producto-precio="{{ $ubicacionData['precio_venta'] ?? '' }}"
                                         data-producto-vencimiento="{{ $ubicacionData['fecha_vencimiento'] ?? '' }}"
                                         data-producto-stock="{{ $ubicacionData['unidades'] ?? '' }}"
                                         data-estante-id="{{ $estante->id }}"
                                         data-nivel="{{ $nivel['numero'] }}"
                                         data-posicion="{{ $index + 1 }}"
                                         data-fuera-de-rango="{{ (isset($ubicacionData['fuera_de_rango']) && $ubicacionData['fuera_de_rango']) ? 'true' : 'false' }}"
                                         data-es-fusionado="{{ $esFusionado ? 'true' : 'false' }}"
                                         data-tipo-fusion="{{ $tipoFusion }}"
                                         data-slots-ocupados="{{ $slotsOcupados }}"
                                         onclick="if(this.classList.contains('vacio') && !document.body.classList.contains('modo-fusion-activo')) { 
                                            console.log('👆 Click en slot vacío: {{ $codigoUbicacion }}');
                                            if(window.modalAgregar) { 
                                                window.modalAgregar.abrirModoSlotEspecifico('{{ $codigoUbicacion }}'); 
                                            } else {
                                                console.error('❌ Error: window.modalAgregar no está inicializado');

                                                if(typeof ModalAgregar !== 'undefined') {
                                                    window.modalAgregar = new ModalAgregar();
                                                    window.modalAgregar.abrirModoSlotEspecifico('{{ $codigoUbicacion }}');
                                                }
                                            }
                                         }">
                                <div class="slot-content">
                                            @if($esProducto)
                                                <div class="slot-posicion">
                                                    @if($esFusionado)
                                                        @php
                                                            $nivelActual = $nivel['numero'];
                                                            $posicionActual = $index + 1;
                                                            $rangoTexto = '';
                                                            
                                                            switch($tipoFusion) {
                                                                case 'horizontal-2':
                                                                    $rangoTexto = "{$nivelActual}-{$posicionActual} hasta {$nivelActual}-" . ($posicionActual + 1);
                                                                    break;
                                                                case 'horizontal-3':
                                                                    $rangoTexto = "{$nivelActual}-{$posicionActual} hasta {$nivelActual}-" . ($posicionActual + 2);
                                                                    break;
                                                                case 'horizontal-4':
                                                                    $rangoTexto = "{$nivelActual}-{$posicionActual} hasta {$nivelActual}-" . ($posicionActual + 3);
                                                                    break;
                                                                case 'vertical-2':
                                                                    $rangoTexto = "{$nivelActual}-{$posicionActual} hasta " . ($nivelActual + 1) . "-{$posicionActual}";
                                                                    break;
                                                                case 'cuadrado-2x2':
                                                                    $rangoTexto = "{$nivelActual}-{$posicionActual} hasta " . ($nivelActual + 1) . "-" . ($posicionActual + 1);
                                                                    break;
                                                                default:
                                                                    $rangoTexto = $codigoUbicacion;
                                                            }
                                                        @endphp
                                                        {{ $rangoTexto }}
                                                    @else
                                                        {{ $codigoUbicacion }}
                                                    @endif
                                                </div>
                                        <div class="producto-info">
                                                    <div class="producto-nombre">
                                                        {{ $ubicacionData['nombre'] }}
                                                        @if(isset($ubicacionData['fuera_de_rango']) && $ubicacionData['fuera_de_rango'])
                                                            <span class="badge-fuera-rango-slot">Fuera de rango</span>
                                                        @endif
                                                    </div>
                                                    <div class="producto-stock">Stock: {{ $ubicacionData['unidades'] }}</div>
                                        </div>
                                        <div class="slot-acciones">
                                                    <button class="btn-slot-accion" data-action="ver" title="Ver detalles">
                                                <iconify-icon icon="solar:eye-bold"></iconify-icon>
                                            </button>
                                                    <button class="btn-slot-accion" data-action="editar" title="Editar producto">
                                                <iconify-icon icon="solar:pen-bold"></iconify-icon>
                                            </button>
                                                    <button class="btn-slot-accion" data-action="eliminar" title="Eliminar de ubicación">
                                                <iconify-icon icon="solar:trash-bin-minimalistic-bold"></iconify-icon>
                                            </button>
                                                    <button class="btn-slot-accion" data-action="mover" title="Mover producto">
                                                <iconify-icon icon="solar:transfer-horizontal-bold"></iconify-icon>
                                            </button>
                                        </div>
                                    @else
                                        @if($esFusionado)
                                            <div class="slot-vacio slot-fusionado-simple">
                                                <iconify-icon icon="solar:widget-2-bold-duotone"></iconify-icon>
                                                <span class="slot-fusionado-rango">
                                                    @php
                                                        $nivelActual = $nivel['numero'];
                                                        $posicionActual = $index + 1;
                                                        $rangoTexto = '';
                                                        
                                                        switch($tipoFusion) {
                                                            case 'horizontal-2':
                                                                $rangoTexto = "{$nivelActual}-{$posicionActual} hasta {$nivelActual}-" . ($posicionActual + 1);
                                                                break;
                                                            case 'horizontal-3':
                                                                $rangoTexto = "{$nivelActual}-{$posicionActual} hasta {$nivelActual}-" . ($posicionActual + 2);
                                                                break;
                                                            case 'horizontal-4':
                                                                $rangoTexto = "{$nivelActual}-{$posicionActual} hasta {$nivelActual}-" . ($posicionActual + 3);
                                                                break;
                                                            case 'vertical-2':
                                                                $rangoTexto = "{$nivelActual}-{$posicionActual} hasta " . ($nivelActual + 1) . "-{$posicionActual}";
                                                                break;
                                                            case 'cuadrado-2x2':
                                                                $rangoTexto = "{$nivelActual}-{$posicionActual} hasta " . ($nivelActual + 1) . "-" . ($posicionActual + 1);
                                                                break;
                                                            default:
                                                                $rangoTexto = "Desde {$codigoUbicacion}";
                                                        }
                                                    @endphp
                                                    {{ $rangoTexto }}
                                                </span>
                                                
                                                
                                                <div class="slot-acciones">
                                                    <button class="btn-slot-accion btn-separar-fusionado" data-action="separar" title="Separar slots" data-ubicacion-id="{{ $ubicacionData['ubicacion_id'] }}" data-slot="{{ $codigoUbicacion }}">
                                                        <iconify-icon icon="solar:widget-broken-bold-duotone"></iconify-icon>
                                                        <span>Separar</span>
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <div class="slot-vacio">
                                                <iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon>
                                                <span>Slot Vacío</span>
                                                <span class="slot-id">{{ $codigoUbicacion }}</span>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>

                
                <div id="panelControlFusion" class="panel-fusion-control hidden">
                    <div class="panel-fusion-header">
                        <div class="panel-fusion-title">
                            <iconify-icon icon="solar:widget-2-bold-duotone"></iconify-icon>
                            <span>Modo Fusión Activo</span>
                            <span class="fusion-badge">Selecciona los slots que quieres fusionar</span>
                        </div>
                        <div class="panel-fusion-contador">
                            <span id="slotsSeleccionados">0</span> slots seleccionados
                        </div>
                    </div>
                    
                    <div class="panel-fusion-acciones">
                        <button id="btnCancelarFusion" class="btn-fusion-cancelar">
                            <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                            Cancelar
                        </button>
                        <button id="btnConfirmarFusionDirecta" class="btn-fusion-confirmar" disabled>
                            <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                            <span id="textoConfirmarFusion">Fusionar Slots</span>
                        </button>
                    </div>
                </div>

                
                <div class="leyenda-estados">
                    <div class="leyenda-item">
                        <div class="leyenda-color estado-ok"></div>
                        <span>Stock Normal</span>
                    </div>
                    <div class="leyenda-item">
                        <div class="leyenda-color estado-alerta"></div>
                        <span>Stock Bajo</span>
                    </div>
                    <div class="leyenda-item">
                        <div class="leyenda-color estado-peligro"></div>
                        <span>Stock Crítico</span>
                    </div>
                    <div class="leyenda-item">
                        <div class="leyenda-color estado-vacio"></div>
                        <span>Slot Vacío</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div id="modalVerProducto" class="modal-overlay-estante hidden">
    <div class="modal-container-estante modal-ver-producto">
        <div class="modal-header-ver">
            <h3>
                <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                <span id="verProductoTitulo">Detalles del Producto</span>
            </h3>
            <button class="modal-close-btn" data-modal="modalVerProducto">&times;</button>
        </div>
        
        <div class="modal-content-ver">
            
            <div class="producto-info-header">
                <div class="producto-titulo-seccion">
                    <h2 class="producto-nombre-principal" id="verProductoNombreCompleto">Paracetamol 500mg</h2>
                    <div class="producto-concentracion-badge" id="verProductoConcentracion">500mg</div>
                </div>
                
                <div class="producto-detalles-principales">
                    <div class="detalle-item-principal">
                        <div class="detalle-icono">
                            <iconify-icon icon="solar:map-point-bold-duotone"></iconify-icon>
                        </div>
                        <div class="detalle-contenido">
                            <span class="detalle-label">Ubicación</span>
                            <span class="detalle-valor-principal" id="verProductoUbicacion">Nivel 3, Posición 2</span>
                        </div>
                    </div>
                    
                    <div class="detalle-item-principal">
                        <div class="detalle-icono">
                            <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                        </div>
                        <div class="detalle-contenido">
                            <span class="detalle-label">Stock</span>
                            <div class="stock-info-completa">
                                <span class="detalle-valor-principal" id="verProductoStockValor">22 unidades</span>
                                <span class="estado-pill" id="verProductoEstadoPill">Normal</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="producto-detalles-grid">
                <div class="detalle-card">
                    <div class="detalle-card-header">
                        <iconify-icon icon="solar:tag-bold-duotone"></iconify-icon>
                        <h4>Marca</h4>
                    </div>
                    <div class="detalle-card-value" id="verProductoMarca">Bayer Perú</div>
                </div>
                
                <div class="detalle-card">
                    <div class="detalle-card-header">
                        <iconify-icon icon="solar:dollar-bold-duotone"></iconify-icon>
                        <h4>Precio</h4>
                    </div>
                    <div class="detalle-card-value precio" id="verProductoPrecio">S/ 0.50</div>
                </div>
                
                <div class="detalle-card">
                    <div class="detalle-card-header">
                        <iconify-icon icon="solar:calendar-bold-duotone"></iconify-icon>
                        <h4>Vencimiento</h4>
                    </div>
                    <div class="detalle-card-value fecha" id="verProductoVencimiento">15/12/2025</div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer-ver">
            <button class="btn-cerrar-modal" data-modal="modalVerProducto">
                <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                Cerrar
            </button>
        </div>
    </div>
</div>

<div id="modalEditarProducto" class="modal-overlay-estante modal-editar hidden">
    <div class="modal-container-estante">
        <div class="modal-header-estante">
            <h3>
                <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                <span id="editarProductoTitulo">Editar Producto</span>
            </h3>
            <button class="modal-close-btn" data-modal="modalEditarProducto">&times;</button>
        </div>
        <div class="modal-content-estante">
            <div class="producto-info-preview">
                <div class="preview-header">
                    <iconify-icon icon="solar:pill-bold-duotone"></iconify-icon>
                    <span>Información del Producto</span>
                </div>
                <div class="preview-content">
                    <span class="preview-ubicacion" id="previewUbicacion">Ubicación: --</span>
                </div>
            </div>
            
            <form class="form-editar-producto">
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                            Stock Actual
                        </label>
                        <input type="number" class="form-input" id="editarStock" placeholder="0" min="0" required>
                        <small class="form-hint">Cantidad actual del producto en esta ubicación</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer-estante">
            <button class="btn-modal-secondary" data-modal="modalEditarProducto">
                <iconify-icon icon="solar:close-circle-bold-duotone"></iconify-icon>
                Cancelar
            </button>
            <button class="btn-modal-primary" id="btnGuardarEdicion">
                <iconify-icon icon="solar:diskette-bold"></iconify-icon>
                Guardar Cambios
            </button>
        </div>
    </div>
</div>

<div id="modalMoverProducto" class="modal-overlay-estante modal-mover hidden">
    <div class="modal-container-estante">
        <div class="modal-header-estante">
            <h3>
                <iconify-icon icon="solar:transfer-horizontal-bold-duotone"></iconify-icon>
                <span id="moverProductoTitulo">Mover Producto</span>
            </h3>
            <button class="modal-close-btn" data-modal="modalMoverProducto">&times;</button>
        </div>
        <div class="modal-content-estante">
            <div class="mover-info">
                
                <div class="producto-mover-info">
                    <h4>Producto a Mover</h4>
                    <div class="producto-card-mover">
                        <div class="producto-imagen-mover">
                            <iconify-icon icon="solar:pill-bold-duotone"></iconify-icon>
                        </div>
                        <div class="producto-detalles-mover">
                            <div class="producto-nombre-mover" id="moverProductoNombre">Paracetamol</div>
                            <div class="producto-concentracion-mover" id="moverProductoConcentracion">500mg</div>
                            <div class="producto-stock-mover" id="moverProductoStock">Stock: 22</div>
                        </div>
                    </div>
                </div>

                
                <div class="origen-destino">
                    <div class="slot-info origen">
                        <h4>Posición Actual</h4>
                        <div class="slot-card">
                            <div class="slot-numero" id="moverSlotOrigen">3-2</div>
                            <div class="slot-descripcion">Nivel 3, Posición 2</div>
                        </div>
                    </div>
                    
                    <div class="flecha-mover">
                        <iconify-icon icon="solar:arrow-right-bold"></iconify-icon>
                    </div>
                    
                    <div class="slot-info destino">
                        <h4>Nueva Posición</h4>
                        <select class="form-select" id="moverSlotDestino">
                            <option value="">Seleccionar slot destino...</option>
                            <optgroup label="Nivel 4">
                                <option value="4-3">4-3 (Nivel 4, Posición 3)</option>
                            </optgroup>
                            <optgroup label="Nivel 3">
                                <option value="3-1">3-1 (Nivel 3, Posición 1)</option>
                                <option value="3-4">3-4 (Nivel 3, Posición 4)</option>
                            </optgroup>
                            <optgroup label="Nivel 2">
                                <option value="2-1">2-1 (Nivel 2, Posición 1)</option>
                                <option value="2-4">2-4 (Nivel 2, Posición 4)</option>
                                <option value="2-5">2-5 (Nivel 2, Posición 5)</option>
                            </optgroup>
                            <optgroup label="Nivel 1">
                                <option value="1-2">1-2 (Nivel 1, Posición 2)</option>
                                <option value="1-3">1-3 (Nivel 1, Posición 3)</option>
                            </optgroup>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer-estante">
            <button class="btn-modal-secondary" data-modal="modalMoverProducto">Cancelar</button>
            <button class="btn-modal-primary" id="btnConfirmarMovimiento">
                <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                Confirmar Movimiento
            </button>
        </div>
    </div>
</div>

<div id="modalAgregarProducto" class="modal-overlay-estante hidden">
    <div class="modal-container-estante">
        <div class="modal-header-estante">
            <h3>
                <iconify-icon icon="solar:add-circle-bold-duotone"></iconify-icon>
                <span id="tituloModalAgregar">Agregar Producto al Estante</span>
            </h3>
            <button class="modal-close-btn">&times;</button>
        </div>
        <div class="modal-content-estante">
            <form class="form-agregar-producto">
                <div class="form-row">
                    <div class="form-group">
                        <label>Seleccionar Producto</label>
                        <select class="form-select" name="producto" id="selectProducto" onchange="mostrarInfoProducto(this.value)">
                            <option value="">Buscar producto...</option>
                        </select>
                        
                        <div id="infoProductoSeleccionado" class="producto-info-card hidden" style="margin-top: 12px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                            <div class="info-producto-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <iconify-icon icon="solar:box-bold-duotone" style="color: #6366f1; font-size: 16px;"></iconify-icon>
                                <span style="font-weight: 600; color: #1f2937; font-size: 14px;">Información de Stock</span>
                            </div>
                            <div class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px;">
                                <div>
                                    <span style="color: #6b7280;">Stock Total:</span>
                                    <span id="stockTotalProducto" style="font-weight: 600; color: #059669;">0 unidades</span>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">Stock Sin Ubicar:</span>
                                    <span id="stockSinUbicarProducto" style="font-weight: 600; color: #dc2626;">0 unidades</span>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">Ubicaciones Actuales:</span>
                                    <span id="ubicacionesActualesProducto" style="font-weight: 600; color: #7c3aed;">0 ubicaciones</span>
                                </div>
                                <div>
                                    <span style="color: #6b7280;">Stock Disponible:</span>
                                    <span id="stockDisponibleProducto" style="font-weight: 600; color: #0891b2;">0 unidades</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" id="grupoSlotDestino">
                        <label>Slot de Destino</label>
                        <select class="form-select" name="slot" id="selectSlot">
                            <option value="">Seleccionar slot...</option>
                        </select>
                    </div>
                    
                    <div class="form-group hidden" id="grupoSlotEspecifico">
                        <label>Ubicación</label>
                        <div class="slot-info-display">
                            <div class="slot-numero-display" id="slotNumeroDisplay">4-1</div>
                            <div class="slot-descripcion-display" id="slotDescripcionDisplay">Nivel 4, Posición 1</div>
                        </div>
                        <input type="hidden" name="slot" id="slotEspecificoValue">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Cantidad</label>
                        <input type="number" class="form-input" name="cantidad" id="cantidadProducto" placeholder="0" min="1" max="0" oninput="validarCantidadDisponible(this)">
                        <small class="form-hint" id="hintCantidad">Introduce la cantidad a ubicar</small>
                        <div id="alertaCantidad" class="alerta-cantidad hidden" style="margin-top: 4px; padding: 6px 8px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; color: #dc2626; font-size: 12px;">
                            <iconify-icon icon="solar:danger-triangle-bold" style="margin-right: 4px;"></iconify-icon>
                            <span>La cantidad no puede exceder el stock disponible</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Stock Mínimo</label>
                        <input type="number" class="form-input" name="stockMinimo" placeholder="10" min="1">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer-estante">
            <button class="btn-modal-secondary">Cancelar</button>
            <button class="btn-modal-primary">
                <iconify-icon icon="solar:diskette-bold"></iconify-icon>
                Guardar
            </button>
        </div>
    </div>
</div>

<div id="modalFusionSlots" class="modal-fusion hidden">
    <div class="modal-fusion-content">
        <div class="modal-fusion-header">
            <h3 class="modal-fusion-title">
                <iconify-icon icon="solar:widget-2-bold-duotone"></iconify-icon>
                Fusionar Slots para Productos Grandes
            </h3>
            <button class="modal-fusion-close" onclick="cerrarModalFusion()">
                <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
            </button>
        </div>
        
        <div class="modal-fusion-body">
            <div class="fusion-info">
                <p><strong>Slot seleccionado:</strong> <span id="slotOrigenFusion">4-1</span></p>
                <p>Elige cómo quieres fusionar los slots para crear espacio para productos más grandes:</p>
            </div>
            
            <div class="fusion-options">
                <div class="fusion-option" data-type="horizontal-2">
                    <div class="fusion-option-icon">
                        <iconify-icon icon="solar:list-bold-duotone"></iconify-icon>
                    </div>
                    <div class="fusion-option-title">Horizontal (2 slots)</div>
                    <div class="fusion-option-description">
                        Fusiona 2 slots horizontalmente para productos largos
                    </div>
                </div>
                
                <div class="fusion-option" data-type="horizontal-3">
                    <div class="fusion-option-icon">
                        <iconify-icon icon="solar:hamburger-menu-bold-duotone"></iconify-icon>
                    </div>
                    <div class="fusion-option-title">Horizontal (3 slots)</div>
                    <div class="fusion-option-description">
                        Fusiona 3 slots horizontalmente para productos muy largos
                    </div>
                </div>
                
                <div class="fusion-option" data-type="vertical-2">
                    <div class="fusion-option-icon">
                        <iconify-icon icon="solar:sort-vertical-bold-duotone"></iconify-icon>
                    </div>
                    <div class="fusion-option-title">Vertical (2 slots)</div>
                    <div class="fusion-option-description">
                        Fusiona 2 slots verticalmente para productos altos
                    </div>
                </div>
                
                <div class="fusion-option" data-type="cuadrado-2x2">
                    <div class="fusion-option-icon">
                        <iconify-icon icon="solar:widget-4-bold-duotone"></iconify-icon>
                    </div>
                    <div class="fusion-option-title">Cuadrado (2×2)</div>
                    <div class="fusion-option-description">
                        Fusiona 4 slots en cuadrado para productos grandes
                    </div>
                </div>
            </div>
            
            <div class="fusion-preview">
                <div class="fusion-preview-title">
                    <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                    Vista Previa
                </div>
                <div id="fusionPreviewGrid" class="fusion-preview-grid">
                    
                </div>
            </div>
        </div>
        
        <div class="modal-fusion-footer">
            <button class="btn-fusion-action btn-fusion-secondary" onclick="cerrarModalFusion()">
                Cancelar
            </button>
            <button id="btnConfirmarFusion" class="btn-fusion-action btn-fusion-primary" disabled>
                <iconify-icon icon="solar:widget-2-bold-duotone"></iconify-icon>
                Fusionar Slots
            </button>
        </div>
    </div>
</div>

@endsection

<script>

function mostrarInfoProducto(productoId) {
    const infoCard = document.getElementById('infoProductoSeleccionado');
    const cantidadInput = document.getElementById('cantidadProducto');
    
    if (!productoId) {
        infoCard.classList.add('hidden');
        cantidadInput.max = 0;
        return;
    }

    infoCard.classList.remove('hidden');
    infoCard.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <iconify-icon icon="solar:refresh-bold-duotone" class="spin" style="font-size: 24px; color: #6366f1;"></iconify-icon>
            <p style="margin: 8px 0 0 0; color: #6b7280;">Cargando información...</p>
        </div>
    `;

    fetch(`/api/productos/${productoId}/informacion-stock`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const producto = data.producto;

                infoCard.innerHTML = `
                    <div class="info-producto-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <iconify-icon icon="solar:box-bold-duotone" style="color: #6366f1; font-size: 16px;"></iconify-icon>
                        <span style="font-weight: 600; color: #1f2937; font-size: 14px;">Información de Stock - ${producto.nombre}</span>
                    </div>
                    <div class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px;">
                        <div>
                            <span style="color: #6b7280;">Stock Total:</span>
                            <span style="font-weight: 600; color: #059669;">${producto.stock_actual} unidades</span>
                        </div>
                        <div>
                            <span style="color: #6b7280;">Stock Sin Ubicar:</span>
                            <span style="font-weight: 600; color: ${producto.stock_sin_ubicar > 0 ? '#dc2626' : '#6b7280'};">${producto.stock_sin_ubicar} unidades</span>
                        </div>
                        <div>
                            <span style="color: #6b7280;">Ubicaciones Actuales:</span>
                            <span style="font-weight: 600; color: #7c3aed;">${producto.total_ubicaciones} ubicaciones</span>
                        </div>
                        <div>
                            <span style="color: #6b7280;">Stock Disponible:</span>
                            <span style="font-weight: 600; color: #0891b2;">${producto.stock_sin_ubicar} unidades</span>
                        </div>
                    </div>
                    ${producto.ubicaciones_existentes && producto.ubicaciones_existentes.length > 0 ? `
                        <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                            <small style="color: #6b7280; font-size: 12px;">
                                <strong>Ya ubicado en:</strong> ${producto.ubicaciones_existentes.join(', ')}
                            </small>
                        </div>
                    ` : ''}
                `;

                cantidadInput.max = producto.stock_sin_ubicar;
                const hintCantidad = document.getElementById('hintCantidad');
                if (hintCantidad) {
                    hintCantidad.textContent = `Máximo disponible: ${producto.stock_sin_ubicar} unidades`;
                }

                if (producto.stock_sin_ubicar <= 0) {
                    cantidadInput.disabled = true;
                    cantidadInput.placeholder = "Sin stock disponible";
                    mostrarAlertaCantidad("Este producto no tiene stock disponible para ubicar");
                } else {
                    cantidadInput.disabled = false;
                    cantidadInput.placeholder = `Máx: ${producto.stock_sin_ubicar}`;
                    ocultarAlertaCantidad();
                }
                
            } else {
                throw new Error(data.message || 'Error al cargar información');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            infoCard.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #dc2626;">
                    <iconify-icon icon="solar:danger-triangle-bold" style="font-size: 24px;"></iconify-icon>
                    <p style="margin: 8px 0 0 0;">Error al cargar información del producto</p>
                </div>
            `;
        });
}

function validarCantidadDisponible(input) {
    const cantidad = parseInt(input.value);
    const maxDisponible = parseInt(input.max);
    
    if (cantidad > maxDisponible) {
        mostrarAlertaCantidad(`La cantidad no puede exceder ${maxDisponible} unidades disponibles`);
        input.value = maxDisponible;
    } else if (cantidad <= 0) {
        mostrarAlertaCantidad("La cantidad debe ser mayor a 0");
    } else {
        ocultarAlertaCantidad();
    }
}

function mostrarAlertaCantidad(mensaje) {
    const alerta = document.getElementById('alertaCantidad');
    if (alerta) {
        alerta.querySelector('span').textContent = mensaje;
        alerta.classList.remove('hidden');
    }
}

function ocultarAlertaCantidad() {
    const alerta = document.getElementById('alertaCantidad');
    if (alerta) {
        alerta.classList.add('hidden');
    }
}

</script>
