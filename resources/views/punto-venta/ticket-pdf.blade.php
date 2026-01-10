<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Venta - {{ $venta->numero_venta }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .comprobante-container {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        
        .company-info {
            font-size: 11px;
            color: #666;
        }
        
        .comprobante-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-left, .info-right {
            flex: 1;
        }
        
        .info-right {
            text-align: right;
        }
        
        .comprobante-title {
            background-color: #007bff;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            border-radius: 3px;
        }
        
        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .productos-table th {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        
        .productos-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        
        .productos-table .text-right {
            text-align: right;
        }
        
        .productos-table .text-center {
            text-align: center;
        }
        
        .totales {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .total-final {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #007bff;
            padding-top: 10px;
            margin-top: 10px;
            color: #007bff;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .qr-section {
            text-align: center;
            margin: 20px 0;
        }
        
        @media print {
            body { margin: 0; }
            .comprobante-container { border: none; }
        }
    </style>
</head>
<body>
    <div class="comprobante-container">
        <div class="header">
            <div class="company-name">BOTICA SISTEMA</div>
            <div class="company-info">
                RUC: 20123456789<br>
                Dirección de la Botica<br>
                Teléfono: (01) 123-4567<br>
                Email: info@boticasistema.com
            </div>
        </div>
        
        <div class="comprobante-title">
            {{ strtoupper($venta->tipo_comprobante) }} DE VENTA
        </div>
        
        <div class="comprobante-info">
            <div class="info-left">
                <strong>N° Comprobante:</strong> {{ $venta->numero_venta }}<br>
            <strong>Fecha:</strong> {{ optional($venta->fecha_venta ?? $venta->created_at)->format('d/m/Y H:i') ?? '-' }}<br>
                <strong>Cajero:</strong> {{ $venta->usuario->name ?? 'Sistema' }}<br>
                @if($venta->cliente)
                <strong>Cliente:</strong> {{ $venta->cliente->nombre_completo }}<br>
                @if($venta->cliente->documento)
                <strong>Documento:</strong> {{ $venta->cliente->documento }}
                @endif
                @endif
            </div>
            <div class="info-right">
                <strong>Método de Pago:</strong> {{ ucfirst($venta->metodo_pago) }}<br>
                <strong>Estado:</strong> {{ ucfirst($venta->estado) }}<br>
                @if($venta->comprobante_electronico)
                <strong>Serie:</strong> {{ $venta->comprobante_electronico->serie_numero }}
                @endif
            </div>
        </div>
        
        <table class="productos-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-right">P. Unit.</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                <tr>
                    <td>
                        <strong>{{ $detalle->producto->nombre }}</strong><br>
                        <small>{{ $detalle->producto->presentacion ?? '' }}</small>
                    </td>
                    <td class="text-center">{{ $detalle->cantidad }}</td>
                    <td class="text-right">S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="totales">
            @if($venta->descuento_monto > 0)
            <div class="total-line">
                <span>Subtotal:</span>
                <span>S/ {{ number_format($venta->subtotal + $venta->descuento_monto, 2) }}</span>
            </div>
            <div class="total-line">
                <span>Descuento ({{ $venta->descuento_porcentaje }}%):</span>
                <span>-S/ {{ number_format($venta->descuento_monto, 2) }}</span>
            </div>
            @endif
            
            @if($venta->igv > 0)
            <div class="total-line">
                <span>Subtotal:</span>
                <span>S/ {{ number_format($venta->subtotal, 2) }}</span>
            </div>
            <div class="total-line">
                <span>IGV (18%):</span>
                <span>S/ {{ number_format($venta->igv, 2) }}</span>
            </div>
            @endif
            
            <div class="total-line total-final">
                <span>TOTAL A PAGAR:</span>
                <span>S/ {{ number_format($venta->total, 2) }}</span>
            </div>
            
            @if($venta->metodo_pago === 'efectivo' && $venta->efectivo_recibido > 0)
            <div class="total-line" style="margin-top: 10px;">
                <span>Efectivo recibido:</span>
                <span>S/ {{ number_format($venta->efectivo_recibido, 2) }}</span>
            </div>
            <div class="total-line">
                <span>Vuelto:</span>
                <span>S/ {{ number_format($venta->vuelto, 2) }}</span>
            </div>
            @endif
        </div>
        
        @if($venta->comprobante_electronico && $venta->comprobante_electronico->qr_code)
        <div class="qr-section">
            <p><strong>Código QR:</strong></p>
            <div style="font-family: monospace; font-size: 10px; word-break: break-all;">
                {{ $venta->comprobante_electronico->qr_code }}
            </div>
        </div>
        @endif
        
        <div class="footer">
            <p><strong>¡Gracias por su compra!</strong></p>
            <p>Este comprobante fue generado electrónicamente</p>
            <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>