<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta Electrónica A4 - {{ $venta->numero_venta }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .page-break { page-break-before: always; }
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #fff;
        }
        
        .boleta-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #dc2626;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 8px;
        }
        
        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1.3;
        }
        
        .boleta-info {
            background: #fee2e2;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #dc2626;
            text-align: center;
            min-width: 200px;
        }
        
        .boleta-title {
            font-size: 16px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 5px;
        }
        
        .boleta-number {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .client-info {
            background: #fff7ed;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .client-title {
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background: #dc2626;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        
        .items-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .totals-table {
            width: 300px;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
        }
        
        .totals-table .label {
            background: #f8f9fa;
            font-weight: bold;
            text-align: right;
        }
        
        .totals-table .total-final {
            background: #dc2626;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        
        .payment-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #dc2626;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .qr-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        
        .legal-info {
            font-size: 9px;
            color: #666;
            line-height: 1.2;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Botón de imprimir removido por requerimiento: descarga directa en PDF -->
    
    <div class="boleta-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div style="display:flex; align-items:center; gap:12px;">
                    <img src="/assets/images/logotipo.png" alt="Logo" style="width:58px; height:58px; object-fit:contain;">
                    <div>
                        <div class="company-name">BOTICA SAN ANTONIO</div>
                        <div class="company-details">
                            <strong>Dirección:</strong> AV. FERROCARRIL 188, CHILCA 12003<br>
                            <strong>Correo:</strong> BOTICA@SANANTONIO.COM
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="boleta-info">
                <div class="boleta-title">BOLETA ELECTRÓNICA</div>
                <div class="boleta-number">{{ $venta->numero_sunat ?? $venta->numero_venta }}</div>
                <div style="font-size: 11px; color: #666;">
            Fecha: {{ optional($venta->created_at)->format('d/m/Y') ?? '-' }}<br>
            Hora: {{ optional($venta->created_at)->format('H:i:s') ?? '-' }}
                </div>
            </div>
        </div>
        
        <!-- Client Info -->
        <div class="client-info">
            <div class="client-title">DATOS DEL CLIENTE</div>
            @if($venta->cliente)
                <strong>Nombre:</strong> {{ $venta->cliente->nombre }}<br>
                @if($venta->cliente->documento)
                <strong>Documento:</strong> {{ $venta->cliente->documento }}<br>
                @endif
                <strong>Dirección:</strong> HUANCAYO<br>
            @else
                <strong>Cliente:</strong> Cliente General<br>
                <strong>Dirección:</strong> HUANCAYO
            @endif
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%">#</th>
                    <th style="width: 45%">DESCRIPCIÓN</th>
                    <th style="width: 12%" class="text-center">CANTIDAD</th>
                    <th style="width: 15%" class="text-right">P. UNIT.</th>
                    <th style="width: 20%" class="text-right">SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $index => $detalle)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        @php
                            $p = $detalle->producto;
                            $descParts = array_filter([
                                $p->nombre ?? null,
                                $p->concentracion ?? null,
                                $p->presentacion ?? null,
                            ]);
                            $descripcionCompleta = trim(implode(' ', $descParts));
                        @endphp
                        <strong>{{ $descripcionCompleta ?: ($detalle->producto->nombre ?? 'ITEM') }}</strong>
                        @if(!empty($p->codigo))
                        <br><small style="color: #666;">Código: {{ $p->codigo }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $detalle->cantidad }} {{ $detalle->cantidad == 1 ? 'UNIDAD' : 'UNIDADES' }}</td>
                    <td class="text-right">S/. {{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td class="text-right">S/. {{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="text-right">S/. {{ number_format($venta->subtotal, 2) }}</td>
                </tr>
                @if($venta->descuento > 0)
                <tr>
                    <td class="label">Descuento:</td>
                    <td class="text-right">-S/. {{ number_format($venta->descuento, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">IGV (18%):</td>
                    <td class="text-right">S/. {{ number_format($venta->igv, 2) }}</td>
                </tr>
                <tr class="total-final">
                    <td class="label">TOTAL A PAGAR:</td>
                    <td class="text-right">S/. {{ number_format($venta->total, 2) }}</td>
                </tr>
            </table>
        </div>
        
        <!-- Payment Info -->
        @if($venta->metodo_pago)
        <div class="payment-info">
            <strong>INFORMACIÓN DE PAGO</strong><br>
            <strong>Método de pago:</strong> {{ ucfirst($venta->metodo_pago) }}
            @if($venta->monto_recibido > 0)
            <br><strong>Monto recibido:</strong> S/. {{ number_format($venta->monto_recibido, 2) }}
            <br><strong>Vuelto:</strong> S/. {{ number_format($venta->vuelto, 2) }}
            @endif
        </div>
        @endif
        
        @php
            $qrCadena = null;
            if (!empty($venta->nube_data)) {
                try {
                    $data = is_array($venta->nube_data) ? $venta->nube_data : json_decode($venta->nube_data, true);
                    $qrCadena = $data['cadena_para_codigo_qr'] ?? null;
                } catch (\Throwable $e) {}
            }
        @endphp
        @if($qrCadena)
        <div class="qr-section">
            <div>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($qrCadena) }}" alt="QR" style="width:120px;height:120px;" />
                @if(!empty($venta->codigo_hash))
                <div class="legal-info" style="margin-top:6px; word-break:break-all;">Hash: {{ $venta->codigo_hash }}</div>
                @endif
            </div>
            <div class="legal-info">
                Representación impresa de boleta electrónica.<br>
                Consulte el comprobante en SUNAT usando el código QR.
            </div>
        </div>
        @endif
        
        <!-- Footer -->
        <div class="footer">
            <div style="margin-bottom: 10px;">
                <strong>¡GRACIAS POR SU COMPRA!</strong>
            </div>
            <div>
                Documento generado el {{ now()->format('d/m/Y H:i:s') }} | Sistema POS Botica Profesional v2.0
            </div>
            <div style="margin-top: 5px; font-size: 8px;">
                Este documento ha sido generado electrónicamente y es válido sin firma ni sello
            </div>
        </div>
    </div>
</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-YcsIPiJr7G1Vv9skJmZCw1p6Nw0CezrV1BqfIuTaFvH0qC1oWw/3bTxQH3WwqYbG4htA8CwCwLkWCOxZg2nG3A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    // Descargar automáticamente como PDF al cargar la página
    (function() {
        const contenedor = document.querySelector('.boleta-container');
        if (!contenedor) return;
        const numero = '{{ $venta->numero_venta }}';
        const nombreArchivo = `Boleta-${numero}.pdf`;
        const opt = {
            margin:       10,
            filename:     nombreArchivo,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        // Pequeña espera para asegurar que las imágenes carguen
        setTimeout(() => html2pdf().set(opt).from(contenedor).save(), 400);
    })();
</script>
</html>
