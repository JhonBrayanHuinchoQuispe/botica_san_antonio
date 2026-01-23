@php
    header('Content-Type: text/html; charset=utf-8');
    $fecha = date('d/m/Y H:i');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<table>
    
    <tr><th colspan="12" style="height: 20px;"></th></tr>
    
    
    <tr>
        <th style="width: 50px;"></th> 
        <th colspan="9" style="font-size: 22px; font-weight: bold; text-align: center; height: 50px; color: #1e293b; vertical-align: middle;">
            REPORTE DE SALUD DEL INVENTARIO - BOTICA SAN ANTONIO
        </th>
    </tr>
    <tr>
        <th style="width: 50px;"></th>
        <th colspan="9" style="font-size: 13px; text-align: center; height: 30px; color: #64748b; font-style: italic;">
            Fecha de Generación: {{ $fecha }}
        </th>
    </tr>
    <tr><th colspan="12" style="height: 25px;"></th></tr> 

    <thead>
        <tr>
            <th style="width: 50px;"></th> 
            <th style="background-color: #BFDBFE; color: #1E40AF; font-weight: bold; text-align: center; width: 100px; height: 40px; vertical-align: middle; border: 2px solid #1E40AF;">ID</th>
            <th style="background-color: #BFDBFE; color: #1E40AF; font-weight: bold; text-align: left; width: 350px; height: 40px; vertical-align: middle; border: 2px solid #1E40AF;">Producto</th>
            <th style="background-color: #BFDBFE; color: #1E40AF; font-weight: bold; text-align: center; width: 150px; height: 40px; vertical-align: middle; border: 2px solid #1E40AF;">Lote</th>
            <th style="background-color: #BFDBFE; color: #1E40AF; font-weight: bold; text-align: center; width: 100px; height: 40px; vertical-align: middle; border: 2px solid #1E40AF;">Stock</th>
            <th style="background-color: #BFDBFE; color: #1E40AF; font-weight: bold; text-align: right; width: 150px; height: 40px; vertical-align: middle; border: 2px solid #1E40AF;">P. Compra</th>
            <th style="background-color: #BFDBFE; color: #1E40AF; font-weight: bold; text-align: right; width: 150px; height: 40px; vertical-align: middle; border: 2px solid #1E40AF;">P. Venta</th>
            <th style="background-color: #BFDBFE; color: #1E40AF; font-weight: bold; text-align: right; width: 180px; height: 40px; vertical-align: middle; border: 2px solid #1E40AF;">Valorización</th>
            <th style="background-color: #BFDBFE; color: #1E40AF; font-weight: bold; text-align: center; width: 150px; height: 40px; vertical-align: middle; border: 2px solid #1E40AF;">Vencimiento</th>
            <th style="background-color: #BFDBFE; color: #1E40AF; font-weight: bold; text-align: center; width: 200px; height: 40px; vertical-align: middle; border: 2px solid #1E40AF;">Estado Salud</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $index => $lote)
            @php
                $dias = $lote->dias_para_vencer;
                $estado = 'Saludable';
                $color = '#DCFCE7';
                if ($dias !== null && $dias < 0) { $estado = 'VENCIDO'; $color = '#FECACA'; }
                elseif ($dias !== null && $dias <= 90) { $estado = 'RIESGO ALTO'; $color = '#FFEDD5'; }
                elseif ($dias !== null && $dias <= 180) { $estado = 'RIESGO MEDIO'; $color = '#FEF9C3'; }
            @endphp
            <tr>
                <td style="width: 50px;"></td>
                <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $index + 1 }}</td>
                <td style="text-align: left; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $lote->producto?->nombre ?? 'N/A' }}</td>
                <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $lote->lote }}</td>
                <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $lote->cantidad }}</td>
                <td style="text-align: right; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">S/ {{ number_format($lote->precio_compra_lote, 2) }}</td>
                <td style="text-align: right; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">S/ {{ number_format($lote->precio_venta_lote, 2) }}</td>
                <td style="text-align: right; border: 1px solid #e2e8f0; font-weight: bold; height: 30px; vertical-align: middle;">S/ {{ number_format($lote->cantidad * $lote->precio_compra_lote, 2) }}</td>
                <td style="text-align: center; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle;">{{ $lote->fecha_vencimiento ? $lote->fecha_vencimiento->format('d/m/Y') : 'N/A' }}</td>
                <td style="text-align: center; background-color: {{ $color }}; border: 1px solid #e2e8f0; height: 30px; vertical-align: middle; font-weight: bold; color: #1e293b;">{{ $estado }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
