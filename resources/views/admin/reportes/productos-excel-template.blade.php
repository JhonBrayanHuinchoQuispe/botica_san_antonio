@php
    header('Content-Type: text/html; charset=utf-8');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .header-bg { background-color: #FEE2E2; color: #991B1B; }
        .border { border: 1px solid #e2e8f0; }
        td, th { 
            padding: 8px 12px; 
            vertical-align: middle;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
<table>
    
    <tr>
        <th colspan="12" style="font-size: 22px; font-weight: bold; text-align: center; height: 60px; color: #FFFFFF; background-color: #EF4444; vertical-align: middle;">
            {{ $datos['titulo'] }}
        </th>
    </tr>
    <tr>
        <th colspan="12" style="font-size: 17px; text-align: center; height: 35px; color: #1e293b; font-weight: bold; vertical-align: middle;">
            Fecha de Generación: {{ now()->format('d/m/Y') }} | Total Productos: {{ $datos['total'] }}
        </th>
    </tr>
    <tr><th colspan="12" style="height: 15px;"></th></tr>

    
    <thead>
        <tr>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: center; width: 60px; height: 40px; vertical-align: middle; border: 1px solid #FECACA;">#</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: left; width: 450px; height: 45px; vertical-align: middle; border: 1px solid #FECACA;">Producto</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: left; width: 250px; height: 45px; vertical-align: middle; border: 1px solid #FECACA;">Categoría</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: center; width: 180px; height: 45px; vertical-align: middle; border: 1px solid #FECACA;">Marca</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: left; width: 300px; height: 45px; vertical-align: middle; border: 1px solid #FECACA;">Presentación</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: left; width: 200px; height: 45px; vertical-align: middle; border: 1px solid #FECACA;">Lotes</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: center; width: 120px; height: 40px; vertical-align: middle; border: 1px solid #FECACA;">Stock Actual</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: center; width: 120px; height: 40px; vertical-align: middle; border: 1px solid #FECACA;">Stock Mín.</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: right; width: 130px; height: 40px; vertical-align: middle; border: 1px solid #FECACA;">P. Compra</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: right; width: 130px; height: 40px; vertical-align: middle; border: 1px solid #FECACA;">P. Venta</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: center; width: 150px; height: 40px; vertical-align: middle; border: 1px solid #FECACA;">Vencimiento</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; text-align: center; width: 120px; height: 40px; vertical-align: middle; border: 1px solid #FECACA;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos['productos'] as $index => $prod)
            @php
                $colorEstado = '#10B981';
                if($prod->estado == 'Agotado') $colorEstado = '#EF4444';
                elseif($prod->estado == 'Bajo stock') $colorEstado = '#F59E0B';
                elseif($prod->estado == 'Por vencer') $colorEstado = '#F59E0B';
                elseif($prod->estado == 'Vencido') $colorEstado = '#EF4444';

                $presentacionesText = $prod->presentaciones->map(function($p) {
                    return $p->nombre_presentacion . ' (x' . $p->unidades_por_presentacion . ')';
                })->implode(', ');

                $lotesText = $prod->ubicaciones->where('cantidad', '>', 0)->pluck('lote')->filter()->unique()->implode(', ');
                if (empty($lotesText) && $prod->lote) {
                    $lotesText = $prod->lote;
                }
            @endphp
            <tr style="{{ $index % 2 == 0 ? '' : 'background-color: #F9FAFB;' }}">
                <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $index + 1 }}</td>
                <td style="text-align: left; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle; font-weight: bold;">{{ $prod->nombre }} {{ $prod->concentracion }}</td>
                <td style="text-align: left; border: 1px solid #e2e8f0; height: 35px; vertical-align: middle; white-space: normal; word-wrap: break-word;">{{ $prod->categoria_model?->nombre ?? $prod->categoria ?? '-' }}</td>
                <td style="text-align: center; border: 1px solid #e2e8f0; height: 35px; vertical-align: middle;">{{ $prod->marca ?? '-' }}</td>
                <td style="text-align: left; border: 1px solid #e2e8f0; height: 35px; vertical-align: middle; white-space: normal; word-wrap: break-word;">{{ $presentacionesText ?: '-' }}</td>
                <td style="text-align: left; border: 1px solid #e2e8f0; height: 35px; vertical-align: middle; white-space: normal; word-wrap: break-word;">{{ $lotesText ?: '-' }}</td>
                <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle; font-weight: bold;">{{ $prod->stock_actual }}</td>
                <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle; color: #64748b;">{{ $prod->stock_minimo }}</td>
                <td style="text-align: right; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">S/ {{ number_format($prod->precio_compra, 2) }}</td>
                <td style="text-align: right; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle; font-weight: bold;">S/ {{ number_format($prod->precio_venta, 2) }}</td>
                <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $prod->fecha_vencimiento ? \Carbon\Carbon::parse($prod->fecha_vencimiento)->format('d/m/Y') : '-' }}</td>
                <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle; color: {{ $colorEstado }}; font-weight: bold; text-transform: uppercase;">{{ $prod->estado }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
