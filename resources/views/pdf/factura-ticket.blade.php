<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Electrónica - Ticket</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
        }
        
        .center {
            text-align: center;
        }
        
        .left {
            text-align: left;
        }
        
        .right {
            text-align: right;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .company-header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
        }
        
        .company-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .company-ruc {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .company-address {
            font-size: 9px;
            line-height: 1.1;
        }
        
        .document-info {
            text-align: center;
            margin: 10px 0;
            border: 1px solid #000;
            padding: 5px;
        }
        
        .document-type {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .document-number {
            font-size: 12px;
            font-weight: bold;
        }
        
        .section {
            margin: 8px 0;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        
        .section:last-child {
            border-bottom: none;
        }
        
        .client-info {
            font-size: 9px;
        }
        
        .client-row {
            margin-bottom: 2px;
        }
        
        .items-header {
            font-size: 9px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-bottom: 3px;
        }
        
        .item {
            font-size: 9px;
            margin-bottom: 3px;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 2px;
        }
        
        .item-description {
            font-weight: bold;
            margin-bottom: 1px;
        }
        
        .item-details {
            display: table;
            width: 100%;
        }
        
        .item-left {
            display: table-cell;
            width: 60%;
        }
        
        .item-right {
            display: table-cell;
            width: 40%;
            text-align: right;
        }
        
        .totals {
            font-size: 9px;
            margin-top: 5px;
        }
        
        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 2px;
        }
        
        .total-label {
            display: table-cell;
            width: 60%;
        }
        
        .total-value {
            display: table-cell;
            width: 40%;
            text-align: right;
        }
        
        .total-final {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 3px;
        }
        
        .qr-section {
            text-align: center;
            margin: 10px 0;
        }
        
        .qr-code {
            margin: 5px 0;
        }
        
        .footer-info {
            font-size: 8px;
            text-align: center;
            line-height: 1.1;
        }
        
        .hash {
            font-size: 7px;
            word-break: break-all;
            margin: 5px 0;
        }
        
        .observations {
            font-size: 8px;
            margin-top: 5px;
            text-align: left;
        }
        
        .separator {
            text-align: center;
            margin: 5px 0;
            font-size: 8px;
        }
    </style>
</head>
<body>
    
    <div class="company-header">
        <div class="company-name">{{ $company->razon_social }}</div>
        <div class="company-ruc">RUC: {{ $company->ruc }}</div>
        <div class="company-address">
            {{ $company->direccion }}<br>
            {{ $company->distrito }}, {{ $company->provincia }}<br>
            @if($company->telefono)Tel: {{ $company->telefono }}<br>@endif
            @if($company->email){{ $company->email }}@endif
        </div>
    </div>

    
    <div class="document-info">
        <div class="document-type">FACTURA ELECTRÓNICA</div>
        <div class="document-number">{{ $invoice->serie }}-{{ $invoice->numero }}</div>
    </div>

    
    <div class="section">
        <div class="center">
            <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($invoice->fecha_emision)->format('d/m/Y H:i') }}<br>
            <strong>Moneda:</strong> {{ $invoice->tipo_moneda == 'PEN' ? 'Soles' : 'Dólares' }}
        </div>
    </div>

    
    <div class="section client-info">
        <div class="bold center">DATOS DEL CLIENTE</div>
        <div class="client-row">
            <strong>Cliente:</strong><br>{{ $invoice->cliente_razon_social }}
        </div>
        <div class="client-row">
            <strong>{{ $invoice->cliente_tipo_documento == '6' ? 'RUC' : 'DNI' }}:</strong> {{ $invoice->cliente_numero_documento }}
        </div>
        @if($invoice->cliente_direccion)
        <div class="client-row">
            <strong>Dirección:</strong><br>{{ $invoice->cliente_direccion }}
        </div>
        @endif
    </div>

    
    <div class="section">
        <div class="items-header center">DETALLE DE PRODUCTOS/SERVICIOS</div>
        @foreach($invoice->details as $detail)
        <div class="item">
            <div class="item-description">{{ $detail->descripcion }}</div>
            <div class="item-details">
                <div class="item-left">
                    @if($detail->codigo_interno)Cód: {{ $detail->codigo_interno }}<br>@endif
                    {{ number_format($detail->cantidad, 2) }} {{ $detail->unidad_medida }}
                    @if($detail->descuento > 0)
                        <br>Desc: {{ number_format($detail->descuento, 2) }}
                    @endif
                </div>
                <div class="item-right">
                    {{ number_format($detail->valor_unitario, 2) }}<br>
                    <strong>{{ number_format($detail->valor_venta_con_descuento, 2) }}</strong>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    
    <div class="section totals">
        @if($invoice->total_operaciones_gravadas > 0)
        <div class="total-row">
            <div class="total-label">Op. Gravadas:</div>
            <div class="total-value">{{ number_format($invoice->total_operaciones_gravadas, 2) }}</div>
        </div>
        @endif
        @if($invoice->total_operaciones_inafectas > 0)
        <div class="total-row">
            <div class="total-label">Op. Inafectas:</div>
            <div class="total-value">{{ number_format($invoice->total_operaciones_inafectas, 2) }}</div>
        </div>
        @endif
        @if($invoice->total_operaciones_exoneradas > 0)
        <div class="total-row">
            <div class="total-label">Op. Exoneradas:</div>
            <div class="total-value">{{ number_format($invoice->total_operaciones_exoneradas, 2) }}</div>
        </div>
        @endif
        @if($invoice->total_descuentos > 0)
        <div class="total-row">
            <div class="total-label">Descuentos:</div>
            <div class="total-value">{{ number_format($invoice->total_descuentos, 2) }}</div>
        </div>
        @endif
        @if($invoice->total_igv > 0)
        <div class="total-row">
            <div class="total-label">IGV (18%):</div>
            <div class="total-value">{{ number_format($invoice->total_igv, 2) }}</div>
        </div>
        @endif
        @if($invoice->total_isc > 0)
        <div class="total-row">
            <div class="total-label">ISC:</div>
            <div class="total-value">{{ number_format($invoice->total_isc, 2) }}</div>
        </div>
        @endif
        @if($invoice->total_otros_tributos > 0)
        <div class="total-row">
            <div class="total-label">Otros Tributos:</div>
            <div class="total-value">{{ number_format($invoice->total_otros_tributos, 2) }}</div>
        </div>
        @endif
        
        <div class="total-row total-final">
            <div class="total-label">TOTAL A PAGAR:</div>
            <div class="total-value">{{ $invoice->tipo_moneda }} {{ number_format($invoice->precio_total, 2) }}</div>
        </div>
    </div>

    
    @if($invoice->qr || $invoice->hash)
    <div class="section qr-section">
        @if($invoice->qr)
        <div class="qr-code">
            <img src="data:image/png;base64,{{ $invoice->qr }}" alt="QR" style="width: 80px; height: 80px;">
        </div>
        @endif
        @if($invoice->hash)
        <div class="hash">
            <strong>Hash:</strong><br>{{ $invoice->hash }}
        </div>
        @endif
    </div>
    @endif

    
    <div class="section footer-info">
        <div class="bold">Representación impresa de la</div>
        <div class="bold">Factura Electrónica</div>
        <div>Consulte en: www.sunat.gob.pe</div>
        <br>
        <div><strong>Estado SUNAT:</strong> {{ $invoice->estado_sunat }}</div>
        @if($invoice->fecha_envio_sunat)
        <div><strong>Enviado:</strong> {{ \Carbon\Carbon::parse($invoice->fecha_envio_sunat)->format('d/m/Y H:i') }}</div>
        @endif
    </div>

    @if($invoice->observaciones)
    <div class="observations">
        <strong>Observaciones:</strong><br>
        {{ $invoice->observaciones }}
    </div>
    @endif

    <div class="separator">
        ================================
    </div>
    <div class="center" style="font-size: 8px;">
        Gracias por su preferencia
    </div>
</body>
</html>