<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta Electrónica - {{ $venta->numero_venta }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .boleta-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .boleta-header {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .boleta-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .boleta-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .boleta-body {
            padding: 30px;
        }
        
        .empresa-info {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .empresa-info h2 {
            font-size: 24px;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .empresa-info p {
            color: #64748b;
            margin: 2px 0;
        }
        
        .venta-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .detail-section h3 {
            color: #e53e3e;
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .detail-label {
            color: #64748b;
            font-weight: 500;
        }
        
        .detail-value {
            color: #2d3748;
            font-weight: 600;
        }
        
        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            background: #f8fafc;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .productos-table th {
            background: #e53e3e;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .productos-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .productos-table tr:last-child td {
            border-bottom: none;
        }
        
        .productos-table tr:nth-child(even) {
            background: white;
        }
        
        .totales-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 25px;
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .total-item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
        }
        
        .total-final {
            border-top: 2px solid #e53e3e;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 20px;
            font-weight: 700;
            color: #e53e3e;
        }
        
        .footer-info {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            color: #64748b;
            font-size: 14px;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #e53e3e;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            background: #c53030;
            transform: translateY(-2px);
        }
        
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            
            .boleta-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: none !important;
            }
            
            .print-btn {
                display: none !important;
            }
        }
        
        @media (max-width: 768px) {
            .venta-details {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .productos-table {
                font-size: 14px;
            }
            
            .productos-table th,
            .productos-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">
        🖨️ Imprimir
    </button>
    
    <div class="boleta-container">
        <div class="boleta-header">
            <h1>BOLETA ELECTRÓNICA</h1>
            <p>{{ $venta->numero_venta }}</p>
        </div>
        
        <div class="boleta-body">
            <div class="empresa-info">
                <h2>🏥 Botica San Antonio</h2>
                <p>RUC: 20123456789</p>
                <p>Jr. Los Remedios 123, Lima - Perú</p>
                <p>Teléfono: (01) 234-5678</p>
                <p>Email: ventas@boticasanantonio.com</p>
            </div>
            
            <div class="venta-details">
                <div class="detail-section">
                    <h3>📄 Datos de la Venta</h3>
                    <div class="detail-item">
                        <span class="detail-label">Fecha:</span>
                        <span class="detail-value">{{ $venta->fecha_venta->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Vendedor:</span>
                        <span class="detail-value">{{ $venta->usuario->name ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Método de Pago:</span>
                        <span class="detail-value">{{ ucfirst($venta->metodo_pago) }}</span>
                    </div>
                    @if($venta->metodo_pago === 'efectivo' && $venta->efectivo_recibido)
                    <div class="detail-item">
                        <span class="detail-label">Efectivo Recibido:</span>
                        <span class="detail-value">S/. {{ number_format($venta->efectivo_recibido, 2) }}</span>
                    </div>
                    @if($venta->vuelto > 0)
                    <div class="detail-item">
                        <span class="detail-label">Vuelto:</span>
                        <span class="detail-value">S/. {{ number_format($venta->vuelto, 2) }}</span>
                    </div>
                    @endif
                    @endif
                </div>
                
                @if($venta->cliente)
                <div class="detail-section">
                    <h3>👤 Datos del Cliente</h3>
                    <div class="detail-item">
                        <span class="detail-label">Nombre:</span>
                        <span class="detail-value">{{ $venta->cliente->nombre_completo }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">DNI:</span>
                        <span class="detail-value">{{ $venta->cliente->dni }}</span>
                    </div>
                </div>
                @endif
            </div>
            
            <table class="productos-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $detalle)
                    <tr>
                        <td>
                            <strong>{{ $detalle->producto->nombre }}</strong><br>
                            <small style="color: #64748b;">{{ $detalle->producto->concentracion ?? '' }}</small>
                        </td>
                        <td>{{ $detalle->cantidad }}</td>
                        <td>S/. {{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td><strong>S/. {{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="totales-section">
                <div class="total-item">
                    <span>Subtotal:</span>
                    <span>S/. {{ number_format($venta->subtotal, 2) }}</span>
                </div>
                <div class="total-item">
                    <span>IGV (18%):</span>
                    <span>S/. {{ number_format($venta->igv, 2) }}</span>
                </div>
                <div class="total-item total-final">
                    <span>TOTAL:</span>
                    <span>S/. {{ number_format($venta->total, 2) }}</span>
                </div>
            </div>
            
            <div class="footer-info">
                <p><strong>¡Gracias por su compra!</strong></p>
                <p>Esta boleta ha sido generada electrónicamente</p>
                <p>Conserve este documento para futuras referencias</p>
                <p style="margin-top: 15px; font-size: 12px;">
                    Fecha de emisión: {{ now()->format('d/m/Y H:i:s') }}
                </p>
            </div>
        </div>
    </div>
</body>
</html> 