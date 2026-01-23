@php
    header('Content-Type: text/html; charset=utf-8');
    $fecha = date('d/m/Y h:i A');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .header-bg { background-color: #BFDBFE; color: #1E40AF; }
        .border { border: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<table>
    
    <tr>
        <th colspan="8" style="font-size: 22px; font-weight: bold; text-align: center; height: 50px; color: #FFFFFF; background-color: #EF4444; vertical-align: middle;">
            REPORTE DE VENTAS - BOTICA SAN ANTONIO
        </th>
    </tr>
    <tr>
        <th colspan="8" style="font-size: 10px; text-align: center; height: 35px; color: #1e293b; font-weight: bold;">
            Período: {{ $datos['tituloPeriodo'] }}
        </th>
    </tr>
    <tr>
        <th colspan="8" style="font-size: 10px; text-align: center; height: 35px; color: #64748b; font-style: italic;">
            Generado el: {{ date('d/m/Y h:i A') }}
        </th>
    </tr>
    <tr><th colspan="8" style="height: 20px;"></th></tr>

    
    <tr>
        <th colspan="3" style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: left; height: 35px; padding-left: 10px; vertical-align: middle;">
            RESUMEN GENERAL
        </th>
        <th colspan="5"></th>
    </tr>
    <tr>
        <td colspan="2" style="font-weight: bold; border: 1px solid #CCCCCC; height: 30px; vertical-align: middle;">Total de Ventas:</td>
        <td style="text-align: center; border: 1px solid #CCCCCC; vertical-align: middle;">{{ $datos['total_ventas'] }}</td>
        <td colspan="5"></td>
    </tr>
    <tr>
        <td colspan="2" style="font-weight: bold; border: 1px solid #CCCCCC; height: 30px; vertical-align: middle;">Ingresos Totales:</td>
        <td style="text-align: right; border: 1px solid #CCCCCC; font-weight: bold; color: #10B981; vertical-align: middle;">S/ {{ number_format($datos['total_ingresos'], 2) }}</td>
        <td colspan="5"></td>
    </tr>
    <tr><th colspan="8" style="height: 20px;"></th></tr>

    
    <tr>
        <th colspan="3" style="background-color: #10B981; color: #FFFFFF; font-weight: bold; text-align: left; height: 35px; padding-left: 10px; vertical-align: middle;">
            MÉTODOS DE PAGO
        </th>
        <th colspan="5"></th>
    </tr>
    <tr style="background-color: #f8fafc;">
        <th style="border: 1px solid #CCCCCC; font-weight: bold; text-align: left; width: 180px; height: 30px; vertical-align: middle;">Método</th>
        <th style="border: 1px solid #CCCCCC; font-weight: bold; text-align: center; width: 120px; vertical-align: middle;">Ventas</th>
        <th style="border: 1px solid #CCCCCC; font-weight: bold; text-align: right; width: 180px; vertical-align: middle;">Monto Total</th>
        <th colspan="5"></th>
    </tr>
    @php
        $totalMetodosCount = $datos['ventas_por_metodo']->sum('total') ?: 1;
    @endphp
        @foreach(['efectivo', 'tarjeta', 'yape'] as $m)
        @php
            $metodoData = $datos['ventas_por_metodo']->firstWhere('metodo_pago', $m);
            $count = $metodoData->total ?? 0;
            $porcentaje = $totalMetodosCount > 0 ? ($count / $totalMetodosCount) * 100 : 0;
            $montoEstimado = ($porcentaje / 100) * $datos['total_ingresos'];
        @endphp
        <tr>
            <td style="border: 1px solid #CCCCCC; height: 30px; text-transform: capitalize; vertical-align: middle;">{{ $m }}</td>
            <td style="border: 1px solid #CCCCCC; text-align: center; vertical-align: middle;">{{ $count }}</td>
            <td style="border: 1px solid #CCCCCC; text-align: right; vertical-align: middle;">S/ {{ number_format($montoEstimado, 2) }}</td>
            <td colspan="5"></td>
        </tr>
    @endforeach
    <tr><th colspan="8" style="height: 30px;"></th></tr>

    @if($tipo === 'detallado' && isset($datos['ventas_lista']))
        
        <thead>
            <tr>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: center; width: 60px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">#</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: center; width: 150px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Fecha</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: center; width: 150px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">N° Venta</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: left; width: 300px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Cliente</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: center; width: 150px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Vendedor</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: center; width: 120px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Método</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: right; width: 120px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Total</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: center; width: 120px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['ventas_lista'] as $index => $venta)
                <tr style="{{ $index % 2 == 0 ? '' : 'background-color: #F3F4F6;' }}">
                    <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $index + 1 }}</td>
                    <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y H:i') }}</td>
                    <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $venta->numero_venta }}</td>
                    <td style="text-align: left; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $venta->cliente_razon_social ?? $venta->cliente?->nombre_completo ?? 'Cliente General' }}</td>
                    <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $venta->usuario->name ?? 'N/A' }}</td>
                    <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle; text-transform: uppercase;">{{ $venta->metodo_pago }}</td>
                    <td style="text-align: right; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle; font-weight: bold;">S/ {{ number_format($venta->total, 2) }}</td>
                    <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle; text-transform: uppercase;">{{ $venta->estado }}</td>
                </tr>
            @endforeach
        </tbody>
    @else
        
        <thead>
            <tr>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: center; width: 60px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">#</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: left; width: 350px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Producto</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: center; width: 150px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Marca</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: center; width: 100px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Cantidad</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: right; width: 130px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Total Vendido</th>
                <th style="background-color: #6366F1; color: #FFFFFF; font-weight: bold; text-align: center; width: 150px; height: 40px; vertical-align: middle; border: 1px solid #4F46E5;">Rendimiento</th>
            </tr>
        </thead>
        <tbody>
            @php
                $maxVendido = $datos['detalle_productos_vendidos']->max('total_vendido') ?: 1;
            @endphp
            @foreach($datos['detalle_productos_vendidos'] as $index => $prod)
                <tr style="{{ $index % 2 == 0 ? '' : 'background-color: #F3F4F6;' }}">
                    <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $index + 1 }}</td>
                    <td style="text-align: left; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $prod->nombre }}</td>
                    <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $prod->marca ?? '-' }}</td>
                    <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $prod->cantidad_total }}</td>
                    <td style="text-align: right; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle; font-weight: bold;">S/ {{ number_format($prod->total_vendido, 2) }}</td>
                    <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">
                        {{ number_format(($prod->total_vendido / $maxVendido) * 100, 1) }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    @endif
</table>
</body>
</html>
