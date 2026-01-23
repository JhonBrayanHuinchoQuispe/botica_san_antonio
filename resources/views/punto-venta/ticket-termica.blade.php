<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Botica San Antonio</title>
    <style>
        @media screen {
            body {
                margin: 0;
                padding: 0;
                overflow: hidden;
                background: white !important;
                font-family: 'Arial', sans-serif;
                font-size: 12px;
                line-height: 1.3;
                width: 80mm;
                color: #000;
                word-break: break-word;
                overflow-wrap: anywhere;
            }
            
            body::before,
            body::after {
                display: none !important;
            }
            
            body > *:not(.ticket-container) {
                display: none !important;
                visibility: hidden !important;
            }
            
            .ticket-container {
                width: 72mm;
                padding: 4mm;
                background: white;
                border: none;
                box-shadow: none;
                font-size: 12px;
                line-height: 1.3;
                box-sizing: border-box;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 99999;
                height: 100vh;
                overflow: auto;
            }
        }
        
        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            
            html, body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                margin: 0;
                padding: 0;
                background: white !important;
                font-family: 'Arial', sans-serif;
                font-size: 12px;
                line-height: 1.3;
                width: 80mm;
                color: #000;
                word-break: break-word;
                overflow-wrap: anywhere;
            }
            
            .ticket-container {
                width: 72mm;
                background: white;
                padding: 4mm;
                margin: 0 auto;
                border: none;
                box-shadow: none;
                font-size: 12px;
                line-height: 1.3;
                box-sizing: border-box;
                position: static;
            }
        }
        @if(!empty($modoPdf))
        html, body {
            margin: 0;
            padding: 0;
            background: white !important;
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.3;
            width: 80mm;
            color: #000;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .ticket-container {
            width: 72mm;
            background: white;
            padding: 4mm;
            margin: 0 auto;
            border: none;
            box-shadow: none;
            font-size: 12px;
            line-height: 1.3;
            box-sizing: border-box;
            position: static;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        @endif
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        
        .company-info {
            font-size: 11px;
            margin-bottom: 2px;
        }
        
        .ticket-info {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #000;
        }
        .ticket-title {
            font-weight: bold;
            font-size: 13px;
        }
        
        .ticket-number {
            font-weight: bold;
            font-size: 16px;
        }
        
        .items-section {
            margin-bottom: 8px;
            padding-top: 8px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 12px;
        }
        .items-table th,
        .items-table td {
            padding: 5px 3px;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .items-table thead th {
            border-bottom: 1px solid #000;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
            font-size: 11px;
            padding: 5px 6px;
        }
        .items-table tbody td:last-child {
            text-align: right;
        }
        .text-right { text-align: right; }
        .client-block {
            text-align: left;
            font-size: 12px;
            margin-top: 6px;
        }
        .client-label { font-weight: 700; }
        
        .item {
            margin-bottom: 4px;
            font-size: 12px;
        }
        
        .item-name {
            font-weight: bold;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
        }
        
        .totals-section {
            border-top: 1px dashed #000;
            padding-top: 8px;
            margin-top: 12px;
            font-size: 12px;
        }
        
        .total-line {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            margin-bottom: 2px;
        }
        .total-line span:last-child { text-align: right; }
        .total-label { font-weight: 700; }
        
        .total-final {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #000;
            padding-top: 6px;
            margin-top: 6px;
        }
        
        .footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px dashed #000;
            font-size: 12px;
        }
        
        .print-button {
            display: none; 
        }
    </style>
</head>
<body>

    
    <div class="ticket-container">
        
        <div class="header">
            @php
                $logoSrc = !empty($configuracion) && !empty($configuracion->logo_empresa)
                    ? asset('storage/' . ltrim($configuracion->logo_empresa, '/'))
                    : asset('assets/images/logotipo.png');
                $nombreEmpresa = !empty($configuracion) && !empty($configuracion->nombre_empresa) ? $configuracion->nombre_empresa : 'Botica San Antonio';
                $direccionEmpresa = !empty($configuracion) && !empty($configuracion->direccion_empresa) ? $configuracion->direccion_empresa : 'Av. Ferrocarril 118, Chilca 12003';
                $emailEmpresa = !empty($configuracion) && !empty($configuracion->email_empresa) ? $configuracion->email_empresa : 'BOTICA@SANANTONIO.COM';
                $mostrarLogo = empty($configuracion) ? true : (bool)$configuracion->ticket_mostrar_logo;
                $mostrarDireccion = empty($configuracion) ? true : (bool)$configuracion->ticket_mostrar_direccion;
            @endphp
            @if($mostrarLogo)
                <img src="{{ $logoSrc }}" alt="Logo" style="width:64px;height:64px;object-fit:contain;display:block;margin:0 auto 8px;">
            @endif
            <div class="company-name">{{ strtoupper($nombreEmpresa) }}</div>
            @if($mostrarDireccion)
                <div class="company-info">{{ $direccionEmpresa }}</div>
            @endif
            <div class="company-info">{{ strtoupper($emailEmpresa) }}</div>
        </div>
        
        
        <div class="ticket-info">
            <div class="ticket-title">BOLETA SIMPLE</div>
            <div class="ticket-number">N° {{ $venta->numero_venta }}</div>
            @php
                $cliNombre = optional($venta->cliente)->nombre_completo ?? null;
                $cliDoc = optional($venta->cliente)->dni ?? null;
                $cliTel = optional($venta->cliente)->telefono ?? null;
                $mostrarDefaults = empty($cliNombre) && empty($cliDoc) && empty($cliTel);
            @endphp
            <div class="client-block">
                @if($venta->usuario)
                <div class="client-row">
                    <span class="client-label">Vendedor:</span>
                    <span class="client-value">{{ $venta->usuario->full_name ?? $venta->usuario->name }}</span>
                </div>
                @endif
                @if(!empty($cliNombre) || $mostrarDefaults)
                <div class="client-row">
                    <span class="client-label">Cliente:</span>
                    <span class="client-value">{{ $cliNombre ?? 'Cliente General' }}</span>
                </div>
                @endif
                @if(!empty($cliDoc) || $mostrarDefaults)
                <div class="client-row">
                    <span class="client-label">Documento:</span>
                    <span class="client-value">{{ $cliDoc ?? '999999999' }}</span>
                </div>
                @endif
                @if(!empty($cliTel))
                <div class="client-row">
                    <span class="client-label">Telf.:</span>
                    <span class="client-value">{{ $cliTel }}</span>
                </div>
                @endif
                <div class="client-row">
                    <span class="client-label">Fecha Emi.:</span>
                    <span class="client-value">{{ optional($venta->created_at)->format('d/m/Y H:i') ?? '-' }}</span>
                </div>
            </div>
        </div>
        
        
        <div class="items-section">
            <table class="items-table">
                <colgroup>
                <col style="width:12%">
                <col style="width:54%">
                <col style="width:16%">
                <col style="width:18%">
                </colgroup>
                <thead>
                    <tr>
                        <th>CANT.</th>
                        <th>PRODUCTO</th>
                        <th>P.U.</th>
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $detalle)
                    <tr>
                        <td>{{ number_format($detalle->cantidad, 0) }}</td>
                        <td>{{ $detalle->producto->nombre }}</td>
                        <td class="text-right">{{ number_format($detalle->precio_unitario, 2) }}</td>
                        <td class="text-right">{{ number_format($detalle->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        
        <div class="totals-section">
            @php
                $subTotal = isset($venta->subtotal) ? (float)$venta->subtotal : (isset($venta->total) ? (float)$venta->total : 0);
                $descMonto = isset($venta->descuento) ? (float)$venta->descuento : (isset($venta->descuento_monto) ? (float)$venta->descuento_monto : 0);
                $descPct = isset($venta->porcentaje_descuento) ? (float)$venta->porcentaje_descuento : (isset($venta->descuento_porcentaje) ? (float)$venta->descuento_porcentaje : 0);
                $igvMonto = isset($venta->igv) ? (float)$venta->igv : 0;
            @endphp
            <div class="total-line">
                <span class="total-label">SUB TOTAL</span>
                <span class="text-right">S/. {{ number_format($subTotal, 2) }}</span>
            </div>
            @if($descMonto > 0)
            <div class="total-line">
                <span class="total-label">{{ $descPct > 0 ? ('DESCUENTO (' . number_format($descPct, 0) . '%)') : 'DESCUENTO' }}</span>
                <span class="text-right">-S/. {{ number_format($descMonto, 2) }}</span>
            </div>
            @endif
            <div class="total-line">
                <span class="total-label">I.G.V</span>
                <span class="text-right">S/. {{ number_format($igvMonto, 2) }}</span>
            </div>
            <div class="total-line total-final">
                <span>TOTAL</span>
                <span class="text-right">S/. {{ number_format($venta->total, 2) }}</span>
            </div>
        </div>
        
        
        @if($venta->metodo_pago)
        <div style="text-align: left; margin-top: 8px; font-size: 12px;">
            <strong>Met. Pago:</strong> {{ ucfirst($venta->metodo_pago) }}
            @if($venta->metodo_pago == 'efectivo' && $venta->efectivo_recibido > 0)
            <br><strong>Recibido:</strong> S/. {{ number_format($venta->efectivo_recibido, 2) }}
            <br><strong>Vuelto:</strong> S/. {{ number_format($venta->vuelto, 2) }}
            @elseif(in_array($venta->metodo_pago, ['tarjeta', 'yape']))
            <br><strong>Monto:</strong> S/. {{ number_format($venta->total, 2) }}
            @endif
            @if($venta->usuario)
            <br><strong>Vendedor:</strong> {{ $venta->usuario->full_name ?? $venta->usuario->name }}
            @endif
        </div>
        @endif
        
        
        <div class="footer">
            <div style="font-weight: 700; font-size: 12px;">¡Gracias por su compra!</div>
            <div style="margin-top: 8px; font-size: 12px; border-top: 1px dashed #000; padding-top: 5px;">
                <strong>POLÍTICA DE DEVOLUCIÓN:</strong><br>
                Las devoluciones solo se aceptan el mismo día<br>
                de la compra con boleta original.<br>
                Productos en perfecto estado.
            </div>
        </div>

        @php
            $qrCadena = null;
            if (!empty($venta->nube_data)) {
                try { $data = is_array($venta->nube_data) ? $venta->nube_data : json_decode($venta->nube_data, true); $qrCadena = $data['cadena_para_codigo_qr'] ?? null; } catch (\Throwable $e) {}
            }
        @endphp
        @if($qrCadena)
        <div style="text-align:center; margin-top:10px;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=110x110&data={{ urlencode($qrCadena) }}" alt="QR" style="width:110px;height:110px;" />
            @if(!empty($venta->codigo_hash))
            <div style="font-size:10px; margin-top:4px; word-break:break-all;">Hash: {{ $venta->codigo_hash }}</div>
            @endif
        </div>
        @endif
    </div>
    
</body>
</html>
