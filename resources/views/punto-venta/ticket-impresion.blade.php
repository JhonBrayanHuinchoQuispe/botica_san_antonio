<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Venta - {{ $venta->numero_venta }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.2;
            margin: 0;
            padding: 10px;
            width: 80mm;
            max-width: 300px;
        }
        
        .ticket-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .ticket-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .ticket-info {
            margin-bottom: 10px;
            font-size: 11px;
        }
        
        .ticket-products {
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .product-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        
        .product-name {
            flex: 1;
            margin-right: 10px;
        }
        
        .product-qty-price {
            white-space: nowrap;
        }
        
        .ticket-totals {
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        
        .total-final {
            font-weight: bold;
            font-size: 13px;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        
        .ticket-footer {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
        }
        
        .print-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Imprimir Ticket</button>
    </div>
    
    <div class="ticket-header">
        <div class="ticket-title">BOTICA SISTEMA</div>
        <div>RUC: 20123456789</div>
        <div>Dirección de la Botica</div>
        <div>Teléfono: (01) 123-4567</div>
    </div>
    
    <div class="ticket-info">
        <div><strong>TICKET DE VENTA</strong></div>
        <div>N°: {{ $venta->numero_venta }}</div>
        <div>Fecha: {{ optional($venta->fecha_venta ?? $venta->created_at)->format('d/m/Y H:i') ?? '-' }}</div>
        <div>Cajero: {{ $venta->usuario->name ?? 'Sistema' }}</div>
        @if($venta->cliente)
        <div>Cliente: {{ $venta->cliente->nombre_completo }}</div>
        @endif
    </div>
    
    <div class="ticket-products">
        <div style="font-weight: bold; margin-bottom: 5px;">PRODUCTOS:</div>
        @foreach($venta->detalles as $detalle)
        <div class="product-line">
            <div class="product-name">{{ $detalle->producto->nombre }}</div>
        </div>
        <div class="product-line">
            <div class="product-qty-price">
                {{ $detalle->cantidad }} x S/ {{ number_format($detalle->precio_unitario, 2) }} = S/ {{ number_format($detalle->subtotal, 2) }}
            </div>
        </div>
        @endforeach
    </div>
    
    <div class="ticket-totals">
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
            <span>TOTAL:</span>
            <span>S/ {{ number_format($venta->total, 2) }}</span>
        </div>
        
        @if($venta->metodo_pago === 'efectivo' && $venta->efectivo_recibido > 0)
        <div class="total-line">
            <span>Efectivo recibido:</span>
            <span>S/ {{ number_format($venta->efectivo_recibido, 2) }}</span>
        </div>
        <div class="total-line">
            <span>Vuelto:</span>
            <span>S/ {{ number_format($venta->vuelto, 2) }}</span>
        </div>
        @endif
    </div>
    
    <div class="ticket-footer">
        <div>Método de pago: {{ ucfirst($venta->metodo_pago) }}</div>
        <div>Tipo: {{ ucfirst($venta->tipo_comprobante) }}</div>
        <div style="margin-top: 10px;">¡Gracias por su compra!</div>
        <div>Vuelva pronto</div>
    </div>
    
    <script>
        // Auto-imprimir si se abre en nueva ventana
        if (window.opener) {
            setTimeout(() => {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>