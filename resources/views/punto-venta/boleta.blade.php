<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta Electrónica - {{ $venta->numero_venta }}</title>
    <style>
        @media print {
            /* Mantener colores en impresión (Chrome/Edge/Firefox) */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body { 
                margin: 0; 
                padding: 0;
                background: white !important;
            }
            .boleta-container {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 10mm !important;
                width: 100% !important;
                max-width: none !important;
                box-sizing: border-box;
            }
            @page {
                size: A4;
                margin: 5mm;
                /* Intentar preservar colores en algunos motores */
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }

            /* Forzar los colores del tema rojo en impresión */
            .header { border-bottom: 2px solid #000 !important; }
            .document-section { border: 2px solid #000 !important; }
            .company-name { color: #000 !important; }
            .products-table th { background: #000 !important; color: #ffffff !important; }
            .total-final { background: #000 !important; color: #ffffff !important; }
            .footer { border-top: 2px solid #000 !important; }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            background: white;
            color: black;
        }

        .boleta-container {
            width: 190mm;
            max-width: 750px;
            margin: 0 auto;
            padding: 15mm;
            background: white;
            color: black;
            min-height: 270mm;
            box-sizing: border-box;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }

        .company-section {
            flex: 1;
        }
        
        /* Se elimina caja duplicada de boleta electrónica */
        
        .boleta-title {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }
        
        .boleta-number {
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 10px;
            display: inline-block;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
            color: #dc2626;
        }

        .company-info {
            font-size: 11px;
            margin: 2px 0;
            color: #333;
        }

        .document-section {
            text-align: center;
            border: 2px solid #000;
            padding: 15px;
            min-width: 200px;
        }

        .document-type {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .document-number {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
        }

        .client-info {
            margin: 20px 0;
            background: #fff;
            padding: 0;
        }

        .info-row {
            display: flex;
            margin: 8px 0;
        }

        .info-label {
            font-weight: bold;
            width: 120px;
            color: #333;
        }

        .info-value {
            flex: 1;
            color: #000;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10px;
        }

        .products-table th {
            background: #000;
            color: white;
            padding: 8px 6px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #000;
            font-size: 10px;
        }

        .products-table td {
            padding: 6px 4px;
            border: 1px solid #ccc;
            vertical-align: middle;
            font-size: 10px;
        }

        .products-table tr:nth-child(even) {
            background: #fff;
        }

        .products-table .col-cant { width: 10%; }
        .products-table .col-unidad { width: 14%; }
        .products-table .col-descripcion { width: 46%; }
        .products-table .col-precio { width: 12%; }
        .products-table .col-desc { width: 8%; }
        .products-table .col-total { width: 10%; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .totals-section {
            margin-top: 20px;
            float: right;
            width: 300px;
            clear: both;
            border: 1px solid #000;
        }

        .total-row { display: none; }
        .totals-section .row {
            display: grid;
            grid-template-columns: 1fr 100px;
            align-items: center;
            padding: 6px 8px;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }
        .totals-section .row:last-child { border-bottom: none; }
        .total-label { font-weight: bold; color: #333; }
        .total-amount { font-weight: bold; color: #000; text-align: right; }

        .total-label {
            font-weight: bold;
            color: #333;
        }

        .total-amount {
            font-weight: bold;
            color: #000;
        }

        .total-final {
            background: #000;
            color: white;
            padding: 10px;
            margin-top: 8px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 0;
        }

        .payment-info {
            clear: both;
            margin-top: 20px;
            padding: 10px 0;
            background: #fff;
            border-radius: 0;
            font-size: 12px;
        }

        .qr-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0;
            clear: both;
            page-break-inside: avoid;
        }

        .qr-placeholder {
            border: 2px solid #2E8B57;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .qr-info {
            flex: 1;
            margin-left: 15px;
            font-size: 10px;
            line-height: 1.4;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #000;
            font-size: 12px;
            color: #666;
            page-break-inside: avoid;
        }

        .footer div {
            margin: 2px 0;
        }

        .footer strong {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="boleta-container">
        <!-- Header -->
        <div class="header">
            <div class="company-section">
                <div class="logo">
                    <img src="{{ asset('assets/images/logotipo.png') }}" alt="Botica San Antonio Logo">
                </div>
                <div class="company-name">BOTICA SAN ANTONIO</div>
                <div class="company-info"><strong>Dirección:</strong> AV. FERROCARRIL 188, CHILCA 12003</div>
                <div class="company-info"><strong>Correo:</strong> BOTICA@SANANTONIO.COM</div>
            </div>
            
            <div class="document-section">
                <div class="document-type">BOLETA DE VENTA ELECTRÓNICA</div>
                <div class="document-number">{{ $venta->numero_venta }}</div>
            </div>
        </div>

        <!-- Client Information -->
        <div class="client-info">
            <div class="info-row">
                <span class="info-label">Cliente:</span>
                <span class="info-value">{{ $venta->cliente->nombre ?? 'CLIENTE GENERAL' }}</span>
            </div>
            @if($venta->cliente && $venta->cliente->documento)
            <div class="info-row">
                <span class="info-label">RUC:</span>
                <span class="info-value">{{ $venta->cliente->documento }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Dirección:</span>
                <span class="info-value">HUANCAYO</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha de emisión:</span>
                <span class="info-value">{{ $venta->fecha_venta->format('Y-m-d') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha de vencimiento:</span>
                <span class="info-value">{{ $venta->fecha_venta->format('Y-m-d') }}</span>
            </div>
        </div>

        <!-- Products Table -->
        <table class="products-table">
            <thead>
                <tr>
                    <th class="col-cant">Cantidad</th>
                    <th class="col-unidad">Unidad Medida</th>
                    <th class="col-descripcion">Descripción</th>
                    <th class="col-precio">Valor Unitario(*)</th>
                    <th class="col-desc">Descuento(*)</th>
                    <th class="col-total">Importe de Venta(**)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                <tr>
                    <td class="text-center col-cant">{{ number_format($detalle->cantidad, 2) }}</td>
                    <td class="text-center col-unidad">UNIDAD</td>
                    <td class="col-descripcion">{{ $detalle->producto->nombre }}</td>
                    <td class="text-center col-precio">S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
                    @php $detalleDesc = isset($detalle->descuento_monto) ? (float)$detalle->descuento_monto : (isset($detalle->descuento) ? (float)$detalle->descuento : 0); @endphp
                    <td class="text-center col-desc">S/ {{ number_format($detalleDesc, 2) }}</td>
                    <td class="text-center col-total">S/ {{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="row"><span class="total-label">Op. Gravada:</span><span class="total-amount">S/ {{ number_format($venta->subtotal, 2) }}</span></div>
            <div class="row"><span class="total-label">Op. Exonerada:</span><span class="total-amount">S/ 0.00</span></div>
            <div class="row"><span class="total-label">Op. Inafecta:</span><span class="total-amount">S/ 0.00</span></div>
            <div class="row"><span class="total-label">ISC:</span><span class="total-amount">S/ 0.00</span></div>
            <div class="row"><span class="total-label">IGV:</span><span class="total-amount">S/ {{ number_format($venta->igv, 2) }}</span></div>
            <div class="row"><span class="total-label">Otros Cargos:</span><span class="total-amount">S/ 0.00</span></div>
            <div class="row"><span class="total-label">Otros Tributos:</span><span class="total-amount">S/ 0.00</span></div>
            <div class="row"><span class="total-label">Monto de Redondeo:</span><span class="total-amount">S/ 0.00</span></div>
            <div class="total-final">
                <div class="row" style="border-bottom:none;">
                    <span>TOTAL A PAGAR:</span>
                    <span class="text-right">S/ {{ number_format($venta->total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div><strong>SON:</strong> {{ numeroALetras($venta->total) ?? 'CIENTO SESENTA Y DOS CON 25/100 SOLES' }}</div>
            <div style="margin-top:8px;"><strong>Met. Pago:</strong> {{ ucfirst($venta->metodo_pago) }}</div>
            @if($venta->metodo_pago === 'efectivo' && $venta->efectivo_recibido > 0)
            <div><strong>Recibido:</strong> S/ {{ number_format($venta->efectivo_recibido, 2) }}</div>
            <div><strong>Vuelto:</strong> S/ {{ number_format($venta->vuelto, 2) }}</div>
            @elseif(in_array($venta->metodo_pago, ['tarjeta','yape']))
            <div><strong>Monto:</strong> S/ {{ number_format($venta->total, 2) }}</div>
            @endif
            @if($venta->usuario)
            <div style="margin-top:8px;"><strong>Vendedor:</strong> {{ $venta->usuario->full_name ?? $venta->usuario->name }}</div>
            @endif
        </div>

        <!-- Se elimina QR y datos bancarios -->

        <!-- Footer -->
        <div class="footer">
            <div style="font-weight:700;">¡Gracias por su compra!</div>
            <div style="margin-top:6px;">
                <strong>POLÍTICA DE DEVOLUCIÓN:</strong> Las devoluciones solo se aceptan el mismo día
                de la compra con boleta original. Productos en perfecto estado.
            </div>
            <div style="margin-top:10px"><strong>CONDICIÓN DE PAGO:</strong> Contado</div>
            <div>REPRESENTACIÓN IMPRESA DE LA BOLETA DE VENTA ELECTRÓNICA</div>
            <div>Fecha de impresión: {{ now()->format('d/m/Y H:i:s') }}</div>
        </div>
    </div>

    <script>
        // Impresión controlada desde el POS; no auto-imprimir aquí para evitar dobles.
    </script>
</body>
</html>